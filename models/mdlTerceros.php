<?php
    class terceros extends Conectarserver{

        public function get_terceros(){
            $cn = new Conectarserver;
            $query = "SELECT * FROM TblTerceros";
            $registros = sqlsrv_query($cn->getConecta(), $query);
                if( $registros === false ){
                    echo "Error al ejecutar consulta.</br>";
                }  else {
                        while($stmt= sqlsrv_fetch_array($registros)) {
                            $resultado[] = $stmt;                     
                        }
                        return $resultado;
                }
            
        }

        public function get_terceroxnit($id){
            $cn = new Conectarserver;
            $query = "SELECT TOP(5) * FROM TblTerceros WHERE nit_cedula like '%$id%' AND bloqueo = 0";
            $registros = sqlsrv_query($cn->getConecta(), $query);
                if( $registros === false ){
                    echo "Error al ejecutar consulta.</br>";
                }  else {
                        while($stmt= sqlsrv_fetch_array($registros)) {
                            $resultado[] = $stmt;                     
                        }
                        return $resultado;
                }
            
        }

        public function get_tercerodirxnit($nit){
            $cn = new Conectarserver;
            $query = "SELECT * FROM Terceros_Dir WHERE nit = '$nit'";
            $registros = sqlsrv_query($cn->getConecta(), $query);
                if( $registros === false ){
                    echo "Error al ejecutar consulta.</br>";
                }  else {
                        while($stmt= sqlsrv_fetch_array($registros)) {
                            $resultado[] = $stmt;                     
                        }
                        return $resultado;
                }
            
        }

        public function get_telefono_dir($direccion, $nit){
            $cn = new Conectarserver;
            $query = "SELECT * FROM Terceros_Dir WHERE nit = '$nit' AND codigo_direccion = '$direccion' ";
            $registros = sqlsrv_query($cn->getConecta(), $query);
                if( $registros === false ){
                    echo "Error al ejecutar consulta.</br>";
                }  else {
                        while($stmt= sqlsrv_fetch_array($registros)) {
                            $resultado[] = $stmt;                     
                        }
                        return $resultado;
                }

        }

        /**
         * Valida la dirección elegida en un combo de combo_dir contra el NIT que se va a guardar.
         *
         * El combo envía "codigo_direccion,nit". Antes se descartaba el NIT de la opción y se guardaba
         * el código junto al NIT del campo de texto; si el combo había quedado con las opciones de OTRO
         * tercero (p. ej. el NIT de Facturar A pre-llenado, y luego se cambió el NIT de Enviar A), se
         * guardaba un codigo_direccion que no existe para ese NIT (caso 926-19358: dirección 12).
         *
         * Devuelve array('codigo' => <codigo>, 'error' => null) si es válida, o 'error' con el mensaje.
         */
        public function validar_direccion($valorCombo, $nit, $etiqueta){
            $nit   = trim((string)$nit);
            $valor = trim((string)$valorCombo);

            if ($valor === '' || $nit === '') {
                return array('codigo' => null, 'error' => "Debe seleccionar el NIT y la dirección de $etiqueta.");
            }

            $partes = explode(',', $valor);
            $codigo = trim($partes[0]);
            if (count($partes) > 1 && trim($partes[1]) !== $nit) {
                return array('codigo' => null, 'error' => "La dirección de $etiqueta pertenece al NIT " . trim($partes[1]) .
                    " y no al NIT $nit. Vuelva a seleccionar la dirección.");
            }

            $cn = new Conectarserver;
            $stmt = sqlsrv_query($cn->getConecta(),
                "SELECT COUNT(*) AS total FROM Terceros_Dir WHERE nit = ? AND codigo_direccion = ?",
                array($nit, $codigo));
            $row = $stmt ? sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC) : null;
            if (!$row) {
                return array('codigo' => null, 'error' => "No se pudo validar la dirección de $etiqueta.");
            }
            if ((int)$row['total'] === 0) {
                return array('codigo' => null, 'error' => "La dirección $codigo no existe para el NIT $nit ($etiqueta). Vuelva a seleccionar la dirección.");
            }

            return array('codigo' => $codigo, 'error' => null);
        }

    }
?>