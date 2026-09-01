<?php
    require_once("../config/conexionserver.php");
    require_once("../config/session_guard.php");
    verificar_sesion_activa();
    require_once("../models/mdlSalidas.php");
    require_once("../models/mdlPermisos.php");

    $permisos = new Permisos();
    if (!$permisos->tiene_permiso_especial($_SESSION["Id_Usuario"], 'seguimiento_os')) {
        echo json_encode(["status" => "error", "message" => "No tiene permiso para acceder a este módulo."]);
        exit();
    }

    $salidas = new Salidas();

    switch($_GET["op"]){

        case "seguimiento_os":
            echo $salidas->seguimiento_os($_POST["numero"] ?? '');
        break;

    }
?>
