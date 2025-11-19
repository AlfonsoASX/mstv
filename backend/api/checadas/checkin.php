<?php
/**
 * ===========================================
 *  API | CHECADAS → CHECK-IN (Entrada / Llegada)
 *  Proyecto: Seguridad Privada - ASX
 *  Archivo: backend/api/checadas/checkin.php
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

// 2️⃣ Recibir datos (JSON desde la app)
$data = Helpers::getJsonInput();

if (!Helpers::validarCampos($data, ['sitio_id', 'lat', 'lng', 'foto_base64'])) {
    Response::error("Faltan datos obligatorios.");
}

$sitio_id     = intval($data['sitio_id']);
$latGuardia   = floatval($data['lat']);
$lngGuardia   = floatval($data['lng']);
$comentario   = isset($data['comentario']) ? Helpers::cleanString($data['comentario']) : '';
$tipoChecada  = CHECADA_TIPOS['ENTRADA'];  // ENTRADA o LLEGADA según flujo

// 3️⃣ Obtener datos del sitio
$sqlSitio = "SELECT lat, lng, radio_metros FROM sitios WHERE id = :id AND activo = 1";
$stmt = $db->prepare($sqlSitio);
$stmt->execute(['id' => $sitio_id]);
$sitio = $stmt->fetch();

if (!$sitio) {
    Response::error("El sitio no existe o está inactivo");
}

// 4️⃣ Validar geocerca
$geoCheck = Geo::validarGeocerca(
    $latGuardia,
    $lngGuardia,
    $sitio['lat'],
    $sitio['lng'],
    $sitio['radio_metros']
);

$validado_geo = $geoCheck['dentro'] ? 1 : 0;

// 5️⃣ Procesar selfie: guardar imagen
$extension = Helpers::extensionBase64($data['foto_base64']);
$nombreFoto = Helpers::generarNombreArchivo($extension);
$rutaFoto = FOTOS_SELFIE_PATH . $nombreFoto;

Helpers::guardarBase64($data['foto_base64'], $rutaFoto);

// 6️⃣ Validar reconocimiento facial (si el guardia tiene foto base)
$sqlFotoBase = "SELECT foto_base FROM usuarios WHERE id = :id";
$stmt = $db->prepare($sqlFotoBase);
$stmt->execute(['id' => $user['id']]);
$userData = $stmt->fetch();

$validado_facial = 0;

if (!empty($userData['foto_base'])) {
    $fotoRegistro = FOTOS_REGISTRO_PATH . $userData['foto_base'];

    if (file_exists($fotoRegistro)) {
        $validado_facial = FaceRecognition::compareFaces($fotoRegistro, $rutaFoto) ? 1 : 0;
    }
}

// 7️⃣ Guardar checada en BD
$sqlInsert = "
    INSERT INTO checadas (
        guardia_id, sitio_id, tipo, fecha_hora,
        lat, lng, foto, validado_geo, validado_facial, comentario
    ) VALUES (
        :guardia_id, :sitio_id, :tipo, NOW(),
        :lat, :lng, :foto, :vgeo, :vface, :comentario
    )
";

$stmt = $db->prepare($sqlInsert);
$stmt->execute([
    'guardia_id' => $user['id'],
    'sitio_id'   => $sitio_id,
    'tipo'       => $tipoChecada,
    'lat'        => $latGuardia,
    'lng'        => $lngGuardia,
    'foto'       => $nombreFoto,
    'vgeo'       => $validado_geo,
    'vface'      => $validado_facial,
    'comentario' => $comentario
]);

$checada_id = $db->lastInsertId();

// 8️⃣ Respuesta final
Response::success("Checada registrada correctamente.", [
    'checada_id'      => $checada_id,
    'validado_geo'    => $validado_geo,
    'distancia_metros'=> round($geoCheck['distancia'], 2),
    'validado_facial' => $validado_facial,
    'foto_guardada'   => $nombreFoto
]);
