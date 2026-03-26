<?php
class ConectarMysql{
    protected $dbh;
    
    protected function Conexion(){
        try {
            $conectar = $this->dbh = new PDO("mysql:host=localhost;dbname=permisos_tecno","root","");
            $conectar->exec("set names utf8");
            return $conectar;	
        } catch (Exception $e) {
            print "¡Error BD!: " . $e->getMessage() . "<br/>";
            die();	
        }
    }

    public function obtenerConexion(){
        return $this->Conexion();
    }
    
    public function ruta(){
        // Método vacío por compatibilidad
    }
    
    public function close(){
        $this->dbh = null;
    }
}
?>