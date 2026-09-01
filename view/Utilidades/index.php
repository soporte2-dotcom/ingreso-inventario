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

<title>Cervalle:: Utilidades</title>

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
							<h3>Utilidades</h3>
							<ol class="breadcrumb breadcrumb-simple">
								<li><a href="#">Home</a></li>
								<li class="active">Utilidades</li>
							</ol>
						</div>
					</div>
				</div>
			</header>

			<div class="box-typical box-typical-padding">

				<form method="post" id="doc_form">

				<div class="row">

					<div class="col-lg-4">
						<fieldset class="form-group">
							<label class="form-label semibold" id="txt_idTipo">Tipo de Documento</label>
							<select id="idTipo" name="idTipo" class="form-control" required></select>
						</fieldset>
					</div>

					<div class="col-lg-3">
						<label class="form-label semibold">Fecha</label>
						<input type="date" class="form-control" id="fecha1" name="fecha1" required>
					</div>

					<div class="col-lg-2">
						<br/>
						<button type="button" id="btnConsultar" class="btn btn-success">Consultar</button>
					</div>

				</div>

				<div class="container-fluid">
					<div class="row">
						<div class="col-lg-12 col-md-12 col-sm-12">
							<table id="tb-doc" class="table table-bordered table-striped table-vcenter js-dataTable-full">
								<thead>
									<tr>
										<th class="text-center">Tipo</th>
										<th class="text-center">Consecutivo</th>
										<th class="text-center">Proveedor</th>
										<th class="text-center">Fecha</th>
										<th class="text-center">Ingresar Numero</th>
										<th> </th>
									</tr>
								</thead>

								<tbody>
									<tr><td colspan="6">Realice una consulta para ver los resultados.</td></tr>
								</tbody>

							</table>
						</div>
					</div>
				</div>
				<br/>

				<div class="row">
					<div class="col-sm-6 col-md-3 col-lg-2 d-flex mx-auto">
						<button type="button" id="btnupdate" class="d-flex w-15 btn btn-rounded btn-inline btn-success">Actualizar</button>
					</div>
				</div>

				</form>

			</div>
		</div>
	</div>
	<!-- Contenido -->
	<script type="text/javascript" src="nuevodoc.js?v=4"></script>

</body>

</html>
<?php
}else{
	header("Location:../../index.php");
}
?>