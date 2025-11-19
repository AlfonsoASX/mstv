<?php
/**
 * ===========================================
 * API | TURNOS EXTRA → INICIAR
 * Proyecto: Seguridad Privada - ASX
 * Archivo: backend/api/turnos/extra_iniciar.php
 * ===========================================
 */

require_once __DIR__ . '/../../core/middleware.php';
require_once __DIR__ . '/../../core/response.php';
require_once __DIR__ . '/../../core/helpers.php';
require_once __DIR__ . '/../../core/geo.php';
require_once __DIR__ . '/../../core/face.php';
require_once __DIR__ . '/../../config/database.php';

// 1️⃣ Autenticación (solo guardias)
$user = Middleware::secure([ ROLES['GUARDIA'] ]);

$db = Database::getInstance()->getConnection();

// 2️⃣ Recibir datos
$data = Helpers::getJsonInput();

if (!Helpers::validarCampos($data, ['sitio_id', 'lat', 'lng', 'foto_base64'])) {
    Response::error("Faltan datos obligatorios: sitio_id, lat, lng, foto_base64.");
}

$sitio_id    = intval($data['sitio_id']);
$latGuardia  = floatval($data['lat']);
$lngGuardia  = floatval($data['lng']);
$comentario  = !empty($data['comentario']) ? Helpers::cleanString($data['comentario']) : '';


// 3️⃣ Validar que el guardia esté asignado a ese sitio
$sqlAsignacion = "
    SELECT 1 
    FROM guardias_sitios 
    WHERE guardia_id = :guardia_id AND sitio_id = :sitio_id
";
$stmt = $db->prepare($sqlAsignacion);
$stmt->execute(['guardia_id' => $user['id'], 'sitio_id' => $sitio_id]);

if (!$stmt->fetch()) {
    Response::error("Este guardia no está asignado a este sitio.");
}


// 4️⃣ Validar que NO haya un turno extra activo sin cerrar
$sqlActivo = "
    SELECT id 
    FROM turnos_extras 
    WHERE guardia_id = :guardia_id AND fin IS NULL 
    ORDER BY inicio DESC LIMIT 1
";
$stmt = $db->prepare($sqlActivo);
$stmt->execute(['guardia_id' => $user['id']]);

if ($stmt->fetch()) {
    Response::error("Ya tienes un turno extra activo. Debes cerrarlo antes de iniciar otro.");
}


// 5️⃣ Obtener datos del sitio para validar la geocerca
$sqlSitio = "SELECT lat, lng, radio_metros FROM sitios WHERE id = :id";
$stmt = $db->prepare($sqlSitio);
$stmt->execute(['id' => $sitio_id]);
$sitio = $stmt->fetch();

if (!$sitio) Response::error("Sitio no encontrado o inactivo.");


// 6️⃣ Validar geocerca
$geoCheck = Geo::validarGeocerca(
    $latGuardia,
    $lngGuardia,
    $sitio['lat'],
    $sitio['lng'],
    $sitio['radio_metros']
);
$validado_geo = $geoCheck['dentro'] ? 1 : 0;


// 7️⃣ Procesar selfie (guardar)
$ext        = Helpers::extensionBase64($data['foto_base64']);
$nombreFoto = Helpers::generarNombreArchivo($ext);
$rutaFoto   = FOTOS_SELFIE_PATH . $nombreFoto;

Helpers::guardarBase64($data['foto_base64'], $rutaFoto);


// 8️⃣ Validación facial
$sqlFoto = "SELECT foto_base FROM usuarios WHERE id = :id";
$stmt = $db->prepare($sqlFoto);
$stmt->execute(['id' => $user['id']]);
$userData = $stmt->fetch();

$validado_facial = 0;
if (!empty($userData['foto_base'])) {
    $fotoOriginal = FOTOS_REGISTRO_PATH . $userData['foto_base'];
    if (file_exists($fotoOriginal)) {
        $validado_facial = FaceRecognition::compareFaces($fotoOriginal, $rutaFoto) ? 1 : 0;
    }
}


// 9️⃣ Insertar turno extra
$sqlInsert = "
    INSERT INTO turnos_extras (
        guardia_id, sitio_id, inicio, lat_inicio, lng_inicio,
        foto_inicio, validado_geo_inicio, validado_facial_inicio, comentario
    ) VALUES (
        :guardia_id, :sitio_id, NOW(), :lat, :lng,
        :foto, :vgeo, :vface, :comentario
    )
";

$stmt = $db->prepare($sqlInsert);
$stmt->execute([
    'guardia_id' => $user['id'],
    'sitio_id'   => $sitio_id,
    'lat'        => $latGuardia,
    'lng'        => $lngGuardia,
    'foto'       => $nombreFoto,
    'vgeo'       => $validado_geo,
    'vface'      => $validado_facial,
    'comentario' => $comentario
]);

$turno_extra_id = $db->lastInsertId();


// 🔟 Respuesta final
Response::success("Turno extra iniciado correctamente.", [
    'turno_extra_id' => $turno_extra_id,
    'validado_geo'   => $validado_geo,
    'distancia_metros' => round($geoCheck['distancia'], 2),
    'validado_facial'=> $validado_facial,
    'foto_guardada'  => $nombreFoto
]);
