<?php
require_once("../config/conexionserver.php");
require_once("../config/session_guard.php");
verificar_sesion_activa();

require_once("../models/mdlConceptosDotacion.php");

$conceptos = new ConceptosDotacion();
$idUsuario = $_SESSION["Id_Usuario"];

header('Content-Type: application/json; charset=utf-8');

$op = $_GET['op'] ?? '';

switch ($op) {

    case "listar_activos":
        echo json_encode($conceptos->listar_activos());
        break;

    case "listar":
        $busqueda = trim($_POST['busqueda'] ?? '');
        $rows     = $conceptos->listar($busqueda);
        $data     = [];
        foreach ($rows as $row) {
            $estadoBadge = $row['estado'] == 1
                ? '<span class="badge badge-success">Activo</span>'
                : '<span class="badge badge-secondary">Inactivo</span>';
            $fecha = $row['createdAt']
                ? date('d/m/Y H:i', strtotime($row['createdAt']))
                : '-';
            $btnEstado = $row['estado'] == 1
                ? '<button class="btn btn-xs btn-warning btn-accion"
                          onclick="cambiarEstado(' . $row['id'] . ', 0, \'' . htmlspecialchars($row['nombre'], ENT_QUOTES) . '\')" title="Inactivar">
                      <i class="fa fa-ban"></i>
                   </button>'
                : '<button class="btn btn-xs btn-success btn-accion"
                          onclick="cambiarEstado(' . $row['id'] . ', 1, \'' . htmlspecialchars($row['nombre'], ENT_QUOTES) . '\')" title="Activar">
                      <i class="fa fa-check"></i>
                   </button>';
            $acciones = '
                <button class="btn btn-xs btn-info btn-accion"
                        onclick="abrirModalEditar(' . $row['id'] . ')" title="Editar">
                    <i class="fa fa-pencil"></i>
                </button>
                ' . $btnEstado;
            $data[] = [
                $row['id'],
                htmlspecialchars($row['nombre']),
                $estadoBadge,
                $fecha,
                $acciones
            ];
        }
        echo json_encode([
            'sEcho'                => 1,
            'iTotalRecords'        => count($data),
            'iTotalDisplayRecords' => count($data),
            'aaData'               => $data
        ]);
        break;

    case "get_concepto":
        $id      = (int)($_GET['id'] ?? 0);
        $concepto = $conceptos->get_por_id($id);
        echo $concepto
            ? json_encode(['status' => 'success', 'data' => $concepto])
            : json_encode(['status' => 'error', 'message' => 'Concepto no encontrado']);
        break;

    case "crear":
        $nombre = trim($_POST['nombre'] ?? '');
        echo json_encode($conceptos->crear($nombre, $idUsuario));
        break;

    case "editar":
        $id     = (int)($_POST['id']     ?? 0);
        $nombre = trim($_POST['nombre']  ?? '');
        $estado = (int)($_POST['estado'] ?? 1);
        echo json_encode($conceptos->editar($id, $nombre, $estado, $idUsuario));
        break;

    case "cambiar_estado":
        $id     = (int)($_POST['id']     ?? 0);
        $estado = (int)($_POST['estado'] ?? 0);
        echo json_encode($conceptos->cambiar_estado($id, $estado, $idUsuario));
        break;

    default:
        echo json_encode(['status' => 'error', 'message' => 'Operación no reconocida']);
        break;
}
?>
