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
            $query = "SELECT TOP(5) * FROM TblTerceros WHERE nit_cedula like '%$id%' ";
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
        
    }
?>