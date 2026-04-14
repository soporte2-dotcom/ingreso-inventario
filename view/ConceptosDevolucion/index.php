<?php
require_once("../../config/conexionserver.php");
if (isset($_SESSION["Id_Usuario"])) {
date_default_timezone_set("America/Bogota");
?>
<!DOCTYPE html>
<html>
<?php require_once("../MainHead/head.php"); ?>
<title>Cervalle :: Conceptos de Devolución</title>
</head>

<body class="with-side-menu sidebar-hidden">

    <?php require_once("../MainHeader/header.php"); ?>

    <div class="mobile-menu-left-overlay"></div>

    <?php require_once("../MainNav/nav.php"); ?>

    <!-- Contenido -->
    <div class="page-content">
        <div class="container-fluid">

            <header class="section-header">
                <div class="tbl">
                    <div class="tbl-row">
                        <div class="tbl-cell">
                            <h3>Conceptos de Devolución</h3>
                            <ol class="breadcrumb breadcrumb-simple">
                                <li><a href="#">Home</a></li>
                                <li class="active">Conceptos de Devolución</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </header>

            <div class="box-typical box-typical-padding">

                <!-- Barra de acciones -->
                <div class="row mb-3">
                    <div class="col-sm-6">
                        <button type="button" class="btn btn-success btn-rounded" onclick="abrirModalCrear()">
                            <i class="fa fa-plus"></i> Nuevo Concepto
                        </button>
                    </div>
                    <div class="col-sm-6">
                        <div class="input-group">
                            <input type="text" id="inputBusqueda" class="form-control"
                                   placeholder="Buscar por nombre..." autocomplete="off">
                            <span class="input-group-btn">
                                <button class="btn btn-info" type="button" onclick="buscar()">
                                    <i class="fa fa-search"></i> Buscar
                                </button>
                                <button class="btn btn-default" type="button" onclick="limpiarBusqueda()">
                                    <i class="fa fa-times"></i>
                                </button>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Tabla -->
                <div class="row">
                    <div class="col-lg-12">
                        <table id="tbConceptos" class="table table-bordered table-striped table-vcenter js-dataTable-full">
                            <thead>
                                <tr>
                                    <th class="text-center" style="width:60px">ID</th>
                                    <th class="text-center">Nombre</th>
                                    <th class="text-center" style="width:100px">Estado</th>
                                    <th class="text-center" style="width:150px">Fecha Creación</th>
                                    <th class="text-center" style="width:120px">Acciones</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>
    <!-- Fin Contenido -->

    <!-- ── Modal Crear / Editar ──────────────────────────────────────────────── -->
    <div class="modal fade" id="modalConcepto" tabindex="-1" role="dialog"
         aria-labelledby="modalConceptoTitle" aria-hidden="true"
         data-backdrop="static" data-keyboard="false">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalConceptoTitle">Nuevo Concepto</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="conceptoId">

                    <div class="form-group">
                        <label class="font-weight-bold">Nombre <span class="text-danger">*</span></label>
                        <input type="text" id="conceptoNombre" class="form-control"
                               placeholder="Ingrese el concepto de devolución" maxlength="50" autocomplete="off">
                        <small class="form-text text-muted">Máximo 50 caracteres.</small>
                    </div>

                    <div class="form-group" id="divEstado" style="display:none">
                        <label class="font-weight-bold">Estado</label>
                        <select id="conceptoEstado" class="form-control">
                            <option value="1">Activo</option>
                            <option value="0">Inactivo</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="button" id="btnGuardarConcepto" class="btn btn-success" onclick="guardarConcepto()">
                        <i class="fa fa-save"></i> Guardar
                    </button>
                </div>
            </div>
        </div>
    </div>
    <!-- Fin Modal Crear/Editar -->

    <?php require_once("../MainJs/js.php"); ?>
    <script src="conceptos.js?v=1"></script>

</body>
</html>
<?php
} else {
    header("Location:../../index.php");
}
?>
