<?php


    $serverName = "192.168.16.8\BDACOPI";
    $conecctionInfo = array("Database"=>"DOT_BUGA_NUEVABK20260106","UID"=>"sa","PWD"=>"3.1416.asd*","CharacterSet"=>"UTF-8");
    //$conecctionInfo = array("Database"=>"DOT_BUGA_NUEVA","UID"=>"sa","PWD"=>"3.1416.asd*","CharacterSet"=>"UTF-8");
    $con = sqlsrv_connect($serverName,$conecctionInfo);

?>