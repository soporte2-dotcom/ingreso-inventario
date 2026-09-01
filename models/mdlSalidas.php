<?php
    // Bitácora en MySQL de toda operación que crea, modifica o borra líneas de
    // documentos. SQL Server es la base heredada y no admite triggers propios.
    require_once(dirname(__FILE__) . '/AuditoriaDocumentos.php');

    class Salidas extends Conectarserver{

      // Lista documentos de Salida/Consumo visibles para el usuario según los tipos de documento
      // que tiene permiso de ver (no según quién los creó), acotado opcionalmente por
      // rango de fecha, rango de número de documento y estado de exportado.
      public function listar_salidas_filtro($tipos_permitidos, $tipo, $fechaDesde, $fechaHasta, $numDesde, $numHasta, $exportado = '', $anulado = ''){
        $cn = new Conectarserver;
        $resultado = array();

        if (empty($tipos_permitidos)) {
            return $resultado;
        }

        $tiposFiltro = ($tipo !== '' && in_array($tipo, $tipos_permitidos)) ? array($tipo) : $tipos_permitidos;

        $placeholders = implode(',', array_fill(0, count($tiposFiltro), '?'));
        $params = $tiposFiltro;

        // Numero_documento > 0 excluye los BORRADORES (números negativos, consecutivo diferido).
        $where = "tt.idTipoDoctos IN ($placeholders) AND tt.tipo IN ('11', '2')
                  AND tt.idTipoDoctos = d.tipo AND td.nit = d.nit_Cedula AND d.codigo_direccion = td.codigo_direccion
                  AND t.nit_cedula = d.nit_Cedula AND d.Numero_documento > 0";

        if ($fechaDesde !== '') {
            $where .= " AND CONVERT(date, d.Fecha_Hora_Factura) >= ?";
            $params[] = $fechaDesde;
        }
        if ($fechaHasta !== '') {
            $where .= " AND CONVERT(date, d.Fecha_Hora_Factura) <= ?";
            $params[] = $fechaHasta;
        }
        if ($numDesde !== '') {
            $where .= " AND d.Numero_documento >= ?";
            $params[] = (int)$numDesde;
        }
        if ($numHasta !== '') {
            $where .= " AND d.Numero_documento <= ?";
            $params[] = (int)$numHasta;
        }
        if ($exportado === 'S' || $exportado === 'N') {
            $where .= " AND d.exportado = ?";
            $params[] = $exportado;
        }
        if ($anulado === 'S' || $anulado === 'N') {
            $where .= " AND d.anulado = ?";
            $params[] = $anulado;
        }

        $sql = "SELECT d.Fecha_Hora_Factura, d.tipo, tt.TipoDoctos, d.Numero_documento, d.Numero_Docto_Base, d.Tipo_Docto_Base, d.Tipo_Docto_Base_2, d.Numero_Docto_Base_2,
                d.nit_Cedula, d.Nombre_Cliente, d.codigo_direccion, td.direccion, td.telefono_1, d.exportado, d.anulado, d.usuario
                FROM Documentos d, Terceros_Dir td, TblTipoDoctos tt, TblTerceros t
                WHERE $where
                ORDER BY d.Fecha_Hora_Factura DESC";

        $registros = sqlsrv_query($cn->getConecta(), $sql, $params);

        if($registros === false) {
            $this->registrar_error("Error en listar_salidas_filtro: " . print_r(sqlsrv_errors(), true));
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

        // ─── BORRADOR / CONSECUTIVO DIFERIDO ─────────────────────────────────
        // El documento se crea como BORRADOR con Numero_Documento NEGATIVO (= -id de
        // la tabla MySQL salida_borrador_seq) y NO consume consecutivo. El número real
        // se reserva solo al Guardar (ver guardar_salida). Así no quedan documentos
        // vacíos ni huecos en la numeración si el usuario abandona el proceso.

        // Abre la conexión al MySQL de permisos_tecno (donde vive salida_borrador_seq).
        // Mismo patrón que Documento::abrir_conexion_mysql.
        private function abrir_conexion_mysql() {
            require_once(dirname(__FILE__) . '/../config/conexionmysql.php');
            $my = new ConectarMysql();
            $pdo = $my->obtenerConexion();
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $pdo;
        }

        // Reserva el consecutivo REAL del tipo de documento de forma atómica: el UPDATE
        // toma un lock de fila que dura hasta el COMMIT, así que dos creaciones simultáneas
        // quedan serializadas en vez de recibir el mismo número. Debe llamarse DENTRO de la
        // transacción, para que un fallo posterior devuelva el número al consecutivo.
        private function reservar_consecutivo_real($conn, $tipo) {
            $stmt = sqlsrv_query($conn,
                "UPDATE Consecutivos SET siguiente = siguiente + 1
                 OUTPUT INSERTED.siguiente AS nuevo
                 WHERE tipo = ?",
                array($tipo));
            if ($stmt === false) {
                throw new Exception("Error al reservar consecutivo: " . print_r(sqlsrv_errors(), true));
            }
            $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            if (!$row) {
                throw new Exception("No existe un consecutivo configurado para el tipo de documento $tipo");
            }
            return (int)$row['nuevo'];
        }

        // ─── DESACTIVADO: consecutivo diferido / borradores negativos ─────────────
        // Se dejó de usar por petición del negocio: los números negativos se filtraban
        // hacia pantallas que no los esperaban y complicaban más de lo que resolvían.
        // Las funciones de abajo (reservar_id_borrador, marcar_borrador,
        // descartar_borrador, purgar_borradores_salida) quedan en el código, sin usarse,
        // para poder reactivar el mecanismo cuando se retome. Mientras tanto son inertes:
        // descartar/purgar solo actúan sobre números negativos, y ya no se generan.

        // Reserva un id negativo único de borrador. AUTO_INCREMENT garantiza unicidad
        // global de forma atómica, sin bloquear la tabla caliente Documentos.
        // Devuelve el Numero_Documento negativo a usar. Lanza excepción si MySQL falla.
        private function reservar_id_borrador($tipo, $usuario) {
            $pdo = $this->abrir_conexion_mysql();
            $ins = $pdo->prepare("INSERT INTO salida_borrador_seq (tipo, usuario) VALUES (?, ?)");
            $ins->execute([(int)$tipo, (string)$usuario]);
            $id = (int)$pdo->lastInsertId();
            if ($id <= 0) {
                throw new Exception("No se pudo reservar el id de borrador.");
            }
            return -$id;
        }

        // Marca en el registro MySQL el destino final del borrador (best-effort: nunca
        // interrumpe el flujo principal si MySQL no responde).
        private function marcar_borrador($draftNeg, $estado, $numeroReal = null) {
            if ((int)$draftNeg >= 0) return; // no era borrador
            try {
                $pdo = $this->abrir_conexion_mysql();
                $up = $pdo->prepare("UPDATE salida_borrador_seq SET estado = ?, numero_real = ? WHERE id = ?");
                $up->execute([$estado, $numeroReal !== null ? (int)$numeroReal : null, abs((int)$draftNeg)]);
            } catch (Exception $e) {
                $this->registrar_error("marcar_borrador ($estado) id=" . abs((int)$draftNeg) . ": " . $e->getMessage());
            }
        }

        // Descarta un borrador concreto (encabezado + líneas). Se llama, best-effort, cuando el
        // usuario cierra/abandona la pantalla sin guardar (sendBeacon). Idempotente y seguro:
        // solo borra si el número es negativo (borrador) y exportado='N' (nunca un doc guardado).
        public function descartar_borrador($tipo, $numdoc) {
            $numdoc = (int)$numdoc;
            if ($numdoc >= 0) {
                return json_encode(array("status" => "ok", "message" => "No es un borrador; nada que descartar."));
            }
            $cn = new Conectarserver;
            $conn = $cn->getConecta();
            try {
                // Se fotografía antes de borrar aunque sea "solo un borrador": si alguna vez
                // se descarta algo que no debía, la bitácora permite reconstruirlo.
                $snap = AuditoriaDocumentos::snapshotLineas($conn, $tipo, $numdoc);
                $stmtLin = sqlsrv_query($conn, "DELETE FROM Documentos_Lin WHERE tipo = ? AND Numero_Documento = ?", array($tipo, $numdoc));
                sqlsrv_query($conn, "DELETE FROM Documentos WHERE tipo = ? AND Numero_Documento = ? AND exportado = 'N'", array($tipo, $numdoc));
                $this->marcar_borrador($numdoc, 'descartado');
                AuditoriaDocumentos::registrar(array(
                    'modulo' => 'Salidas', 'operacion' => 'descartar_borrador', 'destructiva' => 1,
                    'tipo' => $tipo, 'numero' => $numdoc,
                    'lineas_antes' => $snap['lineas'], 'lineas_despues' => 0,
                    'filas_afectadas' => $stmtLin ? sqlsrv_rows_affected($stmtLin) : null,
                    'resultado' => 'ok', 'mensaje' => 'Borrador abandonado por el usuario',
                    'detalle_antes' => $snap['detalle']
                ));
                return json_encode(array("status" => "ok"));
            } catch (Exception $e) {
                $this->registrar_error("descartar_borrador tipo=$tipo num=$numdoc: " . $e->getMessage());
                return json_encode(array("status" => "error", "message" => $e->getMessage()));
            }
        }

        // Barrido autoritativo: elimina borradores (Numero_Documento negativo, exportado='N') más
        // viejos que $horas. Cubre los casos donde el sendBeacon de descarte no llegó (crash, corte).
        // Los borradores son inertes (no tocan stock ni consecutivos), así que borrarlos es seguro.
        public function purgar_borradores_salida($horas = 12) {
            $cn = new Conectarserver;
            $conn = $cn->getConecta();
            $horas = (int)$horas;
            try {
                $stmtLin = sqlsrv_query($conn,
                    "DELETE dl FROM Documentos_Lin dl
                     INNER JOIN Documentos d ON d.tipo = dl.tipo AND d.Numero_Documento = dl.Numero_Documento
                     WHERE d.Numero_Documento < 0 AND d.exportado = 'N'
                       AND d.fecha_hora < DATEADD(HOUR, -?, GETDATE())",
                    array($horas));
                $stmtDoc = sqlsrv_query($conn,
                    "DELETE FROM Documentos
                     WHERE Numero_Documento < 0 AND exportado = 'N'
                       AND fecha_hora < DATEADD(HOUR, -?, GETDATE())",
                    array($horas));

                // Solo se registra cuando realmente barrió algo, para no llenar la bitácora
                // con un registro por cada carga de pantalla.
                $filasLin = $stmtLin ? sqlsrv_rows_affected($stmtLin) : 0;
                $filasDoc = $stmtDoc ? sqlsrv_rows_affected($stmtDoc) : 0;
                if ($filasLin > 0 || $filasDoc > 0) {
                    AuditoriaDocumentos::registrar(array(
                        'modulo' => 'Salidas', 'operacion' => 'purgar_borradores', 'destructiva' => 1,
                        'filas_afectadas' => $filasLin,
                        'resultado' => 'ok',
                        'mensaje' => "Barrido de borradores > $horas h: $filasDoc encabezados, $filasLin líneas"
                    ));
                }
            } catch (Exception $e) {
                $this->registrar_error("purgar_borradores_salida: " . $e->getMessage());
                AuditoriaDocumentos::registrar(array(
                    'modulo' => 'Salidas', 'operacion' => 'purgar_borradores', 'destructiva' => 1,
                    'resultado' => 'error', 'mensaje' => $e->getMessage()
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

                // El documento se crea directamente con su consecutivo real.
                $draft = $this->reservar_consecutivo_real($cn->getConecta(), $tipo);

                $sql = "INSERT INTO Documentos(sw, tipo, modelo, Numero_Documento, Numero_Docto_Base,
                nit_Cedula, codigo_direccion, Fecha_Hora_Factura, Fecha_Hora_Vencimiento, Fecha_orden_Venta,
                condicion, valor_total, valor_aplicado, Retencion_1, Retencion_2, Retencion_3, retencion_causada, retencion_iva, retencion_ica,
                retencion_descuento, descuento_pie, DescuentoOrdenVenta, descuento_1, descuento_2, descuento_3, costo, IdVendedor, anulado, usuario,
                notas, pc, fecha_hora, duracion, bodega, Valor_impuesto, Impuesto_Consumo, impuesto_deporte, concepto, vencimiento_presup,
                exportado, prefijo, moneda, CentroDeCostosDoc, valor_mercancia, abono, Comision_Vendedor, Tasa_Moneda_Ext, Tomador, Tasa_Fija_o_Variable, Punto_FOB,
                Fletes_Moneda_Ext, Miselaneos_Moneda_Ext, Cargo_Por_Fletes, Impuesto_Por_Fletes, Total_Items, Nombre_Cliente, Ordenado_Por, Telefono_De_Envio_1,
                Telefono_De_Envio_2, Factura_Impresa, IdFormaEnvio, IdTransportador, nit_Cedula_2, codigo_direccion_2, Numero_Docto_Base_2, Tipo_Docto_Base,
                Tipo_Docto_Base_2, IdActividadEconomica, TarifaReteFuenteCree, Valor_ReteCree, IdVehiculo)

                (SELECT td.tipo AS sw, '$tipo' AS tipo, '$tipo' AS modelo, $draft AS Numero_Documento, '' AS Numero_Docto_Base,
                '$nit1' AS nit_Cedula, '$dir1' AS codigo_direccion, CONVERT(datetime,'$fecha_factura',120) AS Fecha_Hora_Factura, GETDATE() AS Fecha_Hora_Vencimiento, GETDATE() AS Fecha_orden_Venta,
                t.condicion AS condicion, 0 AS valor_total, 0 AS valor_aplicado, 0 AS Retencion_1, 0 AS Retencion_2, 0 AS Retencion_3,
                0 AS retencion_causada, 0 AS retencion_iva, 0 AS retencion_ica, 0 AS retencion_descuento, 0 AS descuento_pie, 0 AS DescuentoOrdenVenta,
                0 AS descuento_1, 0 AS descuento_2, 0 AS descuento_3, 0 AS costo, 0 AS IdVendedor, 'N' AS anulado, '$usuario' AS usuario,
                '' AS notas, HOST_NAME() AS pc, GETDATE() AS fecha_hora, 0 AS duracion, td.IdBodega AS bodega,
                0 AS Valor_impuesto, 0 AS Impuesto_Consumo, 0 AS impuesto_deporte, '' AS concepto, GETDATE() AS vencimiento_presup,
                'N' AS exportado, ISNULL(td.Prefijo, '0') AS prefijo, 1 AS moneda, 0 AS CentroDeCostosDoc, 0 AS valor_mercancia, 0 AS abono, 0 AS Comision_Vendedor,
                1 AS Tasa_Moneda_Ext, '' AS Tomador, 'V' AS Tasa_Fija_o_Variable, 0 AS Punto_FOB,
                0 AS Fletes_Moneda_Ext, 0 AS Miselaneos_Moneda_Ext, 0 AS Cargo_Por_Fletes, 0 AS Impuesto_Por_Fletes, 0 AS Total_Items,
                t.nombre AS Nombre_Cliente, '' AS Ordenado_Por, '' AS Telefono_De_Envio_1, '' AS Telefono_De_Envio_2, 'N' AS Factura_Impresa,
                0 AS IdFormaEnvio, 0 AS IdTransportador,
                '$nit2' AS nit_Cedula_2, '$dir2' AS codigo_direccion_2, '' AS Numero_Docto_Base_2, '0' AS Tipo_Docto_Base,
                '2' AS Tipo_Docto_Base_2, '0' AS IdActividadEconomica, 0 AS TarifaReteFuenteCree, 0 AS Valor_ReteCree, '1' AS IdVehiculo

                FROM TblTerceros t, TblTipoDoctos td
                WHERE td.idTipoDoctos = '$tipo' AND t.nit_cedula = '$nit1')";

                $registros = sqlsrv_prepare($cn->getConecta(), $sql);
                if (sqlsrv_execute($registros) === false) {
                    throw new Exception("Error al insertar documento: " . print_r(sqlsrv_errors(), true));
                }

                sqlsrv_commit($cn->getConecta());

                return json_encode(array(
                    "status" => "success",
                    "message" => "Documento manual registrado correctamente",
                    "tipo" => $tipo,
                    "consecutivo" => $draft
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

        public function guardar_salida($tipo, $numdoc, $nit1, $direccion1, $nit2, $direccion2, $despacho, $notas, $dotacion = false, $fecha_factura = '', $idTransportador = '1', $idVehiculo = '1'){
            $cn = new Conectarserver;
            $conn = $cn->getConecta();

            // Guarda de robustez: el número debe ser un entero (negativo=borrador, positivo=heredado).
            // Un valor vacío/no numérico haría que no se actualice ninguna fila silenciosamente.
            if (!is_numeric($numdoc) || (int)$numdoc === 0) {
                return json_encode(array(
                    "status"  => "error",
                    "message" => "No se pudo identificar el documento a guardar (número inválido: '" . $numdoc . "'). Recargue la página e intente de nuevo."
                ));
            }

            $idTransportador = $idTransportador ?: '1';
            $idVehiculo      = $idVehiculo      ?: '1';
            $idVendedorSql = $dotacion ? ", IdVendedor = 12" : "";
            $fechaSql = $fecha_factura ? ", Fecha_Hora_Factura = CONVERT(datetime,'$fecha_factura',120)" : "";

            // El consecutivo diferido está DESACTIVADO (ver la nota junto a reservar_id_borrador):
            // los documentos se crean con su número real, así que $esBorrador siempre es false y
            // la rama de renumeración de abajo no se ejecuta. Se conserva intacta para poder
            // reactivar el mecanismo sin volver a escribirla; los documentos negativos que
            // quedaron de antes siguen guardándose bien si alguien abre uno.
            $esBorrador = ((int)$numdoc < 0);
            $draftNeg   = (int)$numdoc;
            $finalNum   = (int)$numdoc; // número definitivo (real para borrador, el mismo para heredados)

            try {
                if ($esBorrador) {
                    sqlsrv_begin_transaction($conn);

                    // 1. Reservar consecutivo real (lock de fila atómico hasta el commit).
                    $stmtSeq = sqlsrv_query($conn,
                        "UPDATE Consecutivos SET siguiente = siguiente + 1 OUTPUT INSERTED.siguiente AS nuevo WHERE tipo = ?",
                        array($tipo));
                    if ($stmtSeq === false) {
                        throw new Exception("Error al reservar consecutivo: " . print_r(sqlsrv_errors(), true));
                    }
                    $rowSeq = sqlsrv_fetch_array($stmtSeq, SQLSRV_FETCH_ASSOC);
                    if (!$rowSeq) {
                        throw new Exception("No existe un consecutivo configurado para el tipo de documento $tipo");
                    }
                    $finalNum = (int)$rowSeq['nuevo'];

                    // 2. Renumerar las líneas del borrador al número real.
                    $stmtLin = sqlsrv_query($conn,
                        "UPDATE Documentos_Lin SET Numero_Documento = ? WHERE tipo = ? AND Numero_documento = ?",
                        array($finalNum, $tipo, $draftNeg));
                    if ($stmtLin === false) {
                        throw new Exception("Error al renumerar líneas: " . print_r(sqlsrv_errors(), true));
                    }

                    // 3. Renumerar + finalizar el encabezado (totales calculados ya sobre el número real).
                    //    El WHERE exige exportado='N' y el número de borrador: si otra pestaña ya lo guardó,
                    //    afecta 0 filas y se hace ROLLBACK → el consecutivo reservado NO se consume.
                    $sqlHead = "UPDATE Documentos SET
                        Numero_Documento = $finalNum,
                        nit_Cedula = '$nit1', codigo_direccion = '$direccion1',
                        nit_Cedula_2 = '$nit2', codigo_direccion_2 = '$direccion2',
                        Numero_Docto_Base = '$despacho', notas = '$notas', exportado = 'S',
                        IdTransportador = '$idTransportador', IdVehiculo = '$idVehiculo' $idVendedorSql $fechaSql,
                        Total_Items = (SELECT COUNT(*) FROM Documentos_Lin WHERE tipo = $tipo AND Numero_documento = $finalNum),
                        valor_total = (SELECT SUM(ROUND((d.Cantidad_Facturada * d.Valor_Unitario) * (1 - d.Porcentaje_Descuento_1 / 100), 2) + ((d.Cantidad_Facturada * d.Valor_Unitario) * (1 - d.Porcentaje_Descuento_1 / 100)) * (d.Porcentaje_Impuesto / 100)) FROM Documentos_Lin d WHERE tipo = $tipo AND Numero_documento = $finalNum),
                        costo = (SELECT SUM(d.Cantidad_Facturada * d.Costo_Unitario) FROM Documentos_Lin d WHERE tipo = $tipo AND Numero_documento = $finalNum),
                        descuento_1 = (SELECT SUM(ROUND((d.Cantidad_Facturada * d.Valor_Unitario) * (d.Porcentaje_Descuento_1 / 100), 2)) FROM Documentos_Lin d WHERE tipo = $tipo AND Numero_documento = $finalNum),
                        Valor_impuesto = (SELECT SUM(((d.Cantidad_Facturada * d.Valor_Unitario) * (1 - d.Porcentaje_Descuento_1 / 100)) * (d.Porcentaje_Impuesto / 100)) FROM Documentos_Lin d WHERE tipo = $tipo AND Numero_documento = $finalNum)
                        WHERE tipo = $tipo AND Numero_Documento = $draftNeg AND exportado = 'N'";
                    $stmtHead = sqlsrv_query($conn, $sqlHead);
                    if ($stmtHead === false) {
                        throw new Exception("Error al guardar la salida: " . print_r(sqlsrv_errors(), true));
                    }
                    if (sqlsrv_rows_affected($stmtHead) < 1) {
                        // El borrador ya no existe / ya fue guardado (p. ej. desde otra pestaña):
                        // se revierte TODO (incluida la reserva del consecutivo) para no dejar huecos.
                        sqlsrv_rollback($conn);
                        AuditoriaDocumentos::registrar(array(
                            'modulo' => 'Salidas', 'operacion' => 'guardar_salida',
                            'tipo' => $tipo, 'numero' => $draftNeg,
                            'resultado' => 'bloqueado',
                            'mensaje' => "El borrador $draftNeg ya no existe o ya fue guardado; se revirtió el consecutivo $finalNum"
                        ));
                        return json_encode(array(
                            "status"  => "error",
                            "code"    => "ya_guardado",
                            "message" => "Este borrador ya fue guardado. Se recargará el documento."
                        ));
                    }

                    sqlsrv_commit($conn);
                    $this->marcar_borrador($draftNeg, 'guardado', $finalNum);

                } else {
                    // Flujo heredado: documento con número real ya asignado (exportado='N'); solo finalizar.
                    // Se castea a int ($numInt) para no interpolar nunca un valor no numérico en el SQL.
                    $numInt = (int)$numdoc;
                    $sqlHead = "UPDATE Documentos SET
                        nit_Cedula = '$nit1', codigo_direccion = '$direccion1',
                        nit_Cedula_2 = '$nit2', codigo_direccion_2 = '$direccion2',
                        Numero_Docto_Base = '$despacho', notas = '$notas', exportado = 'S',
                        IdTransportador = '$idTransportador', IdVehiculo = '$idVehiculo' $idVendedorSql $fechaSql,
                        Total_Items = (SELECT COUNT(*) FROM Documentos_Lin WHERE tipo = $tipo AND Numero_documento = $numInt),
                        valor_total = (SELECT SUM(ROUND((d.Cantidad_Facturada * d.Valor_Unitario) * (1 - d.Porcentaje_Descuento_1 / 100), 2) + ((d.Cantidad_Facturada * d.Valor_Unitario) * (1 - d.Porcentaje_Descuento_1 / 100)) * (d.Porcentaje_Impuesto / 100)) FROM Documentos_Lin d WHERE tipo = $tipo AND Numero_documento = $numInt),
                        costo = (SELECT SUM(d.Cantidad_Facturada * d.Costo_Unitario) FROM Documentos_Lin d WHERE tipo = $tipo AND Numero_documento = $numInt),
                        descuento_1 = (SELECT SUM(ROUND((d.Cantidad_Facturada * d.Valor_Unitario) * (d.Porcentaje_Descuento_1 / 100), 2)) FROM Documentos_Lin d WHERE tipo = $tipo AND Numero_documento = $numInt),
                        Valor_impuesto = (SELECT SUM(((d.Cantidad_Facturada * d.Valor_Unitario) * (1 - d.Porcentaje_Descuento_1 / 100)) * (d.Porcentaje_Impuesto / 100)) FROM Documentos_Lin d WHERE tipo = $tipo AND Numero_documento = $numInt)
                        WHERE tipo = $tipo AND Numero_Documento = $numInt";
                    $stmtHead = sqlsrv_query($conn, $sqlHead);
                    if ($stmtHead === false) {
                        throw new Exception("Error al guardar la salida: " . print_r(sqlsrv_errors(), true));
                    }
                    $finalNum = $numInt;
                }

                // ─── Post-proceso común, sobre el número DEFINITIVO ($finalNum) ───
                if ($dotacion) {
                    sqlsrv_query($conn,
                        "UPDATE Documentos_Lin SET IdVendedor = 12 WHERE tipo = ? AND Numero_Documento = ?",
                        array($tipo, $finalNum));
                }

                // Recalcular exportado en Documentos_ped si este documento viene de una OS (Tipo_Docto_Base_2 = '10')
                $sql_os = "SELECT Numero_Docto_Base_2 FROM Documentos
                           WHERE tipo = $tipo AND Numero_Documento = $finalNum
                           AND Tipo_Docto_Base_2 = '10'";
                $stmt_os = sqlsrv_query($conn, $sql_os);
                if ($stmt_os) {
                    $row_os = sqlsrv_fetch_array($stmt_os, SQLSRV_FETCH_ASSOC);
                    if ($row_os && !empty($row_os['Numero_Docto_Base_2'])) {
                        $numero_os = $row_os['Numero_Docto_Base_2'];
                        $sql_chk = "SELECT COUNT(*) AS con_pendiente
                                    FROM Documentos_Lin_Ped dlp
                                    LEFT JOIN (
                                        SELECT dl.IdProducto, dl.seq, SUM(CASE WHEN d.Tipo_Docto_Base = '0' THEN dl.Cantidad_Facturada ELSE -dl.Cantidad_Facturada END) AS total_facturado
                                        FROM Documentos d
                                        JOIN Documentos_Lin dl ON dl.tipo = d.tipo AND dl.Numero_Documento = d.Numero_documento
                                        WHERE d.Numero_Docto_Base_2 = '$numero_os' AND d.Tipo_Docto_Base_2 = '10'
                                        AND d.exportado = 'S'
                                        AND d.bodega = (SELECT bodega FROM Documentos_Ped WHERE numero_pedido = '$numero_os' AND sw = '10')
                                        GROUP BY dl.IdProducto, dl.seq
                                    ) f ON f.IdProducto = dlp.IdProducto AND f.seq = dlp.Linea
                                    WHERE dlp.numero_pedido = '$numero_os' AND dlp.sw = '10'
                                    AND (dlp.cantidad - ISNULL(f.total_facturado, 0)) > 0";
                        $stmt_chk = sqlsrv_query($conn, $sql_chk);
                        $row_chk = sqlsrv_fetch_array($stmt_chk, SQLSRV_FETCH_ASSOC);
                        $exportado_ped = ($row_chk && $row_chk['con_pendiente'] == 0) ? 'S' : 'P';
                        $sql_upd_ped = "UPDATE Documentos_Ped
                                        SET exportado = '$exportado_ped'
                                        WHERE numero_pedido = '$numero_os' AND sw = '10'";
                        sqlsrv_query($conn, $sql_upd_ped);
                    }
                }

                $registros = sqlsrv_prepare($conn, "(EXEC UPDATE_PRODUCTO_STO)");
                sqlsrv_execute($registros);

                // Foto del documento en el momento en que se sella e imprime: es la
                // referencia contra la que se compara cualquier reclamo posterior de
                // "el detalle no es el mismo".
                $snap = AuditoriaDocumentos::snapshotLineas($conn, $tipo, $finalNum);
                AuditoriaDocumentos::registrar(array(
                    'modulo' => 'Salidas', 'operacion' => 'guardar_salida',
                    'tipo' => $tipo, 'numero' => $finalNum,
                    'exportado_antes' => 'N',
                    'lineas_antes' => $snap['lineas'], 'lineas_despues' => $snap['lineas'],
                    'resultado' => 'ok',
                    'mensaje' => 'Documento sellado (exportado = S)'
                             . ($esBorrador ? " desde borrador $draftNeg" : ' (flujo heredado)'),
                    'detalle_antes' => $snap['detalle']
                ));

                return json_encode(array(
                    "status"      => "success",
                    "message"     => "Salida guardada correctamente",
                    "tipo"        => $tipo,
                    "consecutivo" => $finalNum
                ));

            } catch (Exception $e) {
                if ($esBorrador) { @sqlsrv_rollback($conn); }
                $this->registrar_error("Error en guardar_salida: " . $e->getMessage());
                AuditoriaDocumentos::registrar(array(
                    'modulo' => 'Salidas', 'operacion' => 'guardar_salida',
                    'tipo' => $tipo, 'numero' => $finalNum,
                    'resultado' => 'error', 'mensaje' => $e->getMessage()
                ));
                return json_encode(array(
                    "status"  => "error",
                    "message" => $e->getMessage()
                ));
            }
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

            // Un documento guardado o anulado no se puede modificar. Cambiar el lote no
            // altera el número de líneas ni los totales, así que hacerlo sobre un documento
            // ya impreso no descuadraba nada y pasaba inadvertido. Y si $seqs viene vacío,
            // el UPDATE alcanza a TODAS las líneas del documento.
            $sqlChk = "SELECT exportado, anulado FROM Documentos WHERE tipo = ? AND Numero_documento = ?";
            $stmtChk = sqlsrv_query($cn->getConecta(), $sqlChk, array($tipo, $numdoc));
            $rowChk  = $stmtChk ? sqlsrv_fetch_array($stmtChk, SQLSRV_FETCH_ASSOC) : null;
            if (!$rowChk) {
                echo "Documento no encontrado";
                return;
            }
            if (trim($rowChk['exportado']) === 'S' || trim($rowChk['anulado'] ?? 'N') === 'S') {
                AuditoriaDocumentos::registrar(array(
                    'modulo' => 'Salidas', 'operacion' => 'update_lote', 'destructiva' => 1,
                    'tipo' => $tipo, 'numero' => $numdoc,
                    'exportado_antes' => $rowChk['exportado'], 'anulado_antes' => $rowChk['anulado'] ?? 'N',
                    'resultado' => 'bloqueado',
                    'mensaje' => "Intento de cambiar el lote a '$lote' en un documento guardado/anulado. seqs="
                               . ($seqs !== '' ? $seqs : 'TODAS')
                ));
                echo "El documento ya está guardado o anulado, no se puede modificar";
                return;
            }

            $snapLote = AuditoriaDocumentos::snapshotLineas($cn->getConecta(), $tipo, $numdoc);

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
                AuditoriaDocumentos::registrar(array(
                    'modulo' => 'Salidas', 'operacion' => 'update_lote',
                    'tipo' => $tipo, 'numero' => $numdoc,
                    'resultado' => 'error',
                    'mensaje' => "lote=$lote seqs=$seqs - " . print_r(sqlsrv_errors(), true)
                ));
                echo "Error al actualizar lote";
            } else {
                AuditoriaDocumentos::registrar(array(
                    'modulo' => 'Salidas', 'operacion' => 'update_lote', 'destructiva' => 1,
                    'tipo' => $tipo, 'numero' => $numdoc,
                    'exportado_antes' => $rowChk['exportado'], 'anulado_antes' => $rowChk['anulado'] ?? 'N',
                    'lineas_antes' => $snapLote['lineas'], 'lineas_despues' => $snapLote['lineas'],
                    'filas_afectadas' => sqlsrv_rows_affected($stmt),
                    'resultado' => 'ok',
                    'mensaje' => "lote=$lote seqs=" . ($seqs !== '' ? $seqs : 'TODAS'),
                    'detalle_antes' => $snapLote['detalle']
                ));
                echo "Lote actualizado correctamente";
            }
        }

        public function get_lineas_devolucion($tipo, $numero) {
            $cn = new Conectarserver;
            // La subconsulta calcula cuánto ya fue devuelto por ítem (seq)
            // buscando documentos con Numero_Docto_Base = numero original y Tipo_Docto_Base = tipo original
            $sql = "SELECT dl.seq, dl.IdProducto, LTRIM(RTRIM(p.Producto)) AS Producto,
                           LTRIM(RTRIM(u.Unidad)) AS Unidad,
                           dl.Cantidad_Facturada AS cantidad_original,
                           ISNULL((
                               SELECT SUM(dl2.Cantidad_Facturada)
                               FROM Documentos dev
                               INNER JOIN Documentos_Lin dl2
                                   ON dl2.tipo = dev.tipo
                                   AND dl2.Numero_documento = dev.Numero_documento
                                   AND dl2.seq = dl.seq
                               WHERE dev.Numero_Docto_Base = CAST(dl.Numero_documento AS VARCHAR)
                                 AND dev.Tipo_Docto_Base = ?
                                 AND dev.exportado = 'S'
                           ), 0) AS cantidad_devuelta,
                           dl.Valor_Unitario,
                           dl.Porcentaje_Descuento_1, dl.Porcentaje_Impuesto,
                           dl.Numero_Lote, dl.Fecha_Vence
                    FROM Documentos_Lin dl
                    INNER JOIN TblProducto p ON p.IdProducto = dl.IdProducto
                    INNER JOIN TblUnidad u ON u.idUnidad = dl.IdUnidad
                    WHERE dl.tipo = ? AND dl.Numero_documento = ?
                    ORDER BY dl.seq ASC";
            $params = array($tipo, $tipo, $numero);
            $stmt = sqlsrv_query($cn->getConecta(), $sql, $params);
            if (!$stmt) {
                return json_encode(['status' => 'error', 'message' => 'Error al obtener líneas del documento']);
            }
            $lineas = [];
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $fechaVence = '';
                if (isset($row['Fecha_Vence']) && $row['Fecha_Vence'] instanceof DateTime) {
                    $fechaVence = date_format($row['Fecha_Vence'], 'd/m/Y');
                }
                $cantOrig      = (float)$row['cantidad_original'];
                $cantDevuelta  = (float)$row['cantidad_devuelta'];
                $cantDisponible = max(0, $cantOrig - $cantDevuelta);
                $lineas[] = array(
                    'seq'               => $row['seq'],
                    'IdProducto'        => $row['IdProducto'],
                    'Producto'          => $row['Producto'],
                    'Unidad'            => $row['Unidad'],
                    'cantidad_original' => $cantOrig,
                    'cantidad_devuelta' => $cantDevuelta,
                    'cantidad_disponible' => $cantDisponible,
                    'Valor_Unitario'    => (float)$row['Valor_Unitario'],
                    'Porcentaje_Descuento' => (float)$row['Porcentaje_Descuento_1'],
                    'Porcentaje_Impuesto'  => (float)$row['Porcentaje_Impuesto'],
                    'Numero_Lote'       => $row['Numero_Lote'],
                    'Fecha_Vence'       => $fechaVence
                );
            }
            return json_encode(['status' => 'success', 'lineas' => $lineas]);
        }

        public function insert_devolucion($tipo, $numero, $tiporef, $usuario, $idConcepto, $nombreConcepto, $lineas = null){
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

                // Validar que el documento esté exportado (guardado)
                $sql_exp = "SELECT exportado FROM Documentos WHERE Numero_documento = ? AND tipo = ?";
                $stmt_exp = sqlsrv_query($cn->getConecta(), $sql_exp, array($numero, $tiporef));
                $row_exp = sqlsrv_fetch_array($stmt_exp, SQLSRV_FETCH_ASSOC);
                if (!$row_exp || $row_exp['exportado'] !== 'S') {
                    return json_encode(array(
                        "status" => "error",
                        "message" => "El documento N° '$numero' no está en estado Exportado. Solo se pueden generar devoluciones sobre documentos ya guardados."
                    ));
                }

                sqlsrv_begin_transaction($cn->getConecta());

                // Reserva atómica del consecutivo, igual que en Documento::insert_doc.
                // Antes se leía (c.siguiente+1) dentro del INSERT y solo al final se
                // incrementaba Consecutivos: en esa ventana otro proceso podía tomar el
                // mismo número, y peor aún, los UPDATE de totales y de concepto apuntaban a
                // "(SELECT siguiente+1 FROM Consecutivos)" -- si el consecutivo se movía
                // entre medio, esos UPDATE caían sobre un documento ajeno.
                $stmtSeq = sqlsrv_query($cn->getConecta(),
                    "UPDATE Consecutivos SET siguiente = siguiente + 1
                     OUTPUT INSERTED.siguiente AS nuevo
                     WHERE tipo = ?",
                    array($tipo));
                if ($stmtSeq === false) {
                    throw new Exception("Error al reservar consecutivo: " . print_r(sqlsrv_errors(), true));
                }
                $rowSeq = sqlsrv_fetch_array($stmtSeq, SQLSRV_FETCH_ASSOC);
                if (!$rowSeq) {
                    throw new Exception("No existe un consecutivo configurado para el tipo de documento $tipo");
                }
                $numDoc = (int)$rowSeq['nuevo'];

                $sql="INSERT INTO Documentos(sw, tipo, modelo, Numero_Documento, Numero_Docto_Base,
                nit_Cedula, codigo_direccion, Fecha_Hora_Factura,Fecha_Hora_Vencimiento,Fecha_orden_Venta,
                condicion,valor_total, valor_aplicado, Retencion_1,Retencion_2, Retencion_3, retencion_causada, retencion_iva,retencion_ica,
                retencion_descuento, descuento_pie, DescuentoOrdenVenta, descuento_1, descuento_2, descuento_3, costo, IdVendedor, anulado, usuario,
                notas,pc, fecha_hora, duracion, bodega, Valor_impuesto, Impuesto_Consumo, impuesto_deporte, concepto, vencimiento_presup,
                exportado, prefijo, moneda, CentroDeCostosDoc, valor_mercancia, abono, Comision_Vendedor, Tasa_Moneda_Ext, Tomador, Tasa_Fija_o_Variable, Punto_FOB,
                Fletes_Moneda_Ext, Miselaneos_Moneda_Ext, Cargo_Por_Fletes, Impuesto_Por_Fletes, Total_Items, Nombre_Cliente, Ordenado_Por, Telefono_De_Envio_1,
                Telefono_De_Envio_2, Factura_Impresa, IdFormaEnvio, IdTransportador, nit_Cedula_2, codigo_direccion_2, Numero_Docto_Base_2, Tipo_Docto_Base,
                Tipo_Docto_Base_2, IdActividadEconomica, TarifaReteFuenteCree, Valor_ReteCree, IdVehiculo)

                (SELECT td.tipo AS sw, '$tipo' AS tipo, '$tipo' AS modelo, $numDoc AS Numero_Documento, '$numero' AS Numero_Docto_Base,
                d.nit_Cedula AS nit_Cedula, d.codigo_direccion AS codigo_direccion,  GETDATE() AS Fecha_Hora_Factura, GETDATE() AS Fecha_Hora_Vencimiento, GETDATE() AS Fecha_orden_Venta,
                d.condicion AS condicion, d.valor_total AS valor_total, 0 AS valor_aplicado, d.Retencion_1 AS Retencion_1, d.Retencion_2 AS Retencion_2, d.Retencion_3 AS Retencion_3, 0 AS retencion_causada, 0 AS retencion_iva,
                0 AS retencion_ica, 0 AS retencion_descuento, 0 AS descuento_pie, 0 AS DescuentoOrdenVenta, d.descuento_1 AS descuento_1, d.descuento_2 AS descuento_2, d.descuento_3 AS descuento_3,
                d.costo AS costo, d.IdVendedor AS IdVendedor, 'N' AS anulado, '$usuario' AS usuario,
                d.notas AS notas, HOST_NAME() AS pc, GETDATE() AS fecha_hora, 0 AS duracion, td.IdBodega AS bodega, 0 AS Valor_impuesto, 0 AS Impuesto_Consumo,
                0 AS impuesto_deporte, d.concepto AS concepto, GETDATE() AS vencimiento_presup,
                'S' AS exportado, ISNULL(td.Prefijo, '0') AS prefijo, d.moneda AS moneda, 0 AS CentroDeCostosDoc, 0 AS valor_mercancia, 0 AS abono, 0 AS Comision_Vendedor,
                1 AS Tasa_Moneda_Ext, '' AS Tomador, 'V' AS Tasa_Fija_o_Variable, d.Punto_FOB AS Punto_FOB,
                0 AS Fletes_Moneda_Ext, 0 AS Miselaneos_Moneda_Ext, 0 AS Cargo_Por_Fletes, 0 AS Impuesto_Por_Fletes, d.Total_Items AS Total_Items, d.Nombre_Cliente AS Nombre_Cliente,
                SUBSTRING(d.Ordenado_Por,0,20) AS Ordenado_Por, d.Telefono_De_Envio_1 AS Telefono_De_Envio_1, d.Telefono_De_Envio_2 AS Telefono_De_Envio_2, 'N' AS Factura_Impresa, d.IdFormaEnvio AS IdFormaEnvio, d.IdTRansportador AS IdTransportador,
                d.nit_Cedula_2 AS nit_Cedula_2, d.codigo_direccion_2 AS codigo_direccion_2, d.Numero_Docto_Base_2 AS Numero_Docto_Base_2, '$tiporef' AS Tipo_Docto_Base,
                d.Tipo_Docto_Base_2 AS Tipo_Docto_Base_2, d.IdActividadEconomica AS IdActividadEconomica, d.TarifaReteFuenteCree AS TarifaReteFuenteCree, d.Valor_ReteCree AS Valor_ReteCree, d.IdVehiculo AS IdVehiculo

                FROM Documentos d, TblTipoDoctos td
                WHERE d.Numero_documento = '$numero' AND d.tipo = '$tiporef' AND td.idTipoDoctos = '$tipo')";

                $registros = sqlsrv_prepare($cn->getConecta(), $sql);
                if(sqlsrv_execute($registros) === false) {
                    throw new Exception("Error al insertar documento: " . print_r(sqlsrv_errors(), true));
                }

                // Insertar líneas: total o parcial según $lineas
                $sqlLinBase = "INSERT INTO Documentos_Lin
                (sw, tipo, seq, modelo, Numero_Documento, Numero_Docto_Base, Numero_Lote, Nit_Cedula, Codigo_Direccion, Fecha_Documento,
                IdProducto, IdUnidad, Factor_Conversion, Cantidad_Facturada, Cantidad_Pendiente, Cantidad_Orden, Costo_Unitario, Valor_Unitario,
                Valor_Impuesto, Porcentaje_Impuesto, Porcentaje_Descuento_1, Porcentaje_Descuento_2,Porcentaje_Descuento_3, IdVendedor, Comision_Vendedor,
                Valor_Comision_Vendedor, IdBodega, Maneja_Inventario, Tomador, IdMoneda, Tasa_Moneda_Ext, CentroDeCostosDoc,
                Nota_Linea, Unidades, Fecha_Vence, Exportado, Costo_Unitario_Inicial,
                Porcentaje_ReteFuente, Envase, Numero_Lote_Destino, serial, Impuesto_Consumo, Porcentaje_ReteFuente_2,
                Porcentaje_ReteFuente_3, Porcentaje_ReteFuente_4, Emp_1, Emp_2, Emp_3, Emp_4, Emp_5, Emp_6,
                Emp_7, Emp_8, Tara_1, Tara_2, Tara_3, Tara_4, Tara_5, Tara_6, Tara_7, Tara_8)
                (SELECT td.tipo AS sw, '$tipo' AS tipo, dl.seq AS seq, p.contable AS Modelo, $numDoc AS Numero_Documento,
                '' AS Numero_Docto_Base, dl.Numero_Lote AS Numero_Lote, dl.Nit_Cedula AS Nit_Cedula, dl.codigo_direccion AS codigo_direccion, GETDATE() AS Fecha_Documento,
                dl.IdProducto AS IdProducto, dl.IdUnidad AS IdUnidad, '1' AS Factor_Conversion, {CANT_FACTURADA},
                {CANT_PENDIENTE}, dl.Cantidad_Orden AS Cantidad_Orden,
                dl.Costo_Unitario AS Costo_Unitario, dl.valor_unitario AS Valor_Unitario, {VALOR_IMPUESTO}, dl.Porcentaje_Impuesto AS Porcentaje_Impuesto,
                dl.Porcentaje_Descuento_1 AS Porcentaje_Descuento_1, dl.Porcentaje_Descuento_2 AS Porcentaje_Descuento_2,
                dl.Porcentaje_Descuento_3 AS Porcentaje_Descuento_3, dl.IdVendedor AS IdVendedor, 0 AS Comision_Vendedor, 0 AS Valor_Comision_Vendedor,
                td.IdBodega AS IdBodega, 'S' AS Maneja_Inventario, '' AS Tomador, 1 AS IdMoneda, 1 AS Tasa_Moneda_Ext, '0' AS CentroDeCostosDoc,
                ISNULL(dl.Nota_Linea, ' ') AS Nota_Linea, '1' AS Unidades, GETDATE() AS Fecha_Vence, 'N' AS Exportado, dl.Costo_Unitario_Inicial AS Costo_Unitario_Inicial,
                CASE WHEN LTRIM(RTRIM(t.TipoPersona)) = 'Juridica' THEN r.PorcentajeRetencionJuridica
                ELSE r.PorcentajeRetencionNatural END AS Porcentaje_ReteFuente, 0 AS Envase, 0 AS Numero_Lote_Destino, '' AS serial, 0 AS Impuesto_Consumo, 0 AS Porcentaje_ReteFuente_2,
                0 AS Porcentaje_ReteFuente_3, 0 AS Porcentaje_ReteFuente_4, 0 AS Emp_1, 0 AS Emp_2, 0 AS Emp_3, 0 AS Emp_4, 0 AS Emp_5, 0 AS Emp_6,
                0 AS Emp_7, 0 AS Emp_8, 0 AS Tara_1, 0 AS Tara_2, 0 AS Tara_3, 0 AS Tara_4, 0 AS Tara_5, 0 AS Tara_6, 0 AS Tara_7, 0 AS Tara_8
                FROM Documentos_Lin dl
                INNER JOIN Documentos d ON d.Numero_documento=dl.Numero_Documento AND d.tipo = dl.tipo
                INNER JOIN TblTipoDoctos td ON td.idTipoDoctos = '$tipo'
                LEFT JOIN TblProducto p ON p.IdProducto = dl.IdProducto
                LEFT JOIN TblTerceros t ON dl.Nit_Cedula=t.nit_cedula
                LEFT JOIN TblRetencion r ON p.Retencion=r.IdRetencion
                WHERE dl.Numero_documento = '$numero' AND dl.tipo = '$tiporef'{SEQ_FILTER})";

                if ($lineas !== null && is_array($lineas) && count($lineas) > 0) {
                    // Devolución parcial: insertar línea por línea con cantidad personalizada
                    foreach ($lineas as $linea) {
                        $seq        = (int)$linea['seq'];
                        $cantidadDev = (float)$linea['cantidad'];
                        if ($cantidadDev <= 0) continue;

                        $sqlLin = str_replace(
                            ['{CANT_FACTURADA}', '{CANT_PENDIENTE}', '{VALOR_IMPUESTO}', '{SEQ_FILTER}'],
                            [
                                "$cantidadDev AS Cantidad_Facturada",
                                "($cantidadDev) * -1 AS Cantidad_Pendiente",
                                "(dl.Porcentaje_Impuesto / 100.0 * dl.valor_unitario * $cantidadDev) AS Valor_Impuesto",
                                " AND dl.seq = $seq"
                            ],
                            $sqlLinBase
                        );
                        $stmtLin = sqlsrv_prepare($cn->getConecta(), $sqlLin);
                        if (sqlsrv_execute($stmtLin) === false) {
                            throw new Exception("Error al insertar línea seq=$seq: " . print_r(sqlsrv_errors(), true));
                        }
                    }
                } else {
                    // Devolución total: copiar todas las líneas del documento original
                    $sql1 = str_replace(
                        ['{CANT_FACTURADA}', '{CANT_PENDIENTE}', '{VALOR_IMPUESTO}', '{SEQ_FILTER}'],
                        [
                            'Cantidad_Facturada AS Cantidad_Facturada',
                            '(dl.Cantidad_Facturada) * -1 AS Cantidad_Pendiente',
                            '(dl.Porcentaje_Impuesto / 100.0 * dl.valor_unitario * dl.Cantidad_Facturada) AS Valor_Impuesto',
                            ''
                        ],
                        $sqlLinBase
                    );
                    $stmtLin = sqlsrv_prepare($cn->getConecta(), $sql1);
                    if (sqlsrv_execute($stmtLin) === false) {
                        throw new Exception("Error al insertar detalle del documento: " . print_r(sqlsrv_errors(), true));
                    }
                }

                // Recalcular totales en cabecera (obligatorio para parciales, consistencia en totales para totales)
                $sql_totales = "UPDATE Documentos SET
                    Total_Items    = (SELECT COUNT(*) FROM Documentos_Lin WHERE tipo = '$tipo' AND Numero_documento = $numDoc),
                    Valor_impuesto = (SELECT ISNULL(SUM(dl.Valor_Impuesto), 0) FROM Documentos_Lin dl WHERE dl.tipo = '$tipo' AND dl.Numero_documento = $numDoc),
                    valor_total    = (SELECT ISNULL(SUM(ROUND((dl.Cantidad_Facturada * dl.Valor_Unitario) * (1 - dl.Porcentaje_Descuento_1/100), 2) + ((dl.Cantidad_Facturada * dl.Valor_Unitario) * (1 - dl.Porcentaje_Descuento_1/100)) * (dl.Porcentaje_Impuesto/100)), 0) FROM Documentos_Lin dl WHERE dl.tipo = '$tipo' AND dl.Numero_documento = $numDoc),
                    costo          = (SELECT ISNULL(SUM(dl.Cantidad_Facturada * dl.Costo_Unitario), 0) FROM Documentos_Lin dl WHERE dl.tipo = '$tipo' AND dl.Numero_documento = $numDoc)
                    WHERE tipo = '$tipo' AND Numero_Documento = $numDoc";
                $stmtTot = sqlsrv_prepare($cn->getConecta(), $sql_totales);
                if (sqlsrv_execute($stmtTot) === false) {
                    throw new Exception("Error al recalcular totales en cabecera: " . print_r(sqlsrv_errors(), true));
                }

                // Guardar el concepto de devolución en RespuestaCorrectaDian (campo dedicado)
                $sqlConcepto = "UPDATE Documentos SET RespuestaCorrectaDian = ?
                    WHERE tipo = '$tipo' AND Numero_Documento = $numDoc";
                $stmtConcepto = sqlsrv_prepare($cn->getConecta(), $sqlConcepto, array($nombreConcepto));
                if(sqlsrv_execute($stmtConcepto) === false) {
                    throw new Exception("Error al guardar concepto de devolución: " . print_r(sqlsrv_errors(), true));
                }

                // Intentar actualizar idConceptoDevolucion (requiere ejecutar sql/02_sqlserver_alter_documentos.sql)
                $sqlCheckCol = "SELECT COUNT(*) AS existe FROM sys.columns
                    WHERE object_id = OBJECT_ID('Documentos') AND name = 'idConceptoDevolucion'";
                $stmtCheck = sqlsrv_query($cn->getConecta(), $sqlCheckCol);
                if ($stmtCheck !== false) {
                    $rowCheck = sqlsrv_fetch_array($stmtCheck, SQLSRV_FETCH_ASSOC);
                    if ($rowCheck && $rowCheck['existe'] > 0) {
                        $sqlIdConc = "UPDATE Documentos SET idConceptoDevolucion = ?
                            WHERE tipo = '$tipo' AND Numero_Documento = $numDoc";
                        $paramsIdConc = array((int)$idConcepto);
                        $stmtIdConc = sqlsrv_prepare($cn->getConecta(), $sqlIdConc, $paramsIdConc);
                        if(sqlsrv_execute($stmtIdConc) === false) {
                            throw new Exception("Error al actualizar idConceptoDevolucion: " . print_r(sqlsrv_errors(), true));
                        }
                    }
                }

                sqlsrv_commit($cn->getConecta());

                // Se devuelve $numDoc (el número realmente reservado) en vez de releer
                // Consecutivos: esa relectura podía devolver el número de otro documento
                // si alguien más guardó en el intervalo.
                $snap = AuditoriaDocumentos::snapshotLineas($cn->getConecta(), $tipo, $numDoc);
                AuditoriaDocumentos::registrar(array(
                    'modulo' => 'Salidas', 'operacion' => 'insert_devolucion',
                    'tipo' => $tipo, 'numero' => $numDoc,
                    'lineas_antes' => 0, 'lineas_despues' => $snap['lineas'],
                    'resultado' => 'ok',
                    'mensaje' => "Devolución sobre documento $numero (tipo $tiporef). Concepto: $nombreConcepto",
                    'detalle_antes' => $snap['detalle']
                ));

                return json_encode(array(
                    "status" => "success",
                    "message" => "Documento registrado correctamente",
                    "tipo" => $tipo,
                    "consecutivo" => $numDoc
                ));

            } catch (Exception $e) {
                if (isset($cn) && $cn->getConecta()) {
                    sqlsrv_rollback($cn->getConecta());
                }
                $this->registrar_error("Error en insert_devolucion: " . $e->getMessage());
                AuditoriaDocumentos::registrar(array(
                    'modulo' => 'Salidas', 'operacion' => 'insert_devolucion',
                    'tipo' => $tipo, 'numero' => isset($numDoc) ? $numDoc : null,
                    'resultado' => 'error',
                    'mensaje' => "Devolución sobre documento $numero (tipo $tiporef): " . $e->getMessage()
                ));
                return json_encode(array(
                    "status" => "error",
                    "message" => $e->getMessage()
                ));
            }
        }

        public function insert_devolucion_manual($tipo, $nit1, $dir1, $nit2, $dir2, $usuario, $idConcepto, $nombreConcepto) {
            $cn = new Conectarserver;
            try {
                sqlsrv_begin_transaction($cn->getConecta());

                // El documento se crea directamente con su consecutivo real.
                $draft = $this->reservar_consecutivo_real($cn->getConecta(), $tipo);

                $sql = "INSERT INTO Documentos(sw, tipo, modelo, Numero_Documento, Numero_Docto_Base,
                nit_Cedula, codigo_direccion, Fecha_Hora_Factura, Fecha_Hora_Vencimiento, Fecha_orden_Venta,
                condicion, valor_total, valor_aplicado, Retencion_1, Retencion_2, Retencion_3, retencion_causada, retencion_iva, retencion_ica,
                retencion_descuento, descuento_pie, DescuentoOrdenVenta, descuento_1, descuento_2, descuento_3, costo, IdVendedor, anulado, usuario,
                notas, pc, fecha_hora, duracion, bodega, Valor_impuesto, Impuesto_Consumo, impuesto_deporte, concepto, vencimiento_presup,
                exportado, prefijo, moneda, CentroDeCostosDoc, valor_mercancia, abono, Comision_Vendedor, Tasa_Moneda_Ext, Tomador, Tasa_Fija_o_Variable, Punto_FOB,
                Fletes_Moneda_Ext, Miselaneos_Moneda_Ext, Cargo_Por_Fletes, Impuesto_Por_Fletes, Total_Items, Nombre_Cliente, Ordenado_Por, Telefono_De_Envio_1,
                Telefono_De_Envio_2, Factura_Impresa, IdFormaEnvio, IdTransportador, nit_Cedula_2, codigo_direccion_2, Numero_Docto_Base_2, Tipo_Docto_Base,
                Tipo_Docto_Base_2, IdActividadEconomica, TarifaReteFuenteCree, Valor_ReteCree, IdVehiculo)

                (SELECT td.tipo AS sw, '$tipo' AS tipo, '$tipo' AS modelo, $draft AS Numero_Documento, '' AS Numero_Docto_Base,
                '$nit1' AS nit_Cedula, '$dir1' AS codigo_direccion, GETDATE() AS Fecha_Hora_Factura, GETDATE() AS Fecha_Hora_Vencimiento, GETDATE() AS Fecha_orden_Venta,
                t.condicion AS condicion, 0 AS valor_total, 0 AS valor_aplicado, 0 AS Retencion_1, 0 AS Retencion_2, 0 AS Retencion_3,
                0 AS retencion_causada, 0 AS retencion_iva, 0 AS retencion_ica, 0 AS retencion_descuento, 0 AS descuento_pie, 0 AS DescuentoOrdenVenta,
                0 AS descuento_1, 0 AS descuento_2, 0 AS descuento_3, 0 AS costo, 0 AS IdVendedor, 'N' AS anulado, '$usuario' AS usuario,
                '' AS notas, HOST_NAME() AS pc, GETDATE() AS fecha_hora, 0 AS duracion, td.IdBodega AS bodega,
                0 AS Valor_impuesto, 0 AS Impuesto_Consumo, 0 AS impuesto_deporte, '' AS concepto, GETDATE() AS vencimiento_presup,
                'N' AS exportado, ISNULL(td.Prefijo, '0') AS prefijo, 1 AS moneda, 0 AS CentroDeCostosDoc, 0 AS valor_mercancia, 0 AS abono, 0 AS Comision_Vendedor,
                1 AS Tasa_Moneda_Ext, '' AS Tomador, 'V' AS Tasa_Fija_o_Variable, 0 AS Punto_FOB,
                0 AS Fletes_Moneda_Ext, 0 AS Miselaneos_Moneda_Ext, 0 AS Cargo_Por_Fletes, 0 AS Impuesto_Por_Fletes, 0 AS Total_Items,
                t.nombre AS Nombre_Cliente, '' AS Ordenado_Por, '' AS Telefono_De_Envio_1, '' AS Telefono_De_Envio_2, 'N' AS Factura_Impresa,
                0 AS IdFormaEnvio, 0 AS IdTransportador,
                '$nit2' AS nit_Cedula_2, '$dir2' AS codigo_direccion_2, '' AS Numero_Docto_Base_2, '0' AS Tipo_Docto_Base,
                '2' AS Tipo_Docto_Base_2, '0' AS IdActividadEconomica, 0 AS TarifaReteFuenteCree, 0 AS Valor_ReteCree, '1' AS IdVehiculo

                FROM TblTerceros t, TblTipoDoctos td
                WHERE td.idTipoDoctos = '$tipo' AND t.nit_cedula = '$nit1')";

                $registros = sqlsrv_prepare($cn->getConecta(), $sql);
                if (sqlsrv_execute($registros) === false) {
                    throw new Exception("Error al insertar documento: " . print_r(sqlsrv_errors(), true));
                }

                // Guardar concepto en RespuestaCorrectaDian
                $sqlConcepto = "UPDATE Documentos SET RespuestaCorrectaDian = ?
                    WHERE tipo = '$tipo'
                      AND Numero_Documento = $draft";
                $stmtConcepto = sqlsrv_prepare($cn->getConecta(), $sqlConcepto, array($nombreConcepto));
                if (sqlsrv_execute($stmtConcepto) === false) {
                    throw new Exception("Error al guardar concepto: " . print_r(sqlsrv_errors(), true));
                }

                // Actualizar idConceptoDevolucion si la columna existe
                $sqlCheckCol = "SELECT COUNT(*) AS existe FROM sys.columns
                    WHERE object_id = OBJECT_ID('Documentos') AND name = 'idConceptoDevolucion'";
                $stmtCheck = sqlsrv_query($cn->getConecta(), $sqlCheckCol);
                if ($stmtCheck !== false) {
                    $rowCheck = sqlsrv_fetch_array($stmtCheck, SQLSRV_FETCH_ASSOC);
                    if ($rowCheck && $rowCheck['existe'] > 0) {
                        $sqlIdConc = "UPDATE Documentos SET idConceptoDevolucion = ?
                            WHERE tipo = '$tipo'
                              AND Numero_Documento = $draft";
                        $stmtIdConc = sqlsrv_prepare($cn->getConecta(), $sqlIdConc, array((int)$idConcepto));
                        if (sqlsrv_execute($stmtIdConc) === false) {
                            throw new Exception("Error al guardar idConceptoDevolucion: " . print_r(sqlsrv_errors(), true));
                        }
                    }
                }

                sqlsrv_commit($cn->getConecta());

                return json_encode(array(
                    "status"      => "success",
                    "message"     => "Devolución manual creada. Agregue los productos en el detalle y guarde el documento.",
                    "tipo"        => $tipo,
                    "consecutivo" => $draft
                ));

            } catch (Exception $e) {
                if (isset($cn) && $cn->getConecta()) {
                    sqlsrv_rollback($cn->getConecta());
                }
                $this->registrar_error("Error en insert_devolucion_manual: " . $e->getMessage());
                return json_encode(array(
                    "status"  => "error",
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
                                     SELECT dl.IdProducto, dl.seq,
                                            SUM(CASE WHEN d.Tipo_Docto_Base = '0' THEN dl.Cantidad_Facturada ELSE -dl.Cantidad_Facturada END) AS total_facturado
                                     FROM Documentos d
                                     JOIN Documentos_Lin dl ON dl.tipo = d.tipo AND dl.Numero_Documento = d.Numero_documento
                                     WHERE d.Numero_Docto_Base_2 = '$numero' AND d.Tipo_Docto_Base_2 = '10'
                                     AND d.bodega = (SELECT bodega FROM Documentos_Ped WHERE numero_pedido = '$numero' AND sw = '10')
                                     GROUP BY dl.IdProducto, dl.seq
                                 ) f ON f.IdProducto = dlp.IdProducto AND f.seq = dlp.Linea
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

                // El documento se crea directamente con su consecutivo real.
                $numDoc = $this->reservar_consecutivo_real($cn->getConecta(), $tipo);

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
                t.condicion AS condicion, dp.valor_total AS valor_total, 0 AS valor_aplicado, dp.Retencion_1 AS Retencion_1, 0 AS Retencion_2, 0 AS Retencion_3,
                0 AS retencion_causada, 0 AS retencion_iva, 0 AS retencion_ica, 0 AS retencion_descuento, 0 AS descuento_pie, dp.NroOCTERCERO AS DescuentoOrdenVenta, 0 AS descuento_1, 0 AS descuento_2,
                0 AS descuento_3, 0 AS costo, dp.vendedor AS idVendedor, 'N' AS anulado, '$usuario' AS usuario, dp.notas AS notas, HOST_NAME() AS pc, GETDATE() AS fecha_hora,
                0 AS duracion, td.IdBodega AS bodega, 0 AS Valor_impuesto, 0 AS Impuesto_Consumo, 0 AS impuesto_deporte, dp.concepto AS concepto, GETDATE() AS vencimiento_presup,
                'N' AS exportado, ISNULL(td.Prefijo, '0') AS prefijo, dp.moneda AS moneda, 0 AS CentroDeCostosDoc, 0 AS valor_mercancia, 0 AS abono, 0 AS Comision_Vendedor,
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
                '' AS Numero_Docto_Base, ISNULL(dp.Numero_Lote, '0') AS Numero_Lote, dp.IdCliente AS Nit_Cedula, dp.DireccionFactura AS codigo_direccion, GETDATE() AS Fecha_Documento,
                dp.IdProducto AS IdProducto, dp.und AS IdUnidad, '1' AS Factor_Conversion,
                (dp.cantidad - ISNULL(f.total_facturado, 0)) AS Cantidad_Facturada,
                0 AS Cantidad_Pendiente, dp.cantidad AS Cantidad_Orden, p.costo_unitario AS Costo_Unitario, dp.valor_unitario AS Valor_Unitario,
                ((ISNULL(dp.porcentaje_iva, 0)/100) * dp.valor_unitario) AS Valor_Impuesto, ISNULL(dp.porcentaje_iva, 0) AS Porcentaje_Impuesto, ISNULL(dp.porcentaje_descuento, 0) AS Porcentaje_Descuento_1,
                ISNULL(dp.porc_dcto_2, 0) AS Porcentaje_Descuento_2, ISNULL(dp.porc_dcto_3, 0) AS Porcentaje_Descuento_3, dp.IdVendedor AS IdVendedor, 0 AS Comision_Vendedor, 0 AS Valor_Comision_Vendedor,
                td.IdBodega AS IdBodega, 'S' AS Maneja_Inventario, '' AS Tomador, 1 AS IdMoneda, 1 AS Tasa_Moneda_Ext, '0' AS CentroDeCostosDoc,
                ISNULL(dp.nota, ' ') AS Nota_Linea, '1' AS Unidades, GETDATE() AS Fecha_Vence, 'N' AS Exportado, p.costo_unitario AS Costo_Unitario_Inicial,
                dp.Porcentaje_ReteFuente AS Porcentaje_ReteFuente, 0 AS Envase, 0 AS Numero_Lote_Destino, '' AS serial, 0 AS Impuesto_Consumo, 0 AS Porcentaje_ReteFuente_2,
                0 AS Porcentaje_ReteFuente_3, 0 AS Porcentaje_ReteFuente_4, 0 AS Emp_1, 0 AS Emp_2, 0 AS Emp_3, 0 AS Emp_4, 0 AS Emp_5, 0 AS Emp_6,
                0 AS Emp_7, 0 AS Emp_8, 0 AS Tara_1, 0 AS Tara_2, 0 AS Tara_3, 0 AS Tara_4, 0 AS Tara_5, 0 AS Tara_6, 0 AS Tara_7, 0 AS Tara_8

                FROM Documentos_Lin_Ped dp
                JOIN TblTipoDoctos td ON td.idTipoDoctos = '$tipo'
                JOIN TblProducto p ON p.IdProducto = dp.IdProducto
                LEFT JOIN (
                    SELECT dl.IdProducto, dl.seq,
                           SUM(CASE WHEN d.Tipo_Docto_Base = '0' THEN dl.Cantidad_Facturada ELSE -dl.Cantidad_Facturada END) AS total_facturado
                    FROM Documentos d
                    JOIN Documentos_Lin dl ON dl.tipo = d.tipo AND dl.Numero_Documento = d.Numero_documento
                    WHERE d.Numero_Docto_Base_2 = '$numero' AND d.Tipo_Docto_Base_2 = '10'
                    AND d.bodega = (SELECT bodega FROM Documentos_Ped WHERE numero_pedido = '$numero' AND sw = '10')
                    GROUP BY dl.IdProducto, dl.seq
                ) f ON f.IdProducto = dp.IdProducto AND f.seq = dp.Linea
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
                    costo = (SELECT ISNULL(SUM(dl.Cantidad_Facturada * dl.Costo_Unitario), 0) FROM Documentos_Lin dl WHERE dl.tipo = '$tipo' AND dl.Numero_documento = $numDoc)
                    WHERE tipo = '$tipo' AND Numero_Documento = $numDoc";
                
                $registros_tot = sqlsrv_prepare($cn->getConecta(), $sql_totales);
                if(sqlsrv_execute($registros_tot) === false) {
                    throw new Exception("Error al actualizar totales en cabecera: " . print_r(sqlsrv_errors(), true));
                }


                // Calcular si quedan pendientes para marcar exportado en Documentos_ped (P=parcial, S=completo)
                $sql_chk_pend = "SELECT COUNT(*) AS con_pendiente
                                 FROM Documentos_Lin_Ped dlp
                                 LEFT JOIN (
                                     SELECT dl.IdProducto, dl.seq,
                                            SUM(CASE WHEN d.Tipo_Docto_Base = '0' THEN dl.Cantidad_Facturada ELSE -dl.Cantidad_Facturada END) AS total_facturado
                                     FROM Documentos d
                                     JOIN Documentos_Lin dl ON dl.tipo = d.tipo AND dl.Numero_Documento = d.Numero_documento
                                     WHERE d.Numero_Docto_Base_2 = '$numero' AND d.Tipo_Docto_Base_2 = '10'
                                     AND d.exportado = 'S'
                                     AND d.bodega = (SELECT bodega FROM Documentos_Ped WHERE numero_pedido = '$numero' AND sw = '10')
                                     GROUP BY dl.IdProducto, dl.seq
                                 ) f ON f.IdProducto = dlp.IdProducto AND f.seq = dlp.Linea
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
                    "consecutivo" => $numDoc
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

        public function reiniciar_doc_desde_os($tipo, $numdoc, $confirmado = false) {
            $cn = new Conectarserver;
            try {
                sqlsrv_begin_transaction($cn->getConecta());

                $sql_check = "SELECT Numero_Docto_Base_2, exportado FROM Documentos WHERE tipo = ? AND Numero_Documento = ?";
                $stmt_check = sqlsrv_query($cn->getConecta(), $sql_check, array($tipo, (int)$numdoc));
                if ($stmt_check === false) throw new Exception("Error al verificar el documento.");
                $row_check = sqlsrv_fetch_array($stmt_check, SQLSRV_FETCH_ASSOC);
                if (!$row_check) throw new Exception("Documento no encontrado.");
                if ($row_check['exportado'] === 'S') throw new Exception("El documento ya fue exportado y no puede reiniciarse.");

                // Un documento que estuvo exportado y volvió a editable con "Desmarcar" ya
                // pudo haberse impreso: reiniciarlo regenera el detalle con las cantidades
                // pendientes de HOY, que no son las que se imprimieron. Se exige una
                // confirmación explícita adicional antes de destruirlo.
                $desmarcado = AuditoriaDocumentos::fueDesmarcado($tipo, (int)$numdoc);
                if ($desmarcado && !$confirmado) {
                    sqlsrv_rollback($cn->getConecta());
                    AuditoriaDocumentos::registrar(array(
                        'modulo' => 'Salidas', 'operacion' => 'reiniciar_doc_desde_os', 'destructiva' => 1,
                        'tipo' => $tipo, 'numero' => $numdoc,
                        'exportado_antes' => $row_check['exportado'],
                        'resultado' => 'bloqueado',
                        'mensaje' => 'Requiere confirmación: documento desmarcado el ' . $desmarcado['fecha']
                                   . ' por ' . $desmarcado['usuario']
                    ));
                    return json_encode(array(
                        "status"  => "confirmar",
                        "message" => "ATENCIÓN: este documento ya había sido guardado y fue desmarcado el "
                                   . $desmarcado['fecha'] . " por " . $desmarcado['usuario']
                                   . ". Si lo reinicia, el detalle actual se BORRA y se regenera con las "
                                   . "cantidades pendientes de hoy, que pueden no coincidir con lo ya impreso."
                    ));
                }

                // Foto del detalle ANTES de destruirlo, para poder reponerlo si el
                // resultado regenerado no coincide con lo que se imprimió.
                $snap = AuditoriaDocumentos::snapshotLineas($cn->getConecta(), $tipo, (int)$numdoc);

                $numero_os = $row_check['Numero_Docto_Base_2'];

                $sql_del = "DELETE FROM Documentos_Lin WHERE tipo = ? AND Numero_Documento = ?";
                $stmt_del = sqlsrv_query($cn->getConecta(), $sql_del, array($tipo, (int)$numdoc));
                if ($stmt_del === false) throw new Exception("Error al eliminar líneas: " . print_r(sqlsrv_errors(), true));

                $sql_lin = "INSERT INTO Documentos_Lin
                (sw, tipo, seq, modelo, Numero_Documento, Numero_Docto_Base, Numero_Lote, Nit_Cedula, Codigo_Direccion, Fecha_Documento,
                IdProducto, IdUnidad, Factor_Conversion, Cantidad_Facturada, Cantidad_Pendiente, Cantidad_Orden, Costo_Unitario, Valor_Unitario,
                Valor_Impuesto, Porcentaje_Impuesto, Porcentaje_Descuento_1, Porcentaje_Descuento_2, Porcentaje_Descuento_3, IdVendedor, Comision_Vendedor,
                Valor_Comision_Vendedor, IdBodega, Maneja_Inventario, Tomador, IdMoneda, Tasa_Moneda_Ext, CentroDeCostosDoc,
                Nota_Linea, Unidades, Fecha_Vence, Exportado, Costo_Unitario_Inicial,
                Porcentaje_ReteFuente, Envase, Numero_Lote_Destino, serial, Impuesto_Consumo, Porcentaje_ReteFuente_2,
                Porcentaje_ReteFuente_3, Porcentaje_ReteFuente_4, Emp_1, Emp_2, Emp_3, Emp_4, Emp_5, Emp_6,
                Emp_7, Emp_8, Tara_1, Tara_2, Tara_3, Tara_4, Tara_5, Tara_6, Tara_7, Tara_8)
                (SELECT td.tipo AS sw, '$tipo' AS tipo, dp.Linea AS seq, p.contable AS Modelo, $numdoc AS Numero_Documento,
                '' AS Numero_Docto_Base, ISNULL(dp.Numero_Lote, '0') AS Numero_Lote, dp.IdCliente AS Nit_Cedula, dp.DireccionFactura AS codigo_direccion, GETDATE() AS Fecha_Documento,
                dp.IdProducto AS IdProducto, dp.und AS IdUnidad, '1' AS Factor_Conversion,
                (dp.cantidad - ISNULL(f.total_facturado, 0)) AS Cantidad_Facturada,
                0 AS Cantidad_Pendiente, dp.cantidad AS Cantidad_Orden, p.costo_unitario AS Costo_Unitario, dp.valor_unitario AS Valor_Unitario,
                ((ISNULL(dp.porcentaje_iva, 0)/100) * dp.valor_unitario) AS Valor_Impuesto, ISNULL(dp.porcentaje_iva, 0) AS Porcentaje_Impuesto,
                ISNULL(dp.porcentaje_descuento, 0) AS Porcentaje_Descuento_1, ISNULL(dp.porc_dcto_2, 0) AS Porcentaje_Descuento_2,
                ISNULL(dp.porc_dcto_3, 0) AS Porcentaje_Descuento_3, dp.IdVendedor AS IdVendedor, 0 AS Comision_Vendedor, 0 AS Valor_Comision_Vendedor,
                td.IdBodega AS IdBodega, 'S' AS Maneja_Inventario, '' AS Tomador, 1 AS IdMoneda, 1 AS Tasa_Moneda_Ext, '0' AS CentroDeCostosDoc,
                ' ' AS Nota_Linea, '1' AS Unidades, GETDATE() AS Fecha_Vence, 'N' AS Exportado, p.costo_unitario AS Costo_Unitario_Inicial,
                dp.Porcentaje_ReteFuente AS Porcentaje_ReteFuente, 0 AS Envase, 0 AS Numero_Lote_Destino, '' AS serial, 0 AS Impuesto_Consumo,
                0 AS Porcentaje_ReteFuente_2, 0 AS Porcentaje_ReteFuente_3, 0 AS Porcentaje_ReteFuente_4,
                0 AS Emp_1, 0 AS Emp_2, 0 AS Emp_3, 0 AS Emp_4, 0 AS Emp_5, 0 AS Emp_6,
                0 AS Emp_7, 0 AS Emp_8, 0 AS Tara_1, 0 AS Tara_2, 0 AS Tara_3, 0 AS Tara_4, 0 AS Tara_5, 0 AS Tara_6, 0 AS Tara_7, 0 AS Tara_8
                FROM Documentos_Lin_Ped dp
                JOIN TblTipoDoctos td ON td.idTipoDoctos = '$tipo'
                JOIN TblProducto p ON p.IdProducto = dp.IdProducto
                LEFT JOIN (
                    SELECT dl.IdProducto, dl.seq, SUM(CASE WHEN d.Tipo_Docto_Base = '0' THEN dl.Cantidad_Facturada ELSE -dl.Cantidad_Facturada END) AS total_facturado
                    FROM Documentos d
                    JOIN Documentos_Lin dl ON dl.tipo = d.tipo AND dl.Numero_Documento = d.Numero_documento
                    WHERE d.Numero_Docto_Base_2 = '$numero_os' AND d.Tipo_Docto_Base_2 = '10'
                    AND d.bodega = (SELECT bodega FROM Documentos_Ped WHERE numero_pedido = '$numero_os' AND sw = '10')
                    GROUP BY dl.IdProducto, dl.seq
                ) f ON f.IdProducto = dp.IdProducto AND f.seq = dp.Linea
                WHERE dp.numero_pedido = '$numero_os' AND dp.sw = '10'
                AND (dp.cantidad - ISNULL(f.total_facturado, 0)) > 0)";

                $stmt_lin = sqlsrv_prepare($cn->getConecta(), $sql_lin);
                if (sqlsrv_execute($stmt_lin) === false) throw new Exception("Error al reinsertar líneas: " . print_r(sqlsrv_errors(), true));

                $sql_tot = "UPDATE Documentos SET
                    Total_Items = (SELECT COUNT(*) FROM Documentos_Lin WHERE tipo = '$tipo' AND Numero_documento = $numdoc),
                    Valor_impuesto = (SELECT ISNULL(SUM(((dl.Cantidad_Facturada * dl.Valor_Unitario) * (1 - ISNULL(dl.Porcentaje_Descuento_1,0)/100)) * (ISNULL(dl.Porcentaje_Impuesto,0)/100)),0) FROM Documentos_Lin dl WHERE dl.tipo='$tipo' AND dl.Numero_documento=$numdoc),
                    valor_total = (SELECT ISNULL(SUM(ROUND((dl.Cantidad_Facturada*dl.Valor_Unitario)*(1-ISNULL(dl.Porcentaje_Descuento_1,0)/100),2)+((dl.Cantidad_Facturada*dl.Valor_Unitario)*(1-ISNULL(dl.Porcentaje_Descuento_1,0)/100))*(ISNULL(dl.Porcentaje_Impuesto,0)/100)),0) FROM Documentos_Lin dl WHERE dl.tipo='$tipo' AND dl.Numero_documento=$numdoc),
                    costo = (SELECT ISNULL(SUM(dl.Cantidad_Facturada * dl.Costo_Unitario), 0) FROM Documentos_Lin dl WHERE dl.tipo='$tipo' AND dl.Numero_documento=$numdoc)
                    WHERE tipo = '$tipo' AND Numero_Documento = $numdoc";

                $stmt_tot = sqlsrv_prepare($cn->getConecta(), $sql_tot);
                if (sqlsrv_execute($stmt_tot) === false) throw new Exception("Error al actualizar totales: " . print_r(sqlsrv_errors(), true));

                sqlsrv_commit($cn->getConecta());

                AuditoriaDocumentos::registrar(array(
                    'modulo' => 'Salidas', 'operacion' => 'reiniciar_doc_desde_os', 'destructiva' => 1,
                    'tipo' => $tipo, 'numero' => $numdoc,
                    'exportado_antes' => $row_check['exportado'],
                    'lineas_antes' => $snap['lineas'],
                    'lineas_despues' => AuditoriaDocumentos::contarLineas($cn->getConecta(), $tipo, (int)$numdoc),
                    'resultado' => 'ok',
                    'mensaje' => 'Detalle borrado y regenerado desde la OS ' . $numero_os
                               . ($desmarcado ? ' (documento previamente DESMARCADO)' : ''),
                    'detalle_antes' => $snap['detalle']
                ));

                return json_encode(array("status" => "success", "message" => "Documento reiniciado correctamente desde la OS."));

            } catch (Exception $e) {
                if (isset($cn) && $cn->getConecta()) sqlsrv_rollback($cn->getConecta());
                AuditoriaDocumentos::registrar(array(
                    'modulo' => 'Salidas', 'operacion' => 'reiniciar_doc_desde_os', 'destructiva' => 1,
                    'tipo' => $tipo, 'numero' => $numdoc,
                    'lineas_antes' => isset($snap) ? $snap['lineas'] : null,
                    'resultado' => 'error', 'mensaje' => $e->getMessage(),
                    'detalle_antes' => isset($snap) ? $snap['detalle'] : null
                ));
                return json_encode(array("status" => "error", "message" => $e->getMessage()));
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

            // UPDLOCK/HOLDLOCK: sin el lock, dos altas simultáneas sobre el mismo documento
            // (dos pestañas, o el Excel masivo corriendo mientras alguien agrega a mano)
            // obtienen el mismo seq, y a partir de ahí un solo clic en eliminar borra ambas.
            $sql_seq = "SELECT ISNULL(MAX(seq), 0) + 1 AS next_seq FROM Documentos_Lin WITH (UPDLOCK, HOLDLOCK) WHERE tipo = '$tipo' AND Numero_documento = '$numdoc'";
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
            $cantidad, 0, $cantidad, p.costo_unitario, $valorUnitario,
            $valorImpuesto, $porcentajeImpuesto, 0, 0, 0,
            0, 0, 0, td.IdBodega, 'S', '',
            1, 1, '0', '$notaEscapada', 1, CONVERT(DATE, '$fechaVence', 23), 'N',
            p.costo_unitario, 0, 0, 0, '', 0,
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
                costo = (SELECT ISNULL(SUM(dl.Cantidad_Facturada * dl.Costo_Unitario), 0) FROM Documentos_Lin dl WHERE dl.tipo = '$tipo' AND dl.Numero_documento = $numdoc)
                WHERE tipo = '$tipo' AND Numero_Documento = $numdoc";
            
            $registros_tot = sqlsrv_prepare($cn->getConecta(), $sql_totales);
            sqlsrv_execute($registros_tot);

            AuditoriaDocumentos::registrar(array(
                'modulo' => 'Salidas', 'operacion' => 'agregar_linea_manual',
                'tipo' => $tipo, 'numero' => $numdoc,
                'lineas_antes' => $seq - 1,
                'lineas_despues' => AuditoriaDocumentos::contarLineas($cn->getConecta(), $tipo, $numdoc),
                'resultado' => 'ok',
                'mensaje' => "seq=$seq producto=$idProducto cantidad=$cantidad lote=$lote"
            ));

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

        // Resuelve el idLista de precios a usar (por cliente/dirección, o por el documento ya creado).
        private function resolver_id_lista($cn, $tipo, $numdoc, $nit, $direccion) {
            $ID_LISTA_DEFAULT = 50;
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
            return ($idListaReal !== null && (int)$idListaReal > 0) ? (int)$idListaReal : $ID_LISTA_DEFAULT;
        }

        // Valida (y resuelve precio/impuesto) de una fila del Excel de Salidas, sin escribir nada.
        // Usada tanto por validar_excel_salidas (vista previa) como por confirmar_masiva_excel_salidas
        // (revalida por si el catálogo cambió entre la vista previa y la confirmación).
        private function validar_fila_salida($cn, $cnDev, $idProducto, $cantidad, $lote, $idLista, &$procesados) {
            $ID_LISTA_DEFAULT = 50;

            if ($idProducto === '') {
                return ['ok' => false, 'mensaje' => 'IdProducto vacío'];
            }
            if (!is_numeric($cantidad) || (float)$cantidad <= 0) {
                return ['ok' => false, 'mensaje' => 'Cantidad debe ser un número mayor a 0'];
            }

            // Ya no se bloquea por producto+lote repetido dentro del archivo: se procesa igual
            // y solo se marca como advertencia en el resultado.
            $clave = $idProducto . '|' . $lote;
            $esDuplicado = in_array($clave, $procesados);

            $sqlProd = "SELECT p.Producto, ISNULL(i.PorcentajeImpuesto, 0) AS PorcentajeImpuesto
                        FROM TblProducto p
                        LEFT JOIN TblImpuesto i ON p.Impuesto_venta = i.IdImpuesto
                        WHERE p.IdProducto = ?";
            $stProd  = sqlsrv_query($cn->getConecta(), $sqlProd, [(int)$idProducto]);
            if ($stProd === false) {
                return ['ok' => false, 'mensaje' => 'Error al consultar producto'];
            }
            $rProd = sqlsrv_fetch_array($stProd, SQLSRV_FETCH_ASSOC);
            if (!$rProd) {
                return ['ok' => false, 'mensaje' => 'Producto no existe en el sistema'];
            }
            $porcentajeImpuesto = (float)$rProd['PorcentajeImpuesto'];
            $nombreProducto     = $rProd['Producto'];

            if ($lote !== '' && $cnDev !== null) {
                $sqlLote = "SELECT COUNT(*) AS cnt FROM cvapptblfarmbatch WHERE numberBatch = ? AND statusBatch = 'S'";
                $stLote  = sqlsrv_query($cnDev, $sqlLote, [$lote]);
                if ($stLote !== false) {
                    $rLote = sqlsrv_fetch_array($stLote, SQLSRV_FETCH_ASSOC);
                    if (!$rLote || (int)$rLote['cnt'] === 0) {
                        return ['ok' => false, 'mensaje' => 'Lote "' . $lote . '" no válido o inactivo'];
                    }
                }
            }

            $precio  = 0;
            $sqlPrec = "SELECT TOP 1 precio FROM Producto_Pre WHERE IdProducto = ? AND IdPrecio = ? ORDER BY Fecha DESC";
            $stPrec  = sqlsrv_query($cn->getConecta(), $sqlPrec, [(int)$idProducto, $idLista]);
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

            $procesados[] = $clave;
            return [
                'ok'                 => true,
                'esDuplicado'        => $esDuplicado,
                'nombreProducto'     => $nombreProducto,
                'porcentajeImpuesto' => $porcentajeImpuesto,
                'precio'             => $precio
            ];
        }

        private function abrir_conexion_dev_lotes_salidas() {
            require_once(dirname(__FILE__) . '/../config/conexiondev.php');
            $devConn = new ConectarDev();
            return $devConn->getConecta() ?: null;
        }

        // Paso 1: lee y valida el archivo completo, sin insertar nada. Devuelve, además del detalle
        // por fila, la lista de filas válidas ("validos") para que el frontend las reenvíe tal cual
        // al paso de confirmación sin tener que resubir el archivo.
        public function validar_excel_salidas($tipo, $numdoc, $nit, $direccion, $filePath) {
            $rows = $this->leer_xlsx($filePath);
            if (isset($rows['error'])) {
                return json_encode(['status' => 'error', 'message' => $rows['error']]);
            }
            if (count($rows) < 2) {
                return json_encode(['status' => 'error', 'message' => 'El archivo no contiene datos (solo encabezado o vacío)']);
            }

            $cn     = new Conectarserver;
            $cnDev  = $this->abrir_conexion_dev_lotes_salidas();
            $idLista = $this->resolver_id_lista($cn, $tipo, $numdoc, $nit, $direccion);

            $resultados = [];
            $validos    = [];
            $procesados = [];

            for ($i = 1; $i < count($rows); $i++) {
                $row        = $rows[$i];
                $idProducto = trim($row[0] ?? '');
                $cantidad   = trim($row[1] ?? '');
                $nota       = trim($row[2] ?? '');
                $lote       = trim($row[3] ?? '');

                if ($idProducto === '' && $cantidad === '') continue; // fila vacía

                $check = $this->validar_fila_salida($cn, $cnDev, $idProducto, $cantidad, $lote, $idLista, $procesados);

                if ($check['ok']) {
                    $mensaje = htmlspecialchars($check['nombreProducto']) .
                        ' | Precio: $' . number_format($check['precio'], 2, ',', '.') .
                        ($check['esDuplicado'] ? ' (Producto+Lote repetido en el archivo)' : '');
                    $resultados[] = [
                        'fila' => $i + 1, 'idProducto' => $idProducto, 'cantidad' => $cantidad, 'lote' => $lote,
                        'status' => $check['esDuplicado'] ? 'warning' : 'ok', 'mensaje' => $mensaje
                    ];
                    $validos[] = ['fila' => $i + 1, 'idProducto' => $idProducto, 'cantidad' => $cantidad, 'nota' => $nota, 'lote' => $lote];
                } else {
                    $resultados[] = [
                        'fila' => $i + 1, 'idProducto' => $idProducto, 'cantidad' => $cantidad, 'lote' => $lote,
                        'status' => 'error', 'mensaje' => $check['mensaje']
                    ];
                }
            }

            $ok      = count(array_filter($resultados, function($r) { return $r['status'] === 'ok'; }));
            $warning = count(array_filter($resultados, function($r) { return $r['status'] === 'warning'; }));
            $error   = count($resultados) - $ok - $warning;

            return json_encode([
                'status'     => 'ok',
                'ok'         => $ok,
                'warning'    => $warning,
                'error'      => $error,
                'resultados' => $resultados,
                'validos'    => $validos
            ]);
        }

        // Paso 2: inserta únicamente las filas ya validadas y confirmadas por el usuario.
        // Revalida cada una por si el catálogo cambió entre la vista previa y la confirmación.
        public function confirmar_masiva_excel_salidas($tipo, $numdoc, $nit, $direccion, $validos) {
            if (!is_array($validos) || count($validos) === 0) {
                return json_encode(['status' => 'error', 'message' => 'No hay filas válidas para procesar']);
            }

            $cn      = new Conectarserver;
            $cnDev   = $this->abrir_conexion_dev_lotes_salidas();
            $idLista = $this->resolver_id_lista($cn, $tipo, $numdoc, $nit, $direccion);

            $resultados = [];
            $procesados = [];

            foreach ($validos as $fila) {
                $idProducto = trim($fila['idProducto'] ?? '');
                $cantidad   = trim($fila['cantidad'] ?? '');
                $nota       = trim($fila['nota'] ?? '');
                $lote       = trim($fila['lote'] ?? '');

                $resultado = [
                    'fila' => $fila['fila'] ?? '', 'idProducto' => $idProducto, 'cantidad' => $cantidad,
                    'lote' => $lote, 'status' => 'error', 'mensaje' => ''
                ];

                $check = $this->validar_fila_salida($cn, $cnDev, $idProducto, $cantidad, $lote, $idLista, $procesados);
                if (!$check['ok']) {
                    $resultado['mensaje'] = $check['mensaje'];
                    $resultados[] = $resultado; continue;
                }

                $loteVal  = $lote !== '' ? $lote : '0';
                $insertar = $this->agregar_linea_manual(
                    $tipo, $numdoc, $idProducto, (float)$cantidad,
                    $check['precio'], $loteVal, date('Y-m-d'), $check['porcentajeImpuesto'], $nota
                );
                $ins = json_decode($insertar, true);
                if ($ins && $ins['status'] === 'success') {
                    $resultado['status']  = $check['esDuplicado'] ? 'warning' : 'ok';
                    $resultado['mensaje'] = htmlspecialchars($check['nombreProducto']) .
                        ' | Precio: $' . number_format($check['precio'], 2, ',', '.') .
                        ($check['esDuplicado'] ? ' (Producto+Lote repetido en el archivo, agregado igual)' : '');
                } else {
                    $resultado['mensaje'] = 'Error al insertar línea: ' . ($ins['message'] ?? 'desconocido');
                }
                $resultados[] = $resultado;
            }

            $ok      = count(array_filter($resultados, function($r) { return $r['status'] === 'ok'; }));
            $warning = count(array_filter($resultados, function($r) { return $r['status'] === 'warning'; }));
            $error   = count(array_filter($resultados, function($r) { return $r['status'] === 'error'; }));

            return json_encode([
                'status'     => 'ok',
                'ok'         => $ok,
                'warning'    => $warning,
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
            // Las devoluciones tienen Tipo_Docto_Base != '0', se restan para liberar la OS
            $sql_pend = "SELECT
                                SUM(CASE WHEN (dlp.cantidad - ISNULL(f.total_facturado, 0)) > 0 THEN 1 ELSE 0 END) AS con_pendiente,
                                SUM(dlp.cantidad) AS total_ordenado,
                                ISNULL(SUM(f.total_facturado), 0) AS total_despachado
                         FROM Documentos_Lin_Ped dlp
                         LEFT JOIN (
                             SELECT dl.IdProducto, dl.seq,
                                    SUM(CASE WHEN d.Tipo_Docto_Base = '0' THEN dl.Cantidad_Facturada ELSE -dl.Cantidad_Facturada END) AS total_facturado
                             FROM Documentos d
                             JOIN Documentos_Lin dl ON dl.tipo = d.tipo AND dl.Numero_Documento = d.Numero_documento
                             WHERE d.Numero_Docto_Base_2 = '$numero' AND d.Tipo_Docto_Base_2 = '10'
                             AND d.bodega = (SELECT bodega FROM Documentos_Ped WHERE numero_pedido = '$numero' AND sw = '10')
                             GROUP BY dl.IdProducto, dl.seq
                         ) f ON f.IdProducto = dlp.IdProducto AND f.seq = dlp.Linea
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

        /**
         * Seguimiento de una Orden de Salida ítem por ítem: cuánto se pidió de cada línea,
         * cuánto se ha descontado y EN QUÉ DOCUMENTOS se descontó.
         *
         * El enlace entre una línea de la OS y una línea de documento es por
         * (IdProducto + Linea/seq), no solo por producto: una misma OS puede pedir el mismo
         * producto en varias líneas con cantidades distintas, y agrupar solo por producto
         * las mezclaría.
         *
         * Las devoluciones (Tipo_Docto_Base <> '0') restan, igual que en el cálculo de
         * pendientes que ya usan insert_doc_salida y reiniciar_doc_desde_os, para que el
         * pendiente que se muestra aquí sea el mismo que el sistema aplica al despachar.
         */
        public function seguimiento_os($numero) {
            $cn = new Conectarserver;
            $conn = $cn->getConecta();
            $vacio = array('status' => 'no_existe', 'message' => 'La Orden de Salida no existe');

            $numero = trim($numero);
            if ($numero === '') {
                return json_encode(array('status' => 'error', 'message' => 'Debe indicar el número de la Orden de Salida'));
            }
            // numero_pedido es int en la base: sin esta validación, un texto provoca un
            // error de conversión de SQL Server en vez de un mensaje entendible.
            if (!ctype_digit($numero)) {
                return json_encode(array('status' => 'error', 'message' => 'El número de la Orden de Salida debe ser numérico'));
            }

            // 1. Cabecera de la OS
            $sqlOs = "SELECT dp.numero_pedido, dp.nit, LTRIM(RTRIM(t.nombre)) AS cliente, dp.bodega,
                             dp.fecha_hora_pedido, dp.anulado, dp.exportado, dp.despacho,
                             LTRIM(RTRIM(dp.notas)) AS notas, LTRIM(RTRIM(dp.usuario)) AS usuario
                      FROM Documentos_Ped dp
                      LEFT JOIN TblTerceros t ON t.nit_cedula = dp.nit
                      WHERE dp.numero_pedido = ? AND dp.sw = '10'";
            $stOs = sqlsrv_query($conn, $sqlOs, array($numero));
            if ($stOs === false) {
                $this->registrar_error("seguimiento_os cabecera ($numero): " . print_r(sqlsrv_errors(), true));
                return json_encode(array('status' => 'error', 'message' => 'Error al consultar la Orden de Salida'));
            }
            $os = sqlsrv_fetch_array($stOs, SQLSRV_FETCH_ASSOC);
            if (!$os) return json_encode($vacio);

            // En Documentos_Ped el campo anulado se guarda invertido: 1 = vigente, 0 = anulada.
            // Es el mismo criterio que usa validar_os (CAST(anulado AS int) = 1).
            $osAnulada = ((int)$os['anulado'] === 0);

            // 2. Líneas de la OS (todas, incluso las que aún no se han despachado)
            $sqlLin = "SELECT dlp.Linea, dlp.IdProducto, LTRIM(RTRIM(p.Producto)) AS Producto,
                              LTRIM(RTRIM(u.Unidad)) AS Unidad, dlp.cantidad AS ordenado,
                              LTRIM(RTRIM(ISNULL(dlp.nota, ''))) AS nota
                       FROM Documentos_Lin_Ped dlp
                       LEFT JOIN TblProducto p ON p.IdProducto = dlp.IdProducto
                       LEFT JOIN TblUnidad  u ON u.idUnidad   = dlp.und
                       WHERE dlp.numero_pedido = ? AND dlp.sw = '10'
                       ORDER BY dlp.Linea";
            $stLin = sqlsrv_query($conn, $sqlLin, array($numero));
            if ($stLin === false) {
                $this->registrar_error("seguimiento_os lineas ($numero): " . print_r(sqlsrv_errors(), true));
                return json_encode(array('status' => 'error', 'message' => 'Error al consultar las líneas de la OS'));
            }
            $lineas = array();
            while ($r = sqlsrv_fetch_array($stLin, SQLSRV_FETCH_ASSOC)) {
                $lineas[(string)$r['Linea']] = array(
                    'linea'       => $r['Linea'],
                    'idProducto'  => $r['IdProducto'],
                    'producto'    => $r['Producto'],
                    'unidad'      => $r['Unidad'],
                    'nota'        => $r['nota'],
                    'ordenado'    => (float)$r['ordenado'],
                    'despachado'  => 0.0,
                    'pendiente'   => (float)$r['ordenado'],
                    'movimientos' => array()
                );
            }

            // 3. Movimientos: una fila por (línea de la OS x documento que la consumió).
            //    A propósito NO se filtra por bodega. El cálculo de pendientes del despacho
            //    sí lo hace (d.bodega = bodega de la OS), y por eso ignora los documentos
            //    hechos desde otra bodega: en producción eso afecta a más de la mitad de las
            //    órdenes, casi siempre porque la OS quedó con bodega 0. Este módulo existe
            //    para mostrar la realidad, así que los trae todos y marca cuáles quedan
            //    fuera de ese cálculo.
            $sqlMov = "SELECT dlp.Linea, d.tipo, LTRIM(RTRIM(tt.TipoDoctos)) AS TipoDoctos,
                              d.Numero_documento, d.Fecha_Hora_Factura, d.exportado, d.anulado,
                              LTRIM(RTRIM(d.usuario)) AS usuario, dl.seq,
                              dl.Cantidad_Facturada, dl.Numero_Lote, d.bodega,
                              CASE WHEN d.Tipo_Docto_Base = '0' THEN 1 ELSE 0 END AS es_despacho
                       FROM Documentos_Lin_Ped dlp
                       INNER JOIN Documentos d
                               ON d.Numero_Docto_Base_2 = ? AND d.Tipo_Docto_Base_2 = '10'
                       INNER JOIN Documentos_Lin dl
                               ON dl.tipo = d.tipo AND dl.Numero_Documento = d.Numero_documento
                              AND dl.IdProducto = dlp.IdProducto AND dl.seq = dlp.Linea
                       LEFT JOIN TblTipoDoctos tt ON tt.idTipoDoctos = d.tipo
                       WHERE dlp.numero_pedido = ? AND dlp.sw = '10'
                       ORDER BY dlp.Linea, d.Fecha_Hora_Factura, d.Numero_documento";
            $stMov = sqlsrv_query($conn, $sqlMov, array($numero, $numero));
            if ($stMov === false) {
                $this->registrar_error("seguimiento_os movimientos ($numero): " . print_r(sqlsrv_errors(), true));
                return json_encode(array('status' => 'error', 'message' => 'Error al consultar los movimientos'));
            }

            $bodegaOs   = trim($os['bodega'] ?? '');
            $documentos = array();
            $movsOtraBodega = 0;
            while ($m = sqlsrv_fetch_array($stMov, SQLSRV_FETCH_ASSOC)) {
                $k = (string)$m['Linea'];
                if (!isset($lineas[$k])) continue;   // movimiento sin línea de OS: se ignora

                $esDespacho = ((int)$m['es_despacho'] === 1);
                $cantidad   = (float)$m['Cantidad_Facturada'];
                $anulado    = (trim($m['anulado'] ?? 'N') === 'S');
                $bodegaDoc  = trim($m['bodega'] ?? '');
                $otraBodega = ($bodegaDoc !== $bodegaOs);
                if ($otraBodega) $movsOtraBodega++;

                // Un documento anulado ya no descuenta: sus cantidades quedaron en cero al
                // anularlo, así que sumarlo no cambiaría nada, pero se muestra igual para
                // que se entienda por qué el pendiente volvió a subir.
                $lineas[$k]['despachado'] += ($esDespacho ? $cantidad : -$cantidad);

                $lineas[$k]['movimientos'][] = array(
                    'tipo'       => trim($m['tipo']),
                    'tipoNombre' => $m['TipoDoctos'],
                    'numero'     => $m['Numero_documento'],
                    'fecha'      => ($m['Fecha_Hora_Factura'] instanceof DateTime)
                                    ? $m['Fecha_Hora_Factura']->format('d/m/Y H:i') : '',
                    'cantidad'   => $cantidad,
                    'esDespacho' => $esDespacho,
                    'lote'       => is_string($m['Numero_Lote']) ? trim($m['Numero_Lote']) : $m['Numero_Lote'],
                    'exportado'  => trim($m['exportado']),
                    'anulado'    => $anulado ? 'S' : 'N',
                    'usuario'    => $m['usuario'],
                    'bodega'     => $bodegaDoc,
                    'otraBodega' => $otraBodega ? 'S' : 'N'
                );
                $documentos[trim($m['tipo']) . '-' . $m['Numero_documento']] = true;
            }

            // Documentos que apuntan a esta OS pero de los que no salió ni un movimiento:
            // sus líneas no enlazan por (IdProducto + Linea/seq). Ocurre en el 0,85% de los
            // documentos de OS, y como el cálculo de pendientes usa ese mismo enlace,
            // esas cantidades tampoco le cuentan a él. Sin este aviso, el usuario vería
            // "0 descontado" sin ninguna explicación.
            $docsSinEnlace = 0;
            $sqlHuerf = "SELECT COUNT(*) AS n FROM Documentos d
                         WHERE d.Numero_Docto_Base_2 = ? AND d.Tipo_Docto_Base_2 = '10'
                           AND NOT EXISTS (
                               SELECT 1 FROM Documentos_Lin dl
                               JOIN Documentos_Lin_Ped dlp
                                    ON dlp.numero_pedido = ? AND dlp.sw = '10'
                                   AND dlp.IdProducto = dl.IdProducto AND dlp.Linea = dl.seq
                               WHERE dl.tipo = d.tipo AND dl.Numero_Documento = d.Numero_documento)";
            $stHuerf = sqlsrv_query($conn, $sqlHuerf, array($numero, $numero));
            if ($stHuerf !== false) {
                $rowHuerf = sqlsrv_fetch_array($stHuerf, SQLSRV_FETCH_ASSOC);
                $docsSinEnlace = $rowHuerf ? (int)$rowHuerf['n'] : 0;
            }

            $totOrdenado = 0.0; $totDespachado = 0.0; $lineasPendientes = 0;
            foreach ($lineas as $k => $l) {
                $pendiente = $l['ordenado'] - $l['despachado'];
                $lineas[$k]['pendiente'] = $pendiente;
                $totOrdenado   += $l['ordenado'];
                $totDespachado += $l['despachado'];
                if ($pendiente > 0) $lineasPendientes++;
            }

            return json_encode(array(
                'status' => 'ok',
                'os' => array(
                    'numero'  => trim($os['numero_pedido']),
                    'nit'     => trim($os['nit'] ?? ''),
                    'cliente' => $os['cliente'],
                    'bodega'  => trim($os['bodega'] ?? ''),
                    'fecha'   => ($os['fecha_hora_pedido'] instanceof DateTime)
                                 ? $os['fecha_hora_pedido']->format('d/m/Y H:i') : '',
                    'usuario' => $os['usuario'],
                    'notas'   => $os['notas'],
                    'anulada' => $osAnulada ? 'S' : 'N'
                ),
                'totales' => array(
                    'lineas'           => count($lineas),
                    'lineasPendientes' => $lineasPendientes,
                    'documentos'       => count($documentos),
                    // Movimientos que el cálculo de pendientes del despacho NO ve, por estar
                    // en una bodega distinta a la de la OS. Si es > 0, esta orden puede
                    // volver a despacharse aunque ya esté servida.
                    'movsOtraBodega'   => $movsOtraBodega,
                    'bodegaOs'         => $bodegaOs,
                    // Documentos que apuntan a la OS pero cuyas líneas no enlazan por
                    // (producto + número de línea): tampoco los ve el despacho.
                    'docsSinEnlace'    => $docsSinEnlace,
                    'ordenado'         => $totOrdenado,
                    'despachado'       => $totDespachado,
                    'pendiente'        => $totOrdenado - $totDespachado
                ),
                'lineas' => array_values($lineas)
            ));
        }

        public function preview_doc_devolucion($numero, $tiporef) {
            $cn = new Conectarserver;

            $sql = "SELECT d.Numero_documento, d.Fecha_Hora_Factura, LTRIM(RTRIM(d.Nombre_Cliente)) AS Nombre_Cliente,
                           d.nit_Cedula, d.exportado, LTRIM(RTRIM(tt.TipoDoctos)) AS TipoDoctos,
                           (SELECT COUNT(*) FROM Documentos dev
                            WHERE dev.Numero_Docto_Base = CAST(d.Numero_documento AS VARCHAR)
                            AND dev.Tipo_Docto_Base = '$tiporef'
                            AND dev.exportado = 'S') AS tiene_devolucion
                    FROM Documentos d
                    INNER JOIN TblTipoDoctos tt ON tt.idTipoDoctos = d.tipo
                    WHERE d.Numero_documento = ? AND d.tipo = ?";

            $stmt = sqlsrv_query($cn->getConecta(), $sql, array($numero, $tiporef));
            if (!$stmt) {
                return json_encode(['status' => 'error', 'message' => 'Error al consultar el documento']);
            }

            $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            if (!$row) {
                return json_encode(['status' => 'not_found']);
            }

            return json_encode([
                'status'           => 'found',
                'numero'           => $row['Numero_documento'],
                'tipo'             => $row['TipoDoctos'],
                'fecha'            => $row['Fecha_Hora_Factura'] ? date_format($row['Fecha_Hora_Factura'], 'd/m/Y') : '',
                'empresa'          => $row['Nombre_Cliente'],
                'nit'              => $row['nit_Cedula'],
                'exportado'        => $row['exportado'],
                'tiene_devolucion' => (int)$row['tiene_devolucion']
            ]);
        }

    }
?>
