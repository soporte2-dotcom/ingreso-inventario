-- ============================================================================
-- Tabla de idempotencia para la carga masiva por Excel (inventario y pedidos).
-- Base: MySQL/MariaDB `permisos_tecno` (misma BD que permisos/conceptos).
--
-- Patrón "claim/confirm" (la tabla vive en OTRA base que Documentos_Lin, así que
-- no comparte la transacción de SQL Server; por eso se usa una máquina de estados):
--   1. Al empezar la confirmación se INSERTA el token con estado 'procesando'.
--      La PK garantiza que solo una petición gane; un reintento/concurrente que
--      reenvía el mismo token choca con la PK y el backend responde "ya procesada".
--   2. Tras el COMMIT de las líneas en SQL Server, se marca estado 'ok'.
--   3. Si la carga falla (rollback) o el documento estaba ocupado, se BORRA el
--      token para que un reintento legítimo pueda volver a procesarse.
--
-- Hueco residual: un crash entre el paso 1 y el commit deja el token en
-- 'procesando'. Se auto-resuelve: un token 'procesando' más viejo que
-- TOKEN_STALE_MIN (ver Documento::reclamar_token_carga) se considera abandonado
-- y el nuevo intento lo toma. Mientras la tabla NO exista, la carga funciona
-- igual pero SIN protección de reintento.
--
-- Ejecutar UNA vez en MySQL/MariaDB.
-- ============================================================================

CREATE TABLE IF NOT EXISTS cargamasivatoken (
    token     VARCHAR(64)  NOT NULL,
    tipo      VARCHAR(20)  NULL,
    numdoc    INT          NULL,
    usuario   VARCHAR(50)  NULL,
    filas     INT          NULL,
    estado    VARCHAR(15)  NOT NULL DEFAULT 'procesando',  -- 'procesando' | 'ok'
    createdAt DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updatedAt DATETIME     NULL,
    PRIMARY KEY (token),
    KEY idx_estado_created (estado, createdAt)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
