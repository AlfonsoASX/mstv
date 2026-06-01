<?php




$db_host = "50.6.138.29";
$db_user = "veron119_mstv";
$db_pass = "s$Kxze,gL_ri";
$db_name = "veron119_mstv";



$conexion = mysqli_connect($db_host, $db_user, $db_pass, $db_name);
if (!$conexion) {
    die("Error de conexión a la base de datos: " . mysqli_connect_error());
}
mysqli_set_charset($conexion, "utf8mb4");