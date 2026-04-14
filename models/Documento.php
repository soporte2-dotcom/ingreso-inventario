<?php
    class Documento extends Conectarserver{

        public function insert_doc($tipo, $consecutivo, $nit, $direccion, $usuario){
            $cn = new Conectarserver;

            $sql="INSERT INTO Documentos(sw, tipo, modelo, Numero_Documento, Numero_Docto_Base,
            nit_Cedula, codigo_direccion, Fecha_Hora_Factura,Fecha_Hora_Vencimiento,Fecha_orden_Venta,
            condicion,valor_total, valor_aplicado, Retencion_1,Retencion_2, Retencion_3, retencion_causada, retencion_iva,retencion_ica,
            retencion_descuento, descuento_pie, DescuentoOrdenVenta, descuento_1, descuento_2, descuento_3, costo, IdVendedor, anulado, usuario,
            notas,pc, fecha_hora, duracion, bodega, Valor_impuesto, Impuesto_Consumo, impuesto_deporte, concepto, vencimiento_presup, 
            exportado, prefijo, moneda, CentroDeCostosDoc, valor_mercancia, abono, Comision_Vendedor, Tasa_Moneda_Ext, Tomador, Tasa_Fija_o_Variable, Punto_FOB,
            Fletes_Moneda_Ext, Miselaneos_Moneda_Ext, Cargo_Por_Fletes, Impuesto_Por_Fletes, Total_Items, Nombre_Cliente, Ordenado_Por, Telefono_De_Envio_1,
            Telefono_De_Envio_2, Factura_Impresa, IdFormaEnvio, IdTransportador, nit_Cedula_2, codigo_direccion_2, Numero_Docto_Base_2, Tipo_Docto_Base, 
            Tipo_Docto_Base_2, IdActividadEconomica, TarifaReteFuenteCree, Valor_ReteCree, IdVehiculo)
            
            (SELECT '99' AS sw, '$tipo' AS tipo, '$tipo' AS modelo, '$consecutivo' AS Numero_Documento, '$consecutivo' AS Numero_Docto_Base,
            '$nit' AS nit_Cedula, '$direccion' AS codigo_direccion,  GETDATE() AS Fecha_Hora_Factura, GETDATE() AS Fecha_Hora_Vencimiento, GETDATE() AS Fecha_orden_Venta,
            t.condicion, 0 AS valor_total, 0 AS valor_aplicado, 0 AS Retencion_1, 0 AS Retencion_2, 0 AS Retencion_3, 0 AS retencion_causada, 0 AS retencion_iva, 
            0 AS retencion_ica, 0 AS retencion_descuento, 0 AS descuento_pie, 0 AS DescuentoOrdenVenta, 0 AS descuento_1, 0 AS descuento_2,0 AS descuento_3, 
            0 AS costo, td.IdVendedor, 'N' AS anulado, u.Id_Usuario AS usuario,
            '' AS notas, HOST_NAME() AS pc, GETDATE() AS fecha_hora, 0 AS duracion, do.IdBodega AS bodega, 0 AS Valor_impuesto, 0 AS Impuesto_Consumo, 
            0 AS impuesto_deporte, 0 AS concepto, GETDATE() AS vencimiento_presup, 
            'N' AS exportado, '0' AS prefijo, 1 AS moneda, 0 AS CentroDeCostosDoc, 0 AS valor_mercancia, 0 AS abono, 0 AS Comision_Vendedor, 
            1 AS Tasa_Moneda_Ext, '' AS Tomador, 'V' AS Tasa_Fija_o_Variable, td.idLista AS Punto_FOB,
            0 AS Fletes_Moneda_Ext, 0 AS Miselaneos_Moneda_Ext, 0 AS Cargo_Por_Fletes, 0 AS Impuesto_Por_Fletes, 2 AS Total_Items, t.nombre AS Nombre_Cliente, 
            'Cerdos del Valle S.A' AS Ordenado_Por, td.telefono_1 AS Telefono_De_Envio_1, '' AS Telefono_De_Envio_2, 'N' AS Factura_Impresa, '1' AS IdFormaEnvio, '1' AS IdTransportador, 
            '$nit' AS nit_Cedula_2, '$direccion' AS codigo_direccion_2, '0' AS Numero_Docto_Base_2, '0' AS Tipo_Docto_Base, 
            '0' AS Tipo_Docto_Base_2, '0' AS IdActividadEconomica, 0 AS TarifaReteFuenteCree, 0 AS Valor_ReteCree, '1' AS IdVehiculo            
            FROM TblTerceros t, Terceros_Dir td, TblUsuarios u, TblTipoDoctos do            
            WHERE td.codigo_direccion = '$direccion' AND u.Id_Usuario = '$usuario' AND td.nit = t.nit_cedula AND td.nit = '$nit' AND do.idTipoDoctos = '$tipo') ";

            $registros = sqlsrv_prepare($cn->getConecta(), $sql);            
            if(sqlsrv_execute($registros)){
                echo"Agregado correctamente \n";
            }else{
                echo"No Agregado \n";
            }

            $sql2="UPDATE Consecutivos SET siguiente = siguiente+1 WHERE tipo = '$tipo' ";
            $registros =  sqlsrv_prepare($cn->getConecta(), $sql2);
            if(sqlsrv_execute($registros)){
                echo" Consecutivo Actualizado correctamente \n";
            }else{
                echo" No Actualizado consecutivo \n";
            }
        }
        
        public function insert_detalle($tipo, $consecutivo, $nit, $seq, $producto, $cantidad){
            $cn = new Conectarserver;

            $sql="INSERT INTO Documentos_Lin (sw,tipo, seq, modelo, Numero_Documento, Numero_Docto_Base, Numero_Lote, Nit_Cedula, Codigo_Direccion, Fecha_Documento,
            IdProducto, IdUnidad, Factor_Conversion, Cantidad_Facturada, Cantidad_Pendiente, Cantidad_Orden, Costo_Unitario, Valor_Unitario,
            Valor_Impuesto, Porcentaje_Impuesto, Porcentaje_Descuento_1, Porcentaje_Descuento_2,Porcentaje_Descuento_3, IdVendedor, Comision_Vendedor,
            Valor_Comision_Vendedor, IdBodega, Maneja_Inventario, Tomador, IdMoneda, Tasa_Moneda_Ext, CentroDeCostosDoc,
            Nota_Linea, Unidades, Fecha_Vence, Exportado, Costo_Unitario_Inicial,
            Porcentaje_ReteFuente, Envase, Numero_Lote_Destino, serial, Impuesto_Consumo, Porcentaje_ReteFuente_2,
            Porcentaje_ReteFuente_3, Porcentaje_ReteFuente_4, Emp_1, Emp_2, Emp_3, Emp_4, Emp_5, Emp_6,
            Emp_7, Emp_8, Tara_1, Tara_2, Tara_3, Tara_4, Tara_5, Tara_6, Tara_7, Tara_8)
            
            (SELECT '99' AS sw, '$tipo' AS tipo,  $seq+1 AS seq, p.contable AS Modelo,  $consecutivo AS Numero_Documento,
            0 AS Numero_Docto_Base, '0' AS Numero_Lote, '$nit' AS Nit_Cedula, d.codigo_direccion AS codigo_direccion,  GETDATE() AS Fecha_Documento,
            '$producto' AS IdProducto, p.unidad_Inventario AS IdUnidad, '1' AS Factor_Conversion,  $cantidad AS Cantidad_Facturada,
            ($cantidad)* -1 AS Cantidad_Pendiente, 0 AS Cantidad_Orden, p.costo_unitario AS costo_unitario, p.costo_unitario AS Valor_Unitario, 0 AS Valor_Impuesto, 
            0 AS Porcentaje_Impuesto, 0 AS Porcentaje_Descuento_1, 0 AS Porcentaje_Descuento_2, 0 AS Porcentaje_Descuento_3, 1 AS IdVendedor, 0 AS Comision_Vendedor, 
            0 AS Valor_Comision_Vendedor, do.IdBodega AS IdBodega, 'S' AS Maneja_Inventario, '' AS Tomador, 1 AS IdMoneda, 1 AS Tasa_Moneda_Ext, 
            '0' AS CentroDeCostosDoc, ' ' AS Nota_Linea, '1' AS Unidades, '2000-01-01 00:00:00.000' AS Fecha_Vence, 'N' AS Exportado, p.costo_unitario AS Costo_Unitario_Inicial,
            0 AS Porcentaje_ReteFuente, 0 AS Envase, 0 AS Numero_Lote_Destino, '' AS serial, 0 AS Impuesto_Consumo, 0 AS Porcentaje_ReteFuente_2,
            0 AS Porcentaje_ReteFuente_3, 0 AS Porcentaje_ReteFuente_4, 0 AS Emp_1, 0 AS Emp_2, 0 AS Emp_3, 0 AS Emp_4, 0 AS Emp_5, 0 AS Emp_6,
            0 AS Emp_7, 0 AS Emp_8, 0 AS Tara_1, 0 AS Tara_2, 0 AS Tara_3, 0 AS Tara_4, 0 AS Tara_5, 0 AS Tara_6, 0 AS Tara_7, 0 AS Tara_8
                                                
            FROM Documentos d, TblProducto p, TblTipoDoctos do
                        
            WHERE p.IdProducto = '$producto' AND d.tipo = '$tipo' AND d.Numero_Documento = '$consecutivo' AND do.idTipoDoctos = d.tipo) ";

            $registros = sqlsrv_prepare($cn->getConecta(), $sql);            
            if(sqlsrv_execute($registros)){
                echo"Agregado correctamente \n";
            }else{
                echo"No Agregado \n";
            }

        }

        public function update_doc($tipo, $consecutivo, $notas, $remision){
            $cn = new Conectarserver;

            if(empty($remision)){
                $sql="UPDATE Documentos SET notas = '$notas', exportado = 'S',
                Total_Items = (SELECT COUNT(*) FROM Documentos_Lin WHERE tipo = $tipo AND Numero_documento = $consecutivo),
                valor_total = (SELECT SUM(ROUND((d.Cantidad_Facturada * d.Valor_Unitario) * (1 - d.Porcentaje_Descuento_1 / 100), 2) + ((d.Cantidad_Facturada * d.Valor_Unitario) * (1 - d.Porcentaje_Descuento_1 / 100)) * (d.Porcentaje_Impuesto / 100)) 
                FROM Documentos_Lin d WHERE tipo = $tipo AND Numero_documento = $consecutivo),
                costo = (SELECT SUM(ROUND((d.Cantidad_Facturada * d.Valor_Unitario) * (1 - d.Porcentaje_Descuento_1 / 100), 2) + ((d.Cantidad_Facturada * d.Valor_Unitario) * (1 - d.Porcentaje_Descuento_1 / 100)) * (d.Porcentaje_Impuesto / 100)) 
                FROM Documentos_Lin d WHERE tipo = $tipo AND Numero_documento = $consecutivo),
                valor_aplicado = (SELECT SUM(ROUND((d.Cantidad_Facturada * d.Valor_Unitario) * (1 - d.Porcentaje_Descuento_1 / 100), 2) + ((d.Cantidad_Facturada * d.Valor_Unitario) * (1 - d.Porcentaje_Descuento_1 / 100)) * (d.Porcentaje_Impuesto / 100)) 
                FROM Documentos_Lin d WHERE tipo = $tipo AND Numero_documento = $consecutivo),
                descuento_1 = (SELECT SUM(ROUND((d.Cantidad_Facturada * d.Valor_Unitario) * (d.Porcentaje_Descuento_1 / 100), 2)) 
                FROM Documentos_Lin d WHERE tipo = $tipo AND Numero_documento = $consecutivo),
                Valor_impuesto = (SELECT SUM(((d.Cantidad_Facturada * d.Valor_Unitario) * (1 - d.Porcentaje_Descuento_1 / 100)) * (d.Porcentaje_Impuesto / 100))
                FROM Documentos_Lin d WHERE tipo = $tipo AND Numero_documento = $consecutivo)
                WHERE tipo = $tipo AND Numero_Documento = $consecutivo";
            }else{
                $sql="UPDATE Documentos SET notas = '$notas', exportado = 'S', IdVendedor = '$remision',
                Total_Items = (SELECT COUNT(*) FROM Documentos_Lin WHERE tipo = $tipo AND Numero_documento = $consecutivo),
                valor_total = (SELECT SUM(ROUND((d.Cantidad_Facturada * d.Valor_Unitario) * (1 - d.Porcentaje_Descuento_1 / 100), 2) + ((d.Cantidad_Facturada * d.Valor_Unitario) * (1 - d.Porcentaje_Descuento_1 / 100)) * (d.Porcentaje_Impuesto / 100)) 
                FROM Documentos_Lin d WHERE tipo = $tipo AND Numero_documento = $consecutivo),
                costo = (SELECT SUM(ROUND((d.Cantidad_Facturada * d.Valor_Unitario) * (1 - d.Porcentaje_Descuento_1 / 100), 2) + ((d.Cantidad_Facturada * d.Valor_Unitario) * (1 - d.Porcentaje_Descuento_1 / 100)) * (d.Porcentaje_Impuesto / 100)) 
                FROM Documentos_Lin d WHERE tipo = $tipo AND Numero_documento = $consecutivo),
                valor_aplicado = (SELECT SUM(ROUND((d.Cantidad_Facturada * d.Valor_Unitario) * (1 - d.Porcentaje_Descuento_1 / 100), 2) + ((d.Cantidad_Facturada * d.Valor_Unitario) * (1 - d.Porcentaje_Descuento_1 / 100)) * (d.Porcentaje_Impuesto / 100)) 
                FROM Documentos_Lin d WHERE tipo = $tipo AND Numero_documento = $consecutivo),
                descuento_1 = (SELECT SUM(ROUND((d.Cantidad_Facturada * d.Valor_Unitario) * (d.Porcentaje_Descuento_1 / 100), 2)) 
                FROM Documentos_Lin d WHERE tipo = $tipo AND Numero_documento = $consecutivo),
                Valor_impuesto = (SELECT SUM(((d.Cantidad_Facturada * d.Valor_Unitario) * (1 - d.Porcentaje_Descuento_1 / 100)) * (d.Porcentaje_Impuesto / 100))
                FROM Documentos_Lin d WHERE tipo = $tipo AND Numero_documento = $consecutivo)
                WHERE tipo = $tipo AND Numero_Documento = $consecutivo";
            }

            $registros = sqlsrv_prepare($cn->getConecta(), $sql);
            if(sqlsrv_execute($registros)){
                echo"Documento Actualizado Correctamente \n";
            }else{
                echo"No se Actualizo Documento \n";
            }
            

            $sql5="(EXEC UPDATE_PRODUCTO_STO )";
            $registros =  sqlsrv_prepare($cn->getConecta(), $sql5);
            if(sqlsrv_execute($registros)){
                echo" No Actualizado Procedimiento Almacenado \n";
            }else{
                echo" Procedimiento Almacenado Actualizado correctamente \n";
            }

        }

        public function listar_entradas_x_usuario($usuario){
            $cn = new Conectarserver;
            $resultado = array();

            if($usuario == 'LAUREN' || $usuario == 'SA'){

                $sql="SELECT d.Fecha_Hora_Factura, d.tipo, tt.TipoDoctos, d.Numero_documento, d.Numero_Docto_Base, d.Tipo_Docto_Base_2, d.Numero_Docto_Base_2,
                    d.nit_Cedula, d.Nombre_Cliente, d.codigo_direccion, td.direccion, td.telefono_1, d.exportado, d.usuario

                    FROM Documentos d, Terceros_Dir td, TblTipoDoctos tt, TblTerceros t

                    WHERE CONVERT(date, Fecha_Hora_Factura) > '2025/07/31' AND tt.tipo IN ('12', '3')
                    AND tt.idTipoDoctos = d.tipo AND td.nit = d.nit_Cedula AND d.codigo_direccion = td.codigo_direccion
                    AND t.nit_cedula = d.nit_Cedula
                    ORDER BY d.Fecha_Hora_Factura DESC";

                $registros = sqlsrv_query($cn->getConecta(), $sql);

            } else {

                $sql="SELECT d.Fecha_Hora_Factura, d.tipo, tt.TipoDoctos, d.Numero_documento, d.Numero_Docto_Base, d.Tipo_Docto_Base_2, d.Numero_Docto_Base_2,
                    d.nit_Cedula, d.Nombre_Cliente, d.codigo_direccion, td.direccion, td.telefono_1, d.exportado, d.usuario

                    FROM Documentos d, Terceros_Dir td, TblTipoDoctos tt, TblTerceros t

                    WHERE d.usuario = ? AND CONVERT(date, Fecha_Hora_Factura) > '2024/12/31' AND tt.tipo IN ('12', '3')
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
            d.nit_Cedula_2, t.nombre AS nombre2, d.codigo_direccion_2, td2.direccion AS direccion2, d.notas, d.exportado, d.IdVendedor, d.Fecha_Hora_Factura,
            d.IdTransportador, d.IdVehiculo
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
        

        public function listar_prod_x_doc($tipo, $consecutivo, $producto){
            $cn = new Conectarserver;
            $sql="SELECT * FROM Documentos_Lin WHERE tipo = $tipo AND Numero_documento = $consecutivo AND IdProducto = $producto";
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
            $sql="SELECT d.tipo, d.Numero_Documento, d.seq, d.IdProducto, p.Producto, u.Unidad, d.Cantidad_Facturada, d.Porcentaje_Descuento_1, d.Porcentaje_Impuesto, d.Valor_Unitario, d.Numero_Lote, d.Fecha_Vence, d.Nota_Linea, d.Unidades, o.exportado
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

        public function delete_masivo($tipo, $consecutivo, $seqs, $productos) {
            $cn = new Conectarserver;

            $seqArr     = array_filter(array_map('trim', explode(',', $seqs)));
            $prodArr    = array_filter(array_map('trim', explode(',', $productos)));

            if (empty($seqArr)) return "error: sin secuencias";

            $errores = 0;
            foreach ($seqArr as $i => $seq) {
                $producto = isset($prodArr[$i]) ? $prodArr[$i] : null;
                $sql = "DELETE FROM Documentos_Lin WHERE tipo = ? AND Numero_documento = ? AND seq = ?";
                $params = [$tipo, $consecutivo, (int)$seq];
                if ($producto !== null) {
                    $sql .= " AND IdProducto = ?";
                    $params[] = (int)$producto;
                }
                $stmt = sqlsrv_prepare($cn->getConecta(), $sql, $params);
                if (!sqlsrv_execute($stmt)) $errores++;
            }

            if ($errores > 0) return "error: fallaron $errores eliminaciones";

            // Actualizar totales del documento una sola vez al final
            $sqlUpdate = "UPDATE Documentos SET
                Total_Items    = (SELECT COUNT(*) FROM Documentos_Lin WHERE tipo = ? AND Numero_documento = ?),
                valor_total    = (SELECT ISNULL(SUM(ROUND((d.Cantidad_Facturada * d.Valor_Unitario) * (1 - d.Porcentaje_Descuento_1 / 100), 2) + ((d.Cantidad_Facturada * d.Valor_Unitario) * (1 - d.Porcentaje_Descuento_1 / 100)) * (d.Porcentaje_Impuesto / 100)), 0) FROM Documentos_Lin d WHERE tipo = ? AND Numero_documento = ?),
                costo          = (SELECT ISNULL(SUM(ROUND((d.Cantidad_Facturada * d.Valor_Unitario) * (1 - d.Porcentaje_Descuento_1 / 100), 2) + ((d.Cantidad_Facturada * d.Valor_Unitario) * (1 - d.Porcentaje_Descuento_1 / 100)) * (d.Porcentaje_Impuesto / 100)), 0) FROM Documentos_Lin d WHERE tipo = ? AND Numero_documento = ?),
                valor_aplicado = (SELECT ISNULL(SUM(ROUND((d.Cantidad_Facturada * d.Valor_Unitario) * (1 - d.Porcentaje_Descuento_1 / 100), 2) + ((d.Cantidad_Facturada * d.Valor_Unitario) * (1 - d.Porcentaje_Descuento_1 / 100)) * (d.Porcentaje_Impuesto / 100)), 0) FROM Documentos_Lin d WHERE tipo = ? AND Numero_documento = ?),
                descuento_1    = (SELECT ISNULL(SUM(ROUND((d.Cantidad_Facturada * d.Valor_Unitario) * (d.Porcentaje_Descuento_1 / 100), 2)), 0) FROM Documentos_Lin d WHERE tipo = ? AND Numero_documento = ?),
                Valor_impuesto = (SELECT ISNULL(SUM(((d.Cantidad_Facturada * d.Valor_Unitario) * (1 - d.Porcentaje_Descuento_1 / 100)) * (d.Porcentaje_Impuesto / 100)), 0) FROM Documentos_Lin d WHERE tipo = ? AND Numero_documento = ?)
                WHERE tipo = ? AND Numero_Documento = ?";

            $paramsUpdate = [];
            for ($i = 0; $i < 6; $i++) {
                $paramsUpdate[] = $tipo;
                $paramsUpdate[] = $consecutivo;
            }
            $paramsUpdate[] = $tipo;
            $paramsUpdate[] = $consecutivo;

            $stmtU = sqlsrv_prepare($cn->getConecta(), $sqlUpdate, $paramsUpdate);
            sqlsrv_execute($stmtU);

            return "success";
        }

        public function delete_id($tipo, $consecutivo, $producto, $seq) {
            $cn = new Conectarserver;
            
            // Eliminar el registro
            $sql = "DELETE FROM Documentos_Lin 
                    WHERE tipo = ? AND Numero_documento = ? AND IdProducto = ? AND seq = ?";
            
            $params = array($tipo, $consecutivo, $producto, $seq);
            $stmt = sqlsrv_prepare($cn->getConecta(), $sql, $params);
            
            if(sqlsrv_execute($stmt)) {
                
                $sqlUpdate = "UPDATE Documentos SET 
                    Total_Items = (SELECT COUNT(*) FROM Documentos_Lin WHERE tipo = ? AND Numero_documento = ?),
                    valor_total = (SELECT ISNULL(SUM(ROUND((dl.Cantidad_Facturada * dl.Valor_Unitario) * (1 - ISNULL(dl.Porcentaje_Descuento_1, 0) / 100), 2) + ((dl.Cantidad_Facturada * dl.Valor_Unitario) * (1 - ISNULL(dl.Porcentaje_Descuento_1, 0) / 100)) * (ISNULL(dl.Porcentaje_Impuesto, 0) / 100)), 0) FROM Documentos_Lin dl WHERE dl.tipo = ? AND dl.Numero_documento = ?),
                    costo = (SELECT ISNULL(SUM(ROUND((dl.Cantidad_Facturada * dl.Valor_Unitario) * (1 - ISNULL(dl.Porcentaje_Descuento_1, 0) / 100), 2) + ((dl.Cantidad_Facturada * dl.Valor_Unitario) * (1 - ISNULL(dl.Porcentaje_Descuento_1, 0) / 100)) * (ISNULL(dl.Porcentaje_Impuesto, 0) / 100)), 0) FROM Documentos_Lin dl WHERE dl.tipo = ? AND dl.Numero_documento = ?),
                    valor_aplicado = (SELECT ISNULL(SUM(ROUND((dl.Cantidad_Facturada * dl.Valor_Unitario) * (1 - ISNULL(dl.Porcentaje_Descuento_1, 0) / 100), 2) + ((dl.Cantidad_Facturada * dl.Valor_Unitario) * (1 - ISNULL(dl.Porcentaje_Descuento_1, 0) / 100)) * (ISNULL(dl.Porcentaje_Impuesto, 0) / 100)), 0) FROM Documentos_Lin dl WHERE dl.tipo = ? AND dl.Numero_documento = ?),
                    descuento_1 = (SELECT ISNULL(SUM(ROUND((dl.Cantidad_Facturada * dl.Valor_Unitario) * (ISNULL(dl.Porcentaje_Descuento_1, 0) / 100), 2)), 0) FROM Documentos_Lin dl WHERE dl.tipo = ? AND dl.Numero_documento = ?),
                    Valor_impuesto = (SELECT ISNULL(SUM(((dl.Cantidad_Facturada * dl.Valor_Unitario) * (1 - ISNULL(dl.Porcentaje_Descuento_1, 0) / 100)) * (ISNULL(dl.Porcentaje_Impuesto, 0) / 100)), 0) FROM Documentos_Lin dl WHERE dl.tipo = ? AND dl.Numero_documento = ?)
                    WHERE tipo = ? AND Numero_Documento = ?";

                $paramsUpdate = array();
                for ($i = 0; $i < 6; $i++) {
                    $paramsUpdate[] = $tipo;
                    $paramsUpdate[] = $consecutivo;
                }
                $paramsUpdate[] = $tipo;
                $paramsUpdate[] = $consecutivo;

                $stmtUpdate = sqlsrv_prepare($cn->getConecta(), $sqlUpdate, $paramsUpdate);
                sqlsrv_execute($stmtUpdate);
                
                echo "success";
            } else {
                if (($errors = sqlsrv_errors()) != null) {
                    error_log("Error SQL en delete_id: " . print_r($errors, true));
                }
                echo "error";
            }
        }

        // public function update_prod_doc($tipo, $consecutivo, $producto, $cantidad, $Valor_Unitario, $lote, $fecha_vence){
        //     $cn = new Conectarserver;
        //     echo $fecha_vence;
        //     $sql="UPDATE Documentos_Lin 
        //     SET Cantidad_Facturada = $cantidad, Cantidad_Pendiente = ($cantidad*-1), Numero_Lote = '$lote', Fecha_Vence = CAST('$fecha_vence' AS DATE),
        //     Valor_Unitario = $Valor_Unitario, Costo_Unitario = $Valor_Unitario, Costo_Unitario_Inicial = $Valor_Unitario
        //     WHERE tipo = '$tipo' AND Numero_Documento = '$consecutivo' AND IdProducto = '$producto' ";
        //     $registros = sqlsrv_prepare($cn->getConecta(), $sql);            
        //     if(sqlsrv_execute($registros)){
        //         echo"Se Actualizo el producto \n";
        //     }else{
        //         echo"No se Actualizo los Productos \n";
        //     }

        //     $sql="UPDATE Documentos SET 
        //         Total_Items = (SELECT COUNT(*) FROM Documentos_Lin WHERE tipo = $tipo AND Numero_documento = $consecutivo),
        //         valor_total = (SELECT SUM(ROUND((d.Cantidad_Facturada * d.Valor_Unitario) * (1 - d.Porcentaje_Descuento_1 / 100), 2) + ((d.Cantidad_Facturada * d.Valor_Unitario) * (1 - d.Porcentaje_Descuento_1 / 100)) * (d.Porcentaje_Impuesto / 100)) 
        //         FROM Documentos_Lin d WHERE tipo = $tipo AND Numero_documento = $consecutivo),
        //         costo = (SELECT SUM(ROUND((d.Cantidad_Facturada * d.Valor_Unitario) * (1 - d.Porcentaje_Descuento_1 / 100), 2) + ((d.Cantidad_Facturada * d.Valor_Unitario) * (1 - d.Porcentaje_Descuento_1 / 100)) * (d.Porcentaje_Impuesto / 100)) 
        //         FROM Documentos_Lin d WHERE tipo = $tipo AND Numero_documento = $consecutivo),
        //         valor_aplicado = (SELECT SUM(ROUND((d.Cantidad_Facturada * d.Valor_Unitario) * (1 - d.Porcentaje_Descuento_1 / 100), 2) + ((d.Cantidad_Facturada * d.Valor_Unitario) * (1 - d.Porcentaje_Descuento_1 / 100)) * (d.Porcentaje_Impuesto / 100)) 
        //         FROM Documentos_Lin d WHERE tipo = $tipo AND Numero_documento = $consecutivo),
        //         descuento_1 = (SELECT SUM(ROUND((d.Cantidad_Facturada * d.Valor_Unitario) * (d.Porcentaje_Descuento_1 / 100), 2)) 
        //         FROM Documentos_Lin d WHERE tipo = $tipo AND Numero_documento = $consecutivo),
        //         Valor_impuesto = (SELECT SUM(((d.Cantidad_Facturada * d.Valor_Unitario) * (1 - d.Porcentaje_Descuento_1 / 100)) * (d.Porcentaje_Impuesto / 100))
        //         FROM Documentos_Lin d WHERE tipo = $tipo AND Numero_documento = $consecutivo)
        //         WHERE tipo = $tipo AND Numero_Documento = $consecutivo";

        //     $registros = sqlsrv_prepare($cn->getConecta(), $sql);
        //     if(sqlsrv_execute($registros)){
        //         echo"Documento Actualizado Correctamente \n";
        //     }else{
        //         echo"No se Actualizo Documento \n";
        //     }

        // }

        public function update_prod_doc($tipo, $consecutivo, $producto, $seq, $cantidad = null, $valor_unitario = null, $lote = null, $fecha_vence = null, $descuento = null, $nota = null, $unidades = null) {
            $cn = new Conectarserver;
            
            error_log("🔄 Actualizando producto: tipo=$tipo, consecutivo=$consecutivo, producto=$producto, seq=$seq");
            
            // Construir la consulta UPDATE dinámicamente
            $updates = [];
            $params = [];
            
            // Solo incluir campos que fueron proporcionados
            if ($cantidad !== null) {
                // Validar contra OS si aplica (antes de modificar)
                $error_os = $this->validar_cantidad_vs_os($tipo, $consecutivo, $producto, $seq, $cantidad);
                if ($error_os !== null) {
                    return ['status' => 'error', 'message' => $error_os];
                }

                $updates[] = "Cantidad_Facturada = ?";
                // Cantidad_Pendiente = Cantidad_Orden - nueva_cantidad, mínimo 0
                $updates[] = "Cantidad_Pendiente = CASE WHEN (Cantidad_Orden - ?) < 0 THEN 0 ELSE (Cantidad_Orden - ?) END";
                $params[] = $cantidad;
                $params[] = $cantidad;
                $params[] = $cantidad;
            }
            
            if ($valor_unitario !== null) {
                $updates[] = "Valor_Unitario = ?";
                $updates[] = "Costo_Unitario = ?";
                $updates[] = "Costo_Unitario_Inicial = ?";
                $params[] = $valor_unitario;
                $params[] = $valor_unitario;
                $params[] = $valor_unitario;
            }
            
            if ($lote !== null) {
                $updates[] = "Numero_Lote = ?";
                $params[] = $lote;
            }
            
            if ($fecha_vence !== null && $fecha_vence !== '') {
                // Intentar primero d/m/Y (formato de visualización de la tabla),
                // luego Y-m-d (ISO, desde JS o Excel). date_create() no se usa porque
                // interpreta 09/04/2026 como m/d/Y (septiembre 4) en vez de d/m/Y (abril 9).
                $fecha_parsed = DateTime::createFromFormat('d/m/Y', $fecha_vence);
                if (!$fecha_parsed) {
                    $fecha_parsed = DateTime::createFromFormat('Y-m-d', $fecha_vence);
                }
                if ($fecha_parsed) {
                    $fecha_vence = $fecha_parsed->format('Y-m-d');
                }
                $updates[] = "Fecha_Vence = CONVERT(DATE, ?, 23)";
                $params[] = $fecha_vence;
            }
            
            if ($descuento !== null) {
                $updates[] = "Porcentaje_Descuento_1 = ?";
                $params[] = $descuento;
            }
            
            if ($nota !== null) {
                $updates[] = "Nota_Linea = ?";
                $params[] = $nota;
            }
            
            if ($unidades !== null) {
                $updates[] = "Unidades = ?";
                $params[] = $unidades;
            }
            
            // Si no hay campos para actualizar, retornar error
            if (empty($updates)) {
                error_log("❌ No se proporcionaron campos para actualizar");
                return ['status' => 'error', 'message' => 'No se proporcionaron campos para actualizar'];
            }
            
            // ⬅️ AGREGAR seq AL WHERE
            $sql = "UPDATE Documentos_Lin SET " . implode(", ", $updates) . 
                " WHERE tipo = ? AND Numero_Documento = ? AND IdProducto = ? AND seq = ?";
            
            // Agregar parámetros WHERE
            $params[] = $tipo;
            $params[] = $consecutivo;
            $params[] = $producto;
            $params[] = $seq;  // ⬅️ NUEVO
            
            error_log("📝 SQL: " . $sql);
            error_log("📝 Params: " . print_r($params, true));
            
            // Preparar y ejecutar la consulta
            $stmt = sqlsrv_prepare($cn->getConecta(), $sql, $params);
            
            if (!$stmt) {
                $errors = sqlsrv_errors();
                error_log("❌ Error al preparar UPDATE: " . print_r($errors, true));
                return ['status' => 'error', 'message' => 'Error al preparar la actualización'];
            }

            if (sqlsrv_execute($stmt)) {
                $filas_afectadas = sqlsrv_rows_affected($stmt);
                error_log("✅ Actualización exitosa. Filas afectadas: " . $filas_afectadas);

                sqlsrv_free_stmt($stmt);

                // Actualizar los totales del documento
                $this->actualizar_totales_documento($tipo, $consecutivo);
                return ['status' => 'success'];
            } else {
                $errors = sqlsrv_errors();
                error_log("❌ Error al ejecutar UPDATE: " . print_r($errors, true));
                sqlsrv_free_stmt($stmt);
                return ['status' => 'error', 'message' => 'Error al ejecutar la actualización'];
            }
        }

        /**
         * Función auxiliar para actualizar los totales del documento
         */
        private function actualizar_totales_documento($tipo, $consecutivo) {
            $cn = new Conectarserver;
            
            $sql = "UPDATE Documentos SET 
                Total_Items = (SELECT COUNT(*) FROM Documentos_Lin WHERE tipo = ? AND Numero_documento = ?),
                valor_total = (SELECT ISNULL(SUM(ROUND((dl.Cantidad_Facturada * dl.Valor_Unitario) * (1 - ISNULL(dl.Porcentaje_Descuento_1, 0) / 100), 2) + ((dl.Cantidad_Facturada * dl.Valor_Unitario) * (1 - ISNULL(dl.Porcentaje_Descuento_1, 0) / 100)) * (ISNULL(dl.Porcentaje_Impuesto, 0) / 100)), 0) FROM Documentos_Lin dl WHERE dl.tipo = ? AND dl.Numero_documento = ?),
                costo = (SELECT ISNULL(SUM(ROUND((dl.Cantidad_Facturada * dl.Valor_Unitario) * (1 - ISNULL(dl.Porcentaje_Descuento_1, 0) / 100), 2) + ((dl.Cantidad_Facturada * dl.Valor_Unitario) * (1 - ISNULL(dl.Porcentaje_Descuento_1, 0) / 100)) * (ISNULL(dl.Porcentaje_Impuesto, 0) / 100)), 0) FROM Documentos_Lin dl WHERE dl.tipo = ? AND dl.Numero_documento = ?),
                valor_aplicado = (SELECT ISNULL(SUM(ROUND((dl.Cantidad_Facturada * dl.Valor_Unitario) * (1 - ISNULL(dl.Porcentaje_Descuento_1, 0) / 100), 2) + ((dl.Cantidad_Facturada * dl.Valor_Unitario) * (1 - ISNULL(dl.Porcentaje_Descuento_1, 0) / 100)) * (ISNULL(dl.Porcentaje_Impuesto, 0) / 100)), 0) FROM Documentos_Lin dl WHERE dl.tipo = ? AND dl.Numero_documento = ?),
                descuento_1 = (SELECT ISNULL(SUM(ROUND((dl.Cantidad_Facturada * dl.Valor_Unitario) * (ISNULL(dl.Porcentaje_Descuento_1, 0) / 100), 2)), 0) FROM Documentos_Lin dl WHERE dl.tipo = ? AND dl.Numero_documento = ?),
                Valor_impuesto = (SELECT ISNULL(SUM(((dl.Cantidad_Facturada * dl.Valor_Unitario) * (1 - ISNULL(dl.Porcentaje_Descuento_1, 0) / 100)) * (ISNULL(dl.Porcentaje_Impuesto, 0) / 100)), 0) FROM Documentos_Lin dl WHERE dl.tipo = ? AND dl.Numero_documento = ?)
                WHERE tipo = ? AND Numero_Documento = ?";

            $params = array();
            // Repetir parámetros para cada subconsulta
            for ($i = 0; $i < 6; $i++) {
                $params[] = $tipo;
                $params[] = $consecutivo;
            }
            // Parámetros finales WHERE
            $params[] = $tipo;
            $params[] = $consecutivo;

            $stmt = sqlsrv_prepare($cn->getConecta(), $sql, $params);
            
            return sqlsrv_execute($stmt);
        }

        /**
         * Valida que la nueva cantidad no supere el pendiente real de la OS.
         * Retorna null si no aplica o si la cantidad es válida.
         * Retorna string con el mensaje de error si la cantidad es inválida.
         */
        private function validar_cantidad_vs_os($tipo, $consecutivo, $producto, $seq, $nueva_cantidad) {
            $cn = new Conectarserver;

            // Verificar si este documento tiene referencia a una OS
            $sql_os_ref = "SELECT Numero_Docto_Base_2 FROM Documentos
                           WHERE tipo = ? AND Numero_Documento = ? AND Tipo_Docto_Base_2 = '10'";
            $stmt_ref = sqlsrv_query($cn->getConecta(), $sql_os_ref, array($tipo, $consecutivo));
            if (!$stmt_ref) return null;
            $row_ref = sqlsrv_fetch_array($stmt_ref, SQLSRV_FETCH_ASSOC);
            if (!$row_ref || empty($row_ref['Numero_Docto_Base_2'])) return null;

            $numero_os = $row_ref['Numero_Docto_Base_2'];

            // Obtener cantidad ordenada en la OS para este producto
            $sql_os_qty = "SELECT cantidad FROM Documentos_Lin_Ped
                           WHERE numero_pedido = ? AND sw = '10' AND IdProducto = ?";
            $stmt_qty = sqlsrv_query($cn->getConecta(), $sql_os_qty, array($numero_os, $producto));
            if (!$stmt_qty) return null;
            $row_qty = sqlsrv_fetch_array($stmt_qty, SQLSRV_FETCH_ASSOC);
            if (!$row_qty) return null;
            $cantidad_os = (float)$row_qty['cantidad'];

            // Sumar lo despachado en OTROS documentos (excluir el actual)
            $sql_otros = "SELECT ISNULL(SUM(dl.Cantidad_Facturada), 0) AS total_otros
                          FROM Documentos d
                          JOIN Documentos_Lin dl ON dl.tipo = d.tipo AND dl.Numero_Documento = d.Numero_documento
                          WHERE d.Numero_Docto_Base_2 = ? AND d.Tipo_Docto_Base_2 = '10'
                          AND NOT (d.tipo = ? AND d.Numero_documento = ?)
                          AND dl.IdProducto = ?";
            $stmt_otros = sqlsrv_query($cn->getConecta(), $sql_otros,
                                       array($numero_os, $tipo, $consecutivo, $producto));
            if (!$stmt_otros) return null;
            $row_otros = sqlsrv_fetch_array($stmt_otros, SQLSRV_FETCH_ASSOC);
            $total_otros = (float)($row_otros['total_otros'] ?? 0);

            $pendiente_real = max(0, $cantidad_os - $total_otros);

            if ((float)$nueva_cantidad > $pendiente_real) {
                return "La cantidad ingresada (" . (float)$nueva_cantidad . ") supera la cantidad pendiente disponible (" . $pendiente_real . ") para este producto en la Orden de Salida.";
            }

            return null;
        }

        public function total_entrada($tipo, $consecutivo){
            $cn = new Conectarserver;

            // Subtotal bruto (qty * precio) calculado en vivo desde Documentos_Lin
            // para que siempre refleje el estado real sin depender de tablas cacheadas.
            $sql = "SELECT ISNULL(SUM(dl.Cantidad_Facturada * dl.Valor_Unitario), 0) AS total
                    FROM Documentos_Lin dl
                    WHERE dl.tipo = $tipo AND dl.Numero_documento = $consecutivo";

            $registros = sqlsrv_query($cn->getConecta(), $sql);
            if ($registros === false) {
                echo "Error al ejecutar consulta.\n";
            } else {
                $resultado = array();
                while ($stmt = sqlsrv_fetch_array($registros)) {
                    $resultado[] = $stmt;
                }
                return $resultado;
            }
        }

        public function totales($tipo, $consecutivo){
            $cn = new Conectarserver;

            // Calcula IVA, descuento y total en vivo desde Documentos_Lin
            // para que siempre refleje el estado real independientemente del
            // UPDATE en la cabecera (Documentos).
            $sql = "SELECT
                        ISNULL(SUM(
                            ROUND((dl.Cantidad_Facturada * dl.Valor_Unitario) * (1 - ISNULL(dl.Porcentaje_Descuento_1, 0) / 100.0), 2)
                            + ((dl.Cantidad_Facturada * dl.Valor_Unitario) * (1 - ISNULL(dl.Porcentaje_Descuento_1, 0) / 100.0))
                              * (ISNULL(dl.Porcentaje_Impuesto, 0) / 100.0)
                        ), 0) AS valor_total,
                        ISNULL(SUM(
                            ROUND((dl.Cantidad_Facturada * dl.Valor_Unitario) * (ISNULL(dl.Porcentaje_Descuento_1, 0) / 100.0), 2)
                        ), 0) AS descuento_1,
                        ISNULL(SUM(
                            ((dl.Cantidad_Facturada * dl.Valor_Unitario) * (1 - ISNULL(dl.Porcentaje_Descuento_1, 0) / 100.0))
                            * (ISNULL(dl.Porcentaje_Impuesto, 0) / 100.0)
                        ), 0) AS Valor_impuesto
                    FROM Documentos_Lin dl
                    WHERE dl.tipo = $tipo AND dl.Numero_documento = $consecutivo";

            $registros = sqlsrv_query($cn->getConecta(), $sql);
            if ($registros === false) {
                echo "Error al ejecutar consulta.\n";
            } else {
                $resultado = array();
                while ($stmt = sqlsrv_fetch_array($registros)) {
                    $resultado[] = $stmt;
                }
                return $resultado;
            }
        }

        public function total_cantidad($tipo, $consecutivo){
            $cn = new Conectarserver;

            $sql="SELECT SUM(Cantidad_Facturada) AS totalCantidad FROM Documentos_Lin WHERE tipo = $tipo AND Numero_documento = $consecutivo ";

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

        public function insert_entrada_traslado($tipo, $numero, $tiporef, $usuario){
            $cn = new Conectarserver;

            try{

                $sql_validar = "SELECT COUNT(*) AS existe FROM Documentos 
                        WHERE Numero_documento = ? AND tipo = ?";

                $params = array($numero, $tiporef);
                $stmt = sqlsrv_query($cn->getConecta(), $sql_validar, $params);

                if ($stmt === false) {
                    throw new Exception("Error al validar el documento de referencia: " . print_r(sqlsrv_errors(), true));
                }

                $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

                if (!$row || $row['existe'] == 0) {
                    // El documento de referencia no existe, devolvemos un mensaje de error
                    return json_encode(array(
                        "status" => "error",
                        "message" => "El documento de referencia con número '$numero' y tipo '$tiporef' no existe en el sistema"
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
                
                (SELECT td.tipo AS sw, '$tipo' AS tipo, dl.seq AS seq,  p.contable AS Modelo,  (c.siguiente+1) AS Numero_Documento,
                '' AS Numero_Docto_Base, '0' AS Numero_Lote, dl.Nit_Cedula AS Nit_Cedula, dl.codigo_direccion AS codigo_direccion,  GETDATE() AS Fecha_Documento,
                dl.IdProducto AS IdProducto, dl.IdUnidad AS IdUnidad, '1' AS Factor_Conversion,  Cantidad_Facturada AS Cantidad_Facturada,
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
                if (isset($cn) && $cn->getConecta()) {
                    sqlsrv_rollback($cn->getConecta());
                }
                
                // Registramos el error en un log
                $this->registrar_error("Error en insert_entrada_traslado: " . $e->getMessage());
                
                // Devolvemos un objeto JSON con un status de error
                return json_encode(array(
                    "status" => "error",
                    "message" => $e->getMessage()
                ));
            }

        }

        public function update_lote($tipo, $numdoc, $id, $lote){

            $cn = new Conectarserver;
            $id = $_POST['id'];            
            $count = count($id);
            //Rebuscamos en busca de resultados $_POST (id selecionados). 
            for ($i=0; $i<$count; $i++) {
                $sql="UPDATE Documentos_Lin 
                SET Numero_Lote = '$lote'
                WHERE tipo = '$tipo' AND Numero_Documento = '$numdoc' AND IdProducto = '$id[$i]' ";
                $registros = sqlsrv_prepare($cn->getConecta(), $sql);           
                if(sqlsrv_execute($registros)){
                    echo"Lote Actualizado \n";
                }else{
                    echo"Lote NO Actualizado \n";
                }
            } 

        }

        public function update_doc_ref1($tipo, $consecutivo, $numero){
            $cn = new Conectarserver;

            $sql="UPDATE Documentos SET Numero_Docto_Base = '$numero' WHERE tipo = '$tipo' AND Numero_Documento = '$consecutivo' ";
            $registros = sqlsrv_prepare($cn->getConecta(), $sql);            
            if(sqlsrv_execute($registros)){
                echo"Se Actualizaron el doc referencia Correctamente \n";
            }else{
                echo"No se Actualizo el doc referencia \n";
            }
        }

        public function update_doc_ref($registros){
            $cn = new Conectarserver;

             // Inicializar variables
             $success = true;

            foreach($registros as $registro) {
                $sql = "UPDATE Documentos 
                        SET Numero_Docto_Base = ?                            
                        WHERE tipo = ? AND Numero_documento = ?";
                
                $params = array(
                    $registro->numeroDoctoBase,
                    $registro->tipo,
                    $registro->numeroDocumento
                );
                
                $stmt = sqlsrv_query($cn->getConecta(), $sql, $params);
                
                if(!$stmt) {
                    $success = false;
                    break;
                }
            }

            if($success) {
                sqlsrv_commit($cn->getConecta());
                echo json_encode(array("status" => true, "message" => "Actualización exitosa"));
            } else {
                sqlsrv_rollback($cn->getConecta());
                echo json_encode(array("status" => false, "message" => "Error al actualizar"));
            }

        }

        // public function update_lote_nota($registros){
        //     $cn = new Conectarserver;

        //      // Inicializar variables
        //      $success = true;

        //     foreach($registros as $registro) {
        //         $sql = "UPDATE Documentos_Lin
        //                 SET Numero_Lote = ?, Nota_Linea = ?
        //                 WHERE tipo = ? AND Numero_documento = ? AND seq = ?";
                
        //         $params = array(
        //             $registro->lote,
        //             $registro->nota,
        //             $registro->tipo,
        //             $registro->numeroDocumento,
        //             $registro->seq
        //         );
                
        //         $stmt = sqlsrv_query($cn->getConecta(), $sql, $params);
                
        //         if(!$stmt) {
        //             $success = false;
        //             break;
        //         }
        //     }

        //     if($success) {
        //         sqlsrv_commit($cn->getConecta());
        //         echo json_encode(array("status" => true, "message" => "Actualización exitosa"));
        //     } else {
        //         sqlsrv_rollback($cn->getConecta());
        //         echo json_encode(array("status" => false, "message" => "Error al actualizar"));
        //     }

        // }

        public function update_lote_nota($lineas, $notaGeneral, $idTipo, $numdoc){
            $cn = new Conectarserver;
            $con = $cn->getConecta();
            
            // Iniciar transacción
            sqlsrv_begin_transaction($con);
            
            $success = true;
            $message = '';
            $updatesRealizados = array();

            try {
                // 1. ACTUALIZAR NOTA GENERAL EN Documentos
                if (!empty($notaGeneral) && !empty($idTipo) && !empty($numdoc)) {
                    $sql_nota_general = "UPDATE Documentos 
                                        SET Notas = ? 
                                        WHERE tipo = ? AND Numero_documento = ?";
                    
                    $params_nota = array(
                        $notaGeneral,
                        $idTipo,
                        $numdoc
                    );
                    
                    $stmt_nota = sqlsrv_query($con, $sql_nota_general, $params_nota);
                    
                    if(!$stmt_nota) {
                        $success = false;
                        $message = 'Error al actualizar nota general: ' . print_r(sqlsrv_errors(), true);
                    } else {
                        $updatesRealizados[] = "Nota general actualizada";
                        sqlsrv_free_stmt($stmt_nota);
                    }
                }

                // 2. ACTUALIZAR LÍNEAS (lotes y notas de línea) - solo si la primera operación fue exitosa
                if ($success && !empty($lineas)) {
                    $lineasActualizadas = 0;
                    
                    foreach($lineas as $linea) {
                        $sql_linea = "UPDATE Documentos_Lin
                                    SET Numero_Lote = ?, Nota_Linea = ?
                                    WHERE tipo = ? AND Numero_documento = ? AND seq = ?";
                        
                        $params_linea = array(
                            $linea->lote,
                            $linea->nota_linea,
                            $linea->tipo,
                            $linea->numeroDocumento,
                            $linea->seq
                        );
                        
                        $stmt_linea = sqlsrv_query($con, $sql_linea, $params_linea);
                        
                        if(!$stmt_linea) {
                            $success = false;
                            $message = 'Error al actualizar línea: ' . print_r(sqlsrv_errors(), true);
                            break;
                        }
                        $lineasActualizadas++;
                        sqlsrv_free_stmt($stmt_linea);
                    }
                    
                    if ($lineasActualizadas > 0) {
                        $updatesRealizados[] = $lineasActualizadas . " línea(s) actualizada(s)";
                    }
                }

                // 3. CONFIRMAR O CANCELAR TRANSACCIÓN
                if($success) {
                    sqlsrv_commit($con);
                    echo json_encode(array(
                        "status" => true, 
                        "message" => "Actualización exitosa",
                        "detalles" => $updatesRealizados
                    ));
                } else {
                    sqlsrv_rollback($con);
                    echo json_encode(array("status" => false, "message" => $message));
                }

            } catch (Exception $e) {
                sqlsrv_rollback($con);
                echo json_encode(array("status" => false, "message" => "Error: " . $e->getMessage()));
            }
        }

        public function update_fecha($fecha, $ids_seleccionados){

            $cn = new Conectarserver;

            // Establecer la zona horaria a Bogotá, Colombia
            date_default_timezone_set("America/Bogota");
            // Obtener la hora actual en formato "00:00:00"
            $hora_actual = date("H:i:s");
            // Convertir la fecha de 'date' a 'datetime'
            $fecha_datetime = $fecha .'T'.$hora_actual;
           

            foreach ($ids_seleccionados as $id_seleccionado) {
                list($tipo, $numdoc) = explode('|', $id_seleccionado);
                $sql = "UPDATE Documentos 
                        SET Fecha_Hora_Factura = '$fecha_datetime'
                        WHERE tipo = '$tipo' AND Numero_Documento = '$numdoc' ";             
                $registros = sqlsrv_prepare($cn->getConecta(), $sql);
                if ($registros === false) {
                    die(print_r(sqlsrv_errors(), true));
                }
                if (sqlsrv_execute($registros) === false) {
                    die(print_r(sqlsrv_errors(), true));
                }

                $sql1 = "UPDATE Documentos_Lin 
                        SET Fecha_Documento = '$fecha_datetime'
                        WHERE tipo = '$tipo' AND Numero_Documento = '$numdoc' ";             
                $registros1 = sqlsrv_prepare($cn->getConecta(), $sql1);
                if ($registros1 === false) {
                    die(print_r(sqlsrv_errors(), true));
                }
                if (sqlsrv_execute($registros1) === false) {
                    die(print_r(sqlsrv_errors(), true));
                }
              
                /*if (sqlsrv_execute($registros)) {
                  $response['message'][] = "Fecha Actualizada para tipo: $tipo, Numero de Documento: $numdoc";
                  echo "Fecha Actualizada";
                } else {
                  $response['error'][] = "Fecha NO Actualizada para tipo: $tipo, Numero de Documento: $numdoc";
                  echo "Fecha NO Actualizada";
                }*/
              }

        }

        public function save_entrada($tipo, $numdoc, $notas, $remision, $nit, $nombre, $direccion, $telefono, $traslfact, $idTransportador = 1, $idVehiculo = 1){
            $cn = new Conectarserver;

            if(empty($remision)){
                $sql="UPDATE Documentos SET nit_Cedula_2 = '$nit', codigo_direccion_2 = '$direccion', Numero_Docto_Base = '$traslfact', notas = '$notas', exportado = 'S', IdTransportador = '$idTransportador', IdVehiculo = '$idVehiculo',
                Total_Items = (SELECT COUNT(*) FROM Documentos_Lin WHERE tipo = $tipo AND Numero_documento = $numdoc),
                valor_total = (SELECT SUM(ROUND((d.Cantidad_Facturada * d.Valor_Unitario) * (1 - d.Porcentaje_Descuento_1 / 100), 2) + ((d.Cantidad_Facturada * d.Valor_Unitario) * (1 - d.Porcentaje_Descuento_1 / 100)) * (d.Porcentaje_Impuesto / 100)) 
                FROM Documentos_Lin d WHERE tipo = $tipo AND Numero_documento = $numdoc),
                costo = (SELECT SUM(ROUND((d.Cantidad_Facturada * d.Valor_Unitario) * (1 - d.Porcentaje_Descuento_1 / 100), 2) + ((d.Cantidad_Facturada * d.Valor_Unitario) * (1 - d.Porcentaje_Descuento_1 / 100)) * (d.Porcentaje_Impuesto / 100)) 
                FROM Documentos_Lin d WHERE tipo = $tipo AND Numero_documento = $numdoc),
                valor_aplicado = (SELECT SUM(ROUND((d.Cantidad_Facturada * d.Valor_Unitario) * (1 - d.Porcentaje_Descuento_1 / 100), 2) + ((d.Cantidad_Facturada * d.Valor_Unitario) * (1 - d.Porcentaje_Descuento_1 / 100)) * (d.Porcentaje_Impuesto / 100)) 
                FROM Documentos_Lin d WHERE tipo = $tipo AND Numero_documento = $numdoc),
                descuento_1 = (SELECT SUM(ROUND((d.Cantidad_Facturada * d.Valor_Unitario) * (d.Porcentaje_Descuento_1 / 100), 2)) 
                FROM Documentos_Lin d WHERE tipo = $tipo AND Numero_documento = $numdoc),
                Valor_impuesto = (SELECT SUM(((d.Cantidad_Facturada * d.Valor_Unitario) * (1 - d.Porcentaje_Descuento_1 / 100)) * (d.Porcentaje_Impuesto / 100))
                FROM Documentos_Lin d WHERE tipo = $tipo AND Numero_documento = $numdoc),
                retencion_iva = (SELECT SUM(((d.Cantidad_Facturada * d.Valor_Unitario) * (1 - d.Porcentaje_Descuento_1 / 100)) * (d.Porcentaje_Impuesto / 100)) * 0.15
                FROM Documentos_Lin d WHERE tipo = $tipo AND Numero_documento = $numdoc),
                Retencion_1 = (SELECT SUM(((d.Cantidad_Facturada * d.Valor_Unitario) * (1 - d.Porcentaje_Descuento_1 / 100)) * (d.Porcentaje_ReteFuente / 100) )
                FROM Documentos_Lin d WHERE tipo = $tipo AND Numero_documento = $numdoc)
                WHERE tipo = $tipo AND Numero_Documento = $numdoc";
            }else{
                $sql="UPDATE Documentos SET nit_Cedula_2 = '$nit', codigo_direccion_2 = '$direccion', Numero_Docto_Base = '$traslfact', notas = '$notas', exportado = 'S', IdVendedor = '$remision', IdTransportador = '$idTransportador', IdVehiculo = '$idVehiculo',
                Total_Items = (SELECT COUNT(*) FROM Documentos_Lin WHERE tipo = $tipo AND Numero_documento = $numdoc),
                valor_total = (SELECT SUM(ROUND((d.Cantidad_Facturada * d.Valor_Unitario) * (1 - d.Porcentaje_Descuento_1 / 100), 2) + ((d.Cantidad_Facturada * d.Valor_Unitario) * (1 - d.Porcentaje_Descuento_1 / 100)) * (d.Porcentaje_Impuesto / 100)) 
                FROM Documentos_Lin d WHERE tipo = $tipo AND Numero_documento = $numdoc),
                costo = (SELECT SUM(ROUND((d.Cantidad_Facturada * d.Valor_Unitario) * (1 - d.Porcentaje_Descuento_1 / 100), 2) + ((d.Cantidad_Facturada * d.Valor_Unitario) * (1 - d.Porcentaje_Descuento_1 / 100)) * (d.Porcentaje_Impuesto / 100)) 
                FROM Documentos_Lin d WHERE tipo = $tipo AND Numero_documento = $numdoc),
                valor_aplicado = (SELECT SUM(ROUND((d.Cantidad_Facturada * d.Valor_Unitario) * (1 - d.Porcentaje_Descuento_1 / 100), 2) + ((d.Cantidad_Facturada * d.Valor_Unitario) * (1 - d.Porcentaje_Descuento_1 / 100)) * (d.Porcentaje_Impuesto / 100)) 
                FROM Documentos_Lin d WHERE tipo = $tipo AND Numero_documento = $numdoc),
                descuento_1 = (SELECT SUM(ROUND((d.Cantidad_Facturada * d.Valor_Unitario) * (d.Porcentaje_Descuento_1 / 100), 2)) 
                FROM Documentos_Lin d WHERE tipo = $tipo AND Numero_documento = $numdoc),
                Valor_impuesto = (SELECT SUM(((d.Cantidad_Facturada * d.Valor_Unitario) * (1 - d.Porcentaje_Descuento_1 / 100)) * (d.Porcentaje_Impuesto / 100))
                FROM Documentos_Lin d WHERE tipo = $tipo AND Numero_documento = $numdoc),
                retencion_iva = (SELECT SUM(((d.Cantidad_Facturada * d.Valor_Unitario) * (1 - d.Porcentaje_Descuento_1 / 100)) * (d.Porcentaje_Impuesto / 100)) * 0.15
                FROM Documentos_Lin d WHERE tipo = $tipo AND Numero_documento = $numdoc),
                Retencion_1 = (SELECT SUM(((d.Cantidad_Facturada * d.Valor_Unitario) * (1 - d.Porcentaje_Descuento_1 / 100)) * (d.Porcentaje_ReteFuente / 100) )
                FROM Documentos_Lin d WHERE tipo = $tipo AND Numero_documento = $numdoc)
                WHERE tipo = $tipo AND Numero_Documento = $numdoc";
            }

            $registros = sqlsrv_prepare($cn->getConecta(), $sql);            
            if(sqlsrv_execute($registros)){
                echo"Se Actualizaron las Entradas Correctamente \n";
            }else{
                echo"No se Actualizo las Entradas \n";
            }

             // Obtener el número insertado en Documentos
             $ica = sqlsrv_fetch_array(sqlsrv_query($cn->getConecta(), "SELECT Porcentaje FROM TblTerceros t, TblRete_Ica i, Documentos d 
             WHERE t.nit_cedula = d.nit_Cedula AND t.codigo_ica = i.IdRete_Ica AND tipo = $tipo AND Numero_documento = $numdoc "), SQLSRV_FETCH_ASSOC);
             $por_ica = number_format($ica['Porcentaje'],2);
            //echo "Por ICA ".$por_ica;
             $sql1="UPDATE Documentos SET 
                retencion_ica = (SELECT SUM(ROUND((d.Cantidad_Facturada * d.Valor_Unitario) * (1 - d.Porcentaje_Descuento_1 / 100), 2) * ($por_ica / 100))
                FROM Documentos_Lin d WHERE tipo = $tipo AND Numero_documento = $numdoc)
                WHERE tipo = $tipo AND Numero_Documento = $numdoc";
            
            $registros1 = sqlsrv_prepare($cn->getConecta(), $sql1);            
                if(sqlsrv_execute($registros1)){
                    echo"Se Actualizo el ICA Correctamente \n";
                }else{
                    echo"No se Actualizo el ICA \n";
                }

            $sql2="UPDATE Documentos_Lin SET Numero_Docto_Base = '$traslfact'
                WHERE tipo = '$tipo' AND Numero_Documento = '$numdoc' ";
            $registros = sqlsrv_prepare($cn->getConecta(), $sql2);            
                if(sqlsrv_execute($registros)){
                    echo"Se Actualizo el doc base de la Entrada Correctamente \n";
                }else{
                    echo"No se Actualizo el doc base de la Entrada \n";
                }

            $sql5="(EXEC UPDATE_PRODUCTO_STO )";
            $registros =  sqlsrv_prepare($cn->getConecta(), $sql5);
            if(sqlsrv_execute($registros)){
                echo" No Actualizado Procedimiento Almacenado \n";
            }else{
                echo" ".$registros; 
                echo" Procedimiento Almacenado Actualizado correctamente \n";
            }

        }

        public function listar_documentos_fecha(){
            $cn = new Conectarserver;
            $sql="SELECT tipo, Numero_documento, Numero_Docto_Base_2, notas, usuario, CAST(Fecha_Hora_Factura AS date) AS Fecha_Hora_Factura 
            FROM Documentos WHERE Fecha_Hora_Factura >= DATEADD(day, -100, GETDATE())";
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

        public function duplicar_linea($tipo, $consecutivo, $producto, $seq) {
            $cn = new Conectarserver;
            
            error_log("🔍 Iniciando duplicación para: tipo=$tipo, consecutivo=$consecutivo, producto=$producto, seq=$seq");
            
            try {
                $conexion = $cn->getConecta();
                
                // 1. Obtener el próximo seq PRIMERO
                $sql_seq = "SELECT ISNULL(MAX(CAST(seq AS INT)), 0) + 1 as next_seq 
                        FROM Documentos_Lin 
                        WHERE tipo = ? AND Numero_Documento = ?";
                $params_seq = array($tipo, $consecutivo);
                
                $stmt_seq = sqlsrv_query($conexion, $sql_seq, $params_seq);
                if ($stmt_seq === false) {
                    error_log("❌ Error al obtener secuencia: " . print_r(sqlsrv_errors(), true));
                    return false;
                }
                
                $row_seq = sqlsrv_fetch_array($stmt_seq, SQLSRV_FETCH_ASSOC);
                $next_seq = $row_seq['next_seq'];
                error_log("🔢 Próximo seq: " . $next_seq);
                sqlsrv_free_stmt($stmt_seq);
                
                // 2. Construir INSERT directo usando SELECT
                // Esta es la forma más segura de duplicar TODOS los campos
                $sql_duplicar = "
                    INSERT INTO Documentos_Lin (
                        sw, tipo, seq, Modelo, Numero_Documento, Numero_Docto_Base, 
                        Numero_Lote, Nit_Cedula, Codigo_Direccion, Fecha_Documento,
                        IdProducto, IdUnidad, Factor_Conversion, Cantidad_Facturada,
                        Cantidad_Pendiente, Cantidad_Orden, Costo_Unitario, Valor_Unitario,
                        Valor_Impuesto, Porcentaje_Impuesto, Porcentaje_Descuento_1,
                        Porcentaje_Descuento_2, Porcentaje_Descuento_3, IdVendedor,
                        Comision_Vendedor, Valor_Comision_Vendedor, IdBodega,
                        Maneja_Inventario, Tomador, IdMoneda, Tasa_Moneda_Ext,
                        CentroDeCostosDoc, Nota_Linea, Unidades, Fecha_Vence, Exportado,
                        Costo_Unitario_Inicial, Costo_Flete, Porcentaje_ReteFuente,
                        Envase, Numero_Lote_Destino, Serial, Impuesto_Consumo,
                        Porcentaje_ReteFuente_2, Porcentaje_ReteFuente_3,
                        Porcentaje_ReteFuente_4, Emp_1, Emp_2, Emp_3, Emp_4, Emp_5,
                        Emp_6, Emp_7, Emp_8, Tara_1, Tara_2, Tara_3, Tara_4, Tara_5,
                        Tara_6, Tara_7, Tara_8, Bodega, IdFormaEnvio, IdOtroImpuesto,
                        TarifaOtroImpuesto
                    )
                    SELECT 
                        sw, tipo, ?, Modelo, Numero_Documento, Numero_Docto_Base,
                        Numero_Lote, Nit_Cedula, Codigo_Direccion, Fecha_Documento,
                        IdProducto, IdUnidad, Factor_Conversion, Cantidad_Facturada,
                        Cantidad_Pendiente, Cantidad_Orden, Costo_Unitario, Valor_Unitario,
                        Valor_Impuesto, Porcentaje_Impuesto, Porcentaje_Descuento_1,
                        Porcentaje_Descuento_2, Porcentaje_Descuento_3, IdVendedor,
                        Comision_Vendedor, Valor_Comision_Vendedor, IdBodega,
                        Maneja_Inventario, Tomador, IdMoneda, Tasa_Moneda_Ext,
                        CentroDeCostosDoc, Nota_Linea, Unidades, Fecha_Vence, Exportado,
                        Costo_Unitario_Inicial, Costo_Flete, Porcentaje_ReteFuente,
                        Envase, Numero_Lote_Destino, Serial, Impuesto_Consumo,
                        Porcentaje_ReteFuente_2, Porcentaje_ReteFuente_3,
                        Porcentaje_ReteFuente_4, Emp_1, Emp_2, Emp_3, Emp_4, Emp_5,
                        Emp_6, Emp_7, Emp_8, Tara_1, Tara_2, Tara_3, Tara_4, Tara_5,
                        Tara_6, Tara_7, Tara_8, Bodega, IdFormaEnvio, IdOtroImpuesto,
                        TarifaOtroImpuesto
                    FROM Documentos_Lin
                    WHERE tipo = ? 
                        AND Numero_Documento = ? 
                        AND IdProducto = ? 
                        AND seq = ?
                ";
                
                // Parámetros: next_seq, tipo, consecutivo, producto, seq
                $params_duplicar = array($next_seq, $tipo, $consecutivo, $producto, $seq);
                
                error_log("📝 Ejecutando INSERT con SELECT");
                error_log("📝 Parámetros: " . print_r($params_duplicar, true));
                
                $stmt_duplicar = sqlsrv_query($conexion, $sql_duplicar, $params_duplicar);
                
                if ($stmt_duplicar === false) {
                    $errors = sqlsrv_errors();
                    error_log("❌ Error al ejecutar INSERT: " . print_r($errors, true));
                    return false;
                }
                
                $filas_afectadas = sqlsrv_rows_affected($stmt_duplicar);
                error_log("✅ Línea duplicada exitosamente. Filas afectadas: " . $filas_afectadas);
                
                sqlsrv_free_stmt($stmt_duplicar);
                
                // 3. Actualizar totales si existe la función
                if (method_exists($this, 'actualizar_totales_documento')) {
                    $actualizacion_totales = $this->actualizar_totales_documento($tipo, $consecutivo);
                    error_log("📊 Actualización de totales: " . ($actualizacion_totales ? "EXITOSA" : "FALLIDA"));
                }
                
                return true;
                
            } catch (Exception $e) {
                error_log("💥 Excepción al duplicar línea: " . $e->getMessage());
                error_log("💥 Stack trace: " . $e->getTraceAsString());
                return false;
            }
        }

        public function combo_transportador(){
            $cn = new Conectarserver;
            $sql = "SELECT IdTransportador, Transportador FROM tblTransportador ORDER BY Transportador";
            $registros = sqlsrv_query($cn->getConecta(), $sql);
            $html = '<option value="1">-- Seleccione --</option>';
            if($registros){
                while($row = sqlsrv_fetch_array($registros, SQLSRV_FETCH_ASSOC)){
                    $html .= '<option value="'.$row['IdTransportador'].'">'.$row['Transportador'].'</option>';
                }
            }
            return $html;
        }

        public function combo_vehiculo(){
            $cn = new Conectarserver;
            $sql = "SELECT IdVehiculo, Vehiculo FROM TblVehiculo ORDER BY Vehiculo";
            $registros = sqlsrv_query($cn->getConecta(), $sql);
            $html = '<option value="1">-- Seleccione --</option>';
            if($registros){
                while($row = sqlsrv_fetch_array($registros, SQLSRV_FETCH_ASSOC)){
                    $html .= '<option value="'.$row['IdVehiculo'].'">'.$row['Vehiculo'].'</option>';
                }
            }
            return $html;
        }

    }
?>
