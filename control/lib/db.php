<?php

$db_host = "ganas001.mysql.guardedhost.com";
$db_user = "ganas001_control";
$db_pass = "zV76(b5Hvn";
$db_name = "ganas001_asx";

$conexion = mysqli_connect($db_host, $db_user, $db_pass, $db_name);
if (!$conexion) {
    die("Error de conexión a la base de datos: " . mysqli_connect_error());
}
mysqli_set_charset($conexion, "utf8mb4");