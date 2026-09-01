-- ============================================================================
-- Bitácora de auditoría de Documentos / Documentos_Lin.
-- Base: MySQL/MariaDB `permisos_tecno` (la que sí podemos manipular; SQL Server
-- es la BD heredada de Tecnocarnes y no se le pueden agregar tablas ni triggers).
--
-- Por qué existe: hay documentos que pierden líneas de `Documentos_Lin` después
-- de haber sido guardados e impresos, y el código no deja rastro de QUIÉN ni
-- CUÁNDO. Con el PK de Documentos = (sw, tipo, Numero_documento) quedó descartado
-- que dos documentos compartan número en silencio, así que la pérdida viene de
-- una operación de borrado/regeneración legítima ejecutada sobre el documento ya
-- guardado (típicamente Desmarcar -> Reiniciar), o de un actor externo.
--
-- Cómo se usa esta tabla para encontrar el culpable:
--   -- Historia completa de un documento sospechoso:
--   SELECT * FROM auditoria_documentos
--   WHERE tipo = '5' AND numero = 12345 ORDER BY fecha;
--
--   -- Todas las operaciones que redujeron el número de líneas de un documento:
--   SELECT * FROM auditoria_documentos
--   WHERE lineas_despues < lineas_antes ORDER BY fecha DESC;
--
--   -- Quién devolvió a editable un documento ya guardado, y por qué:
--   SELECT fecha, usuario, ip, tipo, numero, lineas_antes, mensaje
--   FROM auditoria_documentos WHERE operacion = 'desmarcar' ORDER BY fecha DESC;
--
--   -- Borrados que se llevaron más de una línea de un solo golpe (seq duplicado):
--   SELECT * FROM auditoria_documentos
--   WHERE operacion = 'delete_id' AND filas_afectadas > 1 ORDER BY fecha DESC;
--
--   -- Intentos bloqueados por las validaciones (documento ya guardado/anulado):
--   SELECT fecha, usuario, ip, operacion, tipo, numero, mensaje
--   FROM auditoria_documentos WHERE resultado = 'bloqueado' ORDER BY fecha DESC;
--
--   -- Recuperar el detalle perdido: `detalle_antes` guarda el JSON de las
--   -- líneas TAL COMO ESTABAN justo antes de la operación destructiva.
--
-- IMPORTANTE: el registro es best-effort. Si MySQL no responde, la operación
-- principal sobre SQL Server NO se interrumpe (ver AuditoriaDocumentos::registrar).
--
-- Ejecutar UNA vez en MySQL/MariaDB.
-- ============================================================================

CREATE TABLE IF NOT EXISTS auditoria_documentos (
    id              BIGINT       NOT NULL AUTO_INCREMENT,
    fecha           DATETIME(3)  NOT NULL,
    usuario         VARCHAR(50)  NULL,
    ip              VARCHAR(45)  NULL,
    modulo          VARCHAR(40)  NOT NULL,   -- 'Salidas' | 'Entradas' | 'GestionDocumentos' | ...
    operacion       VARCHAR(50)  NOT NULL,   -- 'delete_id' | 'reiniciar_doc_desde_os' | 'desmarcar' | ...
    destructiva     TINYINT(1)   NOT NULL DEFAULT 0,  -- 1 = borra o regenera líneas
    tipo            VARCHAR(20)  NULL,
    numero          INT          NULL,
    exportado_antes CHAR(1)      NULL,
    anulado_antes   CHAR(1)      NULL,
    lineas_antes    INT          NULL,
    lineas_despues  INT          NULL,
    filas_afectadas INT          NULL,
    resultado       ENUM('ok','error','bloqueado') NOT NULL DEFAULT 'ok',
    mensaje         VARCHAR(500) NULL,
    detalle_antes   MEDIUMTEXT   NULL,       -- JSON con las líneas previas (solo operaciones destructivas)
    contexto        VARCHAR(255) NULL,       -- request URI / parámetros relevantes
    PRIMARY KEY (id),
    KEY idx_doc         (tipo, numero, fecha),
    KEY idx_fecha       (fecha),
    KEY idx_operacion   (operacion, fecha),
    KEY idx_usuario     (usuario, fecha),
    KEY idx_destructiva (destructiva, fecha)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
