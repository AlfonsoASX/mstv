<?php
// turnos-politicas.php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit;
}

include 'lib/db.php'; // Debe definir $conexion (mysqli)

// Roles permitidos para configurar políticas
$rol = isset($_SESSION['rol_nombre']) ? $_SESSION['rol_nombre'] : '';
$roles_permitidos = ['ADMIN', 'RH', 'NOMINA', 'DUEÑO'];
if (!in_array($rol, $roles_permitidos)) {
    echo "No tienes permisos para ver esta página.";
    exit;
}

function limpiar($txt) {
    return trim(htmlspecialchars($txt, ENT_QUOTES, 'UTF-8'));
}

$mensaje_ok  = '';
$mensaje_err = '';

/* ============================================================
   1) ELIMINAR POLÍTICA (GET ?del=clave)
   ============================================================ */
if (isset($_GET['del']) && $_GET['del'] !== '') {
    $clave_del = limpiar($_GET['del']);

    $sql_del = "DELETE FROM configuracion_sistema WHERE clave_configuracion = ?";
    if ($stmt = mysqli_prepare($conexion, $sql_del)) {
        mysqli_stmt_bind_param($stmt, "s", $clave_del);
        if (mysqli_stmt_execute($stmt)) {
            $mensaje_ok = "Política eliminada correctamente.";
        } else {
            $mensaje_err = "Error al eliminar la política: " . mysqli_error($conexion);
        }
        mysqli_stmt_close($stmt);
    } else {
        $mensaje_err = "Error al preparar la eliminación: " . mysqli_error($conexion);
    }
}

/* ============================================================
   2) GUARDAR / ACTUALIZAR POLÍTICA (POST)
   ============================================================ */
$editando_clave = '';
$valor_editar   = '';
$desc_editar    = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'guardar_politica') {

    $clave          = isset($_POST['clave_configuracion']) ? limpiar($_POST['clave_configuracion']) : '';
    $valor          = isset($_POST['valor_configuracion']) ? trim($_POST['valor_configuracion']) : '';
    $descripcion    = isset($_POST['descripcion']) ? limpiar($_POST['descripcion']) : '';
    $original_clave = isset($_POST['original_clave']) ? limpiar($_POST['original_clave']) : '';

    if ($clave === '' || $valor === '') {
        $mensaje_err = "La clave y el valor de la política son obligatorios.";
    } else {

        // Si no hay original_clave, es un INSERT
        if ($original_clave === '') {

            $sql_ins = "
                INSERT INTO configuracion_sistema (clave_configuracion, valor_configuracion, descripcion)
                VALUES (?, ?, ?)
            ";

            if ($stmt = mysqli_prepare($conexion, $sql_ins)) {
                mysqli_stmt_bind_param($stmt, "sss", $clave, $valor, $descripcion);
                if (mysqli_stmt_execute($stmt)) {
                    $mensaje_ok = "Política creada correctamente.";
                } else {
                    // Posible error por PK duplicada
                    $mensaje_err = "Error al crear la política: " . mysqli_error($conexion);
                    $editando_clave = $clave;
                    $valor_editar   = $valor;
                    $desc_editar    = $descripcion;
                }
                mysqli_stmt_close($stmt);
            } else {
                $mensaje_err = "Error al preparar el insert: " . mysqli_error($conexion);
            }

        } else {
            // UPDATE (puede cambiar la clave también)
            $sql_upd = "
                UPDATE configuracion_sistema
                SET clave_configuracion = ?, valor_configuracion = ?, descripcion = ?
                WHERE clave_configuracion = ?
            ";

            if ($stmt = mysqli_prepare($conexion, $sql_upd)) {
                mysqli_stmt_bind_param($stmt, "ssss", $clave, $valor, $descripcion, $original_clave);
                if (mysqli_stmt_execute($stmt)) {
                    $mensaje_ok = "Política actualizada correctamente.";
                } else {
                    $mensaje_err = "Error al actualizar la política: " . mysqli_error($conexion);
                }
                mysqli_stmt_close($stmt);
            } else {
                $mensaje_err = "Error al preparar el update: " . mysqli_error($conexion);
            }

            // dejamos el formulario vacío tras actualizar
        }
    }
}

/* ============================================================
   3) CARGAR UNA POLÍTICA PARA EDICIÓN (GET ?edit=clave)
   ============================================================ */
if (isset($_GET['edit']) && $_GET['edit'] !== '') {
    $clave_edit = limpiar($_GET['edit']);

    $sql_edit = "SELECT * FROM configuracion_sistema WHERE clave_configuracion = ? LIMIT 1";
    if ($stmt = mysqli_prepare($conexion, $sql_edit)) {
        mysqli_stmt_bind_param($stmt, "s", $clave_edit);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        if ($row = mysqli_fetch_assoc($res)) {
            $editando_clave = $row['clave_configuracion'];
            $valor_editar   = $row['valor_configuracion'];
            $desc_editar    = $row['descripcion'];
        }
        mysqli_stmt_close($stmt);
    }
}

/* ============================================================
   4) LISTAR POLÍTICAS RELACIONADAS A TURNOS / NÓMINA
   ============================================================ */
$politicas = [];

$sql_p = "
    SELECT clave_configuracion, valor_configuracion, descripcion
    FROM configuracion_sistema
    WHERE clave_configuracion LIKE 'turnos_%'
       OR clave_configuracion LIKE 'nomina_%'
       OR clave_configuracion LIKE 'extras_%'
    ORDER BY clave_configuracion
";

if ($res_p = mysqli_query($conexion, $sql_p)) {
    while ($row = mysqli_fetch_assoc($res_p)) {
        $politicas[] = $row;
    }
    mysqli_free_result($res_p);
}

$total_politicas = count($politicas);

function getValorPolitica($politicas, $clave) {
    foreach ($politicas as $p) {
        if ($p['clave_configuracion'] === $clave) {
            return $p['valor_configuracion'];
        }
    }
    return null;
}

// Algunos valores “importantes” para mostrar en cards si existen
$tol_min     = getValorPolitica($politicas, 'turnos_tolerancia_minutos');
$max_dia     = getValorPolitica($politicas, 'turnos_max_horas_extra_diarias');
$max_sem     = getValorPolitica($politicas, 'turnos_max_horas_extra_semanales');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, shrink-to-fit=no">
    <title>Políticas de Turnos y Horas Extra</title>
    <link rel="icon" type="image/x-icon" href="../src/assets/img/favicon.ico"/>
    <link href="../layouts/vertical-light-menu/css/light/loader.css" rel="stylesheet" type="text/css" />
    <link href="../layouts/vertical-light-menu/css/dark/loader.css" rel="stylesheet" type="text/css" />
    <script src="../layouts/vertical-light-menu/loader.js"></script>

    <!-- GLOBAL STYLES -->
    <link href="https://fonts.googleapis.com/css?family=Nunito:400,600,700" rel="stylesheet">
    <link href="../src/bootstrap/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
    <link href="../layouts/vertical-light-menu/css/light/plugins.css" rel="stylesheet" type="text/css" />
    <link href="../layouts/vertical-light-menu/css/dark/plugins.css" rel="stylesheet" type="text/css" />

    <!-- DASHBOARD STYLES -->
    <link href="../src/assets/css/light/dashboard/dash_1.css" rel="stylesheet" type="text/css" />
    <link href="../src/assets/css/dark/dashboard/dash_1.css" rel="stylesheet" type="text/css" />
</head>
<body class="layout-boxed">

    <!-- LOADER -->
    <div id="load_screen">
        <div class="loader">
            <div class="loader-content">
                <div class="spinner-grow align-self-center"></div>
            </div>
        </div>
    </div>

    <?php include 'partes/nav.php'; ?>

    <div class="main-container" id="container">

        <div class="overlay"></div>
        <div class="search-overlay"></div>

        <!-- SIDEBAR -->
        <div class="sidebar-wrapper sidebar-theme">
            <nav id="sidebar">
                <div class="sidebar-wrapper sidebar-theme">
                    <?php include 'partes/menu.php'; ?>
                </div>
            </nav>
        </div>
        <!-- /SIDEBAR -->

        <!-- CONTENT -->
        <div id="content" class="main-content">
            <div class="layout-px-spacing">

                <div class="middle-content container-xxl p-0">

                    <!-- BARRA SUPERIOR -->
                    <div class="secondary-nav mb-3">
                        <div class="breadcrumbs-container" data-page-heading="Políticas de Turnos">
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
                                <div class="ms-3">
                                    <h5 class="mb-0">Políticas de Turnos y Horas Extra</h5>
                                    <small class="text-muted">
                                        Define tolerancias, topes de horas extra y reglas para nómina.
                                    </small>
                                </div>
                            </header>
                        </div>
                    </div>

                    <!-- WIDGETS RESUMEN -->
                    <div class="row layout-top-spacing mb-3">

                        <div class="col-xl-4 col-lg-4 col-md-4 col-sm-12 col-12 layout-spacing">
                            <div class="widget widget-one_hybrid widget-followers">
                                <div class="widget-heading">
                                    <div class="w-title">
                                        <div class="w-icon">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                 viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                 stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                                 class="feather feather-settings">
                                                <circle cx="12" cy="12" r="3"></circle>
                                                <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l0 0a2 2 0 1 1-2.83 2.83l0 0A1.65 1.65 0 0 0 15 19.4a1.65 1.65 0 0 0-1 .6 1.65 1.65 0 0 0-.33 1.82l0 0a2 2 0 1 1-3.32 0l0 0A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l0 0a2 2 0 1 1-2.83-2.83l0 0A1.65 1.65 0 0 0 4.6 15a1.65 1.65 0 0 0-.6-1 1.65 1.65 0 0 0-1.82-.33l0 0a2 2 0 1 1 0-3.32l0 0A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0 .33-1.82l0 0A2 2 0 1 1 7.76 4.35l0 0A1.65 1.65 0 0 0 9 4.6a1.65 1.65 0 0 0 1-.6 1.65 1.65 0 0 0 .33-1.82l0 0a2 2 0 1 1 3.32 0l0 0A1.65 1.65 0 0 0 15 4.6a1.65 1.65 0 0 0 1.82-.33l0 0a2 2 0 1 1 2.83 2.83l0 0A1.65 1.65 0 0 0 19.4 9c.26.31.46.67.6 1.06.14.39.14.81 0 1.2-.14.39-.34.75-.6 1.06z"></path>
                                            </svg>
                                        </div>
                                        <div class="">
                                            <p class="w-value"><?php echo $total_politicas; ?></p>
                                            <h5 class="">Políticas configuradas</h5>
                                        </div>
                                    </div>
                                </div>
                                <div class="widget-content">
                                    <div class="w-chart">
                                        <div id="hybrid_followers"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-4 col-lg-4 col-md-4 col-sm-12 col-12 layout-spacing">
                            <div class="widget widget-one_hybrid widget-referral">
                                <div class="widget-heading">
                                    <div class="w-title">
                                        <div class="w-icon">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                 viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                 stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                                 class="feather feather-clock">
                                                <circle cx="12" cy="12" r="10"></circle>
                                                <polyline points="12 6 12 12 16 14"></polyline>
                                            </svg>
                                        </div>
                                        <div class="">
                                            <p class="w-value">
                                                <?php echo $tol_min !== null ? intval($tol_min) . ' min' : '--'; ?>
                                            </p>
                                            <h5 class="">Tolerancia entrada (turnos_tolerancia_minutos)</h5>
                                        </div>
                                    </div>
                                </div>
                                <div class="widget-content">
                                    <div class="w-chart">
                                        <div id="hybrid_followers1"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-4 col-lg-4 col-md-4 col-sm-12 col-12 layout-spacing">
                            <div class="widget widget-one_hybrid widget-engagement">
                                <div class="widget-heading">
                                    <div class="w-title">
                                        <div class="w-icon">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                 viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                 stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                                 class="feather feather-activity">
                                                <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
                                            </svg>
                                        </div>
                                        <div class="">
                                            <p class="w-value">
                                                <?php
                                                    $txt = [];
                                                    if ($max_dia !== null) { $txt[] = intval($max_dia) . 'h/día'; }
                                                    if ($max_sem !== null) { $txt[] = intval($max_sem) . 'h/sem'; }
                                                    echo empty($txt) ? '--' : implode(' · ', $txt);
                                                ?>
                                            </p>
                                            <h5 class="">Topes horas extra (diarios / semanales)</h5>
                                        </div>
                                    </div>
                                </div>
                                <div class="widget-content">
                                    <div class="w-chart">
                                        <div id="hybrid_followers3"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- MENSAJES -->
                    <?php if ($mensaje_ok): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?php echo $mensaje_ok; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    <?php if ($mensaje_err): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?php echo $mensaje_err; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <!-- CONTENIDO PRINCIPAL -->
                    <div class="row layout-top-spacing">

                        <!-- IZQUIERDA: FORMULARIO Y AYUDA -->
                        <div class="col-xl-4 col-lg-5 col-md-12 col-sm-12 layout-spacing">

                            <!-- Formulario crear/editar -->
                            <div style="padding:20px!important" class="widget widget-card-two mb-4">
                                <div class="widget-content">
                                    <div class="media mb-2">
                                        <div class="w-img">
                                            <img src="../src/assets/img/money-bag.png" alt="politicas">
                                        </div>
                                        <div class="media-body">
                                            <h6><?php echo $editando_clave ? 'Editar política' : 'Nueva política'; ?></h6>
                                            <p class="meta-date-time">
                                                Las políticas se almacenan en <strong>configuracion_sistema</strong>.
                                            </p>
                                        </div>
                                    </div>

                                    <form method="post">
                                        <input type="hidden" name="accion" value="guardar_politica">
                                        <input type="hidden" name="original_clave" value="<?php echo htmlspecialchars($editando_clave); ?>">

                                        <div class="mb-2">
                                            <label class="form-label">Clave de configuración</label>
                                            <input type="text"
                                                   name="clave_configuracion"
                                                   class="form-control form-control-sm"
                                                   placeholder="Ej. turnos_tolerancia_minutos"
                                                   value="<?php echo htmlspecialchars($editando_clave); ?>"
                                                   required>
                                        </div>

                                        <div class="mb-2">
                                            <label class="form-label">Valor</label>
                                            <textarea name="valor_configuracion"
                                                      class="form-control form-control-sm"
                                                      rows="3"
                                                      placeholder="Ej. 10, 8, 12.5, JSON con reglas, etc."
                                                      required><?php echo htmlspecialchars($valor_editar); ?></textarea>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Descripción</label>
                                            <input type="text"
                                                   name="descripcion"
                                                   class="form-control form-control-sm"
                                                   placeholder="Ej. Minutos de tolerancia para llegada tarde."
                                                   value="<?php echo htmlspecialchars($desc_editar); ?>">
                                        </div>

                                        <div class="text-end">
                                            <?php if ($editando_clave): ?>
                                                <a href="turnos-politicas.php" class="btn btn-light btn-sm">Cancelar edición</a>
                                            <?php endif; ?>
                                            <button type="submit" class="btn btn-primary btn-sm">
                                                <?php echo $editando_clave ? 'Actualizar' : 'Guardar'; ?>
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            <!-- Ayuda / sugerencias de claves -->
                            <div class="widget widget-card-one" style="padding:20px!important">
                                <div class="widget-content">
                                    <h6 class="mb-2">Sugerencias de políticas</h6>
                                    <p class="mb-2">
                                        Algunas claves típicas que puedes usar para controlar el comportamiento del sistema:
                                    </p>
                                    <ul class="list-unstyled mb-0 small">
                                        <li><strong>turnos_tolerancia_minutos</strong> — Minutos de tolerancia en la entrada.</li>
                                        <li><strong>turnos_max_horas_extra_diarias</strong> — Límite de horas extra por día.</li>
                                        <li><strong>turnos_max_horas_extra_semanales</strong> — Límite por semana.</li>
                                        <li><strong>turnos_min_horas_descanso</strong> — Horas mínimas entre turnos.</li>
                                        <li><strong>nomina_factor_pago_extra</strong> — Multiplicador de pago de hora extra (1.5, 2.0, etc.).</li>
                                        <li><strong>extras_notificar_correo</strong> — Correos separados por coma para alertas de exceso de horas.</li>
                                    </ul>
                                </div>
                            </div>

                        </div>

                        <!-- DERECHA: TABLA DE POLÍTICAS -->
                        <div class="col-xl-8 col-lg-7 col-md-12 col-sm-12 layout-spacing">

                            <div class="widget widget-table-one">
                                <div class="widget-heading d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="">Listado de políticas de turnos/nómina</h6>
                                        <small class="text-muted">
                                            Se muestran las claves que comienzan con <code>turnos_</code>, <code>nomina_</code> o <code>extras_</code>.
                                        </small>
                                    </div>
                                </div>
                                <div class="widget-content">
                                    <div class="table-responsive">
                                        <table class="table table-sm mb-0 align-middle">
                                            <thead>
                                                <tr>
                                                    <th>Clave</th>
                                                    <th>Valor</th>
                                                    <th>Descripción</th>
                                                    <th class="text-end">Acciones</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (empty($politicas)): ?>
                                                    <tr>
                                                        <td colspan="4" class="text-center text-muted py-3">
                                                            Aún no hay políticas registradas para turnos/nómina.
                                                        </td>
                                                    </tr>
                                                <?php else: ?>
                                                    <?php foreach ($politicas as $p): ?>
                                                        <tr>
                                                            <td>
                                                                <code><?php echo htmlspecialchars($p['clave_configuracion']); ?></code>
                                                            </td>
                                                            <td style="max-width: 260px;">
                                                                <small><?php echo nl2br(htmlspecialchars($p['valor_configuracion'])); ?></small>
                                                            </td>
                                                            <td style="max-width: 260px;">
                                                                <small><?php echo nl2br(htmlspecialchars($p['descripcion'])); ?></small>
                                                            </td>
                                                            <td class="text-end">
                                                                <a href="turnos-politicas.php?edit=<?php echo urlencode($p['clave_configuracion']); ?>"
                                                                   class="btn btn-outline-primary btn-sm">
                                                                    Editar
                                                                </a>
                                                                <a href="turnos-politicas.php?del=<?php echo urlencode($p['clave_configuracion']); ?>"
                                                                   class="btn btn-outline-danger btn-sm"
                                                                   onclick="return confirm('¿Eliminar esta política?');">
                                                                    Eliminar
                                                                </a>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
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
        <!-- /CONTENT -->

    </div>

    <!-- SCRIPTS -->
    <script src="../src/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../src/plugins/src/perfect-scrollbar/perfect-scrollbar.min.js"></script>
    <script src="../src/plugins/src/mousetrap/mousetrap.min.js"></script>
    <script src="../src/plugins/src/waves/waves.min.js"></script>
    <script src="../layouts/vertical-light-menu/app.js"></script>
</body>
</html>
