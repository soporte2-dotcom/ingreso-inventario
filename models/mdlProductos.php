<?php
    class productos extends Conectarserver{

        

        public function get_producto_id($producto){
            $cn = new Conectarserver;
            $query = "SELECT COUNT(*) AS producto FROM TblProducto WHERE IdProducto =  '$producto' ";
            $params = array();
            $options =  array( "Scrollable" => SQLSRV_CURSOR_KEYSET );
            $registros = sqlsrv_query($cn->getConecta(), $query);
            $row_count = sqlsrv_num_rows($registros);
                if( $registros === false ){
                    echo "Error al ejecutar consulta";
                }  else {
                        while($stmt= sqlsrv_fetch_array($registros)) {
                            $resultado[] = $stmt;                     
                        }
                        return $resultado;
                }
            
        }

        public function get_productoxid($idproducto){
            $cn = new Conectarserver;
            $query = "SELECT TOP(6) * FROM TblProducto WHERE IdProducto = '$idproducto' ";
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