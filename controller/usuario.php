<?php
    require_once("../config/conexionserver.php");
    require_once("../config/session_guard.php");
    verificar_sesion_activa();
    require_once("../models/Usuario.php");
    $usuario = new Usuario();

    switch($_GET["op"]){       
        
    }
?>