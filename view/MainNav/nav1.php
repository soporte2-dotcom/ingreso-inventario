<?php
/**
 * Menú lateral con permisos de usuario
 * 
 * Este código organiza el menú lateral basado en permisos de usuario
 * utilizando arrays para definir los usuarios y sus permisos
 * Los usuarios que no estén en ninguna lista no verán ningún elemento del menú
 */

// Definición de grupos de usuarios y sus permisos
$adminUsuarios = ['LUISBERO', 'ANDREACR', 'YULIGIRA'];

$usuariosConAccesoCompleto = ['LAUREN', 'CAVEN', 'SOSMA', 'SA', 'DBERRIO', 'ANASPROD', 'SVALEN', 'ANASINV', 'JGOMEZ', 'NRESTREP'];
                             
$usuariosConAccesoUtilidades = ['BREN', 'YEISONT', 'JVELEZ', 'ELIMAR', 'JPOLANCO', 'SJARAMI', 'LACRUZ', 'CAMPLAZA'];

$usuariosConAccesoEntradas = ['NPADILLA', 'MILADYS', 'KARINA', 'JCABAL', 'JNARVAEZ', 'SERNEL', 'WORDUZ', 'JVELASCO', 
'LSALAS', 'JNARVAEZ', 'DANYO', 'YOLANDAT', 'ESIERRA', 'LGARCIA', 'JALVAREZ', 'MICHAELK','AMEJIA','JRIVAS', 'LCASTILL', 'JMENDOZA', 'JMENDOZA', 'VGARZON', 'DAYANALE',
'ABOLIVAR', 'YDIAZ', 'MBOTINA', 'JALVAREZ'];

// Obtener usuario actual
$usuarioActual = $_SESSION["Id_Usuario"];

// Definir los enlaces del menú por tipo de acceso
$menuAdmin = [
    ['url' => '..\CambioFecha\\', 'icono' => 'glyphicon-th', 'texto' => 'Cambio Fecha']
];

$menuEstandar = [
    ['url' => '#', 'icono' => 'glyphicon-th', 'texto' => 'Inicio'],
    ['url' => '..\NuevoDoc\\', 'icono' => 'glyphicon-th', 'texto' => 'Inventarios'],
    ['url' => '..\ConsultarEntradas\\', 'icono' => 'glyphicon-th', 'texto' => 'Entradas']
];

$menuUtilidades = [
    ['url' => '..\Utilidades\\', 'icono' => 'glyphicon-th', 'texto' => 'Utilidades Dian']
];

$menuAccesoCompleto = [
    ['url' => '..\CambioFecha\\', 'icono' => 'glyphicon-th', 'texto' => 'Cambio Fecha'],
    ['url' => '..\Lotes\\', 'icono' => 'glyphicon-th', 'texto' => 'Actualizar Lotes y Notas']
];

$menuEntradas = [
    ['url' => '..\ConsultarEntradas\\', 'icono' => 'glyphicon-th', 'texto' => 'Entradas']
];

// Comprobar si el usuario actual está en alguna de las listas
$usuarioTieneAcceso = in_array($usuarioActual, $adminUsuarios) || 
                      in_array($usuarioActual, $usuariosConAccesoCompleto) || 
                      in_array($usuarioActual, $usuariosConAccesoUtilidades) || 
                      in_array($usuarioActual, $usuariosConAccesoEntradas);
?>

<nav class="side-menu">
    <ul class="side-menu-list">
        <?php
        // Solo mostrar el menú si el usuario tiene acceso
        if ($usuarioTieneAcceso) {
            // Mostrar menú según el tipo de usuario
            if (in_array($usuarioActual, $adminUsuarios)) {
                // Mostrar menú para administradores
                foreach ($menuAdmin as $item) {
                    echo "<li class='blue-dirty'>
                            <a href='{$item['url']}'>
                                <span class='glyphicon {$item['icono']}'></span>
                                <span class='lbl'>{$item['texto']}</span>
                            </a>
                          </li>";
                }
            } elseif (in_array($usuarioActual, $usuariosConAccesoEntradas)) {
                // Mostrar SOLO menú de entradas para estos usuarios
                foreach ($menuEntradas as $item) {
                    echo "<li class='blue-dirty'>
                            <a href='{$item['url']}'>
                                <span class='glyphicon {$item['icono']}'></span>
                                <span class='lbl'>{$item['texto']}</span>
                            </a>
                          </li>";
                }
            } else {
                // Mostrar menú estándar para todos los demás usuarios que tienen acceso
                foreach ($menuEstandar as $item) {
                    echo "<li class='blue-dirty'>
                            <a href='{$item['url']}'>
                                <span class='glyphicon {$item['icono']}'></span>
                                <span class='lbl'>{$item['texto']}</span>
                            </a>
                          </li>";
                }
                
                // Agregar elementos para usuarios con acceso completo o acceso a utilidades
                if (in_array($usuarioActual, $usuariosConAccesoCompleto)) {
                    // Mostrar menú de utilidades
                    foreach ($menuUtilidades as $item) {
                        echo "<li class='blue-dirty'>
                                <a href='{$item['url']}'>
                                    <span class='glyphicon {$item['icono']}'></span>
                                    <span class='lbl'>{$item['texto']}</span>
                                </a>
                              </li>";
                    }
                    
                    // Mostrar menú de acceso completo
                    foreach ($menuAccesoCompleto as $item) {
                        echo "<li class='blue-dirty'>
                                <a href='{$item['url']}'>
                                    <span class='glyphicon {$item['icono']}'></span>
                                    <span class='lbl'>{$item['texto']}</span>
                                </a>
                              </li>";
                    }
                } elseif (in_array($usuarioActual, $usuariosConAccesoUtilidades)) {
                    // Mostrar solo menú de utilidades para estos usuarios
                    foreach ($menuUtilidades as $item) {
                        echo "<li class='blue-dirty'>
                                <a href='{$item['url']}'>
                                    <span class='glyphicon {$item['icono']}'></span>
                                    <span class='lbl'>{$item['texto']}</span>
                                </a>
                              </li>";
                    }
                }
            }
        }
        // Si el usuario no tiene acceso, no visualiza ningún elemento del menú
        ?>
    </ul>
</nav>