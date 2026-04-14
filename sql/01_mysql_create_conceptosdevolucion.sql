-- ============================================================
-- MySQL: Base de datos permisos_tecno
-- Crear tabla conceptosdevolucion
-- ============================================================
CREATE TABLE IF NOT EXISTS conceptosdevolucion (
  id             INT          AUTO_INCREMENT PRIMARY KEY,
  nombre         VARCHAR(50)  NOT NULL,
  estado         INT          NOT NULL DEFAULT 1,
  createdAt      DATETIME     NOT NULL,
  updateAt       DATETIME     NOT NULL,
  idUserCreated  VARCHAR(20)  NULL,
  idUserModified VARCHAR(20)  NULL
);
