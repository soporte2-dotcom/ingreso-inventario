<?php
require_once("../../config/conexionserver.php");
if(isset($_SESSION["Id_Usuario"])){
    date_default_timezone_set("America/Bogota");
    $DateAndTime = date('d-m-Y h:i:s', time());
    
    // Variables para almacenar los datos del formulario
    $notaGeneral = "";
    $idTipo = isset($_POST["idTipo"]) ? $_POST["idTipo"] : '';
    $numdoc = isset($_POST['numdoc']) ? $_POST['numdoc'] : '';
    
    // Si se envió el formulario, cargar la nota general desde la base de datos
    if (isset($_POST['idTipo']) || isset($_POST['numdoc'])) {
        require_once("../../config/conexion.php");
        
        // Consultar la nota general del documento
        $sql_nota = "SELECT Notas FROM Documentos WHERE tipo = ? AND Numero_documento = ?";
        $params_nota = array($idTipo, $numdoc);
        $stmt_nota = sqlsrv_query($con, $sql_nota, $params_nota);
        
        if ($stmt_nota && $row_nota = sqlsrv_fetch_array($stmt_nota, SQLSRV_FETCH_ASSOC)) {
            $notaGeneral = $row_nota['Notas'];
        }
        
        if ($stmt_nota) {
            sqlsrv_free_stmt($stmt_nota);
        }
    }
?>
<!DOCTYPE html>
<html>
<?php require_once("../MainHead/head.php"); ?>
<?php require_once("../MainJs/js.php"); ?>

<title>Cervalle:: Lotes</title>

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
                            <h3>Lotes</h3>
                            <ol class="breadcrumb breadcrumb-simple">
                                <li><a href="#">Home</a></li>
                                <li class="active">Lotes</li>
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
                                <label class="form-label semibold">Tipo de Documento</label>
                                <select id="idTipo" name="idTipo" class="form-control" required>
                                    <option value="">Seleccione un tipo</option>
                                </select>
                            </fieldset>
                        </div>

                        <div class="col-lg-3">
                            <label class="form-label semibold">Número de Documento</label>
                            <input type="number" class="form-control" name="numdoc" id="numdoc" 
                                   value="<?php echo htmlspecialchars($numdoc); ?>" required>
                        </div>

                        <div class="col-lg-2">
                            <br/>
                            <button type="submit" class="btn btn-success" name="generar_reporte">Consultar</button>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-lg-8">        
                            <fieldset class="form-group">
                                <label class="form-label semibold">Nota General del Documento</label>
                                <textarea class="form-control" name="notas" id="notas" placeholder="Nota general para todo el documento"><?php echo htmlspecialchars($notaGeneral); ?></textarea>
                            </fieldset>
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
                                            <th class="text-center">Producto</th>
                                            <th class="text-center">Cantidad</th>
                                            <th class="text-center">Valor</th>
                                            <th class="text-center">Lote</th>
                                            <th class="text-center">Nota Línea</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        require_once("../../config/conexion.php");
                                        if (isset($_POST['idTipo']) || isset($_POST['numdoc'])) {
                                            $sql = "SELECT dl.seq, dl.tipo, t.TipoDoctos, dl.Numero_Documento, 
                                                           p.Producto, dl.IdProducto, dl.Cantidad_Facturada, 
                                                           dl.Valor_Unitario, dl.Numero_Lote, dl.Nota_Linea
                                                    FROM Documentos_Lin dl 
                                                    INNER JOIN TblProducto p ON p.IdProducto = dl.IdProducto
                                                    INNER JOIN TblTipoDoctos t ON dl.tipo = t.idTipoDoctos
                                                    WHERE 1=1";
                                            
                                            $params = array();
                                            
                                            if (!empty($idTipo)) {
                                                $sql .= " AND dl.tipo = ?";
                                                $params[] = $idTipo;
                                            }

                                            if (!empty($numdoc)) {
                                                $sql .= " AND dl.Numero_Documento = ?";
                                                $params[] = $numdoc;
                                            }

                                            $sql .= " ORDER BY dl.seq ASC";
                                            
                                            $options = array("Scrollable" => SQLSRV_CURSOR_KEYSET);
                                            $stmt = sqlsrv_query($con, $sql, $params, $options);

                                            if ($stmt === false) {
                                                echo '<tr><td colspan="7">Error en la consulta: ' . sqlsrv_errors()[0]['message'] . '</td></tr>';
                                            } else {
                                                $row_count = sqlsrv_num_rows($stmt);
                                                if ($row_count == 0) {
                                                    echo '<tr><td colspan="7">No se encontraron resultados.</td></tr>';
                                                } else {
                                                    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                                                        echo '<tr>
                                                                <td>' . htmlspecialchars($row['TipoDoctos']) . '</td>
                                                                <td>' . htmlspecialchars($row['Numero_Documento']) . '</td>
                                                                <td>' . htmlspecialchars($row['Producto']) . '</td>
                                                                <td>' . number_format($row["Cantidad_Facturada"], 2) . '</td>
                                                                <td>' . number_format($row["Valor_Unitario"], 2) . '</td>
                                                                <td><input type="text" id="lote_' . $row["tipo"] . '_' . $row["Numero_Documento"] . '_' . $row["seq"] . '" value="'. htmlspecialchars($row["Numero_Lote"]) . '" class="form-control lote-input"></td>
                                                                <td><input type="text" id="nota_' . $row["tipo"] . '_' . $row["Numero_Documento"] . '_' . $row["seq"] . '" value="'. htmlspecialchars($row["Nota_Linea"]) . '" class="form-control nota-linea-input"></td>
                                                            </tr>';
                                                    }
                                                }
                                                sqlsrv_free_stmt($stmt);
                                            }
                                            sqlsrv_close($con);
                                        } else {
                                            echo '<tr><td colspan="7">Realice una consulta para ver los resultados.</td></tr>';
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <br/>

                    <!-- Campos ocultos para mantener los valores de filtro -->
                    <?php if (isset($_POST['idTipo']) || isset($_POST['numdoc'])): ?>
                    <input type="hidden" id="current_idTipo" value="<?php echo htmlspecialchars($idTipo); ?>">
                    <input type="hidden" id="current_numdoc" value="<?php echo htmlspecialchars($numdoc); ?>">
                    <input type="hidden" id="current_nota" value="<?php echo htmlspecialchars($notaGeneral); ?>">
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-sm-6 col-md-3 col-lg-2 d-flex mx-auto">
                            <button type="button" id="btnupdate" class="d-flex w-15 btn btn-rounded btn-inline btn-success">Actualizar Lotes y Notas</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- Contenido -->
    <script type="text/javascript" src="nuevodoc.js?v=<?php echo time(); ?>"></script>
</body>
</html>
<?php
} else {
    header("Location:../../index.php");
    exit();
}
?>