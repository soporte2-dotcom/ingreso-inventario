<?php
require_once("../../config/conexionserver.php");
if(isset($_SESSION["Id_Usuario"])){
date_default_timezone_set("America/Bogota");
$DateAndTime = date('d-m-Y h:i:s', time());
require_once("../../models/mdlPermisos.php");
$_permisos_entradas = new Permisos();
$permiteEntradaFrigopork = $_permisos_entradas->tiene_permiso_especial($_SESSION["Id_Usuario"], 'entrada_frigopork_manual');
?>
<!DOCTYPE html>
<html>
<?php require_once("../MainHead/head.php"); ?>
<?php require_once("../MainJs/js.php"); ?>

<script type="text/javascript">
	window.currentUser = '<?= strtoupper(trim($_SESSION["Id_Usuario"])) ?>';
	window.puedeEditarValor = ['LAUREN', 'SA'].indexOf(window.currentUser) !== -1;
	window.permiteEntradaFrigopork = <?= $permiteEntradaFrigopork ? 'true' : 'false' ?>;
</script>

<script type="text/javascript">

	var nitEspejo = '';

	$(function() {
		$("#nit3").autocomplete({
			source: "../../controller/terceros.php?op=terceroxnit",
			minLength: 2,
			select: function(event, ui) {
				$('#nombre3').val(ui.item.nombre);
				$('#telefono3').val('');
				$('#direccion3').html('<option value="" disabled selected>Seleccione...</option>');
				$.post('../../controller/terceros.php?op=combo_dir', { nit: ui.item.value }, function(html) {
					$('#direccion3').html(html);
				});
				$("#nit3").focus();
			}
		});

		// Facturar A — autocomplete con espejo automático a Enviar A
		$("#nit1").autocomplete({
			source: "../../controller/terceros.php?op=terceroxnit",
			minLength: 2,
			select: function(event, ui) {
				$('#nombre1').val(ui.item.nombre);
				$('#telefono1').val('');
				$('#select_dir1').html('<option value="" disabled selected>Seleccione...</option>');
				$.post('../../controller/terceros.php?op=combo_dir', { nit: ui.item.value }, function(html) {
					$('#select_dir1').html(html);
				});
				// Espejo a Enviar A si está vacío o sigue siendo el espejo anterior
				const nit2Actual = $('#nit2').val();
				if (!nit2Actual || nit2Actual === nitEspejo) {
					nitEspejo = ui.item.value;
					$('#nit2').val(ui.item.value);
					$('#nombre2').val(ui.item.nombre);
					$('#select_dir2').html('<option value="" disabled selected>Seleccione...</option>');
					$.post('../../controller/terceros.php?op=combo_dir', { nit: ui.item.value }, function(html) {
						$('#select_dir2').html(html);
					});
				}
				$("#nit1").focus();
			}
		});

		// Si el usuario cambia Enviar A manualmente, romper el espejo
		$("#nit2").autocomplete({
			source: "../../controller/terceros.php?op=terceroxnit",
			minLength: 2,
			select: function(event, ui) {
				nitEspejo = '';
				$('#nombre2').val(ui.item.nombre);
				$('#select_dir2').html('<option value="" disabled selected>Seleccione...</option>');
				$.post('../../controller/terceros.php?op=combo_dir', { nit: ui.item.value }, function(html) {
					$('#select_dir2').html(html);
				});
				$("#nit2").focus();
			}
		});

		// Autocomplete código producto en modal agregar manual
		$("#idproducto_m").autocomplete({
			source: "../../controller/productos.php?op=productoxid",
			minLength: 1,
			select: function(event, ui) {
				$('#nombre_prod_m').val(ui.item.Producto);
				$("#idproducto_m").val(ui.item.value);
				$("#cantidad_m").focus();
			}
		});

		// Teléfono Facturar A al seleccionar dirección
		$(document).on('change', '#select_dir1', function() {
			const direccion = $(this).val();
			if (direccion) {
				$.post('../../controller/terceros.php?op=telefono_dir', { direccion: direccion }, function(data) {
					data = JSON.parse(data);
					$('#telefono1').val(data.telefono_1);
				});
				// Espejo de dirección a Enviar A si el NIT es el mismo
				if ($('#nit2').val() === nitEspejo && nitEspejo) {
					$('#select_dir2').val(direccion);
				}
			}
		});
	});

</script>

<title>Cervalle:: Entradas</title>

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
							<h3>Entradas</h3>
							<ol class="breadcrumb breadcrumb-simple">
								<li><a href="#">Home</a></li>
								<li class="active">Entradas</li>
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

					<div class="col-lg-2">
						<fieldset class="form-group">
							<label class="form-label semibold" id="txt_consecutivo">Consecutivo</label>
							<input type="text" name="consecutivo" id="consecutivo" class="form-control" readonly/>
						</fieldset>
					</div>

					<div class="col-lg-2">
						<fieldset class="form-group">
							<label class="form-label semibold" id="txt_fecha">Fecha</label>
							<input type="text" id="fecha" class="form-control" value="<?php echo $DateAndTime ?>" readonly />
						</fieldset>
					</div>

				</div>

				<div class="row">

					<div class="col-lg-3">
						<fieldset class="form-group">
							<label class="form-label semibold" id="txt_docref">¿Tiene documento referencia?</label>
							<select id="docref" name="docref" class="form-control" onchange="showInp()" required>
								<option value="0">No</option>
								<option value="1">Si</option>
								<option value="2" id="opt_manual" style="display:none">Manual</option>
							</select>
						</fieldset>
					</div>

					<div class="col-lg-3">
						<fieldset class="form-group">
							<label class="form-label semibold" style="display: none" id="txt_tipoDocRef">Documento</label>
							<select id="tipoDocRef" style="display: none" name="tipoDocRef" class="form-control" required>
								<option value="0">Sin Documento</option>
								<option value="938">Traslado Almacen</option>
								<option value="215">Traslado Almacen Acopi</option>
								<option value="155">Traslado Planta Balanceado</option>
							</select>
						</fieldset>
					</div>
				</div>

				<div class="row">

					<div class="col-lg-2">
						<fieldset class="form-group">
							<label class="form-label semibold" id="txt_numero">Ingresar el Numero</label>
							<input type="text" name="numero" id="numero" class="form-control" required/>
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
					<input type="hidden" name="sw" id="sw" class="form-control" />

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

					<div class="col-lg-2">
						<fieldset class="form-group">
							<label class="form-label semibold" style="display: none" id="txt_fecha1">Fecha</label>
							<input type="text" style="display: none" id="fecha1" class="form-control" value="<?php echo $DateAndTime ?>" readonly />
						</fieldset>
					</div>

					
					
					
											
				</div>
				
				<div class="col-lg-12" id="hr1" style="display: none">
					<h6 class="m-t-lg with-border">Facturar A: </h6>
				</div>
		
				<div class="row">

					<div class="col-lg-2">
						<fieldset class="form-group">
							<label class="form-label semibold" style="display: none" id="txt_nit1">Nit/Cedula</label>
							<input type="text" style="display: none" name="nit1" id="nit1" class="form-control ui-autocomplete-input" autocomplete="off"/>
						</fieldset>
					</div>

					<div class="col-lg-3">
						<fieldset class="form-group">
							<label class="form-label semibold" style="display: none" id="txt_nombre1">Nombre</label>
							<input type="text" style="display: none" name="nombre1" id="nombre1" class="form-control" readonly/>
						</fieldset>
					</div>

					<div class="col-lg-3">
						<fieldset class="form-group">
							<label class="form-label semibold" style="display: none" id="txt_direccion1">Direccion</label>
							<input type="text" style="display: none" id="direccion1" name="direccion1" class="form-control" readonly>
							<select style="display: none" id="select_dir1" name="select_dir1" class="form-control"></select>
						</fieldset>
					</div>

					<div class="col-lg-2">
						<fieldset class="form-group">
							<label class="form-label semibold" style="display: none" id="txt_telefono1">Telefono</label>
							<input type="text" style="display: none" name="telefono1" id="telefono1" class="form-control" readonly/>
						</fieldset>
					</div>

				</div>

				<div class="col-lg-12" id="hr2" style="display: none">
					<h6 class="m-t-lg with-border">Enviar A: </h6>
				</div>

				<div class="row">

					<div class="col-lg-2">
						<fieldset class="form-group">
							<label class="form-label semibold" style="display: none" id="txt_nit2">Nit/Cedula</label>
							<input type="text" style="display: none" name="nit2" id="nit2" class="form-control ui-autocomplete-input" autocomplete="off"/>
						</fieldset>
					</div>

					<div class="col-lg-3">
						<fieldset class="form-group">
							<label class="form-label semibold" style="display: none" id="txt_nombre2">Nombre</label>
							<input type="text" style="display: none" name="nombre2" id="nombre2" class="form-control" readonly/>
						</fieldset>
					</div>

					<div class="col-lg-3">
						<fieldset class="form-group">
							<label class="form-label semibold" style="display: none" id="txt_direccion2">Direccion</label>
							<input type="text" style="display: none" id="direccion2" name="direccion2" class="form-control" readonly>
							<select style="display: none" id="select_dir2" name="select_dir2" class="form-control"></select>
						</fieldset>
					</div>

				</div>

				<div class="col-lg-12" id="hr_prov_manual" style="display: none">
					<h6 class="m-t-lg with-border">Proveedor: </h6>
				</div>

				<div class="row">

					<div class="col-lg-2">
						<fieldset class="form-group">
							<label class="form-label semibold" style="display: none" id="txt_nit3">Nit/Cedula</label>
							<input type="tel" name="nit3" id="nit3" style="display: none" class="form-control input-medium ui-autocomplete-input"  autocomplete="off" required/>
						</fieldset>
					</div>

					<div class="col-lg-3">
						<fieldset class="form-group">
							<label class="form-label semibold" style="display: none" id="txt_nombre3">Nombre</label>
							<input type="text" style="display: none" name="nombre3" id="nombre3" class="form-control" required readonly/>
						</fieldset>
					</div>

					<div class="col-lg-3">
						<fieldset class="form-group">
							<label class="form-label semibold" style="display: none" id="txt_direccion3">Direccion</label>
							<select id="direccion3" name="direccion3" style="display: none" class="form-control" required></select>
						</fieldset>
					</div>

					<div class="col-lg-2">
						<fieldset class="form-group">
							<label class="form-label semibold" style="display: none" id="txt_telefono3">Telefono</label>
							<input type="text" name="telefono3" id="telefono3" style="display: none" class="form-control" required readonly/>
						</fieldset>
					</div>

				</div>

				<div class="col-lg-12" id="hr3" style="display: none">
					<h6 class="m-t-lg with-border"></h6>
				</div>

				<div class="row">

					<div class="col-lg-2">
						<fieldset class="form-group">
							<label class="form-label semibold" style="display: none" id="txt_pedido1">Pedido</label>
							<input type="text" style="display: none" id="pedido1" name="pedido1" class="form-control" readonly />
						</fieldset>
					</div>

					<div class="col-lg-2">
						<fieldset class="form-group">
							<label class="form-label semibold" style="display: none" id="txt_traslfact1">Traslado/Factura</label>
							<input type="text" style="display: none" name="traslfact1" id="traslfact1" class="form-control" disabled/>
						</fieldset>
					</div>

					<div class="col-lg-2">
						<div class="form-check form-check-inline">
  							<label class="form-label semibold" style="display: none" id="txt_remision">
  							<input class="form-check-input" style="display: none" type="checkbox" value="11" id="remision" name="remision"> Remision</label>
						</div>
					</div>

					<div class="col-lg-2">
						<fieldset class="form-group">
							<label class="form-label semibold" style="display: none" id="txt_transportador">Transportador</label>
							<select id="idTransportador" name="idTransportador" style="display: none" class="form-control"></select>
						</fieldset>
					</div>

					<div class="col-lg-2">
						<fieldset class="form-group">
							<label class="form-label semibold" style="display: none" id="txt_vehiculo">Placa</label>
							<select id="idVehiculo" name="idVehiculo" style="display: none" class="form-control"></select>
						</fieldset>
					</div>

				</div>

				<div class="row">
					<div class="col-sm-6 col-md-3 col-lg-2 d-flex mx-auto">
						<button type="button" id="btnlot" name="action"  class="lot d-flex w-15 btn btn-rounded btn-inline btn-.bg-primary" data-toggle="modal" data-target="#lot" style="display: none"> Lote</button>
					</div>
					<div class="col-sm-6 col-md-3 col-lg-2 d-flex mx-auto">
						<button type="button" id="btneliminarsel" class="d-flex w-15 btn btn-rounded btn-inline btn-danger" style="display: none" onclick="eliminarSeleccionados()">
							<i class="fa fa-trash"></i> Eliminar sel.
						</button>
					</div>
					<div class="col-sm-6 col-md-3 col-lg-2 d-flex mx-auto">
						<button type="button" id="btnAgregarProd" class="d-flex w-15 btn btn-rounded btn-inline btn-primary" style="display: none" data-toggle="modal" data-target="#modalAgregarProd">
							<i class="fa fa-plus"></i> Agregar Producto
						</button>
					</div>
				</div>

				<div class="container-fluid">
					<div class="row">
						<div class="col-lg-12 col-md-12 col-sm-12">
							<table id="tb-doc" class="table table-bordered table-striped table-vcenter js-dataTable-full">
								<thead>
									<tr>
											<th class="text-center">Seq</th>
											<th class="text-center">Producto</th>
											<th class="text-center">Nombre</th>
											<th class="text-center">U medida</th>
											<th class="text-center">Cantidad</th>
											<th class="text-center">% Desc</th>
											<th class="text-center">% Iva</th>
											<th class="text-center">Valor</th>
											<th class="text-center">Lote</th>
											<th class="text-center">Fecha Venc</th>
											<th class="text-center">Nota</th>
											<th class="text-center">Unidades</th>
											<th class="text-center">Acciones</th>
											<th><a href="#" id="marcarTodo">Marcar</a> | <a href="#" id="desmarcarTodo">Desmarcar</a></th>
									</tr>
								</thead>				
							</table>
						</div>
					</div>
				</div>
				<br/>

				<div class="row">				

					<div class="col-lg-2">
						<h6><b>Total Cant: </b> <span id="totalCantidad"> </span></h6>
					</div>

					<div class="col-lg-2">
						<h6><b>SubTotal: </b> <span id="total"> </span></h6>
					</div>

					<div class="col-lg-2">
						<h6><b>Descuento: </b> <span id="totalDescuento"> </span></h6>
					</div>

					<div class="col-lg-2">
						<h6><b>IVA: </b> <span id="totalImpuesto"> </span></h6>
					</div>

					<div class="col-lg-2">
						<h6><b>Total: </b> <span id="valorTotal"> </span></h6>
					</div>
				</div>

				<br/>
				
				<div class="form-group py-2">
					<label class="font-weight-bold">Fecha de Recibo y Notas Generales: </label>
					<textarea class="form-control" rows="3" name="notas" id="notas"></textarea>
        		</div>

				<div class="row">
					<div class="col-sm-6 col-md-3 col-lg-2 d-flex mx-auto">
						<button type="button" style="display: none" id="btnguardar"  class="d-flex w-15 btn btn-rounded btn-inline btn-success">Guardar</button>
					</div>
					<div class="col-sm-6 col-md-3 col-lg-2 d-flex mx-auto">
						<button type="button" style="display: none" id="btnreiniciar" class="d-flex w-15 btn btn-rounded btn-inline btn-warning" onclick="reiniciarDocumento()" title="Vuelve a cargar las líneas originales del pedido">
							<i class="fa fa-refresh"></i> Reiniciar
						</button>
					</div>
				</div>

							<!-- Modal Agregar-->
							<div class="modal fade" id="modalagregar" tabindex="-1" role="dialog" aria-labelledby="modalagregar" aria-hidden="true" data-backdrop="static" data-keyboard="false">
								<div class="modal-dialog" role="document">
									<div class="modal-content">
										<div class="modal-header">
											<h5 class="modal-title" id="modalagregar">Producto</h5>
											<button type="button" class="close" data-dismiss="modal" aria-label="Close">
												<span aria-hidden="true">&times;</span>
											</button>
										</div>
										<div class="modal-body">
											<div class="row">												
												<div class="col-lg-12 col-md-12 col-sm-12">
													<label>Codigo Producto</label>
													<input class="form-control" type="number" name="idproducto" id="idproducto" readonly>
												</div>
												<div class="col-lg-12 col-md-12 col-sm-12">
													<label>Cantidad</label>
													<input class="form-control" type="text" name="cantidad" id="cantidad">
												</div>
												<div class="col-lg-12 col-md-12 col-sm-12">
													<label>Valor Unitario</label>
													<input class="form-control" type="text" name="Valor_Unitario" id="Valor_Unitario">
												</div>
												<div class="col-lg-12 col-md-12 col-sm-12">
													<label>Lote</label>
													<input class="form-control" type="text" name="lote" id="lote" maxlength="18">
												</div>
												<div class="col-lg-12 col-md-12 col-sm-12">
													<label>Fecha de Vencimiento</label>
													<input class="form-control" type="date" name="fecha_vence" id="fecha_vence">
												</div>
											</div>
										</div>
										<div class="modal-footer">
											<button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
											<button type="button" id="btneditar" class="btn btn-primary">Editar</button>
										</div>
									</div>
								</div>
							</div>
							<!-- Fin modal Agregar -->

							<!-- Modal Agregar Producto Manual -->
							<div class="modal fade" id="modalAgregarProd" tabindex="-1" role="dialog" aria-labelledby="modalAgregarProdTitle" aria-hidden="true" data-backdrop="static" data-keyboard="false">
								<div class="modal-dialog" role="document">
									<div class="modal-content">
										<div class="modal-header">
											<h5 class="modal-title" id="modalAgregarProdTitle">Producto</h5>
											<button type="button" class="close" data-dismiss="modal" aria-label="Close">
												<span aria-hidden="true">&times;</span>
											</button>
										</div>
										<div class="modal-body">
											<div class="row">
												<div class="col-lg-12 col-md-12 col-sm-12">
													<label>Codigo Producto</label>
													<input class="form-control" type="number" name="idproducto_m" id="idproducto_m" autocomplete="off">
												</div>
												<div class="col-lg-12 col-md-12 col-sm-12">
													<label>Nombre</label>
													<input class="form-control" type="text" id="nombre_prod_m" readonly>
												</div>
												<div class="col-lg-12 col-md-12 col-sm-12">
													<label>Cantidad</label>
													<input class="form-control" type="text" name="cantidad_m" id="cantidad_m">
												</div>
												<div class="col-lg-12 col-md-12 col-sm-12">
													<label>Valor Unitario</label>
													<input class="form-control" type="text" name="Valor_Unitario_m" id="Valor_Unitario_m" readonly>
												</div>
												<div class="col-lg-12 col-md-12 col-sm-12">
													<label>% IVA</label>
													<input class="form-control" type="text" id="porcentaje_iva_m" readonly>
												</div>
												<div class="col-lg-12 col-md-12 col-sm-12">
													<label>Lote</label>
													<select class="form-control" name="lote_m" id="lote_m" required></select>
												</div>
												<input type="hidden" id="porcentaje_impuesto_m" name="porcentaje_impuesto_m" value="0">
												<input type="hidden" name="fecha_vence_m" id="fecha_vence_m">
											</div>
										</div>
										<div class="modal-footer">
											<button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
											<button type="button" id="btnRegistrarProd" class="btn btn-primary">Registrar</button>
										</div>
									</div>
								</div>
							</div>
							<!-- Fin Modal Agregar Producto Manual -->

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
												
												<div class="col-lg-12">
													<label>Lote</label>
													<input type="text" class="form-control" name="lote1" id="lote1">							
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

						<!-- Modal Imprimir Etiqueta -->
						<div class="modal fade" id="modalImprimirEtiqueta" tabindex="-1" role="dialog" aria-labelledby="modalImprimirEtiquetaLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
							<div class="modal-dialog" role="document">
								<div class="modal-content">
									<div class="modal-header">
										<h5 class="modal-title" id="modalImprimirEtiquetaLabel"><i class="fa fa-tag"></i> Imprimir Etiqueta</h5>
										<button type="button" class="close" data-dismiss="modal" aria-label="Close">
											<span aria-hidden="true">&times;</span>
										</button>
									</div>
									<div class="modal-body">
										<div class="row">
											<div class="col-lg-12 col-md-12 col-sm-12">
												<label>Código Producto</label>
												<input class="form-control" type="text" id="etq_codigo" readonly>
											</div>
											<div class="col-lg-12 col-md-12 col-sm-12 mt-2">
												<label>Nombre Producto</label>
												<input class="form-control" type="text" id="etq_nombre" readonly>
											</div>
											<div class="col-lg-12 col-md-12 col-sm-12 mt-2">
												<label>Lote</label>
												<input class="form-control" type="text" id="etq_lote" readonly>
											</div>
											<div class="col-lg-12 col-md-12 col-sm-12 mt-2">
												<label>Cantidad</label>
												<input class="form-control" type="number" id="etq_cantidad" min="0.01" step="any">
											</div>
										</div>
									</div>
									<div class="modal-footer">
										<button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
										<button type="button" class="btn btn-success" id="btnImprimirEtiqueta">
											<i class="fa fa-print"></i> Imprimir
										</button>
									</div>
								</div>
							</div>
						</div>
						<!-- Fin Modal Imprimir Etiqueta -->

						</form>

			</div>
		</div>
	</div>
	<!-- Contenido -->
	<script type="text/javascript" src="nuevodoc.js?v=<?php echo time(); ?>"></script>

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
		
<style>
.btn-disabled {
		opacity: 0.65;
		cursor: not-allowed;
}

/* Estilo específico para el botón guardar cuando está deshabilitado */
.btn-success.btn-disabled {
		background-color: #6c757d !important; /* Color gris para botones deshabilitados */
		border-color: #6c757d !important;
}

/* Estilo para el texto dentro del botón cuando está deshabilitado */
.btn-disabled span {
		text-decoration: line-through;
}

/* Añadir un ícono de candado para indicar que el documento está bloqueado */
.btn-disabled:before {
		content: "🔒 "; /* Emoji de candado */
}

/* Estilos para edición inline */
.editable-cell {
    cursor: pointer;
    transition: background-color 0.2s;
}

.editable-cell:hover {
    background-color: #e3f2fd !important;
}

.editing {
    background-color: #fff3cd !important;
    border: 2px solid #ffc107 !important;
}

.edit-input {
    width: 100%;
    border: 1px solid #007bff;
    border-radius: 4px;
    padding: 4px 8px;
    font-size: 14px;
}

.btn-action {
    padding: 2px 8px;
    margin: 0 2px;
    font-size: 12px;
}

.edit-actions {
    white-space: nowrap;
}

/* Estados visuales */
.saving {
    opacity: 0.6;
    pointer-events: none;
}

.saved {
    background-color: #d4edda !important;
    transition: background-color 1s;
}

/* Estilos para botón duplicar */
.btn-duplicar {
    background-color: #17a2b8 !important;
    border-color: #17a2b8 !important;
    color: white !important;
}

.btn-duplicar:hover {
    background-color: #138496 !important;
    border-color: #117a8b !important;
}

/* Asegurar que los botones quepan en la celda */
.edit-actions {
    white-space: nowrap;
    display: flex;
    gap: 2px;
    justify-content: center;
}

.btn-action {
    padding: 2px 6px;
    font-size: 12px;
    width: 28px;
    height: 28px;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* Hacer que parezcan inputs desde el principio */
.editable-cell {
    background-color: #ffffff !important;
    border: 1px solid #ced4da !important;
    cursor: text;
    padding: 8px !important;
    position: relative;
    transition: all 0.2s ease;
}

.editable-cell:hover {
    border-color: #80bdff !important;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
    background-color: #fff !important;
}

/* Icono de edición sutil */
.editable-cell::before {
    content: "✏️";
    position: absolute;
    right: 8px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 11px;
    opacity: 0;
    transition: opacity 0.2s;
}

.editable-cell:hover::before {
    opacity: 0.5;
}

/* Efecto al hacer clic */
.editable-cell:active {
    transform: scale(0.98);
}

</style>

</body>

</html>

<?php
}else{
	header("Location:../../index.php");
}
?>