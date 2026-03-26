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
						<input type="date" class="form-control" name="fecha1" required>
					</div>

					<div class="col-lg-2">
						<br/>
						<button type="submit" class="btn btn-success" name="generar_reporte">Consultar</button>
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
									<?php
									require_once("../../config/conexion.php");
									if (isset($_POST['idTipo']) || isset($_POST['fecha1'])) {
										$sql = "SELECT d.tipo, t.TipoDoctos, d.Numero_documento, d.Numero_Docto_Base, d.notas, d.usuario, d.Fecha_Hora_Factura , d.nit_Cedula, c.nombre
												FROM Documentos d, TblTipoDoctos t, TblTerceros c
												WHERE d.tipo = t.idTipoDoctos AND c.nit_cedula=d.nit_Cedula";

										if (isset($_POST["idTipo"]) && !empty($_POST["idTipo"])) {
											$sql .= " AND d.tipo = '" . $_POST["idTipo"] . "'";
										}

										if (isset($_POST['fecha1']) && !empty($_POST['fecha1'])) {
											$fecha1 = date('Y-m-d', strtotime($_POST['fecha1']));
											$sql .= " AND CONVERT(date, d.Fecha_Hora_Factura) = '$fecha1' ";
										}

										$sql .= " ORDER BY d.Numero_documento DESC";
										$params = array();
										$options =  array( "Scrollable" => SQLSRV_CURSOR_KEYSET );
										$stmt = sqlsrv_query($con, $sql, $params, $options);

										$row_count = sqlsrv_num_rows($stmt);
										if ($row_count == 0) {
											echo '<tr><td colspan="8">No se encontraron resultados.</td></tr>';
										} else {
											while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
												echo '<tr>
														<td>' . $row['TipoDoctos'] . '</td>
														<td>' . $row['Numero_documento'] . '</td>
														<td>' . $row['nombre'] . '</td>
														<td>' . date_format($row["Fecha_Hora_Factura"], "Y-m-d"). '</td>
														<td><input type="text" id="' . $row["tipo"] . '_' . $row["Numero_documento"] . '" value="'. $row["Numero_Docto_Base"] . '" class="form-control"></td>
													</tr>';

											}
										}
										sqlsrv_close($con);
									} else {
										// Mostrar la tabla vacía si no se ha realizado ningún filtro
										echo '<tr><td colspan="8">Realice una consulta para ver los resultados.</td></tr>';
									}
									?>
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