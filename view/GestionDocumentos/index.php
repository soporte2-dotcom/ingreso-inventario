<?php
require_once("../../config/conexionserver.php");
if(isset($_SESSION["Id_Usuario"])){
date_default_timezone_set("America/Bogota");
require_once("../../models/mdlPermisos.php");
$_permisos = new Permisos();
$permiteGestionDocumentos = $_permisos->tiene_permiso_especial($_SESSION["Id_Usuario"], 'gestion_documentos');
if (!$permiteGestionDocumentos) {
    header("Location: ../Home/index.php");
    exit();
}
?>
<!DOCTYPE html>
<html>
<?php require_once("../MainHead/head.php"); ?>
<?php require_once("../MainJs/js.php"); ?>

<title>Cervalle:: Gestión de Documentos</title>

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
							<h3>Gestión de Documentos</h3>
							<ol class="breadcrumb breadcrumb-simple">
								<li><a href="#">Home</a></li>
								<li class="active">Gestión de Documentos</li>
							</ol>
						</div>
					</div>
				</div>
			</header>

			<div class="box-typical box-typical-padding">

				<div class="alert alert-warning">
					<i class="fa fa-exclamation-triangle"></i>
					Desmarcar o anular un documento afecta directamente la información contable e inventario. Verifique bien el documento antes de confirmar.
				</div>

				<div class="row">

					<div class="col-lg-4">
						<fieldset class="form-group">
							<label class="form-label semibold">Tipo de Documento</label>
							<select id="tipoDoc" class="form-control"></select>
						</fieldset>
					</div>

					<div class="col-lg-3">
						<fieldset class="form-group">
							<label class="form-label semibold">Número de Documento</label>
							<input type="text" id="numeroDoc" class="form-control" placeholder="Ej: 12345">
						</fieldset>
					</div>

					<div class="col-lg-2">
						<label class="form-label semibold">&nbsp;</label>
						<button type="button" id="btnBuscarDoc" class="btn btn-primary btn-block">Buscar</button>
					</div>

				</div>

				<div id="resultadoDoc" style="display:none; margin-top:20px">

					<table class="table table-bordered">
						<tbody>
							<tr><th style="width:220px">Tipo de Documento</th><td id="rd_tipo"></td></tr>
							<tr><th>Número</th><td id="rd_numero"></td></tr>
							<tr><th>Fecha</th><td id="rd_fecha"></td></tr>
							<tr><th>Usuario que lo creó</th><td id="rd_usuario"></td></tr>
							<tr><th>Nit/Cédula</th><td id="rd_nit"></td></tr>
							<tr><th>Nombre</th><td id="rd_nombre"></td></tr>
							<tr><th>Notas</th><td id="rd_notas"></td></tr>
							<tr><th>Exportado</th><td id="rd_exportado"></td></tr>
							<tr><th>Anulado</th><td id="rd_anulado"></td></tr>
						</tbody>
					</table>

					<div class="row">
						<div class="col-sm-6 col-md-4 col-lg-3 d-flex mx-auto">
							<button type="button" id="btnDesmarcar" class="d-flex w-15 btn btn-rounded btn-inline btn-warning">Desmarcar (Exportado &rarr; N)</button>
						</div>
						<div class="col-sm-6 col-md-4 col-lg-3 d-flex mx-auto">
							<button type="button" id="btnAnular" class="d-flex w-15 btn btn-rounded btn-inline btn-danger">Anular Documento</button>
						</div>
					</div>

				</div>

			</div>
		</div>
	</div>
	<!-- Contenido -->

	<!-- Modal Motivo de Anulación -->
	<div class="modal fade" id="modalMotivoAnulacion" tabindex="-1" role="dialog" aria-labelledby="modalMotivoAnulacionTitle" aria-hidden="true" data-backdrop="static" data-keyboard="false">
		<div class="modal-dialog" role="document">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title" id="modalMotivoAnulacionTitle"><i class="fa fa-ban"></i> Motivo de Anulación</h5>
					<button type="button" class="close" data-dismiss="modal" aria-label="Close">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>
				<div class="modal-body">
					<p id="motivoAnulacionDocInfo" class="text-muted"></p>
					<div class="form-group">
						<label class="font-weight-bold">Motivo <span class="text-danger">*</span></label>
						<textarea id="motivoAnulacion" class="form-control" rows="3" placeholder="Explique el motivo de la anulación..."></textarea>
					</div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
					<button type="button" id="btnConfirmarAnulacion" class="btn btn-danger">Anular Documento</button>
				</div>
			</div>
		</div>
	</div>
	<!-- Fin modal Motivo de Anulación -->

	<script type="text/javascript" src="gestion.js?v=<?php echo time(); ?>"></script>

</body>

</html>
<?php
}else{
	header("Location:../../index.php");
}
?>
