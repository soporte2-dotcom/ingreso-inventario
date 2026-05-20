<?php
require_once(__DIR__ . "/../config/conexionmysql.php");

class ConceptosDotacion {
    private $mysql;

    public function __construct() {
        $this->mysql = new ConectarMysql();
    }

    public function listar_activos() {
        try {
            $conn = $this->mysql->obtenerConexion();
            $stmt = $conn->prepare("SELECT id, nombre FROM conceptosdotacion WHERE estado = 1 ORDER BY nombre ASC");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("ConceptosDotacion::listar_activos - " . $e->getMessage());
            return [];
        }
    }

    public function listar($busqueda = '') {
        try {
            $conn = $this->mysql->obtenerConexion();
            if ($busqueda !== '') {
                $stmt = $conn->prepare(
                    "SELECT id, nombre, estado, createdAt FROM conceptosdotacion
                     WHERE nombre LIKE ? ORDER BY id DESC"
                );
                $stmt->execute(['%' . $busqueda . '%']);
            } else {
                $stmt = $conn->prepare(
                    "SELECT id, nombre, estado, createdAt FROM conceptosdotacion ORDER BY id DESC"
                );
                $stmt->execute();
            }
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("ConceptosDotacion::listar - " . $e->getMessage());
            return [];
        }
    }

    public function get_por_id($id) {
        try {
            $conn = $this->mysql->obtenerConexion();
            $stmt = $conn->prepare("SELECT id, nombre, estado FROM conceptosdotacion WHERE id = ?");
            $stmt->execute([(int)$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("ConceptosDotacion::get_por_id - " . $e->getMessage());
            return null;
        }
    }

    private function existe_nombre($nombre, $excluir_id = null) {
        try {
            $conn = $this->mysql->obtenerConexion();
            if ($excluir_id !== null) {
                $stmt = $conn->prepare("SELECT COUNT(*) FROM conceptosdotacion WHERE nombre = ? AND id <> ?");
                $stmt->execute([$nombre, (int)$excluir_id]);
            } else {
                $stmt = $conn->prepare("SELECT COUNT(*) FROM conceptosdotacion WHERE nombre = ?");
                $stmt->execute([$nombre]);
            }
            return (int)$stmt->fetchColumn() > 0;
        } catch (Exception $e) {
            error_log("ConceptosDotacion::existe_nombre - " . $e->getMessage());
            return false;
        }
    }

    public function crear($nombre, $idUsuario) {
        try {
            $nombre = trim($nombre);
            if ($nombre === '')      return ['status' => 'error', 'message' => 'El nombre no puede estar vacío'];
            if (strlen($nombre) > 100) return ['status' => 'error', 'message' => 'El nombre no puede superar 100 caracteres'];
            if ($this->existe_nombre($nombre)) return ['status' => 'error', 'message' => 'Ya existe un concepto con ese nombre'];

            $conn  = $this->mysql->obtenerConexion();
            $ahora = date('Y-m-d H:i:s');
            $stmt  = $conn->prepare(
                "INSERT INTO conceptosdotacion (nombre, estado, createdAt, updateAt, idUserCreated, idUserModified)
                 VALUES (?, 1, ?, ?, ?, ?)"
            );
            $stmt->execute([$nombre, $ahora, $ahora, $idUsuario, $idUsuario]);
            return ['status' => 'success', 'message' => 'Concepto creado correctamente'];
        } catch (Exception $e) {
            error_log("ConceptosDotacion::crear - " . $e->getMessage());
            return ['status' => 'error', 'message' => 'Error al crear el concepto'];
        }
    }

    public function editar($id, $nombre, $estado, $idUsuario) {
        try {
            $nombre = trim($nombre);
            if ($nombre === '')      return ['status' => 'error', 'message' => 'El nombre no puede estar vacío'];
            if (strlen($nombre) > 100) return ['status' => 'error', 'message' => 'El nombre no puede superar 100 caracteres'];
            if ($this->existe_nombre($nombre, $id)) return ['status' => 'error', 'message' => 'Ya existe un concepto con ese nombre'];

            $conn  = $this->mysql->obtenerConexion();
            $ahora = date('Y-m-d H:i:s');
            $stmt  = $conn->prepare(
                "UPDATE conceptosdotacion SET nombre = ?, estado = ?, updateAt = ?, idUserModified = ? WHERE id = ?"
            );
            $stmt->execute([$nombre, (int)$estado, $ahora, $idUsuario, (int)$id]);
            return ['status' => 'success', 'message' => 'Concepto actualizado correctamente'];
        } catch (Exception $e) {
            error_log("ConceptosDotacion::editar - " . $e->getMessage());
            return ['status' => 'error', 'message' => 'Error al actualizar el concepto'];
        }
    }

    public function cambiar_estado($id, $estado, $idUsuario) {
        try {
            $conn  = $this->mysql->obtenerConexion();
            $ahora = date('Y-m-d H:i:s');
            $stmt  = $conn->prepare(
                "UPDATE conceptosdotacion SET estado = ?, updateAt = ?, idUserModified = ? WHERE id = ?"
            );
            $stmt->execute([(int)$estado, $ahora, $idUsuario, (int)$id]);
            $msg = $estado == 1 ? 'Concepto activado correctamente' : 'Concepto desactivado correctamente';
            return ['status' => 'success', 'message' => $msg];
        } catch (Exception $e) {
            error_log("ConceptosDotacion::cambiar_estado - " . $e->getMessage());
            return ['status' => 'error', 'message' => 'Error al cambiar el estado'];
        }
    }
}
?>
