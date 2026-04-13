<?php
    require_once("../config/conexionserver.php");
    require_once("../models/Documento.php");
    $documento = new Documento();

    switch($_GET["op"]){

        case "insert_doc":
        
            $direccion = $_POST["direccion"];

            if (strpos($direccion, ",") !== false) {
                    
                $direccion = explode(",", $direccion);

                $documento->insert_doc($_POST["idTipo"],$_POST["consecutivo"],$_POST["nit"],$direccion[0], $_SESSION["Id_Usuario"]);
                
            }
                                   
        break;
        
        case "insert_detalle":          

            $documento->insert_detalle($_POST["tipo"],$_POST["numdoc"],$_POST["nit1"], $_POST["seq"], $_POST["idproducto"], $_POST["cantidad"]);        
                                   
        break;

        case "guardar_doc":          

            $documento->update_doc($_POST["tipo"],$_POST["numdoc"],$_POST["notas"],$_POST["remision"]);        
                                   
        break;

        case "listar_detalle":
            $datos=$documento->listar_docdetalle_x_id($_POST["tipo"],$_POST["consecutivo"]);
            $data= Array();
            foreach($datos as $row){
                $sub_array = array();
                $sub_array[] = $row["IdProducto"];
                $sub_array[] = $row["Producto"];
                $sub_array[] = $row["Unidad"];
                $sub_array[] = round($row["Cantidad_Facturada"],2);
                /*$sub_array[] = number_format($row["Valor_Unitario"]);
                $sub_array[] = $row["Numero_Lote"];*/
                $sub_array[] = '<button type="button" onClick="eliminar('.$_POST["tipo"].','.$_POST["consecutivo"].','.$row["IdProducto"].');"  id="'.$_POST["tipo"].','.$_POST["consecutivo"].','.$row["IdProducto"].'" class="btn btn-inline btn-danger btn-sm ladda-button"><i class="fa fa-trash"></i></button>';
                $data[] = $sub_array;
            }

            $results = array(
                "sEcho"=>1,
                "iTotalRecords"=>count($data),
                "iTotalDisplayRecords"=>count($data),
                "aaData"=>$data);
            echo json_encode($results);
        break;
    
        case "mostrar":
                $datos=$documento->listar_doc_x_id($_POST["tipo"],$_POST["consecutivo"]);  
                if(is_array($datos)==true and count($datos)>0){
                    foreach($datos as $row)
                    {
                        $output["tipo"] = $row["tipo"];
                        $output["TipoDoctos"] = $row["TipoDoctos"];
                        $output["Numero_documento"] = $row["Numero_documento"];
                        $output["nit_Cedula"] = $row["nit_Cedula"];
                        $output["Nombre_Cliente"] = $row["Nombre_Cliente"];
                        $output["codigo_direccion"] = $row["codigo_direccion"];
                        $output["direccion"] = $row["direccion"];
                        $output["telefono_1"] = $row["telefono_1"];
                    }
                    echo json_encode($output);
    
                }   
        break;

        case "consultar_seq":
       
            $datos=$documento->get_seq_doc($_POST["tipo"],$_POST["consecutivo"]);  
               if(is_array($datos)==true and count($datos)>0){
                    foreach($datos as $row){
                    $output["seq"] = $row["seq"];
                    }
                    echo json_encode($output);
                }     
              
        break;

        
        case "eliminar":
                $documento->delete_id($_POST["tipo"], $_POST["consecutivo"],  $_POST["producto"], $_POST["seq"]);
        break;

        case "eliminar_masivo":
            $tipo        = $_POST["tipo"]        ?? '';
            $consecutivo = $_POST["consecutivo"] ?? '';
            $seqs        = $_POST["seqs"]        ?? '';
            $productos   = $_POST["productos"]   ?? '';
            if ($tipo && $consecutivo && $seqs) {
                echo $documento->delete_masivo($tipo, $consecutivo, $seqs, $productos);
            } else {
                echo "error: parámetros incompletos";
            }
        break;

        /*** ENTRADAS ***/  

        case "insert_doc_entrada":
            
            if($_POST["docref"] == 0 ){
                $resultado = $documento->insert_doc_entrada($_POST["idTipo"],$_POST["numero"], $_SESSION["Id_Usuario"]);
                echo $resultado;
            }else{            
                $resultado = $documento->insert_entrada_traslado($_POST["idTipo"],$_POST["numero"], $_POST["tipoDocRef"], $_SESSION["Id_Usuario"]);
                echo $resultado;
            }
                                
        break;

        case "listar_entradas":
            $datos=$documento->listar_entradas_x_usuario($_SESSION["Id_Usuario"]);
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

                $sub_array[] = '<a href="../Entradas/?tipo='.$row["tipo"].'&consecutivo='.$row["Numero_documento"].'" 
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

        case "mostrar_entrada":
            $datos=$documento->listar_doc_x_id($_POST["tipo"],$_POST["consecutivo"]);  
            if(is_array($datos)==true and count($datos)>0){
                foreach($datos as $row)
                {
                    $output["tipo"] = $row["tipo"];
                    $output["TipoDoctos"] = $row["TipoDoctos"];
                    $output["Numero_documento"] = $row["Numero_documento"];
                    $output["Numero_Docto_Base"] = $row["Numero_Docto_Base"];
                    $output["Tipo_Docto_Base_2"] = $row["Tipo_Docto_Base_2"];
                    $output["Numero_Docto_Base_2"] = $row["Numero_Docto_Base_2"];
                    $output["nit_Cedula"] = $row["nit_Cedula"];
                    $output["Nombre_Cliente"] = $row["Nombre_Cliente"];
                    $output["codigo_direccion"] = $row["codigo_direccion"];
                    $output["direccion"] = $row["direccion"];
                    $output["telefono_1"] = $row["telefono_1"];
                    $output["nit_Cedula_2"] = $row["nit_Cedula_2"];
                    $output["nombre2"] = $row["nombre2"];
                    $output["codigo_direccion_2"] = $row["codigo_direccion_2"];
                    $output["direccion2"] = $row["direccion2"];
                    $output["notas"] = $row["notas"];
                    $output["exportado"] = $row["exportado"];
                    $output["IdVendedor"] = $row["IdVendedor"];
                    $output["Fecha_Hora_Factura"] = $row["Fecha_Hora_Factura"] ? date_format($row["Fecha_Hora_Factura"], "Y-m-d") : date("Y-m-d");
                    $output["IdTransportador"] = $row["IdTransportador"];
                    $output["IdVehiculo"] = $row["IdVehiculo"];
                }
                echo json_encode($output);

            }
        break;

        case "total_entrada":
            $datos=$documento->total_entrada($_POST["tipo"],$_POST["consecutivo"]);  
            $output = array("total" => "0");
            if(is_array($datos)==true and count($datos)>0){
                foreach($datos as $row)
                {
                    $output["total"] = number_format($row["total"]);
                }
            }
            echo json_encode($output);
        break;

        case "totales":
            $datos=$documento->totales($_POST["tipo"],$_POST["consecutivo"]);  
            $output = array(
                "valorTotal" => "0",
                "totalImpuesto" => "0",
                "totalDescuento" => "0"
            );
            if(is_array($datos)==true and count($datos)>0){
                foreach($datos as $row)
                {
                    $output["valorTotal"] = number_format($row["valor_total"]);
                    $output["totalImpuesto"] = number_format($row["Valor_impuesto"]);
                    $output["totalDescuento"] = number_format($row["descuento_1"]);
                }
            }   
            echo json_encode($output);
        break;

        case "total_cantidad":
            $datos=$documento->total_cantidad($_POST["tipo"],$_POST["consecutivo"]);  
            $output = array("totalCantidad" => "0");
            if(is_array($datos)==true and count($datos)>0){
                foreach($datos as $row)
                {
                    $output["totalCantidad"] = number_format($row["totalCantidad"]);
                }
            }   
            echo json_encode($output);
        break;

        case "listar_detalle_entrada":
            $datos = $documento->listar_docdetalle_x_id($_POST["tipo"], $_POST["consecutivo"]);
            $data = Array();
            
            foreach($datos as $row) {
                $sub_array = array();
                $sub_array[] = $row["seq"];
                $sub_array[] = $row["IdProducto"];
                $sub_array[] = $row["Producto"];
                $sub_array[] = $row["Unidad"];
                $sub_array[] = number_format($row["Cantidad_Facturada"], 2);
                $sub_array[] = number_format($row["Porcentaje_Descuento_1"], 2);
                $sub_array[] = number_format($row["Porcentaje_Impuesto"], 2);
                $sub_array[] = number_format($row["Valor_Unitario"], 2);
                $sub_array[] = $row["Numero_Lote"];
                $sub_array[] = $row["Fecha_Vence"] ? date_format($row["Fecha_Vence"], "d/m/Y") : '';
                $sub_array[] = $row["Nota_Linea"];
                $sub_array[] = $row["Unidades"];

                if($row["exportado"] == 'N') {
                    $sub_array[] = '
                        <div class="edit-actions">
                            <button type="button" class="btn btn-info btn-sm btn-action btn-duplicar" title="Duplicar línea">
                                <i class="fa fa-copy"></i>
                            </button>
                            <button type="button" class="btn btn-warning btn-sm btn-action btn-eliminar" title="Eliminar registro">
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

        case "mostrarXproducto":
            $datos=$documento->listar_prod_x_doc($_POST["tipo"],$_POST["consecutivo"], $_POST["producto"]);  
            if(is_array($datos)==true and count($datos)>0){
                foreach($datos as $row)
                {
                    $output["tipo"] = $row["tipo"];
                    $output["Numero_Documento"] = $row["Numero_Documento"];
                    $output["IdProducto"] = $row["IdProducto"];
                    $output["Cantidad_Facturada"] = round($row["Cantidad_Facturada"],2);
                    $output["Valor_Unitario"] = round($row["Valor_Unitario"],2);
                    $output["Numero_Lote"] = $row["Numero_Lote"];
                    $output["Fecha_Vence"] = date_format($row["Fecha_Vence"],"Y-m-d");
                }
                echo json_encode($output);

            }   
        break;

        // case "update_prod_doc":
            
        //     $documento->update_prod_doc($_POST["tipo"],$_POST["numdoc"],$_POST["idproducto"], $_POST["cantidad"],$_POST["Valor_Unitario"],$_POST["lote"],$_POST["fecha_vence"]);      
                                
        // break;

        case "update_prod_doc":
            // Obtener parámetros básicos
            $tipo = $_POST["tipo"];
            $consecutivo = $_POST["consecutivo"];
            $producto = $_POST["producto"];
            $seq = isset($_POST["seq"]) ? $_POST["seq"] : null;
            
            // Parámetros opcionales para edición inline
            $cantidad = isset($_POST["cantidad"]) ? $_POST["cantidad"] : null;
            $valor_unitario = isset($_POST["valor"]) ? $_POST["valor"] : null;
            $lote = isset($_POST["lote"]) ? $_POST["lote"] : null;
            $fecha_vence = isset($_POST["fecha_vence"]) ? $_POST["fecha_vence"] : null;
            $descuento = isset($_POST["descuento"]) ? $_POST["descuento"] : null;
            $nota = isset($_POST["nota"]) ? $_POST["nota"] : null;
            $unidades = isset($_POST["unidades"]) ? $_POST["unidades"] : null;
            
            if ($seq === null) {
                echo "error: seq requerido";
                exit();
            }
            
            $resultado = $documento->update_prod_doc(
                $tipo, 
                $consecutivo, 
                $producto,
                $seq, 
                $cantidad, 
                $valor_unitario, 
                $lote, 
                $fecha_vence,
                $descuento,
                $nota,
                $unidades
            );
            
            if ($resultado) {
                echo "success";
            } else {
                echo "error";
            }
            exit(); 
        break;

        case "guardar_entrada":

            $direccion = $_POST["direccion3"];

            if (strpos($direccion, ",") !== false) {

                $direccion = explode(",", $direccion);

                $idTransportador = (isset($_POST["idTransportador"]) && $_POST["idTransportador"] !== '') ? $_POST["idTransportador"] : '1';
                $idVehiculo      = (isset($_POST["idVehiculo"])      && $_POST["idVehiculo"]      !== '') ? $_POST["idVehiculo"]      : '1';

                $documento->save_entrada($_POST["tipo"],$_POST["numdoc"],$_POST["notas"],$_POST["remision"],$_POST["nit3"],$_POST["nombre3"],$direccion[0],$_POST["telefono3"],$_POST["traslfact1"],$idTransportador,$idVehiculo);
            }
                                   
        break;

        case "asignar_selecc":
            $documento->update_lote($_POST["tipo"], $_POST["numdoc"], $_POST['id'], $_POST["lote1"]);            
        break;

        case "update_doc_ref":
            $registros = json_decode($_POST["registros"]); // Decodificar el JSON primero
            $documento->update_doc_ref($registros);
        break;

        case "update_doc_ref1":
            $documento->update_doc_ref($_POST["idTipo"], $_POST["consecutivo"], $_POST['numero']);            
        break;

        // case "update_lote_nota":
        //     $registros = json_decode($_POST["registros"]); // Decodificar el JSON primero
        //     $documento->update_lote_nota($registros);
        // break;

        case "update_lote_nota":
            // Decodificar los datos recibidos
            $lineas = isset($_POST["lineas"]) ? json_decode($_POST["lineas"]) : array();
            $notaGeneral = isset($_POST["notaGeneral"]) ? $_POST["notaGeneral"] : '';
            $idTipo = isset($_POST["idTipo"]) ? $_POST["idTipo"] : '';
            $numdoc = isset($_POST["numdoc"]) ? $_POST["numdoc"] : '';
            
            $documento->update_lote_nota($lineas, $notaGeneral, $idTipo, $numdoc);
        break;

        case "update_fecha":
            $documento->update_fecha($_POST["fecha_factura"], $_POST["ids_seleccionados"]);            
        break;

        case "listar_documentos_fecha":

            $datos=$documento->listar_documentos_fecha();
            $data= Array();
            foreach($datos as $row){
                $sub_array = array();
                $sub_array[] = $row["tipo"];
                $sub_array[] = $row["Numero_documento"];
                $sub_array[] = $row["Numero_Docto_Base_2"];
                $sub_array[] = $row["notas"];
                $sub_array[] = $row["usuario"];
                $sub_array[] = date_format($row["Fecha_Hora_Factura"],"Y-m-d");
                $sub_array[] = '<input type="checkbox" id="'.$row["tipo"].'" name="id[]" value="'.$row["tipo"].'">';
                $data[] = $sub_array;
            }

            $results = array(
                "sEcho"=>1,
                "iTotalRecords"=>count($data),
                "iTotalDisplayRecords"=>count($data),
                "aaData"=>$data);
            echo json_encode($results);
        break;

        case "duplicar_linea":
        error_log("🚀 Iniciando duplicar_linea desde controller");
        error_log("📦 POST data: " . print_r($_POST, true));
        
        if (!isset($_POST["tipo"]) || !isset($_POST["consecutivo"]) || 
            !isset($_POST["producto"]) || !isset($_POST["seq"])) {
            error_log("❌ Faltan parámetros");
            echo json_encode([
                "status" => "error",
                "message" => "Faltan parámetros requeridos"
            ]);
            break;
        }
        
        $tipo = $_POST["tipo"];
        $consecutivo = $_POST["consecutivo"];
        $producto = $_POST["producto"];
        $seq = $_POST["seq"];
        
        error_log("📋 Llamando a duplicar_linea con: tipo=$tipo, consecutivo=$consecutivo, producto=$producto, seq=$seq");
        
        $resultado = $documento->duplicar_linea($tipo, $consecutivo, $producto, $seq);
        
        error_log("📊 Resultado de duplicar_linea: " . ($resultado ? "TRUE" : "FALSE"));
        
        if ($resultado) {
            echo json_encode([
                "status" => "success",
                "message" => "Línea duplicada correctamente"
            ]);
        } else {
            echo json_encode([
                "status" => "error",
                "message" => "No se pudo duplicar la línea. Revise los logs del servidor."
            ]);
        }
        
        break;

        case "combo_transportador":
            echo $documento->combo_transportador();
        break;

        case "combo_vehiculo":
            echo $documento->combo_vehiculo();
        break;

    }

?>
