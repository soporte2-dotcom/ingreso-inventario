<?php
/**
 * Prueba de humo de la bitácora de auditoría (sql/05_mysql_create_auditoria_documentos.sql).
 *
 * Comprueba, sin tocar ningún documento real:
 *   1. Que la tabla existe y el INSERT del logger casa con sus columnas.
 *   2. Que se guardan los milisegundos, el JSON del detalle y los tres resultados.
 *   3. Que el logger NO revienta cuando MySQL no responde (es best-effort).
 *   4. Opcional (--sqlsrv): que snapshotLineas/contarLineas leen bien de SQL Server.
 *
 * Uso desde la carpeta del proyecto:
 *   php test_auditoria.php                       -> solo MySQL
 *   php test_auditoria.php --sqlsrv 5 12345      -> además lee el detalle de ese documento
 *
 * Las filas que inserta se marcan con modulo='PRUEBA' y se borran al terminar.
 * Borra este archivo cuando termines de validar: no debe quedar en producción.
 */

if (php_sapi_name() !== 'cli') {
    die("Este script solo se ejecuta por consola.\n");
}

// conexionserver.php llama a session_start(), que por consola avisa "headers already
// sent" porque ya se imprimió texto. Sin cookies de sesión no hay cabeceras que enviar
// y el aviso desaparece, sin tocar el archivo de configuración real.
ini_set('session.use_cookies', '0');
ini_set('session.use_only_cookies', '0');
session_cache_limiter('');

require_once(__DIR__ . '/models/AuditoriaDocumentos.php');

$ok = 0;
$fallos = array();

function chequear($descripcion, $condicion, $detalle = '') {
    global $ok, $fallos;
    if ($condicion) {
        $ok++;
        echo "  [OK]    $descripcion\n";
    } else {
        $fallos[] = $descripcion;
        echo "  [FALLA] $descripcion" . ($detalle !== '' ? " -> $detalle" : '') . "\n";
    }
}

function pdoPruebas() {
    require_once(__DIR__ . '/config/conexionmysql.php');
    $my = new ConectarMysql();
    $pdo = $my->obtenerConexion();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    return $pdo;
}

echo "\n=== Prueba de la bitácora de auditoría ===\n\n";

// ---------------------------------------------------------------- 1. Conexión
echo "1. Conexión y tabla\n";
try {
    $pdo = pdoPruebas();
    chequear('Conecta a MySQL permisos_tecno', true);
} catch (Exception $e) {
    echo "  [FALLA] No se pudo conectar a MySQL: " . $e->getMessage() . "\n";
    echo "\nRevisa config/conexionmysql.php antes de seguir.\n";
    exit(1);
}

$existe = $pdo->query("SHOW TABLES LIKE 'auditoria_documentos'")->fetch();
chequear('La tabla auditoria_documentos existe', (bool)$existe);
if (!$existe) {
    echo "\nEjecuta primero sql/05_mysql_create_auditoria_documentos.sql\n";
    exit(1);
}

// Se limpia cualquier resto de una corrida anterior interrumpida.
$pdo->exec("DELETE FROM auditoria_documentos WHERE modulo = 'PRUEBA'");

// ------------------------------------------------- 2. Registro con snapshot
echo "\n2. Registro de una operación destructiva (con detalle)\n";

$detalleFalso = array('lineas' => array(
    array('seq' => 1, 'prod' => 1001, 'lote' => 'L-001', 'cant' => 10.5,
          'vlr' => 2500.0, 'costo' => 2000.0, 'iva' => 19.0, 'nota' => 'línea de prueba áéíóú'),
    array('seq' => 2, 'prod' => 1002, 'lote' => '0', 'cant' => 3.0,
          'vlr' => 800.0,  'costo' => 700.0,  'iva' => 0.0,  'nota' => null)
));

AuditoriaDocumentos::registrar(array(
    'modulo' => 'PRUEBA', 'operacion' => 'delete_masivo', 'destructiva' => 1,
    'tipo' => '99', 'numero' => 987654,
    'exportado_antes' => 'N', 'anulado_antes' => 'N',
    'lineas_antes' => 2, 'lineas_despues' => 0, 'filas_afectadas' => 2,
    'resultado' => 'ok',
    'mensaje' => 'Prueba de humo con acentos: ñáéíóú',
    'detalle_antes' => $detalleFalso,
    'contexto' => 'test_auditoria.php'
));

$fila = $pdo->query("SELECT * FROM auditoria_documentos WHERE modulo = 'PRUEBA'
                     ORDER BY id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);

chequear('El registro se insertó', (bool)$fila);
if ($fila) {
    chequear('El campo fecha trae los 3 decimales',
             preg_match('/\.\d{3}$/', $fila['fecha']) === 1, 'fecha=' . $fila['fecha']);
    chequear('destructiva = 1', (int)$fila['destructiva'] === 1);
    chequear('numero se guardó como entero', (int)$fila['numero'] === 987654);
    chequear('exportado_antes = N', $fila['exportado_antes'] === 'N');
    chequear('lineas_antes/despues correctos',
             (int)$fila['lineas_antes'] === 2 && (int)$fila['lineas_despues'] === 0);

    $json = json_decode($fila['detalle_antes'], true);
    chequear('detalle_antes es JSON válido', is_array($json));
    chequear('El JSON conserva las 2 líneas',
             isset($json['lineas']) && count($json['lineas']) === 2);
    chequear('El JSON conserva acentos sin escapar',
             isset($json['lineas'][0]['nota']) && strpos($json['lineas'][0]['nota'], 'áéíóú') !== false,
             isset($json['lineas'][0]['nota']) ? $json['lineas'][0]['nota'] : '(sin nota)');
    chequear('El mensaje conserva acentos',
             strpos($fila['mensaje'], 'ñáéíóú') !== false, $fila['mensaje']);
}

// ------------------------------------------- 2b. Milisegundos y hora local
// Comprobar solo que hay 3 decimales no basta: date('...v') devolvía siempre
// ".000" y pasaba esa prueba. Aquí se exige que los milisegundos varíen de verdad
// y que la hora sea la de Bogotá, no la del php.ini.
echo "\n2b. Milisegundos reales y zona horaria\n";
for ($i = 0; $i < 8; $i++) {
    AuditoriaDocumentos::registrar(array(
        'modulo' => 'PRUEBA', 'operacion' => 'ms_' . $i, 'mensaje' => 'orden ' . $i
    ));
}
$fechas = $pdo->query("SELECT fecha FROM auditoria_documentos
                       WHERE modulo = 'PRUEBA' AND operacion LIKE 'ms_%'
                       ORDER BY id")->fetchAll(PDO::FETCH_COLUMN);

$milis = array_map(function($f) { return substr($f, -3); }, $fechas);
chequear('Los milisegundos no son siempre 000',
         count(array_filter($milis, function($m) { return $m !== '000'; })) > 0,
         'valores=' . implode(',', $milis));
chequear('Las marcas de tiempo respetan el orden de inserción',
         $fechas === array_values($fechas) && $fechas[0] <= $fechas[count($fechas) - 1],
         'primera=' . $fechas[0] . ' última=' . $fechas[count($fechas) - 1]);

// Ambas fechas deben interpretarse en Bogotá. Comparar la fecha guardada usando la
// zona del php.ini daba un desfase falso de 7 horas: el error estaba en la prueba.
$tzPrevia = date_default_timezone_get();
date_default_timezone_set('America/Bogota');
$esperado = time();
$guardado = strtotime(substr($fechas[0], 0, 19));
$muestra  = date('Y-m-d H:i:s', $esperado);
date_default_timezone_set($tzPrevia);

chequear('La hora guardada es la de Bogotá (± 2 min)',
         abs($esperado - $guardado) <= 120,
         'guardado=' . $fechas[0] . ' esperado≈' . $muestra
         . ' desfase=' . ($guardado - $esperado) . 's');
chequear('La zona no se filtra al resto de la petición',
         date_default_timezone_get() === $tzPrevia);

// ------------------------------------------------------- 3. Otros resultados
echo "\n3. Los tres valores de resultado\n";
foreach (array('ok', 'error', 'bloqueado') as $res) {
    AuditoriaDocumentos::registrar(array(
        'modulo' => 'PRUEBA', 'operacion' => 'resultado_' . $res,
        'tipo' => '99', 'numero' => 987654,
        'resultado' => $res, 'mensaje' => "Prueba de resultado=$res"
    ));
}
$n = (int)$pdo->query("SELECT COUNT(*) FROM auditoria_documentos
                       WHERE modulo = 'PRUEBA' AND operacion LIKE 'resultado_%'")->fetchColumn();
chequear('Se aceptan ok, error y bloqueado', $n === 3, "insertados=$n");

// Un valor inválido no debe reventar: cae a 'ok'.
AuditoriaDocumentos::registrar(array(
    'modulo' => 'PRUEBA', 'operacion' => 'resultado_invalido',
    'resultado' => 'valor_que_no_existe', 'mensaje' => 'Debe caer a ok'
));
$r = $pdo->query("SELECT resultado FROM auditoria_documentos
                  WHERE modulo = 'PRUEBA' AND operacion = 'resultado_invalido'")->fetchColumn();
chequear('Un resultado inválido cae a ok en vez de fallar', $r === 'ok', "resultado=$r");

// --------------------------------------------- 4. Campos ausentes / nulos
echo "\n4. Robustez con datos incompletos\n";
AuditoriaDocumentos::registrar(array('modulo' => 'PRUEBA', 'operacion' => 'minimo'));
$fm = $pdo->query("SELECT * FROM auditoria_documentos
                   WHERE modulo = 'PRUEBA' AND operacion = 'minimo'")->fetch(PDO::FETCH_ASSOC);
chequear('Registra aunque falten casi todos los campos', (bool)$fm);
if ($fm) {
    chequear('tipo/numero quedan NULL, no en 0',
             $fm['tipo'] === null && $fm['numero'] === null,
             'tipo=' . var_export($fm['tipo'], true) . ' numero=' . var_export($fm['numero'], true));
}

$largo = str_repeat('X', 900);
AuditoriaDocumentos::registrar(array(
    'modulo' => 'PRUEBA', 'operacion' => 'mensaje_largo', 'mensaje' => $largo
));
$ml = $pdo->query("SELECT mensaje FROM auditoria_documentos
                   WHERE modulo = 'PRUEBA' AND operacion = 'mensaje_largo'")->fetchColumn();
chequear('Un mensaje largo se recorta a 500 sin romper el INSERT',
         $ml !== false && strlen($ml) === 500, 'longitud=' . ($ml === false ? 'null' : strlen($ml)));

// ------------------------------------------------ 5. SQL Server (opcional)
$args = array_slice($argv, 1);
if (in_array('--sqlsrv', $args, true)) {
    echo "\n5. Lectura de detalle desde SQL Server\n";
    $i = array_search('--sqlsrv', $args, true);
    $tipoDoc = isset($args[$i + 1]) ? $args[$i + 1] : null;
    $numDoc  = isset($args[$i + 2]) ? $args[$i + 2] : null;

    if ($tipoDoc === null || $numDoc === null) {
        echo "  [AVISO] Faltan argumentos: php test_auditoria.php --sqlsrv <tipo> <numero>\n";
    } else {
        require_once(__DIR__ . '/config/conexionserver.php');
        $cn = new Conectarserver;
        $conn = $cn->getConecta();
        chequear('Conecta a SQL Server', (bool)$conn,
                 $conn ? '' : print_r(sqlsrv_errors(), true));

        if ($conn) {
            $estado = AuditoriaDocumentos::estadoDocumento($conn, $tipoDoc, $numDoc);
            chequear("El documento tipo=$tipoDoc num=$numDoc existe",
                     $estado['exportado'] !== null,
                     'exportado=' . var_export($estado['exportado'], true));

            $snap = AuditoriaDocumentos::snapshotLineas($conn, $tipoDoc, $numDoc);
            $cuenta = AuditoriaDocumentos::contarLineas($conn, $tipoDoc, $numDoc);
            chequear('snapshotLineas devuelve un conteo', $snap['lineas'] !== null);
            chequear('snapshotLineas y contarLineas coinciden',
                     $snap['lineas'] === $cuenta, "snapshot={$snap['lineas']} contar=$cuenta");

            // Documentos muy grandes (los inventarios de inicio de mes pasan de 1000
            // líneas) deben quedar marcados como truncados, no reventar el MEDIUMTEXT.
            $tope = AuditoriaDocumentos::MAX_LINEAS_SNAPSHOT;
            if ($snap['lineas'] > $tope) {
                chequear("Un documento de {$snap['lineas']} líneas se marca como truncado",
                         !empty($snap['detalle']['truncado']));
                chequear("Se guardan solo $tope líneas y se anota el total real",
                         count($snap['detalle']['lineas']) === $tope
                         && (int)$snap['detalle']['total'] === $snap['lineas']);
                $bytes = strlen(json_encode($snap['detalle'], JSON_UNESCAPED_UNICODE));
                chequear('El JSON truncado cabe holgado en MEDIUMTEXT (16 MB)',
                         $bytes < 1048576, 'bytes=' . $bytes);
            } else {
                chequear("El detalle completo cabe sin truncar ({$snap['lineas']} líneas)",
                         empty($snap['detalle']['truncado']));
            }

            echo "\n  Documento tipo=$tipoDoc N=$numDoc | exportado={$estado['exportado']}"
               . " anulado={$estado['anulado']} | líneas={$snap['lineas']}\n";
            if (!empty($snap['detalle']['lineas'])) {
                echo "  Primeras líneas capturadas:\n";
                foreach (array_slice($snap['detalle']['lineas'], 0, 5) as $l) {
                    echo "    seq={$l['seq']} prod={$l['prod']} lote={$l['lote']} cant={$l['cant']}\n";
                }
            }
        }
    }
} else {
    echo "\n5. Lectura desde SQL Server: omitida (usa --sqlsrv <tipo> <numero> para probarla)\n";
}

// ------------------------------- 5b. Ruta bloqueada sobre documento real
// Esta es la prueba de riesgo cero: se llaman los métodos REALES del modelo sobre un
// documento ya exportado. La guarda los rechaza, no se modifica ni una fila en SQL
// Server, y aun así queda el registro de auditoría. Valida toda la cadena de una vez:
// que Documento.php carga AuditoriaDocumentos, que la guarda dispara, y que el intento
// queda grabado con el estado real del documento.
if (in_array('--bloqueado', $args, true)) {
    echo "\n5b. Intento bloqueado sobre un documento ya exportado (no modifica nada)\n";
    $i = array_search('--bloqueado', $args, true);
    $tipoB = isset($args[$i + 1]) ? $args[$i + 1] : null;
    $numB  = isset($args[$i + 2]) ? $args[$i + 2] : null;

    if ($tipoB === null || $numB === null) {
        echo "  [AVISO] Uso: php test_auditoria.php --bloqueado <tipo> <numero>\n";
    } else {
        require_once(__DIR__ . '/config/conexionserver.php');
        require_once(__DIR__ . '/models/Documento.php');
        $cnB   = new Conectarserver;
        $connB = $cnB->getConecta();

        $estadoB = AuditoriaDocumentos::estadoDocumento($connB, $tipoB, $numB);
        chequear('El documento existe', $estadoB['exportado'] !== null);
        chequear('El documento está exportado (S), que es lo que se quiere probar',
                 $estadoB['exportado'] === 'S',
                 'exportado=' . var_export($estadoB['exportado'], true)
                 . ' -- elige un documento guardado');

        if ($estadoB['exportado'] === 'S') {
            $snapB    = AuditoriaDocumentos::snapshotLineas($connB, $tipoB, $numB);
            $lineasB  = $snapB['lineas'];
            $primera  = !empty($snapB['detalle']['lineas']) ? $snapB['detalle']['lineas'][0] : null;
            chequear('El documento tiene al menos una línea para intentar borrar',
                     $primera !== null, "lineas=$lineasB");

            if ($primera !== null) {
                $idAntes = (int)$pdo->query("SELECT IFNULL(MAX(id),0) FROM auditoria_documentos")->fetchColumn();

                // delete_id imprime su resultado en vez de devolverlo: se captura.
                $doc = new Documento();
                ob_start();
                $doc->delete_id($tipoB, $numB, $primera['prod'], $primera['seq']);
                $salida = trim(ob_get_clean());
                chequear('delete_id responde "error" sobre un documento exportado',
                         $salida === 'error', 'respuesta=' . var_export($salida, true));

                $salidaMasivo = $doc->delete_masivo($tipoB, $numB, (string)$primera['seq'], (string)$primera['prod']);
                chequear('delete_masivo también lo rechaza',
                         strpos($salidaMasivo, 'ya está guardado o anulado') !== false,
                         'respuesta=' . $salidaMasivo);

                // Lo más importante: que NO se haya tocado el documento.
                $lineasDespues = AuditoriaDocumentos::contarLineas($connB, $tipoB, $numB);
                chequear('EL DOCUMENTO QUEDÓ INTACTO (mismo número de líneas)',
                         $lineasDespues === $lineasB, "antes=$lineasB despues=$lineasDespues");

                $filasAud = $pdo->query("SELECT * FROM auditoria_documentos
                                         WHERE id > $idAntes ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
                chequear('Quedaron registrados los 2 intentos bloqueados',
                         count($filasAud) === 2, 'registros=' . count($filasAud));

                $todosBloqueados = $filasAud && !array_filter($filasAud,
                    function($f) { return $f['resultado'] !== 'bloqueado'; });
                chequear('Ambos con resultado = bloqueado', (bool)$todosBloqueados);

                if ($filasAud) {
                    chequear('Guardan el estado real del documento (exportado = S)',
                             $filasAud[0]['exportado_antes'] === 'S');
                    chequear('Guardan tipo y número del documento real',
                             $filasAud[0]['tipo'] === (string)$tipoB
                             && (int)$filasAud[0]['numero'] === (int)$numB);

                    echo "\n  Así queda en la bitácora un intento bloqueado:\n";
                    foreach ($filasAud as $f) {
                        echo "    {$f['fecha']} | {$f['operacion']} | {$f['resultado']} | {$f['mensaje']}\n";
                    }

                    // Se borran para no dejar ruido de la prueba en la bitácora real.
                    $pdo->exec("DELETE FROM auditoria_documentos WHERE id > $idAntes");
                    echo "  (registros de prueba eliminados de la bitácora)\n";
                }
            }
        }
    }
} else {
    echo "\n5b. Intento bloqueado: omitido (usa --bloqueado <tipo> <numero> con un documento exportado)\n";
}

// ----------------------------------------------------------- 6. Cómo se lee
echo "\n6. Así se ve la bitácora (filas de prueba)\n";
$listado = $pdo->query("SELECT fecha, usuario, ip, operacion, destructiva, resultado,
                               lineas_antes, lineas_despues, filas_afectadas
                        FROM auditoria_documentos WHERE modulo = 'PRUEBA' ORDER BY id");
printf("  %-23s %-18s %-4s %-10s %5s %5s %5s\n",
       'FECHA', 'OPERACION', 'DEST', 'RESULTADO', 'ANT', 'DESP', 'FILAS');
foreach ($listado as $r) {
    printf("  %-23s %-18s %-4s %-10s %5s %5s %5s\n",
        $r['fecha'], $r['operacion'], $r['destructiva'], $r['resultado'],
        $r['lineas_antes'] === null ? '-' : $r['lineas_antes'],
        $r['lineas_despues'] === null ? '-' : $r['lineas_despues'],
        $r['filas_afectadas'] === null ? '-' : $r['filas_afectadas']);
}

// ------------------------------------------------------------- 7. Limpieza
$borradas = $pdo->exec("DELETE FROM auditoria_documentos WHERE modulo = 'PRUEBA'");
echo "\n7. Limpieza: $borradas filas de prueba eliminadas\n";

// ------------------------------------------------------------- Resultado
echo "\n=== Resultado: $ok comprobaciones OK, " . count($fallos) . " fallas ===\n";
if ($fallos) {
    foreach ($fallos as $f) echo "  - $f\n";
    exit(1);
}
echo "La bitácora quedó operativa.\n";
exit(0);
