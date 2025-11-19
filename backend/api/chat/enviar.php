<?php
/**
 * ===========================================
 *  API | CHAT → ENVIAR MENSAJE
 *  Proyecto: Seguridad Privada - ASX
 *  Archivo: backend/api/chat/enviar.php
 * ===========================================
 */

require_once __DIR__ . '/../../core/middleware.php';
require_once __DIR__ . '/../../core/response.php';
require_once __DIR__ . '/../../core/helpers.php';
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/database.php';

// 1️⃣ Autorización (casi todos pueden enviar chat)
$user = Middleware::secure([
    ROLES['GUARDIA'],
    ROLES['SUPERVISOR'],
    ROLES['RH'],
    ROLES['ADMIN']
]);

$db = Database::getInstance()->getConnection();

// 2️⃣ Recibir datos (POST JSON)
$data = Helpers::getJsonInput();

if (!Helpers::validarCampos($data, ['mensaje'])) {
    Response::error("Mensaje es requerido");
}

$mensajeOriginal = Helpers::cleanString($data['mensaje']);
$sitio_id = !empty($data['sitio_id']) ? intval($data['sitio_id']) : null;

// 3️⃣ Aplicar filtro anti-groserías (suavizado)
$mensajeFiltrado = self::filtrarGroserias($mensajeOriginal);

// 4️⃣ Guardar mensaje
$sql = "INSERT INTO chat_mensajes (sitio_id, emisor_id, mensaje, moderado)
        VALUES (:sitio_id, :emisor_id, :mensaje, :moderado)";
$stmt = $db->prepare($sql);

$moderadoFlag = ($mensajeOriginal !== $mensajeFiltrado) ? 1 : 0;

$stmt->execute([
    'sitio_id'  => $sitio_id,
    'emisor_id' => $user['id'],
    'mensaje'   => $mensajeFiltrado,
    'moderado'  => $moderadoFlag
]);

// 5️⃣ Responder
Response::success("Mensaje enviado", [
    'mensaje_original' => $mensajeOriginal,
    'mensaje_filtrado' => $mensajeFiltrado,
    'moderado' => $moderadoFlag
]);
 

/**
 * ===============================================
 *  FUNCIÓN DE FILTRADO ANTIGROSERÍAS
 * ===============================================
 */
function filtrarGroserias($texto)
{
    foreach (BAD_WORDS as $bad) {
        $regex = '/\b' . preg_quote($bad, '/') . '\b/i';
        $texto = preg_replace($regex, str_repeat('*', strlen($bad)), $texto);
    }
    return $texto;
}
