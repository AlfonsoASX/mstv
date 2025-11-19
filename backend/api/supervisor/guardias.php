<?php
/**
 * ===========================================
 * API | SUPERVISOR → LISTAR GUARDIAS
 * Proyecto: Seguridad Privada - ASX
 * Archivo: backend/api/supervisor/guardias.php
 * ===========================================
 */

require_once __DIR__ . '/../../core/middleware.php';
require_once __DIR__ . '/../../core/response.php';
require_once __DIR__ . '/../../core/helpers.php';
require_once __DIR__ . '/../../config/database.php';

// 1️⃣ Autorización — Supervisor, Admin o RH
$user = Middleware::secure([
    ROLES['SUPERVISOR'],
    ROLES['ADMIN'],
    ROLES['RH']
]);

$db = Database::getInstance()->getConnection();

// 2️⃣ Recibir filtros
$params = Helpers::cleanArray($_GET);
$where  = [];
$exec   = [];

// Filtrar por sitio
if (!empty($params['sitio_id'])) {
    $where[] = "gs.sitio_id = :sitio_id";
    $exec['sitio_id'] = intval($params['sitio_id']);
}

// Filtrar por supervisor responsable
if (!empty($params['supervisor_id'])) {
    $where[] = "gs.supervisor_id = :supervisor_id";
    $exec['supervisor_id'] = intval($params['supervisor_id']);
}

// Filtrar solo guardias activos
if (!empty($params['solo_activos']) && $params['solo_activos'] == 1) {
    $where[] = "u.activo = 1";
}

$whereSQL = $where ? "WHERE " . implode(" AND ", $where) : "";

// 3️⃣ Query principal
$sql = "
    SELECT 
        u.id,
        CONCAT(u.nombre, ' ', IFNULL(u.apellido, '')) AS nombre,
        u.foto_base,
        u.activo,
        s.nombre AS sitio_nombre,
        gs.sitio_id,
        gs.supervisor_id,

        -- Último registro de checada
        (
            SELECT MAX(fecha_hora) 
            FROM checadas c 
            WHERE c.guardia_id = u.id
        ) AS ultima_checada,

        -- Incidencias pendientes
        (
            SELECT COUNT(*) 
            FROM incidencias i 
            WHERE i.guardia_id = u.id 
              AND i.estado = 'pendiente'
        ) AS incidencias_pendientes

    FROM guardias_sitios gs
    INNER JOIN usuarios u ON gs.guardia_id = u.id
    INNER JOIN sitios s   ON gs.sitio_id = s.id
    $whereSQL
    ORDER BY u.nombre ASC
    LIMIT 200
";

$stmt = $db->prepare($sql);
$stmt->execute($exec);
$guardias = $stmt->fetchAll();

// 4️⃣ Respuesta
if (!$guardias) {
    Response::success("No hay guardias asignados.", []);
}

Response::success("Guardias encontrados", $guardias);
