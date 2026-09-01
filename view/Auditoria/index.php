<?php
require_once("../../config/conexionserver.php");
if(isset($_SESSION["Id_Usuario"])){
date_default_timezone_set("America/Bogota");
require_once("../../models/mdlPermisos.php");
$_permisos = new Permisos();
$permiteAuditoria = $_permisos->tiene_permiso_especial($_SESSION["Id_Usuario"], 'auditoria_documentos');
if (!$permiteAuditoria) {
    header("Location: ../Home/index.php");
    exit();
}
?>
<!DOCTYPE html>
<html>
<?php require_once("../MainHead/head.php"); ?>
<?php require_once("../MainJs/js.php"); ?>

<title>Cervalle:: Auditoría de Documentos</title>

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
							<h3>Auditoría de Documentos</h3>
							<ol class="breadcrumb breadcrumb-simple">
								<li><a href="#">Home</a></li>
								<li class="active">Auditoría de Documentos</li>
							</ol>
						</div>
					</div>
				</div>
			</header>

			<div class="box-typical box-typical-padding">

				<div class="alert alert-info">
					<i class="fa fa-info-circle"></i>
					Registro de todo lo que crea, modifica o borra líneas de documentos.
					Los intentos <b>bloqueados</b> son operaciones que el sistema rechazó por tratarse
					de un documento ya guardado o anulado: ahí se ve quién intentó modificarlo.
					<br>
					<small class="text-muted">
						No aparece aquí lo que se haga por fuera de esta aplicación (Tecnocarnes o SQL directo).
					</small>
				</div>

				<div class="row">
					<div class="col-lg-2">
						<fieldset class="form-group">
							<label class="form-label semibold">Desde</label>
							<input type="date" id="fechaDesde" class="form-control">
						</fieldset>
					</div>
					<div class="col-lg-2">
						<fieldset class="form-group">
							<label class="form-label semibold">Hasta</label>
							<input type="date" id="fechaHasta" class="form-control">
						</fieldset>
					</div>
					<div class="col-lg-2">
						<fieldset class="form-group">
							<label class="form-label semibold">Tipo Doc.</label>
							<input type="text" id="tipo" class="form-control" placeholder="Ej: 226">
						</fieldset>
					</div>
					<div class="col-lg-2">
						<fieldset class="form-group">
							<label class="form-label semibold">Número</label>
							<input type="text" id="numero" class="form-control" placeholder="Ej: 1798">
						</fieldset>
					</div>
					<div class="col-lg-2">
						<fieldset class="form-group">
							<label class="form-label semibold">Usuario</label>
							<input type="text" id="usuario" class="form-control" placeholder="Ej: KARENR">
						</fieldset>
					</div>
					<div class="col-lg-2">
						<fieldset class="form-group">
							<label class="form-label semibold">Operación</label>
							<select id="operacion" class="form-control"><option value="">-- Todas --</option></select>
						</fieldset>
					</div>
				</div>

				<div class="row">
					<div class="col-lg-2">
						<fieldset class="form-group">
							<label class="form-label semibold">Resultado</label>
							<select id="resultado" class="form-control">
								<option value="">-- Todos --</option>
								<option value="ok">Ejecutada</option>
								<option value="bloqueado">Bloqueada</option>
								<option value="error">Con error</option>
							</select>
						</fieldset>
					</div>
					<div class="col-lg-3">
						<label class="form-label semibold">&nbsp;</label>
						<div class="checkbox">
							<label><input type="checkbox" id="soloDestructivas"> Solo operaciones destructivas</label>
						</div>
					</div>
					<div class="col-lg-3">
						<label class="form-label semibold">&nbsp;</label>
						<div class="checkbox">
							<label><input type="checkbox" id="soloConPerdida"> <b>Solo las que perdieron líneas</b></label>
						</div>
					</div>
					<div class="col-lg-2">
						<label class="form-label semibold">&nbsp;</label>
						<button type="button" id="btnConsultar" class="btn btn-primary btn-block">Consultar</button>
					</div>
					<div class="col-lg-2">
						<label class="form-label semibold">&nbsp;</label>
						<button type="button" id="btnLimpiar" class="btn btn-secondary btn-block">Limpiar</button>
					</div>
				</div>

				<div id="avisoTope" class="alert alert-warning" style="display:none; margin-top:10px"></div>

				<div class="table-responsive" style="margin-top:15px">
					<table id="tb-auditoria" class="table table-bordered table-striped table-sm" style="width:100%">
						<thead>
							<tr>
								<th>Fecha</th>
								<th>Usuario</th>
								<th>IP</th>
								<th>Módulo</th>
								<th>Operación</th>
								<th>Tipo / Nº</th>
								<th>Resultado</th>
								<th>Líneas</th>
								<th>Filas</th>
								<th>Detalle de la operación</th>
								<th>Ver</th>
							</tr>
						</thead>
						<tbody></tbody>
					</table>
				</div>

			</div>
		</div>
	</div>
	<!-- Contenido -->

	<!-- Modal: detalle del documento ANTES de la operación -->
	<div class="modal fade" id="modalDetalleAuditoria" tabindex="-1" role="dialog" aria-hidden="true">
		<div class="modal-dialog modal-lg" role="document">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title"><i class="fa fa-history"></i> Detalle antes de la operación</h5>
					<button type="button" class="close" data-dismiss="modal" aria-label="Close">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>
				<div class="modal-body">
					<p id="da_resumen" class="text-muted"></p>
					<div id="da_truncado" class="alert alert-warning" style="display:none"></div>
					<div class="table-responsive" style="max-height:420px; overflow:auto">
						<table class="table table-bordered table-sm">
							<thead>
								<tr>
									<th>Seq</th><th>Producto</th><th>Lote</th><th>Cantidad</th>
									<th>Vlr. Unitario</th><th>Costo</th><th>% IVA</th><th>Nota</th>
								</tr>
							</thead>
							<tbody id="da_lineas"></tbody>
						</table>
					</div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
				</div>
			</div>
		</div>
	</div>

	<script type="text/javascript" src="auditoria.js?v=<?php echo time(); ?>"></script>

</body>

</html>
<?php
}else{
	header("Location:../../index.php");
}
?>
