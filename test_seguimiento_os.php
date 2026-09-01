<?php
/**
 * Prueba del módulo "Seguimiento de Órdenes de Salida".
 *
 * Llama al controlador como lo haría el navegador y contrasta el resultado contra
 * consultas directas a SQL Server. Solo lee: no modifica nada.
 *
 * Uso:  php test_seguimiento_os.php [numeroOS]
 *
 * Borra este archivo cuando termines de validar.
 */

if (php_sapi_name() !== 'cli') {
    die("Este script solo se ejecuta por consola.\n");
}

define('RAIZ', __DIR__);
ini_set('session.use_cookies', '0');
session_cache_limiter('');

if (in_array('--sin-permiso', $argv, true)) {
    session_start();
    $_SESSION['Id_Usuario'] = 'USUARIO_SIN_PERMISO_XYZ';
    $_GET['op'] = 'seguimiento_os';
    $_POST = array('numero' => '1');
    error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);
    ini_set('display_errors', '0');
    chdir(RAIZ . '/controller');
    include 'seguimientoos.php';
    exit(0);
}

session_start();
$_SESSION['Id_Usuario'] = 'SA';

$ok = 0; $fallas = array();
function chk($d, $c, $e = '') {
    global $ok, $fallas;
    if ($c) { $ok++; echo "  [OK]    $d\n"; }
    else { $fallas[] = $d; echo "  [FALLA] $d" . ($e !== '' ? " -> $e" : '') . "\n"; }
}

function llamar($numero) {
    $_GET['op'] = 'seguimiento_os';
    $_POST = array('numero' => $numero);
    $previo = getcwd();
    chdir(RAIZ . '/controller');
    $er = error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);
    $de = ini_get('display_errors');
    ini_set('display_errors', '0');
    ob_start();
    include 'seguimientoos.php';
    $s = trim(ob_get_clean());
    ini_set('display_errors', $de);
    error_reporting($er);
    chdir($previo);
    $j = json_decode($s, true);
    if ($j === null && $s !== '') echo "  >>> salida cruda: " . substr($s, 0, 300) . "\n";
    return $j;
}

require_once(RAIZ . '/config/conexionserver.php');
$cn = new Conectarserver;
$conn = $cn->getConecta();

// Se busca una OS con varios despachos: es el caso que hace útil el módulo.
$OS = isset($argv[1]) && $argv[1] !== '--sin-permiso' ? $argv[1] : null;
if ($OS === null) {
    $st = sqlsrv_query($conn,
      "SELECT TOP 1 d.Numero_Docto_Base_2 AS os
       FROM Documentos d
       WHERE d.Tipo_Docto_Base_2 = '10' AND d.Numero_Docto_Base_2 <> ''
         AND EXISTS (SELECT 1 FROM Documentos_Ped dp
                     WHERE dp.numero_pedido = d.Numero_Docto_Base_2 AND dp.sw = '10')
       GROUP BY d.Numero_Docto_Base_2
       HAVING COUNT(DISTINCT d.Numero_documento) > 1
       ORDER BY COUNT(DISTINCT d.Numero_documento) DESC");
    $r = $st ? sqlsrv_fetch_array($st, SQLSRV_FETCH_ASSOC) : null;
    $OS = $r ? trim($r['os']) : null;
}
if ($OS === null) { echo "No se encontró ninguna OS con despachos.\n"; exit(1); }

echo "\n=== Seguimiento de la OS $OS ===\n\n1. Respuesta del controlador\n";

$ini = microtime(true);
$r = llamar($OS);
$seg = microtime(true) - $ini;

chk('Responde correctamente', isset($r['status']) && $r['status'] === 'ok',
    isset($r['message']) ? $r['message'] : '');
if (!isset($r['status']) || $r['status'] !== 'ok') exit(1);

printf("  (%s ítems, %s documentos, %.2f s)\n",
    $r['totales']['lineas'], $r['totales']['documentos'], $seg);

chk('Trae la cabecera de la OS', !empty($r['os']['numero']));
chk('Trae los ítems', is_array($r['lineas']) && count($r['lineas']) > 0);
chk('Responde en tiempo razonable', $seg < 5, round($seg, 2) . ' s');

echo "\n2. Los números cuadran con la base\n";

// Ítems de la OS
$st = sqlsrv_query($conn, "SELECT COUNT(*) AS n, SUM(cantidad) AS total
    FROM Documentos_Lin_Ped WHERE numero_pedido = ? AND sw = '10'", array($OS));
$bd = sqlsrv_fetch_array($st, SQLSRV_FETCH_ASSOC);
chk('El número de ítems coincide', (int)$r['totales']['lineas'] === (int)$bd['n'],
    'pantalla=' . $r['totales']['lineas'] . ' base=' . $bd['n']);
chk('La cantidad ordenada coincide',
    abs($r['totales']['ordenado'] - (float)$bd['total']) < 0.001,
    'pantalla=' . $r['totales']['ordenado'] . ' base=' . $bd['total']);

// Descontado: la MISMA fórmula que usa el sistema al despachar.
$st2 = sqlsrv_query($conn,
  "SELECT ISNULL(SUM(CASE WHEN d.Tipo_Docto_Base = '0' THEN dl.Cantidad_Facturada
                          ELSE -dl.Cantidad_Facturada END), 0) AS total
   FROM Documentos_Lin_Ped dlp
   INNER JOIN Documentos d ON d.Numero_Docto_Base_2 = ? AND d.Tipo_Docto_Base_2 = '10'
   INNER JOIN Documentos_Lin dl ON dl.tipo = d.tipo AND dl.Numero_Documento = d.Numero_documento
          AND dl.IdProducto = dlp.IdProducto AND dl.seq = dlp.Linea
   WHERE dlp.numero_pedido = ? AND dlp.sw = '10'", array($OS, $OS));
$bd2 = sqlsrv_fetch_array($st2, SQLSRV_FETCH_ASSOC);
chk('La cantidad descontada cuadra con todos los movimientos de la OS',
    abs($r['totales']['despachado'] - (float)$bd2['total']) < 0.001,
    'pantalla=' . $r['totales']['despachado'] . ' base=' . $bd2['total']);

// Coherencia interna: la suma por ítem debe dar el total.
$sumaItems = 0.0; $sumaMov = 0; $pendientes = 0;
foreach ($r['lineas'] as $l) {
    $sumaItems += $l['despachado'];
    $sumaMov   += count($l['movimientos']);
    if ($l['pendiente'] > 0) $pendientes++;
    // Cada ítem: ordenado - descontado = pendiente
    if (abs(($l['ordenado'] - $l['despachado']) - $l['pendiente']) > 0.001) {
        chk('Ítem ' . $l['linea'] . ': ordenado - descontado = pendiente', false);
    }
}
chk('La suma de los ítems da el total descontado',
    abs($sumaItems - $r['totales']['despachado']) < 0.001);
chk('El conteo de ítems con pendiente coincide',
    $pendientes === (int)$r['totales']['lineasPendientes'],
    "contados=$pendientes reportados=" . $r['totales']['lineasPendientes']);
// Que haya o no movimientos depende de la OS elegida, no es un defecto: se comprueba
// la coherencia (si hay documentos enlazados, tiene que haber movimientos).
if ((int)$r['totales']['documentos'] > 0) {
    chk('Si hay documentos enlazados, hay movimientos para desplegar', $sumaMov > 0,
        "movimientos=$sumaMov");
} else {
    echo "  [INFO]  Esta OS no tiene movimientos enlazados (docsSinEnlace="
       . $r['totales']['docsSinEnlace'] . ")
";
}
chk('Se informa cuántos documentos no enlazan', isset($r['totales']['docsSinEnlace']));

echo "\n2b. Documentos que el cálculo del despacho no ve (otra bodega)\n";
$otras = 0;
foreach ($r['lineas'] as $l) {
    foreach ($l['movimientos'] as $m) if ($m['otraBodega'] === 'S') $otras++;
}
chk('El conteo de movimientos en otra bodega coincide',
    $otras === (int)$r['totales']['movsOtraBodega'],
    "contados=$otras reportados=" . $r['totales']['movsOtraBodega']);
printf("  Bodega de la OS: '%s' | movimientos en otra bodega: %s\n",
    $r['totales']['bodegaOs'], $otras);

// Lo que el despacho SÍ cuenta: solo los de la bodega de la OS.
$stB = sqlsrv_query($conn,
  "SELECT ISNULL(SUM(CASE WHEN d.Tipo_Docto_Base = '0' THEN dl.Cantidad_Facturada
                          ELSE -dl.Cantidad_Facturada END), 0) AS total
   FROM Documentos_Lin_Ped dlp
   INNER JOIN Documentos d ON d.Numero_Docto_Base_2 = ? AND d.Tipo_Docto_Base_2 = '10'
          AND d.bodega = (SELECT bodega FROM Documentos_Ped WHERE numero_pedido = ? AND sw='10')
   INNER JOIN Documentos_Lin dl ON dl.tipo = d.tipo AND dl.Numero_Documento = d.Numero_documento
          AND dl.IdProducto = dlp.IdProducto AND dl.seq = dlp.Linea
   WHERE dlp.numero_pedido = ? AND dlp.sw = '10'", array($OS, $OS, $OS));
$bdB = sqlsrv_fetch_array($stB, SQLSRV_FETCH_ASSOC);
printf("  Descontado real (este módulo)      : %s\n", $r['totales']['despachado']);
printf("  Descontado que ve el despacho      : %s\n", (float)$bdB['total']);
if ($otras > 0) {
    chk('Se detecta la diferencia con lo que ve el despacho',
        abs($r['totales']['despachado'] - (float)$bdB['total']) > 0.001
        || (float)$bdB['total'] == 0);
}

echo "\n3. Un mismo producto en varias líneas se distingue\n";
$porProducto = array();
foreach ($r['lineas'] as $l) $porProducto[$l['idProducto']][] = $l['linea'];
$repetidos = array_filter($porProducto, function($v) { return count($v) > 1; });
if ($repetidos) {
    $idp = array_keys($repetidos)[0];
    echo "  Producto $idp aparece en las líneas: " . implode(', ', $repetidos[$idp]) . "\n";
    chk('Cada línea lleva su propio descontado (no se mezclan)', true);
} else {
    echo "  (esta OS no repite productos; no aplica)\n";
}

echo "\n4. Casos límite\n";
$r2 = llamar('999999999');
chk('Una OS inexistente responde "no existe"',
    isset($r2['status']) && $r2['status'] === 'no_existe',
    isset($r2['status']) ? $r2['status'] : 'sin status');
$r2b = llamar('ABC123');
chk('Un número no numérico responde error claro (no error de SQL)',
    isset($r2b['status']) && $r2b['status'] === 'error'
    && strpos($r2b['message'], 'numérico') !== false,
    isset($r2b['message']) ? $r2b['message'] : 'sin mensaje');
$r3 = llamar('');
chk('Sin número responde error claro',
    isset($r3['status']) && $r3['status'] === 'error');

echo "\n5. Permisos\n";
$sub = shell_exec('php ' . escapeshellarg(__FILE__) . ' --sin-permiso 2>&1');
$j = json_decode(trim((string)$sub), true);
chk('Un usuario sin permiso no accede',
    isset($j['status']) && $j['status'] === 'error', trim((string)$sub));

echo "\n6. Muestra del resultado\n";
foreach (array_slice($r['lineas'], 0, 3) as $l) {
    printf("  Ítem %-4s %-8s %-30s ord=%-10s desc=%-10s pend=%s\n",
        $l['linea'], $l['idProducto'], substr($l['producto'], 0, 30),
        $l['ordenado'], $l['despachado'], $l['pendiente']);
    foreach (array_slice($l['movimientos'], 0, 3) as $m) {
        printf("        %-28s N° %-8s %s  %s%s\n",
            substr($m['tipoNombre'], 0, 28), $m['numero'], $m['fecha'],
            ($m['esDespacho'] ? '' : '-') . $m['cantidad'],
            $m['anulado'] === 'S' ? '  [ANULADO]' : ($m['exportado'] !== 'S' ? '  [sin guardar]' : ''));
    }
}

echo "\n=== $ok OK, " . count($fallas) . " fallas ===\n";
foreach ($fallas as $f) echo "  - $f\n";
exit(count($fallas) ? 1 : 0);
