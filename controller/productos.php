<?php
require_once("../config/conexionserver.php");
require_once("../models/mdlProductos.php");
$productos = new productos();

switch ($_GET["op"]) {
    
    case "consultar":
       
        $datos=$productos->get_producto_id($_POST['idproducto']);  
           if(is_array($datos)==true and count($datos)>0){
                foreach($datos as $row){
                $output["producto"] = $row["producto"];
                }
                echo json_encode($output);
            }     
          
    break;


    case "productoxid":
        if (isset($_GET["term"])){
            $idproducto = $_GET["term"];
            $datos=$productos->get_productoxid($idproducto);  
            if(is_array($datos)==true and count($datos)>0){
                $return_arr = array();
                foreach($datos as $row){
                    $output["value"] = $row["IdProducto"];
                    $output["IdProducto"] = $row["IdProducto"];
                    $output["Producto"] = rtrim($row["Producto"]);
                    array_push($return_arr,$output);
                }
                echo json_encode($return_arr);
            }
        }
    break;

}  
    


?>