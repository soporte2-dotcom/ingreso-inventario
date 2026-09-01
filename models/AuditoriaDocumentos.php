<?php
    /**
     * Bitácora de auditoría de Documentos / Documentos_Lin sobre MySQL (`permisos_tecno`).
     *
     * Existe porque SQL Server es la base heredada de Tecnocarnes: no se le pueden
     * agregar tablas ni triggers, así que el rastro de quién modifica un documento
     * se guarda en la base que sí controlamos. Ver sql/05_mysql_create_auditoria_documentos.sql.
     *
     * Regla de oro: registrar NUNCA puede tumbar la operación principal. Todo va
     * dentro de try/catch y los fallos se mandan a logs/errores.log.
     */
    class AuditoriaDocumentos {

        /**
         * Máximo de líneas que se serializan en detalle_antes.
         *
         * Se fija en 3000 igual que Documento::EXCEL_MAX_FILAS, el tope de filas que puede
         * tener un documento cargado por Excel: así el snapshot alcanza para reponer el
         * detalle COMPLETO de cualquier documento del sistema, que es para lo que existe.
         * Medido sobre un inventario real: ~101 bytes por línea, o sea ~0,3 MB en el peor
         * caso, muy por debajo de los 16 MB de un MEDIUMTEXT.
         */
        const MAX_LINEAS_SNAPSHOT = 3000;

        /** Conexión PDO reutilizada dentro de una misma petición. */
        private static $pdo = null;
        /** Si MySQL falló una vez en esta petición, no se reintenta (evita latencia acumulada). */
        private static $deshabilitado = false;

        private static function conexion() {
            if (self::$deshabilitado) return null;
            if (self::$pdo !== null)  return self::$pdo;
            try {
                require_once(dirname(__FILE__) . '/../config/conexionmysql.php');
                $my  = new ConectarMysql();
                $pdo = $my->obtenerConexion();
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                self::$pdo = $pdo;
                return $pdo;
            } catch (Exception $e) {
                self::$deshabilitado = true;
                self::logArchivo("AuditoriaDocumentos: no se pudo conectar a MySQL: " . $e->getMessage());
                return null;
            }
        }

        private static function logArchivo($mensaje) {
            $archivo = dirname(__FILE__) . '/../logs/errores.log';
            @file_put_contents($archivo, "[" . date('Y-m-d H:i:s') . "] $mensaje" . PHP_EOL, FILE_APPEND);
        }

        /**
         * Inserta un registro de auditoría.
         *
         * @param array $d Claves: modulo, operacion, destructiva, tipo, numero,
         *                 exportado_antes, anulado_antes, lineas_antes, lineas_despues,
         *                 filas_afectadas, resultado ('ok'|'error'|'bloqueado'),
         *                 mensaje, detalle_antes (array|string), contexto.
         */
        public static function registrar(array $d) {
            $pdo = self::conexion();
            if ($pdo === null) return;

            try {
                $detalle = isset($d['detalle_antes']) ? $d['detalle_antes'] : null;
                if (is_array($detalle)) {
                    $detalle = json_encode($detalle, JSON_UNESCAPED_UNICODE);
                }

                $sql = "INSERT INTO auditoria_documentos
                        (fecha, usuario, ip, modulo, operacion, destructiva, tipo, numero,
                         exportado_antes, anulado_antes, lineas_antes, lineas_despues,
                         filas_afectadas, resultado, mensaje, detalle_antes, contexto)
                        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";

                $stmt = $pdo->prepare($sql);
                $stmt->execute(array(
                    self::ahoraConMilisegundos(),
                    self::usuarioActual(),
                    self::ipActual(),
                    (string)(isset($d['modulo'])    ? $d['modulo']    : '?'),
                    (string)(isset($d['operacion']) ? $d['operacion'] : '?'),
                    !empty($d['destructiva']) ? 1 : 0,
                    (isset($d['tipo'])   && $d['tipo']   !== '') ? (string)$d['tipo'] : null,
                    (isset($d['numero']) && $d['numero'] !== '') ? (int)$d['numero']  : null,
                    self::charONulo(isset($d['exportado_antes']) ? $d['exportado_antes'] : null),
                    self::charONulo(isset($d['anulado_antes'])   ? $d['anulado_antes']   : null),
                    isset($d['lineas_antes'])    ? (int)$d['lineas_antes']    : null,
                    isset($d['lineas_despues'])  ? (int)$d['lineas_despues']  : null,
                    isset($d['filas_afectadas']) ? (int)$d['filas_afectadas'] : null,
                    self::resultadoValido(isset($d['resultado']) ? $d['resultado'] : 'ok'),
                    isset($d['mensaje']) ? mb_substr(self::aTexto($d['mensaje']), 0, 500) : null,
                    $detalle,
                    mb_substr((string)(isset($d['contexto']) ? $d['contexto'] : self::contextoActual()), 0, 255)
                ));
            } catch (Exception $e) {
                // Un fallo de auditoría jamás debe propagarse a la operación de negocio.
                self::logArchivo("AuditoriaDocumentos::registrar falló ("
                    . (isset($d['operacion']) ? $d['operacion'] : '?')
                    . " tipo=" . (isset($d['tipo']) ? $d['tipo'] : '?')
                    . " num="  . (isset($d['numero']) ? $d['numero'] : '?') . "): " . $e->getMessage());
            }
        }

        /**
         * Fotografía de las líneas de un documento ANTES de una operación destructiva.
         * Es lo que permite decir exactamente qué se perdió y reponerlo si hace falta.
         *
         * @param resource $conn Conexión sqlsrv ya abierta.
         * @return array ['lineas' => int|null, 'detalle' => array|null]
         */
        public static function snapshotLineas($conn, $tipo, $numero) {
            $vacio = array('lineas' => null, 'detalle' => null);
            if (!$conn) return $vacio;
            try {
                $sql = "SELECT seq, IdProducto, Numero_Lote, Cantidad_Facturada, Valor_Unitario,
                               Costo_Unitario, Porcentaje_Impuesto, Nota_Linea
                        FROM Documentos_Lin
                        WHERE tipo = ? AND Numero_Documento = ?
                        ORDER BY seq";
                $stmt = sqlsrv_query($conn, $sql, array($tipo, (int)$numero));
                if ($stmt === false) return $vacio;

                $filas = array();
                $total = 0;
                while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                    $total++;
                    if (count($filas) < self::MAX_LINEAS_SNAPSHOT) {
                        $filas[] = array(
                            'seq'   => $row['seq'],
                            'prod'  => $row['IdProducto'],
                            'lote'  => is_string($row['Numero_Lote']) ? trim($row['Numero_Lote']) : $row['Numero_Lote'],
                            'cant'  => $row['Cantidad_Facturada']  !== null ? (float)$row['Cantidad_Facturada']  : null,
                            'vlr'   => $row['Valor_Unitario']      !== null ? (float)$row['Valor_Unitario']      : null,
                            'costo' => $row['Costo_Unitario']      !== null ? (float)$row['Costo_Unitario']      : null,
                            'iva'   => $row['Porcentaje_Impuesto'] !== null ? (float)$row['Porcentaje_Impuesto'] : null,
                            'nota'  => is_string($row['Nota_Linea']) ? trim($row['Nota_Linea']) : null
                        );
                    }
                }
                sqlsrv_free_stmt($stmt);

                $detalle = array('lineas' => $filas);
                if ($total > self::MAX_LINEAS_SNAPSHOT) {
                    $detalle['truncado'] = true;
                    $detalle['total']    = $total;
                }
                return array('lineas' => $total, 'detalle' => $detalle);
            } catch (Exception $e) {
                self::logArchivo("AuditoriaDocumentos::snapshotLineas falló (tipo=$tipo num=$numero): " . $e->getMessage());
                return $vacio;
            }
        }

        /** Cuenta las líneas de un documento (barato: se usa para el "después"). */
        public static function contarLineas($conn, $tipo, $numero) {
            if (!$conn) return null;
            $stmt = sqlsrv_query($conn,
                "SELECT COUNT(*) AS n FROM Documentos_Lin WHERE tipo = ? AND Numero_Documento = ?",
                array($tipo, (int)$numero));
            if ($stmt === false) return null;
            $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            return $row ? (int)$row['n'] : null;
        }

        /** Estado (exportado/anulado) del documento, para dejarlo grabado en la bitácora. */
        public static function estadoDocumento($conn, $tipo, $numero) {
            $vacio = array('exportado' => null, 'anulado' => null);
            if (!$conn) return $vacio;
            $stmt = sqlsrv_query($conn,
                "SELECT exportado, anulado FROM Documentos WHERE tipo = ? AND Numero_documento = ?",
                array($tipo, (int)$numero));
            if ($stmt === false) return $vacio;
            $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            if (!$row) return $vacio;
            return array(
                'exportado' => isset($row['exportado']) ? trim($row['exportado']) : null,
                'anulado'   => isset($row['anulado'])   ? trim($row['anulado'])   : null
            );
        }

        /** Tope de filas que devuelve una consulta de la pantalla, para no traer la tabla entera. */
        const MAX_FILAS_CONSULTA = 500;

        /**
         * Consulta la bitácora para la pantalla de Auditoría.
         *
         * `detalle_antes` NO se incluye aquí a propósito: son cientos de KB por fila y
         * reventaría la respuesta. La pantalla lo pide aparte con obtenerPorId() solo
         * para la fila que el usuario abre.
         *
         * @param array $f fechaDesde, fechaHasta, tipo, numero, usuario, operacion,
         *                 resultado, soloDestructivas, soloConPerdida
         * @return array
         */
        public static function consultar(array $f) {
            $pdo = self::conexion();
            if ($pdo === null) return array();

            $where  = array();
            $params = array();

            if (!empty($f['fechaDesde'])) { $where[] = "fecha >= ?"; $params[] = $f['fechaDesde'] . ' 00:00:00.000'; }
            if (!empty($f['fechaHasta'])) { $where[] = "fecha <= ?"; $params[] = $f['fechaHasta'] . ' 23:59:59.999'; }
            if (!empty($f['tipo']))       { $where[] = "tipo = ?";   $params[] = (string)$f['tipo']; }
            if (!empty($f['numero']))     { $where[] = "numero = ?"; $params[] = (int)$f['numero']; }
            if (!empty($f['usuario']))    { $where[] = "usuario LIKE ?"; $params[] = '%' . $f['usuario'] . '%'; }
            if (!empty($f['operacion']))  { $where[] = "operacion = ?"; $params[] = $f['operacion']; }
            if (!empty($f['resultado']))  { $where[] = "resultado = ?"; $params[] = $f['resultado']; }
            if (!empty($f['soloDestructivas'])) { $where[] = "destructiva = 1"; }
            // Lo que de verdad interesa: operaciones que dejaron el documento con menos
            // líneas de las que tenía.
            if (!empty($f['soloConPerdida'])) {
                $where[] = "lineas_despues IS NOT NULL AND lineas_antes IS NOT NULL AND lineas_despues < lineas_antes";
            }

            $sql = "SELECT id, fecha, usuario, ip, modulo, operacion, destructiva, tipo, numero,
                           exportado_antes, anulado_antes, lineas_antes, lineas_despues,
                           filas_afectadas, resultado, mensaje,
                           (detalle_antes IS NOT NULL) AS tiene_detalle
                    FROM auditoria_documentos";
            if ($where) $sql .= " WHERE " . implode(' AND ', $where);
            $sql .= " ORDER BY fecha DESC, id DESC LIMIT " . (int)self::MAX_FILAS_CONSULTA;

            try {
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e) {
                self::logArchivo("AuditoriaDocumentos::consultar falló: " . $e->getMessage());
                return array();
            }
        }

        /** Un registro completo, con el JSON del detalle previo. Para el modal de la pantalla. */
        public static function obtenerPorId($id) {
            $pdo = self::conexion();
            if ($pdo === null) return null;
            try {
                $stmt = $pdo->prepare("SELECT * FROM auditoria_documentos WHERE id = ?");
                $stmt->execute(array((int)$id));
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                return $row ? $row : null;
            } catch (Exception $e) {
                self::logArchivo("AuditoriaDocumentos::obtenerPorId falló (id=$id): " . $e->getMessage());
                return null;
            }
        }

        /** Operaciones distintas ya registradas, para llenar el combo de filtro. */
        public static function operacionesRegistradas() {
            $pdo = self::conexion();
            if ($pdo === null) return array();
            try {
                return $pdo->query("SELECT DISTINCT operacion FROM auditoria_documentos ORDER BY operacion")
                           ->fetchAll(PDO::FETCH_COLUMN);
            } catch (Exception $e) {
                return array();
            }
        }

        /**
         * ¿Este documento fue devuelto a editable con "Desmarcar" alguna vez?
         *
         * Es la señal de riesgo que importa: un documento que ya estuvo exportado
         * (guardado e impreso) y volvió a estado editable puede ser reiniciado o
         * recortado, y el detalle resultante ya no coincide con lo que se imprimió.
         * Devuelve null si nunca se desmarcó o si MySQL no está disponible.
         *
         * @return array|null ['fecha' => ..., 'usuario' => ..., 'mensaje' => ...]
         */
        public static function fueDesmarcado($tipo, $numero) {
            $pdo = self::conexion();
            if ($pdo === null) return null;
            try {
                $stmt = $pdo->prepare(
                    "SELECT fecha, usuario, mensaje FROM auditoria_documentos
                     WHERE operacion = 'desmarcar' AND resultado = 'ok'
                       AND tipo = ? AND numero = ?
                     ORDER BY fecha DESC LIMIT 1");
                $stmt->execute(array((string)$tipo, (int)$numero));
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                return $row ? $row : null;
            } catch (Exception $e) {
                self::logArchivo("AuditoriaDocumentos::fueDesmarcado falló (tipo=$tipo num=$numero): " . $e->getMessage());
                return null;
            }
        }

        /**
         * Fecha/hora con milisegundos reales, siempre en hora de Bogotá.
         *
         * Dos detalles que importan y que no son obvios:
         *  - date() NO produce milisegundos: sus formatos 'u' y 'v' siempre devuelven
         *    ceros porque opera sobre un timestamp entero. Hay que sacarlos de microtime().
         *  - La zona se fija aquí y no se hereda del php.ini ni de que el endpoint de turno
         *    haya llamado antes a date_default_timezone_set(). Si no, unas filas quedarían
         *    en hora local y otras en UTC, y una bitácora con horas mezcladas no sirve
         *    para reconstruir la secuencia de un incidente.
         *
         * Los milisegundos permiten ordenar varias operaciones de una misma petición.
         */
        private static function ahoraConMilisegundos() {
            $t  = microtime(true);
            $ms = (int)(($t - floor($t)) * 1000); // floor, no round: nunca desborda a 1000
            $tzPrevia = date_default_timezone_get();
            date_default_timezone_set('America/Bogota');
            $fecha = date('Y-m-d H:i:s', (int)$t) . '.' . sprintf('%03d', $ms);
            date_default_timezone_set($tzPrevia); // no se altera el resto de la petición
            return $fecha;
        }

        private static function usuarioActual() {
            if (!empty($_SESSION['Id_Usuario'])) return mb_substr((string)$_SESSION['Id_Usuario'], 0, 50);
            return null;
        }

        private static function ipActual() {
            // Sin proxy de por medio REMOTE_ADDR es la IP real del PC del operario,
            // que es lo que se necesita para ubicar el puesto de trabajo.
            return isset($_SERVER['REMOTE_ADDR']) ? mb_substr($_SERVER['REMOTE_ADDR'], 0, 45) : null;
        }

        private static function contextoActual() {
            return isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : 'cli';
        }

        private static function resultadoValido($v) {
            return in_array($v, array('ok', 'error', 'bloqueado'), true) ? $v : 'ok';
        }

        private static function charONulo($v) {
            if ($v === null || $v === '') return null;
            return mb_substr(trim((string)$v), 0, 1);
        }

        private static function aTexto($v) {
            return is_string($v) ? $v : print_r($v, true);
        }
    }
?>
