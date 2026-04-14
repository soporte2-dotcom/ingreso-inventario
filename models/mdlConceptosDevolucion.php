<?php
require_once(__DIR__ . "/../config/conexionmysql.php");

class ConceptosDevolucion {
    private $mysql;

    public function __construct() {
        $this->mysql = new ConectarMysql();
    }

    // Listar solo conceptos activos (para selects/combos)
    public function listar_activos() {
        try {
            $conn = $this->mysql->obtenerConexion();
            $stmt = $conn->prepare("SELECT id, nombre FROM conceptosdevolucion WHERE estado = 1 ORDER BY nombre ASC");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("ConceptosDevolucion::listar_activos - " . $e->getMessage());
            return [];
        }
    }

    // Listar todos los conceptos (para DataTable admin)
    public function listar($busqueda = '') {
        try {
            $conn = $this->mysql->obtenerConexion();
            if ($busqueda !== '') {
                $stmt = $conn->prepare(
                    "SELECT id, nombre, estado, createdAt FROM conceptosdevolucion
                     WHERE nombre LIKE ? ORDER BY id DESC"
                );
                $stmt->execute(['%' . $busqueda . '%']);
            } else {
                $stmt = $conn->prepare(
                    "SELECT id, nombre, estado, createdAt FROM conceptosdevolucion ORDER BY id DESC"
                );
                $stmt->execute();
            }
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("ConceptosDevolucion::listar - " . $e->getMessage());
            return [];
        }
    }

    // Obtener un concepto por id
    public function get_por_id($id) {
        try {
            $conn = $this->mysql->obtenerConexion();
            $stmt = $conn->prepare(
                "SELECT id, nombre, estado FROM conceptosdevolucion WHERE id = ?"
            );
            $stmt->execute([(int)$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("ConceptosDevolucion::get_por_id - " . $e->getMessage());
            return null;
        }
    }

    // Validar que un concepto exista y esté activo
    public function validar_activo($id) {
        try {
            $conn = $this->mysql->obtenerConexion();
            $stmt = $conn->prepare(
                "SELECT nombre FROM conceptosdevolucion WHERE id = ? AND estado = 1"
            );
            $stmt->execute([(int)$id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ? $row['nombre'] : null;
        } catch (Exception $e) {
            error_log("ConceptosDevolucion::validar_activo - " . $e->getMessage());
            return null;
        }
    }

    // Verificar nombre duplicado
    private function existe_nombre($nombre, $excluir_id = null) {
        try {
            $conn = $this->mysql->obtenerConexion();
            if ($excluir_id !== null) {
                $stmt = $conn->prepare(
                    "SELECT COUNT(*) FROM conceptosdevolucion WHERE nombre = ? AND id <> ?"
                );
                $stmt->execute([$nombre, (int)$excluir_id]);
            } else {
                $stmt = $conn->prepare(
                    "SELECT COUNT(*) FROM conceptosdevolucion WHERE nombre = ?"
                );
                $stmt->execute([$nombre]);
            }
            return (int)$stmt->fetchColumn() > 0;
        } catch (Exception $e) {
            error_log("ConceptosDevolucion::existe_nombre - " . $e->getMessage());
            return false;
        }
    }

    // Crear concepto
    public function crear($nombre, $idUsuario) {
        try {
            $nombre = trim($nombre);
            if ($nombre === '') {
                return ['status' => 'error', 'message' => 'El nombre no puede estar vacío'];
            }
            if (strlen($nombre) > 50) {
                return ['status' => 'error', 'message' => 'El nombre no puede superar 50 caracteres'];
            }
            if ($this->existe_nombre($nombre)) {
                return ['status' => 'error', 'message' => 'Ya existe un concepto con ese nombre'];
            }
            $conn  = $this->mysql->obtenerConexion();
            $ahora = date('Y-m-d H:i:s');
            $stmt  = $conn->prepare(
                "INSERT INTO conceptosdevolucion (nombre, estado, createdAt, updateAt, idUserCreated, idUserModified)
                 VALUES (?, 1, ?, ?, ?, ?)"
            );
            $stmt->execute([$nombre, $ahora, $ahora, $idUsuario, $idUsuario]);
            return ['status' => 'success', 'message' => 'Concepto creado correctamente'];
        } catch (Exception $e) {
            error_log("ConceptosDevolucion::crear - " . $e->getMessage());
            return ['status' => 'error', 'message' => 'Error al crear el concepto'];
        }
    }

    // Editar concepto
    public function editar($id, $nombre, $estado, $idUsuario) {
        try {
            $nombre = trim($nombre);
            if ($nombre === '') {
                return ['status' => 'error', 'message' => 'El nombre no puede estar vacío'];
            }
            if (strlen($nombre) > 50) {
                return ['status' => 'error', 'message' => 'El nombre no puede superar 50 caracteres'];
            }
            if ($this->existe_nombre($nombre, $id)) {
                return ['status' => 'error', 'message' => 'Ya existe un concepto con ese nombre'];
            }
            $conn  = $this->mysql->obtenerConexion();
            $ahora = date('Y-m-d H:i:s');
            $stmt  = $conn->prepare(
                "UPDATE conceptosdevolucion SET nombre = ?, estado = ?, updateAt = ?, idUserModified = ?
                 WHERE id = ?"
            );
            $stmt->execute([$nombre, (int)$estado, $ahora, $idUsuario, (int)$id]);
            return ['status' => 'success', 'message' => 'Concepto actualizado correctamente'];
        } catch (Exception $e) {
            error_log("ConceptosDevolucion::editar - " . $e->getMessage());
            return ['status' => 'error', 'message' => 'Error al actualizar el concepto'];
        }
    }

    // Cambiar estado (activar/inactivar)
    public function cambiar_estado($id, $estado, $idUsuario) {
        try {
            $conn  = $this->mysql->obtenerConexion();
            $ahora = date('Y-m-d H:i:s');
            $stmt  = $conn->prepare(
                "UPDATE conceptosdevolucion SET estado = ?, updateAt = ?, idUserModified = ? WHERE id = ?"
            );
            $stmt->execute([(int)$estado, $ahora, $idUsuario, (int)$id]);
            $msg = $estado == 1 ? 'Concepto activado correctamente' : 'Concepto desactivado correctamente';
            return ['status' => 'success', 'message' => $msg];
        } catch (Exception $e) {
            error_log("ConceptosDevolucion::cambiar_estado - " . $e->getMessage());
            return ['status' => 'error', 'message' => 'Error al cambiar el estado'];
        }
    }
}
?>
