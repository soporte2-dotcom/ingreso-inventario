<?php
    require_once("../config/conexionserver.php");
    require_once("../config/session_guard.php");
    verificar_sesion_activa();
    require_once("../models/AuditoriaDocumentos.php");
    require_once("../models/mdlPermisos.php");

    // La bitácora muestra quién hizo qué sobre documentos de toda la empresa, así que
    // se protege con el mismo permiso de módulo que Gestión de Documentos.
    $permisos = new Permisos();
    if (!$permisos->tiene_permiso_especial($_SESSION["Id_Usuario"], 'auditoria_documentos')) {
        echo json_encode(["status" => "error", "message" => "No tiene permiso para acceder a este módulo."]);
        exit();
    }

    switch($_GET["op"]){

        case "consultar":
            $filtros = array(
                'fechaDesde'       => trim($_POST['fechaDesde'] ?? ''),
                'fechaHasta'       => trim($_POST['fechaHasta'] ?? ''),
                'tipo'             => trim($_POST['tipo'] ?? ''),
                'numero'           => trim($_POST['numero'] ?? ''),
                'usuario'          => trim($_POST['usuario'] ?? ''),
                'operacion'        => trim($_POST['operacion'] ?? ''),
                'resultado'        => trim($_POST['resultado'] ?? ''),
                'soloDestructivas' => ($_POST['soloDestructivas'] ?? '') === '1',
                'soloConPerdida'   => ($_POST['soloConPerdida'] ?? '') === '1',
            );

            $filas = AuditoriaDocumentos::consultar($filtros);

            $data = array();
            foreach ($filas as $r) {
                // Marca visual del cambio en el número de líneas: es la columna que más
                // rápido delata una pérdida de detalle.
                $antes   = $r['lineas_antes'];
                $despues = $r['lineas_despues'];
                if ($antes === null && $despues === null) {
                    $cambio = '<span class="text-muted">-</span>';
                } elseif ($despues !== null && $antes !== null && (int)$despues < (int)$antes) {
                    $cambio = '<span class="badge badge-danger">' . $antes . ' &rarr; ' . $despues
                            . ' (-' . ((int)$antes - (int)$despues) . ')</span>';
                } elseif ($despues !== null && $antes !== null && (int)$despues > (int)$antes) {
                    $cambio = '<span class="badge badge-info">' . $antes . ' &rarr; ' . $despues
                            . ' (+' . ((int)$despues - (int)$antes) . ')</span>';
                } else {
                    $cambio = '<span class="text-muted">' . ($antes !== null ? $antes : '?') . '</span>';
                }

                $colorRes = array('ok' => 'success', 'error' => 'danger', 'bloqueado' => 'warning');
                $badgeRes = '<span class="badge badge-' . ($colorRes[$r['resultado']] ?? 'secondary') . '">'
                          . htmlspecialchars($r['resultado']) . '</span>';

                $doc = ($r['tipo'] !== null ? htmlspecialchars($r['tipo']) : '-')
                     . ($r['numero'] !== null ? ' / ' . $r['numero'] : '');

                $botonDetalle = $r['tiene_detalle']
                    ? '<button type="button" class="btn btn-sm btn-primary btn-ver-detalle" data-id="'
                      . $r['id'] . '" title="Ver el detalle que tenía el documento antes de esta operación">'
                      . '<i class="fa fa-search"></i></button>'
                    : '<span class="text-muted">-</span>';

                $data[] = array(
                    htmlspecialchars($r['fecha']),
                    htmlspecialchars($r['usuario'] ?? '-'),
                    htmlspecialchars($r['ip'] ?? '-'),
                    htmlspecialchars($r['modulo']),
                    htmlspecialchars($r['operacion']) . ($r['destructiva'] ? ' <i class="fa fa-exclamation-triangle text-danger" title="Operación destructiva"></i>' : ''),
                    $doc,
                    $badgeRes,
                    $cambio,
                    $r['filas_afectadas'] !== null ? $r['filas_afectadas'] : '-',
                    htmlspecialchars($r['mensaje'] ?? ''),
                    $botonDetalle,
                );
            }

            echo json_encode(array(
                "sEcho" => 1,
                "iTotalRecords" => count($data),
                "iTotalDisplayRecords" => count($data),
                "aaData" => $data,
                "tope" => count($data) >= AuditoriaDocumentos::MAX_FILAS_CONSULTA
                    ? AuditoriaDocumentos::MAX_FILAS_CONSULTA : 0
            ));
        break;

        case "detalle":
            $registro = AuditoriaDocumentos::obtenerPorId($_POST['id'] ?? 0);
            if (!$registro) {
                echo json_encode(["status" => "error", "message" => "Registro no encontrado"]);
                break;
            }
            $detalle = $registro['detalle_antes'] ? json_decode($registro['detalle_antes'], true) : null;
            echo json_encode(array(
                "status"   => "ok",
                "registro" => array(
                    'fecha'          => $registro['fecha'],
                    'usuario'        => $registro['usuario'],
                    'ip'             => $registro['ip'],
                    'operacion'      => $registro['operacion'],
                    'tipo'           => $registro['tipo'],
                    'numero'         => $registro['numero'],
                    'resultado'      => $registro['resultado'],
                    'mensaje'        => $registro['mensaje'],
                    'lineas_antes'   => $registro['lineas_antes'],
                    'lineas_despues' => $registro['lineas_despues'],
                ),
                "detalle" => $detalle
            ));
        break;

        case "operaciones":
            echo json_encode(AuditoriaDocumentos::operacionesRegistradas());
        break;

    }
?>
