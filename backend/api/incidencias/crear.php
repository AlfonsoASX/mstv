<?php
/**
 * ===========================================
 *  API | INCIDENCIAS → CREAR
 *  Proyecto: Seguridad Privada - ASX
 *  Archivo: backend/api/incidencias/crear.php
 * ===========================================
 */

require_once __DIR__ . '/../../core/middleware.php';
require_once __DIR__ . '/../../core/response.php';
require_once __DIR__ . '/../../core/helpers.php';
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/database.php';

// 1️⃣ Autenticación (Guardia, Supervisor, Admin)
$user = Middleware::secure([
    ROLES['GUARDIA'],
    ROLES['SUPERVISOR'],
    ROLES['ADMIN']
]);

$db = Database::getInstance()->getConnection();

// 2️⃣ Recibir datos del frontend (Flutter)
$data = Helpers::getJsonInput();

if (!Helpers::validarCampos($data, ['sitio_id', 'tipo', 'prioridad', 'descripcion'])) {
    Response::error("Faltan datos obligatorios: sitio_id, tipo, prioridad, descripcion.");
}

$sitio_id    = intval($data['sitio_id']);
$guardia_id  = $user['id'];
$tipo        = Helpers::cleanString($data['tipo']);
$prioridad   = Helpers::cleanString($data['prioridad']);
$descripcion = Helpers::cleanString($data['descripcion']);
$foto_base64 = !empty($data['foto_base64']) ? $data['foto_base64'] : null;

// 3️⃣ Validar catálogos (tipo y prioridad)
if (!in_array($tipo, INCIDENCIA_TIPOS)) {
    Response::error("Tipo de incidencia no válido.");
}
if (!in_array($prioridad, INCIDENCIA_PRIORIDAD)) {
    Response::error("Prioridad no válida.");
}

// 4️⃣ Guardar imagen si viene
$nombreFoto = null;

if ($foto_base64) {
    $ext = Helpers::extensionBase64($foto_base64);
    $nombreFoto = Helpers::generarNombreArchivo($ext);
    $rutaFoto = FOTOS_INCIDENCIAS_PATH . $nombreFoto;
    Helpers::guardarBase64($foto_base64, $rutaFoto);
}

// 5️⃣ Insertar en BD
$sql = "
    INSERT INTO incidencias 
    (guardia_id, sitio_id, tipo, prioridad, descripcion, foto, estado, creado_en)
    VALUES 
    (:guardia_id, :sitio_id, :tipo, :prioridad, :descripcion, :foto, 'pendiente', NOW())
";

$stmt = $db->prepare($sql);
$stmt->execute([
    'guardia_id'  => $guardia_id,
    'sitio_id'    => $sitio_id,
    'tipo'        => $tipo,
    'prioridad'   => $prioridad,
    'descripcion' => $descripcion,
    'foto'        => $nombreFoto
]);

$incidencia_id = $db->lastInsertId();

// 6️⃣ Hook: si prioridad es ALTA, disparar notificación externa
if ($prioridad === 'alta') {
    Helpers::log("Incidencia urgente ID $incidencia_id en sitio $sitio_id");

    // Aquí puedes conectar SMS/email/WhatsApp
    // sendSMS($numeroGerente, $mensaje);
    // sendEmail($correoAdmin, $mensaje);
}

// 7️⃣ Respuesta final
Response::success("Incidencia registrada exitosamente.", [
    'incidencia_id' => $incidencia_id,
    'foto_guardada' => $nombreFoto,
    'prioridad'     => $prioridad,
    'tipo'          => $tipo
]);
