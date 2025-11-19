<?php
/**
 * ===========================================
 *  API | CAPACITACIÓN → LISTAR VIDEOS
 *  Proyecto: Seguridad Privada - ASX
 *  Archivo: backend/api/capacitacion/videos.php
 * ===========================================
 */

require_once __DIR__ . '/../../core/middleware.php';
require_once __DIR__ . '/../../core/response.php';
require_once __DIR__ . '/../../config/database.php';

// Autorización: Guardias, Supervisores, Admin
$user = Middleware::secure([
    ROLES['GUARDIA'],
    ROLES['SUPERVISOR'],
    ROLES['ADMIN']
]);

$db = Database::getInstance()->getConnection();

// Buscar todos los videos y marcar si el usuario los ha visto
$sql = "
    SELECT 
        v.id,
        v.titulo,
        v.url_video,
        v.obligatorio,
        IF(cv.id IS NOT NULL, 1, 0) AS visto,
        cv.visto_en
    FROM capacitacion_videos v
    LEFT JOIN capacitacion_vistos cv
        ON cv.video_id = v.id AND cv.guardia_id = :guardia_id
    ORDER BY v.id ASC
";

$stmt = $db->prepare($sql);
$stmt->execute(['guardia_id' => $user['id']]);
$videos = $stmt->fetchAll();

// Respuesta
if (!$videos) {
    Response::success("No hay videos configurados.", []);
}

Response::success("Videos de capacitación encontrados", $videos);
