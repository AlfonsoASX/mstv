<?php
require_once __DIR__ . '/lib/app.php';

app_require_session();
app_require_page_permission('usuarios-lista.php');

header('Content-Type: application/json; charset=UTF-8');

function json_response(array $payload, int $code = 200): void
{
    http_response_code($code);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function post_text(string $key): string
{
    return trim((string)($_POST[$key] ?? ''));
}

function post_int(string $key): int
{
    return (int)($_POST[$key] ?? 0);
}

function db_scalar(mysqli $conexion, string $sql, string $types = '', array $params = [])
{
    if (!$stmt = mysqli_prepare($conexion, $sql)) {
        json_response(['error' => 'No fue posible preparar la consulta.'], 500);
    }

    if ($types !== '') {
        $bindParams = [$types];
        foreach ($params as $index => $value) {
            $params[$index] = $value;
            $bindParams[] = &$params[$index];
        }
        mysqli_stmt_bind_param($stmt, ...$bindParams);
    }

    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = $result ? mysqli_fetch_row($result) : null;
    if ($result) {
        mysqli_free_result($result);
    }
    mysqli_stmt_close($stmt);

    return $row[0] ?? null;
}

function role_exists(mysqli $conexion, int $rolId): bool
{
    return $rolId > 0 && (int)db_scalar($conexion, 'SELECT COUNT(*) FROM roles WHERE id = ?', 'i', [$rolId]) > 0;
}

function username_exists(mysqli $conexion, string $usuario, int $excludeId = 0): bool
{
    if ($excludeId > 0) {
        return (int)db_scalar(
            $conexion,
            'SELECT COUNT(*) FROM usuarios WHERE usuario = ? AND id <> ?',
            'si',
            [$usuario, $excludeId]
        ) > 0;
    }

    return (int)db_scalar($conexion, 'SELECT COUNT(*) FROM usuarios WHERE usuario = ?', 's', [$usuario]) > 0;
}

function require_post(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        json_response(['error' => 'Método no permitido.'], 405);
    }
}

$action = (string)($_GET['action'] ?? '');

try {
    switch ($action) {
        case 'get':
            $id = (int)($_GET['id'] ?? 0);
            if ($id <= 0) {
                json_response(['error' => 'Falta identificar al usuario.'], 400);
            }

            $sql = 'SELECT id, usuario, email, rol_id, esta_activo FROM usuarios WHERE id = ? LIMIT 1';
            if (!$stmt = mysqli_prepare($conexion, $sql)) {
                json_response(['error' => 'No fue posible consultar el usuario.'], 500);
            }

            mysqli_stmt_bind_param($stmt, 'i', $id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $row = $result ? mysqli_fetch_assoc($result) : null;
            if ($result) {
                mysqli_free_result($result);
            }
            mysqli_stmt_close($stmt);

            if (!$row) {
                json_response(['error' => 'Usuario no encontrado.'], 404);
            }

            json_response([
                'id' => (int)$row['id'],
                'usuario' => (string)$row['usuario'],
                'email' => (string)($row['email'] ?? ''),
                'rol_id' => (int)$row['rol_id'],
                'esta_activo' => (int)$row['esta_activo'],
            ]);

        case 'crear':
            require_post();

            $usuario = post_text('usuario');
            $email = post_text('email');
            $rolId = post_int('rol_id');
            $password = (string)($_POST['password'] ?? '');
            $estaActivo = isset($_POST['esta_activo']) ? 1 : 0;

            if ($usuario === '' || $rolId <= 0 || trim($password) === '') {
                json_response(['error' => 'Usuario, rol y contraseña son obligatorios.'], 400);
            }

            if (!role_exists($conexion, $rolId)) {
                json_response(['error' => 'El rol seleccionado no existe.'], 400);
            }

            if (username_exists($conexion, $usuario)) {
                json_response(['error' => 'Ya existe un usuario con ese nombre.'], 409);
            }

            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $emailValue = $email === '' ? null : $email;
            $sql = 'INSERT INTO usuarios (usuario, contrasena_hash, email, rol_id, esta_activo) VALUES (?, ?, ?, ?, ?)';
            if (!$stmt = mysqli_prepare($conexion, $sql)) {
                json_response(['error' => 'No fue posible preparar el alta del usuario.'], 500);
            }

            mysqli_stmt_bind_param($stmt, 'sssii', $usuario, $passwordHash, $emailValue, $rolId, $estaActivo);
            $ok = mysqli_stmt_execute($stmt);
            $error = mysqli_stmt_error($stmt);
            mysqli_stmt_close($stmt);

            if (!$ok) {
                json_response(['error' => $error ?: 'No fue posible crear el usuario.'], 500);
            }

            json_response(['success' => true]);

        case 'editar':
            require_post();

            $id = post_int('id');
            $usuario = post_text('usuario');
            $email = post_text('email');
            $rolId = post_int('rol_id');
            $password = (string)($_POST['password'] ?? '');
            $estaActivo = isset($_POST['esta_activo']) ? 1 : 0;

            if ($id <= 0 || $usuario === '' || $rolId <= 0) {
                json_response(['error' => 'Usuario y rol son obligatorios.'], 400);
            }

            if ((int)db_scalar($conexion, 'SELECT COUNT(*) FROM usuarios WHERE id = ?', 'i', [$id]) <= 0) {
                json_response(['error' => 'Usuario no encontrado.'], 404);
            }

            if (!role_exists($conexion, $rolId)) {
                json_response(['error' => 'El rol seleccionado no existe.'], 400);
            }

            if (username_exists($conexion, $usuario, $id)) {
                json_response(['error' => 'Ya existe otro usuario con ese nombre.'], 409);
            }

            $emailValue = $email === '' ? null : $email;
            if (trim($password) !== '') {
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                $sql = 'UPDATE usuarios SET usuario = ?, email = ?, rol_id = ?, esta_activo = ?, contrasena_hash = ? WHERE id = ?';
                if (!$stmt = mysqli_prepare($conexion, $sql)) {
                    json_response(['error' => 'No fue posible preparar la edición del usuario.'], 500);
                }

                mysqli_stmt_bind_param($stmt, 'ssiisi', $usuario, $emailValue, $rolId, $estaActivo, $passwordHash, $id);
            } else {
                $sql = 'UPDATE usuarios SET usuario = ?, email = ?, rol_id = ?, esta_activo = ? WHERE id = ?';
                if (!$stmt = mysqli_prepare($conexion, $sql)) {
                    json_response(['error' => 'No fue posible preparar la edición del usuario.'], 500);
                }

                mysqli_stmt_bind_param($stmt, 'ssiii', $usuario, $emailValue, $rolId, $estaActivo, $id);
            }

            $ok = mysqli_stmt_execute($stmt);
            $error = mysqli_stmt_error($stmt);
            mysqli_stmt_close($stmt);

            if (!$ok) {
                json_response(['error' => $error ?: 'No fue posible actualizar el usuario.'], 500);
            }

            json_response(['success' => true]);

        case 'toggle':
            require_post();

            $id = post_int('id');
            if ($id <= 0) {
                json_response(['error' => 'Falta identificar al usuario.'], 400);
            }

            $sql = 'UPDATE usuarios SET esta_activo = IF(esta_activo = 1, 0, 1) WHERE id = ?';
            if (!$stmt = mysqli_prepare($conexion, $sql)) {
                json_response(['error' => 'No fue posible preparar el cambio de estado.'], 500);
            }

            mysqli_stmt_bind_param($stmt, 'i', $id);
            $ok = mysqli_stmt_execute($stmt);
            $affected = mysqli_stmt_affected_rows($stmt);
            $error = mysqli_stmt_error($stmt);
            mysqli_stmt_close($stmt);

            if (!$ok) {
                json_response(['error' => $error ?: 'No fue posible actualizar el estado.'], 500);
            }

            if ($affected < 1) {
                json_response(['error' => 'Usuario no encontrado.'], 404);
            }

            json_response(['success' => true]);

        default:
            json_response(['error' => 'Acción no válida.'], 400);
    }
} catch (Throwable $e) {
    json_response(['error' => 'Error interno al procesar la solicitud.'], 500);
}
