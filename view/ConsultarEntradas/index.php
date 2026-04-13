<?php
require_once("../../config/conexionserver.php");
if(isset($_SESSION["Id_Usuario"])){
date_default_timezone_set("America/Bogota");
$DateAndTime = date('d-m-Y h:i:s', time());
?>
<!DOCTYPE html>
<html>
    <?php require_once("../MainHead/head.php");?>
	<title>Cervalle::Consultar Entradas</title>
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
							<h3>Consultar Entradas</h3>
							<ol class="breadcrumb breadcrumb-simple">
								<li><a href="#">Home</a></li>
								<li class="active">Consultar Entradas</li>
							</ol>
						</div>

					</div>
				</div>
			</header>
			<form  method="post" id="doc_form">	
          
            <div class="tbl-cell tbl-cell-action">
                <a href="../Entradas/index.php" class="btn btn-primary btn-md">
                    <i class="fa fa-plus-circle"></i> Nueva Entrada
                </a>
            </div>

        <div class="box-typical box-typical-padding">
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
	
	<script type="text/javascript" src="consultar.js?v=5"></script>

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