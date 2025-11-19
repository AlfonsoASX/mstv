<?php
/**
 * ===============================================
 * AUTH GUARD → PROTECCIÓN PARA VISTAS PHP (no APIs)
 * Controla acceso usando $_SESSION + token HMAC
 * ===============================================
 */

session_start();
require_once __DIR__ . '/../../core/helpers.php';
require_once __DIR__ . '/../../config/constants.php';

// 1️⃣ Validar que exista SESSION y Token
if (empty($_SESSION['token'])) {
    header("Location: ../cliente/login.php");
    exit;
}

// 2️⃣ Validar y decodificar token
$token = $_SESSION['token'];
$payload = Helpers::verificarTokenSeguro($token); // Regresa array si válido o false

if (!$payload) {
    session_destroy();
    header("Location: ../cliente/login.php?e=session_expired");
    exit;
}

// 3️⃣ Validar expiración
if (isset($payload['exp']) && $payload['exp'] < time()) {
    session_destroy();
    header("Location: ../cliente/login.php?e=expired");
    exit;
}

// 4️⃣ Verificar rol permitido (ajustar según contexto)
if (!in_array($payload['rol'], ['cliente', 'CLIENTE'])) {
    header("Location: ../cliente/login.php?e=unauthorized");
    exit;
}

// 5️⃣ Inyectar datos del usuario para usar en la vista
$AUTH_USER = [
    'id'   => $payload['id'],
    'rol'  => $payload['rol'],
    'exp'  => $payload['exp'],
    'nombre' => $payload['nombre'] ?? '',
    'email'  => $payload['email'] ?? '',
];

// Opcional: mantener token y payload fresco
$_SESSION['token_payload'] = $AUTH_USER;
