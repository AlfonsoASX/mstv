<?php
// usuarios-lista.php
session_start();
include 'lib/db.php'; // Debe definir $conexion (mysqli)

if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit;
}

// Helper para imprimir HTML seguro evitando el deprecated (null)
function h($txt) {
    return htmlspecialchars($txt ?? '', ENT_QUOTES, 'UTF-8');
}

// Cargar usuarios desde BD
$usuarios = [];
$sql = "
    SELECT 
        u.id,
        u.usuario,
        u.email,
        u.esta_activo,
        u.fecha_creacion,
        r.nombre AS rol_nombre
    FROM usuarios u
    LEFT JOIN roles r ON r.id = u.rol_id
    ORDER BY u.usuario ASC
";
if ($res = mysqli_query($conexion, $sql)) {
    while ($row = mysqli_fetch_assoc($res)) {
        $usuarios[] = $row;
    }
    mysqli_free_result($res);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, shrink-to-fit=no">
    <title>Usuarios y Roles</title>

    <link rel="icon" type="image/x-icon" href="../src/assets/img/favicon.ico"/>
    <link href="../layouts/vertical-light-menu/css/light/loader.css" rel="stylesheet" type="text/css" />
    <link href="../layouts/vertical-light-menu/css/dark/loader.css" rel="stylesheet" type="text/css" />
    <script src="../layouts/vertical-light-menu/loader.js"></script>

    <!-- BEGIN GLOBAL MANDATORY STYLES -->
    <link href="https://fonts.googleapis.com/css?family=Nunito:400,600,700" rel="stylesheet">
    <link href="../src/bootstrap/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
    <link href="../layouts/vertical-light-menu/css/light/plugins.css" rel="stylesheet" type="text/css" />
    <link href="../layouts/vertical-light-menu/css/dark/plugins.css" rel="stylesheet" type="text/css" />
    <!-- END GLOBAL MANDATORY STYLES -->

    <!-- BEGIN PAGE LEVEL STYLES (DataTables CORK) -->
    <link rel="stylesheet" type="text/css" href="../src/plugins/src/table/datatable/datatables.css">
    <link rel="stylesheet" type="text/css" href="../src/plugins/src/table/datatable/dt-global_style.css">
    <!-- END PAGE LEVEL STYLES -->

</head>
<body class="layout-boxed">
    <!-- BEGIN LOADER -->
    <div id="load_screen">
        <div class="loader">
            <div class="loader-content">
                <div class="spinner-grow align-self-center"></div>
            </div>
        </div>
    </div>
    <!--  END LOADER -->

    <?php include 'partes/nav.php'; ?>

    <!--  BEGIN MAIN CONTAINER  -->
    <div class="main-container" id="container">

        <div class="overlay"></div>
        <div class="search-overlay"></div>

        <!--  BEGIN SIDEBAR  -->
        <div class="sidebar-wrapper sidebar-theme">
            <nav id="sidebar">
                <?php include 'partes/menu.php'; ?>
            </nav>
        </div>
        <!--  END SIDEBAR  -->

        <!--  BEGIN CONTENT AREA  -->
        <div id="content" class="main-content">
            <div class="layout-px-spacing">

                <div class="middle-content container-xxl p-0">

                    <!--  BEGIN BREADCRUMBS / HEADER  -->
                    <div class="secondary-nav">
                        <div class="breadcrumbs-container" data-page-heading="Usuarios">
                            <header class="header navbar navbar-expand-sm">
                                <a href="javascript:void(0);" class="btn-toggle sidebarCollapse" data-placement="bottom">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                         viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                         stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                         class="feather feather-menu">
                                        <line x1="3" y1="12" x2="21" y2="12"></line>
                                        <line x1="3" y1="6" x2="21" y2="6"></line>
                                        <line x1="3" y1="18" x2="21" y2="18"></line>
                                    </svg>
                                </a>
                                <div class="d-flex align-items-center ms-3">
                                    <h4 class="mb-0">Usuarios del sistema</h4>
                                </div>
                            </header>
                        </div>
                    </div>
                    <!--  END BREADCRUMBS / HEADER  -->

                    <div class="row layout-top-spacing">

                        <div class="col-12 layout-spacing">
                            <div class="widget widget-table-one">
                                <div class="widget-heading d-flex justify-content-between align-items-center mb-3">
                                    <h5 class="mb-0">Gestión de usuarios</h5>
                                    <button type="button" class="btn btn-primary btn-sm" id="btnNuevoUsuario">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18"
                                             viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                             stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                             class="feather feather-user-plus me-1">
                                            <path d="M16 21v-2a4 4 0 0 0-3-3.87"></path>
                                            <path d="M9 7a4 4 0 1 0 0-8 4 4 0 0 0 0 8z"></path>
                                            <line x1="19" y1="8" x2="19" y2="14"></line>
                                            <line x1="16" y1="11" x2="22" y2="11"></line>
                                        </svg>
                                        Nuevo usuario
                                    </button>
                                </div>

                                <div class="widget-content">
                                    <div class="table-responsive">
                                        <table id="usuarios-table" class="table table-striped table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>ID</th>
                                                    <th>Usuario</th>
                                                    <th>Email</th>
                                                    <th>Rol</th>
                                                    <th>Estado</th>
                                                    <th>Creado</th>
                                                    <th class="text-center">Acciones</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                            <?php foreach ($usuarios as $u): ?>
                                                <?php
                                                    $id          = (int)$u['id'];
                                                    $usuario     = h($u['usuario']);
                                                    $email       = h($u['email']);
                                                    $rolNombre   = h($u['rol_nombre']);
                                                    $estaActivo  = (int)$u['esta_activo'] === 1;
                                                    $fechaCreac  = h($u['fecha_creacion']);
                                                ?>
                                                <tr data-id="<?php echo $id; ?>">
                                                    <td><?php echo $id; ?></td>
                                                    <td><?php echo $usuario; ?></td>
                                                    <td><?php echo $email; ?></td>
                                                    <td><?php echo $rolNombre; ?></td>
                                                    <td>
                                                        <?php if ($estaActivo): ?>
                                                            <span class="badge bg-success">Activo</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-secondary">Inactivo</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?php echo $fechaCreac; ?></td>
                                                    <td class="text-center">
                                                        <div class="btn-group btn-group-sm" role="group">
                                                            <button type="button"
                                                                    class="btn btn-outline-info btn-ver"
                                                                    title="Ver">
                                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                                                     viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                                     stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                                                     class="feather feather-eye">
                                                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8S1 12 1 12z"></path>
                                                                    <circle cx="12" cy="12" r="3"></circle>
                                                                </svg>
                                                            </button>
                                                            <button type="button"
                                                                    class="btn btn-outline-warning btn-editar"
                                                                    title="Editar">
                                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                                                     viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                                     stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                                                     class="feather feather-edit">
                                                                    <path d="M11 4h9a2 2 0 0 1 2 2v9"></path>
                                                                    <path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L7 20.5 2 22l1.5-5z"></path>
                                                                </svg>
                                                            </button>
                                                            <button type="button"
                                                                    class="btn btn-outline-secondary btn-toggle-estado"
                                                                    title="Activar/Desactivar">
                                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                                                     viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                                     stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                                                     class="feather feather-power">
                                                                    <path d="M18.36 6.64a9 9 0 1 1-12.73 0"></path>
                                                                    <line x1="12" y1="2" x2="12" y2="12"></line>
                                                                </svg>
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div> <!-- table-responsive -->
                                </div> <!-- widget-content -->
                            </div>
                        </div>

                    </div> <!-- row -->

                </div> <!-- middle-content -->

            </div> <!-- layout-px-spacing -->

            <!--  BEGIN FOOTER  -->
            <?php include 'partes/footer.php'; ?>
            <!--  END FOOTER  -->

        </div>
        <!--  END CONTENT AREA  -->

    </div>
    <!-- END MAIN CONTAINER -->

    <!-- MODAL: FORM USUARIO (ALTA / EDICIÓN / VER) -->
    <div class="modal fade" id="modalUsuario" tabindex="-1" aria-labelledby="modalUsuarioLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form id="formUsuario">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalUsuarioLabel">Usuario</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="id" id="usr_id">
                        <input type="hidden" name="accion" id="usr_accion" value="crear">

                        <div class="mb-3">
                            <label for="usr_usuario" class="form-label">Usuario</label>
                            <input type="text" class="form-control" id="usr_usuario" name="usuario" required>
                        </div>

                        <div class="mb-3">
                            <label for="usr_email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="usr_email" name="email">
                        </div>

                        <div class="mb-3">
                            <label for="usr_rol" class="form-label">Rol</label>
                            <select class="form-select" id="usr_rol" name="rol_id" required>
                                <option value="">Selecciona un rol...</option>
                                <?php
                                $sqlRoles = "SELECT id, nombre FROM roles ORDER BY nombre ASC";
                                if ($resR = mysqli_query($conexion, $sqlRoles)) {
                                    while ($r = mysqli_fetch_assoc($resR)) {
                                        echo '<option value="'.(int)$r['id'].'">'.h($r['nombre']).'</option>';
                                    }
                                    mysqli_free_result($resR);
                                }
                                ?>
                            </select>
                        </div>

                        <div class="mb-3" id="grupo_password">
                            <label for="usr_password" class="form-label">Contraseña</label>
                            <input type="password" class="form-control" id="usr_password" name="password">
                            <small class="text-muted" id="help_password">Obligatoria solo al crear usuario o si deseas cambiarla.</small>
                        </div>

                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" id="usr_activo" name="esta_activo" checked>
                            <label class="form-check-label" for="usr_activo">Usuario activo</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cerrar</button>
                        <button type="submit" class="btn btn-primary" id="btnGuardarUsuario">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- FIN MODAL -->

    <!-- BEGIN GLOBAL MANDATORY SCRIPTS -->
    <script src="../src/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../src/plugins/src/perfect-scrollbar/perfect-scrollbar.min.js"></script>
    <script src="../src/plugins/src/mousetrap/mousetrap.min.js"></script>
    <script src="../src/plugins/src/waves/waves.min.js"></script>
    <script src="../layouts/vertical-light-menu/app.js"></script>
    <!-- END GLOBAL MANDATORY SCRIPTS -->

    <!-- BEGIN PAGE LEVEL SCRIPTS (DataTables) -->
    <script src="../src/plugins/src/table/datatable/datatables.js"></script>
    <!-- END PAGE LEVEL SCRIPTS -->

    <script>
    document.addEventListener('DOMContentLoaded', function () {

        // DataTable
        let table = $('#usuarios-table').DataTable({
            language: {
                sProcessing:   "Procesando...",
                sLengthMenu:   "Mostrar _MENU_ registros",
                sZeroRecords:  "No se encontraron resultados",
                sEmptyTable:   "Ningún dato disponible en esta tabla",
                sInfo:         "Mostrando _START_ a _END_ de _TOTAL_ registros",
                sInfoEmpty:    "Mostrando 0 a 0 de 0 registros",
                sInfoFiltered: "(filtrado de _MAX_ registros totales)",
                sSearch:       "Buscar:",
                oPaginate: {
                    sFirst:    "Primero",
                    sLast:     "Último",
                    sNext:     "Siguiente",
                    sPrevious: "Anterior"
                }
            }
        });

        const modalEl = document.getElementById('modalUsuario');
        const modalUsuario = new bootstrap.Modal(modalEl);

        const formUsuario      = document.getElementById('formUsuario');
        const usrId            = document.getElementById('usr_id');
        const usrAccion        = document.getElementById('usr_accion');
        const usrUsuario       = document.getElementById('usr_usuario');
        const usrEmail         = document.getElementById('usr_email');
        const usrRol           = document.getElementById('usr_rol');
        const usrPassword      = document.getElementById('usr_password');
        const usrActivo        = document.getElementById('usr_activo');
        const grupoPassword    = document.getElementById('grupo_password');
        const helpPassword     = document.getElementById('help_password');
        const btnGuardarUsuario= document.getElementById('btnGuardarUsuario');
        const modalLabel       = document.getElementById('modalUsuarioLabel');

        // Nuevo
        document.getElementById('btnNuevoUsuario').addEventListener('click', function () {
            formUsuario.reset();
            usrId.value      = '';
            usrAccion.value  = 'crear';
            modalLabel.textContent = 'Nuevo usuario';
            usrPassword.required = true;
            usrPassword.value = '';
            helpPassword.textContent = 'Contraseña obligatoria para crear usuario.';
            usrActivo.checked = true;

            usrUsuario.removeAttribute('readonly');
            usrEmail.removeAttribute('readonly');
            usrRol.removeAttribute('disabled');
            usrPassword.removeAttribute('readonly');
            usrActivo.removeAttribute('disabled');
            btnGuardarUsuario.style.display = '';

            modalUsuario.show();
        });

        // Ver
        $('#usuarios-table').on('click', '.btn-ver', function () {
            const id = this.closest('tr').getAttribute('data-id');
            if (!id) return;

            fetch('usuarios-ajax.php?action=get&id=' + encodeURIComponent(id))
                .then(r => r.json())
                .then(data => {
                    if (!data || data.error) {
                        alert(data.error || 'No se pudo cargar el usuario.');
                        return;
                    }
                    usrId.value       = data.id;
                    usrAccion.value   = 'ver';
                    usrUsuario.value  = data.usuario || '';
                    usrEmail.value    = data.email || '';
                    usrRol.value      = data.rol_id || '';
                    usrPassword.value = '';
                    usrActivo.checked = (parseInt(data.esta_activo) === 1);

                    modalLabel.textContent = 'Ver usuario';

                    usrUsuario.setAttribute('readonly', 'readonly');
                    usrEmail.setAttribute('readonly', 'readonly');
                    usrRol.setAttribute('disabled', 'disabled');
                    usrPassword.setAttribute('readonly', 'readonly');
                    usrActivo.setAttribute('disabled', 'disabled');
                    btnGuardarUsuario.style.display = 'none';

                    modalUsuario.show();
                })
                .catch(() => alert('Error al consultar el usuario.'));
        });

        // Editar
        $('#usuarios-table').on('click', '.btn-editar', function () {
            const id = this.closest('tr').getAttribute('data-id');
            if (!id) return;

            fetch('usuarios-ajax.php?action=get&id=' + encodeURIComponent(id))
                .then(r => r.json())
                .then(data => {
                    if (!data || data.error) {
                        alert(data.error || 'No se pudo cargar el usuario.');
                        return;
                    }
                    usrId.value       = data.id;
                    usrAccion.value   = 'editar';
                    usrUsuario.value  = data.usuario || '';
                    usrEmail.value    = data.email || '';
                    usrRol.value      = data.rol_id || '';
                    usrPassword.value = '';
                    usrActivo.checked = (parseInt(data.esta_activo) === 1);

                    modalLabel.textContent = 'Editar usuario';

                    usrUsuario.removeAttribute('readonly');
                    usrEmail.removeAttribute('readonly');
                    usrRol.removeAttribute('disabled');
                    usrPassword.removeAttribute('readonly');
                    usrActivo.removeAttribute('disabled');
                    btnGuardarUsuario.style.display = '';

                    usrPassword.required = false;
                    helpPassword.textContent = 'Déjala en blanco si no deseas cambiar la contraseña.';

                    modalUsuario.show();
                })
                .catch(() => alert('Error al consultar el usuario.'));
        });

        // Activar / Desactivar
        $('#usuarios-table').on('click', '.btn-toggle-estado', function () {
            const row = this.closest('tr');
            const id  = row.getAttribute('data-id');
            if (!id) return;

            if (!confirm('¿Seguro que deseas activar/desactivar este usuario?')) {
                return;
            }

            fetch('usuarios-ajax.php?action=toggle', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'
                },
                body: 'id=' + encodeURIComponent(id)
            })
            .then(r => r.json())
            .then(data => {
                if (data && data.success) {
                    location.reload(); // sencillo y confiable
                } else {
                    alert(data.error || 'No se pudo actualizar el estado.');
                }
            })
            .catch(() => alert('Error al actualizar el estado.'));
        });

        // Guardar (crear / editar)
        formUsuario.addEventListener('submit', function (e) {
            e.preventDefault();

            const formData = new FormData(formUsuario);

            let action = formData.get('accion');
            let url = 'usuarios-ajax.php?action=' + encodeURIComponent(action);

            fetch(url, {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data && data.success) {
                    modalUsuario.hide();
                    location.reload(); // refrescar lista
                } else {
                    alert(data.error || 'No se pudo guardar el usuario.');
                }
            })
            .catch(() => alert('Error de comunicación con el servidor.'));
        });

    });
    </script>

</body>
</html>
