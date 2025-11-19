<?php
/**
 * ===========================================
 * API | LOGIN → AUTENTICACIÓN
 * Proyecto: Seguridad Privada - ASX
 * Archivo: backend/api/login.php
 * ===========================================
 */

require_once __DIR__ . '/../core/helpers.php';
require_once __DIR__ . '/../core/response.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';

// 1️⃣ Recibir credenciales
$data = Helpers::getJsonInput();

if (!Helpers::validarCampos($data, ['usuario', 'password'])) {
    Response::error("Usuario y contraseña son obligatorios.");
}

$usuario  = Helpers::cleanString($data['usuario']);
$password = $data['password'];

$db = Database::getInstance()->getConnection();

// 2️⃣ Verificar usuario en la BD
$sql = "SELECT id, usuario, password, nombre, apellido, rol, activo, foto_base 
        FROM usuarios 
        WHERE usuario = :usuario LIMIT 1";
$stmt = $db->prepare($sql);
$stmt->execute(['usuario' => $usuario]);
$user = $stmt->fetch();

if (!$user) {
    Response::error("Credenciales incorrectas.");
}

// 3️⃣ Verificar que esté activo
if (!$user['activo']) {
    Response::error("Usuario deshabilitado, consulte con administración.");
}

// 4️⃣ Validar contraseña (hash)
if (!password_verify($password, $user['password'])) {
    Response::error("Credenciales incorrectas.");
}

// 5️⃣ Generar Token Seguro (simple HMAC firmado)
$payload = [
    'id'   => $user['id'],
    'rol'  => $user['rol'],
    'exp'  => time() + (60 * 60 * 24), // expira en 24h
];

$token = Helpers::crearTokenSeguro($payload); // función implementada en helpers.php

// 6️⃣ Registrar login en bitácora (opcional)
Helpers::logBitacora($user['id'], 'login', 'usuarios', $user['id'], 'Inicio de sesión exitoso');

// 7️⃣ Respuesta con datos del usuario y token
Response::success("Autenticación correcta.", [
    'token' => $token,
    'usuario' => [
        'id'        => $user['id'],
        'nombre'    => $user['nombre'],
        'apellido'  => $user['apellido'],
        'rol'       => $user['rol'],
        'foto_base' => $user['foto_base'],
    ]
]);
