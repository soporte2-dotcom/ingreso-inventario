-- ============================================================================
-- Registra el módulo "Auditoría de Documentos" (view/Auditoria) en el menú y
-- concede el permiso a quienes hoy administran documentos.
-- Base: MySQL/MariaDB `permisos_tecno`.
--
-- Requiere haber ejecutado antes sql/05_mysql_create_auditoria_documentos.sql.
--
-- El menú lateral se arma leyendo `modulos_sistema`, y el acceso se valida contra
-- `usuario_permisos` (ver Permisos::tiene_permiso_especial). Por eso hacen falta
-- las dos cosas: dar de alta el módulo y otorgar el permiso.
--
-- Ejecutar UNA vez en MySQL/MariaDB.
-- ============================================================================

-- 1. Alta del módulo en el menú.
INSERT INTO modulos_sistema (nombre_modulo, ruta, icono, texto_menu, orden_menu, activo)
SELECT 'auditoria_documentos', '../Auditoria/', 'glyphicon-eye-open',
       'Auditoria de Documentos', 14, 'S'
WHERE NOT EXISTS (
    SELECT 1 FROM modulos_sistema WHERE nombre_modulo = 'auditoria_documentos'
);

-- 2. Permiso para los usuarios que ya tienen Gestión de Documentos.
--    Es el mismo perfil: quien puede desmarcar y anular debe poder revisar el rastro.
--    Para dar el permiso a alguien más:
--      INSERT INTO usuario_permisos (usuario_id, modulo, permiso)
--      VALUES ('NOMBRE_USUARIO', 'auditoria_documentos', 'S');
INSERT INTO usuario_permisos (usuario_id, modulo, permiso)
SELECT up.usuario_id, 'auditoria_documentos', 'S'
FROM usuario_permisos up
WHERE up.modulo = 'gestion_documentos' AND up.permiso = 'S'
  AND NOT EXISTS (
      SELECT 1 FROM usuario_permisos x
      WHERE x.usuario_id = up.usuario_id AND x.modulo = 'auditoria_documentos'
  );

-- 3. Verificación.
SELECT 'modulo' AS que, nombre_modulo AS valor, activo AS estado
FROM modulos_sistema WHERE nombre_modulo = 'auditoria_documentos'
UNION ALL
SELECT 'usuario con acceso', usuario_id, permiso
FROM usuario_permisos WHERE modulo = 'auditoria_documentos';
