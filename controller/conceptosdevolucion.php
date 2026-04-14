<?php
require_once("../config/conexionserver.php"); // inicia sesión

if (!isset($_SESSION["Id_Usuario"])) {
    echo json_encode(['status' => 'error', 'message' => 'Sesión no activa']);
    exit();
}

require_once("../models/mdlConceptosDevolucion.php");

$conceptos  = new ConceptosDevolucion();
$idUsuario  = $_SESSION["Id_Usuario"];

header('Content-Type: application/json; charset=utf-8');

$op = $_GET['op'] ?? '';

switch ($op) {

    // ── LISTAR ACTIVOS (para combos/selects en devoluciones) ────────────────
    case "listar_activos":
        echo json_encode($conceptos->listar_activos());
        break;

    // ── LISTAR (para DataTable admin) ────────────────────────────────────────
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
                          onclick="cambiarEstado(' . $row['id'] . ', 0, \'' . htmlspecialchars($row['nombre']) . '\')" title="Inactivar">
                      <i class="fa fa-ban"></i>
                   </button>'
                : '<button class="btn btn-xs btn-success btn-accion"
                          onclick="cambiarEstado(' . $row['id'] . ', 1, \'' . htmlspecialchars($row['nombre']) . '\')" title="Activar">
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

    // ── GET CONCEPTO (para cargar modal de edición) ──────────────────────────
    case "get_concepto":
        $id      = (int)($_GET['id'] ?? 0);
        $concepto = $conceptos->get_por_id($id);
        if ($concepto) {
            echo json_encode(['status' => 'success', 'data' => $concepto]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Concepto no encontrado']);
        }
        break;

    // ── CREAR ─────────────────────────────────────────────────────────────────
    case "crear":
        $nombre = trim($_POST['nombre'] ?? '');
        echo json_encode($conceptos->crear($nombre, $idUsuario));
        break;

    // ── EDITAR ────────────────────────────────────────────────────────────────
    case "editar":
        $id     = (int)($_POST['id']     ?? 0);
        $nombre = trim($_POST['nombre']  ?? '');
        $estado = (int)($_POST['estado'] ?? 1);
        echo json_encode($conceptos->editar($id, $nombre, $estado, $idUsuario));
        break;

    // ── CAMBIAR ESTADO (activar/inactivar) ───────────────────────────────────
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
