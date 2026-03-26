<?php
require_once("../config/conexionserver.php");
require_once("../models/mdlTerceros.php");
$terceros = new terceros();

switch ($_GET["op"]) {    

    case "terceroxnit":
        if (isset($_GET["term"])){
            $nit = $_GET["term"];
            $datos=$terceros->get_terceroxnit($nit);  
            if(is_array($datos)==true and count($datos)>0){
                $return_arr = array();
                foreach($datos as $row){
                    $output["value"] = $row["nit_cedula"];
                    $output["nit_cedula"] = $row["nit_cedula"];
                    $output["nombre"] = rtrim($row["nombre"]);
                    $output["telefono"] = $row["telefono"];
                    array_push($return_arr,$output);
                }
                echo json_encode($return_arr);
            }
        }
    break;

    case "combo_dir":
        $datos=$terceros->get_tercerodirxnit($_POST["nit"]);
        $html = "";
        if (is_array($datos) == true and count($datos) > 0) {
            $html .= "<option value='' disabled selected>Seleccione...</option>";
            foreach ($datos as $row){
                $html .= "<option value='". $row['codigo_direccion'] .",".$row['nit']."'>" . $row['direccion'] . "</option>";
            }
            echo $html;
        }
    break;

    case "telefono_dir":
        $direccion = $_POST["direccion"];

        if (strpos($direccion, ",") !== false) {
                
            $direccion = explode(",", $direccion);

            $datos=$terceros->get_telefono_dir($direccion[0], $direccion[1]);  
            if(is_array($datos)==true and count($datos)>0){
                foreach($datos as $row){
                $output["telefono_1"] = $row["telefono_1"];
                }
                echo json_encode($output);
            } 
            
        }
          
    break;

}  
    


?>