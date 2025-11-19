<?php
/**
 * ===========================================
 * API | TURNOS EXTRA → CERRAR
 * Proyecto: Seguridad Privada - ASX
 * Archivo: backend/api/turnos/extra_cerrar.php
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

if (!Helpers::validarCampos($data, ['turno_extra_id', 'lat', 'lng', 'foto_base64'])) {
    Response::error("Faltan datos obligatorios: turno_extra_id, lat, lng, foto_base64.");
}

$turno_extra_id = intval($data['turno_extra_id']);
$latGuardia     = floatval($data['lat']);
$lngGuardia     = floatval($data['lng']);
$comentario     = isset($data['comentario']) ? Helpers::cleanString($data['comentario']) : '';


// 3️⃣ Validar que exista turno extra sin cerrar
$sql = "
    SELECT te.*, s.lat, s.lng, s.radio_metros
    FROM turnos_extras te
    INNER JOIN sitios s ON te.sitio_id = s.id
    WHERE te.id = :id AND te.guardia_id = :guardia_id AND te.fin IS NULL
";
$stmt = $db->prepare($sql);
$stmt->execute([
    'id'         => $turno_extra_id,
    'guardia_id' => $user['id']
]);

$turno = $stmt->fetch();
if (!$turno) Response::error("No existe un turno extra activo para este guardia.");


// 4️⃣ Validar geocerca
$geoCheck = Geo::validarGeocerca(
    $latGuardia,
    $lngGuardia,
    $turno['lat'],
    $turno['lng'],
    $turno['radio_metros']
);
$validado_geo = $geoCheck['dentro'] ? 1 : 0;


// 5️⃣ Guardar selfie de salida
$ext       = Helpers::extensionBase64($data['foto_base64']);
$nombreFoto = Helpers::generarNombreArchivo($ext);
$rutaFoto   = FOTOS_SELFIE_PATH . $nombreFoto;

Helpers::guardarBase64($data['foto_base64'], $rutaFoto);


// 6️⃣ Validar reconocimiento facial
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


// 7️⃣ Calcular horas trabajadas (desde inicio hasta ahora)
$sqlHoras = "
    SELECT TIMESTAMPDIFF(HOUR, inicio, NOW()) AS horas,
           TIMESTAMPDIFF(MINUTE, inicio, NOW()) AS minutos
    FROM turnos_extras
    WHERE id = :id
";
$stmt = $db->prepare($sqlHoras);
$stmt->execute(['id' => $turno_extra_id]);
$tiempo = $stmt->fetch();

$horas  = (int)$tiempo['horas'];
$minutos = (int)$tiempo['minutos'];


// 8️⃣ Registrar cierre del turno extra
$sqlUpdate = "
    UPDATE turnos_extras
    SET 
        fin = NOW(),
        horas_extra = :horas,
        minutos_extra = :minutos,
        foto_salida = :foto,
        validado_geo_salida = :vgeo,
        validado_facial_salida = :vface,
        comentario_salida = :comentario
    WHERE id = :id
";
$stmt = $db->prepare($sqlUpdate);
$stmt->execute([
    'horas'    => $horas,
    'minutos'  => $minutos,
    'foto'     => $nombreFoto,
    'vgeo'     => $validado_geo,
    'vface'    => $validado_facial,
    'comentario' => $comentario,
    'id'       => $turno_extra_id
]);


// 9️⃣ Respuesta
Response::success("Turno extra cerrado correctamente.", [
    'turno_extra_id' => $turno_extra_id,
    'horas_extra'    => $horas,
    'minutos_extra'  => $minutos,
    'validado_geo'   => $validado_geo,
    'validado_facial'=> $validado_facial,
    'foto_salida'    => $nombreFoto,
    'distancia_metros'=> round($geoCheck['distancia'], 2)
]);
