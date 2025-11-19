<?php
/**
 * ===============================================
 * LOGIN PANEL ADMINISTRATIVO
 * Archivo: backend/admin/login.php
 * ===============================================
 */

session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../core/helpers.php';

$error = "";

// Si ya está logueado, lo mandamos al dashboard
if (!empty($_SESSION['admin_id'])) {
    header("Location: dashboard.php");
    exit;
}

// Si vienen datos de formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $usuario = trim($_POST['usuario'] ?? '');
    $password = trim($_POST['password'] ?? '');

    // Validaciones mínimas
    if (!$usuario || !$password) {
        $error = "Ingrese usuario y contraseña.";
    } else {

        $db = Database::getInstance()->getConnection();

        // Buscar usuario
        $stmt = $db->prepare("SELECT id, usuario, password, rol, activo, intentos_fallidos
                              FROM usuarios 
                              WHERE usuario = :u AND rol IN ('admin','rh','nomina')");
        $stmt->execute(['u' => $usuario]);
        $user = $stmt->fetch();

        if (!$user) {
            $error = "Credenciales incorrectas.";
        } else {
            // Verificar si está bloqueado por intentos
            if ($user['intentos_fallidos'] >= MAX_LOGIN_ATTEMPTS) {
                $error = "Usuario bloqueado por múltiples intentos. Contacte al administrador.";
            } 
            elseif (!$user['activo']) {
                $error = "Usuario inactivo.";
            } 
            else if (password_verify($password, $user['password'])) {

                // Login correcto
                $db->prepare("UPDATE usuarios SET intentos_fallidos = 0 WHERE id = :id")
                   ->execute(['id' => $user['id']]);

                session_regenerate_id(true);
                $_SESSION['admin_id'] = $user['id'];
                $_SESSION['admin_rol'] = $user['rol'];
                $_SESSION['admin_name'] = $user['usuario'];

                header("Location: dashboard.php");
                exit;
            } 
            else {
                // Contraseña incorrecta → aumenta intentos
                $db->prepare("UPDATE usuarios 
                              SET intentos_fallidos = intentos_fallidos + 1 
                              WHERE id = :id")
                   ->execute(['id' => $user['id']]);
                $error = "Credenciales incorrectas.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Login Administrativo</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" 
      rel="stylesheet">
<style>
    body {
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 100vh;
        background: #f5f6fa;
    }
    .login-card {
        max-width: 400px;
        width: 100%;
        padding: 30px;
        border-radius: 10px;
        box-shadow: 0 0 12px rgba(0,0,0,0.1);
        background: #fff;
    }
</style>
</head>
<body>

<div class="login-card">

    <h4 class="text-center mb-4">Panel de Administración</h4>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <form method="POST">

        <div class="mb-3">
            <label>Usuario</label>
            <input type="text" name="usuario" class="form-control" required>
        </div>

        <div class="mb-3">
            <label>Contraseña</label>
            <input type="password" name="password" class="form-control" required>
        </div>

        <button class="btn btn-primary w-100 mt-2">Ingresar</button>

    </form>

    <div class="mt-3 text-center">
        <a href="#" class="small text-muted">¿Olvidó su contraseña?</a>
    </div>

</div>

</body>
</html>
