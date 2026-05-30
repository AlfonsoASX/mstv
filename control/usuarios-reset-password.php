<?php
require_once __DIR__ . '/lib/app.php';
require_once __DIR__ . '/lib/operations.php';

app_require_session();
app_require_roles(['ADMIN', 'RH', 'DUEÑO']);
app_support_bootstrap($conexion);

$mensajeExito = '';
$mensajeError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuarioId = (int)app_post('usuario_id', 0);
    $nuevaContrasena = trim((string)app_post('nueva_contrasena', ''));

    if ($usuarioId <= 0 || $nuevaContrasena === '') {
        $mensajeError = 'Debes seleccionar un usuario y escribir la nueva contraseña.';
    } elseif (mb_strlen($nuevaContrasena, 'UTF-8') < 6) {
        $mensajeError = 'La nueva contraseña debe tener al menos 6 caracteres.';
    } else {
        $hash = password_hash($nuevaContrasena, PASSWORD_DEFAULT);

        if ($hash === false) {
            $mensajeError = 'No fue posible generar el hash de la nueva contraseña.';
        } else {
            $sql = "UPDATE usuarios SET contrasena_hash = ? WHERE id = ? LIMIT 1";

            if ($stmt = mysqli_prepare($conexion, $sql)) {
                mysqli_stmt_bind_param($stmt, 'si', $hash, $usuarioId);
                if (mysqli_stmt_execute($stmt) && mysqli_stmt_affected_rows($stmt) >= 0) {
                    app_log_system(
                        $conexion,
                        (int)($_SESSION['usuario_id'] ?? 0),
                        'RESET_PASSWORD',
                        'usuarios',
                        $usuarioId,
                        ['password_reset' => true]
                    );
                    $mensajeExito = 'La nueva contraseña se guardó correctamente.';
                } else {
                    $mensajeError = 'No fue posible guardar la nueva contraseña.';
                }
                mysqli_stmt_close($stmt);
            } else {
                $mensajeError = 'No fue posible preparar el cambio de contraseña.';
            }
        }
    }
}

$usuarios = app_db_all($conexion, "
    SELECT
        u.id,
        u.usuario,
        u.email,
        u.esta_activo,
        u.fecha_creacion,
        COALESCE(r.nombre, 'SIN ROL') AS rol_nombre
    FROM usuarios u
    LEFT JOIN roles r ON r.id = u.rol_id
    ORDER BY u.usuario ASC
");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, shrink-to-fit=no">
    <title>Reset de contraseñas</title>
    <link rel="icon" type="image/x-icon" href="../src/assets/img/favicon.ico"/>
    <link href="../layouts/vertical-light-menu/css/light/loader.css" rel="stylesheet" type="text/css" />
    <link href="../layouts/vertical-light-menu/css/dark/loader.css" rel="stylesheet" type="text/css" />
    <script src="../layouts/vertical-light-menu/loader.js"></script>
    <link href="https://fonts.googleapis.com/css?family=Nunito:400,600,700" rel="stylesheet">
    <link href="../src/bootstrap/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
    <link href="../layouts/vertical-light-menu/css/light/plugins.css" rel="stylesheet" type="text/css" />
    <link href="../layouts/vertical-light-menu/css/dark/plugins.css" rel="stylesheet" type="text/css" />
    <style>
        .card-soft { border-radius: 16px; }
        .table td, .table th { vertical-align: middle; }
    </style>
</head>
<body class="layout-boxed">
    <div id="load_screen">
        <div class="loader"><div class="loader-content"><div class="spinner-grow align-self-center"></div></div></div>
    </div>
    <?php include 'partes/nav.php'; ?>
    <div class="main-container" id="container">
        <div class="overlay"></div>
        <div class="search-overlay"></div>
        <div class="sidebar-wrapper sidebar-theme">
            <nav id="sidebar"><div class="sidebar-wrapper sidebar-theme"><?php include 'partes/menu.php'; ?></div></nav>
        </div>
        <div id="content" class="main-content">
            <div class="layout-px-spacing">
                <div class="middle-content container-xxl p-0">
                    <div class="secondary-nav mb-3">
                        <div class="breadcrumbs-container" data-page-heading="Reset de contraseñas">
                            <header class="header navbar navbar-expand-sm">
                                <a href="javascript:void(0);" class="btn-toggle sidebarCollapse" data-placement="bottom">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
                                </a>
                                <div class="ms-3">
                                    <h4 class="mb-0">Reset de contraseñas</h4>
                                    <small class="text-muted">Cambia la contraseña de cualquier usuario sin mostrar la actual.</small>
                                </div>
                            </header>
                        </div>
                    </div>

                    <?php if ($mensajeExito): ?><div class="alert alert-success"><?php echo app_h($mensajeExito); ?></div><?php endif; ?>
                    <?php if ($mensajeError): ?><div class="alert alert-danger"><?php echo app_h($mensajeError); ?></div><?php endif; ?>

                    <div class="row layout-top-spacing">
                        <div class="col-12 layout-spacing">
                            <div class="widget widget-table-one card-soft" style="padding:20px;">
                                <div class="widget-heading d-flex justify-content-between align-items-center mb-3">
                                    <div>
                                        <h5 class="mb-1">Usuarios del sistema</h5>
                                        <small class="text-muted">Usa el botón de cada fila para capturar una nueva contraseña.</small>
                                    </div>
                                </div>

                                <div class="widget-content">
                                    <div class="table-responsive">
                                        <table class="table table-striped mb-0">
                                            <thead>
                                                <tr>
                                                    <th>ID</th>
                                                    <th>Usuario</th>
                                                    <th>Email</th>
                                                    <th>Rol</th>
                                                    <th>Estado</th>
                                                    <th>Alta</th>
                                                    <th class="text-center">Acción</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($usuarios as $usuario): ?>
                                                    <tr>
                                                        <td><?php echo (int)$usuario['id']; ?></td>
                                                        <td><?php echo app_h($usuario['usuario']); ?></td>
                                                        <td><?php echo app_h($usuario['email']); ?></td>
                                                        <td><?php echo app_h($usuario['rol_nombre']); ?></td>
                                                        <td>
                                                            <?php if ((int)$usuario['esta_activo'] === 1): ?>
                                                                <span class="badge bg-success">Activo</span>
                                                            <?php else: ?>
                                                                <span class="badge bg-secondary">Inactivo</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td><?php echo app_datetime($usuario['fecha_creacion']); ?></td>
                                                        <td class="text-center">
                                                            <button
                                                                type="button"
                                                                class="btn btn-warning btn-sm btn-reset-password"
                                                                data-bs-toggle="modal"
                                                                data-bs-target="#modalResetPassword"
                                                                data-usuario-id="<?php echo (int)$usuario['id']; ?>"
                                                                data-usuario-nombre="<?php echo app_h($usuario['usuario']); ?>">
                                                                Cambiar contraseña
                                                            </button>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php include 'partes/footer.php'; ?>
        </div>
    </div>

    <div class="modal fade" id="modalResetPassword" tabindex="-1" aria-labelledby="modalResetPasswordLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="post">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalResetPasswordLabel">Cambiar contraseña</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="usuario_id" id="reset_usuario_id">
                        <div class="mb-3">
                            <label class="form-label">Usuario</label>
                            <input type="text" class="form-control" id="reset_usuario_nombre" readonly>
                        </div>
                        <div class="mb-0">
                            <label for="reset_nueva_contrasena" class="form-label">Nueva contraseña</label>
                            <input type="password" class="form-control" name="nueva_contrasena" id="reset_nueva_contrasena" minlength="6" required>
                            <small class="text-muted">La contraseña actual no se muestra; solo se reemplaza por la nueva.</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar contraseña</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="../src/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../src/plugins/src/perfect-scrollbar/perfect-scrollbar.min.js"></script>
    <script src="../src/plugins/src/mousetrap/mousetrap.min.js"></script>
    <script src="../layouts/vertical-light-menu/app.js"></script>
    <script>
        document.querySelectorAll('.btn-reset-password').forEach((button) => {
            button.addEventListener('click', () => {
                document.getElementById('reset_usuario_id').value = button.dataset.usuarioId || '';
                document.getElementById('reset_usuario_nombre').value = button.dataset.usuarioNombre || '';
                document.getElementById('reset_nueva_contrasena').value = '';
            });
        });
    </script>
</body>
</html>
