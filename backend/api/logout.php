<?php
/**
 * ===========================================
 * API | LOGOUT → CERRAR SESIÓN
 * Proyecto: Seguridad Privada - ASX
 * Archivo: backend/api/logout.php
 * ===========================================
 */

require_once __DIR__ . '/../core/middleware.php';
require_once __DIR__ . '/../core/response.php';
require_once __DIR__ . '/../core/helpers.php';

// 1️⃣ Validar token y obtener usuario autenticado
$user = Middleware::secure([
    ROLES['GUARDIA'],
    ROLES['SUPERVISOR'],
    ROLES['ADMIN'],
    ROLES['RH'],
    ROLES['NOMINA']
]);

// 2️⃣ Registrar en bitácora (no invalida token, solo deja evidencia)
Helpers::logBitacora(
    $user['id'],
    'logout',
    'usuarios',
    $user['id'],
    'Cierre de sesión'
);

// 3️⃣ Responder (el frontend debe borrar el token)
Response::success("Sesión cerrada correctamente. El token debe eliminarse en el cliente.", [
    "usuario_id" => $user['id'],
    "rol" => $user['rol']
]);
