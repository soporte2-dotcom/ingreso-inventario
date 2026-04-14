<?php
    require_once("../config/conexionserver.php");
    require_once("../models/mdlSalidas.php");
    $salidas = new Salidas();

    switch($_GET["op"]){
   
        case "get_farm_info":
            echo $salidas->get_farm_info($_POST["idTipo"]);
        break;

        case "combo_lotes":
            echo $salidas->combo_lotes();
        break;

        case "insert_doc_manual":
            $dir1m = $_POST["dir1"] ?? '';
            if(strpos($dir1m, ",") !== false) $dir1m = explode(",", $dir1m)[0];
            $dir2m = $_POST["dir2"] ?? '';
            if(strpos($dir2m, ",") !== false) $dir2m = explode(",", $dir2m)[0];
            date_default_timezone_set("America/Bogota");
            $fecha_raw = $_POST["fecha_factura"] ?? date('Y-m-d');
            $fecha_manual = $fecha_raw . ' ' . date("H:i:s");
            $resultado = $salidas->insert_doc_manual(
                $_POST["idTipo"],
                $_POST["nit1"], $dir1m,
                $_POST["nit2"], $dir2m,
                $fecha_manual,
                $_SESSION["Id_Usuario"]
            );
            echo $resultado;
        break;

        case "insert_doc_salida":
            if(($_POST["docref"] ?? 0) == 0){
                $resultado = $salidas->insert_doc_salida($_POST["idTipo"], $_POST["numero"], $_SESSION["Id_Usuario"]);
            } else {
                // Validar concepto de devolución obligatorio
                $idConcepto     = isset($_POST["idConceptoDevolucion"]) ? (int)$_POST["idConceptoDevolucion"] : 0;
                $nombreConcepto = trim($_POST["nombreConceptoDevolucion"] ?? '');
                if ($idConcepto <= 0 || $nombreConcepto === '') {
                    echo json_encode(["status" => "error", "message" => "El concepto de devolución es obligatorio."]);
                    break;
                }
                // Verificar que el concepto exista y esté activo en MySQL
                require_once("../models/mdlConceptosDevolucion.php");
                $conceptos = new ConceptosDevolucion();
                $nombreVerificado = $conceptos->validar_activo($idConcepto);
                if ($nombreVerificado === null) {
                    echo json_encode(["status" => "error", "message" => "El concepto seleccionado no es válido o está inactivo."]);
                    break;
                }
                $resultado = $salidas->insert_devolucion(
                    $_POST["idTipo"], $_POST["numero"], $_POST["tipoDocRef"],
                    $_SESSION["Id_Usuario"], $idConcepto, $nombreVerificado
                );
            }
            echo $resultado;
        break;

        case "guardar_salida":
            $dir1 = $_POST["direccion1"] ?? '';
            if(strpos($dir1, ",") !== false) $dir1 = explode(",", $dir1)[0];

            $dir2 = $_POST["direccion2"] ?? '';
            if(strpos($dir2, ",") !== false) $dir2 = explode(",", $dir2)[0];

            $dotacion = isset($_POST["dotacion_epp"]) && $_POST["dotacion_epp"] == '1';
            date_default_timezone_set("America/Bogota");
            $fecha_factura_raw = $_POST["fecha_factura2_iso"] ?? '';
            $fecha_factura = $fecha_factura_raw ? $fecha_factura_raw . ' ' . date("H:i:s") : '';

            $salidas->guardar_salida(
                $_POST["tipo"], $_POST["numdoc"],
                $_POST["nit1"], $dir1,
                $_POST["nit2"], $dir2,
                $_POST["traslfact1"] ?? '', $_POST["notas"] ?? '',
                $dotacion, $fecha_factura
            );
        break;

        case "update_lote_salida":
            $salidas->update_lote_salida($_POST["tipo"], $_POST["numdoc"], $_POST["lote1"], $_POST["seqs"] ?? '');
        break;

        case "update_notas_etapa":
            $salidas->update_notas_etapa($_POST["tipo"], $_POST["numdoc"], $_POST["notas"]);
        break;

        case "get_info_producto":
            echo $salidas->get_info_producto(
                $_POST["idProducto"],
                $_POST["tipo"]      ?? '',
                $_POST["numdoc"]    ?? '',
                $_POST["nit"]       ?? '',
                $_POST["direccion"] ?? ''
            );
        break;

        case "agregar_linea_manual":
            $resultado = $salidas->agregar_linea_manual(
                $_POST["tipo"], $_POST["numdoc"],
                $_POST["idProducto"],
                $_POST["cantidad"],
                $_POST["valorUnitario"] ?? 0,
                $_POST["lote"] ?? '0',
                $_POST["fechaVence"] ?? date('Y-m-d'),
                $_POST["porcentajeImpuesto"] ?? 0
            );
            echo $resultado;
        break;

        case "listar_salidas":
            $datos=$salidas->listar_salidas_x_usuario($_SESSION["Id_Usuario"]);
            $data= Array();
            foreach($datos as $row){
                $sub_array = array();
                $sub_array[] = date_format($row["Fecha_Hora_Factura"], "d-m-Y H:i:s");
                $sub_array[] = $row["TipoDoctos"];
                $sub_array[] = $row["Numero_documento"];
                $sub_array[] = $row["nit_Cedula"];
                $sub_array[] = $row["Nombre_Cliente"];
                $sub_array[] = $row["direccion"];
                $sub_array[] = $row["usuario"];
                
                if($row["exportado"] == "S"){
                    $sub_array[] = '<span class="label label-success">Sí</span>';
                } else {
                    $sub_array[] = '<span class="label label-danger">No</span>';
                }

                $sub_array[] = '<a href="../Salidas/?tipo='.$row["tipo"].'&consecutivo='.$row["Numero_documento"].'" 
                class="btn btn-rounded btn-sm btn-primary" title="Ver Detalle">
                <i class="fa fa-eye"></i> </a>';

                $data[] = $sub_array;
            }

            $results = array(
                "sEcho"=>1,
                "iTotalRecords"=>count($data),
                "iTotalDisplayRecords"=>count($data),
                "aaData"=>$data);
            echo json_encode($results);
        break;

        case "mostrar_salida":
            $datos=$salidas->listar_doc_x_id($_POST["tipo"],$_POST["consecutivo"]);  
            if(is_array($datos)==true and count($datos)>0){
                foreach($datos as $row)
                {
                    foreach($row as $key => $value) {
                         if (!is_numeric($key)) { // Only string keys to avoid duplicates from fetch_array
                             $output[$key] = $value;
                         }
                    }
                }
                echo json_encode($output);

            }   
        break;

        case "cargar_masiva_excel":
            if (!isset($_FILES['archivo']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
                echo json_encode(['status' => 'error', 'message' => 'No se recibió el archivo o hubo un error al subir']);
                break;
            }
            $tmpPath  = $_FILES['archivo']['tmp_name'];
            $origName = strtolower($_FILES['archivo']['name']);
            if (pathinfo($origName, PATHINFO_EXTENSION) !== 'xlsx') {
                echo json_encode(['status' => 'error', 'message' => 'Solo se aceptan archivos .xlsx']);
                break;
            }
            echo $salidas->cargar_masiva_excel(
                $_POST['tipo']      ?? '',
                $_POST['numdoc']    ?? '',
                $_POST['nit']       ?? '',
                $_POST['direccion'] ?? '',
                $tmpPath
            );
        break;

        case "validar_os":
            echo $salidas->validar_os($_POST["numero"] ?? '');
        break;

        case "listar_detalle_salida":
            $datos = $salidas->listar_docdetalle_x_id($_POST["tipo"], $_POST["consecutivo"]);
            $data = Array();
            
            foreach($datos as $row) {
                $sub_array = array();
                $sub_array[] = $row["seq"];
                $sub_array[] = $row["IdProducto"];
                $sub_array[] = $row["Producto"];
                $sub_array[] = $row["Unidad"];
                $sub_array[] = number_format($row["Cantidad_Facturada"], 2);
                $sub_array[] = number_format($row["Porcentaje_Descuento_1"], 2);
                $sub_array[] = number_format($row["Valor_Unitario"], 2);
                $sub_array[] = $row["Numero_Lote"];
                $sub_array[] = $row["Fecha_Vence"] ? date_format($row["Fecha_Vence"], "d/m/Y") : '';
                $sub_array[] = $row["Nota_Linea"];
                $sub_array[] = $row["Unidades"];

                if($row["exportado"] == 'N') {
                    $sub_array[] = '
                        <div class="edit-actions">
                            <button type="button" class="btn btn-info btn-sm btn-action btn-duplicar" title="Duplicar línea" 
                                    onclick="duplicarLinea(\'' . $_POST["tipo"] . '\', \'' . $_POST["consecutivo"] . '\', \'' . $row["IdProducto"] . '\', \'' . $row["seq"] . '\')">
                                <i class="fa fa-copy"></i>
                            </button>
                            <button type="button" class="btn btn-warning btn-sm btn-action btn-eliminar" title="Eliminar registro" 
                                    onclick="eliminar(\'' . $_POST["tipo"] . '\', \'' . $_POST["consecutivo"] . '\', \'' . $row["IdProducto"] . '\', \'' . $row["seq"] . '\')">
                                <i class="fa fa-trash"></i>
                            </button>
                        </div>
                    ';
                    $sub_array[] = '<input type="checkbox" id="' . $row["IdProducto"] . '" name="id[]" value="' . $row["IdProducto"] . '">';
                } else {
                    $sub_array[] = '
                        <div class="edit-actions">
                            <button type="button" class="btn btn-info btn-sm btn-action btn-duplicar" title="Duplicar línea" disabled>
                                <i class="fa fa-copy"></i>
                            </button>
                            <button type="button" class="btn btn-warning btn-sm btn-action btn-eliminar" title="Eliminar registro" disabled>
                                <i class="fa fa-trash"></i>
                            </button>
                        </div>
                    ';
                    $sub_array[] = '-';
                }
                
                $data[] = $sub_array;
            }

            $results = array(
                "sEcho" => 1,
                "iTotalRecords" => count($data),
                "iTotalDisplayRecords" => count($data),
                "aaData" => $data
            );
            echo json_encode($results);
        break;
    
    }

        

?>
