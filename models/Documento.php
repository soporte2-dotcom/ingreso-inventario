<?php
    // Bitácora en MySQL de toda operación que crea, modifica o borra líneas de
    // documentos. SQL Server es la base heredada y no admite triggers propios.
    require_once(dirname(__FILE__) . '/AuditoriaDocumentos.php');

    class Documento extends Conectarserver{

        // Límites de la carga masiva por Excel (protegen memoria/workers de PHP y el SQL Server
        // cuando muchos usuarios cargan inventario a la vez a inicio de mes).
        const EXCEL_MAX_FILAS         = 3000;    // tope de filas de datos por archivo
        const EXCEL_MAX_BYTES         = 8388608; // 8 MB
        const CONFIRM_LOCK_TIMEOUT_SEG = 10;     // espera máx. por el lock del documento antes de fallar rápido

        public function insert_doc($tipo, $nit, $direccion, $usuario){
            $cn = new Conectarserver;
            $conn = $cn->getConecta();

            try {
                sqlsrv_begin_transaction($conn);

                // Reserva atómica del consecutivo: el UPDATE toma un lock exclusivo de fila
                // que se mantiene hasta el COMMIT/ROLLBACK, sin importar el nivel de aislamiento.
                // Dos creaciones concurrentes del mismo tipo de documento quedan serializadas
                // aquí (la segunda espera a que la primera confirme) en vez de recibir el mismo número.
                $sqlSeq = "UPDATE Consecutivos SET siguiente = siguiente + 1
                            OUTPUT INSERTED.siguiente AS nuevo
                            WHERE tipo = ?";
                $stmtSeq = sqlsrv_query($conn, $sqlSeq, array($tipo));
                if ($stmtSeq === false) {
                    throw new Exception("Error al reservar consecutivo: " . print_r(sqlsrv_errors(), true));
                }
                $rowSeq = sqlsrv_fetch_array($stmtSeq, SQLSRV_FETCH_ASSOC);
                if (!$rowSeq) {
                    throw new Exception("No existe un consecutivo configurado para el tipo de documento $tipo");
                }
                $consecutivo = (int)$rowSeq['nuevo'];

                $sql = "INSERT INTO Documentos(sw, tipo, modelo, Numero_Documento, Numero_Docto_Base,
                nit_Cedula, codigo_direccion, Fecha_Hora_Factura,Fecha_Hora_Vencimiento,Fecha_orden_Venta,
                condicion,valor_total, valor_aplicado, Retencion_1,Retencion_2, Retencion_3, retencion_causada, retencion_iva,retencion_ica,
                retencion_descuento, descuento_pie, DescuentoOrdenVenta, descuento_1, descuento_2, descuento_3, costo, IdVendedor, anulado, usuario,
                notas,pc, fecha_hora, duracion, bodega, Valor_impuesto, Impuesto_Consumo, impuesto_deporte, concepto, vencimiento_presup,
                exportado, prefijo, moneda, CentroDeCostosDoc, valor_mercancia, abono, Comision_Vendedor, Tasa_Moneda_Ext, Tomador, Tasa_Fija_o_Variable, Punto_FOB,
                Fletes_Moneda_Ext, Miselaneos_Moneda_Ext, Cargo_Por_Fletes, Impuesto_Por_Fletes, Total_Items, Nombre_Cliente, Ordenado_Por, Telefono_De_Envio_1,
                Telefono_De_Envio_2, Factura_Impresa, IdFormaEnvio, IdTransportador, nit_Cedula_2, codigo_direccion_2, Numero_Docto_Base_2, Tipo_Docto_Base,
                Tipo_Docto_Base_2, IdActividadEconomica, TarifaReteFuenteCree, Valor_ReteCree, IdVehiculo)

                (SELECT do.tipo AS sw, ? AS tipo, ? AS modelo, ? AS Numero_Documento, ? AS Numero_Docto_Base,
                ? AS nit_Cedula, ? AS codigo_direccion,  GETDATE() AS Fecha_Hora_Factura, GETDATE() AS Fecha_Hora_Vencimiento, GETDATE() AS Fecha_orden_Venta,
                t.condicion, 0 AS valor_total, 0 AS valor_aplicado, 0 AS Retencion_1, 0 AS Retencion_2, 0 AS Retencion_3, 0 AS retencion_causada, 0 AS retencion_iva,
                0 AS retencion_ica, 0 AS retencion_descuento, 0 AS descuento_pie, 0 AS DescuentoOrdenVenta, 0 AS descuento_1, 0 AS descuento_2,0 AS descuento_3,
                0 AS costo, td.IdVendedor, 'N' AS anulado, u.Id_Usuario AS usuario,
                '' AS notas, HOST_NAME() AS pc, GETDATE() AS fecha_hora, 0 AS duracion, do.IdBodega AS bodega, 0 AS Valor_impuesto, 0 AS Impuesto_Consumo,
                0 AS impuesto_deporte, 0 AS concepto, GETDATE() AS vencimiento_presup,
                'N' AS exportado, '0' AS prefijo, 1 AS moneda, 0 AS CentroDeCostosDoc, 0 AS valor_mercancia, 0 AS abono, 0 AS Comision_Vendedor,
                1 AS Tasa_Moneda_Ext, '' AS Tomador, 'V' AS Tasa_Fija_o_Variable, td.idLista AS Punto_FOB,
                0 AS Fletes_Moneda_Ext, 0 AS Miselaneos_Moneda_Ext, 0 AS Cargo_Por_Fletes, 0 AS Impuesto_Por_Fletes, 2 AS Total_Items, t.nombre AS Nombre_Cliente,
                'Cerdos del Valle S.A' AS Ordenado_Por, td.telefono_1 AS Telefono_De_Envio_1, '' AS Telefono_De_Envio_2, 'N' AS Factura_Impresa, '1' AS IdFormaEnvio, '1' AS IdTransportador,
                ? AS nit_Cedula_2, ? AS codigo_direccion_2, '0' AS Numero_Docto_Base_2, '0' AS Tipo_Docto_Base,
                '0' AS Tipo_Docto_Base_2, '0' AS IdActividadEconomica, 0 AS TarifaReteFuenteCree, 0 AS Valor_ReteCree, '1' AS IdVehiculo
                FROM TblTerceros t, Terceros_Dir td, TblUsuarios u, TblTipoDoctos do
                WHERE td.codigo_direccion = ? AND u.Id_Usuario = ? AND td.nit = t.nit_cedula AND td.nit = ? AND do.idTipoDoctos = ?) ";

                $params = array(
                    $tipo, $tipo, $consecutivo, $consecutivo,
                    $nit, $direccion,
                    $nit, $direccion,
                    $direccion, $usuario, $nit, $tipo
                );

                $registros = sqlsrv_prepare($conn, $sql, $params);
                if (sqlsrv_execute($registros) === false) {
                    throw new Exception("Error al insertar documento: " . print_r(sqlsrv_errors(), true));
                }

                sqlsrv_commit($conn);

                return json_encode(array(
                    "status" => "success",
                    "message" => "Documento registrado correctamente",
                    "tipo" => $tipo,
                    "consecutivo" => $consecutivo
                ));

            } catch (Exception $e) {
                if (isset($conn) && $conn) {
                    sqlsrv_rollback($conn);
                }
                $this->registrar_error("Error en insert_doc: " . $e->getMessage());
                return json_encode(array(
                    "status" => "error",
                    "message" => $e->getMessage()
                ));
            }
        }
        
        // El parámetro $seq llega del navegador por compatibilidad con la vista, pero NO se
        // usa: el navegador lo refresca de forma asíncrona tras cada alta, así que con un
        // lector de código de barras dos productos seguidos podían quedar con el mismo seq.
        // Dos líneas con igual seq y producto hacen que delete_id borre AMBAS de un clic.
        // Aquí el seq se calcula en el servidor bajo UPDLOCK/HOLDLOCK, igual que en la
        // carga masiva de inventario, y queda serializado dentro de la transacción.
        public function insert_detalle($tipo, $consecutivo, $nit, $seq, $producto, $cantidad){
            $cn = new Conectarserver;
            $conn = $cn->getConecta();

            // Un documento ya guardado o anulado no admite líneas nuevas (mismo criterio
            // que delete_id/delete_masivo; hasta ahora esta ruta no lo validaba).
            $sqlChk = "SELECT exportado, anulado FROM Documentos WHERE tipo = ? AND Numero_documento = ?";
            $stmtChk = sqlsrv_query($conn, $sqlChk, array($tipo, $consecutivo));
            $rowChk  = $stmtChk ? sqlsrv_fetch_array($stmtChk, SQLSRV_FETCH_ASSOC) : null;
            if (!$rowChk) {
                echo "No Agregado: documento no encontrado \n";
                return;
            }
            if (trim($rowChk['exportado']) === 'S' || trim($rowChk['anulado'] ?? 'N') === 'S') {
                AuditoriaDocumentos::registrar(array(
                    'modulo' => 'Documentos', 'operacion' => 'insert_detalle',
                    'tipo' => $tipo, 'numero' => $consecutivo,
                    'exportado_antes' => $rowChk['exportado'], 'anulado_antes' => $rowChk['anulado'] ?? 'N',
                    'resultado' => 'bloqueado',
                    'mensaje' => "Intento de agregar producto=$producto a documento guardado/anulado"
                ));
                echo "No Agregado: el documento ya está guardado o anulado \n";
                return;
            }

            sqlsrv_begin_transaction($conn);
            try {

            $sqlSeq = "SELECT ISNULL(MAX(seq), 0) + 1 AS next_seq FROM Documentos_Lin WITH (UPDLOCK, HOLDLOCK)
                       WHERE tipo = ? AND Numero_documento = ?";
            $stmtSeq = sqlsrv_query($conn, $sqlSeq, array($tipo, $consecutivo));
            if ($stmtSeq === false) {
                throw new Exception("Error al reservar la secuencia de línea: " . print_r(sqlsrv_errors(), true));
            }
            $rowSeq  = sqlsrv_fetch_array($stmtSeq, SQLSRV_FETCH_ASSOC);
            $nuevoSeq = $rowSeq ? (int)$rowSeq['next_seq'] : 1;

            $sql="INSERT INTO Documentos_Lin (sw,tipo, seq, modelo, Numero_Documento, Numero_Docto_Base, Numero_Lote, Nit_Cedula, Codigo_Direccion, Fecha_Documento,
            IdProducto, IdUnidad, Factor_Conversion, Cantidad_Facturada, Cantidad_Pendiente, Cantidad_Orden, Costo_Unitario, Valor_Unitario,
            Valor_Impuesto, Porcentaje_Impuesto, Porcentaje_Descuento_1, Porcentaje_Descuento_2,Porcentaje_Descuento_3, IdVendedor, Comision_Vendedor,
            Valor_Comision_Vendedor, IdBodega, Maneja_Inventario, Tomador, IdMoneda, Tasa_Moneda_Ext, CentroDeCostosDoc,
            Nota_Linea, Unidades, Fecha_Vence, Exportado, Costo_Unitario_Inicial,
            Porcentaje_ReteFuente, Envase, Numero_Lote_Destino, serial, Impuesto_Consumo, Porcentaje_ReteFuente_2,
            Porcentaje_ReteFuente_3, Porcentaje_ReteFuente_4, Emp_1, Emp_2, Emp_3, Emp_4, Emp_5, Emp_6,
            Emp_7, Emp_8, Tara_1, Tara_2, Tara_3, Tara_4, Tara_5, Tara_6, Tara_7, Tara_8)
            
            (SELECT do.tipo AS sw, '$tipo' AS tipo,  $nuevoSeq AS seq, p.contable AS Modelo,  $consecutivo AS Numero_Documento,
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

            $registros = sqlsrv_prepare($conn, $sql);
            if(sqlsrv_execute($registros) === false){
                throw new Exception("Error al insertar la línea: " . print_r(sqlsrv_errors(), true));
            }
            $filas = sqlsrv_rows_affected($registros);
            sqlsrv_commit($conn);

            AuditoriaDocumentos::registrar(array(
                'modulo' => 'Documentos', 'operacion' => 'insert_detalle',
                'tipo' => $tipo, 'numero' => $consecutivo,
                'exportado_antes' => $rowChk['exportado'], 'anulado_antes' => $rowChk['anulado'] ?? 'N',
                'lineas_antes' => $nuevoSeq - 1,
                'lineas_despues' => AuditoriaDocumentos::contarLineas($conn, $tipo, $consecutivo),
                'filas_afectadas' => $filas,
                'resultado' => 'ok',
                'mensaje' => "seq=$nuevoSeq producto=$producto cantidad=$cantidad"
            ));

            // El INSERT ... SELECT rinde 0 filas si el producto o el documento no casan
            // en el FROM; antes eso se reportaba como "Agregado correctamente".
            if ($filas < 1) {
                echo"No Agregado: no se encontró el producto o el documento \n";
            } else {
                echo"Agregado correctamente \n";
            }

            } catch (Exception $e) {
                @sqlsrv_rollback($conn);
                AuditoriaDocumentos::registrar(array(
                    'modulo' => 'Documentos', 'operacion' => 'insert_detalle',
                    'tipo' => $tipo, 'numero' => $consecutivo,
                    'resultado' => 'error',
                    'mensaje' => "producto=$producto cantidad=$cantidad - " . $e->getMessage()
                ));
                echo"No Agregado \n";
            }

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
                // Corte temprano: si el archivo trae más filas del tope permitido, abortamos aquí
                // en vez de seguir cargando todo a memoria y arriesgar el worker de PHP.
                if (count($rows) > self::EXCEL_MAX_FILAS) {
                    return ['error' => 'El archivo supera el máximo de ' . self::EXCEL_MAX_FILAS . ' filas permitidas por carga. Divídelo en archivos más pequeños.'];
                }
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

        // Valida una fila del Excel de inventario (idProducto/cantidad/lote) sin escribir nada.
        // La usan tanto validar_excel_inventario (vista previa) como confirmar_masiva_excel_inventario
        // (revalida por si el catálogo cambió entre que se mostró la vista previa y se confirmó).
        private function validar_fila_inventario($conn, $cnDev, $idProducto, $cantidad, $lote, &$procesados) {
            if ($idProducto === '') {
                return ['ok' => false, 'mensaje' => 'IdProducto vacío'];
            }
            if (!is_numeric($cantidad) || (float)$cantidad <= 0) {
                return ['ok' => false, 'mensaje' => 'Cantidad debe ser un número mayor a 0'];
            }

            $sqlProd = "SELECT p.Producto, p.unidad_Inventario, p.costo_unitario
                        FROM TblProducto p WHERE p.IdProducto = ?";
            $stProd  = sqlsrv_query($conn, $sqlProd, array((int)$idProducto));
            if ($stProd === false) {
                return ['ok' => false, 'mensaje' => 'Error al consultar producto'];
            }
            $rProd = sqlsrv_fetch_array($stProd, SQLSRV_FETCH_ASSOC);
            if (!$rProd) {
                return ['ok' => false, 'mensaje' => 'Producto no existe en el sistema'];
            }

            if ($lote !== '' && $cnDev !== null) {
                $sqlLote = "SELECT COUNT(*) AS cnt FROM cvapptblfarmbatch WHERE numberBatch = ? AND statusBatch = 'S'";
                $stLote  = sqlsrv_query($cnDev, $sqlLote, array($lote));
                if ($stLote !== false) {
                    $rLote = sqlsrv_fetch_array($stLote, SQLSRV_FETCH_ASSOC);
                    if (!$rLote || (int)$rLote['cnt'] === 0) {
                        return ['ok' => false, 'mensaje' => 'Lote "' . $lote . '" no válido o inactivo'];
                    }
                }
            }

            // Duplicado = MISMO producto y MISMO lote repetidos. El negocio lo permite, así que NO se
            // bloquea: se inserta igual pero se marca con advertencia para que el usuario lo revise.
            // Mismo producto con lote distinto es normal y no genera advertencia.
            $clave        = $idProducto . '|' . $lote;
            $duplicado    = in_array($clave, $procesados);
            $procesados[] = $clave;

            $nombre = htmlspecialchars($rProd['Producto']);
            if ($duplicado) {
                return ['ok' => true, 'warn' => true, 'mensaje' => 'Producto y lote repetidos — ' . $nombre, 'producto' => $rProd];
            }
            return ['ok' => true, 'mensaje' => $nombre, 'producto' => $rProd];
        }

        private function abrir_conexion_dev_lotes() {
            require_once(dirname(__FILE__) . '/../config/conexiondev.php');
            $devConn = new ConectarDev();
            return $devConn->getConecta() ?: null;
        }

        // Minutos tras los cuales un token en estado 'procesando' se considera abandonado
        // (p. ej. un crash entre el claim y el commit) y puede ser retomado por un nuevo intento.
        const TOKEN_STALE_MIN = 10;

        // Idempotencia de la carga masiva mediante la tabla MySQL `cargamasivatoken` (patrón claim/confirm).
        // La tabla vive en OTRA base (permisos_tecno) que Documentos_Lin, así que NO comparte la
        // transacción de SQL Server; por eso se usa una máquina de estados 'procesando' → 'ok' y se
        // libera (DELETE) el token si la carga falla. Devuelve null si MySQL/la tabla no están
        // disponibles, y en ese caso la carga sigue sin protección de reintento.
        private function abrir_conexion_mysql() {
            try {
                require_once(dirname(__FILE__) . '/../config/conexionmysql.php');
                $my = new ConectarMysql();
                $pdo = $my->obtenerConexion();
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                return $pdo;
            } catch (Exception $e) {
                $this->registrar_error("carga masiva: no se pudo abrir MySQL para el token: " . $e->getMessage());
                return null;
            }
        }

        // Paso 1 (claim): reclama el token ANTES de tocar SQL Server. Devuelve:
        //   'nuevo'      → token reclamado, se puede continuar con la inserción
        //   'duplicado'  → la carga ya se procesó o está en curso (reintento/concurrente): NO reinsertar
        //   'sin_token'  → sin token o MySQL/tabla no disponibles: continuar sin protección de reintento
        private function reclamar_token_carga($token, $tipo, $numdoc, $usuario, $filas) {
            $token = trim($token);
            if ($token === '') return 'sin_token';
            $pdo = $this->abrir_conexion_mysql();
            if ($pdo === null) return 'sin_token';
            try {
                $ins = $pdo->prepare("INSERT INTO cargamasivatoken (token, tipo, numdoc, usuario, filas, estado, createdAt)
                                      VALUES (?, ?, ?, ?, ?, 'procesando', NOW())");
                $ins->execute([$token, $tipo, (int)$numdoc, (string)$usuario, (int)$filas]);
                return 'nuevo';
            } catch (PDOException $e) {
                // 23000 = violación de clave única: el token ya existe.
                if ($e->getCode() !== '23000') {
                    $this->registrar_error("carga masiva: error al reclamar token: " . $e->getMessage());
                    return 'sin_token'; // ante un error inesperado de MySQL, no bloqueamos la carga
                }
                // Si el token existente quedó 'procesando' y es viejo (crash previo), lo retomamos.
                try {
                    $del = $pdo->prepare("DELETE FROM cargamasivatoken
                                          WHERE token = ? AND estado = 'procesando'
                                            AND createdAt < (NOW() - INTERVAL ? MINUTE)");
                    $del->execute([$token, self::TOKEN_STALE_MIN]);
                    if ($del->rowCount() > 0) {
                        $ins = $pdo->prepare("INSERT INTO cargamasivatoken (token, tipo, numdoc, usuario, filas, estado, createdAt)
                                              VALUES (?, ?, ?, ?, ?, 'procesando', NOW())");
                        $ins->execute([$token, $tipo, (int)$numdoc, (string)$usuario, (int)$filas]);
                        return 'nuevo';
                    }
                } catch (PDOException $e2) {
                    // Otra petición lo retomó primero; se trata como duplicado.
                }
                return 'duplicado';
            }
        }

        // Paso 2 (confirm): marca el token como 'ok' tras el COMMIT de las líneas en SQL Server.
        private function confirmar_token_carga($token) {
            $token = trim($token);
            if ($token === '') return;
            $pdo = $this->abrir_conexion_mysql();
            if ($pdo === null) return;
            try {
                $up = $pdo->prepare("UPDATE cargamasivatoken SET estado = 'ok', updatedAt = NOW() WHERE token = ?");
                $up->execute([$token]);
            } catch (PDOException $e) {
                $this->registrar_error("carga masiva: error al confirmar token: " . $e->getMessage());
            }
        }

        // Paso 3 (liberar): borra el token si la carga falló o el documento estaba ocupado,
        // para que un reintento legítimo pueda volver a procesarse.
        private function liberar_token_carga($token) {
            $token = trim($token);
            if ($token === '') return;
            $pdo = $this->abrir_conexion_mysql();
            if ($pdo === null) return;
            try {
                $del = $pdo->prepare("DELETE FROM cargamasivatoken WHERE token = ? AND estado = 'procesando'");
                $del->execute([$token]);
            } catch (PDOException $e) {
                $this->registrar_error("carga masiva: error al liberar token: " . $e->getMessage());
            }
        }

        // Paso 1: lee y valida el archivo completo, sin tocar Documentos_Lin.
        // Devuelve, además del detalle por fila, la lista de filas válidas ("validos") para
        // que el frontend las reenvíe tal cual al paso de confirmación sin tener que resubir el archivo.
        public function validar_excel_inventario($filePath) {
            $rows = $this->leer_xlsx($filePath);
            if (isset($rows['error'])) {
                return json_encode(['status' => 'error', 'message' => $rows['error']]);
            }
            if (count($rows) < 2) {
                return json_encode(['status' => 'error', 'message' => 'El archivo no contiene datos (solo encabezado o vacío)']);
            }

            $cn    = new Conectarserver;
            $conn  = $cn->getConecta();
            $cnDev = $this->abrir_conexion_dev_lotes();

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

                $check = $this->validar_fila_inventario($conn, $cnDev, $idProducto, $cantidad, $lote, $procesados);

                $resultados[] = [
                    'fila'       => $i + 1,
                    'idProducto' => $idProducto,
                    'cantidad'   => $cantidad,
                    'lote'       => $lote,
                    'status'     => $check['ok'] ? 'ok' : 'error',
                    'warn'       => !empty($check['warn']),
                    'mensaje'    => $check['mensaje']
                ];

                if ($check['ok']) {
                    $validos[] = [
                        'fila'       => $i + 1,
                        'idProducto' => $idProducto,
                        'cantidad'   => $cantidad,
                        'nota'       => $nota,
                        'lote'       => $lote
                    ];
                }
            }

            $ok    = count($validos);
            $error = count($resultados) - $ok;
            $warn  = count(array_filter($resultados, function($r) { return !empty($r['warn']); }));

            return json_encode([
                'status'     => 'ok',
                'ok'         => $ok,
                'error'      => $error,
                'warn'       => $warn,
                'resultados' => $resultados,
                'validos'    => $validos
            ]);
        }

        // Paso 2: inserta únicamente las filas ya validadas y confirmadas por el usuario.
        // Revalida cada una por si el catálogo cambió entre la vista previa y la confirmación.
        public function confirmar_masiva_excel_inventario($tipo, $consecutivo, $validos, $token = '', $usuario = '') {
            if (!is_array($validos) || count($validos) === 0) {
                return json_encode(['status' => 'error', 'message' => 'No hay filas válidas para procesar']);
            }

            $cn    = new Conectarserver;
            $conn  = $cn->getConecta();
            $cnDev = $this->abrir_conexion_dev_lotes();

            $resultados = [];
            $procesados = [];

            // Idempotencia por token (claim): se reclama ANTES de tocar SQL Server. Si esta misma carga
            // ya se procesó o está en curso (reintento tras timeout / doble envío), no reinsertamos nada.
            $estadoToken = $this->reclamar_token_carga($token, $tipo, $consecutivo, $usuario, count($validos));
            if ($estadoToken === 'duplicado') {
                return json_encode(['status' => 'error', 'code' => 'ya_procesada', 'message' => 'Esta carga ya fue procesada anteriormente. Revisa el detalle del documento: no se duplicó nada.']);
            }

            try {
                sqlsrv_begin_transaction($conn);

                // Reserva del rango de seq para todo el lote en una sola lectura bloqueante:
                // UPDLOCK+HOLDLOCK evita que una carga simultánea al mismo documento lea el mismo
                // punto de partida (un SELECT normal libera su lock de inmediato bajo READ COMMITTED).
                $sqlSeq = "SELECT ISNULL(MAX(seq), 0) AS max_seq FROM Documentos_Lin WITH (UPDLOCK, HOLDLOCK)
                           WHERE tipo = ? AND Numero_documento = ?";
                $stmtSeq = sqlsrv_query($conn, $sqlSeq, array($tipo, $consecutivo),
                                        array("QueryTimeout" => self::CONFIRM_LOCK_TIMEOUT_SEG));
                if ($stmtSeq === false) {
                    // Con QueryTimeout, un false aquí casi siempre es lock en espera (otro usuario
                    // cargando el mismo documento) o servidor saturado. Fallamos rápido y claro,
                    // liberando el token para que el usuario pueda reintentar.
                    sqlsrv_rollback($conn);
                    $this->liberar_token_carga($token);
                    return json_encode(['status' => 'error', 'message' => 'El documento está siendo cargado por otra persona en este momento o el servidor está ocupado. Espera unos segundos y vuelve a intentar.']);
                }
                $rowSeq = sqlsrv_fetch_array($stmtSeq, SQLSRV_FETCH_ASSOC);
                $seq    = $rowSeq ? (int)$rowSeq['max_seq'] : 0;

                foreach ($validos as $fila) {
                    $idProducto = trim($fila['idProducto'] ?? '');
                    $cantidad   = trim($fila['cantidad'] ?? '');
                    $nota       = trim($fila['nota'] ?? '');
                    $lote       = trim($fila['lote'] ?? '');

                    $resultado = [
                        'fila'       => $fila['fila'] ?? '',
                        'idProducto' => $idProducto,
                        'cantidad'   => $cantidad,
                        'lote'       => $lote,
                        'status'     => 'error',
                        'mensaje'    => ''
                    ];

                    $check = $this->validar_fila_inventario($conn, $cnDev, $idProducto, $cantidad, $lote, $procesados);
                    if (!$check['ok']) {
                        $resultado['mensaje'] = $check['mensaje'];
                        $resultados[] = $resultado; continue;
                    }

                    $seq++;
                    $loteVal = $lote !== '' ? $lote : '0';
                    $notaVal = $nota !== '' ? $nota : ' ';
                    $costo   = (float)$check['producto']['costo_unitario'];

                    $sqlLin = "INSERT INTO Documentos_Lin (sw, tipo, seq, modelo, Numero_Documento, Numero_Docto_Base, Numero_Lote,
                    Nit_Cedula, Codigo_Direccion, Fecha_Documento, IdProducto, IdUnidad, Factor_Conversion,
                    Cantidad_Facturada, Cantidad_Pendiente, Cantidad_Orden, Costo_Unitario, Valor_Unitario,
                    Valor_Impuesto, Porcentaje_Impuesto, Porcentaje_Descuento_1, Porcentaje_Descuento_2, Porcentaje_Descuento_3,
                    IdVendedor, Comision_Vendedor, Valor_Comision_Vendedor, IdBodega, Maneja_Inventario, Tomador,
                    IdMoneda, Tasa_Moneda_Ext, CentroDeCostosDoc, Nota_Linea, Unidades, Fecha_Vence, Exportado,
                    Costo_Unitario_Inicial, Porcentaje_ReteFuente, Envase, Numero_Lote_Destino, serial, Impuesto_Consumo,
                    Porcentaje_ReteFuente_2, Porcentaje_ReteFuente_3, Porcentaje_ReteFuente_4,
                    Emp_1, Emp_2, Emp_3, Emp_4, Emp_5, Emp_6, Emp_7, Emp_8,
                    Tara_1, Tara_2, Tara_3, Tara_4, Tara_5, Tara_6, Tara_7, Tara_8)

                    SELECT '99', ?, ?, p.contable, d.Numero_Documento, 0, ?,
                    d.nit_Cedula, d.codigo_direccion, GETDATE(), ?, p.unidad_Inventario, 1,
                    ?, ?, 0, ?, ?,
                    0, 0, 0, 0, 0,
                    1, 0, 0, do.IdBodega, 'S', '',
                    1, 1, '0', ?, '1', '2000-01-01 00:00:00.000', 'N',
                    ?, 0, 0, 0, '', 0,
                    0, 0, 0,
                    0, 0, 0, 0, 0, 0, 0, 0,
                    0, 0, 0, 0, 0, 0, 0, 0

                    FROM Documentos d, TblProducto p, TblTipoDoctos do
                    WHERE p.IdProducto = ? AND d.tipo = ? AND d.Numero_Documento = ? AND do.idTipoDoctos = d.tipo";

                    $paramsLin = array(
                        $tipo, $seq, $loteVal,
                        $idProducto,
                        (float)$cantidad, (float)$cantidad * -1, $costo, $costo,
                        $notaVal,
                        $costo,
                        $idProducto, $tipo, $consecutivo
                    );

                    $stmtLin = sqlsrv_prepare($conn, $sqlLin, $paramsLin);
                    if (sqlsrv_execute($stmtLin) === false) {
                        throw new Exception("Error al insertar la línea del producto $idProducto: " . print_r(sqlsrv_errors(), true));
                    }

                    $resultado['status']  = 'ok';
                    $resultado['warn']    = !empty($check['warn']);
                    $resultado['mensaje'] = $check['mensaje'];
                    $resultados[] = $resultado;
                }

                sqlsrv_commit($conn);
                $this->confirmar_token_carga($token); // marcar el token como 'ok' tras el commit

            } catch (Exception $e) {
                if (isset($conn) && $conn) {
                    sqlsrv_rollback($conn);
                }
                $this->liberar_token_carga($token); // liberar el token para permitir un reintento legítimo
                $this->registrar_error("Error en confirmar_masiva_excel_inventario: " . $e->getMessage());
                return json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            }

            $ok    = count(array_filter($resultados, function($r) { return $r['status'] === 'ok'; }));
            $error = count(array_filter($resultados, function($r) { return $r['status'] === 'error'; }));
            $warn  = count(array_filter($resultados, function($r) { return !empty($r['warn']); }));

            return json_encode([
                'status'     => 'ok',
                'ok'         => $ok,
                'error'      => $error,
                'warn'       => $warn,
                'resultados' => $resultados
            ]);
        }

        public function generar_plantilla_excel_inventario() {
            $tmpFile = tempnam(sys_get_temp_dir(), 'plantilla_inv_');
            $zip = new ZipArchive();
            $zip->open($tmpFile, ZipArchive::OVERWRITE);

            $zip->addFromString('[Content_Types].xml',
                '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
                '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">' .
                '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>' .
                '<Default Extension="xml" ContentType="application/xml"/>' .
                '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>' .
                '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>' .
                '<Override PartName="/xl/sharedStrings.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sharedStrings+xml"/>' .
                '</Types>'
            );

            $zip->addFromString('_rels/.rels',
                '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
                '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' .
                '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>' .
                '</Relationships>'
            );

            $zip->addFromString('xl/workbook.xml',
                '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
                '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">' .
                '<sheets><sheet name="Inventario" sheetId="1" r:id="rId1"/></sheets>' .
                '</workbook>'
            );

            $zip->addFromString('xl/_rels/workbook.xml.rels',
                '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
                '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' .
                '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>' .
                '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/sharedStrings" Target="sharedStrings.xml"/>' .
                '</Relationships>'
            );

            // Índices: 0=IdProducto 1=Cantidad 2=Nota 3=Lote 4=(nota ejemplo) 5=(lote ejemplo)
            $zip->addFromString('xl/sharedStrings.xml',
                '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
                '<sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" count="6" uniqueCount="6">' .
                '<si><t>IdProducto</t></si>' .
                '<si><t>Cantidad</t></si>' .
                '<si><t>Nota</t></si>' .
                '<si><t>Lote</t></si>' .
                '<si><t>Ingreso inicial de inventario</t></si>' .
                '<si><t>L001</t></si>' .
                '</sst>'
            );

            $zip->addFromString('xl/worksheets/sheet1.xml',
                '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
                '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">' .
                '<sheetData>' .
                '<row r="1">' .
                '<c r="A1" t="s"><v>0</v></c>' .
                '<c r="B1" t="s"><v>1</v></c>' .
                '<c r="C1" t="s"><v>2</v></c>' .
                '<c r="D1" t="s"><v>3</v></c>' .
                '</row>' .
                '<row r="2">' .
                '<c r="A2"><v>1000</v></c>' .
                '<c r="B2"><v>10</v></c>' .
                '<c r="C2" t="s"><v>4</v></c>' .
                '<c r="D2" t="s"><v>5</v></c>' .
                '</row>' .
                '</sheetData>' .
                '</worksheet>'
            );

            $zip->close();
            return $tmpFile;
        }

        // ─── Excel de Pedidos (idTipoDoctos=948) — mismo flujo validar→confirmar que Inventario,
        // reutilizando leer_xlsx/validar_fila_inventario/abrir_conexion_dev_lotes (genéricos),
        // pero con el INSERT fijando "sw" dinámicamente (do.tipo) en vez del '99' de Inventario.

        public function validar_excel_pedidos($filePath) {
            $rows = $this->leer_xlsx($filePath);
            if (isset($rows['error'])) {
                return json_encode(['status' => 'error', 'message' => $rows['error']]);
            }
            if (count($rows) < 2) {
                return json_encode(['status' => 'error', 'message' => 'El archivo no contiene datos (solo encabezado o vacío)']);
            }

            $cn    = new Conectarserver;
            $conn  = $cn->getConecta();
            $cnDev = $this->abrir_conexion_dev_lotes();

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

                $check = $this->validar_fila_inventario($conn, $cnDev, $idProducto, $cantidad, $lote, $procesados);

                $resultados[] = [
                    'fila'       => $i + 1,
                    'idProducto' => $idProducto,
                    'cantidad'   => $cantidad,
                    'lote'       => $lote,
                    'status'     => $check['ok'] ? 'ok' : 'error',
                    'warn'       => !empty($check['warn']),
                    'mensaje'    => $check['mensaje']
                ];

                if ($check['ok']) {
                    $validos[] = [
                        'fila'       => $i + 1,
                        'idProducto' => $idProducto,
                        'cantidad'   => $cantidad,
                        'nota'       => $nota,
                        'lote'       => $lote
                    ];
                }
            }

            $ok    = count($validos);
            $error = count($resultados) - $ok;
            $warn  = count(array_filter($resultados, function($r) { return !empty($r['warn']); }));

            return json_encode([
                'status'     => 'ok',
                'ok'         => $ok,
                'error'      => $error,
                'warn'       => $warn,
                'resultados' => $resultados,
                'validos'    => $validos
            ]);
        }

        public function confirmar_masiva_excel_pedidos($tipo, $consecutivo, $validos, $token = '', $usuario = '') {
            if (!is_array($validos) || count($validos) === 0) {
                return json_encode(['status' => 'error', 'message' => 'No hay filas válidas para procesar']);
            }

            $cn    = new Conectarserver;
            $conn  = $cn->getConecta();
            $cnDev = $this->abrir_conexion_dev_lotes();

            $resultados = [];
            $procesados = [];

            // Idempotencia por token (claim): se reclama ANTES de tocar SQL Server.
            $estadoToken = $this->reclamar_token_carga($token, $tipo, $consecutivo, $usuario, count($validos));
            if ($estadoToken === 'duplicado') {
                return json_encode(['status' => 'error', 'code' => 'ya_procesada', 'message' => 'Esta carga ya fue procesada anteriormente. Revisa el detalle del documento: no se duplicó nada.']);
            }

            try {
                sqlsrv_begin_transaction($conn);

                // Reserva del rango de seq con UPDLOCK+HOLDLOCK y QueryTimeout: si otra carga tiene
                // tomado el mismo documento (o el server está ocupado), fallamos rápido y claro.
                $sqlSeq = "SELECT ISNULL(MAX(seq), 0) AS max_seq FROM Documentos_Lin WITH (UPDLOCK, HOLDLOCK)
                           WHERE tipo = ? AND Numero_documento = ?";
                $stmtSeq = sqlsrv_query($conn, $sqlSeq, array($tipo, $consecutivo),
                                        array("QueryTimeout" => self::CONFIRM_LOCK_TIMEOUT_SEG));
                if ($stmtSeq === false) {
                    sqlsrv_rollback($conn);
                    $this->liberar_token_carga($token);
                    return json_encode(['status' => 'error', 'message' => 'El documento está siendo cargado por otra persona en este momento o el servidor está ocupado. Espera unos segundos y vuelve a intentar.']);
                }
                $rowSeq = sqlsrv_fetch_array($stmtSeq, SQLSRV_FETCH_ASSOC);
                $seq    = $rowSeq ? (int)$rowSeq['max_seq'] : 0;

                foreach ($validos as $fila) {
                    $idProducto = trim($fila['idProducto'] ?? '');
                    $cantidad   = trim($fila['cantidad'] ?? '');
                    $nota       = trim($fila['nota'] ?? '');
                    $lote       = trim($fila['lote'] ?? '');

                    $resultado = [
                        'fila'       => $fila['fila'] ?? '',
                        'idProducto' => $idProducto,
                        'cantidad'   => $cantidad,
                        'lote'       => $lote,
                        'status'     => 'error',
                        'mensaje'    => ''
                    ];

                    $check = $this->validar_fila_inventario($conn, $cnDev, $idProducto, $cantidad, $lote, $procesados);
                    if (!$check['ok']) {
                        $resultado['mensaje'] = $check['mensaje'];
                        $resultados[] = $resultado; continue;
                    }

                    $seq++;
                    $loteVal = $lote !== '' ? $lote : '0';
                    $notaVal = $nota !== '' ? $nota : ' ';
                    $costo   = (float)$check['producto']['costo_unitario'];

                    $sqlLin = "INSERT INTO Documentos_Lin (sw, tipo, seq, modelo, Numero_Documento, Numero_Docto_Base, Numero_Lote,
                    Nit_Cedula, Codigo_Direccion, Fecha_Documento, IdProducto, IdUnidad, Factor_Conversion,
                    Cantidad_Facturada, Cantidad_Pendiente, Cantidad_Orden, Costo_Unitario, Valor_Unitario,
                    Valor_Impuesto, Porcentaje_Impuesto, Porcentaje_Descuento_1, Porcentaje_Descuento_2, Porcentaje_Descuento_3,
                    IdVendedor, Comision_Vendedor, Valor_Comision_Vendedor, IdBodega, Maneja_Inventario, Tomador,
                    IdMoneda, Tasa_Moneda_Ext, CentroDeCostosDoc, Nota_Linea, Unidades, Fecha_Vence, Exportado,
                    Costo_Unitario_Inicial, Porcentaje_ReteFuente, Envase, Numero_Lote_Destino, serial, Impuesto_Consumo,
                    Porcentaje_ReteFuente_2, Porcentaje_ReteFuente_3, Porcentaje_ReteFuente_4,
                    Emp_1, Emp_2, Emp_3, Emp_4, Emp_5, Emp_6, Emp_7, Emp_8,
                    Tara_1, Tara_2, Tara_3, Tara_4, Tara_5, Tara_6, Tara_7, Tara_8)

                    SELECT do.tipo, ?, ?, p.contable, d.Numero_Documento, 0, ?,
                    d.nit_Cedula, d.codigo_direccion, GETDATE(), ?, p.unidad_Inventario, 1,
                    ?, ?, 0, ?, ?,
                    0, 0, 0, 0, 0,
                    1, 0, 0, do.IdBodega, 'S', '',
                    1, 1, '0', ?, '1', '2000-01-01 00:00:00.000', 'N',
                    ?, 0, 0, 0, '', 0,
                    0, 0, 0,
                    0, 0, 0, 0, 0, 0, 0, 0,
                    0, 0, 0, 0, 0, 0, 0, 0

                    FROM Documentos d, TblProducto p, TblTipoDoctos do
                    WHERE p.IdProducto = ? AND d.tipo = ? AND d.Numero_Documento = ? AND do.idTipoDoctos = d.tipo";

                    $paramsLin = array(
                        $tipo, $seq, $loteVal,
                        $idProducto,
                        (float)$cantidad, (float)$cantidad * -1, $costo, $costo,
                        $notaVal,
                        $costo,
                        $idProducto, $tipo, $consecutivo
                    );

                    $stmtLin = sqlsrv_prepare($conn, $sqlLin, $paramsLin);
                    if (sqlsrv_execute($stmtLin) === false) {
                        throw new Exception("Error al insertar la línea del producto $idProducto: " . print_r(sqlsrv_errors(), true));
                    }

                    $resultado['status']  = 'ok';
                    $resultado['warn']    = !empty($check['warn']);
                    $resultado['mensaje'] = $check['mensaje'];
                    $resultados[] = $resultado;
                }

                sqlsrv_commit($conn);
                $this->confirmar_token_carga($token); // marcar el token como 'ok' tras el commit

            } catch (Exception $e) {
                if (isset($conn) && $conn) {
                    sqlsrv_rollback($conn);
                }
                $this->liberar_token_carga($token); // liberar el token para permitir un reintento legítimo
                $this->registrar_error("Error en confirmar_masiva_excel_pedidos: " . $e->getMessage());
                return json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            }

            $ok    = count(array_filter($resultados, function($r) { return $r['status'] === 'ok'; }));
            $error = count(array_filter($resultados, function($r) { return $r['status'] === 'error'; }));
            $warn  = count(array_filter($resultados, function($r) { return !empty($r['warn']); }));

            return json_encode([
                'status'     => 'ok',
                'ok'         => $ok,
                'error'      => $error,
                'warn'       => $warn,
                'resultados' => $resultados
            ]);
        }

        public function generar_plantilla_excel_pedidos() {
            $tmpFile = tempnam(sys_get_temp_dir(), 'plantilla_ped_');
            $zip = new ZipArchive();
            $zip->open($tmpFile, ZipArchive::OVERWRITE);

            $zip->addFromString('[Content_Types].xml',
                '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
                '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">' .
                '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>' .
                '<Default Extension="xml" ContentType="application/xml"/>' .
                '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>' .
                '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>' .
                '<Override PartName="/xl/sharedStrings.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sharedStrings+xml"/>' .
                '</Types>'
            );

            $zip->addFromString('_rels/.rels',
                '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
                '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' .
                '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>' .
                '</Relationships>'
            );

            $zip->addFromString('xl/workbook.xml',
                '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
                '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">' .
                '<sheets><sheet name="Pedidos" sheetId="1" r:id="rId1"/></sheets>' .
                '</workbook>'
            );

            $zip->addFromString('xl/_rels/workbook.xml.rels',
                '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
                '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' .
                '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>' .
                '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/sharedStrings" Target="sharedStrings.xml"/>' .
                '</Relationships>'
            );

            // Índices: 0=IdProducto 1=Cantidad 2=Nota 3=Lote 4=(nota ejemplo) 5=(lote ejemplo)
            $zip->addFromString('xl/sharedStrings.xml',
                '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
                '<sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" count="6" uniqueCount="6">' .
                '<si><t>IdProducto</t></si>' .
                '<si><t>Cantidad</t></si>' .
                '<si><t>Nota</t></si>' .
                '<si><t>Lote</t></si>' .
                '<si><t>Pedido inicial</t></si>' .
                '<si><t>L001</t></si>' .
                '</sst>'
            );

            $zip->addFromString('xl/worksheets/sheet1.xml',
                '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
                '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">' .
                '<sheetData>' .
                '<row r="1">' .
                '<c r="A1" t="s"><v>0</v></c>' .
                '<c r="B1" t="s"><v>1</v></c>' .
                '<c r="C1" t="s"><v>2</v></c>' .
                '<c r="D1" t="s"><v>3</v></c>' .
                '</row>' .
                '<row r="2">' .
                '<c r="A2"><v>1000</v></c>' .
                '<c r="B2"><v>10</v></c>' .
                '<c r="C2" t="s"><v>4</v></c>' .
                '<c r="D2" t="s"><v>5</v></c>' .
                '</row>' .
                '</sheetData>' .
                '</worksheet>'
            );

            $zip->close();
            return $tmpFile;
        }

        public function update_doc($tipo, $consecutivo, $notas, $remision){
            $cn = new Conectarserver;

            $sqlChkLin = "SELECT COUNT(*) AS total FROM Documentos_Lin WHERE tipo = ? AND Numero_documento = ?";
            $stmtChkLin = sqlsrv_query($cn->getConecta(), $sqlChkLin, array($tipo, $consecutivo));
            $rowChkLin = $stmtChkLin ? sqlsrv_fetch_array($stmtChkLin, SQLSRV_FETCH_ASSOC) : null;
            if (!$rowChkLin || (int)$rowChkLin['total'] === 0) {
                echo "No se puede guardar: el documento debe tener al menos un producto \n";
                return;
            }

            if(empty($remision)){
                $sql="UPDATE Documentos SET notas = '$notas', exportado = 'S',
                Total_Items = (SELECT COUNT(*) FROM Documentos_Lin WHERE tipo = $tipo AND Numero_documento = $consecutivo),
                valor_total = (SELECT SUM(ROUND((d.Cantidad_Facturada * d.Valor_Unitario) * (1 - d.Porcentaje_Descuento_1 / 100), 2) + ((d.Cantidad_Facturada * d.Valor_Unitario) * (1 - d.Porcentaje_Descuento_1 / 100)) * (d.Porcentaje_Impuesto / 100)) 
                FROM Documentos_Lin d WHERE tipo = $tipo AND Numero_documento = $consecutivo),
                costo = (SELECT ISNULL(SUM(d.Cantidad_Facturada * d.Costo_Unitario), 0)
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
                costo = (SELECT ISNULL(SUM(d.Cantidad_Facturada * d.Costo_Unitario), 0)
                FROM Documentos_Lin d WHERE tipo = $tipo AND Numero_documento = $consecutivo),
                descuento_1 = (SELECT SUM(ROUND((d.Cantidad_Facturada * d.Valor_Unitario) * (d.Porcentaje_Descuento_1 / 100), 2))
                FROM Documentos_Lin d WHERE tipo = $tipo AND Numero_documento = $consecutivo),
                Valor_impuesto = (SELECT SUM(((d.Cantidad_Facturada * d.Valor_Unitario) * (1 - d.Porcentaje_Descuento_1 / 100)) * (d.Porcentaje_Impuesto / 100))
                FROM Documentos_Lin d WHERE tipo = $tipo AND Numero_documento = $consecutivo)
                WHERE tipo = $tipo AND Numero_Documento = $consecutivo";
            }

            // Estado y conteo justo antes de sellar el documento: esta es la foto de
            // referencia contra la que se compara si más tarde el detalle no coincide
            // con lo que se imprimió.
            $estadoPrevio = AuditoriaDocumentos::estadoDocumento($cn->getConecta(), $tipo, $consecutivo);
            $snap = AuditoriaDocumentos::snapshotLineas($cn->getConecta(), $tipo, $consecutivo);

            $registros = sqlsrv_prepare($cn->getConecta(), $sql);
            if(sqlsrv_execute($registros)){
                AuditoriaDocumentos::registrar(array(
                    'modulo' => 'Entradas', 'operacion' => 'guardar_doc',
                    'tipo' => $tipo, 'numero' => $consecutivo,
                    'exportado_antes' => $estadoPrevio['exportado'], 'anulado_antes' => $estadoPrevio['anulado'],
                    'lineas_antes' => $snap['lineas'], 'lineas_despues' => $snap['lineas'],
                    'resultado' => 'ok',
                    'mensaje' => 'Documento sellado (exportado = S). remision=' . $remision,
                    'detalle_antes' => $snap['detalle']
                ));
                echo"Documento Actualizado Correctamente \n";
            }else{
                AuditoriaDocumentos::registrar(array(
                    'modulo' => 'Entradas', 'operacion' => 'guardar_doc',
                    'tipo' => $tipo, 'numero' => $consecutivo,
                    'exportado_antes' => $estadoPrevio['exportado'], 'anulado_antes' => $estadoPrevio['anulado'],
                    'lineas_antes' => $snap['lineas'],
                    'resultado' => 'error', 'mensaje' => print_r(sqlsrv_errors(), true)
                ));
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

        // Lista documentos de Entrada visibles para el usuario según los tipos de documento
        // que tiene permiso de ver (no según quién los creó), acotado opcionalmente por
        // rango de fecha y rango de número de documento.
        public function listar_entradas_filtro($tipos_permitidos, $tipo, $fechaDesde, $fechaHasta, $numDesde, $numHasta, $exportado = '', $anulado = ''){
            $cn = new Conectarserver;
            $resultado = array();

            if (empty($tipos_permitidos)) {
                return $resultado;
            }

            // Si viene un tipo específico (ya validado por el controlador contra $tipos_permitidos),
            // se filtra solo por ese; si no, se filtra por todos los tipos permitidos del usuario.
            $tiposFiltro = ($tipo !== '' && in_array($tipo, $tipos_permitidos)) ? array($tipo) : $tipos_permitidos;

            $placeholders = implode(',', array_fill(0, count($tiposFiltro), '?'));
            $params = $tiposFiltro;

            $where = "tt.idTipoDoctos IN ($placeholders) AND tt.tipo IN ('12', '3')
                      AND tt.idTipoDoctos = d.tipo AND td.nit = d.nit_Cedula AND d.codigo_direccion = td.codigo_direccion
                      AND t.nit_cedula = d.nit_Cedula";

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

            $sql = "SELECT d.Fecha_Hora_Factura, d.tipo, tt.TipoDoctos, d.Numero_documento, d.Numero_Docto_Base, d.Tipo_Docto_Base_2, d.Numero_Docto_Base_2,
                    d.nit_Cedula, d.Nombre_Cliente, d.codigo_direccion, td.direccion, td.telefono_1, d.exportado, d.anulado, d.usuario
                    FROM Documentos d, Terceros_Dir td, TblTipoDoctos tt, TblTerceros t
                    WHERE $where
                    ORDER BY d.Fecha_Hora_Factura DESC";

            $registros = sqlsrv_query($cn->getConecta(), $sql, $params);

            if($registros === false) {
                $this->registrar_error("Error en listar_entradas_filtro: " . print_r(sqlsrv_errors(), true));
                return $resultado;
            }

            while($stmt = sqlsrv_fetch_array($registros, SQLSRV_FETCH_ASSOC)) {
                $resultado[] = $stmt;
            }

            return $resultado;
        }


        // Lista documentos de Inventario visibles para el usuario según los tipos de documento
        // que tiene permiso de ver (no según quién los creó), acotado opcionalmente por
        // rango de fecha, rango de número de documento y estado de exportado.
        public function listar_inventario_filtro($tipos_permitidos, $tipo, $fechaDesde, $fechaHasta, $numDesde, $numHasta, $exportado = '', $anulado = ''){
            $cn = new Conectarserver;
            $resultado = array();

            if (empty($tipos_permitidos)) {
                return $resultado;
            }

            $tiposFiltro = ($tipo !== '' && in_array($tipo, $tipos_permitidos)) ? array($tipo) : $tipos_permitidos;

            $placeholders = implode(',', array_fill(0, count($tiposFiltro), '?'));
            $params = $tiposFiltro;

            $where = "tt.idTipoDoctos IN ($placeholders) AND tt.tipo = '99'
                      AND tt.idTipoDoctos = d.tipo AND td.nit = d.nit_Cedula AND d.codigo_direccion = td.codigo_direccion
                      AND t.nit_cedula = d.nit_Cedula";

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

            $sql = "SELECT d.Fecha_Hora_Factura, d.tipo, tt.TipoDoctos, d.Numero_documento, d.Numero_Docto_Base, d.Tipo_Docto_Base_2, d.Numero_Docto_Base_2,
                    d.nit_Cedula, d.Nombre_Cliente, d.codigo_direccion, td.direccion, td.telefono_1, d.exportado, d.anulado, d.usuario
                    FROM Documentos d, Terceros_Dir td, TblTipoDoctos tt, TblTerceros t
                    WHERE $where
                    ORDER BY d.Fecha_Hora_Factura DESC";

            $registros = sqlsrv_query($cn->getConecta(), $sql, $params);

            if($registros === false) {
                $this->registrar_error("Error en listar_inventario_filtro: " . print_r(sqlsrv_errors(), true));
                return $resultado;
            }

            while($stmt = sqlsrv_fetch_array($registros, SQLSRV_FETCH_ASSOC)) {
                $resultado[] = $stmt;
            }

            return $resultado;
        }

        // Lista documentos de Pedidos visibles para el usuario según los tipos de documento
        // que tiene permiso de ver (no según quién los creó), acotado opcionalmente por
        // rango de fecha, rango de número de documento y estado de exportado.
        // A diferencia de Inventario/Entradas/Salidas, Pedidos está acotado a un único
        // idTipoDoctos (948, "Pedidos Granja"), no a una categoría "tipo".
        public function listar_pedidos_filtro($tipos_permitidos, $tipo, $fechaDesde, $fechaHasta, $numDesde, $numHasta, $exportado = '', $anulado = ''){
            $cn = new Conectarserver;
            $resultado = array();

            if (empty($tipos_permitidos)) {
                return $resultado;
            }

            $tiposFiltro = ($tipo !== '' && in_array($tipo, $tipos_permitidos)) ? array($tipo) : $tipos_permitidos;

            $placeholders = implode(',', array_fill(0, count($tiposFiltro), '?'));
            $params = $tiposFiltro;

            $where = "tt.idTipoDoctos IN ($placeholders) AND tt.idTipoDoctos = 948
                      AND tt.idTipoDoctos = d.tipo AND td.nit = d.nit_Cedula AND d.codigo_direccion = td.codigo_direccion
                      AND t.nit_cedula = d.nit_Cedula";

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

            $sql = "SELECT d.Fecha_Hora_Factura, d.tipo, tt.TipoDoctos, d.Numero_documento, d.Numero_Docto_Base, d.Tipo_Docto_Base_2, d.Numero_Docto_Base_2,
                    d.nit_Cedula, d.Nombre_Cliente, d.codigo_direccion, td.direccion, td.telefono_1, d.exportado, d.anulado, d.usuario
                    FROM Documentos d, Terceros_Dir td, TblTipoDoctos tt, TblTerceros t
                    WHERE $where
                    ORDER BY d.Fecha_Hora_Factura DESC";

            $registros = sqlsrv_query($cn->getConecta(), $sql, $params);

            if($registros === false) {
                $this->registrar_error("Error en listar_pedidos_filtro: " . print_r(sqlsrv_errors(), true));
                return $resultado;
            }

            while($stmt = sqlsrv_fetch_array($registros, SQLSRV_FETCH_ASSOC)) {
                $resultado[] = $stmt;
            }

            return $resultado;
        }


        public function listar_doc_x_id($tipo, $consecutivo){
            $cn = new Conectarserver;

            $sql="SELECT d.tipo, tt.TipoDoctos, tt.Prefijo, d.Numero_documento, d.Numero_Docto_Base, d.Tipo_Docto_Base_2, d.Numero_Docto_Base_2,
                d.nit_Cedula, d.Nombre_Cliente, d.codigo_direccion, td.direccion, td.telefono_1, LTRIM(RTRIM(td.ciudad)) AS ciudad,
                d.nit_Cedula_2, t.nombre AS nombre2, d.codigo_direccion_2, td2.direccion AS direccion2, d.notas, d.exportado, d.anulado, d.IdVendedor, d.Fecha_Hora_Factura,
                d.IdTransportador, d.IdVehiculo, d.RespuestaCorrectaDian, d.DescuentoOrdenVenta,
                LTRIM(RTRIM(tb.Bodega)) AS NombreBodega, LTRIM(RTRIM(tv.Vendedor)) AS NombreVendedor
                FROM Documentos d
                INNER JOIN Terceros_Dir td  ON td.nit = d.nit_Cedula AND d.codigo_direccion = td.codigo_direccion
                INNER JOIN TblTipoDoctos tt ON tt.idTipoDoctos = d.tipo
                LEFT  JOIN TblTerceros t    ON t.nit_cedula = d.nit_Cedula_2
                LEFT  JOIN Terceros_Dir td2 ON td2.nit = d.nit_Cedula_2 AND d.codigo_direccion_2 = td2.codigo_direccion
                LEFT  JOIN TblBodega tb     ON tb.IdBodega = d.bodega
                LEFT  JOIN TblVendedor tv   ON tv.Idvendedor = d.IdVendedor
                WHERE d.tipo = '$tipo' AND d.Numero_documento = '$consecutivo'";

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
            $sql="SELECT d.tipo, d.Numero_Documento, d.seq, d.IdProducto, p.Producto, u.Unidad, d.Cantidad_Facturada, d.Porcentaje_Descuento_1, d.Porcentaje_Impuesto, d.Valor_Unitario, d.Numero_Lote, d.Fecha_Vence, d.Nota_Linea, d.Unidades, o.exportado, o.anulado, p.IdGrupoProducto
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

            // Un documento ya guardado (exportado) o anulado no se puede modificar,
            // sin importar lo que muestre/permita la interfaz (mismo criterio que delete_id).
            $sqlChk = "SELECT exportado, anulado FROM Documentos WHERE tipo = ? AND Numero_documento = ?";
            $stmtChk = sqlsrv_query($cn->getConecta(), $sqlChk, array($tipo, $consecutivo));
            $rowChk = $stmtChk ? sqlsrv_fetch_array($stmtChk, SQLSRV_FETCH_ASSOC) : null;
            if (!$rowChk) {
                return "error: documento no encontrado";
            }
            if (trim($rowChk['exportado']) === 'S' || trim($rowChk['anulado'] ?? 'N') === 'S') {
                AuditoriaDocumentos::registrar(array(
                    'modulo' => 'Documentos', 'operacion' => 'delete_masivo', 'destructiva' => 1,
                    'tipo' => $tipo, 'numero' => $consecutivo,
                    'exportado_antes' => $rowChk['exportado'], 'anulado_antes' => $rowChk['anulado'] ?? 'N',
                    'resultado' => 'bloqueado',
                    'mensaje' => 'Intento de borrado masivo sobre documento guardado/anulado. seqs=' . $seqs
                ));
                return "error: el documento ya está guardado o anulado";
            }

            $seqArr     = array_filter(array_map('trim', explode(',', $seqs)));
            $prodArr    = array_filter(array_map('trim', explode(',', $productos)));

            if (empty($seqArr)) return "error: sin secuencias";

            // Foto del detalle ANTES de borrar: es lo que permite reponer las líneas
            // si después se reclama que el documento perdió información.
            $snap = AuditoriaDocumentos::snapshotLineas($cn->getConecta(), $tipo, $consecutivo);

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

            AuditoriaDocumentos::registrar(array(
                'modulo' => 'Documentos', 'operacion' => 'delete_masivo', 'destructiva' => 1,
                'tipo' => $tipo, 'numero' => $consecutivo,
                'exportado_antes' => $rowChk['exportado'], 'anulado_antes' => $rowChk['anulado'] ?? 'N',
                'lineas_antes' => $snap['lineas'],
                'lineas_despues' => AuditoriaDocumentos::contarLineas($cn->getConecta(), $tipo, $consecutivo),
                'filas_afectadas' => count($seqArr) - $errores,
                'resultado' => $errores > 0 ? 'error' : 'ok',
                'mensaje' => 'seqs=' . $seqs . ' productos=' . $productos . ($errores > 0 ? " ($errores fallaron)" : ''),
                'detalle_antes' => $snap['detalle']
            ));

            if ($errores > 0) return "error: fallaron $errores eliminaciones";

            // Actualizar totales del documento una sola vez al final
            $sqlUpdate = "UPDATE Documentos SET
                Total_Items    = (SELECT COUNT(*) FROM Documentos_Lin WHERE tipo = ? AND Numero_documento = ?),
                valor_total    = (SELECT ISNULL(SUM(ROUND((d.Cantidad_Facturada * d.Valor_Unitario) * (1 - d.Porcentaje_Descuento_1 / 100), 2) + ((d.Cantidad_Facturada * d.Valor_Unitario) * (1 - d.Porcentaje_Descuento_1 / 100)) * (d.Porcentaje_Impuesto / 100)), 0) FROM Documentos_Lin d WHERE tipo = ? AND Numero_documento = ?),
                costo          = (SELECT ISNULL(SUM(d.Cantidad_Facturada * d.Costo_Unitario), 0) FROM Documentos_Lin d WHERE tipo = ? AND Numero_documento = ?),
                descuento_1    = (SELECT ISNULL(SUM(ROUND((d.Cantidad_Facturada * d.Valor_Unitario) * (d.Porcentaje_Descuento_1 / 100), 2)), 0) FROM Documentos_Lin d WHERE tipo = ? AND Numero_documento = ?),
                Valor_impuesto = (SELECT ISNULL(SUM(((d.Cantidad_Facturada * d.Valor_Unitario) * (1 - d.Porcentaje_Descuento_1 / 100)) * (d.Porcentaje_Impuesto / 100)), 0) FROM Documentos_Lin d WHERE tipo = ? AND Numero_documento = ?)
                WHERE tipo = ? AND Numero_Documento = ?";

            $paramsUpdate = [];
            for ($i = 0; $i < 5; $i++) {
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

            // Un documento ya guardado (exportado) o anulado no se puede modificar,
            // sin importar lo que muestre/permita la interfaz.
            $sqlChk = "SELECT exportado, anulado FROM Documentos WHERE tipo = ? AND Numero_documento = ?";
            $stmtChk = sqlsrv_query($cn->getConecta(), $sqlChk, array($tipo, $consecutivo));
            $rowChk = $stmtChk ? sqlsrv_fetch_array($stmtChk, SQLSRV_FETCH_ASSOC) : null;
            if (!$rowChk) {
                echo "error";
                return;
            }
            if (trim($rowChk['exportado']) === 'S' || trim($rowChk['anulado'] ?? 'N') === 'S') {
                AuditoriaDocumentos::registrar(array(
                    'modulo' => 'Documentos', 'operacion' => 'delete_id', 'destructiva' => 1,
                    'tipo' => $tipo, 'numero' => $consecutivo,
                    'exportado_antes' => $rowChk['exportado'], 'anulado_antes' => $rowChk['anulado'] ?? 'N',
                    'resultado' => 'bloqueado',
                    'mensaje' => "Intento de borrado sobre documento guardado/anulado. seq=$seq producto=$producto"
                ));
                echo "error";
                return;
            }

            // Foto del detalle ANTES de borrar: es lo que permite reponer la línea
            // si después se reclama que el documento perdió información.
            $snap = AuditoriaDocumentos::snapshotLineas($cn->getConecta(), $tipo, $consecutivo);

            // Eliminar el registro
            $sql = "DELETE FROM Documentos_Lin
                    WHERE tipo = ? AND Numero_documento = ? AND IdProducto = ? AND seq = ?";
            
            $params = array($tipo, $consecutivo, $producto, $seq);
            $stmt = sqlsrv_prepare($cn->getConecta(), $sql, $params);
            
            if(sqlsrv_execute($stmt)) {
                // Se registra sqlsrv_rows_affected: si borra más de 1 fila hay seq
                // duplicados en el documento y el operario perdió una línea que no tocó.
                $filas = sqlsrv_rows_affected($stmt);
                AuditoriaDocumentos::registrar(array(
                    'modulo' => 'Documentos', 'operacion' => 'delete_id', 'destructiva' => 1,
                    'tipo' => $tipo, 'numero' => $consecutivo,
                    'exportado_antes' => $rowChk['exportado'], 'anulado_antes' => $rowChk['anulado'] ?? 'N',
                    'lineas_antes' => $snap['lineas'],
                    'lineas_despues' => AuditoriaDocumentos::contarLineas($cn->getConecta(), $tipo, $consecutivo),
                    'filas_afectadas' => $filas,
                    'resultado' => 'ok',
                    'mensaje' => "seq=$seq producto=$producto" . ($filas > 1 ? " *** BORRÓ $filas FILAS (seq duplicado) ***" : ''),
                    'detalle_antes' => $snap['detalle']
                ));
                
                $sqlUpdate = "UPDATE Documentos SET 
                    Total_Items = (SELECT COUNT(*) FROM Documentos_Lin WHERE tipo = ? AND Numero_documento = ?),
                    valor_total = (SELECT ISNULL(SUM(ROUND((dl.Cantidad_Facturada * dl.Valor_Unitario) * (1 - ISNULL(dl.Porcentaje_Descuento_1, 0) / 100), 2) + ((dl.Cantidad_Facturada * dl.Valor_Unitario) * (1 - ISNULL(dl.Porcentaje_Descuento_1, 0) / 100)) * (ISNULL(dl.Porcentaje_Impuesto, 0) / 100)), 0) FROM Documentos_Lin dl WHERE dl.tipo = ? AND dl.Numero_documento = ?),
                    costo = (SELECT ISNULL(SUM(dl.Cantidad_Facturada * dl.Costo_Unitario), 0) FROM Documentos_Lin dl WHERE dl.tipo = ? AND dl.Numero_documento = ?),
                    descuento_1 = (SELECT ISNULL(SUM(ROUND((dl.Cantidad_Facturada * dl.Valor_Unitario) * (ISNULL(dl.Porcentaje_Descuento_1, 0) / 100), 2)), 0) FROM Documentos_Lin dl WHERE dl.tipo = ? AND dl.Numero_documento = ?),
                    Valor_impuesto = (SELECT ISNULL(SUM(((dl.Cantidad_Facturada * dl.Valor_Unitario) * (1 - ISNULL(dl.Porcentaje_Descuento_1, 0) / 100)) * (ISNULL(dl.Porcentaje_Impuesto, 0) / 100)), 0) FROM Documentos_Lin dl WHERE dl.tipo = ? AND dl.Numero_documento = ?)
                    WHERE tipo = ? AND Numero_Documento = ?";

                $paramsUpdate = array();
                for ($i = 0; $i < 5; $i++) {
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
                AuditoriaDocumentos::registrar(array(
                    'modulo' => 'Documentos', 'operacion' => 'delete_id', 'destructiva' => 1,
                    'tipo' => $tipo, 'numero' => $consecutivo,
                    'exportado_antes' => $rowChk['exportado'], 'anulado_antes' => $rowChk['anulado'] ?? 'N',
                    'lineas_antes' => $snap['lineas'],
                    'resultado' => 'error',
                    'mensaje' => "seq=$seq producto=$producto - " . print_r(sqlsrv_errors(), true),
                    'detalle_antes' => $snap['detalle']
                ));
                echo "error";
            }
        }

        // Refresca %IVA y Valor de una línea ya agregada tomando los datos actuales del
        // producto en el catálogo (TblProducto/TblImpuesto), en vez de dejar que el usuario
        // los digite a mano. Pensado para cuando un producto se corrige en el catálogo
        // (p. ej. le agregan IVA) después de que ya se había agregado a un documento.
        public function actualizar_producto_linea($tipo, $consecutivo, $producto, $seq) {
            $cn = new Conectarserver;

            $sqlChk = "SELECT exportado, anulado FROM Documentos WHERE tipo = ? AND Numero_documento = ?";
            $stmtChk = sqlsrv_query($cn->getConecta(), $sqlChk, array($tipo, $consecutivo));
            $rowChk = $stmtChk ? sqlsrv_fetch_array($stmtChk, SQLSRV_FETCH_ASSOC) : null;
            if (!$rowChk) {
                return json_encode(['status' => 'error', 'message' => 'Documento no encontrado']);
            }
            if (trim($rowChk['exportado']) === 'S' || trim($rowChk['anulado'] ?? 'N') === 'S') {
                return json_encode(['status' => 'error', 'message' => 'El documento ya está guardado o anulado, no se puede modificar']);
            }

            $sqlProd = "SELECT p.costo_unitario, ISNULL(i.PorcentajeImpuesto, 0) AS PorcentajeImpuesto
                        FROM TblProducto p
                        LEFT JOIN TblImpuesto i ON p.Impuesto_venta = i.IdImpuesto
                        WHERE p.IdProducto = ?";
            $stmtProd = sqlsrv_query($cn->getConecta(), $sqlProd, array((int)$producto));
            $rowProd = $stmtProd ? sqlsrv_fetch_array($stmtProd, SQLSRV_FETCH_ASSOC) : null;
            if (!$rowProd) {
                return json_encode(['status' => 'error', 'message' => 'Producto no encontrado en el catálogo']);
            }

            $costoUnitario = (float)$rowProd['costo_unitario'];
            $porcentajeImpuesto = (float)$rowProd['PorcentajeImpuesto'];

            // Solo se refrescan %IVA y Costo_Unitario desde el catálogo. Valor_Unitario
            // no se toca: es el precio de compra real (de la OC o digitado manualmente),
            // no necesariamente igual al costo registrado en el catálogo.
            $snapProd = AuditoriaDocumentos::snapshotLineas($cn->getConecta(), $tipo, $consecutivo);

            $sql = "UPDATE Documentos_Lin SET Costo_Unitario = ?, Porcentaje_Impuesto = ?
                    WHERE tipo = ? AND Numero_documento = ? AND IdProducto = ? AND seq = ?";
            $params = array($costoUnitario, $porcentajeImpuesto, $tipo, $consecutivo, $producto, $seq);
            $stmt = sqlsrv_prepare($cn->getConecta(), $sql, $params);
            if (!sqlsrv_execute($stmt)) {
                return json_encode(['status' => 'error', 'message' => 'Error al actualizar la línea: ' . print_r(sqlsrv_errors(), true)]);
            }

            AuditoriaDocumentos::registrar(array(
                'modulo' => 'Documentos', 'operacion' => 'actualizar_producto_linea', 'destructiva' => 1,
                'tipo' => $tipo, 'numero' => $consecutivo,
                'exportado_antes' => $rowChk['exportado'], 'anulado_antes' => $rowChk['anulado'] ?? 'N',
                'lineas_antes' => $snapProd['lineas'], 'lineas_despues' => $snapProd['lineas'],
                'filas_afectadas' => sqlsrv_rows_affected($stmt),
                'resultado' => 'ok',
                'mensaje' => "seq=$seq producto=$producto | costo=$costoUnitario iva=$porcentajeImpuesto",
                'detalle_antes' => $snapProd['detalle']
            ));

            $sqlUpdate = "UPDATE Documentos SET
                Total_Items    = (SELECT COUNT(*) FROM Documentos_Lin WHERE tipo = ? AND Numero_documento = ?),
                valor_total    = (SELECT ISNULL(SUM(ROUND((d.Cantidad_Facturada * d.Valor_Unitario) * (1 - d.Porcentaje_Descuento_1 / 100), 2) + ((d.Cantidad_Facturada * d.Valor_Unitario) * (1 - d.Porcentaje_Descuento_1 / 100)) * (d.Porcentaje_Impuesto / 100)), 0) FROM Documentos_Lin d WHERE tipo = ? AND Numero_documento = ?),
                costo          = (SELECT ISNULL(SUM(d.Cantidad_Facturada * d.Costo_Unitario), 0) FROM Documentos_Lin d WHERE tipo = ? AND Numero_documento = ?),
                descuento_1    = (SELECT ISNULL(SUM(ROUND((d.Cantidad_Facturada * d.Valor_Unitario) * (d.Porcentaje_Descuento_1 / 100), 2)), 0) FROM Documentos_Lin d WHERE tipo = ? AND Numero_documento = ?),
                Valor_impuesto = (SELECT ISNULL(SUM(((d.Cantidad_Facturada * d.Valor_Unitario) * (1 - d.Porcentaje_Descuento_1 / 100)) * (d.Porcentaje_Impuesto / 100)), 0) FROM Documentos_Lin d WHERE tipo = ? AND Numero_documento = ?)
                WHERE tipo = ? AND Numero_Documento = ?";

            $paramsUpdate = [];
            for ($i = 0; $i < 5; $i++) {
                $paramsUpdate[] = $tipo;
                $paramsUpdate[] = $consecutivo;
            }
            $paramsUpdate[] = $tipo;
            $paramsUpdate[] = $consecutivo;

            $stmtU = sqlsrv_prepare($cn->getConecta(), $sqlUpdate, $paramsUpdate);
            sqlsrv_execute($stmtU);

            return json_encode([
                'status' => 'success',
                'message' => 'Línea actualizada desde el producto',
                'porcentajeImpuesto' => number_format($porcentajeImpuesto, 2, '.', '')
            ]);
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

            // Un documento guardado o anulado no se puede modificar. Esta ruta cambia
            // cantidad, valor, lote y descuento SIN alterar el número de líneas, así que
            // un cambio sobre un documento ya impreso no descuadra Total_Items y pasaba
            // inadvertido: el detalle simplemente dejaba de coincidir con lo impreso.
            // Mismo criterio que delete_id/delete_masivo.
            $sqlChk = "SELECT exportado, anulado FROM Documentos WHERE tipo = ? AND Numero_documento = ?";
            $stmtChk = sqlsrv_query($cn->getConecta(), $sqlChk, array($tipo, $consecutivo));
            $rowChk  = $stmtChk ? sqlsrv_fetch_array($stmtChk, SQLSRV_FETCH_ASSOC) : null;
            if (!$rowChk) {
                return ['status' => 'error', 'message' => 'Documento no encontrado'];
            }
            if (trim($rowChk['exportado']) === 'S' || trim($rowChk['anulado'] ?? 'N') === 'S') {
                AuditoriaDocumentos::registrar(array(
                    'modulo' => 'Documentos', 'operacion' => 'update_prod_doc', 'destructiva' => 1,
                    'tipo' => $tipo, 'numero' => $consecutivo,
                    'exportado_antes' => $rowChk['exportado'], 'anulado_antes' => $rowChk['anulado'] ?? 'N',
                    'resultado' => 'bloqueado',
                    'mensaje' => "Intento de modificar la línea seq=$seq producto=$producto de un documento guardado/anulado"
                ));
                return ['status' => 'error', 'message' => 'El documento ya está guardado o anulado, no se puede modificar'];
            }

            // Foto previa: un cambio de cantidad o de lote no se nota en ningún total,
            // así que sin esto no habría forma de saber qué decía la línea antes.
            $snapUpd = AuditoriaDocumentos::snapshotLineas($cn->getConecta(), $tipo, $consecutivo);
            
            error_log("🔄 Actualizando producto: tipo=$tipo, consecutivo=$consecutivo, producto=$producto, seq=$seq");
            
            // Construir la consulta UPDATE dinámicamente
            $updates = [];
            $params = [];
            
            // Solo incluir campos que fueron proporcionados
            $tolerance_warning = null;
            if ($cantidad !== null) {
                // Validar contra OS si aplica (antes de modificar)
                $error_os = $this->validar_cantidad_vs_os($tipo, $consecutivo, $producto, $seq, $cantidad);
                if ($error_os !== null) {
                    if (is_array($error_os) && !empty($error_os['tolerance'])) {
                        $tolerance_warning = $error_os['message'];
                    } else {
                        return ['status' => 'error', 'message' => $error_os];
                    }
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
                $params[] = $valor_unitario;

                // El costo real viene del catálogo del producto, no del precio de venta
                // que se está editando/agregando (antes se guardaban ambos igual).
                $sqlCostoProd = "SELECT costo_unitario FROM TblProducto WHERE IdProducto = ?";
                $stmtCostoProd = sqlsrv_query($cn->getConecta(), $sqlCostoProd, array((int)$producto));
                $rowCostoProd = $stmtCostoProd ? sqlsrv_fetch_array($stmtCostoProd, SQLSRV_FETCH_ASSOC) : null;
                if ($rowCostoProd) {
                    $updates[] = "Costo_Unitario = ?";
                    $updates[] = "Costo_Unitario_Inicial = ?";
                    $params[] = (float)$rowCostoProd['costo_unitario'];
                    $params[] = (float)$rowCostoProd['costo_unitario'];
                }
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

                // Se registra qué campos cambiaron y cuántas filas tocó: si toca más de
                // una, el WHERE no fue lo bastante específico y se modificó de más.
                $camposCambiados = array();
                if ($cantidad       !== null) $camposCambiados[] = "cantidad=$cantidad";
                if ($valor_unitario !== null) $camposCambiados[] = "valor=$valor_unitario";
                if ($lote           !== null) $camposCambiados[] = "lote=$lote";
                if ($fecha_vence    !== null) $camposCambiados[] = "vence=$fecha_vence";
                if ($descuento      !== null) $camposCambiados[] = "descuento=$descuento";
                if ($nota           !== null) $camposCambiados[] = "nota=$nota";
                if ($unidades       !== null) $camposCambiados[] = "unidades=$unidades";
                AuditoriaDocumentos::registrar(array(
                    'modulo' => 'Documentos', 'operacion' => 'update_prod_doc', 'destructiva' => 1,
                    'tipo' => $tipo, 'numero' => $consecutivo,
                    'exportado_antes' => $rowChk['exportado'], 'anulado_antes' => $rowChk['anulado'] ?? 'N',
                    'lineas_antes' => $snapUpd['lineas'], 'lineas_despues' => $snapUpd['lineas'],
                    'filas_afectadas' => $filas_afectadas,
                    'resultado' => 'ok',
                    'mensaje' => "seq=$seq producto=$producto | " . implode(' ', $camposCambiados)
                              . ($filas_afectadas > 1 ? " *** MODIFICÓ $filas_afectadas FILAS ***" : ''),
                    'detalle_antes' => $snapUpd['detalle']
                ));

                // Actualizar los totales del documento
                $this->actualizar_totales_documento($tipo, $consecutivo);
                if ($tolerance_warning !== null) {
                    return ['status' => 'warning', 'message' => $tolerance_warning];
                }
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
                costo = (SELECT ISNULL(SUM(dl.Cantidad_Facturada * dl.Costo_Unitario), 0) FROM Documentos_Lin dl WHERE dl.tipo = ? AND dl.Numero_documento = ?),
                descuento_1 = (SELECT ISNULL(SUM(ROUND((dl.Cantidad_Facturada * dl.Valor_Unitario) * (ISNULL(dl.Porcentaje_Descuento_1, 0) / 100), 2)), 0) FROM Documentos_Lin dl WHERE dl.tipo = ? AND dl.Numero_documento = ?),
                Valor_impuesto = (SELECT ISNULL(SUM(((dl.Cantidad_Facturada * dl.Valor_Unitario) * (1 - ISNULL(dl.Porcentaje_Descuento_1, 0) / 100)) * (ISNULL(dl.Porcentaje_Impuesto, 0) / 100)), 0) FROM Documentos_Lin dl WHERE dl.tipo = ? AND dl.Numero_documento = ?)
                WHERE tipo = ? AND Numero_Documento = ?";

            $params = array();
            // Repetir parámetros para cada subconsulta
            for ($i = 0; $i < 5; $i++) {
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

            // Obtener el Numero_Lote de la línea que se está editando
            $sql_lote = "SELECT Numero_Lote FROM Documentos_Lin
                         WHERE tipo = ? AND Numero_Documento = ? AND seq = ? AND IdProducto = ?";
            $stmt_lote = sqlsrv_query($cn->getConecta(), $sql_lote, array($tipo, $consecutivo, $seq, $producto));
            $numero_lote = '0';
            if ($stmt_lote) {
                $row_lote = sqlsrv_fetch_array($stmt_lote, SQLSRV_FETCH_ASSOC);
                if ($row_lote) $numero_lote = $row_lote['Numero_Lote'] ?? '0';
            }

            // Obtener cantidad ordenada en la OS para este producto, lote y línea específica
            $sql_os_qty = "SELECT cantidad FROM Documentos_Lin_Ped
                           WHERE numero_pedido = ? AND sw = '10' AND IdProducto = ? AND ISNULL(Numero_Lote, '0') = ? AND Linea = ?";
            $stmt_qty = sqlsrv_query($cn->getConecta(), $sql_os_qty, array($numero_os, $producto, $numero_lote, $seq));
            if (!$stmt_qty) return null;
            $row_qty = sqlsrv_fetch_array($stmt_qty, SQLSRV_FETCH_ASSOC);
            if (!$row_qty) return null;
            $cantidad_os = (float)$row_qty['cantidad'];

            // Sumar lo despachado en OTROS documentos (excluir el actual), filtrado por lote y línea.
            // Las devoluciones (Tipo_Docto_Base != '0') restan del total despachado.
            $sql_otros = "SELECT ISNULL(SUM(CASE WHEN d.Tipo_Docto_Base = '0' THEN dl.Cantidad_Facturada ELSE -dl.Cantidad_Facturada END), 0) AS total_otros
                          FROM Documentos d
                          JOIN Documentos_Lin dl ON dl.tipo = d.tipo AND dl.Numero_Documento = d.Numero_documento
                          WHERE d.Numero_Docto_Base_2 = ? AND d.Tipo_Docto_Base_2 = '10'
                          AND NOT (d.tipo = ? AND d.Numero_documento = ?)
                          AND dl.IdProducto = ?
                          AND dl.Numero_Lote = ?
                          AND dl.seq = ?";
            $stmt_otros = sqlsrv_query($cn->getConecta(), $sql_otros,
                                       array($numero_os, $tipo, $consecutivo, $producto, $numero_lote, $seq));
            if (!$stmt_otros) return null;
            $row_otros = sqlsrv_fetch_array($stmt_otros, SQLSRV_FETCH_ASSOC);
            $total_otros = (float)($row_otros['total_otros'] ?? 0);

            $pendiente_real = max(0, $cantidad_os - $total_otros);

            if ((float)$nueva_cantidad <= $pendiente_real) {
                return null;
            }

            // Supera el pendiente — verificar tolerancia por unidad KILO (idUnidad = 1)
            $sql_unidad = "SELECT IdUnidad FROM Documentos_Lin
                           WHERE tipo = ? AND Numero_Documento = ? AND IdProducto = ? AND seq = ?";
            $stmt_unidad = sqlsrv_query($cn->getConecta(), $sql_unidad,
                                        array($tipo, $consecutivo, $producto, $seq));
            if ($stmt_unidad) {
                $row_unidad = sqlsrv_fetch_array($stmt_unidad, SQLSRV_FETCH_ASSOC);
                if ($row_unidad && (int)$row_unidad['IdUnidad'] === 1) {
                    $tolerancia = 5;
                    if ((float)$nueva_cantidad <= ($pendiente_real + $tolerancia)) {
                        return [
                            'tolerance' => true,
                            'message'   => "Cantidad guardada con tolerancia de peso. Se ingresaron " .
                                           (float)$nueva_cantidad . " kg con un pendiente de " .
                                           $pendiente_real . " kg (tolerancia permitida: +" . $tolerancia . " kg)."
                        ];
                    }
                }
            }

            return "La cantidad ingresada (" . (float)$nueva_cantidad . ") supera la cantidad pendiente disponible (" . $pendiente_real . ") para este producto en la Orden de Salida.";
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

        // Trae la información de la Orden de Compra (sin crear nada) para mostrarla en la
        // modal de confirmación antes de que el usuario cree el documento de verdad.
        public function preview_doc_entrada($tipo, $numero) {
            $cn = new Conectarserver;

            $sql = "SELECT td.TipoDoctos, (c.siguiente + 1) AS proximoConsecutivo,
                           t.nombre AS proveedor, dir.direccion AS direccion, dp.telefono1 AS telefono,
                           dp.valor_total AS valorTotal, dp.notas AS notas
                    FROM Documentos_Ped dp, TblTerceros t, TblTipoDoctos td, Terceros_Dir dir, consecutivos c
                    WHERE c.tipo = ? AND td.idTipoDoctos = ? AND
                          dp.nit = t.nit_cedula AND dir.codigo_direccion = dp.direccion_factura AND dir.nit = dp.nit AND
                          dp.numero_pedido = ? AND dp.sw = '9'";
            $stmt = sqlsrv_query($cn->getConecta(), $sql, array($tipo, $tipo, $numero));
            if ($stmt === false) {
                return json_encode(['status' => 'error', 'message' => 'Error al consultar la orden de compra: ' . print_r(sqlsrv_errors(), true)]);
            }
            $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            if (!$row) {
                return json_encode(['status' => 'error', 'message' => "El número de pedido '$numero' no existe"]);
            }

            $totalItems = 0;
            $sqlItems = "SELECT COUNT(*) AS total FROM Documentos_Lin_Ped WHERE numero_pedido = ? AND sw = '9'";
            $stmtItems = sqlsrv_query($cn->getConecta(), $sqlItems, array($numero));
            if ($stmtItems !== false) {
                $rowItems = sqlsrv_fetch_array($stmtItems, SQLSRV_FETCH_ASSOC);
                if ($rowItems) $totalItems = (int)$rowItems['total'];
            }

            return json_encode([
                'status' => 'success',
                'data' => [
                    'tipoDoctos'   => trim($row['TipoDoctos'] ?? ''),
                    'consecutivo'  => $row['proximoConsecutivo'],
                    'proveedor'    => trim($row['proveedor'] ?? ''),
                    'direccion'    => trim($row['direccion'] ?? ''),
                    'telefono'     => trim($row['telefono'] ?? ''),
                    'valorTotal'   => (float)($row['valorTotal'] ?? 0),
                    'notas'        => trim($row['notas'] ?? ''),
                    'totalItems'   => $totalItems
                ]
            ]);
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

                // Reserva atómica del consecutivo (mismo patrón que insert_doc): el UPDATE
                // toma un lock de fila que dura hasta el COMMIT. Antes se leía (c.siguiente+1)
                // dentro de cada INSERT y solo al final se incrementaba Consecutivos; en esa
                // ventana los UPDATE que apuntaban a "(SELECT siguiente+1 FROM Consecutivos)"
                // podían caer sobre el documento de otro usuario.
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
                
                (SELECT td.tipo AS sw, '$tipo' AS tipo, '$tipo' AS modelo, $numDoc AS Numero_Documento, '' AS Numero_Docto_Base,
                dp.nit AS nit_Cedula, dp.direccion_factura AS codigo_direccion,  GETDATE() AS Fecha_Hora_Factura, GETDATE() AS Fecha_Hora_Vencimiento, GETDATE() AS Fecha_orden_Venta,
                t.condicion AS condicion, dp.valor_total AS valor_total, 0 AS valor_aplicado, dp.Retencion_1 AS Retencion_1, 0 AS Retencion_2, 0 AS Retencion_3,
                0 AS retencion_causada, 0 AS retencion_iva, 0 AS retencion_ica, 0 AS retencion_descuento, 0 AS descuento_pie, 0 AS DescuentoOrdenVenta, 0 AS descuento_1, 0 AS descuento_2,
                0 AS descuento_3, 0 AS costo, dp.vendedor AS idVendedor, 'N' AS anulado, '$usuario' AS usuario, SUBSTRING(dp.notas, 1, 250) AS notas, SUBSTRING(HOST_NAME(), 1, 20) AS pc, GETDATE() AS fecha_hora,
                0 AS duracion, td.IdBodega AS bodega, 0 AS Valor_impuesto, 0 AS Impuesto_Consumo, 0 AS impuesto_deporte, dp.concepto AS concepto, GETDATE() AS vencimiento_presup, 
                'N' AS exportado, ISNULL(td.Prefijo, '0') AS prefijo, dp.moneda AS moneda, 0 AS CentroDeCostosDoc, 0 AS valor_mercancia, 0 AS abono, 0 AS Comision_Vendedor, 
                1 AS Tasa_Moneda_Ext, '' AS Tomador, 'V' AS Tasa_Fija_o_Variable, dir.idLista AS Punto_FOB,
                0 AS Fletes_Moneda_Ext, 0 AS Miselaneos_Moneda_Ext, 0 AS Cargo_Por_Fletes, 0 AS Impuesto_Por_Fletes, 2 AS Total_Items, t.nombre AS Nombre_Cliente, 
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
                0 AS Cantidad_Pendiente, dp.cantidad AS Cantidad_Orden, p.costo_unitario AS Costo_Unitario, dp.valor_unitario AS Valor_Unitario,
                ((dp.porcentaje_iva/100) * dp.valor_unitario) AS Valor_Impuesto, dp.porcentaje_iva AS Porcentaje_Impuesto, dp.porcentaje_descuento AS Porcentaje_Descuento_1,
                dp.porc_dcto_2 AS Porcentaje_Descuento_2, dp.porc_dcto_3 AS Porcentaje_Descuento_3, dp.IdVendedor AS IdVendedor, 0 AS Comision_Vendedor, 0 AS Valor_Comision_Vendedor,
                td.IdBodega AS IdBodega, 'S' AS Maneja_Inventario, '' AS Tomador, 1 AS IdMoneda, 1 AS Tasa_Moneda_Ext, '0' AS CentroDeCostosDoc,
                ' ' AS Nota_Linea, '1' AS Unidades, GETDATE() AS Fecha_Vence, 'N' AS Exportado, p.costo_unitario AS Costo_Unitario_Inicial,
                dp.Porcentaje_ReteFuente AS Porcentaje_ReteFuente, 0 AS Envase, 0 AS Numero_Lote_Destino, '' AS serial, 0 AS Impuesto_Consumo, 0 AS Porcentaje_ReteFuente_2,
                0 AS Porcentaje_ReteFuente_3, 0 AS Porcentaje_ReteFuente_4, 0 AS Emp_1, 0 AS Emp_2, 0 AS Emp_3, 0 AS Emp_4, 0 AS Emp_5, 0 AS Emp_6,
                0 AS Emp_7, 0 AS Emp_8, 0 AS Tara_1, 0 AS Tara_2, 0 AS Tara_3, 0 AS Tara_4, 0 AS Tara_5, 0 AS Tara_6, 0 AS Tara_7, 0 AS Tara_8
                                                                
                FROM  Documentos_Lin_Ped dp, TblTipoDoctos td, TblProducto p
                                        
                WHERE td.idTipoDoctos = '$tipo' AND p.IdProducto = dp.IdProducto
                AND dp.numero_pedido = '$numero' AND dp.sw = 9)";
               
                $registros =  sqlsrv_prepare($cn->getConecta(), $sql1);
                if(sqlsrv_execute($registros) === false) {
                    throw new Exception("Error al insertar detalle del documento: " . print_r(sqlsrv_errors(), true));
                }

                // Recalcular totales en cabecera como suma real del detalle (antes Total_Items
                // quedaba fijo en 2 y costo fijo en 0, sin importar las líneas insertadas).
                // Se usa $numDoc, el consecutivo reservado al inicio de la transacción.
                $sql_tot = "UPDATE Documentos SET
                    Total_Items = (SELECT COUNT(*) FROM Documentos_Lin WHERE tipo = '$tipo' AND Numero_documento = $numDoc),
                    costo = (SELECT ISNULL(SUM(dl.Cantidad_Facturada * dl.Costo_Unitario), 0) FROM Documentos_Lin dl WHERE dl.tipo = '$tipo' AND dl.Numero_documento = $numDoc)
                    WHERE tipo = '$tipo' AND Numero_Documento = $numDoc";
                $stmt_tot = sqlsrv_prepare($cn->getConecta(), $sql_tot);
                if (sqlsrv_execute($stmt_tot) === false) {
                    throw new Exception("Error al actualizar totales: " . print_r(sqlsrv_errors(), true));
                }

                sqlsrv_commit($cn->getConecta()); // Confirmar la transacción si todo ha ido bien

                // Devolvemos un objeto JSON con un status de éxito
                return json_encode(array(
                    "status" => "success",
                    "message" => "Documento registrado correctamente",
                    "tipo" => $tipo,
                    "consecutivo" => $numDoc
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

                // Reserva atómica del consecutivo (mismo patrón que insert_doc): el UPDATE
                // toma un lock de fila que dura hasta el COMMIT. Antes se leía (c.siguiente+1)
                // dentro de cada INSERT y solo al final se incrementaba Consecutivos; en esa
                // ventana los UPDATE que apuntaban a "(SELECT siguiente+1 FROM Consecutivos)"
                // podían caer sobre el documento de otro usuario.
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
                0 AS costo, d.IdVendedor AS IdVendedor, 'N' AS anulado, '$usuario' AS usuario,
                SUBSTRING(d.notas, 1, 250) AS notas, SUBSTRING(HOST_NAME(), 1, 20) AS pc, GETDATE() AS fecha_hora, 0 AS duracion, td.IdBodega AS bodega, 0 AS Valor_impuesto, 0 AS Impuesto_Consumo,
                0 AS impuesto_deporte, d.concepto AS concepto, GETDATE() AS vencimiento_presup, 
                'N' AS exportado, '0' AS prefijo, d.moneda AS moneda, 0 AS CentroDeCostosDoc, 0 AS valor_mercancia, 0 AS abono, 0 AS Comision_Vendedor, 
                1 AS Tasa_Moneda_Ext, '' AS Tomador, 'V' AS Tasa_Fija_o_Variable, d.Punto_FOB AS Punto_FOB,
                0 AS Fletes_Moneda_Ext, 0 AS Miselaneos_Moneda_Ext, 0 AS Cargo_Por_Fletes, 0 AS Impuesto_Por_Fletes, d.Total_Items AS Total_Items, d.Nombre_Cliente AS Nombre_Cliente, 
                SUBSTRING(d.Ordenado_Por,0,20) AS Ordenado_Por, d.Telefono_De_Envio_1 AS Telefono_De_Envio_1, d.Telefono_De_Envio_2 AS Telefono_De_Envio_2, 'N' AS Factura_Impresa, d.IdFormaEnvio AS IdFormaEnvio, d.IdTRansportador AS IdTransportador, 
                d.nit_Cedula_2 AS nit_Cedula_2, d.codigo_direccion_2 AS codigo_direccion_2, d.Numero_Docto_Base_2 AS Numero_Docto_Base_2, '$tiporef' AS Tipo_Docto_Base, 
                '' AS Tipo_Docto_Base_2, d.IdActividadEconomica AS IdActividadEconomica, d.TarifaReteFuenteCree AS TarifaReteFuenteCree, d.Valor_ReteCree AS Valor_ReteCree, d.IdVehiculo AS IdVehiculo

                FROM Documentos d, TblTipoDoctos td
                WHERE d.Numero_documento = '$numero' AND d.tipo = '$tiporef' AND td.idTipoDoctos = '$tipo')";
                
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
                
                (SELECT td.tipo AS sw, '$tipo' AS tipo, dl.seq AS seq,  p.contable AS Modelo,  $numDoc AS Numero_Documento,
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
                                                                    
                FROM  Documentos_Lin dl, Documentos d, TblTerceros t, TblTipoDoctos td, TblProducto p, TblRetencion r
                                            
                WHERE dl.Numero_documento = '$numero' AND dl.tipo = '$tiporef'
                AND td.idTipoDoctos = c.tipo AND d.Numero_documento=dl.Numero_Documento AND d.tipo = dl.tipo
                AND dl.Nit_Cedula=t.nit_cedula 
                AND p.IdProducto = dl.IdProducto
                AND p.Retencion=r.IdRetencion)";
                
                $registros =  sqlsrv_prepare($cn->getConecta(), $sql1);            
                if(sqlsrv_execute($registros) === false) {
                    throw new Exception("Error al insertar detalle del documento: " . print_r(sqlsrv_errors(), true));
                }

                sqlsrv_commit($cn->getConecta()); // Confirmar la transacción si todo ha ido bien

                // Devolvemos un objeto JSON con un status de éxito
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
                
                // Registramos el error en un log
                $this->registrar_error("Error en insert_entrada_traslado: " . $e->getMessage());
                
                // Devolvemos un objeto JSON con un status de error
                return json_encode(array(
                    "status" => "error",
                    "message" => $e->getMessage()
                ));
            }

        }

        public function insert_doc_entrada_manual($tipo, $nit, $dir, $nit2, $dir2, $usuario) {
            $cn = new Conectarserver;
            try {
                sqlsrv_begin_transaction($cn->getConecta());

                // Reserva atómica del consecutivo (mismo patrón que insert_doc): el UPDATE
                // toma un lock de fila que dura hasta el COMMIT. Antes se leía (c.siguiente+1)
                // dentro de cada INSERT y solo al final se incrementaba Consecutivos; en esa
                // ventana los UPDATE que apuntaban a "(SELECT siguiente+1 FROM Consecutivos)"
                // podían caer sobre el documento de otro usuario.
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

                $sql = "INSERT INTO Documentos(sw, tipo, modelo, Numero_Documento, Numero_Docto_Base,
                nit_Cedula, codigo_direccion, Fecha_Hora_Factura, Fecha_Hora_Vencimiento, Fecha_orden_Venta,
                condicion, valor_total, valor_aplicado, Retencion_1, Retencion_2, Retencion_3, retencion_causada, retencion_iva, retencion_ica,
                retencion_descuento, descuento_pie, DescuentoOrdenVenta, descuento_1, descuento_2, descuento_3, costo, IdVendedor, anulado, usuario,
                notas, pc, fecha_hora, duracion, bodega, Valor_impuesto, Impuesto_Consumo, impuesto_deporte, concepto, vencimiento_presup,
                exportado, prefijo, moneda, CentroDeCostosDoc, valor_mercancia, abono, Comision_Vendedor, Tasa_Moneda_Ext, Tomador, Tasa_Fija_o_Variable, Punto_FOB,
                Fletes_Moneda_Ext, Miselaneos_Moneda_ext, Cargo_Por_Fletes, Impuesto_Por_Fletes, Total_Items, Nombre_Cliente, Ordenado_Por, Telefono_De_Envio_1,
                Telefono_De_Envio_2, Factura_Impresa, IdFormaEnvio, IdTransportador, nit_Cedula_2, codigo_direccion_2, Numero_Docto_Base_2, Tipo_Docto_Base,
                Tipo_Docto_Base_2, IdActividadEconomica, TarifaReteFuenteCree, Valor_ReteCree, IdVehiculo)

                (SELECT td.tipo AS sw, '$tipo' AS tipo, '$tipo' AS modelo, $numDoc AS Numero_Documento, '' AS Numero_Docto_Base,
                '$nit' AS nit_Cedula, '$dir' AS codigo_direccion, GETDATE() AS Fecha_Hora_Factura, GETDATE() AS Fecha_Hora_Vencimiento, GETDATE() AS Fecha_orden_Venta,
                0 AS condicion, 0 AS valor_total, 0 AS valor_aplicado, 0 AS Retencion_1, 0 AS Retencion_2, 0 AS Retencion_3,
                0 AS retencion_causada, 0 AS retencion_iva, 0 AS retencion_ica, 0 AS retencion_descuento, 0 AS descuento_pie, 0 AS DescuentoOrdenVenta,
                0 AS descuento_1, 0 AS descuento_2, 0 AS descuento_3, 0 AS costo, 0 AS IdVendedor, 'N' AS anulado, '$usuario' AS usuario,
                '' AS notas, SUBSTRING(HOST_NAME(), 1, 20) AS pc, GETDATE() AS fecha_hora, 0 AS duracion, td.IdBodega AS bodega,
                0 AS Valor_impuesto, 0 AS Impuesto_Consumo, 0 AS impuesto_deporte, '' AS concepto, GETDATE() AS vencimiento_presup,
                'N' AS exportado, '0' AS prefijo, 1 AS moneda, 0 AS CentroDeCostosDoc, 0 AS valor_mercancia, 0 AS abono, 0 AS Comision_Vendedor,
                1 AS Tasa_Moneda_Ext, '' AS Tomador, 'V' AS Tasa_Fija_o_Variable, 0 AS Punto_FOB,
                0 AS Fletes_Moneda_Ext, 0 AS Miselaneos_Moneda_ext, 0 AS Cargo_Por_Fletes, 0 AS Impuesto_Por_Fletes, 0 AS Total_Items,
                t.nombre AS Nombre_Cliente, '' AS Ordenado_Por, '' AS Telefono_De_Envio_1, '' AS Telefono_De_Envio_2, 'N' AS Factura_Impresa,
                0 AS IdFormaEnvio, 1 AS IdTransportador,
                '$nit2' AS nit_Cedula_2, '$dir2' AS codigo_direccion_2, '' AS Numero_Docto_Base_2, '0' AS Tipo_Docto_Base,
                '9' AS Tipo_Docto_Base_2, '0' AS IdActividadEconomica, 0 AS TarifaReteFuenteCree, 0 AS Valor_ReteCree, '1' AS IdVehiculo

                FROM TblTipoDoctos td, TblTerceros t
                WHERE td.idTipoDoctos = '$tipo' AND t.nit_cedula = '$nit')";

                $registros = sqlsrv_prepare($cn->getConecta(), $sql);
                if (sqlsrv_execute($registros) === false) {
                    throw new Exception("Error al insertar documento manual: " . print_r(sqlsrv_errors(), true));
                }

                sqlsrv_commit($cn->getConecta());

                return json_encode(array(
                    "status" => "success",
                    "message" => "Documento Entrada Frigopork manual registrado correctamente",
                    "tipo" => $tipo,
                    "consecutivo" => $numDoc
                ));

            } catch (Exception $e) {
                if (isset($cn) && $cn->getConecta()) {
                    sqlsrv_rollback($cn->getConecta());
                }
                $this->registrar_error("Error en insert_doc_entrada_manual: " . $e->getMessage());
                return json_encode(array(
                    "status" => "error",
                    "message" => $e->getMessage()
                ));
            }
        }

        public function insert_linea_entrada_manual($tipo, $consecutivo, $idproducto, $cantidad, $valorUnitario = 0, $lote = '0', $porcentaje_impuesto = 0) {
            $cn = new Conectarserver;
            try {
                $valorUnitario       = floatval($valorUnitario);
                $porcentaje_impuesto = floatval($porcentaje_impuesto);
                $lote                = ($lote !== '' && $lote !== null) ? addslashes($lote) : '0';
                $valorImpuesto       = round($valorUnitario * $cantidad * ($porcentaje_impuesto / 100), 2);

                // Un documento guardado o anulado no admite líneas nuevas (mismo criterio
                // que insert_detalle/duplicar_linea; esta ruta tampoco lo validaba).
                $sqlChk = "SELECT exportado, anulado FROM Documentos WHERE tipo = ? AND Numero_documento = ?";
                $stmtChk = sqlsrv_query($cn->getConecta(), $sqlChk, array($tipo, $consecutivo));
                $rowChk  = $stmtChk ? sqlsrv_fetch_array($stmtChk, SQLSRV_FETCH_ASSOC) : null;
                if (!$rowChk) {
                    return json_encode(["status" => "error", "message" => "Documento no encontrado"]);
                }
                if (trim($rowChk['exportado']) === 'S' || trim($rowChk['anulado'] ?? 'N') === 'S') {
                    AuditoriaDocumentos::registrar(array(
                        'modulo' => 'Entradas', 'operacion' => 'insert_linea_entrada_manual',
                        'tipo' => $tipo, 'numero' => $consecutivo,
                        'exportado_antes' => $rowChk['exportado'], 'anulado_antes' => $rowChk['anulado'] ?? 'N',
                        'resultado' => 'bloqueado',
                        'mensaje' => "Intento de agregar producto=$idproducto a un documento guardado/anulado"
                    ));
                    return json_encode(["status" => "error", "message" => "El documento ya está guardado o anulado, no se puede modificar"]);
                }

                $sql_prod = "SELECT COUNT(*) AS existe FROM TblProducto WHERE IdProducto = ?";
                $stmt = sqlsrv_query($cn->getConecta(), $sql_prod, [$idproducto]);
                $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
                if (!$row || $row['existe'] == 0) {
                    return json_encode(["status" => "error", "message" => "El producto '$idproducto' no existe"]);
                }

                AuditoriaDocumentos::registrar(array(
                    'modulo' => 'Entradas', 'operacion' => 'insert_linea_entrada_manual',
                    'tipo' => $tipo, 'numero' => $consecutivo,
                    'exportado_antes' => $rowChk['exportado'], 'anulado_antes' => $rowChk['anulado'] ?? 'N',
                    'lineas_antes' => AuditoriaDocumentos::contarLineas($cn->getConecta(), $tipo, $consecutivo),
                    'resultado' => 'ok',
                    'mensaje' => "producto=$idproducto cantidad=$cantidad valor=$valorUnitario lote=$lote"
                ));

                $sql = "INSERT INTO Documentos_Lin (sw, tipo, seq, modelo, Numero_Documento, Numero_Docto_Base, Numero_Lote,
                Nit_Cedula, Codigo_Direccion, Fecha_Documento, IdProducto, IdUnidad, Factor_Conversion,
                Cantidad_Facturada, Cantidad_Pendiente, Cantidad_Orden, Costo_Unitario, Valor_Unitario,
                Valor_Impuesto, Porcentaje_Impuesto, Porcentaje_Descuento_1, Porcentaje_Descuento_2,
                Porcentaje_Descuento_3, IdVendedor, Comision_Vendedor, Valor_Comision_Vendedor, IdBodega,
                Maneja_Inventario, Tomador, IdMoneda, Tasa_Moneda_Ext, CentroDeCostosDoc, Nota_Linea,
                Unidades, Fecha_Vence, Exportado, Costo_Unitario_Inicial, Porcentaje_ReteFuente, Envase,
                Numero_Lote_Destino, serial, Impuesto_Consumo, Porcentaje_ReteFuente_2, Porcentaje_ReteFuente_3,
                Porcentaje_ReteFuente_4, Emp_1, Emp_2, Emp_3, Emp_4, Emp_5, Emp_6, Emp_7, Emp_8,
                Tara_1, Tara_2, Tara_3, Tara_4, Tara_5, Tara_6, Tara_7, Tara_8)

                SELECT '99' AS sw, '$tipo' AS tipo,
                (SELECT ISNULL(MAX(seq), 0) + 1 FROM Documentos_Lin WHERE tipo = '$tipo' AND Numero_Documento = $consecutivo) AS seq,
                p.contable AS modelo, $consecutivo AS Numero_Documento, 0 AS Numero_Docto_Base, '$lote' AS Numero_Lote,
                d.nit_Cedula AS Nit_Cedula, d.codigo_direccion AS Codigo_Direccion, GETDATE() AS Fecha_Documento,
                '$idproducto' AS IdProducto, p.unidad_Inventario AS IdUnidad, '1' AS Factor_Conversion,
                $cantidad AS Cantidad_Facturada, ($cantidad * -1) AS Cantidad_Pendiente, 0 AS Cantidad_Orden,
                $valorUnitario AS Costo_Unitario, $valorUnitario AS Valor_Unitario,
                $valorImpuesto AS Valor_Impuesto, $porcentaje_impuesto AS Porcentaje_Impuesto,
                0 AS Porcentaje_Descuento_1, 0 AS Porcentaje_Descuento_2, 0 AS Porcentaje_Descuento_3,
                1 AS IdVendedor, 0 AS Comision_Vendedor, 0 AS Valor_Comision_Vendedor,
                do.IdBodega AS IdBodega, 'S' AS Maneja_Inventario, '' AS Tomador,
                1 AS IdMoneda, 1 AS Tasa_Moneda_Ext, '0' AS CentroDeCostosDoc, ' ' AS Nota_Linea,
                '1' AS Unidades, '2000-01-01 00:00:00.000' AS Fecha_Vence, 'N' AS Exportado,
                $valorUnitario AS Costo_Unitario_Inicial, 0 AS Porcentaje_ReteFuente,
                0 AS Envase, 0 AS Numero_Lote_Destino, '' AS serial, 0 AS Impuesto_Consumo,
                0 AS Porcentaje_ReteFuente_2, 0 AS Porcentaje_ReteFuente_3, 0 AS Porcentaje_ReteFuente_4,
                0 AS Emp_1, 0 AS Emp_2, 0 AS Emp_3, 0 AS Emp_4, 0 AS Emp_5, 0 AS Emp_6,
                0 AS Emp_7, 0 AS Emp_8, 0 AS Tara_1, 0 AS Tara_2, 0 AS Tara_3, 0 AS Tara_4,
                0 AS Tara_5, 0 AS Tara_6, 0 AS Tara_7, 0 AS Tara_8
                FROM Documentos d, TblProducto p, TblTipoDoctos do
                WHERE d.tipo = '$tipo' AND d.Numero_Documento = $consecutivo
                  AND p.IdProducto = '$idproducto' AND do.idTipoDoctos = '$tipo'";

                $stmt2 = sqlsrv_prepare($cn->getConecta(), $sql);
                if (sqlsrv_execute($stmt2) === false) {
                    throw new Exception("Error al insertar línea: " . print_r(sqlsrv_errors(), true));
                }

                return json_encode(["status" => "success", "message" => "Producto agregado correctamente"]);

            } catch (Exception $e) {
                $this->registrar_error("Error en insert_linea_entrada_manual: " . $e->getMessage());
                return json_encode(["status" => "error", "message" => $e->getMessage()]);
            }
        }

        public function update_lote($tipo, $numdoc, $id, $lote){

            $cn = new Conectarserver;
            $id = $_POST['id'] ?? [];
            if (!is_array($id) || count($id) === 0) {
                echo "No se seleccionó ningún producto \n";
                return;
            }

            // Un documento guardado o anulado no se puede modificar. Además este UPDATE
            // filtra por IdProducto SIN seq: si el mismo producto está en varias líneas,
            // les cambia el lote a todas de una vez.
            $sqlChk = "SELECT exportado, anulado FROM Documentos WHERE tipo = ? AND Numero_documento = ?";
            $stmtChk = sqlsrv_query($cn->getConecta(), $sqlChk, array($tipo, $numdoc));
            $rowChk  = $stmtChk ? sqlsrv_fetch_array($stmtChk, SQLSRV_FETCH_ASSOC) : null;
            if (!$rowChk) {
                echo "Documento no encontrado \n";
                return;
            }
            if (trim($rowChk['exportado']) === 'S' || trim($rowChk['anulado'] ?? 'N') === 'S') {
                AuditoriaDocumentos::registrar(array(
                    'modulo' => 'Documentos', 'operacion' => 'update_lote', 'destructiva' => 1,
                    'tipo' => $tipo, 'numero' => $numdoc,
                    'exportado_antes' => $rowChk['exportado'], 'anulado_antes' => $rowChk['anulado'] ?? 'N',
                    'resultado' => 'bloqueado',
                    'mensaje' => "Intento de cambiar el lote a '$lote' en un documento guardado/anulado"
                ));
                echo "El documento ya está guardado o anulado, no se puede modificar \n";
                return;
            }

            $snapLote = AuditoriaDocumentos::snapshotLineas($cn->getConecta(), $tipo, $numdoc);
            AuditoriaDocumentos::registrar(array(
                'modulo' => 'Documentos', 'operacion' => 'update_lote', 'destructiva' => 1,
                'tipo' => $tipo, 'numero' => $numdoc,
                'exportado_antes' => $rowChk['exportado'], 'anulado_antes' => $rowChk['anulado'] ?? 'N',
                'lineas_antes' => $snapLote['lineas'], 'lineas_despues' => $snapLote['lineas'],
                'resultado' => 'ok',
                'mensaje' => "Lote -> '$lote' en productos: " . implode(',', $id),
                'detalle_antes' => $snapLote['detalle']
            ));
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

        // Lista documentos para el módulo Utilidades (edición manual de Numero_Docto_Base),
        // filtrado opcionalmente por tipo de documento y fecha exacta.
        public function listar_utilidades_doc_ref($idTipo, $fecha1){
            $cn = new Conectarserver;
            $resultado = array();

            $where = "d.tipo = t.idTipoDoctos AND c.nit_cedula = d.nit_Cedula";
            $params = array();

            if ($idTipo !== '') {
                $where .= " AND d.tipo = ?";
                $params[] = $idTipo;
            }
            if ($fecha1 !== '') {
                $where .= " AND CONVERT(date, d.Fecha_Hora_Factura) = ?";
                $params[] = $fecha1;
            }

            $sql = "SELECT d.tipo, t.TipoDoctos, d.Numero_documento, d.Numero_Docto_Base, d.notas, d.usuario, d.Fecha_Hora_Factura, d.nit_Cedula, c.nombre
                    FROM Documentos d, TblTipoDoctos t, TblTerceros c
                    WHERE $where
                    ORDER BY d.Numero_documento DESC";

            $stmt = sqlsrv_query($cn->getConecta(), $sql, $params);
            if ($stmt === false) {
                $this->registrar_error("Error en listar_utilidades_doc_ref: " . print_r(sqlsrv_errors(), true));
                return $resultado;
            }

            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $resultado[] = $row;
            }

            return $resultado;
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

                // Cambia la fecha de la cabecera y la Fecha_Documento de TODAS las líneas,
                // sin mirar si el documento ya está guardado. Se registra por documento
                // para poder reconstruir qué se movió y cuándo.
                $estadoFecha = AuditoriaDocumentos::estadoDocumento($cn->getConecta(), $tipo, $numdoc);
                AuditoriaDocumentos::registrar(array(
                    'modulo' => 'Utilidades', 'operacion' => 'update_fecha', 'destructiva' => 1,
                    'tipo' => $tipo, 'numero' => $numdoc,
                    'exportado_antes' => $estadoFecha['exportado'], 'anulado_antes' => $estadoFecha['anulado'],
                    'lineas_antes' => AuditoriaDocumentos::contarLineas($cn->getConecta(), $tipo, $numdoc),
                    'resultado' => 'ok',
                    'mensaje' => "Fecha del documento y de todas sus líneas -> $fecha_datetime"
                ));

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

            $sqlChkLin = "SELECT COUNT(*) AS total FROM Documentos_Lin WHERE tipo = ? AND Numero_documento = ?";
            $stmtChkLin = sqlsrv_query($cn->getConecta(), $sqlChkLin, array($tipo, $numdoc));
            $rowChkLin = $stmtChkLin ? sqlsrv_fetch_array($stmtChkLin, SQLSRV_FETCH_ASSOC) : null;
            if (!$rowChkLin || (int)$rowChkLin['total'] === 0) {
                echo "No se puede guardar: el documento debe tener al menos un producto \n";
                return;
            }

            if(empty($remision)){
                $sql="UPDATE Documentos SET nit_Cedula_2 = '$nit', codigo_direccion_2 = '$direccion', Numero_Docto_Base = '$traslfact', notas = '$notas', exportado = 'S', IdTransportador = '$idTransportador', IdVehiculo = '$idVehiculo',
                Total_Items = (SELECT COUNT(*) FROM Documentos_Lin WHERE tipo = $tipo AND Numero_documento = $numdoc),
                valor_total = (SELECT SUM(ROUND((d.Cantidad_Facturada * d.Valor_Unitario) * (1 - d.Porcentaje_Descuento_1 / 100), 2) + ((d.Cantidad_Facturada * d.Valor_Unitario) * (1 - d.Porcentaje_Descuento_1 / 100)) * (d.Porcentaje_Impuesto / 100)) 
                FROM Documentos_Lin d WHERE tipo = $tipo AND Numero_documento = $numdoc),
                costo = (SELECT ISNULL(SUM(d.Cantidad_Facturada * d.Costo_Unitario), 0) FROM Documentos_Lin d WHERE tipo = $tipo AND Numero_documento = $numdoc),
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
                costo = (SELECT ISNULL(SUM(d.Cantidad_Facturada * d.Costo_Unitario), 0) FROM Documentos_Lin d WHERE tipo = $tipo AND Numero_documento = $numdoc),
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

            // La factura/traslado se guarda SOLO en la cabecera (Documentos.Numero_Docto_Base, varchar).
            // En Documentos_Lin.Numero_Docto_Base (int) NO se propaga: no corresponde a nivel de línea
            // y las facturas alfanuméricas (p. ej. 'FE4720') rompían ese UPDATE con error de conversión.

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

                // Un documento guardado o anulado no admite líneas nuevas (mismo criterio
                // que insert_detalle/delete_id; esta ruta tampoco lo validaba).
                $sqlChk = "SELECT exportado, anulado FROM Documentos WHERE tipo = ? AND Numero_documento = ?";
                $stmtChk = sqlsrv_query($conexion, $sqlChk, array($tipo, $consecutivo));
                $rowChk  = $stmtChk ? sqlsrv_fetch_array($stmtChk, SQLSRV_FETCH_ASSOC) : null;
                if (!$rowChk) {
                    return false;
                }
                if (trim($rowChk['exportado']) === 'S' || trim($rowChk['anulado'] ?? 'N') === 'S') {
                    AuditoriaDocumentos::registrar(array(
                        'modulo' => 'Documentos', 'operacion' => 'duplicar_linea', 'destructiva' => 1,
                        'tipo' => $tipo, 'numero' => $consecutivo,
                        'exportado_antes' => $rowChk['exportado'], 'anulado_antes' => $rowChk['anulado'] ?? 'N',
                        'resultado' => 'bloqueado',
                        'mensaje' => "Intento de duplicar la línea seq=$seq producto=$producto de un documento guardado/anulado"
                    ));
                    return false;
                }

                // El seq se reserva bajo UPDLOCK/HOLDLOCK para que dos duplicados
                // simultáneos no obtengan el mismo número de línea.
                $sql_seq = "SELECT ISNULL(MAX(CAST(seq AS INT)), 0) + 1 as next_seq
                        FROM Documentos_Lin WITH (UPDLOCK, HOLDLOCK)
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

                AuditoriaDocumentos::registrar(array(
                    'modulo' => 'Documentos', 'operacion' => 'duplicar_linea',
                    'tipo' => $tipo, 'numero' => $consecutivo,
                    'exportado_antes' => $rowChk['exportado'], 'anulado_antes' => $rowChk['anulado'] ?? 'N',
                    'lineas_antes' => $next_seq - 1,
                    'lineas_despues' => AuditoriaDocumentos::contarLineas($conexion, $tipo, $consecutivo),
                    'filas_afectadas' => $filas_afectadas,
                    'resultado' => 'ok',
                    'mensaje' => "Duplicada seq=$seq producto=$producto -> nuevo seq=$next_seq"
                ));
                
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

        public function reiniciar_doc_desde_pedido($tipo, $numdoc, $confirmado = false) {
            return $this->reiniciar_doc_entrada($tipo, $numdoc, $confirmado);
        }

        public function reiniciar_doc_entrada($tipo, $numdoc, $confirmado = false) {
            $cn = new Conectarserver;
            try {
                sqlsrv_begin_transaction($cn->getConecta());

                $sql_check = "SELECT Numero_Docto_Base, Tipo_Docto_Base, Numero_Docto_Base_2, Tipo_Docto_Base_2, exportado
                              FROM Documentos WHERE tipo = ? AND Numero_Documento = ?";
                $stmt_check = sqlsrv_query($cn->getConecta(), $sql_check, array($tipo, (int)$numdoc));
                if ($stmt_check === false) throw new Exception("Error al verificar el documento.");
                $row_check = sqlsrv_fetch_array($stmt_check, SQLSRV_FETCH_ASSOC);
                if (!$row_check) throw new Exception("Documento no encontrado.");
                if ($row_check['exportado'] === 'S') throw new Exception("El documento ya fue exportado y no puede reiniciarse.");

                // Un documento que estuvo exportado y volvió a editable con "Desmarcar" ya
                // pudo haberse impreso: reiniciarlo regenera el detalle desde el pedido con
                // las cantidades pendientes de HOY, que no son las que se imprimieron.
                // Se exige una confirmación explícita adicional antes de destruirlo.
                $desmarcado = AuditoriaDocumentos::fueDesmarcado($tipo, (int)$numdoc);
                if ($desmarcado && !$confirmado) {
                    sqlsrv_rollback($cn->getConecta());
                    AuditoriaDocumentos::registrar(array(
                        'modulo' => 'Entradas', 'operacion' => 'reiniciar_doc_entrada', 'destructiva' => 1,
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

                $tipo_base_2  = trim($row_check['Tipo_Docto_Base_2'] ?? '');
                $numero_base_2 = trim($row_check['Numero_Docto_Base_2'] ?? '');
                $tipo_base    = trim($row_check['Tipo_Docto_Base'] ?? '');
                $numero_base  = trim($row_check['Numero_Docto_Base'] ?? '');

                $sql_del = "DELETE FROM Documentos_Lin WHERE tipo = ? AND Numero_Documento = ?";
                $stmt_del = sqlsrv_query($cn->getConecta(), $sql_del, array($tipo, (int)$numdoc));
                if ($stmt_del === false) throw new Exception("Error al eliminar líneas: " . print_r(sqlsrv_errors(), true));

                if ($tipo_base_2 === '9' && $numero_base_2 !== '') {
                    // Desde pedido (Documentos_Lin_Ped)
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
                    '' AS Numero_Docto_Base, '0' AS Numero_Lote, dp.IdCliente AS Nit_Cedula, dp.DireccionFactura AS codigo_direccion, GETDATE() AS Fecha_Documento,
                    dp.IdProducto AS IdProducto, dp.und AS IdUnidad, '1' AS Factor_Conversion, dp.cantidad AS Cantidad_Facturada,
                    0 AS Cantidad_Pendiente, dp.cantidad AS Cantidad_Orden, p.costo_unitario AS Costo_Unitario, dp.valor_unitario AS Valor_Unitario,
                    ((dp.porcentaje_iva/100) * dp.valor_unitario) AS Valor_Impuesto, dp.porcentaje_iva AS Porcentaje_Impuesto,
                    dp.porcentaje_descuento AS Porcentaje_Descuento_1, dp.porc_dcto_2 AS Porcentaje_Descuento_2,
                    dp.porc_dcto_3 AS Porcentaje_Descuento_3, dp.IdVendedor AS IdVendedor, 0 AS Comision_Vendedor, 0 AS Valor_Comision_Vendedor,
                    td.IdBodega AS IdBodega, 'S' AS Maneja_Inventario, '' AS Tomador, 1 AS IdMoneda, 1 AS Tasa_Moneda_Ext, '0' AS CentroDeCostosDoc,
                    ' ' AS Nota_Linea, '1' AS Unidades, GETDATE() AS Fecha_Vence, 'N' AS Exportado, p.costo_unitario AS Costo_Unitario_Inicial,
                    dp.Porcentaje_ReteFuente AS Porcentaje_ReteFuente, 0 AS Envase, 0 AS Numero_Lote_Destino, '' AS serial, 0 AS Impuesto_Consumo,
                    0 AS Porcentaje_ReteFuente_2, 0 AS Porcentaje_ReteFuente_3, 0 AS Porcentaje_ReteFuente_4,
                    0 AS Emp_1, 0 AS Emp_2, 0 AS Emp_3, 0 AS Emp_4, 0 AS Emp_5, 0 AS Emp_6,
                    0 AS Emp_7, 0 AS Emp_8, 0 AS Tara_1, 0 AS Tara_2, 0 AS Tara_3, 0 AS Tara_4, 0 AS Tara_5, 0 AS Tara_6, 0 AS Tara_7, 0 AS Tara_8
                    FROM Documentos_Lin_Ped dp
                    JOIN TblTipoDoctos td ON td.idTipoDoctos = '$tipo'
                    JOIN TblProducto p ON p.IdProducto = dp.IdProducto
                    WHERE dp.numero_pedido = '$numero_base_2' AND dp.sw = '9')";

                } elseif ($tipo_base !== '' && $tipo_base !== '0' && $numero_base !== '') {
                    // Desde traslado (Documentos_Lin del documento original)
                    $sql_lin = "INSERT INTO Documentos_Lin
                    (sw, tipo, seq, modelo, Numero_Documento, Numero_Docto_Base, Numero_Lote, Nit_Cedula, Codigo_Direccion, Fecha_Documento,
                    IdProducto, IdUnidad, Factor_Conversion, Cantidad_Facturada, Cantidad_Pendiente, Cantidad_Orden, Costo_Unitario, Valor_Unitario,
                    Valor_Impuesto, Porcentaje_Impuesto, Porcentaje_Descuento_1, Porcentaje_Descuento_2, Porcentaje_Descuento_3, IdVendedor, Comision_Vendedor,
                    Valor_Comision_Vendedor, IdBodega, Maneja_Inventario, Tomador, IdMoneda, Tasa_Moneda_Ext, CentroDeCostosDoc,
                    Nota_Linea, Unidades, Fecha_Vence, Exportado, Costo_Unitario_Inicial,
                    Porcentaje_ReteFuente, Envase, Numero_Lote_Destino, serial, Impuesto_Consumo, Porcentaje_ReteFuente_2,
                    Porcentaje_ReteFuente_3, Porcentaje_ReteFuente_4, Emp_1, Emp_2, Emp_3, Emp_4, Emp_5, Emp_6,
                    Emp_7, Emp_8, Tara_1, Tara_2, Tara_3, Tara_4, Tara_5, Tara_6, Tara_7, Tara_8)
                    (SELECT td.tipo AS sw, '$tipo' AS tipo, dl.seq AS seq, p.contable AS Modelo, $numdoc AS Numero_Documento,
                    '' AS Numero_Docto_Base, '0' AS Numero_Lote, dl.Nit_Cedula AS Nit_Cedula, dl.codigo_direccion AS codigo_direccion, GETDATE() AS Fecha_Documento,
                    dl.IdProducto AS IdProducto, dl.IdUnidad AS IdUnidad, '1' AS Factor_Conversion, dl.Cantidad_Facturada AS Cantidad_Facturada,
                    (dl.Cantidad_Facturada) * -1 AS Cantidad_Pendiente, dl.Cantidad_Orden AS Cantidad_Orden,
                    dl.Costo_Unitario AS Costo_Unitario, dl.valor_unitario AS Valor_Unitario, 0 AS Valor_Impuesto,
                    dl.Porcentaje_Impuesto AS Porcentaje_Impuesto, dl.Porcentaje_Descuento_1 AS Porcentaje_Descuento_1,
                    dl.Porcentaje_Descuento_2 AS Porcentaje_Descuento_2, dl.Porcentaje_Descuento_3 AS Porcentaje_Descuento_3,
                    dl.IdVendedor AS IdVendedor, 0 AS Comision_Vendedor, 0 AS Valor_Comision_Vendedor,
                    td.IdBodega AS IdBodega, 'S' AS Maneja_Inventario, '' AS Tomador, 1 AS IdMoneda, 1 AS Tasa_Moneda_Ext, '0' AS CentroDeCostosDoc,
                    ' ' AS Nota_Linea, '1' AS Unidades, GETDATE() AS Fecha_Vence, 'N' AS Exportado, dl.Costo_Unitario_Inicial AS Costo_Unitario_Inicial,
                    CASE WHEN LTRIM(RTRIM(t.TipoPersona)) = 'Juridica' THEN r.PorcentajeRetencionJuridica
                         ELSE r.PorcentajeRetencionNatural END AS Porcentaje_ReteFuente,
                    0 AS Envase, 0 AS Numero_Lote_Destino, '' AS serial, 0 AS Impuesto_Consumo,
                    0 AS Porcentaje_ReteFuente_2, 0 AS Porcentaje_ReteFuente_3, 0 AS Porcentaje_ReteFuente_4,
                    0 AS Emp_1, 0 AS Emp_2, 0 AS Emp_3, 0 AS Emp_4, 0 AS Emp_5, 0 AS Emp_6,
                    0 AS Emp_7, 0 AS Emp_8, 0 AS Tara_1, 0 AS Tara_2, 0 AS Tara_3, 0 AS Tara_4, 0 AS Tara_5, 0 AS Tara_6, 0 AS Tara_7, 0 AS Tara_8
                    FROM Documentos_Lin dl
                    INNER JOIN Documentos d ON d.Numero_documento = dl.Numero_Documento AND d.tipo = dl.tipo
                    INNER JOIN TblTipoDoctos td ON td.idTipoDoctos = '$tipo'
                    INNER JOIN TblProducto p ON p.IdProducto = dl.IdProducto
                    LEFT JOIN TblTerceros t ON dl.Nit_Cedula = t.nit_cedula
                    LEFT JOIN TblRetencion r ON p.Retencion = r.IdRetencion
                    WHERE dl.Numero_documento = '$numero_base' AND dl.tipo = '$tipo_base')";

                } else {
                    throw new Exception("El documento no tiene un documento de referencia válido para reiniciar.");
                }

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
                    'modulo' => 'Entradas', 'operacion' => 'reiniciar_doc_entrada', 'destructiva' => 1,
                    'tipo' => $tipo, 'numero' => $numdoc,
                    'exportado_antes' => $row_check['exportado'],
                    'lineas_antes' => $snap['lineas'],
                    'lineas_despues' => AuditoriaDocumentos::contarLineas($cn->getConecta(), $tipo, (int)$numdoc),
                    'resultado' => 'ok',
                    'mensaje' => 'Detalle borrado y regenerado desde el documento de referencia'
                               . ($desmarcado ? ' (documento previamente DESMARCADO)' : ''),
                    'detalle_antes' => $snap['detalle']
                ));

                return json_encode(array("status" => "success", "message" => "Documento reiniciado correctamente."));

            } catch (Exception $e) {
                if (isset($cn) && $cn->getConecta()) sqlsrv_rollback($cn->getConecta());
                AuditoriaDocumentos::registrar(array(
                    'modulo' => 'Entradas', 'operacion' => 'reiniciar_doc_entrada', 'destructiva' => 1,
                    'tipo' => $tipo, 'numero' => $numdoc,
                    'lineas_antes' => isset($snap) ? $snap['lineas'] : null,
                    'resultado' => 'error', 'mensaje' => $e->getMessage(),
                    'detalle_antes' => isset($snap) ? $snap['detalle'] : null
                ));
                return json_encode(array("status" => "error", "message" => $e->getMessage()));
            }
        }

        // ─── Módulo Gestión de Documentos: buscar/desmarcar/anular sobre cualquier tipo de documento ───

        public function buscar_documento_gestion($tipo, $numero) {
            $cn = new Conectarserver;
            $sql = "SELECT d.tipo, t.TipoDoctos, t.tipo AS categoria, d.Numero_documento, d.exportado, d.anulado, d.usuario,
                           d.Fecha_Hora_Factura, d.nit_Cedula, d.Nombre_Cliente, d.notas
                    FROM Documentos d
                    INNER JOIN TblTipoDoctos t ON d.tipo = t.idTipoDoctos
                    WHERE d.tipo = ? AND d.Numero_documento = ?";
            $stmt = sqlsrv_query($cn->getConecta(), $sql, array($tipo, $numero));
            if ($stmt === false) {
                return json_encode(['status' => 'error', 'message' => 'Error al consultar el documento: ' . print_r(sqlsrv_errors(), true)]);
            }
            $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            if (!$row) {
                return json_encode(['status' => 'error', 'message' => 'Documento no encontrado']);
            }
            $fecha = ($row['Fecha_Hora_Factura'] instanceof DateTime) ? date_format($row['Fecha_Hora_Factura'], 'd/m/Y H:i') : '';
            return json_encode([
                'status' => 'ok',
                'documento' => [
                    'tipo'       => $row['tipo'],
                    'tipoNombre' => trim($row['TipoDoctos']),
                    'categoria'  => trim($row['categoria']),
                    'numero'     => $row['Numero_documento'],
                    'exportado'  => trim($row['exportado']),
                    'anulado'    => trim($row['anulado']),
                    'usuario'    => trim($row['usuario'] ?? ''),
                    'fecha'      => $fecha,
                    'nit'        => trim($row['nit_Cedula'] ?? ''),
                    'nombre'     => trim($row['Nombre_Cliente'] ?? ''),
                    'notas'      => trim($row['notas'] ?? '')
                ]
            ]);
        }

        // Devuelve un documento ya guardado a estado editable (exportado 'S' pasa a 'N').
        // Es la operación de mayor riesgo del sistema: a partir de aquí el detalle de un
        // documento YA IMPRESO puede recortarse o regenerarse. Por eso exige motivo, deja
        // rastro en las notas del propio documento y queda registrada en la bitácora MySQL.
        public function desmarcar_documento($tipo, $numero, $motivo = '', $usuario = '') {
            $cn = new Conectarserver;
            $sqlChk = "SELECT exportado, anulado, notas FROM Documentos WHERE tipo = ? AND Numero_documento = ?";
            $stmtChk = sqlsrv_query($cn->getConecta(), $sqlChk, array($tipo, $numero));
            $row = $stmtChk ? sqlsrv_fetch_array($stmtChk, SQLSRV_FETCH_ASSOC) : null;
            if (!$row) {
                return json_encode(['status' => 'error', 'message' => 'Documento no encontrado']);
            }
            if (trim($row['anulado']) === 'S') {
                return json_encode(['status' => 'error', 'message' => 'El documento está anulado, no se puede desmarcar']);
            }
            if (trim($row['exportado']) !== 'S') {
                return json_encode(['status' => 'error', 'message' => 'El documento ya está desmarcado (Exportado = N)']);
            }
            if (trim($motivo) === '') {
                return json_encode(['status' => 'error', 'message' => 'Debe indicar el motivo por el cual desmarca el documento']);
            }

            // Mismo criterio de rastro que anular_documento: la zona horaria se fija aquí
            // porque este endpoint AJAX no pasa por una vista que la establezca.
            date_default_timezone_set("America/Bogota");
            $fecha = date('Y-m-d H:i:s');
            $notaExistente = trim($row['notas'] ?? '');
            $partes = array();
            if ($notaExistente !== '') $partes[] = $notaExistente;
            $partes[] = trim($motivo);
            $partes[] = 'DESMARCADO';
            $partes[] = $fecha;
            $partes[] = $usuario;
            $notaFinal = implode(' - ', $partes);

            // Cuántas líneas tenía al desmarcarse: si luego aparece con menos, la bitácora
            // muestra exactamente entre qué dos operaciones se perdieron.
            $snap = AuditoriaDocumentos::snapshotLineas($cn->getConecta(), $tipo, $numero);

            $sql = "UPDATE Documentos SET exportado = 'N', notas = ? WHERE tipo = ? AND Numero_documento = ?";
            $stmt = sqlsrv_query($cn->getConecta(), $sql, array($notaFinal, $tipo, $numero));
            if ($stmt === false) {
                $err = print_r(sqlsrv_errors(), true);
                AuditoriaDocumentos::registrar(array(
                    'modulo' => 'GestionDocumentos', 'operacion' => 'desmarcar', 'destructiva' => 1,
                    'tipo' => $tipo, 'numero' => $numero,
                    'exportado_antes' => $row['exportado'], 'anulado_antes' => $row['anulado'],
                    'resultado' => 'error', 'mensaje' => $err
                ));
                return json_encode(['status' => 'error', 'message' => 'Error al desmarcar el documento: ' . $err]);
            }

            AuditoriaDocumentos::registrar(array(
                'modulo' => 'GestionDocumentos', 'operacion' => 'desmarcar', 'destructiva' => 1,
                'tipo' => $tipo, 'numero' => $numero,
                'exportado_antes' => $row['exportado'], 'anulado_antes' => $row['anulado'],
                'lineas_antes' => $snap['lineas'], 'lineas_despues' => $snap['lineas'],
                'resultado' => 'ok',
                'mensaje' => 'Motivo: ' . trim($motivo),
                'detalle_antes' => $snap['detalle']
            ));

            return json_encode([
                'status'  => 'success',
                'message' => 'Documento desmarcado correctamente (Exportado = N)',
                'notas'   => $notaFinal
            ]);
        }

        public function anular_documento($tipo, $numero, $motivo, $usuario) {
            $cn = new Conectarserver;
            $sqlChk = "SELECT anulado, notas FROM Documentos WHERE tipo = ? AND Numero_documento = ?";
            $stmtChk = sqlsrv_query($cn->getConecta(), $sqlChk, array($tipo, $numero));
            $row = $stmtChk ? sqlsrv_fetch_array($stmtChk, SQLSRV_FETCH_ASSOC) : null;
            if (!$row) {
                return json_encode(['status' => 'error', 'message' => 'Documento no encontrado']);
            }
            if (trim($row['anulado']) === 'S') {
                return json_encode(['status' => 'error', 'message' => 'El documento ya está anulado']);
            }

            // Deja rastro de auditoría en las notas: lo que ya tenía el documento, el motivo
            // digitado por el usuario, y quién/cuándo lo anuló.
            // Sin esto, date() usa la zona horaria por defecto del servidor (no Bogotá),
            // ya que este endpoint AJAX no pasa por una vista que la fije como las demás páginas.
            date_default_timezone_set("America/Bogota");
            $notaExistente = trim($row['notas'] ?? '');
            $fechaAnulacion = date('Y-m-d H:i:s');
            $partesNota = array();
            if ($notaExistente !== '') $partesNota[] = $notaExistente;
            $partesNota[] = trim($motivo);
            $partesNota[] = 'ANULADO';
            $partesNota[] = $fechaAnulacion;
            $partesNota[] = $usuario;
            $notaFinal = implode(' - ', $partesNota);

            // Foto del detalle ANTES de poner las cantidades en cero. La nota de cada linea
            // conserva la cantidad original en texto, pero este snapshot la guarda
            // estructurada, que es lo que permite reponerla si la anulacion fue un error.
            $conn = $cn->getConecta();
            $snapAnula = AuditoriaDocumentos::snapshotLineas($conn, $tipo, $numero);
            $lineas = $snapAnula['lineas'];

            sqlsrv_begin_transaction($conn);
            try {
                // Anular pone las cantidades en cero y deja constancia de cual era la
                // cantidad en la nota de la linea, conservando la nota que ya tuviera.
                // El formato es el mismo que viene usando el sistema anterior en los 1.732
                // documentos ya anulados, para que las notas historicas sean homogeneas:
                //     Cantidad Antes de Anular: 1600.00Nota: <nota original>
                //
                // Un solo UPDATE para todo el documento: hacerlo linea por linea seria
                // lento en documentos grandes (los inventarios pasan de 1.000 lineas).
                // Los CONVERT dan el numero con 2 decimales ("1600.00"); sin el paso por
                // decimal, el tipo money se convertiria como "1600.0000".
                // LEFT(...,250) evita el error de truncamiento: Nota_Linea es varchar(250)
                // y ya hay notas de 218 caracteres, que con el prefijo se pasarian.
                $sqlLineas = "UPDATE Documentos_Lin
                    SET Nota_Linea = LEFT('Cantidad Antes de Anular: '
                                          + CONVERT(varchar(30), CONVERT(decimal(18,2), Cantidad_Facturada))
                                          + 'Nota: ' + ISNULL(Nota_Linea, ''), 250),
                        Cantidad_Facturada = 0,
                        Cantidad_Orden     = 0,
                        Valor_Impuesto     = 0
                    WHERE tipo = ? AND Numero_Documento = ?";
                $stmtLineas = sqlsrv_query($conn, $sqlLineas, array($tipo, $numero));
                if ($stmtLineas === false) {
                    throw new Exception("Error al anular las lineas: " . print_r(sqlsrv_errors(), true));
                }
                $lineasAnuladas = sqlsrv_rows_affected($stmtLineas);

                // Cabecera: ademas de marcarla anulada se ponen en cero los totales que
                // dependen de las cantidades, para que no quede afirmando un valor que el
                // detalle ya no respalda. costo y Total_Items se conservan, igual que hace
                // el sistema anterior.
                $sql = "UPDATE Documentos SET anulado = 'S', notas = ?, valor_total = 0, Valor_impuesto = 0
                        WHERE tipo = ? AND Numero_documento = ?";
                $stmt = sqlsrv_query($conn, $sql, array($notaFinal, $tipo, $numero));
                if ($stmt === false) {
                    throw new Exception("Error al anular el documento: " . print_r(sqlsrv_errors(), true));
                }

                sqlsrv_commit($conn);
            } catch (Exception $e) {
                @sqlsrv_rollback($conn);
                $this->registrar_error("Error en anular_documento ($tipo/$numero): " . $e->getMessage());
                AuditoriaDocumentos::registrar(array(
                    'modulo' => 'GestionDocumentos', 'operacion' => 'anular', 'destructiva' => 1,
                    'tipo' => $tipo, 'numero' => $numero, 'anulado_antes' => $row['anulado'],
                    'lineas_antes' => $lineas,
                    'resultado' => 'error', 'mensaje' => $e->getMessage(),
                    'detalle_antes' => $snapAnula['detalle']
                ));
                return json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            }

            // El stock se recalcula igual que despues de guardar un documento: las
            // cantidades cambiaron, y dejarlo sin refrescar mostraria existencias que ya
            // no corresponden.
            $stmtSto = sqlsrv_prepare($conn, "(EXEC UPDATE_PRODUCTO_STO)");
            if ($stmtSto) sqlsrv_execute($stmtSto);

            AuditoriaDocumentos::registrar(array(
                'modulo' => 'GestionDocumentos', 'operacion' => 'anular', 'destructiva' => 1,
                'tipo' => $tipo, 'numero' => $numero, 'anulado_antes' => $row['anulado'],
                'lineas_antes' => $lineas, 'lineas_despues' => $lineas,
                'filas_afectadas' => $lineasAnuladas,
                'resultado' => 'ok',
                'mensaje' => 'Motivo: ' . trim($motivo)
                           . ' | Cantidades puestas en cero en ' . $lineasAnuladas . ' lineas',
                'detalle_antes' => $snapAnula['detalle']
            ));

            return json_encode([
                'status'  => 'success',
                'message' => 'Documento anulado correctamente. Se pusieron en cero las cantidades de '
                           . $lineasAnuladas . ' línea(s); la cantidad original quedó registrada en la nota de cada una.',
                'notas'   => $notaFinal
            ]);
        }

    }
?>
