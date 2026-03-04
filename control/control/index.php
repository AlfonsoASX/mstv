<?php
// index.php
session_start();

/* =======================================
   CONFIGURACIÓN DE BASE DE DATOS
   ======================================= */
$db_host = "ganas001.mysql.guardedhost.com";
$db_user = "ganas001_control";
$db_pass = "zV76(b5Hvn";
$db_name = "ganas001_asx"; // donde está la tabla usuarios/roles

/* =======================================
   FUNCIÓN DE CONEXIÓN
   ======================================= */
$conexion = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

if (!$conexion) {
    die("Error de conexión a la base de datos: " . mysqli_connect_error());
}

/* =======================================
   FUNCIÓN: LIMPIAR TEXTO
   ======================================= */
function limpiar($txt) {
    return trim(htmlspecialchars($txt, ENT_QUOTES, 'UTF-8'));
}

/* =======================================
   SI YA HAY SESIÓN, CARGA DASHBOARD
   ======================================= */
if (isset($_SESSION['usuario_id']) && !empty($_SESSION['usuario_id'])) {
    // El usuario ya está logueado, cargamos dashboard
    include "dashboard.php";
    exit;
}

/* =======================================
   MANEJO DE INTENTO DE LOGIN
   ======================================= */
$mensaje_error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Espera variables 'user' y 'password'
    $user = isset($_POST['user']) ? limpiar($_POST['user']) : "";
    $password = isset($_POST['password']) ? $_POST['password'] : "";

    if ($user !== "" && $password !== "") {

        // Buscar usuario en BD
        $sql = "
            SELECT u.id,
                   u.usuario,
                   u.contrasena_hash,
                   u.email,
                   u.rol_id,
                   u.esta_activo,
                   r.nombre AS rol_nombre,
                   r.descripcion AS rol_descripcion
            FROM usuarios u
            INNER JOIN roles r ON r.id = u.rol_id
            WHERE u.usuario = ?
            LIMIT 1
        ";

        if ($stmt = mysqli_prepare($conexion, $sql)) {
            mysqli_stmt_bind_param($stmt, "s", $user);
            mysqli_stmt_execute($stmt);
            $resultado = mysqli_stmt_get_result($stmt);

            if ($fila = mysqli_fetch_assoc($resultado)) {
                // Verificar que esté activo
                if ((int)$fila['esta_activo'] !== 1) {
                    $mensaje_error = "Usuario inactivo, contacta al administrador.";
                } else {
                    // Verificar contraseña
                    // Asegúrate de que contrasena_hash tenga hashes generados con password_hash()
                    if (password_verify($password, $fila['contrasena_hash'])) {

                        // Login correcto: guardar datos en sesión
                        $_SESSION['usuario_id']       = $fila['id'];
                        $_SESSION['usuario']          = $fila['usuario'];
                        $_SESSION['email']            = $fila['email'];
                        $_SESSION['rol_id']           = $fila['rol_id'];
                        $_SESSION['rol_nombre']       = $fila['rol_nombre'];
                        $_SESSION['rol_descripcion']  = $fila['rol_descripcion'];
                        $_SESSION['ultimo_login']     = date('Y-m-d H:i:s');

                        // Opcional: actualizar ultimo_acceso en BD
                        $update_sql = "UPDATE usuarios SET ultimo_acceso = NOW() WHERE id = ?";
                        if ($update_stmt = mysqli_prepare($conexion, $update_sql)) {
                            mysqli_stmt_bind_param($update_stmt, "i", $fila['id']);
                            mysqli_stmt_execute($update_stmt);
                            mysqli_stmt_close($update_stmt);
                        }

                        mysqli_stmt_close($stmt);
                        mysqli_close($conexion);

                        // Redirigir a dashboard
                        header("Location: dashboard.php");
                        exit;

                    } else {
                        // Contraseña incorrecta
                        $mensaje_error = "Usuario o contraseña incorrectos.";
                    }
                }
            } else {
                // Usuario no encontrado
                $mensaje_error = "Usuario o contraseña incorrectos.";
            }

            mysqli_stmt_close($stmt);

        } else {
            $mensaje_error = "Error interno al preparar la consulta.";
        }

    } else {
        $mensaje_error = "Ingresa usuario y contraseña.";
    }
}

/* =======================================
   SI LLEGAMOS HASTA AQUÍ:
   - No hay sesión, o
   - Falló el login
   -> Mostrar login.php
   ======================================= */

// Puedes hacer que login.php reciba $mensaje_error
// por variable global o incluirlo y usarla directamente.
?>
<?php
// Si quieres que login.php use el mensaje, puedes definir una variable global:
$__mensaje_error_login = $mensaje_error;

// Mostrar la vista de login
include "login.php";
