<?php
/**
 * Script de migración - EJECUTAR UNA SOLA VEZ
 */

// Configuración de la base de datos
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'permisos_tecno');

function conectarBD() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        die("Error de conexión: " . $conn->connect_error);
    }
    return $conn;
}

function asignarPermiso($db, $usuario, $modulo, $permiso) {
    $query = "INSERT INTO usuario_permisos (usuario_id, modulo, permiso) VALUES (?, ?, ?) 
              ON DUPLICATE KEY UPDATE permiso = ?";
    $stmt = $db->prepare($query);
    $stmt->bind_param("ssss", $usuario, $modulo, $permiso, $permiso);
    
    if ($stmt->execute()) {
        echo "✓ Permiso asignado: $usuario -> $modulo<br>";
    } else {
        echo "✗ Error: $usuario -> $modulo: " . $stmt->error . "<br>";
    }
    
    $stmt->close();
}

function migrarPermisosABD() {
    echo "<h3>Iniciando migración de permisos...</h3>";
    $db = conectarBD();
    
    // Tus arrays originales
    $adminUsuarios = ['LUISBERO', 'ANDREACR', 'YULIGIRA'];
    $usuariosConAccesoCompleto = ['LAUREN', 'CAVEN', 'SOSMA', 'SA', 'DBERRIO', 'ANASPROD', 'SVALEN', 'ANASINV', 'JGOMEZ', 'NRESTREP'];
    $usuariosConAccesoUtilidades = ['BREN', 'YEISONT', 'JVELEZ', 'ELIMAR', 'JPOLANCO', 'SJARAMI', 'LACRUZ'];
    $usuariosConAccesoEntradas = ['NPADILLA', 'MILADYS', 'KARINA', 'JCABAL', 'JNARVAEZ', 'SERNEL', 'WORDUZ', 'JVELASCO', 'LSALAS', 'DANYO', 'YOLANDAT', 'ESIERRA', 'LGARCIA', 'JALVAREZ', 'MICHAELK','AMEJIA','JRIVAS', 'LCASTILL', 'MMORALES', 'JMENDOZA', 'VGARZON'];

    echo "<h4>Migrando administradores...</h4>";
    foreach ($adminUsuarios as $usuario) {
        asignarPermiso($db, $usuario, 'cambio_fecha', 'S');
    }

    echo "<h4>Migrando acceso completo...</h4>";
    foreach ($usuariosConAccesoCompleto as $usuario) {
        asignarPermiso($db, $usuario, 'inicio', 'S');
        asignarPermiso($db, $usuario, 'inventarios', 'S');
        asignarPermiso($db, $usuario, 'entradas', 'S');
        asignarPermiso($db, $usuario, 'utilidades', 'S');
        asignarPermiso($db, $usuario, 'cambio_fecha', 'S');
        asignarPermiso($db, $usuario, 'actualizar_lotes', 'S');
    }

    echo "<h4>Migrando acceso a utilidades...</h4>";
    foreach ($usuariosConAccesoUtilidades as $usuario) {
        asignarPermiso($db, $usuario, 'inicio', 'S');
        asignarPermiso($db, $usuario, 'inventarios', 'S');
        asignarPermiso($db, $usuario, 'entradas', 'S');
        asignarPermiso($db, $usuario, 'utilidades', 'S');
    }

    echo "<h4>Migrando solo entradas...</h4>";
    foreach ($usuariosConAccesoEntradas as $usuario) {
        asignarPermiso($db, $usuario, 'entradas', 'S');
    }

    $db->close();
    echo "<h3 style='color: green;'>Migración completada!</h3>";
}

// EJECUTAR MIGRACIÓN (quitar el comentario para ejecutar)
migrarPermisosABD();

?>