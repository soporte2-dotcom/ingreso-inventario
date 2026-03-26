<?php
    class tipoDoctos extends Conectarserver{

        public function get_tipoDoctos(){
            $cn = new Conectarserver;
            $query = "SELECT * FROM TblTipoDoctos WHERE tipo = '99' AND Activo = 'S' ORDER BY TipoDoctos ASC";
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

        public function get_tipoDoctosEnt(){
            $cn = new Conectarserver;
            $query = "SELECT * FROM TblTipoDoctos WHERE tipo IN ('12', '3') AND Activo = 'S' ORDER BY TipoDoctos ASC";
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

        public function get_consecutivos($tipo){
            $cn = new Conectarserver;
            $query = "SELECT siguiente+1 AS consecutivo FROM Consecutivos WHERE tipo = $tipo";
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

        public function get_Doctos(){
            $cn = new Conectarserver;
            $query = "SELECT * FROM TblTipoDoctos WHERE Activo = 'S' ORDER BY TipoDoctos ASC";
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