-- ============================================================
-- SQL Server: agregar columna idConceptoDevolucion a Documentos
-- Ejecutar una sola vez en la base de datos de produccion
-- ============================================================
IF NOT EXISTS (
    SELECT 1
    FROM   sys.columns
    WHERE  object_id = OBJECT_ID('Documentos')
      AND  name      = 'idConceptoDevolucion'
)
BEGIN
    ALTER TABLE Documentos
        ADD idConceptoDevolucion INT NULL;
END

-- ============================================================
-- SQL Server: ampliar RespuestaCorrectaDian para concepto devolucion
-- Ejecutar una sola vez en la base de datos de produccion
-- ============================================================
IF EXISTS (
    SELECT 1 FROM sys.columns
    WHERE object_id = OBJECT_ID('Documentos') AND name = 'RespuestaCorrectaDian'
      AND max_length < 100
)
BEGIN
    ALTER TABLE Documentos
        ALTER COLUMN RespuestaCorrectaDian VARCHAR(100) NULL;
END
