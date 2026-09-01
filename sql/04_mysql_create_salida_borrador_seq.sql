-- ============================================================================
-- Generador de id de BORRADOR para el módulo Salidas (consecutivo diferido).
-- Base: MySQL/MariaDB `permisos_tecno` (misma BD que permisos/conceptos/token).
--
-- Problema que resuelve: hoy el consecutivo real se consume al CREAR el
-- documento, dejando encabezados vacíos y huecos en la numeración si el usuario
-- abandona. Con este cambio el documento se crea como BORRADOR con un
-- Numero_Documento NEGATIVO (= -id de esta tabla) y el consecutivo real solo se
-- reserva al Guardar.
--
-- Por qué una tabla en MySQL y no un contador en SQL Server:
--   - No se puede alterar la tabla heredada `Consecutivos`.
--   - AUTO_INCREMENT da ids únicos globales de forma atómica, SIN poner locks
--     sobre la tabla caliente `Documentos` de SQL Server.
--   - Deja un registro de auditoría (quién/cuándo/estado) de cada borrador.
--
-- Uso (ver models/mdlSalidas.php::reservar_id_borrador):
--   1. INSERT INTO salida_borrador_seq (tipo, usuario) VALUES (?, ?);
--      $id = lastInsertId();  ->  el borrador usa Numero_Documento = -$id
--   2. Al Guardar: UPDATE ... SET estado='guardado', numero_real=? WHERE id=?
--   3. Al descartar/purgar: UPDATE ... SET estado='descartado' WHERE id=?
--
-- Si la tabla NO existe, la creación de salidas falla de forma controlada
-- (MySQL ya es dependencia dura de Salidas por la validación de conceptos).
--
-- Ejecutar UNA vez en MySQL/MariaDB.
-- ============================================================================

CREATE TABLE IF NOT EXISTS salida_borrador_seq (
    id          INT          NOT NULL AUTO_INCREMENT,
    tipo        INT          NOT NULL,
    usuario     VARCHAR(50)  NULL,
    estado      ENUM('borrador','guardado','descartado') NOT NULL DEFAULT 'borrador',
    numero_real INT          NULL,
    creado      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_estado_creado (estado, creado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
