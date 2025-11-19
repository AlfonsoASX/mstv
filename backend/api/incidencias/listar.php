<?php
/**
 * ===========================================
 *  API | INCIDENCIAS → LISTAR
 *  Proyecto: Seguridad Privada - ASX
 *  Archivo: backend/api/incidencias/listar.php
 * ===========================================
 */

require_once __DIR__ . '/../../core/middleware.php';
require_once __DIR__ . '/../../core/response.php';
require_once __DIR__ . '/../../core/helpers.php';
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/database.php';

// 1️⃣ Autenticación
$user = Middleware::secure([
    ROLES['GUARDIA'],
    ROLES['SUPERVISOR'],
    ROLES['ADMIN'],
    ROLES['RH']
]);

$db = Database::getInstance()->getConnection();

// 2️⃣ Recibir filtros opcionales
$params = Helpers::cleanArray($_GET);
$where  = [];
$exec   = [];

// Guardia: solo sus incidencias
if ($user['rol'] === ROLES['GUARDIA']) {
    $where[]      = "i.guardia_id = :mi_id";
    $exec['mi_id'] = $user['id'];
}

// sitio_id
if (!empty($params['sitio_id'])) {
    $where[]       = "i.sitio_id = :sitio_id";
    $exec['sitio_id'] = intval($params['sitio_id']);
}

// guardia_id (solo admin o supervisor)
if (!empty($params['guardia_id']) && $user['rol'] !== ROLES['GUARDIA']) {
    $where[]       = "i.guardia_id = :guardia_id";
    $exec['guardia_id'] = intval($params['guardia_id']);
}

// estado
if (!empty($params['estado']) && in_array($params['estado'], INCIDENCIA_ESTADOS)) {
    $where[]       = "i.estado = :estado";
    $exec['estado'] = $params['estado'];
}

// prioridad
if (!empty($params['prioridad']) && in_array($params['prioridad'], INCIDENCIA_PRIORIDAD)) {
    $where[]       = "i.prioridad = :prioridad";
    $exec['prioridad'] = $params['prioridad'];
}

// rango de fechas
if (!empty($params['desde'])) {
    $where[]       = "DATE(i.creado_en) >= :desde";
    $exec['desde'] = $params['desde'];
}
if (!empty($params['hasta'])) {
    $where[]       = "DATE(i.creado_en) <= :hasta";
    $exec['hasta'] = $params['hasta'];
}

// Combinar filtros
$whereSQL = $where ? "WHERE " . implode(" AND ", $where) : "";

// 3️⃣ Query final
$sql = "
    SELECT 
        i.id,
        i.sitio_id,
        s.nombre AS sitio_nombre,
        i.guardia_id,
        CONCAT(u.nombre,' ',IFNULL(u.apellido,'')) AS guardia_nombre,
        i.tipo,
        i.prioridad,
        i.descripcion,
        i.foto,
        i.estado,
        i.creado_en
    FROM incidencias i
    LEFT JOIN usuarios u ON i.guardia_id = u.id
    LEFT JOIN sitios s   ON i.sitio_id = s.id
    $whereSQL
    ORDER BY i.creado_en DESC
    LIMIT 200
";

$stmt = $db->prepare($sql);
$stmt->execute($exec);
$incidencias = $stmt->fetchAll();

// 4️⃣ Respuesta
if (!$incidencias) {
    Response::success("No hay incidencias con esos filtros.", []);
}

Response::success("Incidencias encontradas.", $incidencias);
