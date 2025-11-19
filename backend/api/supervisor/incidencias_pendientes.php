<?php
/**
 * ===========================================
 * API | SUPERVISOR → INCIDENCIAS PENDIENTES
 * Proyecto: Seguridad Privada - ASX
 * Archivo: backend/api/supervisor/incidencias_pendientes.php
 * ===========================================
 */

require_once __DIR__ . '/../../core/middleware.php';
require_once __DIR__ . '/../../core/response.php';
require_once __DIR__ . '/../../core/helpers.php';
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/database.php';

// 1️⃣ Autorización (Supervisor y Admin)
$user = Middleware::secure([
    ROLES['SUPERVISOR'],
    ROLES['ADMIN']
]);

$db = Database::getInstance()->getConnection();

// 2️⃣ Filtros opcionales
$params = Helpers::cleanArray($_GET);
$where  = [];
$exec   = [];

// Filtrar por sitio
if (!empty($params['sitio_id'])) {
    $where[] = "i.sitio_id = :sitio_id";
    $exec['sitio_id'] = intval($params['sitio_id']);
}

// Supervisor ve solo incidencias de guardias a su cargo
if ($user['rol'] === ROLES['SUPERVISOR']) {
    $where[] = "i.guardia_id IN (
                    SELECT guardia_id 
                    FROM guardias_sitios 
                    WHERE supervisor_id = :supervisor_id
                )";
    $exec['supervisor_id'] = $user['id'];
}

// Prioridad opcional (alta / media / baja)
if (!empty($params['prioridad']) && in_array($params['prioridad'], INCIDENCIA_PRIORIDAD)) {
    $where[] = "i.prioridad = :prioridad";
    $exec['prioridad'] = $params['prioridad'];
}

// Solo estados pendientes o atendidos
$where[] = "i.estado IN ('pendiente', 'atendido')";

$whereSQL = "WHERE " . implode(" AND ", $where);

// 3️⃣ Query de incidencias
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
    LEFT JOIN usuarios u ON u.id = i.guardia_id
    LEFT JOIN sitios s   ON s.id = i.sitio_id
    $whereSQL
    ORDER BY i.prioridad = 'alta' DESC, i.creado_en DESC
    LIMIT 200
";

$stmt = $db->prepare($sql);
$stmt->execute($exec);
$rows = $stmt->fetchAll();

// 4️⃣ Respuesta JSON
if (!$rows) {
    Response::success("No hay incidencias pendientes.", []);
}

Response::success("Incidencias pendientes encontradas.", $rows);
