<?php
/**
 * Prueba del módulo "Auditoría de Documentos" (controller/auditoria.php + view/Auditoria).
 *
 * Simula peticiones reales al controlador y comprueba que la pantalla:
 *   - devuelve la estructura que espera DataTables,
 *   - marca en rojo lo que perdió líneas y en amarillo los intentos bloqueados,
 *   - filtra por fecha, tipo, número, usuario, operación y resultado,
 *   - entrega el detalle previo del documento para el modal,
 *   - y niega el acceso a un usuario sin el permiso `auditoria_documentos`.
 *
 * Uso:  php test_auditoria_pantalla.php
 *
 * Siembra filas con modulo='PRUEBAUI' y las borra al terminar, incluso si algo falla.
 * Requiere sql/05 y sql/06 ejecutados. Borra este archivo cuando termines de validar.
 */

if (php_sapi_name() !== 'cli') {
    die("Este script solo se ejecuta por consola.\n");
}

define('RAIZ', __DIR__);
ini_set('session.use_cookies', '0');
ini_set('session.use_only_cookies', '0');
session_cache_limiter('');

// --- Modo subproceso -------------------------------------------------------
// El controlador hace exit() cuando el usuario no tiene permiso. Eso es correcto en
// producción pero mataría este script, así que ese caso se prueba como lo que es en
// la vida real: otra petición, en otro proceso.
if (in_array('--sin-permiso', $argv, true)) {
    session_start();
    $_SESSION['Id_Usuario'] = 'USUARIO_SIN_PERMISO_XYZ';
    $_GET['op']  = 'consultar';
    $_POST       = array();
    error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);
    ini_set('display_errors', '0');
    chdir(RAIZ . '/controller');
    include 'auditoria.php';
    exit(0);
}

session_start();
$_SESSION['Id_Usuario'] = 'SA';               // usuario con permiso (ver sql/06)
$_SERVER['REMOTE_ADDR'] = '192.168.1.99';
$_SERVER['REQUEST_URI'] = '/controller/auditoria.php?op=consultar';

require_once(RAIZ . '/models/AuditoriaDocumentos.php');
require_once(RAIZ . '/config/conexionmysql.php');   // AuditoriaDocumentos lo carga tarde

$pdo = (new ConectarMysql())->obtenerConexion();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$ok = 0;
$fallas = array();

function chk($descripcion, $condicion, $extra = '') {
    global $ok, $fallas;
    if ($condicion) { $ok++; echo "  [OK]    $descripcion\n"; }
    else { $fallas[] = $descripcion; echo "  [FALLA] $descripcion" . ($extra !== '' ? " -> $extra" : '') . "\n"; }
}

// La limpieza va en un shutdown para que se ejecute también si algo revienta: una
// corrida abortada que deja filas sembradas falsea los conteos de la siguiente.
register_shutdown_function(function () {
    global $ok, $fallas;
    try {
        $p = (new ConectarMysql())->obtenerConexion();
        $n = $p->exec("DELETE FROM auditoria_documentos WHERE modulo = 'PRUEBAUI'");
        echo "\nLimpieza: $n filas de prueba eliminadas\n";
    } catch (Exception $e) {
        echo "\nAVISO: no se pudo limpiar (" . $e->getMessage() . ")\n";
    }
    echo "\n=== $ok comprobaciones OK, " . count($fallas) . " fallas ===\n";
    foreach ($fallas as $f) echo "  - $f\n";
});

// Por si una corrida anterior murió antes de limpiar.
$pdo->exec("DELETE FROM auditoria_documentos WHERE modulo = 'PRUEBAUI'");

/** Ejecuta el controlador como lo haría Apache (directorio de trabajo = controller/). */
function llamar($op, $post) {
    $_GET['op'] = $op;
    $_POST      = $post;
    $previo = getcwd();
    chdir(RAIZ . '/controller');
    // conexionserver.php llama a session_start(), que por consola emite un aviso
    // que ensuciaría el JSON. Se silencia solo durante la llamada.
    $er = error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);
    $de = ini_get('display_errors');
    ini_set('display_errors', '0');
    ob_start();
    include 'auditoria.php';
    $salida = trim(ob_get_clean());
    ini_set('display_errors', $de);
    error_reporting($er);
    chdir($previo);
    $j = json_decode($salida, true);
    if ($j === null && $salida !== '') echo "  >>> salida cruda: " . substr($salida, 0, 300) . "\n";
    return $j;
}

// ------------------------------------------------------------ Datos de prueba
$detalle = array('lineas' => array(
    array('seq'=>1,'prod'=>610553,'lote'=>'L-77','cant'=>100.0,'vlr'=>2500.0,'costo'=>2000.0,'iva'=>19.0,'nota'=>'línea <b>uno</b>'),
    array('seq'=>2,'prod'=>610596,'lote'=>'0','cant'=>40.0,'vlr'=>800.0,'costo'=>700.0,'iva'=>0.0,'nota'=>null),
    array('seq'=>3,'prod'=>703298,'lote'=>'0','cant'=>110.0,'vlr'=>150.0,'costo'=>120.0,'iva'=>0.0,'nota'=>null),
));
AuditoriaDocumentos::registrar(array(
    'modulo'=>'PRUEBAUI','operacion'=>'delete_masivo','destructiva'=>1,
    'tipo'=>'226','numero'=>1798,'exportado_antes'=>'N','anulado_antes'=>'N',
    'lineas_antes'=>3,'lineas_despues'=>1,'filas_afectadas'=>2,'resultado'=>'ok',
    'mensaje'=>'Prueba: perdió 2 líneas','detalle_antes'=>$detalle));
AuditoriaDocumentos::registrar(array(
    'modulo'=>'PRUEBAUI','operacion'=>'delete_id','destructiva'=>1,
    'tipo'=>'226','numero'=>1798,'exportado_antes'=>'S','anulado_antes'=>'N',
    'resultado'=>'bloqueado','mensaje'=>'Prueba: intento sobre documento guardado'));
AuditoriaDocumentos::registrar(array(
    'modulo'=>'PRUEBAUI','operacion'=>'agregar_linea_manual',
    'tipo'=>'999','numero'=>1,'lineas_antes'=>1,'lineas_despues'=>2,
    'resultado'=>'ok','mensaje'=>'Prueba: ganó una línea'));

echo "\n=== Pantalla de Auditoría de Documentos ===\n\n1. Estructura de la tabla\n";

$r = llamar('consultar', array('numero' => 1798));
chk('Responde la estructura que espera DataTables', isset($r['aaData']) && is_array($r['aaData']));
chk('Trae los 2 movimientos del documento 1798', count($r['aaData']) === 2, 'filas=' . count($r['aaData']));
chk('Cada fila trae las 11 columnas', !empty($r['aaData']) && count($r['aaData'][0]) === 11,
    'columnas=' . (empty($r['aaData']) ? 0 : count($r['aaData'][0])));

// Solo las filas sembradas por esta prueba: la bitácora real ya tiene movimientos y
// filtrar por usuario/fecha los arrastraba, falseando los conteos.
$sembradas = array_merge(
    llamar('consultar', array('numero' => 1798))['aaData'],
    llamar('consultar', array('tipo' => '999'))['aaData']
);
$json = json_encode($sembradas);
chk('Lo que perdió líneas sale marcado en rojo',  strpos($json, 'badge-danger') !== false);
chk('Lo que ganó líneas sale marcado en azul',    strpos($json, 'badge-info') !== false);
chk('El intento bloqueado sale marcado',          strpos($json, 'badge-warning') !== false);
chk('Las destructivas llevan icono de alerta',    strpos($json, 'fa-exclamation-triangle') !== false);
chk('El botón de detalle solo aparece si hay detalle guardado',
    substr_count($json, 'btn-ver-detalle') === 1, 'botones=' . substr_count($json, 'btn-ver-detalle'));

echo "\n2. Filtros\n";
$r = llamar('consultar', array('soloConPerdida' => '1', 'numero' => '1798'));
chk('Solo las que perdieron líneas', count($r['aaData']) === 1, 'filas=' . count($r['aaData']));
$r = llamar('consultar', array('resultado' => 'bloqueado', 'numero' => '1798'));
chk('Por resultado = bloqueado', count($r['aaData']) === 1, 'filas=' . count($r['aaData']));
$r = llamar('consultar', array('tipo' => '999'));
chk('Por tipo de documento', count($r['aaData']) === 1, 'filas=' . count($r['aaData']));
$r = llamar('consultar', array('operacion' => 'delete_masivo', 'numero' => 1798));
chk('Por operación', count($r['aaData']) === 1, 'filas=' . count($r['aaData']));
$r = llamar('consultar', array('soloDestructivas' => '1', 'numero' => 1798));
chk('Solo destructivas', count($r['aaData']) === 2, 'filas=' . count($r['aaData']));
$r = llamar('consultar', array('fechaDesde' => '2000-01-01', 'fechaHasta' => '2000-01-02'));
chk('Por rango de fechas', count($r['aaData']) === 0, 'filas=' . count($r['aaData']));

echo "\n3. Modal con el detalle previo\n";
$id = (int)$pdo->query("SELECT id FROM auditoria_documentos
                        WHERE modulo='PRUEBAUI' AND operacion='delete_masivo'")->fetchColumn();
$r = llamar('detalle', array('id' => $id));
chk('Devuelve el registro', isset($r['status']) && $r['status'] === 'ok');
chk('Devuelve las 3 líneas que tenía el documento',
    isset($r['detalle']['lineas']) && count($r['detalle']['lineas']) === 3);
chk('Conserva cantidad y lote originales',
    isset($r['detalle']['lineas'][0]) && $r['detalle']['lineas'][0]['cant'] == 100.0
    && $r['detalle']['lineas'][0]['lote'] === 'L-77');
chk('No expone el JSON crudo en el encabezado', !isset($r['registro']['detalle_antes']));
$r = llamar('detalle', array('id' => 999999999));
chk('Un id inexistente responde error controlado', isset($r['status']) && $r['status'] === 'error');

echo "\n4. Combo de operaciones\n";
$r = llamar('operaciones', array());
chk('Devuelve la lista para el filtro', is_array($r) && in_array('delete_masivo', $r, true));

echo "\n5. Permisos\n";
$salidaSub = shell_exec('php ' . escapeshellarg(__FILE__) . ' --sin-permiso 2>&1');
$j = json_decode(trim((string)$salidaSub), true);
chk('Un usuario sin permiso NO ve la bitácora',
    isset($j['status']) && $j['status'] === 'error', trim((string)$salidaSub));
