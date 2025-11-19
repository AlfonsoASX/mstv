<?php
/**
 * ===========================================
 *  API | BITÁCORA → LISTAR REGISTROS
 *  Proyecto: Seguridad Privada - ASX
 *  Archivo: backend/api/bitacora/listar.php
 * ===========================================
 */

require_once __DIR__ . '/../../core/middleware.php';
require_once __DIR__ . '/../../core/response.php';
require_once __DIR__ . '/../../core/helpers.php';
require_once __DIR__ . '/../../config/database.php';


// 1️⃣ Validar token (solo ciertos roles pueden ver bitácora)
$user = Middleware::secure([
    ROLES['ADMIN'],
    ROLES['SUPERVISOR'],
    ROLES['RH'],
    ROLES['NOMINA']
]);

$db = Database::getInstance()->getConnection();

// 2️⃣ Recibir filtros opcionales
$filtros = Helpers::cleanArray($_GET);
$where = [];
$params = [];

// Filtrar por usuario (id de usuario que generó acción)
if (!empty($filtros['usuario_id'])) {
    $where[] = "b.usuario_id = :usuario_id";
    $params['usuario_id'] = $filtros['usuario_id'];
}

// Filtrar por entidad (ejemplo: 'checada', 'incidencia', etc.)
if (!empty($filtros['entidad'])) {
    $where[] = "b.entidad = :entidad";
    $params['entidad'] = $filtros['entidad'];
}

// Filtrar por rango de fechas
if (!empty($filtros['desde'])) {
    $where[] = "DATE(b.creado_en) >= :desde";
    $params['desde'] = $filtros['desde'];
}
if (!empty($filtros['hasta'])) {
    $where[] = "DATE(b.creado_en) <= :hasta";
    $params['hasta'] = $filtros['hasta'];
}

// Combinar filtros
$where_sql = $where ? "WHERE " . implode(' AND ', $where) : "";

// 3️⃣ Ejecutar consulta
$sql = "
    SELECT 
        b.id,
        b.usuario_id,
        CONCAT(u.nombre, ' ', u.apellido) AS usuario_nombre,
        b.accion,
        b.entidad,
        b.entidad_id,
        b.detalles,
        b.creado_en
    FROM bitacora b
    LEFT JOIN usuarios u ON b.usuario_id = u.id
    $where_sql
    ORDER BY b.creado_en DESC
    LIMIT 200
";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$bitacora = $stmt->fetchAll();

// 4️⃣ Respuesta
if (!$bitacora) {
    Response::success("Sin registros en bitácora.", []);
}

Response::success("Registros encontrados", $bitacora);
