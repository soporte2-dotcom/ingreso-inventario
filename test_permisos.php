<?php
// Quitar el session_start() ya que conexionserver.php ya lo tiene
// session_start();

require_once("config/conexionserver.php");
require_once("models/mdlPermisos.php");

echo "<h3>Test del Sistema de Permisos</h3>";

try {
    $permisos = new Permisos();
    
    echo "<h4>1. Probando conexión MySQL - Módulos:</h4>";
    $modulos = $permisos->get_modulos();
    if (is_array($modulos) && count($modulos) > 0) {
        echo "✅ " . count($modulos) . " módulos encontrados<br>";
        foreach ($modulos as $modulo) {
            echo "&nbsp;&nbsp;• {$modulo['texto_menu']} ({$modulo['nombre_modulo']})<br>";
        }
    } else {
        echo "❌ No se encontraron módulos<br>";
    }
    
    echo "<h4>2. Probando conexión SQL Server - Buscar usuario 'SA':</h4>";
    $usuarios = $permisos->buscar_usuario("SA");
    if (is_array($usuarios) && count($usuarios) > 0) {
        echo "✅ " . count($usuarios) . " usuarios encontrados<br>";
        foreach ($usuarios as $usuario) {
            echo "&nbsp;&nbsp;• {$usuario['Id_Usuario']} - {$usuario['Nom_Usuario']} {$usuario['Ape_Usuario']}<br>";
        }
    } else {
        echo "❌ No se encontraron usuarios o error en conexión SQL Server<br>";
    }
    
    echo "<h4>3. Probando permisos del usuario 'SA':</h4>";
    $permisos_usuario = $permisos->get_permisos_usuario("SA");
    if (is_array($permisos_usuario) && count($permisos_usuario) > 0) {
        echo "✅ " . count($permisos_usuario) . " permisos encontrados<br>";
        foreach ($permisos_usuario as $permiso) {
            $estado = ($permiso['permiso'] == 'S') ? '✅ Activo' : '❌ Inactivo';
            echo "&nbsp;&nbsp;• {$permiso['texto_menu']}: {$estado}<br>";
        }
    } else {
        echo "ℹ️ No se encontraron permisos para este usuario<br>";
    }
    
    echo "<h4>4. Probando menú del usuario 'SA':</h4>";
    $menu = $permisos->get_menu_usuario("SA");
    if (is_array($menu) && count($menu) > 0) {
        echo "✅ " . count($menu) . " items de menú encontrados<br>";
        foreach ($menu as $item) {
            echo "&nbsp;&nbsp;• {$item['texto_menu']} ({$item['ruta']})<br>";
        }
    } else {
        echo "ℹ️ No se encontraron items de menú para este usuario<br>";
    }
    
    echo "<h4>5. Verificando sesión actual:</h4>";
    if (isset($_SESSION["Id_Usuario"])) {
        echo "✅ Usuario en sesión: " . $_SESSION["Id_Usuario"] . "<br>";
        
        // Probar menú del usuario actual
        $menu_actual = $permisos->get_menu_usuario($_SESSION["Id_Usuario"]);
        echo "&nbsp;&nbsp;Items de menú para sesión actual: " . count($menu_actual) . "<br>";
    } else {
        echo "❌ No hay usuario en sesión<br>";
    }
    
} catch (Exception $e) {
    echo "<div style='color: red;'><h4>Error:</h4>" . $e->getMessage() . "</div>";
    echo "<pre>Stack trace:\n" . $e->getTraceAsString() . "</pre>";
}
?>