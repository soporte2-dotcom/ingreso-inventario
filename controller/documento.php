<?php
    require_once("../config/conexionserver.php");
    require_once("../config/session_guard.php");
    verificar_sesion_activa();
    require_once("../models/Documento.php");
    $documento = new Documento();

    switch($_GET["op"]){

        case "insert_doc":

            $direccion = $_POST["direccion"];

            if (strpos($direccion, ",") !== false) {

                $direccion = explode(",", $direccion);

                echo $documento->insert_doc($_POST["idTipo"],$_POST["nit"],$direccion[0], $_SESSION["Id_Usuario"]);

            } else {
                echo json_encode(["status" => "error", "message" => "Dirección inválida"]);
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
                $sub_array[] = round($row["Cantidad_Facturada"],3);
                if ($row["exportado"] === 'S' || ($row["anulado"] ?? 'N') === 'S') {
                    $sub_array[] = '<button type="button" class="btn btn-inline btn-danger btn-sm btn-eliminar" title="El documento ya está guardado, no se puede eliminar" disabled><i class="fa fa-trash"></i></button>';
                    $sub_array[] = '-';
                } else {
                    $sub_array[] = '<button type="button" class="btn btn-inline btn-danger btn-sm btn-eliminar" data-seq="'.$row["seq"].'" data-producto="'.$row["IdProducto"].'" title="Eliminar registro"><i class="fa fa-trash"></i></button>';
                    $sub_array[] = '<input type="checkbox" class="chk-seleccionar" data-seq="'.$row["seq"].'" data-producto="'.$row["IdProducto"].'">';
                }
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
                        $output["notas"] = $row["notas"];
                        $output["exportado"] = $row["exportado"];
                        $output["anulado"] = $row["anulado"];
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

        case "validar_excel_inventario":
            // Nota: si el archivo supera post_max_size, PHP entrega $_FILES/$_POST vacíos;
            // el isset() de abajo lo captura y devolvemos un mensaje claro de "muy grande".
            if (!isset($_FILES['archivo'])) {
                echo json_encode(['status' => 'error', 'message' => 'No se recibió el archivo. Puede que supere el tamaño máximo permitido por el servidor. Intenta con un archivo más pequeño.']);
                break;
            }
            if ($_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
                $erroresSubida = [
                    UPLOAD_ERR_INI_SIZE   => 'El archivo excede el tamaño máximo permitido por el servidor.',
                    UPLOAD_ERR_FORM_SIZE  => 'El archivo excede el tamaño máximo permitido.',
                    UPLOAD_ERR_PARTIAL    => 'El archivo se subió de forma incompleta. Vuelve a intentarlo.',
                    UPLOAD_ERR_NO_FILE    => 'No se seleccionó ningún archivo.',
                    UPLOAD_ERR_NO_TMP_DIR => 'Error temporal del servidor. Reintenta en un momento.',
                    UPLOAD_ERR_CANT_WRITE => 'Error temporal del servidor. Reintenta en un momento.',
                ];
                $msg = $erroresSubida[$_FILES['archivo']['error']] ?? 'Hubo un error al subir el archivo. Vuelve a intentarlo.';
                echo json_encode(['status' => 'error', 'message' => $msg]);
                break;
            }
            if ($_FILES['archivo']['size'] > Documento::EXCEL_MAX_BYTES) {
                echo json_encode(['status' => 'error', 'message' => 'El archivo supera el tamaño máximo permitido (8 MB). Divide la carga en archivos más pequeños.']);
                break;
            }
            $tmpPath  = $_FILES['archivo']['tmp_name'];
            $origName = strtolower($_FILES['archivo']['name']);
            if (pathinfo($origName, PATHINFO_EXTENSION) !== 'xlsx') {
                echo json_encode(['status' => 'error', 'message' => 'Solo se aceptan archivos .xlsx']);
                break;
            }
            echo $documento->validar_excel_inventario($tmpPath);
        break;

        case "confirmar_excel_inventario":
            $validos = isset($_POST['validos']) ? json_decode($_POST['validos'], true) : [];
            echo $documento->confirmar_masiva_excel_inventario(
                $_POST['tipo']   ?? '',
                $_POST['numdoc'] ?? '',
                $validos,
                $_POST['token']  ?? '',
                $_SESSION['Id_Usuario'] ?? ''
            );
        break;

        case "descargar_plantilla_inventario":
            $tmpFile = $documento->generar_plantilla_excel_inventario();
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="plantilla_inventario.xlsx"');
            header('Content-Length: ' . filesize($tmpFile));
            readfile($tmpFile);
            unlink($tmpFile);
        break;

        case "validar_excel_pedidos":
            // Nota: si el archivo supera post_max_size, PHP entrega $_FILES/$_POST vacíos;
            // el isset() de abajo lo captura y devolvemos un mensaje claro de "muy grande".
            if (!isset($_FILES['archivo'])) {
                echo json_encode(['status' => 'error', 'message' => 'No se recibió el archivo. Puede que supere el tamaño máximo permitido por el servidor. Intenta con un archivo más pequeño.']);
                break;
            }
            if ($_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
                $erroresSubida = [
                    UPLOAD_ERR_INI_SIZE   => 'El archivo excede el tamaño máximo permitido por el servidor.',
                    UPLOAD_ERR_FORM_SIZE  => 'El archivo excede el tamaño máximo permitido.',
                    UPLOAD_ERR_PARTIAL    => 'El archivo se subió de forma incompleta. Vuelve a intentarlo.',
                    UPLOAD_ERR_NO_FILE    => 'No se seleccionó ningún archivo.',
                    UPLOAD_ERR_NO_TMP_DIR => 'Error temporal del servidor. Reintenta en un momento.',
                    UPLOAD_ERR_CANT_WRITE => 'Error temporal del servidor. Reintenta en un momento.',
                ];
                $msg = $erroresSubida[$_FILES['archivo']['error']] ?? 'Hubo un error al subir el archivo. Vuelve a intentarlo.';
                echo json_encode(['status' => 'error', 'message' => $msg]);
                break;
            }
            if ($_FILES['archivo']['size'] > Documento::EXCEL_MAX_BYTES) {
                echo json_encode(['status' => 'error', 'message' => 'El archivo supera el tamaño máximo permitido (8 MB). Divide la carga en archivos más pequeños.']);
                break;
            }
            $tmpPath  = $_FILES['archivo']['tmp_name'];
            $origName = strtolower($_FILES['archivo']['name']);
            if (pathinfo($origName, PATHINFO_EXTENSION) !== 'xlsx') {
                echo json_encode(['status' => 'error', 'message' => 'Solo se aceptan archivos .xlsx']);
                break;
            }
            echo $documento->validar_excel_pedidos($tmpPath);
        break;

        case "confirmar_excel_pedidos":
            $validos = isset($_POST['validos']) ? json_decode($_POST['validos'], true) : [];
            echo $documento->confirmar_masiva_excel_pedidos(
                $_POST['tipo']   ?? '',
                $_POST['numdoc'] ?? '',
                $validos,
                $_POST['token']  ?? '',
                $_SESSION['Id_Usuario'] ?? ''
            );
        break;

        case "descargar_plantilla_pedidos":
            $tmpFile = $documento->generar_plantilla_excel_pedidos();
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="plantilla_pedidos.xlsx"');
            header('Content-Length: ' . filesize($tmpFile));
            readfile($tmpFile);
            unlink($tmpFile);
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

        case "preview_doc_entrada":
            echo $documento->preview_doc_entrada($_POST["idTipo"], $_POST["numero"]);
        break;

        case "insert_doc_entrada":

            if($_POST["docref"] == 0 ){
                $resultado = $documento->insert_doc_entrada($_POST["idTipo"],$_POST["numero"], $_SESSION["Id_Usuario"]);
                echo $resultado;
            }else{
                $resultado = $documento->insert_entrada_traslado($_POST["idTipo"],$_POST["numero"], $_POST["tipoDocRef"], $_SESSION["Id_Usuario"]);
                echo $resultado;
            }

        break;

        case "insert_linea_entrada_manual":
            $resultado = $documento->insert_linea_entrada_manual(
                $_POST["tipo"],
                $_POST["consecutivo"],
                $_POST["idproducto"],
                $_POST["cantidad"],
                $_POST["valorUnitario"] ?? 0,
                $_POST["lote"] ?? '0',
                $_POST["porcentaje_impuesto"] ?? 0
            );
            echo $resultado;
        break;

        case "insert_doc_entrada_manual":
            $nit1 = $_POST["nit1"] ?? '';
            $dir1 = $_POST["dir1"] ?? '';
            if (strpos($dir1, ",") !== false) $dir1 = explode(",", $dir1)[0];
            $nit2 = $_POST["nit2"] ?? '';
            $dir2 = $_POST["dir2"] ?? '';
            if (strpos($dir2, ",") !== false) $dir2 = explode(",", $dir2)[0];
            $resultado = $documento->insert_doc_entrada_manual($_POST["idTipo"], $nit1, $dir1, $nit2, $dir2, $_SESSION["Id_Usuario"]);
            echo $resultado;
        break;

        case "listar_entradas":
            require_once("../models/mdlPermisos.php");
            $permisos = new Permisos();
            $tipos_permitidos_rows = $permisos->get_tipos_documento_permitidos($_SESSION["Id_Usuario"]);
            $tipos_permitidos = array_column($tipos_permitidos_rows, 'idTipoDoctos');

            $tipoSolicitado = $_POST['tipo'] ?? '';
            if ($tipoSolicitado !== '' && !in_array($tipoSolicitado, $tipos_permitidos)) {
                // No tiene permiso sobre ese tipo puntual: se ignora y se usan todos los permitidos.
                $tipoSolicitado = '';
            }

            $datos = $documento->listar_entradas_filtro(
                $tipos_permitidos,
                $tipoSolicitado,
                $_POST['fechaDesde'] ?? '',
                $_POST['fechaHasta'] ?? '',
                $_POST['numDesde']   ?? '',
                $_POST['numHasta']   ?? '',
                $_POST['exportado']  ?? '',
                $_POST['anulado']    ?? ''
            );
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

                if(($row["anulado"] ?? 'N') == "S"){
                    $sub_array[] = '<span class="label label-danger">Sí</span>';
                } else {
                    $sub_array[] = '<span class="label label-default">No</span>';
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

        case "listar_inventario":
            require_once("../models/mdlPermisos.php");
            $permisos = new Permisos();
            $tipos_permitidos_rows = $permisos->get_tipos_documento_inventario_permitidos($_SESSION["Id_Usuario"]);
            $tipos_permitidos = array_column($tipos_permitidos_rows, 'idTipoDoctos');

            $tipoSolicitado = $_POST['tipo'] ?? '';
            if ($tipoSolicitado !== '' && !in_array($tipoSolicitado, $tipos_permitidos)) {
                $tipoSolicitado = '';
            }

            $datos = $documento->listar_inventario_filtro(
                $tipos_permitidos,
                $tipoSolicitado,
                $_POST['fechaDesde'] ?? '',
                $_POST['fechaHasta'] ?? '',
                $_POST['numDesde']   ?? '',
                $_POST['numHasta']   ?? '',
                $_POST['exportado']  ?? '',
                $_POST['anulado']    ?? ''
            );
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

                if(($row["anulado"] ?? 'N') == "S"){
                    $sub_array[] = '<span class="label label-danger">Sí</span>';
                } else {
                    $sub_array[] = '<span class="label label-default">No</span>';
                }

                $sub_array[] = '<a href="../NuevoDoc/index.php?tipo='.$row["tipo"].'&consecutivo='.$row["Numero_documento"].'"
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

        case "listar_pedidos":
            require_once("../models/mdlPermisos.php");
            $permisos = new Permisos();
            $tipos_permitidos_rows = $permisos->get_tipos_documento_pedidos_permitidos($_SESSION["Id_Usuario"]);
            $tipos_permitidos = array_column($tipos_permitidos_rows, 'idTipoDoctos');

            $tipoSolicitado = $_POST['tipo'] ?? '';
            if ($tipoSolicitado !== '' && !in_array($tipoSolicitado, $tipos_permitidos)) {
                $tipoSolicitado = '';
            }

            $datos = $documento->listar_pedidos_filtro(
                $tipos_permitidos,
                $tipoSolicitado,
                $_POST['fechaDesde'] ?? '',
                $_POST['fechaHasta'] ?? '',
                $_POST['numDesde']   ?? '',
                $_POST['numHasta']   ?? '',
                $_POST['exportado']  ?? '',
                $_POST['anulado']    ?? ''
            );
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

                if(($row["anulado"] ?? 'N') == "S"){
                    $sub_array[] = '<span class="label label-danger">Sí</span>';
                } else {
                    $sub_array[] = '<span class="label label-default">No</span>';
                }

                $sub_array[] = '<a href="../Pedidos/index.php?tipo='.$row["tipo"].'&consecutivo='.$row["Numero_documento"].'"
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
                    $output["Prefijo"] = trim($row["Prefijo"] ?? '');
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
                    $output["anulado"] = $row["anulado"];
                    $output["IdVendedor"] = $row["IdVendedor"];
                    $output["Fecha_Hora_Factura"] = $row["Fecha_Hora_Factura"] ? date_format($row["Fecha_Hora_Factura"], "Y-m-d") : date("Y-m-d");
                    $output["IdTransportador"] = $row["IdTransportador"];
                    $output["IdVehiculo"] = $row["IdVehiculo"];
                    $output["NroOcTercero"] = trim($row["DescuentoOrdenVenta"] ?? '');
                    $output["RespuestaCorrectaDian"] = $row["RespuestaCorrectaDian"];
                    $output["NombreBodega"]   = $row["NombreBodega"]   ?? '';
                    $output["NombreVendedor"] = $row["NombreVendedor"] ?? '';
                    $output["ciudad"]         = $row["ciudad"]         ?? '';
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

            require_once("../models/mdlPermisos.php");
            $permisosListaEntrada = new Permisos();
            $permiteActualizarProdEntrada = $permisosListaEntrada->tiene_permiso_especial($_SESSION["Id_Usuario"], 'actualizar_producto_entrada');
            $btnActualizarProd = $permiteActualizarProdEntrada
                ? '<button type="button" class="btn btn-secondary btn-sm btn-action btn-actualizar-producto" title="Actualizar %IVA y Valor desde el producto"><i class="fa fa-refresh"></i></button>'
                : '';
            $btnActualizarProdDisabled = $permiteActualizarProdEntrada
                ? '<button type="button" class="btn btn-secondary btn-sm btn-action btn-actualizar-producto" title="Actualizar %IVA y Valor desde el producto" disabled><i class="fa fa-refresh"></i></button>'
                : '';

            foreach($datos as $row) {
                $sub_array = array();
                $sub_array[] = $row["seq"];
                $sub_array[] = $row["IdProducto"];
                $sub_array[] = $row["Producto"];
                $sub_array[] = $row["Unidad"];
                $sub_array[] = number_format($row["Cantidad_Facturada"], 3);
                $sub_array[] = number_format($row["Porcentaje_Descuento_1"], 2);
                $sub_array[] = number_format($row["Porcentaje_Impuesto"], 2);
                $sub_array[] = number_format($row["Valor_Unitario"], 2);
                $sub_array[] = $row["Numero_Lote"];
                $sub_array[] = $row["Fecha_Vence"] ? date_format($row["Fecha_Vence"], "d/m/Y") : '';
                $sub_array[] = $row["Nota_Linea"];
                $sub_array[] = $row["Unidades"];

                if($row["exportado"] == 'N' && ($row["anulado"] ?? 'N') != 'S') {
                    $sub_array[] = '
                        <div class="edit-actions">
                            <button type="button" class="btn btn-success btn-sm btn-action btn-imprimir-etiqueta" title="Imprimir Etiqueta">
                                <i class="fa fa-tag"></i>
                            </button>
                            <button type="button" class="btn btn-info btn-sm btn-action btn-duplicar" title="Duplicar línea">
                                <i class="fa fa-copy"></i>
                            </button>
                            <button type="button" class="btn btn-warning btn-sm btn-action btn-eliminar" title="Eliminar registro">
                                <i class="fa fa-trash"></i>
                            </button>
                            ' . $btnActualizarProd . '
                        </div>
                    ';
                    $sub_array[] = '<input type="checkbox" id="' . $row["IdProducto"] . '" name="id[]" value="' . $row["IdProducto"] . '">';
                } else {
                    $sub_array[] = '
                        <div class="edit-actions">
                            <button type="button" class="btn btn-success btn-sm btn-action btn-imprimir-etiqueta" title="Imprimir Etiqueta">
                                <i class="fa fa-tag"></i>
                            </button>
                            <button type="button" class="btn btn-info btn-sm btn-action btn-duplicar" title="Duplicar línea" disabled>
                                <i class="fa fa-copy"></i>
                            </button>
                            <button type="button" class="btn btn-warning btn-sm btn-action btn-eliminar" title="Eliminar registro" disabled>
                                <i class="fa fa-trash"></i>
                            </button>
                            ' . $btnActualizarProdDisabled . '
                        </div>
                    ';
                    $sub_array[] = '-';
                }

                $sub_array[] = $row["IdGrupoProducto"] ?? ''; // columna oculta, usada para validar Dotación/EPP en Salidas

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
                    $output["Cantidad_Facturada"] = round($row["Cantidad_Facturada"],3);
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

            header('Content-Type: application/json');
            if (is_array($resultado)) {
                echo json_encode($resultado);
            } else {
                echo json_encode($resultado
                    ? ['status' => 'success']
                    : ['status' => 'error', 'message' => 'Error al actualizar']);
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

        case "actualizar_producto_linea":
            require_once("../models/mdlPermisos.php");
            $permisosActualizarProd = new Permisos();
            if (!$permisosActualizarProd->tiene_permiso_especial($_SESSION["Id_Usuario"], 'actualizar_producto_entrada')) {
                echo json_encode(["status" => "error", "message" => "No tiene permiso para actualizar el producto de esta línea."]);
                break;
            }
            if (!isset($_POST["tipo"]) || !isset($_POST["consecutivo"]) ||
                !isset($_POST["producto"]) || !isset($_POST["seq"])) {
                echo json_encode(["status" => "error", "message" => "Faltan parámetros requeridos"]);
                break;
            }
            echo $documento->actualizar_producto_linea($_POST["tipo"], $_POST["consecutivo"], $_POST["producto"], $_POST["seq"]);
        break;

        case "combo_transportador":
            echo $documento->combo_transportador();
        break;

        case "combo_vehiculo":
            echo $documento->combo_vehiculo();
        break;

        case "reiniciar_doc_desde_pedido":
        case "reiniciar_doc_entrada":
            echo $documento->reiniciar_doc_entrada($_POST["tipo"], $_POST["numdoc"]);
        break;

        // Módulo Gestión de Documentos: requiere el permiso de módulo 'gestion_documentos'
        // ademas de la sesión activa (ya validada arriba).
        case "buscar_documento_gestion":
        case "desmarcar_documento":
        case "anular_documento":
            require_once("../models/mdlPermisos.php");
            $permisosGestion = new Permisos();
            if (!$permisosGestion->tiene_permiso_especial($_SESSION["Id_Usuario"], 'gestion_documentos')) {
                echo json_encode(["status" => "error", "message" => "No tiene permiso para acceder a este módulo."]);
                break;
            }

            $tipoGestion   = trim($_POST["tipo"] ?? '');
            $numeroGestion = trim($_POST["numero"] ?? '');
            if ($tipoGestion === '' || $numeroGestion === '') {
                echo json_encode(["status" => "error", "message" => "Debe indicar tipo y número de documento."]);
                break;
            }

            if ($_GET["op"] === "buscar_documento_gestion") {
                echo $documento->buscar_documento_gestion($tipoGestion, $numeroGestion);
            } elseif ($_GET["op"] === "desmarcar_documento") {
                echo $documento->desmarcar_documento($tipoGestion, $numeroGestion);
            } else {
                $motivoAnulacion = trim($_POST["motivo"] ?? '');
                if ($motivoAnulacion === '') {
                    echo json_encode(["status" => "error", "message" => "Debe indicar el motivo de la anulación."]);
                    break;
                }
                echo $documento->anular_documento($tipoGestion, $numeroGestion, $motivoAnulacion, $_SESSION["Id_Usuario"]);
            }
        break;

    }

?>
