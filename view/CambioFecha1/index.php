<?php
require_once("../../config/conexionserver.php");
if(isset($_SESSION["Id_Usuario"])){
date_default_timezone_set("America/Bogota");
$DateAndTime = date('d-m-Y h:i:s', time());
?>
<!DOCTYPE html>
<html>
<?php require_once("../MainHead/head.php"); ?>
<?php require_once("../MainJs/js.php"); ?>

<title>Cervalle:: Cambio Fecha</title>
<script src="https://cdn.datatables.net/1.10.25/js/jquery.dataTables.min.js"></script>
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
							<h3>Cambio Fecha</h3>
							<ol class="breadcrumb breadcrumb-simple">
								<li><a href="#">Home</a></li>
								<li class="active">Cambio Fecha</li>
							</ol>
						</div>
					</div>
				</div>
			</header>

			<div class="box-typical box-typical-padding">

				<form method="post" id="doc_form">

				<div class="row">
					<div class="col-sm-6 col-md-3 col-lg-2 d-flex mx-auto">
						<button type="button" id="btnlot" name="action"  class="lot d-flex w-15 btn btn-rounded btn-inline btn-.bg-primary" data-toggle="modal" data-target="#lot"> Lote</button>
					</div>
				</div>

				<div class="container-fluid">
				
				<div class="row">
					<div class="col-lg-3">
						<div class="form-group">
							<label for="fechaDesde">Fecha Desde:</label>
							<input type="date" class="form-control date-input" id="fechaDesde" name="fechaDesde">
						</div>
					</div>
					<div class="col-lg-3">
						<div class="form-group">
							<label for="fechaHasta">Fecha Hasta:</label>
							<input type="date" class="form-control date-input" id="fechaHasta" name="fechaDesde">
						</div>
					</div>
					<div class="col-lg-3">
						<br/>
					<button type="button" class="btn btn-primary btn-block" id="btnFiltrar">Filtrar</button>
					<button type="button" class="btn btn-default btn-block" id="btnLimpiarFiltro">Limpiar Filtro</button>
					</div>
				</div>
				<br/>
					
					<div class="row">
						<div class="col-lg-12 col-md-12 col-sm-12">
							<table id="tb-doc" class="table table-bordered table-striped table-vcenter js-dataTable-full">
								<thead>
									<tr>
										<th class="text-center">Documento</th>
										<th class="text-center">Consecutivo</th>
										<th class="text-center">Despacho</th>
										<th class="text-center">Nota General</th>
										<th class="text-center">Usuario</th>
										<th class="text-center">Fecha</th>
										<th><a href="#" id="marcarTodo">Marcar</a> | <a href="#" id="desmarcarTodo">Desmarcar</a></th>
									</tr>
								</thead>								
							</table>
						</div>
					</div>
				</div>

							<!-- Modal Lote-->
							<div class="modal fade" id="lot" tabindex="-1" role="dialog" aria-labelledby="lot" aria-hidden="true" data-backdrop="static" data-keyboard="false">
								<div class="modal-dialog" role="document">
									<div class="modal-content">
										<div class="modal-header">
											<button type="button" class="close" data-dismiss="modal" aria-label="Close">
												<span aria-hidden="true">&times;</span>
											</button>
										</div>
										
										<div class="modal-body">
											<div class="row">
												
											<div class="col-lg-12 col-md-12 col-sm-12">
												<label>Fecha Factura</label>
												<input class="form-control" type="date" name="fecha_factura" id="fecha_factura">
											</div>
												
											</div>
										</div>
										<div class="modal-footer">
											<button type="button" class="btn btn-primary" id="btnlote">Guardar</button>
										</div>
									</div>
									
								</div>
							</div>
							<!-- Fin modal lote -->
						</form>

			</div>
		</div>
	</div>
	<!-- Contenido -->
	
	<script>
		$(document).ready(function() {
			// ...
			
			// Capturar el evento clic del botón "Filtrar"
			$("#btnFiltrar").on("click", function() {
				// Obtener las fechas "Desde" y "Hasta" ingresadas por el usuario
				var fechaDesde = $("#fechaDesde").val();
				var fechaHasta = $("#fechaHasta").val();

				// Enviar las fechas al controlador PHP para filtrar los datos
				tabla.DataTable().ajax.url("../../controller/documento.php?op=listar_documentos_fecha&fechaDesde=" + fechaDesde + "&fechaHasta=" + fechaHasta).load();
			});

			// ...
		});
	</script>

<script type="text/javascript" src="nuevodoc.js?v=9"></script>

	<script>
        document.addEventListener("DOMContentLoaded", function() {
            document.getElementById('marcarTodo').addEventListener('click', function(e) {
                e.preventDefault();
                //seleccionarTodo();
                checkAll();
            });
            document.getElementById('desmarcarTodo').addEventListener('click', function(e) {
                e.preventDefault();
                //desmarcarTodo();
                uncheckAll();
            });
        });
    
            
        function checkAll() {
            document.querySelectorAll('#doc_form input[type=checkbox]').forEach(function(checkElement) {
                checkElement.checked = true;
            });
        }
    
        function uncheckAll() {
            document.querySelectorAll('#doc_form input[type=checkbox]').forEach(function(checkElement) {
                checkElement.checked = false;
            });
        }
    </script>

</body>

</html>
<?php
}else{
	header("Location:../../index.php");
}
?>