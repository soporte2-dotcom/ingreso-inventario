<?php
    class Salidas extends Conectarserver{

      public function listar_salidas_x_usuario($usuario){
        $cn = new Conectarserver;
        $resultado = array();

        if($usuario == 'LAUREN' || $usuario == 'SA'){

            $sql="SELECT d.Fecha_Hora_Factura, d.tipo, tt.TipoDoctos, d.Numero_documento, d.Numero_Docto_Base, d.Tipo_Docto_Base, d.Tipo_Docto_Base_2, d.Numero_Docto_Base_2,
                d.nit_Cedula, d.Nombre_Cliente, d.codigo_direccion, td.direccion, td.telefono_1, d.exportado, d.usuario

                FROM Documentos d, Terceros_Dir td, TblTipoDoctos tt, TblTerceros t

                WHERE CONVERT(date, Fecha_Hora_Factura) > '2026/01/01' AND tt.tipo IN ('11', '2')
                AND tt.idTipoDoctos = d.tipo AND td.nit = d.nit_Cedula AND d.codigo_direccion = td.codigo_direccion
                AND t.nit_cedula = d.nit_Cedula
                ORDER BY d.Fecha_Hora_Factura DESC";

            $registros = sqlsrv_query($cn->getConecta(), $sql);

        } else {

            $sql="SELECT d.Fecha_Hora_Factura, d.tipo, tt.TipoDoctos, d.Numero_documento, d.Numero_Docto_Base, d.Tipo_Docto_Base, d.Tipo_Docto_Base_2, d.Numero_Docto_Base_2,
                d.nit_Cedula, d.Nombre_Cliente, d.codigo_direccion, td.direccion, td.telefono_1, d.exportado, d.usuario

                FROM Documentos d, Terceros_Dir td, TblTipoDoctos tt, TblTerceros t

                WHERE d.usuario = ? AND CONVERT(date, Fecha_Hora_Factura) > '2026/01/01' AND tt.tipo IN ('11', '2')
                AND tt.idTipoDoctos = d.tipo AND td.nit = d.nit_Cedula AND d.codigo_direccion = td.codigo_direccion
                AND t.nit_cedula = d.nit_Cedula
                ORDER BY d.Fecha_Hora_Factura DESC";

            $params = array($usuario);
            $registros = sqlsrv_query($cn->getConecta(), $sql, $params);
        }

        if($registros === false) {
            $this->registrar_error("Error en listar_entradas_x_usuario: " . print_r(sqlsrv_errors(), true));
            return $resultado;
        }

        while($stmt = sqlsrv_fetch_array($registros, SQLSRV_FETCH_ASSOC)) {
            $resultado[] = $stmt;
        }

        return $resultado;
      }
        

        public function listar_doc_x_id($tipo, $consecutivo){
            $cn = new Conectarserver;

            $sql="SELECT d.*, tt.TipoDoctos, td.direccion, td.telefono_1, t.nombre AS nombre2, td2.direccion AS direccion2
            FROM Documentos d, Terceros_Dir td, TblTipoDoctos tt, TblTerceros t, Terceros_Dir td2
            WHERE d.tipo = '$tipo' AND d.Numero_documento = '$consecutivo' AND tt.idTipoDoctos = d.tipo AND
            td.nit = d.nit_Cedula AND d.codigo_direccion = td.codigo_direccion AND
            td2.nit = d.nit_Cedula_2 AND d.codigo_direccion_2 = td2.codigo_direccion AND t.nit_cedula = d.nit_Cedula_2";

            $registros = sqlsrv_query($cn->getConecta(), $sql);
            if( $registros === false ){
                echo "Error al ejecutar consulta.\n";
            }  else {
                $resultado = array();
                while($stmt= sqlsrv_fetch_array($registros)) {
                    $resultado[] = $stmt;                   
                }
                return $resultado;
            }
        }
        
        
        public function listar_docdetalle_x_id($tipo, $consecutivo){
            $cn = new Conectarserver;
            $sql="SELECT d.tipo, d.Numero_Documento, d.seq, d.IdProducto, p.Producto, u.Unidad, d.Cantidad_Facturada, d.Porcentaje_Descuento_1, d.Valor_Unitario, d.Numero_Lote, d.Fecha_Vence, d.Nota_Linea, d.Unidades,  o.exportado
            FROM Documentos_Lin d, TblProducto p, TblUnidad u, Documentos o 
            WHERE d.IdProducto = p.IdProducto AND d.IdUnidad = u.idUnidad 
                AND  d.tipo = '$tipo' AND d.Numero_documento = '$consecutivo' 
                AND  o.tipo = d.tipo AND o.Numero_documento = d.Numero_documento
            ORDER BY d.seq ASC";
            $registros = sqlsrv_query($cn->getConecta(), $sql);
            if( $registros === false ){
                echo "Error al ejecutar consulta.\n";
            }  else {
                $resultado = array();
                while($stmt= sqlsrv_fetch_array($registros)) {
                    $resultado[] = $stmt;                   
                }
                return $resultado;
            }
        }

        public function get_seq_doc($tipo, $consecutivo){
            $cn = new Conectarserver;
            $sql="SELECT TOP(1) seq FROM Documentos_Lin WHERE tipo = '$tipo' AND Numero_documento = '$consecutivo'  ORDER BY seq DESC";
            $registros = sqlsrv_query($cn->getConecta(), $sql);
            if( $registros === false ){
                echo "Error al ejecutar consulta.\n";
            }  else {
                $resultado = array();
                while($stmt= sqlsrv_fetch_array($registros)) {
                    $resultado[] = $stmt;                   
                }
                return $resultado;
            }
        }

        /**
         * Obtiene el valor actual del consecutivo para un tipo de documento
         * @param string $tipo Tipo de documento
         * @return int Valor actual del consecutivo
         */
        private function obtener_consecutivo_actual($tipo) {
            $cn = new Conectarserver;
            $sql = "SELECT siguiente FROM Consecutivos WHERE tipo = ?";
            $params = array($tipo);
            $stmt = sqlsrv_query($cn->getConecta(), $sql, $params);
            
            if ($stmt === false) {
                return 0;
            }
            
            $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            return ($row) ? $row['siguiente'] : 0;
        }

        /**
         * Registra un mensaje de error en el archivo de log
         * @param string $mensaje Mensaje de error
         */
        private function registrar_error($mensaje) {
            $fecha = date('Y-m-d H:i:s');
            $log = "[$fecha] $mensaje" . PHP_EOL;
            
            // Modificar la ruta según tu estructura de directorios
            $archivo = dirname(__FILE__) . '/../logs/errores.log';
            
            // Intentar escribir en el archivo de log
            @file_put_contents($archivo, $log, FILE_APPEND);
        }

        public function insert_doc_entrada($tipo, $numero, $usuario){
            $cn = new Conectarserver;

            try {

                // Primero, validar que el número de pedido exista
                $sql_validar = "SELECT COUNT(*) AS existe FROM Documentos_Ped 
                WHERE numero_pedido = ? AND sw = '9'";

                $params = array($numero);
                $stmt = sqlsrv_query($cn->getConecta(), $sql_validar, $params);

                if ($stmt === false) {
                    throw new Exception("Error al validar el número de pedido: " . print_r(sqlsrv_errors(), true));
                }

                $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

                if (!$row || $row['existe'] == 0) {
                    // El pedido no existe, devolvemos un mensaje de error
                    return json_encode(array(
                        "status" => "error",
                        "message" => "El número de pedido '$numero' no existe"
                    ));
                }

                sqlsrv_begin_transaction($cn->getConecta()); // Iniciar la transacción

                // Obtener el siguiente consecutivo de forma segura
                $sql_num = "SELECT (siguiente + 1) AS numDoc FROM Consecutivos WHERE tipo = '$tipo'";
                $stmt_num = sqlsrv_query($cn->getConecta(), $sql_num);
                if ($stmt_num === false) {
                    throw new Exception("Error al obtener consecutivo: " . print_r(sqlsrv_errors(), true));
                }
                $row_num = sqlsrv_fetch_array($stmt_num, SQLSRV_FETCH_ASSOC);
                $numDoc = $row_num['numDoc'];

                $sql="INSERT INTO Documentos(sw, tipo, modelo, Numero_Documento, Numero_Docto_Base,
                nit_Cedula, codigo_direccion, Fecha_Hora_Factura,Fecha_Hora_Vencimiento,Fecha_orden_Venta,
                condicion,valor_total, valor_aplicado, Retencion_1,Retencion_2, Retencion_3, retencion_causada, retencion_iva,retencion_ica,
                retencion_descuento, descuento_pie, DescuentoOrdenVenta, descuento_1, descuento_2, descuento_3, costo, IdVendedor, anulado, usuario,
                notas,pc, fecha_hora, duracion, bodega, Valor_impuesto, Impuesto_Consumo, impuesto_deporte, concepto, vencimiento_presup, 
                exportado, prefijo, moneda, CentroDeCostosDoc, valor_mercancia, abono, Comision_Vendedor, Tasa_Moneda_Ext, Tomador, Tasa_Fija_o_Variable, Punto_FOB,
                Fletes_Moneda_Ext, Miselaneos_Moneda_Ext, Cargo_Por_Fletes, Impuesto_Por_Fletes, Total_Items, Nombre_Cliente, Ordenado_Por, Telefono_De_Envio_1,
                Telefono_De_Envio_2, Factura_Impresa, IdFormaEnvio, IdTransportador, nit_Cedula_2, codigo_direccion_2, Numero_Docto_Base_2, Tipo_Docto_Base, 
                Tipo_Docto_Base_2, IdActividadEconomica, TarifaReteFuenteCree, Valor_ReteCree, IdVehiculo)
                
                (SELECT td.tipo AS sw, '$tipo' AS tipo, '$tipo' AS modelo, $numDoc AS Numero_Documento, '' AS Numero_Docto_Base,
                dp.nit AS nit_Cedula, dp.direccion_factura AS codigo_direccion,  GETDATE() AS Fecha_Hora_Factura, GETDATE() AS Fecha_Hora_Vencimiento, GETDATE() AS Fecha_orden_Venta,
                t.condicion AS condicion, dp.valor_total AS valor_total, dp.valor_total AS valor_aplicado, dp.Retencion_1 AS Retencion_1, 0 AS Retencion_2, 0 AS Retencion_3, 
                0 AS retencion_causada, 0 AS retencion_iva, 0 AS retencion_ica, 0 AS retencion_descuento, 0 AS descuento_pie, 0 AS DescuentoOrdenVenta, 0 AS descuento_1, 0 AS descuento_2,
                0 AS descuento_3, 0 AS costo, dp.vendedor AS idVendedor, 'N' AS anulado, '$usuario' AS usuario, dp.notas AS notas, HOST_NAME() AS pc, GETDATE() AS fecha_hora, 
                0 AS duracion, td.IdBodega AS bodega, 0 AS Valor_impuesto, 0 AS Impuesto_Consumo, 0 AS impuesto_deporte, dp.concepto AS concepto, GETDATE() AS vencimiento_presup, 
                'N' AS exportado, '0' AS prefijo, dp.moneda AS moneda, 0 AS CentroDeCostosDoc, 0 AS valor_mercancia, 0 AS abono, 0 AS Comision_Vendedor, 
                1 AS Tasa_Moneda_Ext, '' AS Tomador, 'V' AS Tasa_Fija_o_Variable, dir.idLista AS Punto_FOB,
                0 AS Fletes_Moneda_Ext, 0 AS Miselaneos_Moneda_Ext, 0 AS Cargo_Por_Fletes, 0 AS Impuesto_Por_Fletes, 0 AS Total_Items, t.nombre AS Nombre_Cliente, 
                SUBSTRING(dp.Contacto_Compras,0,20) AS Ordenado_Por, dp.telefono1 AS Telefono_De_Envio_1, '' AS Telefono_De_Envio_2, 'N' AS Factura_Impresa, dp.IdFormaEnvio AS IdFormaEnvio, dp.IdTRansportador AS IdTransportador, 
                dp.nit_destino AS nit_Cedula_2, dp.direccion_entrega AS codigo_direccion_2, '$numero' AS Numero_Docto_Base_2, '0' AS Tipo_Docto_Base, 
                '9' AS Tipo_Docto_Base_2, '0' AS IdActividadEconomica, 0 AS TarifaReteFuenteCree, 0 AS Valor_ReteCree, '1' AS IdVehiculo           
                
                FROM Documentos_Ped dp, TblTerceros t, TblTipoDoctos td, Terceros_Dir dir
                WHERE td.idTipoDoctos = '$tipo' AND
                dp.nit = t.nit_cedula AND dir.codigo_direccion = dp.direccion_factura AND dir.nit = dp.nit AND
                dp.numero_pedido = '$numero' AND dp.sw = '9') ";

                $registros = sqlsrv_prepare($cn->getConecta(), $sql);            
                if(sqlsrv_execute($registros) === false) {
                    throw new Exception("Error al insertar documento: " . print_r(sqlsrv_errors(), true));
                }

                $sql1="INSERT INTO Documentos_Lin
                (sw, tipo, seq, modelo, Numero_Documento, Numero_Docto_Base, Numero_Lote, Nit_Cedula, Codigo_Direccion, Fecha_Documento,
                IdProducto, IdUnidad, Factor_Conversion, Cantidad_Facturada, Cantidad_Pendiente, Cantidad_Orden, Costo_Unitario, Valor_Unitario,
                Valor_Impuesto, Porcentaje_Impuesto, Porcentaje_Descuento_1, Porcentaje_Descuento_2,Porcentaje_Descuento_3, IdVendedor, Comision_Vendedor,
                Valor_Comision_Vendedor, IdBodega, Maneja_Inventario, Tomador, IdMoneda, Tasa_Moneda_Ext, CentroDeCostosDoc,
                Nota_Linea, Unidades, Fecha_Vence, Exportado, Costo_Unitario_Inicial,
                Porcentaje_ReteFuente, Envase, Numero_Lote_Destino, serial, Impuesto_Consumo, Porcentaje_ReteFuente_2,
                Porcentaje_ReteFuente_3, Porcentaje_ReteFuente_4, Emp_1, Emp_2, Emp_3, Emp_4, Emp_5, Emp_6,
                Emp_7, Emp_8, Tara_1, Tara_2, Tara_3, Tara_4, Tara_5, Tara_6, Tara_7, Tara_8)
                
                (SELECT td.tipo AS sw, '$tipo' AS tipo, dp.Linea AS seq, p.contable AS Modelo, $numDoc AS Numero_Documento,
                '' AS Numero_Docto_Base, '0' AS Numero_Lote, dp.IdCliente AS Nit_Cedula, dp.DireccionFactura AS codigo_direccion,  GETDATE() AS Fecha_Documento,
                dp.IdProducto AS IdProducto, dp.und AS IdUnidad, '1' AS Factor_Conversion,  dp.cantidad AS Cantidad_Facturada,
                0 AS Cantidad_Pendiente, dp.cantidad AS Cantidad_Orden, dp.valor_unitario AS Costo_Unitario, dp.valor_unitario AS Valor_Unitario, 
                ((ISNULL(dp.porcentaje_iva, 0)/100) * dp.valor_unitario) AS Valor_Impuesto, ISNULL(dp.porcentaje_iva, 0) AS Porcentaje_Impuesto, ISNULL(dp.porcentaje_descuento, 0) AS Porcentaje_Descuento_1,
                ISNULL(dp.porc_dcto_2, 0) AS Porcentaje_Descuento_2, ISNULL(dp.porc_dcto_3, 0) AS Porcentaje_Descuento_3, dp.IdVendedor AS IdVendedor, 0 AS Comision_Vendedor, 0 AS Valor_Comision_Vendedor,
                td.IdBodega AS IdBodega, 'S' AS Maneja_Inventario, '' AS Tomador, 1 AS IdMoneda, 1 AS Tasa_Moneda_Ext, '0' AS CentroDeCostosDoc,
                ' ' AS Nota_Linea, '1' AS Unidades, GETDATE() AS Fecha_Vence, 'N' AS Exportado, dp.valor_unitario AS Costo_Unitario_Inicial,
                dp.Porcentaje_ReteFuente AS Porcentaje_ReteFuente, 0 AS Envase, 0 AS Numero_Lote_Destino, '' AS serial, 0 AS Impuesto_Consumo, 0 AS Porcentaje_ReteFuente_2,
                0 AS Porcentaje_ReteFuente_3, 0 AS Porcentaje_ReteFuente_4, 0 AS Emp_1, 0 AS Emp_2, 0 AS Emp_3, 0 AS Emp_4, 0 AS Emp_5, 0 AS Emp_6,
                0 AS Emp_7, 0 AS Emp_8, 0 AS Tara_1, 0 AS Tara_2, 0 AS Tara_3, 0 AS Tara_4, 0 AS Tara_5, 0 AS Tara_6, 0 AS Tara_7, 0 AS Tara_8
                                                                
                FROM  Documentos_Lin_Ped dp, TblTipoDoctos td, TblProducto p
                WHERE td.idTipoDoctos = '$tipo' AND p.IdProducto = dp.IdProducto
                AND dp.numero_pedido = '$numero' AND dp.sw = 9)";
               
                $registros_lin =  sqlsrv_prepare($cn->getConecta(), $sql1);            
                if(sqlsrv_execute($registros_lin) === false) {
                    throw new Exception("Error al insertar detalle del documento: " . print_r(sqlsrv_errors(), true));
                }

                // Actualizar totales en cabecera como suma del detalle
                $sql_totales = "UPDATE Documentos SET 
                    Total_Items = (SELECT COUNT(*) FROM Documentos_Lin WHERE tipo = '$tipo' AND Numero_documento = $numDoc),
                    Valor_impuesto = (SELECT ISNULL(SUM(((dl.Cantidad_Facturada * dl.Valor_Unitario) * (1 - ISNULL(dl.Porcentaje_Descuento_1, 0) / 100)) * (ISNULL(dl.Porcentaje_Impuesto, 0) / 100)), 0) FROM Documentos_Lin dl WHERE dl.tipo = '$tipo' AND dl.Numero_documento = $numDoc),
                    valor_total = (SELECT ISNULL(SUM(ROUND((dl.Cantidad_Facturada * dl.Valor_Unitario) * (1 - ISNULL(dl.Porcentaje_Descuento_1, 0) / 100), 2) + ((dl.Cantidad_Facturada * dl.Valor_Unitario) * (1 - ISNULL(dl.Porcentaje_Descuento_1, 0) / 100)) * (ISNULL(dl.Porcentaje_Impuesto, 0) / 100)), 0) FROM Documentos_Lin dl WHERE dl.tipo = '$tipo' AND dl.Numero_documento = $numDoc),
                    valor_aplicado = (SELECT ISNULL(SUM(ROUND((dl.Cantidad_Facturada * dl.Valor_Unitario) * (1 - ISNULL(dl.Porcentaje_Descuento_1, 0) / 100), 2) + ((dl.Cantidad_Facturada * dl.Valor_Unitario) * (1 - ISNULL(dl.Porcentaje_Descuento_1, 0) / 100)) * (ISNULL(dl.Porcentaje_Impuesto, 0) / 100)), 0) FROM Documentos_Lin dl WHERE dl.tipo = '$tipo' AND dl.Numero_documento = $numDoc),
                    costo = (SELECT ISNULL(SUM(ROUND((dl.Cantidad_Facturada * dl.Valor_Unitario) * (1 - ISNULL(dl.Porcentaje_Descuento_1, 0) / 100), 2) + ((dl.Cantidad_Facturada * dl.Valor_Unitario) * (1 - ISNULL(dl.Porcentaje_Descuento_1, 0) / 100)) * (ISNULL(dl.Porcentaje_Impuesto, 0) / 100)), 0) FROM Documentos_Lin dl WHERE dl.tipo = '$tipo' AND dl.Numero_documento = $numDoc)
                    WHERE tipo = '$tipo' AND Numero_Documento = $numDoc";
                
                $registros_tot = sqlsrv_prepare($cn->getConecta(), $sql_totales);
                if(sqlsrv_execute($registros_tot) === false) {
                    throw new Exception("Error al actualizar totales en cabecera: " . print_r(sqlsrv_errors(), true));
                }

                $sql2="UPDATE Consecutivos SET siguiente = siguiente + 1 WHERE tipo = '$tipo'";
                $registros_con =  sqlsrv_prepare($cn->getConecta(), $sql2);
                if(sqlsrv_execute($registros_con) === false) {
                    throw new Exception("Error al actualizar consecutivo: " . print_r(sqlsrv_errors(), true));
                }

                // Marcar el pedido de origen como despachado
                $sql_despacho = "UPDATE Documentos_Ped SET despacho = 'S' WHERE numero_pedido = ? AND sw = '9'";
                $stmt_despacho = sqlsrv_prepare($cn->getConecta(), $sql_despacho, array($numero));
                if(sqlsrv_execute($stmt_despacho) === false) {
                    throw new Exception("Error al actualizar despacho del pedido: " . print_r(sqlsrv_errors(), true));
                }

                sqlsrv_commit($cn->getConecta()); // Confirmar la transacción si todo ha ido bien

                // Devolvemos un objeto JSON con un status de éxito
                return json_encode(array(
                    "status" => "success",
                    "message" => "Documento registrado correctamente",
                    "tipo" => $tipo,
                    "consecutivo" => $this->obtener_consecutivo_actual($tipo)
                ));

            }catch (Exception $e) {
                
                // Deshacer la transacción en caso de error
                if (isset($cn) && $cn->getConecta()) {
                    sqlsrv_rollback($cn->getConecta());
                }
                
                // Registramos el error en un log
                $this->registrar_error("Error en insert_doc_entrada: " . $e->getMessage());
                
                // Devolvemos un objeto JSON con un status de error
                return json_encode(array(
                    "status" => "error",
                    "message" => $e->getMessage()
                ));
            }                
        }


        public function get_farm_info($idTipo) {
            require_once(dirname(__FILE__) . '/../config/conexiondev.php');
            $cnDev = new ConectarDev();
            if (!$cnDev->getConecta()) {
                return json_encode(array("status" => "error", "message" => "No se pudo conectar a la base de datos DEV"));
            }
            $sql = "SELECT TOP 1 nitCompany, dayEntryPrebail FROM cvapptblmasterfarms WHERE docConsumption = ?";
            $params = array($idTipo);
            $stmt = sqlsrv_query($cnDev->getConecta(), $sql, $params);
            if ($stmt === false) {
                return json_encode(array("status" => "not_found"));
            }
            $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            if (!$row) {
                return json_encode(array("status" => "not_found"));
            }
            return json_encode(array(
                "status" => "success",
                "nitCompany" => $row["nitCompany"],
                "dayEntryPrebail" => $row["dayEntryPrebail"]
            ));
        }

        public function insert_doc_manual($tipo, $nit1, $dir1, $nit2, $dir2, $fecha_factura, $usuario) {
            $cn = new Conectarserver;
            try {
                sqlsrv_begin_transaction($cn->getConecta());

                $sql = "INSERT INTO Documentos(sw, tipo, modelo, Numero_Documento, Numero_Docto_Base,
                nit_Cedula, codigo_direccion, Fecha_Hora_Factura, Fecha_Hora_Vencimiento, Fecha_orden_Venta,
                condicion, valor_total, valor_aplicado, Retencion_1, Retencion_2, Retencion_3, retencion_causada, retencion_iva, retencion_ica,
                retencion_descuento, descuento_pie, DescuentoOrdenVenta, descuento_1, descuento_2, descuento_3, costo, IdVendedor, anulado, usuario,
                notas, pc, fecha_hora, duracion, bodega, Valor_impuesto, Impuesto_Consumo, impuesto_deporte, concepto, vencimiento_presup,
                exportado, prefijo, moneda, CentroDeCostosDoc, valor_mercancia, abono, Comision_Vendedor, Tasa_Moneda_Ext, Tomador, Tasa_Fija_o_Variable, Punto_FOB,
                Fletes_Moneda_Ext, Miselaneos_Moneda_Ext, Cargo_Por_Fletes, Impuesto_Por_Fletes, Total_Items, Nombre_Cliente, Ordenado_Por, Telefono_De_Envio_1,
                Telefono_De_Envio_2, Factura_Impresa, IdFormaEnvio, IdTransportador, nit_Cedula_2, codigo_direccion_2, Numero_Docto_Base_2, Tipo_Docto_Base,
                Tipo_Docto_Base_2, IdActividadEconomica, TarifaReteFuenteCree, Valor_ReteCree, IdVehiculo)

                (SELECT td.tipo AS sw, '$tipo' AS tipo, '$tipo' AS modelo, (c.siguiente+1) AS Numero_Documento, '' AS Numero_Docto_Base,
                '$nit1' AS nit_Cedula, '$dir1' AS codigo_direccion, CONVERT(datetime,'$fecha_factura',120) AS Fecha_Hora_Factura, GETDATE() AS Fecha_Hora_Vencimiento, GETDATE() AS Fecha_orden_Venta,
                t.condicion AS condicion, 0 AS valor_total, 0 AS valor_aplicado, 0 AS Retencion_1, 0 AS Retencion_2, 0 AS Retencion_3,
                0 AS retencion_causada, 0 AS retencion_iva, 0 AS retencion_ica, 0 AS retencion_descuento, 0 AS descuento_pie, 0 AS DescuentoOrdenVenta,
                0 AS descuento_1, 0 AS descuento_2, 0 AS descuento_3, 0 AS costo, 0 AS IdVendedor, 'N' AS anulado, '$usuario' AS usuario,
                '' AS notas, HOST_NAME() AS pc, GETDATE() AS fecha_hora, 0 AS duracion, td.IdBodega AS bodega,
                0 AS Valor_impuesto, 0 AS Impuesto_Consumo, 0 AS impuesto_deporte, '' AS concepto, GETDATE() AS vencimiento_presup,
                'N' AS exportado, '0' AS prefijo, 1 AS moneda, 0 AS CentroDeCostosDoc, 0 AS valor_mercancia, 0 AS abono, 0 AS Comision_Vendedor,
                1 AS Tasa_Moneda_Ext, '' AS Tomador, 'V' AS Tasa_Fija_o_Variable, 0 AS Punto_FOB,
                0 AS Fletes_Moneda_Ext, 0 AS Miselaneos_Moneda_Ext, 0 AS Cargo_Por_Fletes, 0 AS Impuesto_Por_Fletes, 0 AS Total_Items,
                t.nombre AS Nombre_Cliente, '' AS Ordenado_Por, '' AS Telefono_De_Envio_1, '' AS Telefono_De_Envio_2, 'N' AS Factura_Impresa,
                0 AS IdFormaEnvio, 0 AS IdTransportador,
                '$nit2' AS nit_Cedula_2, '$dir2' AS codigo_direccion_2, '' AS Numero_Docto_Base_2, '0' AS Tipo_Docto_Base,
                '2' AS Tipo_Docto_Base_2, '0' AS IdActividadEconomica, 0 AS TarifaReteFuenteCree, 0 AS Valor_ReteCree, '1' AS IdVehiculo

                FROM TblTerceros t, TblTipoDoctos td, consecutivos c
                WHERE c.tipo = '$tipo' AND td.idTipoDoctos = '$tipo' AND t.nit_cedula = '$nit1')";

                $registros = sqlsrv_prepare($cn->getConecta(), $sql);
                if (sqlsrv_execute($registros) === false) {
                    throw new Exception("Error al insertar documento: " . print_r(sqlsrv_errors(), true));
                }

                $sql2 = "UPDATE Consecutivos SET siguiente = siguiente+1 WHERE tipo = '$tipo'";
                $registros = sqlsrv_prepare($cn->getConecta(), $sql2);
                if (sqlsrv_execute($registros) === false) {
                    throw new Exception("Error al actualizar consecutivo: " . print_r(sqlsrv_errors(), true));
                }

                sqlsrv_commit($cn->getConecta());

                return json_encode(array(
                    "status" => "success",
                    "message" => "Documento manual registrado correctamente",
                    "tipo" => $tipo,
                    "consecutivo" => $this->obtener_consecutivo_actual($tipo)
                ));

            } catch (Exception $e) {
                if (isset($cn) && $cn->getConecta()) {
                    sqlsrv_rollback($cn->getConecta());
                }
                $this->registrar_error("Error en insert_doc_manual: " . $e->getMessage());
                return json_encode(array(
                    "status" => "error",
                    "message" => $e->getMessage()
                ));
            }
        }

        public function guardar_salida($tipo, $numdoc, $nit1, $direccion1, $nit2, $direccion2, $despacho, $notas, $dotacion = false, $fecha_factura = ''){
            $cn = new Conectarserver;

            $idVendedorSql = $dotacion ? ", IdVendedor = 12" : "";
            $fechaSql = $fecha_factura ? ", Fecha_Hora_Factura = CONVERT(datetime,'$fecha_factura',120)" : "";

            $sql = "UPDATE Documentos SET
                nit_Cedula = '$nit1', codigo_direccion = '$direccion1',
                nit_Cedula_2 = '$nit2', codigo_direccion_2 = '$direccion2',
                Numero_Docto_Base = '$despacho', notas = '$notas', exportado = 'S' $idVendedorSql $fechaSql,
                Total_Items = (SELECT COUNT(*) FROM Documentos_Lin WHERE tipo = $tipo AND Numero_documento = $numdoc),
                valor_total = (SELECT SUM(ROUND((d.Cantidad_Facturada * d.Valor_Unitario) * (1 - d.Porcentaje_Descuento_1 / 100), 2) + ((d.Cantidad_Facturada * d.Valor_Unitario) * (1 - d.Porcentaje_Descuento_1 / 100)) * (d.Porcentaje_Impuesto / 100)) FROM Documentos_Lin d WHERE tipo = $tipo AND Numero_documento = $numdoc),
                costo = (SELECT SUM(ROUND((d.Cantidad_Facturada * d.Valor_Unitario) * (1 - d.Porcentaje_Descuento_1 / 100), 2) + ((d.Cantidad_Facturada * d.Valor_Unitario) * (1 - d.Porcentaje_Descuento_1 / 100)) * (d.Porcentaje_Impuesto / 100)) FROM Documentos_Lin d WHERE tipo = $tipo AND Numero_documento = $numdoc),
                valor_aplicado = (SELECT SUM(ROUND((d.Cantidad_Facturada * d.Valor_Unitario) * (1 - d.Porcentaje_Descuento_1 / 100), 2) + ((d.Cantidad_Facturada * d.Valor_Unitario) * (1 - d.Porcentaje_Descuento_1 / 100)) * (d.Porcentaje_Impuesto / 100)) FROM Documentos_Lin d WHERE tipo = $tipo AND Numero_documento = $numdoc),
                descuento_1 = (SELECT SUM(ROUND((d.Cantidad_Facturada * d.Valor_Unitario) * (d.Porcentaje_Descuento_1 / 100), 2)) FROM Documentos_Lin d WHERE tipo = $tipo AND Numero_documento = $numdoc),
                Valor_impuesto = (SELECT SUM(((d.Cantidad_Facturada * d.Valor_Unitario) * (1 - d.Porcentaje_Descuento_1 / 100)) * (d.Porcentaje_Impuesto / 100)) FROM Documentos_Lin d WHERE tipo = $tipo AND Numero_documento = $numdoc)
                WHERE tipo = $tipo AND Numero_Documento = $numdoc";

            $registros = sqlsrv_prepare($cn->getConecta(), $sql);
            if(sqlsrv_execute($registros)){
                echo "Salida guardada correctamente";
            } else {
                echo "Error al guardar la salida";
            }

            // Recalcular exportado en Documentos_ped si este documento viene de una OS (Tipo_Docto_Base_2 = '10')
            $sql_os = "SELECT Numero_Docto_Base_2 FROM Documentos
                       WHERE tipo = $tipo AND Numero_Documento = $numdoc
                       AND Tipo_Docto_Base_2 = '10'";
            $stmt_os = sqlsrv_query($cn->getConecta(), $sql_os);
            if ($stmt_os) {
                $row_os = sqlsrv_fetch_array($stmt_os, SQLSRV_FETCH_ASSOC);
                if ($row_os && !empty($row_os['Numero_Docto_Base_2'])) {
                    $numero_os = $row_os['Numero_Docto_Base_2'];
                    $sql_chk = "SELECT COUNT(*) AS con_pendiente
                                FROM Documentos_Lin_Ped dlp
                                LEFT JOIN (
                                    SELECT dl.IdProducto, SUM(dl.Cantidad_Facturada) AS total_facturado
                                    FROM Documentos d
                                    JOIN Documentos_Lin dl ON dl.tipo = d.tipo AND dl.Numero_Documento = d.Numero_documento
                                    WHERE d.Numero_Docto_Base_2 = '$numero_os' AND d.Tipo_Docto_Base_2 = '10'
                                    AND d.exportado = 'S'
                                    GROUP BY dl.IdProducto
                                ) f ON f.IdProducto = dlp.IdProducto
                                WHERE dlp.numero_pedido = '$numero_os' AND dlp.sw = '10'
                                AND (dlp.cantidad - ISNULL(f.total_facturado, 0)) > 0";
                    $stmt_chk = sqlsrv_query($cn->getConecta(), $sql_chk);
                    $row_chk = sqlsrv_fetch_array($stmt_chk, SQLSRV_FETCH_ASSOC);
                    $exportado_ped = ($row_chk && $row_chk['con_pendiente'] == 0) ? 'S' : 'P';
                    $sql_upd_ped = "UPDATE Documentos_Ped
                                    SET exportado = '$exportado_ped'
                                    WHERE numero_pedido = '$numero_os' AND sw = '10'";
                    sqlsrv_query($cn->getConecta(), $sql_upd_ped);
                }
            }

            $sql2 = "(EXEC UPDATE_PRODUCTO_STO)";
            $registros = sqlsrv_prepare($cn->getConecta(), $sql2);
            sqlsrv_execute($registros);
        }

        public function update_notas_etapa($tipo, $numdoc, $notas) {
            $cn = new Conectarserver;
            $sql = "UPDATE Documentos SET notas = ? WHERE tipo = ? AND Numero_Documento = ?";
            $params = array($notas, $tipo, $numdoc);
            $stmt = sqlsrv_query($cn->getConecta(), $sql, $params);
            if ($stmt === false) {
                echo json_encode(['status' => 'error', 'message' => 'Error al actualizar etapa']);
            } else {
                echo json_encode(['status' => 'success', 'message' => 'Etapa asignada correctamente']);
            }
        }

        public function update_lote_salida($tipo, $numdoc, $lote, $seqs = ''){
            $cn = new Conectarserver;

            $seqFilter = '';
            if (!empty($seqs)) {
                $seqArray = array_filter(array_map('intval', explode(',', $seqs)));
                if (!empty($seqArray)) {
                    $seqFilter = " AND seq IN (" . implode(',', $seqArray) . ")";
                }
            }

            $sql = "UPDATE Documentos_Lin SET Numero_Lote = ? WHERE tipo = ? AND Numero_documento = ?" . $seqFilter;
            $params = array($lote, $tipo, $numdoc);
            $stmt = sqlsrv_query($cn->getConecta(), $sql, $params);
            if($stmt === false){
                echo "Error al actualizar lote";
            } else {
                echo "Lote actualizado correctamente";
            }
        }

        public function insert_devolucion($tipo, $numero, $tiporef, $usuario, $idConcepto, $nombreConcepto){
            $cn = new Conectarserver;

            try {

                $sql_validar = "SELECT COUNT(*) AS existe FROM Documentos
                WHERE Numero_documento = ? AND tipo = ?";

                $params = array($numero, $tiporef);
                $stmt = sqlsrv_query($cn->getConecta(), $sql_validar, $params);

                if ($stmt === false) {
                    throw new Exception("Error al validar el documento de referencia: " . print_r(sqlsrv_errors(), true));
                }

                $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

                if (!$row || $row['existe'] == 0) {
                    return json_encode(array(
                        "status" => "error",
                        "message" => "El documento de referencia con número '$numero' y tipo '$tiporef' no existe en el sistema"
                    ));
                }

                sqlsrv_begin_transaction($cn->getConecta());

                $sql="INSERT INTO Documentos(sw, tipo, modelo, Numero_Documento, Numero_Docto_Base,
                nit_Cedula, codigo_direccion, Fecha_Hora_Factura,Fecha_Hora_Vencimiento,Fecha_orden_Venta,
                condicion,valor_total, valor_aplicado, Retencion_1,Retencion_2, Retencion_3, retencion_causada, retencion_iva,retencion_ica,
                retencion_descuento, descuento_pie, DescuentoOrdenVenta, descuento_1, descuento_2, descuento_3, costo, IdVendedor, anulado, usuario,
                notas,pc, fecha_hora, duracion, bodega, Valor_impuesto, Impuesto_Consumo, impuesto_deporte, concepto, vencimiento_presup,
                exportado, prefijo, moneda, CentroDeCostosDoc, valor_mercancia, abono, Comision_Vendedor, Tasa_Moneda_Ext, Tomador, Tasa_Fija_o_Variable, Punto_FOB,
                Fletes_Moneda_Ext, Miselaneos_Moneda_Ext, Cargo_Por_Fletes, Impuesto_Por_Fletes, Total_Items, Nombre_Cliente, Ordenado_Por, Telefono_De_Envio_1,
                Telefono_De_Envio_2, Factura_Impresa, IdFormaEnvio, IdTransportador, nit_Cedula_2, codigo_direccion_2, Numero_Docto_Base_2, Tipo_Docto_Base,
                Tipo_Docto_Base_2, IdActividadEconomica, TarifaReteFuenteCree, Valor_ReteCree, IdVehiculo)

                (SELECT td.tipo AS sw, '$tipo' AS tipo, '$tipo' AS modelo, (c.siguiente+1) AS Numero_Documento, '$numero' AS Numero_Docto_Base,
                d.nit_Cedula AS nit_Cedula, d.codigo_direccion AS codigo_direccion,  GETDATE() AS Fecha_Hora_Factura, GETDATE() AS Fecha_Hora_Vencimiento, GETDATE() AS Fecha_orden_Venta,
                d.condicion AS condicion, d.valor_total AS valor_total, 0 AS valor_aplicado, d.Retencion_1 AS Retencion_1, d.Retencion_2 AS Retencion_2, d.Retencion_3 AS Retencion_3, 0 AS retencion_causada, 0 AS retencion_iva,
                0 AS retencion_ica, 0 AS retencion_descuento, 0 AS descuento_pie, 0 AS DescuentoOrdenVenta, d.descuento_1 AS descuento_1, d.descuento_2 AS descuento_2, d.descuento_3 AS descuento_3,
                d.costo AS costo, d.IdVendedor AS IdVendedor, 'N' AS anulado, '$usuario' AS usuario,
                d.notas AS notas, HOST_NAME() AS pc, GETDATE() AS fecha_hora, 0 AS duracion, td.IdBodega AS bodega, 0 AS Valor_impuesto, 0 AS Impuesto_Consumo,
                0 AS impuesto_deporte, d.concepto AS concepto, GETDATE() AS vencimiento_presup,
                'S' AS exportado, '0' AS prefijo, d.moneda AS moneda, 0 AS CentroDeCostosDoc, 0 AS valor_mercancia, 0 AS abono, 0 AS Comision_Vendedor,
                1 AS Tasa_Moneda_Ext, '' AS Tomador, 'V' AS Tasa_Fija_o_Variable, d.Punto_FOB AS Punto_FOB,
                0 AS Fletes_Moneda_Ext, 0 AS Miselaneos_Moneda_Ext, 0 AS Cargo_Por_Fletes, 0 AS Impuesto_Por_Fletes, d.Total_Items AS Total_Items, d.Nombre_Cliente AS Nombre_Cliente,
                SUBSTRING(d.Ordenado_Por,0,20) AS Ordenado_Por, d.Telefono_De_Envio_1 AS Telefono_De_Envio_1, d.Telefono_De_Envio_2 AS Telefono_De_Envio_2, 'N' AS Factura_Impresa, d.IdFormaEnvio AS IdFormaEnvio, d.IdTRansportador AS IdTransportador,
                d.nit_Cedula_2 AS nit_Cedula_2, d.codigo_direccion_2 AS codigo_direccion_2, d.Numero_Docto_Base_2 AS Numero_Docto_Base_2, '$tiporef' AS Tipo_Docto_Base,
                d.Tipo_Docto_Base_2 AS Tipo_Docto_Base_2, d.IdActividadEconomica AS IdActividadEconomica, d.TarifaReteFuenteCree AS TarifaReteFuenteCree, d.Valor_ReteCree AS Valor_ReteCree, d.IdVehiculo AS IdVehiculo

                FROM Documentos d, consecutivos c, TblTipoDoctos td
                WHERE c.tipo = '$tipo' AND d.Numero_documento = '$numero' AND d.tipo = '$tiporef' AND td.idTipoDoctos = '$tipo')";

                $registros = sqlsrv_prepare($cn->getConecta(), $sql);
                if(sqlsrv_execute($registros) === false) {
                    throw new Exception("Error al insertar documento: " . print_r(sqlsrv_errors(), true));
                }

                $sql1="INSERT INTO Documentos_Lin
                (sw, tipo, seq, modelo, Numero_Documento, Numero_Docto_Base, Numero_Lote, Nit_Cedula, Codigo_Direccion, Fecha_Documento,
                IdProducto, IdUnidad, Factor_Conversion, Cantidad_Facturada, Cantidad_Pendiente, Cantidad_Orden, Costo_Unitario, Valor_Unitario,
                Valor_Impuesto, Porcentaje_Impuesto, Porcentaje_Descuento_1, Porcentaje_Descuento_2,Porcentaje_Descuento_3, IdVendedor, Comision_Vendedor,
                Valor_Comision_Vendedor, IdBodega, Maneja_Inventario, Tomador, IdMoneda, Tasa_Moneda_Ext, CentroDeCostosDoc,
                Nota_Linea, Unidades, Fecha_Vence, Exportado, Costo_Unitario_Inicial,
                Porcentaje_ReteFuente, Envase, Numero_Lote_Destino, serial, Impuesto_Consumo, Porcentaje_ReteFuente_2,
                Porcentaje_ReteFuente_3, Porcentaje_ReteFuente_4, Emp_1, Emp_2, Emp_3, Emp_4, Emp_5, Emp_6,
                Emp_7, Emp_8, Tara_1, Tara_2, Tara_3, Tara_4, Tara_5, Tara_6, Tara_7, Tara_8)

                (SELECT td.tipo AS sw, '$tipo' AS tipo, dl.seq AS seq, p.contable AS Modelo, (c.siguiente+1) AS Numero_Documento,
                '' AS Numero_Docto_Base, dl.Numero_Lote AS Numero_Lote, dl.Nit_Cedula AS Nit_Cedula, dl.codigo_direccion AS codigo_direccion,  GETDATE() AS Fecha_Documento,
                dl.IdProducto AS IdProducto, dl.IdUnidad AS IdUnidad, '1' AS Factor_Conversion, Cantidad_Facturada AS Cantidad_Facturada,
                (dl.Cantidad_Facturada)* -1 AS Cantidad_Pendiente, dl.Cantidad_Orden AS Cantidad_Orden,
                dl.Costo_Unitario AS Costo_Unitario, dl.valor_unitario AS Valor_Unitario, (dl.Porcentaje_Impuesto / 100.0 * dl.valor_unitario * dl.Cantidad_Facturada) AS Valor_Impuesto, dl.Porcentaje_Impuesto AS Porcentaje_Impuesto,
                dl.Porcentaje_Descuento_1 AS Porcentaje_Descuento_1, dl.Porcentaje_Descuento_2 AS Porcentaje_Descuento_2,
                dl.Porcentaje_Descuento_3 AS Porcentaje_Descuento_3, dl.IdVendedor AS IdVendedor, 0 AS Comision_Vendedor, 0 AS Valor_Comision_Vendedor,
                td.IdBodega AS IdBodega, 'S' AS Maneja_Inventario, '' AS Tomador, 1 AS IdMoneda, 1 AS Tasa_Moneda_Ext, '0' AS CentroDeCostosDoc,
                ' ' AS Nota_Linea, '1' AS Unidades, GETDATE() AS Fecha_Vence, 'N' AS Exportado, dl.Costo_Unitario_Inicial AS Costo_Unitario_Inicial,
                CASE
                WHEN LTRIM(RTRIM(t.TipoPersona)) = 'Juridica' THEN r.PorcentajeRetencionJuridica
                ELSE r.PorcentajeRetencionNatural
                END AS Porcentaje_ReteFuente, 0 AS Envase, 0 AS Numero_Lote_Destino, '' AS serial, 0 AS Impuesto_Consumo, 0 AS Porcentaje_ReteFuente_2,
                0 AS Porcentaje_ReteFuente_3, 0 AS Porcentaje_ReteFuente_4, 0 AS Emp_1, 0 AS Emp_2, 0 AS Emp_3, 0 AS Emp_4, 0 AS Emp_5, 0 AS Emp_6,
                0 AS Emp_7, 0 AS Emp_8, 0 AS Tara_1, 0 AS Tara_2, 0 AS Tara_3, 0 AS Tara_4, 0 AS Tara_5, 0 AS Tara_6, 0 AS Tara_7, 0 AS Tara_8

                FROM  consecutivos c, Documentos_Lin dl
                INNER JOIN Documentos d ON d.Numero_documento=dl.Numero_Documento AND d.tipo = dl.tipo
                INNER JOIN TblTipoDoctos td ON td.idTipoDoctos = '$tipo'
                LEFT JOIN TblProducto p ON p.IdProducto = dl.IdProducto
                LEFT JOIN TblTerceros t ON dl.Nit_Cedula=t.nit_cedula
                LEFT JOIN TblRetencion r ON p.Retencion=r.IdRetencion

                WHERE c.tipo = '$tipo' AND dl.Numero_documento = '$numero' AND dl.tipo = '$tiporef'
                )";

                $registros = sqlsrv_prepare($cn->getConecta(), $sql1);
                if(sqlsrv_execute($registros) === false) {
                    throw new Exception("Error al insertar detalle del documento: " . print_r(sqlsrv_errors(), true));
                }

                // Actualizar Valor_impuesto en cabecera como suma de impuestos del detalle
                $sql_imp = "UPDATE Documentos SET
                    Valor_impuesto = (
                        SELECT ISNULL(SUM(dl.Valor_Impuesto), 0)
                        FROM Documentos_Lin dl
                        WHERE dl.tipo = '$tipo'
                          AND dl.Numero_documento = (SELECT siguiente+1 FROM Consecutivos WHERE tipo = '$tipo')
                    )
                    WHERE tipo = '$tipo'
                      AND Numero_Documento = (SELECT siguiente+1 FROM Consecutivos WHERE tipo = '$tipo')";
                $registros = sqlsrv_prepare($cn->getConecta(), $sql_imp);
                if(sqlsrv_execute($registros) === false) {
                    throw new Exception("Error al actualizar Valor_impuesto en cabecera: " . print_r(sqlsrv_errors(), true));
                }

                $sql2="UPDATE Consecutivos SET siguiente = siguiente+1 WHERE tipo = '$tipo' ";
                $registros = sqlsrv_prepare($cn->getConecta(), $sql2);
                if(sqlsrv_execute($registros) === false) {
                    throw new Exception("Error al actualizar consecutivo: " . print_r(sqlsrv_errors(), true));
                }

                // Actualizar Notas con el concepto de devolución (sobrescribe la nota del doc original)
                $notaConcepto = 'Motivo: ' . $nombreConcepto;
                $sqlNotas = "UPDATE Documentos SET Notas = ?
                    WHERE tipo = '$tipo'
                      AND Numero_Documento = (SELECT siguiente FROM Consecutivos WHERE tipo = '$tipo')";
                $paramsNotas = array($notaConcepto);
                $stmtNotas = sqlsrv_prepare($cn->getConecta(), $sqlNotas, $paramsNotas);
                if(sqlsrv_execute($stmtNotas) === false) {
                    throw new Exception("Error al actualizar Notas con concepto: " . print_r(sqlsrv_errors(), true));
                }

                // Intentar actualizar idConceptoDevolucion (requiere ejecutar sql/02_sqlserver_alter_documentos.sql)
                $sqlCheckCol = "SELECT COUNT(*) AS existe FROM sys.columns
                    WHERE object_id = OBJECT_ID('Documentos') AND name = 'idConceptoDevolucion'";
                $stmtCheck = sqlsrv_query($cn->getConecta(), $sqlCheckCol);
                if ($stmtCheck !== false) {
                    $rowCheck = sqlsrv_fetch_array($stmtCheck, SQLSRV_FETCH_ASSOC);
                    if ($rowCheck && $rowCheck['existe'] > 0) {
                        $sqlIdConc = "UPDATE Documentos SET idConceptoDevolucion = ?
                            WHERE tipo = '$tipo'
                              AND Numero_Documento = (SELECT siguiente FROM Consecutivos WHERE tipo = '$tipo')";
                        $paramsIdConc = array((int)$idConcepto);
                        $stmtIdConc = sqlsrv_prepare($cn->getConecta(), $sqlIdConc, $paramsIdConc);
                        if(sqlsrv_execute($stmtIdConc) === false) {
                            throw new Exception("Error al actualizar idConceptoDevolucion: " . print_r(sqlsrv_errors(), true));
                        }
                    }
                }

                sqlsrv_commit($cn->getConecta());

                return json_encode(array(
                    "status" => "success",
                    "message" => "Documento registrado correctamente",
                    "tipo" => $tipo,
                    "consecutivo" => $this->obtener_consecutivo_actual($tipo)
                ));

            } catch (Exception $e) {
                if (isset($cn) && $cn->getConecta()) {
                    sqlsrv_rollback($cn->getConecta());
                }
                $this->registrar_error("Error en insert_devolucion: " . $e->getMessage());
                return json_encode(array(
                    "status" => "error",
                    "message" => $e->getMessage()
                ));
            }
        }

        public function insert_doc_salida($tipo, $numero, $usuario){
            $cn = new Conectarserver;

            try {

                $sql_validar = "SELECT COUNT(*) AS existe, MAX(CAST(anulado AS int)) AS anulado FROM Documentos_Ped
                WHERE numero_pedido = ? AND sw = '10'";

                $params = array($numero);
                $stmt = sqlsrv_query($cn->getConecta(), $sql_validar, $params);

                if ($stmt === false) {
                    throw new Exception("Error al validar el número de pedido: " . print_r(sqlsrv_errors(), true));
                }

                $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

                if (!$row || $row['existe'] == 0) {
                    return json_encode(array(
                        "status" => "error",
                        "message" => "La Orden de Salida '$numero' no existe"
                    ));
                }

                if ($row['anulado'] == 0) {
                    return json_encode(array(
                        "status" => "error",
                        "message" => "La Orden de Salida '$numero' está anulada y no puede ser procesada"
                    ));
                }

                // Verificar pendientes de forma dinámica (sin campos acumulados)
                $sql_pend_chk = "SELECT COUNT(*) AS con_pendiente
                                 FROM Documentos_Lin_Ped dlp
                                 LEFT JOIN (
                                     SELECT dl.IdProducto, SUM(dl.Cantidad_Facturada) AS total_facturado
                                     FROM Documentos d
                                     JOIN Documentos_Lin dl ON dl.tipo = d.tipo AND dl.Numero_Documento = d.Numero_documento
                                     WHERE d.Numero_Docto_Base_2 = '$numero' AND d.Tipo_Docto_Base_2 = '10'
                                     GROUP BY dl.IdProducto
                                 ) f ON f.IdProducto = dlp.IdProducto
                                 WHERE dlp.numero_pedido = '$numero' AND dlp.sw = '10'
                                 AND (dlp.cantidad - ISNULL(f.total_facturado, 0)) > 0";
                $stmt_pend_chk = sqlsrv_query($cn->getConecta(), $sql_pend_chk);
                if ($stmt_pend_chk) {
                    $row_pend_chk = sqlsrv_fetch_array($stmt_pend_chk, SQLSRV_FETCH_ASSOC);
                    if ($row_pend_chk && $row_pend_chk['con_pendiente'] == 0) {
                        return json_encode(array(
                            "status" => "error",
                            "message" => "La Orden de Salida '$numero' ya tiene todas sus cantidades despachadas."
                        ));
                    }
                }

                sqlsrv_begin_transaction($cn->getConecta());

                // Obtener el siguiente consecutivo de forma segura
                $sql_num = "SELECT (siguiente + 1) AS numDoc FROM Consecutivos WHERE tipo = '$tipo'";
                $stmt_num = sqlsrv_query($cn->getConecta(), $sql_num);
                if ($stmt_num === false) {
                    throw new Exception("Error al obtener consecutivo: " . print_r(sqlsrv_errors(), true));
                }
                $row_num = sqlsrv_fetch_array($stmt_num, SQLSRV_FETCH_ASSOC);
                $numDoc = $row_num['numDoc'];

                $sql="INSERT INTO Documentos(sw, tipo, modelo, Numero_Documento, Numero_Docto_Base,
                nit_Cedula, codigo_direccion, Fecha_Hora_Factura,Fecha_Hora_Vencimiento,Fecha_orden_Venta,
                condicion,valor_total, valor_aplicado, Retencion_1,Retencion_2, Retencion_3, retencion_causada, retencion_iva,retencion_ica,
                retencion_descuento, descuento_pie, DescuentoOrdenVenta, descuento_1, descuento_2, descuento_3, costo, IdVendedor, anulado, usuario,
                notas,pc, fecha_hora, duracion, bodega, Valor_impuesto, Impuesto_Consumo, impuesto_deporte, concepto, vencimiento_presup,
                exportado, prefijo, moneda, CentroDeCostosDoc, valor_mercancia, abono, Comision_Vendedor, Tasa_Moneda_Ext, Tomador, Tasa_Fija_o_Variable, Punto_FOB,
                Fletes_Moneda_Ext, Miselaneos_Moneda_Ext, Cargo_Por_Fletes, Impuesto_Por_Fletes, Total_Items, Nombre_Cliente, Ordenado_Por, Telefono_De_Envio_1,
                Telefono_De_Envio_2, Factura_Impresa, IdFormaEnvio, IdTransportador, nit_Cedula_2, codigo_direccion_2, Numero_Docto_Base_2, Tipo_Docto_Base,
                Tipo_Docto_Base_2, IdActividadEconomica, TarifaReteFuenteCree, Valor_ReteCree, IdVehiculo)

                (SELECT td.tipo AS sw, '$tipo' AS tipo, '$tipo' AS modelo, $numDoc AS Numero_Documento, '' AS Numero_Docto_Base,
                dp.nit AS nit_Cedula, dp.direccion_factura AS codigo_direccion,  GETDATE() AS Fecha_Hora_Factura, GETDATE() AS Fecha_Hora_Vencimiento, GETDATE() AS Fecha_orden_Venta,
                t.condicion AS condicion, dp.valor_total AS valor_total, dp.valor_total AS valor_aplicado, dp.Retencion_1 AS Retencion_1, 0 AS Retencion_2, 0 AS Retencion_3,
                0 AS retencion_causada, 0 AS retencion_iva, 0 AS retencion_ica, 0 AS retencion_descuento, 0 AS descuento_pie, 0 AS DescuentoOrdenVenta, 0 AS descuento_1, 0 AS descuento_2,
                0 AS descuento_3, 0 AS costo, dp.vendedor AS idVendedor, 'N' AS anulado, '$usuario' AS usuario, dp.notas AS notas, HOST_NAME() AS pc, GETDATE() AS fecha_hora,
                0 AS duracion, td.IdBodega AS bodega, 0 AS Valor_impuesto, 0 AS Impuesto_Consumo, 0 AS impuesto_deporte, dp.concepto AS concepto, GETDATE() AS vencimiento_presup,
                'N' AS exportado, '0' AS prefijo, dp.moneda AS moneda, 0 AS CentroDeCostosDoc, 0 AS valor_mercancia, 0 AS abono, 0 AS Comision_Vendedor,
                1 AS Tasa_Moneda_Ext, '' AS Tomador, 'V' AS Tasa_Fija_o_Variable, dir.idLista AS Punto_FOB,
                0 AS Fletes_Moneda_Ext, 0 AS Miselaneos_Moneda_Ext, 0 AS Cargo_Por_Fletes, 0 AS Impuesto_Por_Fletes, 0 AS Total_Items, t.nombre AS Nombre_Cliente,
                SUBSTRING(dp.Contacto_Compras,0,20) AS Ordenado_Por, dp.telefono1 AS Telefono_De_Envio_1, '' AS Telefono_De_Envio_2, 'N' AS Factura_Impresa, dp.IdFormaEnvio AS IdFormaEnvio, dp.IdTRansportador AS IdTransportador,
                dp.nit_destino AS nit_Cedula_2, dp.direccion_entrega AS codigo_direccion_2, '$numero' AS Numero_Docto_Base_2, '0' AS Tipo_Docto_Base,
                '10' AS Tipo_Docto_Base_2, '0' AS IdActividadEconomica, 0 AS TarifaReteFuenteCree, 0 AS Valor_ReteCree, '1' AS IdVehiculo

                FROM Documentos_Ped dp, TblTerceros t, TblTipoDoctos td, Terceros_Dir dir
                WHERE td.idTipoDoctos = '$tipo' AND
                dp.nit = t.nit_cedula AND dir.codigo_direccion = dp.direccion_factura AND dir.nit = dp.nit AND
                dp.numero_pedido = '$numero' AND dp.sw = '10') ";

                $registros = sqlsrv_prepare($cn->getConecta(), $sql);
                if(sqlsrv_execute($registros) === false) {
                    throw new Exception("Error al insertar documento: " . print_r(sqlsrv_errors(), true));
                }

                $sql1="INSERT INTO Documentos_Lin
                (sw, tipo, seq, modelo, Numero_Documento, Numero_Docto_Base, Numero_Lote, Nit_Cedula, Codigo_Direccion, Fecha_Documento,
                IdProducto, IdUnidad, Factor_Conversion, Cantidad_Facturada, Cantidad_Pendiente, Cantidad_Orden, Costo_Unitario, Valor_Unitario,
                Valor_Impuesto, Porcentaje_Impuesto, Porcentaje_Descuento_1, Porcentaje_Descuento_2,Porcentaje_Descuento_3, IdVendedor, Comision_Vendedor,
                Valor_Comision_Vendedor, IdBodega, Maneja_Inventario, Tomador, IdMoneda, Tasa_Moneda_Ext, CentroDeCostosDoc,
                Nota_Linea, Unidades, Fecha_Vence, Exportado, Costo_Unitario_Inicial,
                Porcentaje_ReteFuente, Envase, Numero_Lote_Destino, serial, Impuesto_Consumo, Porcentaje_ReteFuente_2,
                Porcentaje_ReteFuente_3, Porcentaje_ReteFuente_4, Emp_1, Emp_2, Emp_3, Emp_4, Emp_5, Emp_6,
                Emp_7, Emp_8, Tara_1, Tara_2, Tara_3, Tara_4, Tara_5, Tara_6, Tara_7, Tara_8)

                (SELECT td.tipo AS sw, '$tipo' AS tipo, dp.Linea AS seq, p.contable AS Modelo, $numDoc AS Numero_Documento,
                '' AS Numero_Docto_Base, '0' AS Numero_Lote, dp.IdCliente AS Nit_Cedula, dp.DireccionFactura AS codigo_direccion, GETDATE() AS Fecha_Documento,
                dp.IdProducto AS IdProducto, dp.und AS IdUnidad, '1' AS Factor_Conversion,
                (dp.cantidad - ISNULL(f.total_facturado, 0)) AS Cantidad_Facturada,
                0 AS Cantidad_Pendiente, dp.cantidad AS Cantidad_Orden, dp.valor_unitario AS Costo_Unitario, dp.valor_unitario AS Valor_Unitario,
                ((ISNULL(dp.porcentaje_iva, 0)/100) * dp.valor_unitario) AS Valor_Impuesto, ISNULL(dp.porcentaje_iva, 0) AS Porcentaje_Impuesto, ISNULL(dp.porcentaje_descuento, 0) AS Porcentaje_Descuento_1,
                ISNULL(dp.porc_dcto_2, 0) AS Porcentaje_Descuento_2, ISNULL(dp.porc_dcto_3, 0) AS Porcentaje_Descuento_3, dp.IdVendedor AS IdVendedor, 0 AS Comision_Vendedor, 0 AS Valor_Comision_Vendedor,
                td.IdBodega AS IdBodega, 'S' AS Maneja_Inventario, '' AS Tomador, 1 AS IdMoneda, 1 AS Tasa_Moneda_Ext, '0' AS CentroDeCostosDoc,
                ' ' AS Nota_Linea, '1' AS Unidades, GETDATE() AS Fecha_Vence, 'N' AS Exportado, dp.valor_unitario AS Costo_Unitario_Inicial,
                dp.Porcentaje_ReteFuente AS Porcentaje_ReteFuente, 0 AS Envase, 0 AS Numero_Lote_Destino, '' AS serial, 0 AS Impuesto_Consumo, 0 AS Porcentaje_ReteFuente_2,
                0 AS Porcentaje_ReteFuente_3, 0 AS Porcentaje_ReteFuente_4, 0 AS Emp_1, 0 AS Emp_2, 0 AS Emp_3, 0 AS Emp_4, 0 AS Emp_5, 0 AS Emp_6,
                0 AS Emp_7, 0 AS Emp_8, 0 AS Tara_1, 0 AS Tara_2, 0 AS Tara_3, 0 AS Tara_4, 0 AS Tara_5, 0 AS Tara_6, 0 AS Tara_7, 0 AS Tara_8

                FROM Documentos_Lin_Ped dp
                JOIN TblTipoDoctos td ON td.idTipoDoctos = '$tipo'
                JOIN TblProducto p ON p.IdProducto = dp.IdProducto
                LEFT JOIN (
                    SELECT dl.IdProducto, SUM(dl.Cantidad_Facturada) AS total_facturado
                    FROM Documentos d
                    JOIN Documentos_Lin dl ON dl.tipo = d.tipo AND dl.Numero_Documento = d.Numero_documento
                    WHERE d.Numero_Docto_Base_2 = '$numero' AND d.Tipo_Docto_Base_2 = '10'
                    GROUP BY dl.IdProducto
                ) f ON f.IdProducto = dp.IdProducto
                WHERE dp.numero_pedido = '$numero' AND dp.sw = '10'
                AND (dp.cantidad - ISNULL(f.total_facturado, 0)) > 0)";

                $registros_lin =  sqlsrv_prepare($cn->getConecta(), $sql1);
                if(sqlsrv_execute($registros_lin) === false) {
                    throw new Exception("Error al insertar detalle del documento: " . print_r(sqlsrv_errors(), true));
                }

                // Actualizar totales en cabecera como suma del detalle
                $sql_totales = "UPDATE Documentos SET 
                    Total_Items = (SELECT COUNT(*) FROM Documentos_Lin WHERE tipo = '$tipo' AND Numero_documento = $numDoc),
                    Valor_impuesto = (SELECT ISNULL(SUM(((dl.Cantidad_Facturada * dl.Valor_Unitario) * (1 - ISNULL(dl.Porcentaje_Descuento_1, 0) / 100)) * (ISNULL(dl.Porcentaje_Impuesto, 0) / 100)), 0) FROM Documentos_Lin dl WHERE dl.tipo = '$tipo' AND dl.Numero_documento = $numDoc),
                    valor_total = (SELECT ISNULL(SUM(ROUND((dl.Cantidad_Facturada * dl.Valor_Unitario) * (1 - ISNULL(dl.Porcentaje_Descuento_1, 0) / 100), 2) + ((dl.Cantidad_Facturada * dl.Valor_Unitario) * (1 - ISNULL(dl.Porcentaje_Descuento_1, 0) / 100)) * (ISNULL(dl.Porcentaje_Impuesto, 0) / 100)), 0) FROM Documentos_Lin dl WHERE dl.tipo = '$tipo' AND dl.Numero_documento = $numDoc),
                    valor_aplicado = (SELECT ISNULL(SUM(ROUND((dl.Cantidad_Facturada * dl.Valor_Unitario) * (1 - ISNULL(dl.Porcentaje_Descuento_1, 0) / 100), 2) + ((dl.Cantidad_Facturada * dl.Valor_Unitario) * (1 - ISNULL(dl.Porcentaje_Descuento_1, 0) / 100)) * (ISNULL(dl.Porcentaje_Impuesto, 0) / 100)), 0) FROM Documentos_Lin dl WHERE dl.tipo = '$tipo' AND dl.Numero_documento = $numDoc),
                    costo = (SELECT ISNULL(SUM(ROUND((dl.Cantidad_Facturada * dl.Valor_Unitario) * (1 - ISNULL(dl.Porcentaje_Descuento_1, 0) / 100), 2) + ((dl.Cantidad_Facturada * dl.Valor_Unitario) * (1 - ISNULL(dl.Porcentaje_Descuento_1, 0) / 100)) * (ISNULL(dl.Porcentaje_Impuesto, 0) / 100)), 0) FROM Documentos_Lin dl WHERE dl.tipo = '$tipo' AND dl.Numero_documento = $numDoc)
                    WHERE tipo = '$tipo' AND Numero_Documento = $numDoc";
                
                $registros_tot = sqlsrv_prepare($cn->getConecta(), $sql_totales);
                if(sqlsrv_execute($registros_tot) === false) {
                    throw new Exception("Error al actualizar totales en cabecera: " . print_r(sqlsrv_errors(), true));
                }

                $sql2="UPDATE Consecutivos SET siguiente = siguiente + 1 WHERE tipo = '$tipo'";
                $registros_con =  sqlsrv_prepare($cn->getConecta(), $sql2);
                if(sqlsrv_execute($registros_con) === false) {
                    throw new Exception("Error al actualizar consecutivo: " . print_r(sqlsrv_errors(), true));
                }

                // Calcular si quedan pendientes para marcar exportado en Documentos_ped (P=parcial, S=completo)
                $sql_chk_pend = "SELECT COUNT(*) AS con_pendiente
                                 FROM Documentos_Lin_Ped dlp
                                 LEFT JOIN (
                                     SELECT dl.IdProducto, SUM(dl.Cantidad_Facturada) AS total_facturado
                                     FROM Documentos d
                                     JOIN Documentos_Lin dl ON dl.tipo = d.tipo AND dl.Numero_Documento = d.Numero_documento
                                     WHERE d.Numero_Docto_Base_2 = '$numero' AND d.Tipo_Docto_Base_2 = '10'
                                     AND d.exportado = 'S'
                                     GROUP BY dl.IdProducto
                                 ) f ON f.IdProducto = dlp.IdProducto
                                 WHERE dlp.numero_pedido = '$numero' AND dlp.sw = '10'
                                 AND (dlp.cantidad - ISNULL(f.total_facturado, 0)) > 0";
                $stmt_chk_pend = sqlsrv_query($cn->getConecta(), $sql_chk_pend);
                $row_chk_pend  = sqlsrv_fetch_array($stmt_chk_pend, SQLSRV_FETCH_ASSOC);
                $exportado_ped = ($row_chk_pend && $row_chk_pend['con_pendiente'] == 0) ? 'S' : 'P';

                // Actualizar Documentos_ped: exportado indica si la OS quedó completa o parcial
                // despacho = 'F' siempre (marca que ya fue procesada al menos una vez)
                $sql_upd_ped = "UPDATE Documentos_Ped
                                SET exportado = '$exportado_ped', despacho = 'F'
                                WHERE numero_pedido = '$numero' AND sw = '10'";
                $stmt_upd_ped = sqlsrv_prepare($cn->getConecta(), $sql_upd_ped);
                if(sqlsrv_execute($stmt_upd_ped) === false) {
                    throw new Exception("Error al actualizar estado de la OS: " . print_r(sqlsrv_errors(), true));
                }

                sqlsrv_commit($cn->getConecta());

                return json_encode(array(
                    "status" => "success",
                    "message" => "Documento registrado correctamente",
                    "tipo" => $tipo,
                    "consecutivo" => $this->obtener_consecutivo_actual($tipo)
                ));

            }catch (Exception $e) {

                if (isset($cn) && $cn->getConecta()) {
                    sqlsrv_rollback($cn->getConecta());
                }

                $this->registrar_error("Error en insert_doc_salida: " . $e->getMessage());

                return json_encode(array(
                    "status" => "error",
                    "message" => $e->getMessage()
                ));
            }
        }

        public function get_info_producto($idProducto, $tipo, $numdoc, $nit = '', $direccion = '') {
            $cn = new Conectarserver;

            // Limpiar dirección: puede llegar como "12,..." desde el select del formulario
            if (strpos($direccion, ',') !== false) {
                $direccion = explode(',', $direccion)[0];
            }
            $direccion = trim($direccion);
            $nit       = trim($nit);

            // 1. Nombre e impuesto del producto
            $sqlProd = "SELECT p.Producto, ISNULL(i.PorcentajeImpuesto, 0) AS PorcentajeImpuesto
                        FROM TblProducto p
                        LEFT JOIN TblImpuesto i ON p.Impuesto_venta = i.IdImpuesto
                        WHERE p.IdProducto = ?";
            $stmt = sqlsrv_query($cn->getConecta(), $sqlProd, array((int)$idProducto));
            if ($stmt === false || ($rowProd = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) === null) {
                return json_encode(array("status" => "error", "message" => "Producto no encontrado"));
            }

            $ID_LISTA_DEFAULT = 50;

            // 2a. IdLista desde Terceros_Dir con nit+dirección del formulario
            $idListaReal = null; // null = no encontrado en BD
            if ($nit !== '' && $direccion !== '') {
                $sqlLista = "SELECT TOP 1 idLista FROM Terceros_Dir
                             WHERE nit = ? AND codigo_direccion = ?";
                $stmtLista = sqlsrv_query($cn->getConecta(), $sqlLista, array($nit, (int)$direccion));
                if ($stmtLista !== false) {
                    $rowLista = sqlsrv_fetch_array($stmtLista, SQLSRV_FETCH_ASSOC);
                    if ($rowLista) $idListaReal = $rowLista['idLista']; // puede ser null si la col es NULL
                }
            }

            // 2b. Fallback vía Documentos
            if ($idListaReal === null && $tipo && $numdoc) {
                $sqlLista2 = "SELECT TOP 1 td.idLista
                              FROM Documentos d
                              INNER JOIN Terceros_Dir td ON td.nit = d.nit_Cedula
                                  AND td.codigo_direccion = d.codigo_direccion
                              WHERE d.tipo = ? AND d.Numero_documento = ?";
                $stmtLista2 = sqlsrv_query($cn->getConecta(), $sqlLista2, array($tipo, $numdoc));
                if ($stmtLista2 !== false) {
                    $rowLista2 = sqlsrv_fetch_array($stmtLista2, SQLSRV_FETCH_ASSOC);
                    if ($rowLista2) $idListaReal = $rowLista2['idLista'];
                }
            }

            // 2c. Determinar lista efectiva: la del cliente si existe y > 0, si no la predeterminada
            $idLista = ($idListaReal !== null && (int)$idListaReal > 0)
                       ? (int)$idListaReal
                       : $ID_LISTA_DEFAULT;

            // 3. Precio en la lista efectiva del cliente
            // Columnas reales en Producto_Pre: precio (valor), IdPrecio (id de lista)
            $precio     = 0;
            $listaUsada = $idLista;
            $sqlPrecio  = "SELECT TOP 1 precio FROM Producto_Pre
                           WHERE IdProducto = ? AND IdPrecio = ? ORDER BY Fecha DESC";
            $stmtPrecio = sqlsrv_query($cn->getConecta(), $sqlPrecio, array((int)$idProducto, $idLista));
            if ($stmtPrecio !== false) {
                $rowPrecio = sqlsrv_fetch_array($stmtPrecio, SQLSRV_FETCH_ASSOC);
                if ($rowPrecio) $precio = (float)$rowPrecio['precio'];
            }

            // 3b. Si no hay precio en la lista del cliente, intentar con la predeterminada (si era distinta)
            if ($precio == 0 && $idLista !== $ID_LISTA_DEFAULT) {
                $stmtFb = sqlsrv_query($cn->getConecta(), $sqlPrecio, array((int)$idProducto, $ID_LISTA_DEFAULT));
                if ($stmtFb !== false) {
                    $rowFb = sqlsrv_fetch_array($stmtFb, SQLSRV_FETCH_ASSOC);
                    if ($rowFb) { $precio = (float)$rowFb['precio']; $listaUsada = $ID_LISTA_DEFAULT; }
                }
            }

            // 3c. Último recurso: precio más reciente del producto en CUALQUIER lista
            if ($precio == 0) {
                $sqlGlobal  = "SELECT TOP 1 precio, IdPrecio FROM Producto_Pre
                               WHERE IdProducto = ? ORDER BY Fecha DESC";
                $stmtGlobal = sqlsrv_query($cn->getConecta(), $sqlGlobal, array((int)$idProducto));
                if ($stmtGlobal !== false) {
                    $rowGlobal = sqlsrv_fetch_array($stmtGlobal, SQLSRV_FETCH_ASSOC);
                    if ($rowGlobal) {
                        $precio     = (float)$rowGlobal['precio'];
                        $listaUsada = (int)$rowGlobal['IdPrecio'];
                    }
                }
            }

            // Diagnóstico: qué listas (IdPrecio) tiene este producto en Producto_Pre
            $listasDisponibles = array();
            $sqlListas  = "SELECT DISTINCT IdPrecio FROM Producto_Pre WHERE IdProducto = ? ORDER BY IdPrecio";
            $stmtListas = sqlsrv_query($cn->getConecta(), $sqlListas, array((int)$idProducto));
            if ($stmtListas !== false) {
                while ($r = sqlsrv_fetch_array($stmtListas, SQLSRV_FETCH_ASSOC)) {
                    $listasDisponibles[] = (int)$r['IdPrecio'];
                }
            }

            return json_encode(array(
                "status"              => "success",
                "nombre"              => $rowProd['Producto'],
                "porcentaje_impuesto" => (float)$rowProd['PorcentajeImpuesto'],
                "precio"              => $precio,
                // campos de diagnóstico (para F12 → consola)
                "_debug" => array(
                    "idLista_cliente"     => $idListaReal,
                    "idLista_usado"       => $listaUsada,
                    "listas_disponibles"  => $listasDisponibles,
                    "nit_recibido"        => $nit,
                    "dir_recibida"        => $direccion,
                )
            ));
        }

        public function agregar_linea_manual($tipo, $numdoc, $idProducto, $cantidad, $valorUnitario, $lote, $fechaVence, $porcentajeImpuesto = 0, $nota = '') {
            $cn = new Conectarserver;

            $porcentajeImpuesto = (float)$porcentajeImpuesto;
            $valorImpuesto      = round(($porcentajeImpuesto / 100) * (float)$valorUnitario, 2);

            $sql_seq = "SELECT ISNULL(MAX(seq), 0) + 1 AS next_seq FROM Documentos_Lin WHERE tipo = '$tipo' AND Numero_documento = '$numdoc'";
            $stmt = sqlsrv_query($cn->getConecta(), $sql_seq);
            if ($stmt === false) {
                return json_encode(array("status" => "error", "message" => "Error al obtener secuencia"));
            }
            $row_seq = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            $seq = $row_seq ? (int)$row_seq['next_seq'] : 1;

            $fechaVence = $fechaVence ?: date('Y-m-d');
            $notaEscapada = str_replace("'", "''", $nota);

            $sql = "INSERT INTO Documentos_Lin (sw, tipo, seq, modelo, Numero_Documento, Numero_Docto_Base, Numero_Lote,
            Nit_Cedula, Codigo_Direccion, Fecha_Documento, IdProducto, IdUnidad, Factor_Conversion,
            Cantidad_Facturada, Cantidad_Pendiente, Cantidad_Orden, Costo_Unitario, Valor_Unitario,
            Valor_Impuesto, Porcentaje_Impuesto, Porcentaje_Descuento_1, Porcentaje_Descuento_2, Porcentaje_Descuento_3,
            IdVendedor, Comision_Vendedor, Valor_Comision_Vendedor, IdBodega, Maneja_Inventario, Tomador,
            IdMoneda, Tasa_Moneda_Ext, CentroDeCostosDoc, Nota_Linea, Unidades, Fecha_Vence, Exportado,
            Costo_Unitario_Inicial, Porcentaje_ReteFuente, Envase, Numero_Lote_Destino, serial, Impuesto_Consumo,
            Porcentaje_ReteFuente_2, Porcentaje_ReteFuente_3, Porcentaje_ReteFuente_4,
            Emp_1, Emp_2, Emp_3, Emp_4, Emp_5, Emp_6, Emp_7, Emp_8,
            Tara_1, Tara_2, Tara_3, Tara_4, Tara_5, Tara_6, Tara_7, Tara_8)

            SELECT td.tipo, '$tipo', $seq, p.contable, $numdoc, '', '$lote',
            d.nit_Cedula, d.codigo_direccion, GETDATE(), $idProducto, ISNULL(p.unidad_inventario, 1), 1,
            $cantidad, 0, $cantidad, $valorUnitario, $valorUnitario,
            $valorImpuesto, $porcentajeImpuesto, 0, 0, 0,
            0, 0, 0, td.IdBodega, 'S', '',
            1, 1, '0', '$notaEscapada', 1, CONVERT(DATE, '$fechaVence', 23), 'N',
            $valorUnitario, 0, 0, 0, '', 0,
            0, 0, 0,
            0, 0, 0, 0, 0, 0, 0, 0,
            0, 0, 0, 0, 0, 0, 0, 0

            FROM TblTipoDoctos td, TblProducto p, Documentos d
            WHERE td.idTipoDoctos = '$tipo' AND p.IdProducto = $idProducto
            AND d.tipo = '$tipo' AND d.Numero_documento = $numdoc";

            $registros = sqlsrv_prepare($cn->getConecta(), $sql);
            if (sqlsrv_execute($registros) === false) {
                $this->registrar_error("Error en agregar_linea_manual: " . print_r(sqlsrv_errors(), true));
                return json_encode(array("status" => "error", "message" => "Error al agregar la línea: " . print_r(sqlsrv_errors(), true)));
            }

            // Actualizar totales en cabecera después de agregar la línea
            $sql_totales = "UPDATE Documentos SET 
                Total_Items = (SELECT COUNT(*) FROM Documentos_Lin WHERE tipo = '$tipo' AND Numero_documento = $numdoc),
                Valor_impuesto = (SELECT ISNULL(SUM(((dl.Cantidad_Facturada * dl.Valor_Unitario) * (1 - ISNULL(dl.Porcentaje_Descuento_1, 0) / 100)) * (ISNULL(dl.Porcentaje_Impuesto, 0) / 100)), 0) FROM Documentos_Lin dl WHERE dl.tipo = '$tipo' AND dl.Numero_documento = $numdoc),
                valor_total = (SELECT ISNULL(SUM(ROUND((dl.Cantidad_Facturada * dl.Valor_Unitario) * (1 - ISNULL(dl.Porcentaje_Descuento_1, 0) / 100), 2) + ((dl.Cantidad_Facturada * dl.Valor_Unitario) * (1 - ISNULL(dl.Porcentaje_Descuento_1, 0) / 100)) * (ISNULL(dl.Porcentaje_Impuesto, 0) / 100)), 0) FROM Documentos_Lin dl WHERE dl.tipo = '$tipo' AND dl.Numero_documento = $numdoc),
                valor_aplicado = (SELECT ISNULL(SUM(ROUND((dl.Cantidad_Facturada * dl.Valor_Unitario) * (1 - ISNULL(dl.Porcentaje_Descuento_1, 0) / 100), 2) + ((dl.Cantidad_Facturada * dl.Valor_Unitario) * (1 - ISNULL(dl.Porcentaje_Descuento_1, 0) / 100)) * (ISNULL(dl.Porcentaje_Impuesto, 0) / 100)), 0) FROM Documentos_Lin dl WHERE dl.tipo = '$tipo' AND dl.Numero_documento = $numdoc),
                costo = (SELECT ISNULL(SUM(ROUND((dl.Cantidad_Facturada * dl.Valor_Unitario) * (1 - ISNULL(dl.Porcentaje_Descuento_1, 0) / 100), 2) + ((dl.Cantidad_Facturada * dl.Valor_Unitario) * (1 - ISNULL(dl.Porcentaje_Descuento_1, 0) / 100)) * (ISNULL(dl.Porcentaje_Impuesto, 0) / 100)), 0) FROM Documentos_Lin dl WHERE dl.tipo = '$tipo' AND dl.Numero_documento = $numdoc)
                WHERE tipo = '$tipo' AND Numero_Documento = $numdoc";
            
            $registros_tot = sqlsrv_prepare($cn->getConecta(), $sql_totales);
            sqlsrv_execute($registros_tot);

            return json_encode(array("status" => "success", "message" => "Línea agregada correctamente"));
        }

        private function limpiar_namespaces_xml($xml) {
            // 1. Quitar declaraciones de namespace: xmlns="..." y xmlns:prefix="..."
            $xml = preg_replace('/\s+xmlns(?::\w+)?="[^"]*"/', '', $xml);
            // 2. Quitar atributos con prefijo de namespace: mc:Ignorable="...", x14ac:dyDescent="...", xr:uid="..."
            $xml = preg_replace('/\s+\w+:\w+="[^"]*"/', '', $xml);
            return $xml;
        }

        private function leer_xlsx($filePath) {
            if (!class_exists('ZipArchive')) {
                return ['error' => 'ZipArchive no disponible en el servidor'];
            }
            $zip = new ZipArchive();
            if ($zip->open($filePath) !== true) {
                return ['error' => 'No se pudo abrir el archivo Excel'];
            }

            libxml_use_internal_errors(true); // suprimir warnings de namespace en stderr/output

            // Shared strings
            $sharedStrings = [];
            $ssRaw = $zip->getFromName('xl/sharedStrings.xml');
            if ($ssRaw) {
                $ssRaw = $this->limpiar_namespaces_xml($ssRaw);
                $ss    = simplexml_load_string($ssRaw, 'SimpleXMLElement', LIBXML_NOERROR | LIBXML_NOWARNING);
                if ($ss) {
                    foreach ($ss->si as $si) {
                        $text = '';
                        if (isset($si->r)) {
                            foreach ($si->r as $r) {
                                $text .= (string)$r->t;
                            }
                        }
                        if ($text === '' && isset($si->t)) {
                            $text = (string)$si->t;
                        }
                        $sharedStrings[] = $text;
                    }
                }
            }

            $sheetRaw = $zip->getFromName('xl/worksheets/sheet1.xml');
            $zip->close();
            if (!$sheetRaw) {
                return ['error' => 'No se encontró la hoja de cálculo (sheet1)'];
            }

            $sheetRaw = $this->limpiar_namespaces_xml($sheetRaw);
            $sheet    = simplexml_load_string($sheetRaw, 'SimpleXMLElement', LIBXML_NOERROR | LIBXML_NOWARNING);
            if (!$sheet || !isset($sheet->sheetData)) {
                return ['error' => 'No se pudo leer el contenido de la hoja'];
            }

            $rows = [];
            foreach ($sheet->sheetData->row as $rowNode) {
                $rowData = [];
                foreach ($rowNode->c as $cell) {
                    $ref = (string)$cell['r'];
                    preg_match('/^([A-Z]+)/', $ref, $m);
                    $colLetter = $m[1];
                    $colIndex  = 0;
                    for ($k = 0; $k < strlen($colLetter); $k++) {
                        $colIndex = $colIndex * 26 + (ord($colLetter[$k]) - 64);
                    }
                    $colIndex--; // 0-based

                    while (count($rowData) < $colIndex) {
                        $rowData[] = '';
                    }

                    $type  = (string)$cell['t'];
                    $value = isset($cell->v) ? (string)$cell->v : '';
                    if ($type === 's') {
                        $idx   = (int)$value;
                        $value = isset($sharedStrings[$idx]) ? $sharedStrings[$idx] : '';
                    }
                    $rowData[] = trim($value);
                }
                $rows[] = $rowData;
            }
            return $rows;
        }

        public function cargar_masiva_excel($tipo, $numdoc, $nit, $direccion, $filePath) {
            $rows = $this->leer_xlsx($filePath);
            if (isset($rows['error'])) {
                return json_encode(['status' => 'error', 'message' => $rows['error']]);
            }
            if (count($rows) < 2) {
                return json_encode(['status' => 'error', 'message' => 'El archivo no contiene datos (solo encabezado o vacío)']);
            }

            $cn         = new Conectarserver;
            $resultados = [];
            $procesados = []; // duplicados dentro del mismo archivo

            $ID_LISTA_DEFAULT = 50;

            // Obtener idLista del cliente una sola vez
            $idListaReal = null;
            if ($nit !== '' && $direccion !== '') {
                $sqlL = "SELECT TOP 1 idLista FROM Terceros_Dir WHERE nit = ? AND codigo_direccion = ?";
                $stL  = sqlsrv_query($cn->getConecta(), $sqlL, [$nit, (int)$direccion]);
                if ($stL !== false) {
                    $rL = sqlsrv_fetch_array($stL, SQLSRV_FETCH_ASSOC);
                    if ($rL) $idListaReal = $rL['idLista'];
                }
            }
            if ($idListaReal === null && $tipo && $numdoc) {
                $sqlL2 = "SELECT TOP 1 td.idLista FROM Documentos d
                          INNER JOIN Terceros_Dir td ON td.nit = d.nit_Cedula AND td.codigo_direccion = d.codigo_direccion
                          WHERE d.tipo = ? AND d.Numero_documento = ?";
                $stL2  = sqlsrv_query($cn->getConecta(), $sqlL2, [$tipo, $numdoc]);
                if ($stL2 !== false) {
                    $rL2 = sqlsrv_fetch_array($stL2, SQLSRV_FETCH_ASSOC);
                    if ($rL2) $idListaReal = $rL2['idLista'];
                }
            }
            $idLista = ($idListaReal !== null && (int)$idListaReal > 0) ? (int)$idListaReal : $ID_LISTA_DEFAULT;

            // Conexión DEV para lotes (se abre una vez)
            $cnDev = null;
            require_once(dirname(__FILE__) . '/../config/conexiondev.php');
            $devConn = new ConectarDev();
            if ($devConn->getConecta()) $cnDev = $devConn->getConecta();

            // Procesar desde fila 2 (índice 1), fila 1 es encabezado
            for ($i = 1; $i < count($rows); $i++) {
                $row        = $rows[$i];
                $idProducto = trim($row[0] ?? '');
                $cantidad   = trim($row[1] ?? '');
                $nota       = trim($row[2] ?? '');
                $lote       = trim($row[3] ?? '');

                if ($idProducto === '' && $cantidad === '') continue; // fila vacía

                $resultado = [
                    'fila'       => $i + 1,
                    'idProducto' => $idProducto,
                    'cantidad'   => $cantidad,
                    'lote'       => $lote,
                    'status'     => 'error',
                    'mensaje'    => ''
                ];

                if ($idProducto === '') {
                    $resultado['mensaje'] = 'IdProducto vacío';
                    $resultados[] = $resultado; continue;
                }
                if (!is_numeric($cantidad) || (float)$cantidad <= 0) {
                    $resultado['mensaje'] = 'Cantidad debe ser un número mayor a 0';
                    $resultados[] = $resultado; continue;
                }

                $clave = $idProducto . '|' . $lote;
                if (in_array($clave, $procesados)) {
                    $resultado['mensaje'] = 'Producto+Lote duplicado dentro del archivo';
                    $resultados[] = $resultado; continue;
                }

                // Validar producto
                $sqlProd = "SELECT p.Producto, ISNULL(i.PorcentajeImpuesto, 0) AS PorcentajeImpuesto
                            FROM TblProducto p
                            LEFT JOIN TblImpuesto i ON p.Impuesto_venta = i.IdImpuesto
                            WHERE p.IdProducto = ?";
                $stProd  = sqlsrv_query($cn->getConecta(), $sqlProd, [(int)$idProducto]);
                if ($stProd === false) {
                    $resultado['mensaje'] = 'Error al consultar producto';
                    $resultados[] = $resultado; continue;
                }
                $rProd = sqlsrv_fetch_array($stProd, SQLSRV_FETCH_ASSOC);
                if (!$rProd) {
                    $resultado['mensaje'] = 'Producto no existe en el sistema';
                    $resultados[] = $resultado; continue;
                }
                $porcentajeImpuesto = (float)$rProd['PorcentajeImpuesto'];
                $nombreProducto     = $rProd['Producto'];

                // Validar lote (si fue ingresado)
                if ($lote !== '' && $cnDev !== null) {
                    $sqlLote = "SELECT COUNT(*) AS cnt FROM cvapptblfarmbatch WHERE numberBatch = ? AND statusBatch = 'S'";
                    $stLote  = sqlsrv_query($cnDev, $sqlLote, [$lote]);
                    if ($stLote !== false) {
                        $rLote = sqlsrv_fetch_array($stLote, SQLSRV_FETCH_ASSOC);
                        if (!$rLote || (int)$rLote['cnt'] === 0) {
                            $resultado['mensaje'] = 'Lote "' . $lote . '" no válido o inactivo';
                            $resultados[] = $resultado; continue;
                        }
                    }
                }

                // Precio
                $precio    = 0;
                $sqlPrec   = "SELECT TOP 1 precio FROM Producto_Pre WHERE IdProducto = ? AND IdPrecio = ? ORDER BY Fecha DESC";
                $stPrec    = sqlsrv_query($cn->getConecta(), $sqlPrec, [(int)$idProducto, $idLista]);
                if ($stPrec !== false) {
                    $rPrec = sqlsrv_fetch_array($stPrec, SQLSRV_FETCH_ASSOC);
                    if ($rPrec) $precio = (float)$rPrec['precio'];
                }
                if ($precio == 0 && $idLista !== $ID_LISTA_DEFAULT) {
                    $stFb = sqlsrv_query($cn->getConecta(), $sqlPrec, [(int)$idProducto, $ID_LISTA_DEFAULT]);
                    if ($stFb !== false) {
                        $rFb = sqlsrv_fetch_array($stFb, SQLSRV_FETCH_ASSOC);
                        if ($rFb) $precio = (float)$rFb['precio'];
                    }
                }
                if ($precio == 0) {
                    $sqlGlob = "SELECT TOP 1 precio FROM Producto_Pre WHERE IdProducto = ? ORDER BY Fecha DESC";
                    $stGlob  = sqlsrv_query($cn->getConecta(), $sqlGlob, [(int)$idProducto]);
                    if ($stGlob !== false) {
                        $rGlob = sqlsrv_fetch_array($stGlob, SQLSRV_FETCH_ASSOC);
                        if ($rGlob) $precio = (float)$rGlob['precio'];
                    }
                }

                $loteVal  = $lote !== '' ? $lote : '0';
                $insertar = $this->agregar_linea_manual(
                    $tipo, $numdoc, $idProducto, (float)$cantidad,
                    $precio, $loteVal, date('Y-m-d'), $porcentajeImpuesto, $nota
                );
                $ins = json_decode($insertar, true);
                if ($ins && $ins['status'] === 'success') {
                    $procesados[] = $clave;
                    $resultado['status']  = 'ok';
                    $resultado['mensaje'] = htmlspecialchars($nombreProducto) .
                        ' | Precio: $' . number_format($precio, 2, ',', '.');
                } else {
                    $resultado['mensaje'] = 'Error al insertar línea: ' . ($ins['message'] ?? 'desconocido');
                }
                $resultados[] = $resultado;
            }

            $ok    = count(array_filter($resultados, function($r) { return $r['status'] === 'ok'; }));
            $error = count(array_filter($resultados, function($r) { return $r['status'] === 'error'; }));

            return json_encode([
                'status'     => 'success',
                'ok'         => $ok,
                'error'      => $error,
                'resultados' => $resultados
            ]);
        }

        public function combo_lotes() {
            require_once(dirname(__FILE__) . '/../config/conexiondev.php');
            $cnDev = new ConectarDev();
            if (!$cnDev->getConecta()) {
                return "<option value=''>Error de conexión DEV</option>";
            }
            $sql = "SELECT DISTINCT numberBatch FROM cvapptblfarmbatch WHERE statusBatch = 'S' ORDER BY numberBatch ASC";
            $stmt = sqlsrv_query($cnDev->getConecta(), $sql);
            if ($stmt === false) {
                return "<option value=''>Error en consulta lotes</option>";
            }
            $html = "<option value='' disabled selected>Seleccione Lote...</option>";
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $html .= "<option value='".$row['numberBatch']."'>".$row['numberBatch']."</option>";
            }
            return $html;
        }

        public function validar_os($numero) {
            $cn = new Conectarserver;
            $resultado = array('status' => 'no_existe', 'documentos' => array(), 'lineas_pendientes' => 0);

            if (empty($numero)) return json_encode($resultado);

            // Verificar que la OS exista y no esté anulada
            $sql_chk = "SELECT COUNT(*) AS existe FROM Documentos_Ped
                        WHERE numero_pedido = ? AND sw = '10' AND CAST(anulado AS int) = 1";
            $stmt_chk = sqlsrv_query($cn->getConecta(), $sql_chk, array($numero));
            if (!$stmt_chk) return json_encode($resultado);
            $row_chk = sqlsrv_fetch_array($stmt_chk, SQLSRV_FETCH_ASSOC);
            if (!$row_chk || $row_chk['existe'] == 0) return json_encode($resultado);

            // Calcular pendientes y totales de forma dinámica
            $sql_pend = "SELECT
                                SUM(CASE WHEN (dlp.cantidad - ISNULL(f.total_facturado, 0)) > 0 THEN 1 ELSE 0 END) AS con_pendiente,
                                SUM(dlp.cantidad) AS total_ordenado,
                                ISNULL(SUM(f.total_facturado), 0) AS total_despachado
                         FROM Documentos_Lin_Ped dlp
                         LEFT JOIN (
                             SELECT dl.IdProducto, SUM(dl.Cantidad_Facturada) AS total_facturado
                             FROM Documentos d
                             JOIN Documentos_Lin dl ON dl.tipo = d.tipo AND dl.Numero_Documento = d.Numero_documento
                             WHERE d.Numero_Docto_Base_2 = '$numero' AND d.Tipo_Docto_Base_2 = '10'
                             GROUP BY dl.IdProducto
                         ) f ON f.IdProducto = dlp.IdProducto
                         WHERE dlp.numero_pedido = '$numero' AND dlp.sw = '10'";
            $stmt_pend = sqlsrv_query($cn->getConecta(), $sql_pend);
            $row_pend  = $stmt_pend ? sqlsrv_fetch_array($stmt_pend, SQLSRV_FETCH_ASSOC) : null;
            $lineas_pendientes = (int)($row_pend['con_pendiente'] ?? 0);
            $total_ordenado   = (float)($row_pend['total_ordenado'] ?? 0);
            $total_despachado = (float)($row_pend['total_despachado'] ?? 0);

            $resultado['lineas_pendientes'] = $lineas_pendientes;
            $resultado['total_ordenado']    = $total_ordenado;
            $resultado['total_despachado']  = $total_despachado;
            $resultado['status'] = ($lineas_pendientes === 0) ? 'finalizado' : 'pendiente';

            // Documentos ya generados desde esta OS
            $sql_docs = "SELECT tt.TipoDoctos, d.Numero_documento, d.Fecha_Hora_Factura,
                                CASE d.exportado
                                    WHEN 'S' THEN 'Guardado'
                                    ELSE 'Sin guardar'
                                END AS estado
                         FROM Documentos d
                         JOIN TblTipoDoctos tt ON tt.idTipoDoctos = d.tipo
                         WHERE d.Numero_Docto_Base_2 = ? AND d.Tipo_Docto_Base_2 = '10'
                         ORDER BY d.Numero_documento DESC";
            $stmt_docs = sqlsrv_query($cn->getConecta(), $sql_docs, array($numero));
            if ($stmt_docs) {
                while ($doc = sqlsrv_fetch_array($stmt_docs, SQLSRV_FETCH_ASSOC)) {
                    $fecha_doc = '';
                    if ($doc['Fecha_Hora_Factura'] instanceof DateTime) {
                        $fecha_doc = date_format($doc['Fecha_Hora_Factura'], "d/m/Y");
                    }
                    $resultado['documentos'][] = array(
                        'tipo'   => $doc['TipoDoctos'],
                        'numero' => $doc['Numero_documento'],
                        'fecha'  => $fecha_doc,
                        'estado' => $doc['estado']
                    );
                }
            }

            return json_encode($resultado);
        }

    }
?>
