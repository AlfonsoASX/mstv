<?php
/**
 * API: Actualizar datos del usuario (perfil)
 * Endpoint: POST backend/api/usuario/actualizar.php
 * Campos esperados:
 *  - usuario_id (INT, obligatorio o token)
 *  - telefono (string, opcional)
 *  - email (string, opcional)
 *  - foto (archivo opcional)
 */

require_once '../../core/auth.php';
require_once '../../core/database.php';
require_once '../../core/response.php';
require_once '../../core/helpers.php';

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

/* ========= 1) Autenticación ========= */
$auth = new Auth();
$current_user = $auth->check(); // Verifica token y retorna datos del usuario

if (!$current_user) {
    Response::error('No autorizado', 401);
}
$usuarioId = $current_user['id'];

/* ========= 2) Validación de entrada ========= */
$telefono = $_POST['telefono'] ?? null;
$email    = $_POST['email'] ?? null;

/* ========= 3) Procesar foto (si existe) ========= */
$foto_guardada = null;

if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
    $ext = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
    $nombreArchivo = 'foto_' . $usuarioId . '_' . time() . '.' . $ext;

    $rutaDestino = '../../uploads/perfiles/' . $nombreArchivo;
    if (!is_dir('../../uploads/perfiles')) {
        mkdir('../../uploads/perfiles', 0777, true);
    }

    if (move_uploaded_file($_FILES['foto']['tmp_name'], $rutaDestino)) {
        $foto_guardada = 'uploads/perfiles/' . $nombreArchivo; // Ruta accesible
    } else {
        Response::error('Error al guardar la imagen', 500);
    }
}

/* ========= 4) Actualizar en la BD ========= */
try {
    $db = new Database();
    $conn = $db->getConnection();

    $sql = "UPDATE usuarios 
            SET telefono = :telefono, email = :email";

    if ($foto_guardada) {
        $sql .= ", foto_base = :foto_base";
    }
    $sql .= ", actualizado_en = NOW() WHERE id = :id";

    $stmt = $conn->prepare($sql);
    $stmt->bindValue(':telefono', $telefono);
    $stmt->bindValue(':email', $email);
    $stmt->bindValue(':id', $usuarioId, PDO::PARAM_INT);

    if ($foto_guardada) {
        $stmt->bindValue(':foto_base', $foto_guardada);
    }

    $stmt->execute();

    /* ====== 5) Registrar en bitácora ====== */
    logBitacora($conn, $usuarioId, 'Actualizar Perfil', 'usuarios', $usuarioId, json_encode($_POST));

    Response::success('Perfil actualizado correctamente', [
        'foto' => $foto_guardada
    ]);

} catch (Exception $e) {
    Response::error('Error en BD: ' . $e->getMessage(), 500);
}
