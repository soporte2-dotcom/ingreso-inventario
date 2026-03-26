<?php
require_once("../../config/conexionserver.php");

// Verificar permisos de acceso
if(isset($_SESSION["Id_Usuario"])) {
    require_once("../../models/mdlPermisos.php");
    $permisos = new Permisos();
    $menuUsuario = $permisos->get_menu_usuario($_SESSION["Id_Usuario"]);
    
    // Verificar si tiene permiso para acceder a este módulo
    $tienePermiso = false;
    foreach ($menuUsuario as $item) {
        if (strpos($item['ruta'], 'Permisos') !== false) {
            $tienePermiso = true;
            break;
        }
    }
    
    if (!$tienePermiso) {
        header("Location:../../view/Home/");
        exit();
    }
?>
<!DOCTYPE html>
<html>
<?php require_once("../MainHead/head.php"); ?>
<title>Cervalle:: Administración de Permisos</title>
<style>
/* === Resultados de Búsqueda === */
.search-results {
    position: absolute;
    width: 100%;
    max-height: 200px;
    overflow-y: auto;
    background: white;
    border: 1px solid #ddd;
    border-top: none;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    z-index: 1000;
}

.usuario-item {
    padding: 10px 15px;
    cursor: pointer;
    border-bottom: 1px solid #eee;
    transition: background 0.2s;
}

.usuario-item:hover {
    background-color: #f5f5f5;
}

.no-results {
    padding: 10px 15px;
    color: #999;
    text-align: center;
}

/* === Cards === */
.card {
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    margin-bottom: 20px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.card-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid #e0e0e0;
    padding: 15px 20px;
    border-radius: 8px 8px 0 0;
}

.card-body {
    padding: 20px;
}

/* === Selector de Tipo de Permisos === */
.tipo-permisos-selector {
    margin-bottom: 30px;
    overflow: hidden;
}

.tipo-permisos-selector:after {
    content: "";
    display: table;
    clear: both;
}

.tipo-permisos-selector .permiso-btn {
    float: left;
    width: 49%;
    padding: 15px 20px;
    background-color: #f8f9fa !important;
    border: 2px solid #dee2e6 !important;
    border-radius: 8px;
    cursor: pointer;
    font-size: 16px;
    font-weight: 600;
    color: #6c757d !important;
    text-align: center;
    margin-right: 2%;
    outline: none;
}

.tipo-permisos-selector .permiso-btn:last-child {
    margin-right: 0;
}

.tipo-permisos-selector .permiso-btn:hover {
    background-color: #e9ecef !important;
    border-color: #adb5bd !important;
}

.tipo-permisos-selector .permiso-btn.active {
    background-color: #28a745 !important;
    border-color: #28a745 !important;
    color: #ffffff !important;
    box-shadow: 0 2px 8px rgba(40, 167, 69, 0.4) !important;
}

.tipo-permisos-selector .permiso-btn:focus {
    outline: none !important;
}

@media (max-width: 768px) {
    .tipo-permisos-selector .permiso-btn {
        float: none;
        width: 100%;
        margin-right: 0;
        margin-bottom: 10px;
    }
}

/* === Usuario Seleccionado === */
.user-selection {
    margin-top: 20px;
    padding: 20px;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    background-color: #fafafa;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

/* === Listas de Permisos === */
.modulos-list .checkbox,
.documentos-list .checkbox {
    margin-bottom: 10px;
    padding: 12px 15px;
    border: 1px solid #e0e0e0;
    border-radius: 6px;
    background: white;
    transition: all 0.2s;
}

.modulos-list .checkbox:hover,
.documentos-list .checkbox:hover {
    background-color: #f8f9fa;
    border-color: #007bff;
}

.modulos-list .checkbox label,
.documentos-list .checkbox label {
    margin-left: 8px;
    cursor: pointer;
}

/* === Utilidades === */
.mt-4 { margin-top: 1.5rem; }
.text-muted { color: #6c757d; }
.glyphicon { margin-right: 8px; }
</style>
</head>

<body class="with-side-menu">

    <?php require_once("../MainHeader/header.php"); ?>
    <?php require_once("../MainNav/nav.php"); ?>

    <div class="page-content">
        <div class="container-fluid">

            <!-- Encabezado -->
            <header class="section-header">
                <div class="tbl">
                    <div class="tbl-row">
                        <div class="tbl-cell">
                            <h3>Administración de Permisos</h3>
                            <ol class="breadcrumb breadcrumb-simple">
                                <li><a href="../Home/">Home</a></li>
                                <li class="active">Permisos</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </header>

            <div class="box-typical box-typical-padding">
                
                <form method="post" id="permisos_form">
                    
                    <!-- Selector de Tipo de Permisos -->
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="tipo-permisos-selector">
                                <button type="button" id="btn_permisos_modulos" class="permiso-btn active">
                                    Permisos de Módulos
                                </button>
                                
                                <button type="button" id="btn_permisos_documentos" class="permiso-btn">
                                    Permisos de Documentos
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Búsqueda de Usuario -->
                    <div class="row mt-4">
                        <div class="col-lg-12">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">
                                        <span class="glyphicon glyphicon-user"></span>
                                        Buscar Usuario
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-lg-8">
                                            <fieldset class="form-group">
                                                <label class="form-label semibold">Ingresa ID, nombre o apellido:</label>
                                                <input type="text" 
                                                       id="buscar_usuario" 
                                                       name="buscar_usuario" 
                                                       class="form-control" 
                                                       placeholder="Ejemplo: SA, MARIA, GOMEZ..."
                                                       autocomplete="off">
                                                <div id="resultados_busqueda" class="search-results" style="display: none;"></div>
                                            </fieldset>
                                        </div>
                                        <div class="col-lg-4">
                                            <fieldset class="form-group">
                                                <label class="form-label semibold">&nbsp;</label>
                                                <button type="button" 
                                                        id="btn_buscar" 
                                                        class="btn btn-rounded btn-inline btn-primary w-100" 
                                                        style="height: 46px;">
                                                    <span class="glyphicon glyphicon-search"></span> 
                                                    Buscar Usuario
                                                </button>
                                            </fieldset>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Área de Permisos (se llena dinámicamente) -->
                    <div id="usuario_seleccionado" class="user-selection" style="display: none;"></div>
                    
                    <style>
                    .select-all-container {
                        background: #e7f3ff;
                        padding: 12px 15px;
                        border-radius: 6px;
                        border: 2px solid #007bff;
                        margin-bottom: 15px;
                    }
                    
                    .select-all-container label {
                        font-weight: 600;
                        color: #007bff;
                        margin: 0;
                        cursor: pointer;
                        font-size: 15px;
                    }
                    
                    .select-all-container input[type="checkbox"] {
                        width: 18px;
                        height: 18px;
                        cursor: pointer;
                        margin-right: 10px;
                    }
                    </style>

                    <!-- Campos ocultos -->
                    <input type="hidden" id="usuario_id" name="usuario_id">
                    <input type="hidden" id="tipo_permisos_actual" name="tipo_permisos_actual" value="modulos">
                    
                </form>

            </div>

        </div>
    </div>

    <?php require_once("../MainJs/js.php"); ?>
    <script type="text/javascript" src="permisos.js"></script>

</body>
</html>
<?php
} else {
    header("Location:../../index.php");
}
?>