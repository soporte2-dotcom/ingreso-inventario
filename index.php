<?php
    require_once("config/conexionserver.php");
    if(isset($_POST["enviar"]) and $_POST["enviar"]=="si"){
        require_once("models/Usuario.php");
        $usuario = new Usuario();
        $usuario->login();
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<title>Login V1</title>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
<!--===============================================================================================-->	
	<link rel="icon" type="image/png" href="public/images/icons/favicon.ico"/>
<!--===============================================================================================-->
	<link rel="stylesheet" type="text/css" href="public/vendor/bootstrap/css/bootstrap.min.css">
<!--===============================================================================================-->
	<link rel="stylesheet" type="text/css" href="public/fonts/font-awesome-4.7.0/css/font-awesome.min.css">
<!--===============================================================================================-->
	<link rel="stylesheet" type="text/css" href="public/vendor/animate/animate.css">
<!--===============================================================================================-->	
	<link rel="stylesheet" type="text/css" href="public/vendor/css-hamburgers/hamburgers.min.css">
<!--===============================================================================================-->
	<link rel="stylesheet" type="text/css" href="public/vendor/select2/select2.min.css">
<!--===============================================================================================-->
	<link rel="stylesheet" type="text/css" href="public/css1/util.css">
	<link rel="stylesheet" type="text/css" href="public/css1/main.css">
<!--===============================================================================================-->
</head>
<body>
	
	<div class="limiter">
		<div class="container-login100">
			<div class="wrap-login100">
				<div class="login100-pic js-tilt" data-tilt>
					<img src="public/images/img-01.png" alt="IMG">
				</div>

				<form class="sign-box" action="" method="post" id="login_form">
                    
                    <input type="hidden" id="rol_id" name="rol_id" value="1">
					<span class="login100-form-title">
						Cervalle Inventarios
					</span>

					<?php
                        if (isset($_GET["m"])){
                            switch($_GET["m"]){
                                case "1";
                                    ?>
                                        <div class="alert alert-danger" role="alert">
                                            El Usuario y/o Contraseña son incorrectos.
                                        </div>
                                    <?php
                                break;

                                case "2";
                                    ?>
                                        <div class="alert alert-warning" role="alert">
                                            Los campos estan vacios.
                                        </div>
                                    <?php
                                break;
                            }
                        }
                    ?>

					<div class="wrap-input100 validate-input" data-validate = "Valid usuario is required">
						<input class="input100" type="text" id="Id_Usuario" name="Id_Usuario" placeholder="usuario">
						<span class="focus-input100"></span>
						<span class="symbol-input100">
							<i class="fa fa-envelope" aria-hidden="true"></i>
						</span>
					</div>

					<div class="wrap-input100 validate-input" data-validate = "Password is required">
						<input class="input100" type="password" id="Clave_Usuario" name="Clave_Usuario" placeholder="Contrasena">
						<span class="focus-input100"></span>
						<span class="symbol-input100">
							<i class="fa fa-lock" aria-hidden="true"></i>
						</span>
					</div>
					
					<div class="container-login100-form-btn">
					<input type="hidden" name="enviar" class="form-control" value="si">
						<button type="submit" class="login100-form-btn">
							Acceder
						</button>
					</div>

					<div class="text-center p-t-12">
						
					</div>

				</form>
			</div>
		</div>
	</div>
	
	

	
<!--===============================================================================================-->	
	<script src="public/vendor/jquery/jquery-3.2.1.min.js"></script>
<!--===============================================================================================-->
	<script src="public/vendor/bootstrap/js/popper.js"></script>
	<script src="public/vendor/bootstrap/js/bootstrap.min.js"></script>
<!--===============================================================================================-->
	<script src="public/vendor/select2/select2.min.js"></script>
<!--===============================================================================================-->
	<script src="public/vendor/tilt/tilt.jquery.min.js"></script>
	<script >
		$('.js-tilt').tilt({
			scale: 1.1
		})
	</script>
<!--===============================================================================================-->
	<script src="public/js/main.js"></script>

</body>
</html>