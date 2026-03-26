<?php
require_once("../../config/conexionserver.php");
if(isset($_SESSION["Id_Usuario"])){
?>
  <!DOCTYPE html>
  <html>
  <?php require_once("../MainHead/head.php"); ?>
  <title>Cervalle::Cambio de Fecha</title>

  <style>
	/* Estilos del checkbox */
	.custom-checkbox {
	/* Ajusta el tamaño del checkbox aquí */
	width: 20px;
	height: 20px;
	/* Añade más estilos si lo deseas, como colores, bordes, etc. */
	}

	/* Estilos del label asociado al checkbox */
	.custom-checkbox + label {
	/* Ajusta el tamaño del label aquí */
	font-size: 18px;
	/* Añade más estilos si lo deseas, como colores, márgenes, etc. */
	}
 </style>
 
  </head>

  <body class="with-side-menu">

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
					<h3>Cambio de Fecha</h3>
					<div id="lblestado"></div>
					<ol class="breadcrumb breadcrumb-simple">
					<li><a href="#">Home</a></li>
					<li class="active">Cambio de Fecha</li>
					</ol>
				</div>
				</div>
			</div>
			</header>
        
        	<div class="box-typical box-typical-padding">
			<form method="post" id="doc_form">

							<!-- Modal Fecha-->
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
											<button type="button" class="btn btn-primary" id="btnfecha">Guardar</button>
										</div>
									</div>
									
								</div>
							</div>
							<!-- Fin modal Fecha -->

				<div class="row">

					<div class="col-lg-4">
						<fieldset class="form-group">
							<label class="form-label semibold" id="txt_idTipo">Tipo de Documento</label>
							<select id="idTipo" name="idTipo" class="form-control"></select>
						</fieldset>
					</div>
														
					<div class="col-lg-3">
						<label class="form-label semibold">Fecha Inicio</label>
						<input type="date" class="form-control" name="fecha1" required>
					</div>

					<div class="col-lg-3">
						<label class="form-label semibold">Fecha Fin</label>
						<input type="date" class="form-control" name="fecha2" required>
					</div>

					<div class="col-lg-2">
						<br/>
						<button type="submit" class="btn btn-success" name="generar_reporte">Consultar</button>
					</div>

				</div>
				<br/>

				<div class="row">
					<div class="col-lg-4">
						<button type="button" id="btnlot" name="action"  class="lot d-flex w-15 btn btn-rounded btn-inline btn-.bg-primary" data-toggle="modal" data-target="#lot"> Cambio de Fecha</button>
					</div>
				</div>

				<br/>
				<div class="row">
					<div class="col-lg-6">
						<label class="form-label semibold">Buscar:</label>
						<input type="text" id="searchInput" class="form-control" placeholder="Escribe tu búsqueda aquí">
					</div>
				</div>	
	
				<div class="table-responsive">
			<table class="table" id="dataTable" style="font-size: 13px;">

				<thead class="thead-dark">
					<tr>
						<th>Tipo</th>
						<th>Consecutivo</th>
						<th>Despacho</th>
						<th>Nota</th>
						<th>Usuario</th>
						<th>fecha</th>
						<th><a href="#" id="marcarTodo">Marcar</a> | <a href="#" id="desmarcarTodo">Desmarcar</a></th>
					</tr>
				</thead>

				<tbody>
                            <?php
                            require_once("../../config/conexion.php");
                            if (isset($_POST['idTipo']) || (isset($_POST['fecha1']) && isset($_POST['fecha2']))) {
                                $sql = "SELECT d.tipo, t.TipoDoctos, d.Numero_documento, d.Numero_Docto_Base, d.notas, d.usuario, d.Fecha_Hora_Factura 
                                        FROM Documentos d, TblTipoDoctos t
                                        WHERE d.tipo = t.idTipoDoctos";

                                if (isset($_POST["idTipo"]) && !empty($_POST["idTipo"])) {
                                    $sql .= " AND d.tipo = '" . $_POST["idTipo"] . "'";
                                }

                                if (isset($_POST['fecha1'], $_POST['fecha2']) && !empty($_POST['fecha1']) && !empty($_POST['fecha2'])) {
                                    $fecha1 = date('Y-m-d', strtotime($_POST['fecha1']));
                                    $fecha2 = date('Y-m-d', strtotime($_POST['fecha2']));
                                    $sql .= " AND CONVERT(date, d.Fecha_Hora_Factura) BETWEEN '$fecha1' AND '$fecha2' ";
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
                                                <td>' . $row['Numero_Docto_Base'] . '</td>
                                                <td>' . substr($row['notas'], 0, 20) . '</td>
                                                <td>' . $row['usuario'] . '</td>
                                                <td>' . date_format($row["Fecha_Hora_Factura"], "Y-m-d") . '</td>
                                                <td><input type="checkbox" id="' . $row["tipo"] . '_' . $row["Numero_documento"] . '" name="id[]" value="' . $row["tipo"] . '|' . $row["Numero_documento"] . '" class="custom-checkbox"></td>
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

            </form>
        </div>
    </div>
</div>

    <?php require_once("../MainJs/js.php"); ?>

	<script type="text/javascript" src="reporte.js"></script>

	<script>
        document.addEventListener("DOMContentLoaded", function() {
            document.getElementById('marcarTodo').addEventListener('click', function(e) {
                e.preventDefault();
                //seleccionarTodo();
                checkAll();
				console.log("Marcar");
            });
            document.getElementById('desmarcarTodo').addEventListener('click', function(e) {
                e.preventDefault();
                //desmarcarTodo();
                uncheckAll();
				console.log("desMarcar")
            });
        });
    
            
        function checkAll() {

            // Obtener el contenedor de la tabla
    		var tableContainer = $('#dataTable').closest('.table-responsive');
    
			// Obtener todos los checkboxes visibles dentro del contenedor de la tabla después de aplicar el filtro de búsqueda
			var visibleCheckboxes = tableContainer.find('tbody tr:visible input[type="checkbox"]');
    
			// Marcar los checkboxes visibles
			visibleCheckboxes.prop('checked', true);
        }
    
        function uncheckAll() {

            // Obtener el contenedor de la tabla
    		var tableContainer = $('#dataTable').closest('.table-responsive');
    
			// Obtener todos los checkboxes visibles dentro del contenedor de la tabla después de aplicar el filtro de búsqueda
			var visibleCheckboxes = tableContainer.find('tbody tr:visible input[type="checkbox"]');
    
			// Desmarcar los checkboxes visibles
			visibleCheckboxes.prop('checked', false);
        }
    </script>

  </body>

  </html>
<?php
} else {
	header("Location:../../index.php");
}
?>