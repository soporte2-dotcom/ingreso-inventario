<?php
require_once(__DIR__ . "/../config/conexionmysql.php");

class Etapas {
    private $mysql;

    public function __construct() {
        $this->mysql = new ConectarMysql();
    }

    // Listar solo etapas activas (para selects/combos)
    public function listar_activas() {
        try {
            $conn = $this->mysql->obtenerConexion();
            $stmt = $conn->prepare("SELECT id, nombre FROM etapas WHERE estado = 1 ORDER BY nombre ASC");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Etapas::listar_activas - " . $e->getMessage());
            return [];
        }
    }

    // Listar todas las etapas, ordenadas por id descendente
    public function listar($busqueda = '') {
        try {
            $conn = $this->mysql->obtenerConexion();
            if ($busqueda !== '') {
                $sql  = "SELECT id, nombre, estado, createdAt FROM etapas
                         WHERE nombre LIKE ? ORDER BY id DESC";
                $stmt = $conn->prepare($sql);
                $stmt->execute(['%' . $busqueda . '%']);
            } else {
                $stmt = $conn->prepare("SELECT id, nombre, estado, createdAt FROM etapas ORDER BY id DESC");
                $stmt->execute();
            }
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Etapas::listar - " . $e->getMessage());
            return [];
        }
    }

    // Obtener una etapa por id (para edición)
    public function get_por_id($id) {
        try {
            $conn = $this->mysql->obtenerConexion();
            $stmt = $conn->prepare("SELECT id, nombre, estado FROM etapas WHERE id = ?");
            $stmt->execute([(int)$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Etapas::get_por_id - " . $e->getMessage());
            return null;
        }
    }

    // Verificar si ya existe una etapa con el mismo nombre (excluyendo un id opcional)
    public function existe_nombre($nombre, $excluir_id = null) {
        try {
            $conn = $this->mysql->obtenerConexion();
            if ($excluir_id !== null) {
                $stmt = $conn->prepare("SELECT COUNT(*) FROM etapas WHERE nombre = ? AND id <> ?");
                $stmt->execute([$nombre, (int)$excluir_id]);
            } else {
                $stmt = $conn->prepare("SELECT COUNT(*) FROM etapas WHERE nombre = ?");
                $stmt->execute([$nombre]);
            }
            return (int)$stmt->fetchColumn() > 0;
        } catch (Exception $e) {
            error_log("Etapas::existe_nombre - " . $e->getMessage());
            return false;
        }
    }

    // Crear una etapa nueva
    public function crear($nombre, $idUsuario) {
        try {
            if (trim($nombre) === '') {
                return ['status' => 'error', 'message' => 'El nombre no puede estar vacío'];
            }
            if ($this->existe_nombre($nombre)) {
                return ['status' => 'error', 'message' => 'El nombre ya existe'];
            }
            $conn  = $this->mysql->obtenerConexion();
            $ahora = date('Y-m-d H:i:s');
            $stmt  = $conn->prepare(
                "INSERT INTO etapas (nombre, estado, createdAt, updateAt, idUserCreated, idUserModified)
                 VALUES (?, 1, ?, ?, ?, ?)"
            );
            $stmt->execute([trim($nombre), $ahora, $ahora, $idUsuario, $idUsuario]);
            return ['status' => 'success', 'message' => 'Etapa creada correctamente'];
        } catch (Exception $e) {
            error_log("Etapas::crear - " . $e->getMessage());
            return ['status' => 'error', 'message' => 'Error al crear la etapa'];
        }
    }

    // Editar nombre y/o estado de una etapa
    public function editar($id, $nombre, $estado, $idUsuario) {
        try {
            if (trim($nombre) === '') {
                return ['status' => 'error', 'message' => 'El nombre no puede estar vacío'];
            }
            if ($this->existe_nombre($nombre, $id)) {
                return ['status' => 'error', 'message' => 'El nombre ya existe'];
            }
            $conn  = $this->mysql->obtenerConexion();
            $ahora = date('Y-m-d H:i:s');
            $stmt  = $conn->prepare(
                "UPDATE etapas SET nombre = ?, estado = ?, updateAt = ?, idUserModified = ?
                 WHERE id = ?"
            );
            $stmt->execute([trim($nombre), (int)$estado, $ahora, $idUsuario, (int)$id]);
            return ['status' => 'success', 'message' => 'Etapa actualizada correctamente'];
        } catch (Exception $e) {
            error_log("Etapas::editar - " . $e->getMessage());
            return ['status' => 'error', 'message' => 'Error al actualizar la etapa'];
        }
    }

    // Eliminación lógica: cambia estado a 0
    public function eliminar($id, $idUsuario) {
        try {
            $conn  = $this->mysql->obtenerConexion();
            $ahora = date('Y-m-d H:i:s');
            $stmt  = $conn->prepare(
                "UPDATE etapas SET estado = 0, updateAt = ?, idUserModified = ? WHERE id = ?"
            );
            $stmt->execute([$ahora, $idUsuario, (int)$id]);
            return ['status' => 'success', 'message' => 'Etapa desactivada correctamente'];
        } catch (Exception $e) {
            error_log("Etapas::eliminar - " . $e->getMessage());
            return ['status' => 'error', 'message' => 'Error al desactivar la etapa'];
        }
    }
}
?>
