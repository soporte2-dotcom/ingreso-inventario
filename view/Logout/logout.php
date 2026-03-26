<?php
    require_once("../../config/conexionserver.php");
    session_destroy();
    header("Location: ../../index.php");
    exit();
?>