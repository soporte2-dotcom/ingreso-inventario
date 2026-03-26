<?php

session_start();

 class Conectarserver{

  protected $cn;
    
    public function __construct(){
        
        /* Esta configuración establece cómo se manejan los avisos (warnings) en las consultas. 
        Cuando se establece en 0, los avisos no se tratan como errores y no generan una excepción. 
        Esto significa que si hay avisos en una consulta, no se interrumpirá la ejecución y se pueden recuperar los resultados, si los hay. */
        sqlsrv_configure("WarningsReturnAsErrors", 0);

        /* Esta configuración establece el nivel de aislamiento de transacciones para las conexiones. */
        sqlsrv_configure("TransactionIsolation", SQLSRV_TXN_READ_UNCOMMITTED);

        /* Esta configuración habilita el "connection pooling" o agrupamiento de conexiones. Cuando está habilitado, 
        se reutilizan las conexiones existentes en lugar de crear nuevas conexiones para cada solicitud.  */
        sqlsrv_configure("ConnectionPooling", true);

        $servidor = "192.168.16.8\BDACOPI";
        //$info = array("Database"=>"DOT_BUGA_NUEVA","UID"=>"sa","PWD"=>"3.1416.asd*","CharacterSet"=>"UTF-8");
        $info = array("Database"=>"DOT_BUGA_NUEVA_BKJH20251002","UID"=>"sa","PWD"=>"3.1416.asd*","CharacterSet"=>"UTF-8");
        
         $this->cn = sqlsrv_connect($servidor, $info);
    }
    
    public function getConecta(){
        return $this->cn;
    }

    public static function ruta(){
    return;
    }

 }
