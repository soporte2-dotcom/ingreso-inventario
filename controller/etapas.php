<?php
require_once("../config/conexionserver.php"); // inicia sesión

if (!isset($_SESSION["Id_Usuario"])) {
    echo json_encode(['status' => 'error', 'message' => 'Sesión no activa']);
    exit();
}

require_once("../models/mdlEtapas.php");

$etapas   = new Etapas();
$idUsuario = $_SESSION["Id_Usuario"];

header('Content-Type: application/json; charset=utf-8');

$op = $_GET['op'] ?? '';

switch ($op) {

    // ── LISTAR ACTIVAS (para combos/selects) ────────────────────────────────
    case "listar_activas":
        echo json_encode($etapas->listar_activas());
        break;

    // ── LISTAR (para DataTable) ──────────────────────────────────────────────
    case "listar":
        $busqueda = trim($_POST['busqueda'] ?? '');
        $rows     = $etapas->listar($busqueda);
        $data     = [];
        foreach ($rows as $row) {
            $estadoBadge = $row['estado'] == 1
                ? '<span class="badge badge-success">Activo</span>'
                : '<span class="badge badge-secondary">Inactivo</span>';
            $fecha = $row['createdAt']
                ? date('d/m/Y H:i', strtotime($row['createdAt']))
                : '-';
            $acciones = '
                <button class="btn btn-xs btn-warning btn-accion"
                        onclick="abrirModalEditar(' . $row['id'] . ')" title="Editar">
                    <i class="fa fa-pencil"></i>
                </button>
                <button class="btn btn-xs btn-danger btn-accion"
                        onclick="desactivarEtapa(' . $row['id'] . ', \'' . htmlspecialchars($row['nombre']) . '\')" title="Desactivar">
                    <i class="fa fa-ban"></i>
                </button>';
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

    // ── GET ETAPA (para cargar modal de edición) ─────────────────────────────
    case "get_etapa":
        $id     = (int)($_GET['id'] ?? 0);
        $etapa  = $etapas->get_por_id($id);
        if ($etapa) {
            echo json_encode(['status' => 'success', 'data' => $etapa]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Etapa no encontrada']);
        }
        break;

    // ── CREAR ─────────────────────────────────────────────────────────────────
    case "crear":
        $nombre = trim($_POST['nombre'] ?? '');
        echo json_encode($etapas->crear($nombre, $idUsuario));
        break;

    // ── EDITAR ────────────────────────────────────────────────────────────────
    case "editar":
        $id     = (int)($_POST['id']     ?? 0);
        $nombre = trim($_POST['nombre']  ?? '');
        $estado = (int)($_POST['estado'] ?? 1);
        echo json_encode($etapas->editar($id, $nombre, $estado, $idUsuario));
        break;

    // ── ELIMINAR (lógico) ─────────────────────────────────────────────────────
    case "eliminar":
        $id = (int)($_POST['id'] ?? 0);
        echo json_encode($etapas->eliminar($id, $idUsuario));
        break;

    default:
        echo json_encode(['status' => 'error', 'message' => 'Operación no reconocida']);
        break;
}
?>
