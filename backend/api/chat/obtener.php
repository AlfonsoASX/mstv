<?php
/**
 * ===========================================
 *  API | CHAT → OBTENER MENSAJES
 *  Proyecto: Seguridad Privada - ASX
 *  Archivo: backend/api/chat/obtener.php
 * ===========================================
 */

require_once __DIR__ . '/../../core/middleware.php';
require_once __DIR__ . '/../../core/response.php';
require_once __DIR__ . '/../../core/helpers.php';
require_once __DIR__ . '/../../config/database.php';

// 1️⃣ Autorización
$user = Middleware::secure([
    ROLES['GUARDIA'],
    ROLES['SUPERVISOR'],
    ROLES['RH'],
    ROLES['ADMIN']
]);

$db = Database::getInstance()->getConnection();

// 2️⃣ Recibir parámetros
$params = Helpers::cleanArray($_GET);
$where = [];
$sqlParams = [];

if (!empty($params['sitio_id'])) {
    $where[] = "cm.sitio_id = :sitio_id";
    $sqlParams['sitio_id'] = $params['sitio_id'];
}

if (!empty($params['emisor_id'])) { // chat individual
    $where[] = "cm.emisor_id = :emisor_id";
    $sqlParams['emisor_id'] = $params['emisor_id'];
}

// Límite de mensajes
$limit = (!empty($params['limit']) && is_numeric($params['limit']))
    ? intval($params['limit'])
    : 50;

$whereSql = $where ? "WHERE " . implode(" AND ", $where) : "";

// 3️⃣ Query de mensajes
$sql = "
    SELECT 
        cm.id,
        cm.sitio_id,
        cm.emisor_id,
        CONCAT(u.nombre, ' ', IFNULL(u.apellido,'')) AS emisor_nombre,
        u.rol AS emisor_rol,
        cm.moderado,
        cm.mensaje,
        cm.creado_en
    FROM chat_mensajes cm
    LEFT JOIN usuarios u ON cm.emisor_id = u.id
    $whereSql
    ORDER BY cm.creado_en DESC
    LIMIT $limit
";

$stmt = $db->prepare($sql);
$stmt->execute($sqlParams);

$resultado = $stmt->fetchAll();

// 4️⃣ Respuesta
if (!$resultado) {
    Response::success("No hay mensajes.", []);
}

Response::success("Mensajes encontrados", $resultado);
