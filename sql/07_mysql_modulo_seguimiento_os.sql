-- ============================================================================
-- Registra el módulo "Seguimiento de Órdenes de Salida" (view/SeguimientoOS) en el
-- menú y concede el permiso a quienes ya trabajan con Salidas.
-- Base: MySQL/MariaDB `permisos_tecno`.
--
-- El módulo permite consultar una OS y ver, ítem por ítem, en qué documentos se
-- descontó cada línea, cuánto y cuándo.
--
-- El menú lateral se arma leyendo `modulos_sistema`, y el acceso se valida contra
-- `usuario_permisos` (ver Permisos::tiene_permiso_especial). Por eso hacen falta las
-- dos cosas: dar de alta el módulo y otorgar el permiso.
--
-- Ejecutar UNA vez en MySQL/MariaDB.
-- ============================================================================

-- 1. Alta del módulo en el menú.
INSERT INTO modulos_sistema (nombre_modulo, ruta, icono, texto_menu, orden_menu, activo)
SELECT 'seguimiento_os', '../SeguimientoOS/', 'glyphicon-search',
       'Seguimiento de OS', 15, 'S'
WHERE NOT EXISTS (
    SELECT 1 FROM modulos_sistema WHERE nombre_modulo = 'seguimiento_os'
);

-- 2. Permiso para los usuarios que ya tienen acceso al módulo de Salidas: es una
--    consulta de solo lectura sobre las órdenes que ellos mismos despachan.
--    Para dárselo a alguien más:
--      INSERT INTO usuario_permisos (usuario_id, modulo, permiso)
--      VALUES ('NOMBRE_USUARIO', 'seguimiento_os', 'S');
INSERT INTO usuario_permisos (usuario_id, modulo, permiso)
SELECT up.usuario_id, 'seguimiento_os', 'S'
FROM usuario_permisos up
WHERE up.modulo = 'Salidas' AND up.permiso = 'S'
  AND NOT EXISTS (
      SELECT 1 FROM usuario_permisos x
      WHERE x.usuario_id = up.usuario_id AND x.modulo = 'seguimiento_os'
  );

-- 3. Verificación.
SELECT 'modulo' AS que, nombre_modulo AS valor, activo AS estado
FROM modulos_sistema WHERE nombre_modulo = 'seguimiento_os'
UNION ALL
SELECT 'usuarios con acceso', CAST(COUNT(*) AS CHAR), 'S'
FROM usuario_permisos WHERE modulo = 'seguimiento_os' AND permiso = 'S';
