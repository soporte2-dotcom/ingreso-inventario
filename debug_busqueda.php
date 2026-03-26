<?php
require_once("config/conexionserver.php");
require_once("models/mdlPermisos.php");

echo "<h3>Debug de Búsqueda de Usuarios</h3>";

$permisos = new Permisos();

// Probar diferentes términos de búsqueda
$terminos_prueba = ['SA', 'A', 'ADMIN', ''];

foreach ($terminos_prueba as $termino) {
    echo "<h4>Buscando: '$termino'</h4>";
    
    $usuarios = $permisos->buscar_usuario($termino);
    
    if (is_array($usuarios)) {
        echo "Número de usuarios encontrados: " . count($usuarios) . "<br>";
        
        if (count($usuarios) > 0) {
            echo "<table border='1' style='border-collapse: collapse;'>";
            echo "<tr><th>ID Usuario</th><th>Nombre</th><th>Apellido</th></tr>";
            foreach ($usuarios as $usuario) {
                echo "<tr>";
                echo "<td>" . $usuario['Id_Usuario'] . "</td>";
                echo "<td>" . $usuario['Nom_Usuario'] . "</td>";
                echo "<td>" . $usuario['Ape_Usuario'] . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "No se encontraron usuarios<br>";
        }
    } else {
        echo "Error: La función no retornó un array<br>";
    }
    echo "<hr>";
}

// Probar consulta SQL directa
echo "<h4>Consulta SQL Directa:</h4>";
try {
    $cn_sqlserver = new Conectarserver();
    
    // Consulta 1: Buscar específicamente 'SA'
    $query1 = "SELECT Id_Usuario, Nom_Usuario, Ape_Usuario 
               FROM TblUsuarios 
               WHERE Id_Usuario = 'SA'";
    
    echo "<strong>Consulta 1 (Id_Usuario = 'SA'):</strong><br>";
    $registros1 = sqlsrv_query($cn_sqlserver->getConecta(), $query1);
    if ($registros1 === false) {
        echo "Error en consulta: " . print_r(sqlsrv_errors(), true) . "<br>";
    } else {
        $contador = 0;
        while ($fila = sqlsrv_fetch_array($registros1, SQLSRV_FETCH_ASSOC)) {
            $contador++;
            echo "Usuario encontrado: " . $fila['Id_Usuario'] . " - " . $fila['Nom_Usuario'] . " " . $fila['Ape_Usuario'] . "<br>";
        }
        if ($contador == 0) {
            echo "No se encontró el usuario 'SA'<br>";
        }
    }
    
    // Consulta 2: Buscar con LIKE
    $query2 = "SELECT Id_Usuario, Nom_Usuario, Ape_Usuario 
               FROM TblUsuarios 
               WHERE (Id_Usuario LIKE '%SA%' OR Nom_Usuario LIKE '%SA%' OR Ape_Usuario LIKE '%SA%')  
               ORDER BY Id_Usuario ASC";
    
    echo "<br><strong>Consulta 2 (LIKE '%SA%'):</strong><br>";
    $registros2 = sqlsrv_query($cn_sqlserver->getConecta(), $query2);
    if ($registros2 === false) {
        echo "Error en consulta: " . print_r(sqlsrv_errors(), true) . "<br>";
    } else {
        $contador = 0;
        while ($fila = sqlsrv_fetch_array($registros2, SQLSRV_FETCH_ASSOC)) {
            $contador++;
            echo "Usuario encontrado: " . $fila['Id_Usuario'] . " - " . $fila['Nom_Usuario'] . " " . $fila['Ape_Usuario'] . "<br>";
        }
        if ($contador == 0) {
            echo "No se encontraron usuarios con 'SA'<br>";
        }
    }
    
    // Consulta 3: Ver todos los usuarios activos
    $query3 = "SELECT TOP 10 Id_Usuario, Nom_Usuario, Ape_Usuario 
               FROM TblUsuarios 
               ORDER BY Id_Usuario ASC";
    
    echo "<br><strong>Consulta 3 (Top 10 usuarios activos):</strong><br>";
    $registros3 = sqlsrv_query($cn_sqlserver->getConecta(), $query3);
    if ($registros3 === false) {
        echo "Error en consulta: " . print_r(sqlsrv_errors(), true) . "<br>";
    } else {
        $contador = 0;
        while ($fila = sqlsrv_fetch_array($registros3, SQLSRV_FETCH_ASSOC)) {
            $contador++;
            echo $fila['Id_Usuario'] . " - " . $fila['Nom_Usuario'] . " " . $fila['Ape_Usuario'] . "<br>";
        }
        echo "Total: " . $contador . " usuarios activos<br>";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "<br>";
}
?>