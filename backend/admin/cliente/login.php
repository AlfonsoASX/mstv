<?php
/**
 * ===========================================
 * PORTAL CLIENTE → LOGIN
 * Proyecto: Seguridad Privada - ASX
 * Archivo: backend/admin/cliente/login.php
 * ===========================================
 */
session_start();

// Si ya hay sesión activa, redirigir
if (!empty($_SESSION['token'])) {
    header("Location: dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Portal Cliente - Login</title>
    <link rel="stylesheet" 
          href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">

    <style>
        body {
            background: #f4f6f9;
        }
        .login-card {
            max-width: 380px;
            margin: 8% auto;
            padding: 2rem;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>

<div class="login-card">
    <h4 class="text-center mb-4">Portal del Cliente</h4>
    
    <div id="errorMsg" class="alert alert-danger d-none"></div>
    
    <form id="loginForm">

        <div class="mb-3">
            <label class="form-label">Usuario</label>
            <input type="text" class="form-control" id="usuario" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Contraseña</label>
            <input type="password" class="form-control" id="password" required>
        </div>

        <button type="submit" class="btn btn-primary w-100">
            Ingresar
        </button>
    </form>
</div>

<script>
document.getElementById('loginForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    const usuario  = document.getElementById('usuario').value.trim();
    const password = document.getElementById('password').value.trim();

    if (!usuario || !password) {
        mostrarError("Ingrese usuario y contraseña.");
        return;
    }

    try {
        const resp = await fetch("../../api/login.php", {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ usuario, password })
        });

        const data = await resp.json();

        if (data.status === 'success') {
            // Guardar token en sesión vía PHP
            fetch('set_session.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ token: data.data.token })
            }).then(() => {
                window.location = 'dashboard.php';
            });
        } else {
            mostrarError(data.message || "Credenciales incorrectas.");
        }

    } catch (err) {
        mostrarError("Error de conexión con el servidor.");
    }
});

function mostrarError(msg) {
    const div = document.getElementById('errorMsg');
    div.textContent = msg;
    div.classList.remove('d-none');
}
</script>

</body>
</html>
