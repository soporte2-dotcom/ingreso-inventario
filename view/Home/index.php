<?php
  require_once("../../config/conexionserver.php"); 
  
  // Verificar si el usuario está logueado
  if(isset($_SESSION["Id_Usuario"])) {
    // Obtener datos del usuario actual
    $usuario = isset($_SESSION["Nom_Usuario"]) ? $_SESSION["Nom_Usuario"] : "Usuario";
?>
<!DOCTYPE html>
<html>
    <?php require_once("../MainHead/head.php");?>
    <link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <title>Cervalle::Home</title>
    <style>
        .welcome-card {
            background: linear-gradient(135deg, #1e88e5, #0d47a1);
            color: white;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            padding: 30px;
            margin-top: 20px;
            transition: all 0.3s ease;
        }
        
        .welcome-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 25px rgba(0, 0, 0, 0.15);
        }
        
        .welcome-title {
            font-size: 28px;
            margin-bottom: 15px;
        }
        
        .welcome-date {
            font-size: 16px;
            opacity: 0.9;
        }
        
        .welcome-icon {
            font-size: 60px;
            float: right;
            opacity: 0.2;
            margin-top: -20px;
        }
    </style>
</head>
<body class="with-side-menu">

    <?php require_once("../MainHeader/header.php");?>

    <div class="mobile-menu-left-overlay"></div>
    
    <?php require_once("../MainNav/nav.php");?>

    <!-- Contenido -->
    <div class="page-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-xl-12">
                    <div class="welcome-card">
                        <i class="fa fa-home welcome-icon"></i>
                        <h1 class="welcome-title">¡Bienvenido(a), <?php echo $usuario; ?>!</h1>
                        <p class="welcome-date">Hoy es <?php echo strftime("%A, %d de %B de %Y", strtotime('today')); ?></p>
                        <p>Sistemas Cervalle. ¡Te deseamos un excelente día!</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Fin Contenido -->

    <?php require_once("../MainJs/js.php");?>
    
    <script>
        $(document).ready(function(){
            // Si necesitas alguna inicialización de JS aquí
        });
    </script>

</body>
</html>
<?php
  } else {
    header("Location:../index.php");
    exit();
  }
?>