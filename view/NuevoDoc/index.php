<?php
require_once("../../config/conexionserver.php");
if(isset($_SESSION["Id_Usuario"])){
date_default_timezone_set("America/Bogota");
$DateAndTime = date('d-m-Y h:i:s a', time());
?>
<!DOCTYPE html>
<html>
<?php require_once("../MainHead/head.php"); ?>
<?php require_once("../MainJs/js.php"); ?>

<style>
.ui-autocomplete {
  z-index: 9999;
}

</style>

<script type="text/javascript">

	$(function() {
		$("#nit").autocomplete({
			source: "../../controller/terceros.php?op=terceroxnit",
			minLength: 2,
			select: function(event, ui) {
				$('#nombre').val(ui.item.nombre);
				//$('#telefono').val(ui.item.telefono);
				$("#nit").focus();
			}
		});
	});

	$(function() {
		$("#idproducto").autocomplete({
			source: "../../controller/productos.php?op=productoxid",
			minLength: 2,
			select: function(event, ui) {
				$('#producto').val(ui.item.Producto);
				$("#idproducto").focus();
			}
		});
	});

</script>

<title>Cervalle:: Inventario</title>
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
							<h3>Inventario</h3>
							<ol class="breadcrumb breadcrumb-simple">
								<li><a href="#">Home</a></li>
								<li class="active">Inventario</li>
							</ol>
						</div>
					</div>
				</div>
			</header>

			<div class="box-typical box-typical-padding">
				<div class="row">
					<div class="col-lg-8">
						<h5 class="m-t-lg with-border">Ingresar Información</h5>
					</div>
					
				</div>

				<form method="post" id="doc_form">

				<div class="row">

					<div class="col-lg-4">
						<fieldset class="form-group">
							<label class="form-label semibold" id="txt_idTipo">Tipo de Documento</label>
							<select id="idTipo" name="idTipo" class="form-control" required></select>
						</fieldset>
					</div>

					<div class="col-lg-2">
						<fieldset class="form-group">
							<label class="form-label semibold" id="txt_consecutivo">Consecutivo</label>
							<input type="text" name="consecutivo" id="consecutivo" class="form-control" readonly/>
						</fieldset>
					</div>

					<div class="col-lg-3">
						<fieldset class="form-group">
							<label class="form-label semibold" id="txt_fecha">Fecha</label>
							<input type="text" id="fecha" class="form-control" value="<?php echo $DateAndTime ?>" readonly />
						</fieldset>
					</div>

				</div>

				<div class="row">

					<div class="col-lg-2">
						<fieldset class="form-group">
							<label class="form-label semibold" id="txt_nit">Nit/Cedula</label>
							<input type="tel" name="nit" id="nit" class="form-control input-medium ui-autocomplete-input"  autocomplete="off" required/>
						</fieldset>
					</div>

					<div class="col-lg-3">
						<fieldset class="form-group">
							<label class="form-label semibold" id="txt_nombre">Nombre</label>
							<input type="text" name="nombre" id="nombre" class="form-control" required readonly/>
						</fieldset>
					</div>

					<div class="col-lg-3">
						<fieldset class="form-group">
							<label class="form-label semibold" id="txt_direccion">Direccion</label>
							<select id="direccion" name="direccion" class="form-control" required></select>
						</fieldset>
					</div>

					<div class="col-lg-2">
						<fieldset class="form-group">
							<label class="form-label semibold" id="txt_telefono">Telefono</label>
							<input type="text" name="telefono" id="telefono" class="form-control" required readonly/>
						</fieldset>
					</div>

				</div>

				<div class="row">
					<div class="col-sm-6 col-md-3 col-lg-2 d-flex mx-auto">
						<button type="button" id="btncrear" class="d-flex w-15 btn btn-rounded btn-inline btn-success">Crear</button>
					</div>
				</div>

				<div class="row">

					
					<input type="hidden" name="tipo" id="tipo" class="form-control" />				
					

					<div class="col-lg-4">
						<fieldset class="form-group">
							<label class="form-label semibold" style="display: none" id="txt_tipodoc">Tipo de Documento</label>
							<input type="text" style="display: none" name="tipodoc" id="tipodoc" class="form-control" readonly/>
						</fieldset>
					</div>

					<div class="col-lg-2">
						<fieldset class="form-group">
							<label class="form-label semibold" style="display: none" id="txt_numdoc">Consecutivo</label>
							<input type="text" style="display: none" name="numdoc" id="numdoc" class="form-control" readonly/>
						</fieldset>
					</div>

					<div class="col-lg-3">
						<fieldset class="form-group">
							<label class="form-label semibold" style="display: none" id="txt_fecha1">Fecha</label>
							<input type="text" style="display: none" id="fecha1" class="form-control" value="<?php echo $DateAndTime ?>" readonly />
						</fieldset>
					</div>
											
				</div>

				<div class="row">

					<div class="col-lg-2">
						<fieldset class="form-group">
							<label class="form-label semibold" style="display: none" id="txt_nit1">Nit/Cedula</label>
							<input type="text" style="display: none" name="nit1" id="nit1" class="form-control" readonly/>
						</fieldset>
					</div>

					<div class="col-lg-3">
						<fieldset class="form-group">
							<label class="form-label semibold" style="display: none" id="txt_nombre1">Nombre</label>
							<input type="text" style="display: none" name="nombre1" id="nombre1" class="form-control"  readonly/>
						</fieldset>
					</div>

					<div class="col-lg-3">
						<fieldset class="form-group">
							<label class="form-label semibold" style="display: none" id="txt_direccion1">Direccion</label>
							<input type="text" style="display: none" id="direccion1" name="direccion1" class="form-control" readonly>
						</fieldset>
					</div>

					<div class="col-lg-2">
						<fieldset class="form-group">
							<label class="form-label semibold" style="display: none" id="txt_telefono1">Telefono</label>
							<input type="text" style="display: none" name="telefono1" id="telefono1" class="form-control" readonly/>
						</fieldset>
					</div>

				</div>

				<div class="row">
					<div class="col-sm-6 col-md-3 col-lg-2 d-flex mx-auto">
						<button type="submit" name="action"  class="modalagregar d-flex w-15 btn btn-rounded btn-inline btn-.bg-primary" data-toggle="modal" data-target="#modalagregar" style="display: none" id="agregar">Agregar Registros</button>
					</div>
				</div>

				<div class="container-fluid">
					<div class="row py-5">
						<div class="col-lg-12 col-md-12 col-sm-12">
							<table id="tb-doc" class="table table-bordered table-striped table-vcenter js-dataTable-full">
								<thead>
									<tr>
										<th class="text-center">Producto</th>
										<th class="text-center">Nombre</th>
										<th class="text-center">U medida</th>
										<th class="text-center">Cantidad</th>										
										<th class="text-center">Eliminar</th>
									</tr>
								</thead>								
							</table>
						</div>
					</div>
				</div>

				<br/>
				
				<div class="form-group py-5">
					<label class="font-weight-bold">Notas: </label>
					<textarea class="form-control" rows="4" name="notas"></textarea>
        		</div>

				<div class="row">								 
					<div class="col-sm-6 col-md-3 col-lg-2 d-flex mx-auto">
						<button type="button" id="btnguardar" class="d-flex w-15 btn btn-rounded btn-inline btn-success">Guardar</button>
					</div>
				</div>	

							<!-- Modal Agregar-->
							<div class="modal fade" id="modalagregar" tabindex="-1" role="dialog" aria-labelledby="modalagregar" aria-hidden="true" data-backdrop="static" data-keyboard="false">
								<div class="modal-dialog" role="document">
									<div class="modal-content">
										<div class="modal-header">
											<h5 class="modal-title" id="modalagregar">Ingresar Producto</h5>
											<button type="button" class="close" data-dismiss="modal" aria-label="Close">
												<span aria-hidden="true">&times;</span>
											</button>
										</div>
										<div class="modal-body">
											<div class="row">
												
												<input class="form-control" type="hidden" name="seq" id="seq">
												
												<div class="col-lg-12 col-md-12 col-sm-12">
													<label>Codigo Producto</label>
													<input class="form-control" type="number" name="idproducto" id="idproducto">
												</div>
												
												<div class="col-lg-12 col-md-12 col-sm-12">
													<label>Producto</label>
													<input class="form-control" type="text" name="producto" id="producto" readonly>
												</div>
												<div class="col-lg-12 col-md-12 col-sm-12">
												<label for="cerdoscanal">Cantidad</label>
													<input class="form-control" type="number" name="cantidad" id="cantidad">
												</div>
											</div>
										</div>
										<div class="modal-footer">
											<button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
											<button type="button" id="btnagregar" class="btn btn-primary" >Registrar</button>
										</div>
									</div>
								</div>
							</div>
							<!-- Fin modal Agregar -->


							
						</form>

			</div>
		</div>
	</div>
	<!-- Contenido -->
	<script type="text/javascript" src="nuevodoc.js?v=2"></script>

</body>

</html>
<?php
} else {
	header("Location:../../index.php");
  }
?>