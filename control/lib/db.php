<?php




$db_host = "localhost";
$db_user = "veron119_mstv";
$db_pass = 's$Kxze,gL_ri';
$db_name = "veron119_mstv";



$conexion = mysqli_connect($db_host, $db_user, $db_pass, $db_name);
if (!$conexion) {
    echo 'err:db'. mysqli_connect_error();
    die("Error de conexión a la base de datos: " . mysqli_connect_error());
}


mysqli_set_charset($conexion, "utf8mb4");