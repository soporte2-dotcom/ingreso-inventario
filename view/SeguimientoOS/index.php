<?php
require_once("../../config/conexionserver.php");
if(isset($_SESSION["Id_Usuario"])){
date_default_timezone_set("America/Bogota");
require_once("../../models/mdlPermisos.php");
$_permisos = new Permisos();
if (!$_permisos->tiene_permiso_especial($_SESSION["Id_Usuario"], 'seguimiento_os')) {
    header("Location: ../Home/index.php");
    exit();
}
?>
<!DOCTYPE html>
<html>
<?php require_once("../MainHead/head.php"); ?>
<?php require_once("../MainJs/js.php"); ?>

<title>Cervalle:: Seguimiento de Órdenes de Salida</title>

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
							<h3>Seguimiento de Órdenes de Salida</h3>
							<ol class="breadcrumb breadcrumb-simple">
								<li><a href="#">Home</a></li>
								<li class="active">Seguimiento de OS</li>
							</ol>
						</div>
					</div>
				</div>
			</header>

			<div class="box-typical box-typical-padding">

				<div class="row">
					<div class="col-lg-3">
						<fieldset class="form-group">
							<label class="form-label semibold">Número de Orden de Salida</label>
							<input type="text" id="numeroOS" class="form-control" placeholder="Ej: 154287" autofocus>
						</fieldset>
					</div>
					<div class="col-lg-2">
						<label class="form-label semibold">&nbsp;</label>
						<button type="button" id="btnBuscarOS" class="btn btn-primary btn-block">
							<i class="fa fa-search"></i> Consultar
						</button>
					</div>
				</div>

				<div id="resultadoOS" style="display:none">

					<div id="avisoOsAnulada" class="alert alert-danger" style="display:none">
						<i class="fa fa-ban"></i> Esta Orden de Salida está <b>ANULADA</b>.
					</div>

					<!-- Aviso clave: el despacho calcula los pendientes filtrando por la bodega de
					     la OS, así que los documentos hechos desde otra bodega NO le cuentan y la
					     orden se puede volver a despachar aunque ya esté servida. -->
					<div id="avisoBodega" class="alert alert-danger" style="display:none">
						<i class="fa fa-exclamation-triangle"></i>
						<b>Atención:</b> <span id="avisoBodegaTexto"></span>
						El cálculo de pendientes que usa el despacho <b>no los tiene en cuenta</b>,
						porque solo mira los documentos de la bodega de la orden
						(<b id="avisoBodegaOs"></b>). Por eso esta orden puede volver a despacharse
						aunque aquí se vea servida. Los movimientos afectados están marcados abajo.
					</div>

					<!-- Resumen de la OS -->
					<div class="row" style="margin-bottom:15px">
						<div class="col-lg-8">
							<table class="table table-bordered table-sm">
								<tbody>
									<tr><th style="width:150px">Orden de Salida</th><td id="os_numero"></td>
										<th style="width:120px">Fecha</th><td id="os_fecha"></td></tr>
									<tr><th>Cliente</th><td id="os_cliente"></td>
										<th>Bodega</th><td id="os_bodega"></td></tr>
									<tr><th>Creada por</th><td id="os_usuario"></td>
										<th>Notas</th><td id="os_notas"></td></tr>
								</tbody>
							</table>
						</div>
						<div class="col-lg-4">
							<table class="table table-bordered table-sm">
								<tbody>
									<tr><th style="width:170px">Ítems de la orden</th><td id="t_lineas" class="text-right"></td></tr>
									<tr><th>Ítems con pendiente</th><td id="t_pendientes" class="text-right"></td></tr>
									<tr><th>Documentos generados</th><td id="t_documentos" class="text-right"></td></tr>
									<tr><th>Cantidad ordenada</th><td id="t_ordenado" class="text-right"></td></tr>
									<tr><th>Cantidad descontada</th><td id="t_despachado" class="text-right"></td></tr>
									<tr class="font-weight-bold"><th>Pendiente</th><td id="t_pendiente" class="text-right"></td></tr>
								</tbody>
							</table>
						</div>
					</div>

					<!-- Segundo desajuste posible: documentos que apuntan a la OS pero cuyas líneas
					     no enlazan por (producto + número de línea). -->
					<div id="avisoSinEnlace" class="alert alert-danger" style="display:none">
						<i class="fa fa-unlink"></i>
						<b>Atención:</b> hay <b id="avisoSinEnlaceNum"></b> documento(s) que apuntan a esta
						orden pero cuyas líneas <b>no enlazan</b> con ningún ítem de la orden: el producto
						existe, pero el número de línea no coincide. Ni este seguimiento ni el cálculo de
						pendientes del despacho pueden atribuirles cantidad.
					</div>

					<div class="alert alert-info">
						<i class="fa fa-info-circle"></i>
						Haga clic en el <b>+</b> de cada ítem para ver en qué documentos se descontó.
						Las <b>devoluciones</b> se muestran restando, y los documentos <b>sin guardar</b> o
						<b>anulados</b> quedan señalados porque afectan el pendiente.
					</div>

					<div class="table-responsive">
						<table id="tb-seguimiento" class="table table-bordered table-striped table-sm" style="width:100%">
							<thead>
								<tr>
									<th style="width:30px"></th>
									<th>Ítem</th>
									<th>Producto</th>
									<th>Nombre</th>
									<th>U. medida</th>
									<th class="text-right">Ordenado</th>
									<th class="text-right">Descontado</th>
									<th class="text-right">Pendiente</th>
									<th class="text-center">Docs.</th>
								</tr>
							</thead>
							<tbody></tbody>
						</table>
					</div>

				</div>

				<div id="sinResultado" class="alert alert-warning" style="display:none"></div>

			</div>
		</div>
	</div>
	<!-- Contenido -->

	<style>
		/* Botón para desplegar los documentos de cada ítem */
		td.detalle-control {
			cursor: pointer;
			text-align: center;
			color: #0275d8;
			font-weight: bold;
		}
		tr.item-pendiente td { background-color: #fff3cd !important; }
		tr.item-completo  td { background-color: #eaf7ea !important; }
		table.tabla-movimientos {
			width: 100%;
			margin: 0;
			background-color: #fbfbfb;
		}
		table.tabla-movimientos th {
			background-color: #eef1f4;
			font-size: 90%;
		}
		table.tabla-movimientos td, table.tabla-movimientos th {
			padding: 5px 8px;
			border: 1px solid #dee2e6;
		}
	</style>

	<script type="text/javascript" src="seguimiento.js?v=<?php echo time(); ?>"></script>

</body>

</html>
<?php
}else{
	header("Location:../../index.php");
}
?>
