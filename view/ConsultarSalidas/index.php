<?php
require_once("../../config/conexionserver.php");
if(isset($_SESSION["Id_Usuario"])){
date_default_timezone_set("America/Bogota");
$DateAndTime = date('d-m-Y h:i:s', time());
?>
<!DOCTYPE html>
<html>
    <?php require_once("../MainHead/head.php");?>
	<title>Cervalle::Consultar Salidas</title>
</head>
<body class="with-side-menu">

    <?php require_once("../MainHeader/header.php");?>

    <div class="mobile-menu-left-overlay"></div>
    
    <?php require_once("../MainNav/nav.php");?>

	<!-- Contenido -->
	<div class="page-content">
		<div class="container-fluid">

			<header class="section-header">
				<div class="tbl">
					<div class="tbl-row">
						<div class="tbl-cell">
							<h3>Consultar Salidas y Consumos</h3>
							<ol class="breadcrumb breadcrumb-simple">
								<li><a href="#">Home</a></li>
								<li class="active">Consultar Salidas y Consumos</li>
							</ol>
						</div>

					</div>
				</div>
			</header>
			<form  method="post" id="doc_form">	
          
            <div class="tbl-cell tbl-cell-action">
                <a href="../Salidas/index.php" class="btn btn-primary btn-md">
                    <i class="fa fa-plus-circle"></i> Nueva Salida y/o Consumo
                </a>
            </div>

        <div class="box-typical box-typical-padding">

          <div class="row">
            <div class="col-lg-3">
              <div class="form-group">
                <label for="filtroTipo">Tipo de Documento:</label>
                <select id="filtroTipo" class="form-control"></select>
              </div>
            </div>
            <div class="col-lg-2">
              <div class="form-group">
                <label for="filtroExportado">Exportado:</label>
                <select id="filtroExportado" class="form-control">
                  <option value="">Todos</option>
                  <option value="N">No (borrador)</option>
                  <option value="S">Sí (guardado)</option>
                </select>
              </div>
            </div>
            <div class="col-lg-2">
              <div class="form-group">
                <label for="filtroFechaDesde">Fecha Desde:</label>
                <input type="date" class="form-control" id="filtroFechaDesde">
              </div>
            </div>
            <div class="col-lg-2">
              <div class="form-group">
                <label for="filtroFechaHasta">Fecha Hasta:</label>
                <input type="date" class="form-control" id="filtroFechaHasta">
              </div>
            </div>
            <div class="col-lg-1">
              <div class="form-group">
                <label for="filtroNumDesde">N° Desde:</label>
                <input type="number" class="form-control" id="filtroNumDesde" placeholder="—">
              </div>
            </div>
            <div class="col-lg-1">
              <div class="form-group">
                <label for="filtroNumHasta">N° Hasta:</label>
                <input type="number" class="form-control" id="filtroNumHasta" placeholder="—">
              </div>
            </div>
            <div class="col-lg-1 d-flex" style="align-items:flex-end;">
              <div class="form-group w-100">
                <button type="button" id="btnFiltrar" class="btn btn-primary btn-block" title="Filtrar"><i class="fa fa-search"></i></button>
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-lg-12 text-right">
              <button type="button" id="btnLimpiarFiltro" class="btn btn-default btn-sm">
                <i class="fa fa-eraser"></i> Limpiar filtro
              </button>
            </div>
          </div>
          <hr>

          <table id="doc_data" class="table table-bordered table-striped table-vcenter js-dataTable-full">
            <thead>
              <tr>
                <th>Fecha</th>
                <th>Tipo</th>
                <th>N° Documento</th>
                <th>Nit/Cedula</th>
                <th>Nombre</th>
                <th>Direccion</th>
                <th>Usuario</th>
                <th>Exportado</th>
                <th>Ver</th>
              </tr>
            </thead>
            <tbody>
            </tbody>
          </table>
        </div>
			</form>

		</div>
	</div>
	<!-- Contenido -->
	
	<?php require_once("../MainJs/js.php");?>
	
	<script type="text/javascript" src="consultar.js?v=7"></script>

  <style>
    /* Estilo para la columna exportado */
    .label {
        padding: 0.3em 0.6em;
        font-size: 85%;
        font-weight: 700;
        border-radius: 0.25em;
        display: inline-block;
        min-width: 40px;
    }
    .label-success {
        background-color: #5cb85c;
        color: white;
    }
    .label-danger {
        background-color: #d9534f;
        color: white;
    }
    .label-default {
        background-color: #6c757d;
        color: white;
    }

    /* Mejorar la apariencia de los botones de acción */
    .btn-rounded {
        border-radius: 50px;
        padding: 0.25rem 0.75rem;
    }
    
    /* Hacer que la tabla se vea mejor */
    .table-responsive {
        min-height: 400px;
    }
    
    /* Estilos para los botones DataTables */
    .dt-buttons {
        margin-bottom: 15px;
    }
</style

</body>
</html>
<?php
}else{
	header("Location:../../index.php");
}
?>