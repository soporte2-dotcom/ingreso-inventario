<?php

class Usuario extends Conectarserver{

        public function login(){
            $cn = new Conectarserver;
            if(isset($_POST["enviar"])){
                $idusuario = $_POST["Id_Usuario"];
                $pass = $_POST["Clave_Usuario"];
                if(empty($idusuario) and empty($pass)){
                    header("Location:".conectarserver::ruta()."index.php?m=2");
					exit();
                }else{
                    
                    $sql = "SELECT * FROM TblUsuarios WHERE Id_Usuario='$idusuario' and Clave_Usuario='$pass' ";
                    $registros = sqlsrv_query($cn->getConecta(), $sql);
                    if( $registros === false ){
                        echo "Error al ejecutar consulta.</br>";
                    }  else {
                            while($stmt= sqlsrv_fetch_array($registros)) {
                                $resultado[] = $stmt; 
                               
                            }
                            if (is_array($resultado)==true and count($resultado)>0){
                                foreach($resultado as $row)
                                {           
                                $_SESSION["Id_Usuario"]=$row["Id_Usuario"];
                                $_SESSION["Nom_Usuario"]=$row["Nom_Usuario"];
                                $_SESSION["Ape_Usuario"]=$row["Ape_Usuario"];
                                }
                                echo json_encode($_SESSION);
                                //header("Location:".Conectarserver::ruta()."view/prueba.php");
                              
                                header("Location:".Conectarserver::ruta()."view/Home/");
                                exit(); 
                            }else{
                                header("Location:".Conectarserver::ruta()."index.php?m=1");
                                exit();
                            }

                            return $resultado;
                        }
                }
            }
        }
}


?>