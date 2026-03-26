<?php
// Solo requiere la conexión SQL Server para la sesión
require_once("../../config/conexionserver.php");

if(isset($_SESSION["Id_Usuario"])) {
    // El modelo ya incluye sus propias conexiones
    require_once("../../models/mdlPermisos.php");
    $permisos = new Permisos();
    $menuUsuario = $permisos->get_menu_usuario($_SESSION["Id_Usuario"]);
?>
<nav class="side-menu">
    <ul class="side-menu-list">
        <?php if (!empty($menuUsuario)): ?>
            <?php foreach ($menuUsuario as $item): ?>
                <li class='blue-dirty'>
                    <a href='<?= $item['ruta'] ?>'>
                        <span class='glyphicon <?= $item['icono'] ?>'></span>
                        <span class='lbl'><?= $item['texto_menu'] ?></span>
                    </a>
                </li>
            <?php endforeach; ?>
        <?php else: ?>
            <li class='blue-dirty'>
                <a href='#'>
                    <span class='glyphicon glyphicon-info-sign'></span>
                    <span class='lbl'>Sin permisos asignados</span>
                </a>
            </li>
        <?php endif; ?>
    </ul>
</nav>
<?php } ?>