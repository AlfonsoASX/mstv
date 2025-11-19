<?php
/**
 * ===========================================
 * API | SUPERVISOR → MONITOREO EN TIEMPO REAL
 * Proyecto: Seguridad Privada - ASX
 * Archivo: backend/api/supervisor/monitoreo.php
 * ===========================================
 */

require_once __DIR__ . '/../../core/middleware.php';
require_once __DIR__ . '/../../core/response.php';
require_once __DIR__ . '/../../core/helpers.php';
require_once __DIR__ . '/../../config/database.php';

// 1️⃣ Permisos
$user = Middleware::secure([
    ROLES['SUPERVISOR'],
    ROLES['ADMIN']
]);

$db = Database::getInstance()->getConnection();

// 2️⃣ Recibir sitio_id opcional
$params = Helpers::cleanArray($_GET);

$where = [];
$exec  = [];

// Filtro por sitio manualmente si lo recibe
if (!empty($params['sitio_id'])) {
    $where[] = "gs.sitio_id = :sitio_id";
    $exec['sitio_id'] = intval($params['sitio_id']);
}

// Si es SUPERVISOR, solo ve sus guardias
if ($user['rol'] === ROLES['SUPERVISOR']) {
    $where[] = "gs.supervisor_id = :supervisor_id";
    $exec['supervisor_id'] = $user['id'];
}

$whereSQL = $where ? "WHERE ".implode(' AND ', $where) : "";

// 3️⃣ Query: Estado actual de guardias asignados
$sql = "
    SELECT
        u.id AS guardia_id,
        CONCAT(u.nombre,' ',IFNULL(u.apellido,'')) AS guardia_nombre,
        u.foto_base,
        s.id AS sitio_id,
        s.nombre AS sitio_nombre,

        -- Última checada registrada
        (
            SELECT fecha_hora 
            FROM checadas c 
            WHERE c.guardia_id = u.id
            ORDER BY fecha_hora DESC
            LIMIT 1
        ) AS ultima_checada,

        -- Tipo de la última checada (entrada/salida)
        (
            SELECT tipo 
            FROM checadas c 
            WHERE c.guardia_id = u.id
            ORDER BY fecha_hora DESC
            LIMIT 1
        ) AS tipo_ultima_checada,

        -- Foto del último registro
        (
            SELECT foto 
            FROM checadas c 
            WHERE c.guardia_id = u.id
            ORDER BY fecha_hora DESC
            LIMIT 1
        ) AS foto_checada,

        -- Incidencias activas
        (
            SELECT COUNT(*) 
            FROM incidencias i
            WHERE i.guardia_id = u.id
            AND i.estado = 'pendiente'
        ) AS incidencias_activas

    FROM guardias_sitios gs
    INNER JOIN usuarios u ON gs.guardia_id = u.id
    INNER JOIN sitios s   ON gs.sitio_id = s.id
    $whereSQL
    ORDER BY guardia_nombre ASC
";

$stmt = $db->prepare($sql);
$stmt->execute($exec);
$guardias = $stmt->fetchAll();

foreach ($guardias as &$g) {
    $g['en_turno'] = ($g['tipo_ultima_checada'] === 'entrada') ? 1 : 0;
}

// 4️⃣ Respuesta final
Response::success("Estado de monitoreo actualizado", $guardias);
