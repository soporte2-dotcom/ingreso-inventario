<?php
require_once("../config/conexionserver.php");
require_once("../models/mdlTipoDoctos.php");
$tipoDoctos = new tipoDoctos();

switch ($_GET["op"]) {

    case "combo":
        $datos = $tipoDoctos->get_tipoDoctos();
        $html = "";
        if (is_array($datos) == true and count($datos) > 0) {
            $html .= "<option value='' disabled selected>Seleccione...</option>";
            foreach ($datos as $row) {
                $html .= "<option value='" . $row['idTipoDoctos'] . "'>" . $row['TipoDoctos'] . "</option>";
            }
            echo $html;
        }
    break;

    case "combo_entradas":
        $datos = $tipoDoctos->get_tipoDoctosEnt();
        $html = "";
        if (is_array($datos) == true and count($datos) > 0) {
            $html .= "<option value='' disabled selected>Seleccione...</option>";
            foreach ($datos as $row) {
                $html .= "<option value='" . $row['idTipoDoctos'] . "'>" . $row['TipoDoctos'] . "</option>";
            }
            echo $html;
        }
    break;

    case "consecutivos":
        $datos=$tipoDoctos->get_consecutivos($_POST["idTipo"]);  
        if(is_array($datos)==true and count($datos)>0){
            foreach($datos as $row)
            {
              $output["consecutivo"] = $row["consecutivo"];
            }
            echo json_encode($output);

        }   
    break;

    case "doctos":
        $datos = $tipoDoctos->get_Doctos();
        $html = "";
        if (is_array($datos) == true and count($datos) > 0) {
            $html .= "<option value='' disabled selected>Seleccione...</option>";
            foreach ($datos as $row) {
                $html .= "<option value='" . $row['idTipoDoctos'] . "'>" . $row['idTipoDoctos'] ."-". $row['TipoDoctos'] . "</option>";
            }
            echo $html;
        }
    break;

}  

?>


