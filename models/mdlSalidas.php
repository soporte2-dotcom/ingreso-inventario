<?php
    class Salidas extends Conectarserver{

      public function listar_salidas_x_usuario($usuario){
        $cn = new Conectarserver;
        $resultado = array();

        if($usuario == 'LAUREN' || $usuario == 'SA'){

            $sql="SELECT d.Fecha_Hora_Factura, d.tipo, tt.TipoDoctos, d.Numero_documento, d.Numero_Docto_Base, d.Tipo_Docto_Base_2, d.Numero_Docto_Base_2,
                d.nit_Cedula, d.Nombre_Cliente, d.codigo_direccion, td.direccion, td.telefono_1, d.exportado, d.usuario

                FROM Documentos d, Terceros_Dir td, TblTipoDoctos tt, TblTerceros t

                WHERE CONVERT(date, Fecha_Hora_Factura) > '2026/01/01' AND tt.tipo IN ('11', '2')
                AND tt.idTipoDoctos = d.tipo AND td.nit = d.nit_Cedula AND d.codigo_direccion = td.codigo_direccion
                AND t.nit_cedula = d.nit_Cedula
                ORDER BY d.Fecha_Hora_Factura DESC";

            $registros = sqlsrv_query($cn->getConecta(), $sql);

        } else {

            $sql="SELECT d.Fecha_Hora_Factura, d.tipo, tt.TipoDoctos, d.Numero_documento, d.Numero_Docto_Base, d.Tipo_Docto_Base_2, d.Numero_Docto_Base_2,
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

            $sql="SELECT d.tipo, tt.TipoDoctos, d.Numero_documento, d.Numero_Docto_Base, d.Tipo_Docto_Base_2, d.Numero_Docto_Base_2, 
            d.nit_Cedula, d.Nombre_Cliente, d.codigo_direccion, td.direccion, td.telefono_1, 
            d.nit_Cedula_2, t.nombre AS nombre2, d.codigo_direccion_2, td2.direccion AS direccion2, d.notas, d.exportado
            FROM Documentos d, Terceros_Dir td, TblTipoDoctos tt, TblTerceros t, Terceros_Dir td2
            WHERE d.tipo = '$tipo' AND d.Numero_documento = '$consecutivo' AND tt.idTipoDoctos = d.tipo AND
            td.nit = d.nit_Cedula AND d.codigo_direccion = td.codigo_direccion AND
            td2.nit = d.nit_Cedula_2 AND d.codigo_direccion_2 = td2.codigo_direccion AND t.nit_cedula = d.nit_Cedula_2";

            $registros = sqlsrv_query($cn->getConecta(), $sql);
            if( $registros === false ){
                echo "Error al ejecutar consulta.\n";
            }  else {
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
                while($stmt= sqlsrv_fetch_array($registros)) {
                    $resultado[] = $stmt;                   
                }
                return $resultado;
            }
        }


        /***         ENTRADAS         ***/

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

                $sql="INSERT INTO Documentos(sw, tipo, modelo, Numero_Documento, Numero_Docto_Base,
                nit_Cedula, codigo_direccion, Fecha_Hora_Factura,Fecha_Hora_Vencimiento,Fecha_orden_Venta,
                condicion,valor_total, valor_aplicado, Retencion_1,Retencion_2, Retencion_3, retencion_causada, retencion_iva,retencion_ica,
                retencion_descuento, descuento_pie, DescuentoOrdenVenta, descuento_1, descuento_2, descuento_3, costo, IdVendedor, anulado, usuario,
                notas,pc, fecha_hora, duracion, bodega, Valor_impuesto, Impuesto_Consumo, impuesto_deporte, concepto, vencimiento_presup, 
                exportado, prefijo, moneda, CentroDeCostosDoc, valor_mercancia, abono, Comision_Vendedor, Tasa_Moneda_Ext, Tomador, Tasa_Fija_o_Variable, Punto_FOB,
                Fletes_Moneda_Ext, Miselaneos_Moneda_Ext, Cargo_Por_Fletes, Impuesto_Por_Fletes, Total_Items, Nombre_Cliente, Ordenado_Por, Telefono_De_Envio_1,
                Telefono_De_Envio_2, Factura_Impresa, IdFormaEnvio, IdTransportador, nit_Cedula_2, codigo_direccion_2, Numero_Docto_Base_2, Tipo_Docto_Base, 
                Tipo_Docto_Base_2, IdActividadEconomica, TarifaReteFuenteCree, Valor_ReteCree, IdVehiculo)
                
                (SELECT td.tipo AS sw, '$tipo' AS tipo, '$tipo' AS modelo, (c.siguiente+1) AS Numero_Documento, '' AS Numero_Docto_Base,
                dp.nit AS nit_Cedula, dp.direccion_factura AS codigo_direccion,  GETDATE() AS Fecha_Hora_Factura, GETDATE() AS Fecha_Hora_Vencimiento, GETDATE() AS Fecha_orden_Venta,
                t.condicion AS condicion, dp.valor_total AS valor_total, dp.valor_total AS valor_aplicado, dp.Retencion_1 AS Retencion_1, 0 AS Retencion_2, 0 AS Retencion_3, 
                0 AS retencion_causada, 0 AS retencion_iva, 0 AS retencion_ica, 0 AS retencion_descuento, 0 AS descuento_pie, 0 AS DescuentoOrdenVenta, 0 AS descuento_1, 0 AS descuento_2,
                0 AS descuento_3, 0 AS costo, dp.vendedor AS idVendedor, 'N' AS anulado, '$usuario' AS usuario, dp.notas AS notas, HOST_NAME() AS pc, GETDATE() AS fecha_hora, 
                0 AS duracion, td.IdBodega AS bodega, 0 AS Valor_impuesto, 0 AS Impuesto_Consumo, 0 AS impuesto_deporte, dp.concepto AS concepto, GETDATE() AS vencimiento_presup, 
                'N' AS exportado, '0' AS prefijo, dp.moneda AS moneda, 0 AS CentroDeCostosDoc, 0 AS valor_mercancia, 0 AS abono, 0 AS Comision_Vendedor, 
                1 AS Tasa_Moneda_Ext, '' AS Tomador, 'V' AS Tasa_Fija_o_Variable, dir.idLista AS Punto_FOB,
                0 AS Fletes_Moneda_Ext, 0 AS Miselaneos_Moneda_Ext, 0 AS Cargo_Por_Fletes, 0 AS Impuesto_Por_Fletes, 2 AS Total_Items, t.nombre AS Nombre_Cliente, 
                SUBSTRING(dp.Contacto_Compras,0,20) AS Ordenado_Por, dp.telefono1 AS Telefono_De_Envio_1, '' AS Telefono_De_Envio_2, 'N' AS Factura_Impresa, dp.IdFormaEnvio AS IdFormaEnvio, dp.IdTRansportador AS IdTransportador, 
                dp.nit_destino AS nit_Cedula_2, dp.direccion_entrega AS codigo_direccion_2, '$numero' AS Numero_Docto_Base_2, '0' AS Tipo_Docto_Base, 
                '9' AS Tipo_Docto_Base_2, '0' AS IdActividadEconomica, 0 AS TarifaReteFuenteCree, 0 AS Valor_ReteCree, '1' AS IdVehiculo           
                
                FROM Documentos_Ped dp, TblTerceros t, TblTipoDoctos td, Terceros_Dir dir, consecutivos c      
                WHERE c.tipo = '$tipo' AND td.idTipoDoctos = '$tipo' AND
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
                
                (SELECT td.tipo AS sw, '$tipo' AS tipo, dp.Linea AS seq, p.contable AS Modelo, (c.siguiente+1) AS Numero_Documento,
                '' AS Numero_Docto_Base, '0' AS Numero_Lote, dp.IdCliente AS Nit_Cedula, dp.DireccionFactura AS codigo_direccion,  GETDATE() AS Fecha_Documento,
                dp.IdProducto AS IdProducto, dp.und AS IdUnidad, '1' AS Factor_Conversion,  dp.cantidad AS Cantidad_Facturada,
                0 AS Cantidad_Pendiente, dp.cantidad AS Cantidad_Orden, dp.valor_unitario AS Costo_Unitario, dp.valor_unitario AS Valor_Unitario, 
                ((dp.porcentaje_iva/100) * dp.valor_unitario) AS Valor_Impuesto, dp.porcentaje_iva AS Porcentaje_Impuesto, dp.porcentaje_descuento AS Porcentaje_Descuento_1,
                dp.porc_dcto_2 AS Porcentaje_Descuento_2, dp.porc_dcto_3 AS Porcentaje_Descuento_3, dp.IdVendedor AS IdVendedor, 0 AS Comision_Vendedor, 0 AS Valor_Comision_Vendedor,
                td.IdBodega AS IdBodega, 'S' AS Maneja_Inventario, '' AS Tomador, 1 AS IdMoneda, 1 AS Tasa_Moneda_Ext, '0' AS CentroDeCostosDoc,
                ' ' AS Nota_Linea, '1' AS Unidades, GETDATE() AS Fecha_Vence, 'N' AS Exportado, dp.valor_unitario AS Costo_Unitario_Inicial,
                dp.Porcentaje_ReteFuente AS Porcentaje_ReteFuente, 0 AS Envase, 0 AS Numero_Lote_Destino, '' AS serial, 0 AS Impuesto_Consumo, 0 AS Porcentaje_ReteFuente_2,
                0 AS Porcentaje_ReteFuente_3, 0 AS Porcentaje_ReteFuente_4, 0 AS Emp_1, 0 AS Emp_2, 0 AS Emp_3, 0 AS Emp_4, 0 AS Emp_5, 0 AS Emp_6,
                0 AS Emp_7, 0 AS Emp_8, 0 AS Tara_1, 0 AS Tara_2, 0 AS Tara_3, 0 AS Tara_4, 0 AS Tara_5, 0 AS Tara_6, 0 AS Tara_7, 0 AS Tara_8
                                                                
                FROM  consecutivos c, Documentos_Lin_Ped dp, TblTipoDoctos td, TblProducto p
                                        
                WHERE c.tipo = '$tipo' AND td.idTipoDoctos = c.tipo AND p.IdProducto = dp.IdProducto
                AND dp.numero_pedido = '$numero' AND dp.sw = 9)";
               
                $registros =  sqlsrv_prepare($cn->getConecta(), $sql1);            
                if(sqlsrv_execute($registros) === false) {
                    throw new Exception("Error al insertar detalle del documento: " . print_r(sqlsrv_errors(), true));
                }

                $sql2="UPDATE Consecutivos SET siguiente = siguiente+1 WHERE tipo = '$tipo' ";                
                $registros =  sqlsrv_prepare($cn->getConecta(), $sql2);
                if(sqlsrv_execute($registros) === false) {
                    throw new Exception("Error al actualizar consecutivo: " . print_r(sqlsrv_errors(), true));
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


        public function guardar_salida($tipo, $numdoc, $nit1, $direccion1, $nit2, $direccion2, $despacho, $notas, $dotacion = false){
            $cn = new Conectarserver;

            $idVendedorSql = $dotacion ? ", IdVendedor = 12" : "";

            $sql = "UPDATE Documentos SET
                nit_Cedula = '$nit1', codigo_direccion = '$direccion1',
                nit_Cedula_2 = '$nit2', codigo_direccion_2 = '$direccion2',
                Numero_Docto_Base = '$despacho', notas = '$notas', exportado = 'S' $idVendedorSql,
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

            $sql2 = "(EXEC UPDATE_PRODUCTO_STO)";
            $registros = sqlsrv_prepare($cn->getConecta(), $sql2);
            sqlsrv_execute($registros);
        }

        public function update_lote_salida($tipo, $numdoc, $lote){
            $cn = new Conectarserver;
            $sql = "UPDATE Documentos_Lin SET Numero_Lote = ? WHERE tipo = ? AND Numero_documento = ?";
            $params = array($lote, $tipo, $numdoc);
            $stmt = sqlsrv_query($cn->getConecta(), $sql, $params);
            if($stmt === false){
                echo "Error al actualizar lote";
            } else {
                echo "Lote actualizado correctamente";
            }
        }

        public function insert_salida_traslado($tipo, $numero, $tiporef, $usuario){
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
                0 AS costo, d.IdVendedor AS IdVendedor, 'N' AS anulado, '$usuario' AS usuario,
                d.notas AS notas, HOST_NAME() AS pc, GETDATE() AS fecha_hora, 0 AS duracion, td.IdBodega AS bodega, 0 AS Valor_impuesto, 0 AS Impuesto_Consumo,
                0 AS impuesto_deporte, d.concepto AS concepto, GETDATE() AS vencimiento_presup,
                'N' AS exportado, '0' AS prefijo, d.moneda AS moneda, 0 AS CentroDeCostosDoc, 0 AS valor_mercancia, 0 AS abono, 0 AS Comision_Vendedor,
                1 AS Tasa_Moneda_Ext, '' AS Tomador, 'V' AS Tasa_Fija_o_Variable, d.Punto_FOB AS Punto_FOB,
                0 AS Fletes_Moneda_Ext, 0 AS Miselaneos_Moneda_Ext, 0 AS Cargo_Por_Fletes, 0 AS Impuesto_Por_Fletes, d.Total_Items AS Total_Items, d.Nombre_Cliente AS Nombre_Cliente,
                SUBSTRING(d.Ordenado_Por,0,20) AS Ordenado_Por, d.Telefono_De_Envio_1 AS Telefono_De_Envio_1, d.Telefono_De_Envio_2 AS Telefono_De_Envio_2, 'N' AS Factura_Impresa, d.IdFormaEnvio AS IdFormaEnvio, d.IdTRansportador AS IdTransportador,
                d.nit_Cedula_2 AS nit_Cedula_2, d.codigo_direccion_2 AS codigo_direccion_2, d.Numero_Docto_Base_2 AS Numero_Docto_Base_2, '$tiporef' AS Tipo_Docto_Base,
                '' AS Tipo_Docto_Base_2, d.IdActividadEconomica AS IdActividadEconomica, d.TarifaReteFuenteCree AS TarifaReteFuenteCree, d.Valor_ReteCree AS Valor_ReteCree, d.IdVehiculo AS IdVehiculo

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
                '' AS Numero_Docto_Base, '0' AS Numero_Lote, dl.Nit_Cedula AS Nit_Cedula, dl.codigo_direccion AS codigo_direccion,  GETDATE() AS Fecha_Documento,
                dl.IdProducto AS IdProducto, dl.IdUnidad AS IdUnidad, '1' AS Factor_Conversion, Cantidad_Facturada AS Cantidad_Facturada,
                (dl.Cantidad_Facturada)* -1 AS Cantidad_Pendiente, dl.Cantidad_Orden AS Cantidad_Orden,
                dl.Costo_Unitario AS Costo_Unitario, dl.valor_unitario AS Valor_Unitario, 0 AS Valor_Impuesto, dl.Porcentaje_Impuesto AS Porcentaje_Impuesto,
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

                FROM  consecutivos c, Documentos_Lin dl, Documentos d, TblTerceros t, TblTipoDoctos td, TblProducto p, TblRetencion r

                WHERE c.tipo = $tipo AND dl.Numero_documento = '$numero' AND dl.tipo = '$tiporef'
                AND td.idTipoDoctos = c.tipo AND d.Numero_documento=dl.Numero_Documento AND d.tipo = dl.tipo
                AND dl.Nit_Cedula=t.nit_cedula
                AND p.IdProducto = dl.IdProducto
                AND p.Retencion=r.IdRetencion)";

                $registros = sqlsrv_prepare($cn->getConecta(), $sql1);
                if(sqlsrv_execute($registros) === false) {
                    throw new Exception("Error al insertar detalle del documento: " . print_r(sqlsrv_errors(), true));
                }

                $sql2="UPDATE Consecutivos SET siguiente = siguiente+1 WHERE tipo = '$tipo' ";
                $registros = sqlsrv_prepare($cn->getConecta(), $sql2);
                if(sqlsrv_execute($registros) === false) {
                    throw new Exception("Error al actualizar consecutivo: " . print_r(sqlsrv_errors(), true));
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
                $this->registrar_error("Error en insert_salida_traslado: " . $e->getMessage());
                return json_encode(array(
                    "status" => "error",
                    "message" => $e->getMessage()
                ));
            }
        }

        public function insert_doc_salida($tipo, $numero, $usuario){
            $cn = new Conectarserver;

            try {

                $sql_validar = "SELECT COUNT(*) AS existe FROM Documentos_Ped
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

                (SELECT td.tipo AS sw, '$tipo' AS tipo, '$tipo' AS modelo, (c.siguiente+1) AS Numero_Documento, '' AS Numero_Docto_Base,
                dp.nit AS nit_Cedula, dp.direccion_factura AS codigo_direccion,  GETDATE() AS Fecha_Hora_Factura, GETDATE() AS Fecha_Hora_Vencimiento, GETDATE() AS Fecha_orden_Venta,
                t.condicion AS condicion, dp.valor_total AS valor_total, dp.valor_total AS valor_aplicado, dp.Retencion_1 AS Retencion_1, 0 AS Retencion_2, 0 AS Retencion_3,
                0 AS retencion_causada, 0 AS retencion_iva, 0 AS retencion_ica, 0 AS retencion_descuento, 0 AS descuento_pie, 0 AS DescuentoOrdenVenta, 0 AS descuento_1, 0 AS descuento_2,
                0 AS descuento_3, 0 AS costo, dp.vendedor AS idVendedor, 'N' AS anulado, '$usuario' AS usuario, dp.notas AS notas, HOST_NAME() AS pc, GETDATE() AS fecha_hora,
                0 AS duracion, td.IdBodega AS bodega, 0 AS Valor_impuesto, 0 AS Impuesto_Consumo, 0 AS impuesto_deporte, dp.concepto AS concepto, GETDATE() AS vencimiento_presup,
                'N' AS exportado, '0' AS prefijo, dp.moneda AS moneda, 0 AS CentroDeCostosDoc, 0 AS valor_mercancia, 0 AS abono, 0 AS Comision_Vendedor,
                1 AS Tasa_Moneda_Ext, '' AS Tomador, 'V' AS Tasa_Fija_o_Variable, dir.idLista AS Punto_FOB,
                0 AS Fletes_Moneda_Ext, 0 AS Miselaneos_Moneda_Ext, 0 AS Cargo_Por_Fletes, 0 AS Impuesto_Por_Fletes, 2 AS Total_Items, t.nombre AS Nombre_Cliente,
                SUBSTRING(dp.Contacto_Compras,0,20) AS Ordenado_Por, dp.telefono1 AS Telefono_De_Envio_1, '' AS Telefono_De_Envio_2, 'N' AS Factura_Impresa, dp.IdFormaEnvio AS IdFormaEnvio, dp.IdTRansportador AS IdTransportador,
                dp.nit_destino AS nit_Cedula_2, dp.direccion_entrega AS codigo_direccion_2, '$numero' AS Numero_Docto_Base_2, '0' AS Tipo_Docto_Base,
                '10' AS Tipo_Docto_Base_2, '0' AS IdActividadEconomica, 0 AS TarifaReteFuenteCree, 0 AS Valor_ReteCree, '1' AS IdVehiculo

                FROM Documentos_Ped dp, TblTerceros t, TblTipoDoctos td, Terceros_Dir dir, consecutivos c
                WHERE c.tipo = '$tipo' AND td.idTipoDoctos = '$tipo' AND
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

                (SELECT td.tipo AS sw, '$tipo' AS tipo, dp.Linea AS seq, p.contable AS Modelo, (c.siguiente+1) AS Numero_Documento,
                '' AS Numero_Docto_Base, '0' AS Numero_Lote, dp.IdCliente AS Nit_Cedula, dp.DireccionFactura AS codigo_direccion,  GETDATE() AS Fecha_Documento,
                dp.IdProducto AS IdProducto, dp.und AS IdUnidad, '1' AS Factor_Conversion,  dp.cantidad AS Cantidad_Facturada,
                0 AS Cantidad_Pendiente, dp.cantidad AS Cantidad_Orden, dp.valor_unitario AS Costo_Unitario, dp.valor_unitario AS Valor_Unitario,
                ((dp.porcentaje_iva/100) * dp.valor_unitario) AS Valor_Impuesto, dp.porcentaje_iva AS Porcentaje_Impuesto, dp.porcentaje_descuento AS Porcentaje_Descuento_1,
                dp.porc_dcto_2 AS Porcentaje_Descuento_2, dp.porc_dcto_3 AS Porcentaje_Descuento_3, dp.IdVendedor AS IdVendedor, 0 AS Comision_Vendedor, 0 AS Valor_Comision_Vendedor,
                td.IdBodega AS IdBodega, td.IdBodegaOrigen AS Bodega, 'S' AS Maneja_Inventario, '' AS Tomador, 1 AS IdMoneda, 1 AS Tasa_Moneda_Ext, '0' AS CentroDeCostosDoc,
                ' ' AS Nota_Linea, '1' AS Unidades, GETDATE() AS Fecha_Vence, 'N' AS Exportado, dp.valor_unitario AS Costo_Unitario_Inicial,
                dp.Porcentaje_ReteFuente AS Porcentaje_ReteFuente, 0 AS Envase, 0 AS Numero_Lote_Destino, '' AS serial, 0 AS Impuesto_Consumo, 0 AS Porcentaje_ReteFuente_2,
                0 AS Porcentaje_ReteFuente_3, 0 AS Porcentaje_ReteFuente_4, 0 AS Emp_1, 0 AS Emp_2, 0 AS Emp_3, 0 AS Emp_4, 0 AS Emp_5, 0 AS Emp_6,
                0 AS Emp_7, 0 AS Emp_8, 0 AS Tara_1, 0 AS Tara_2, 0 AS Tara_3, 0 AS Tara_4, 0 AS Tara_5, 0 AS Tara_6, 0 AS Tara_7, 0 AS Tara_8

                FROM  consecutivos c, Documentos_Lin_Ped dp, TblTipoDoctos td, TblProducto p

                WHERE c.tipo = '$tipo' AND td.idTipoDoctos = c.tipo AND p.IdProducto = dp.IdProducto
                AND dp.numero_pedido = '$numero' AND dp.sw = 10)";

                $registros =  sqlsrv_prepare($cn->getConecta(), $sql1);
                if(sqlsrv_execute($registros) === false) {
                    throw new Exception("Error al insertar detalle del documento: " . print_r(sqlsrv_errors(), true));
                }

                $sql2="UPDATE Consecutivos SET siguiente = siguiente+1 WHERE tipo = '$tipo' ";
                $registros =  sqlsrv_prepare($cn->getConecta(), $sql2);
                if(sqlsrv_execute($registros) === false) {
                    throw new Exception("Error al actualizar consecutivo: " . print_r(sqlsrv_errors(), true));
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

    }
?>
