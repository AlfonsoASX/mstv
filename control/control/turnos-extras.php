<?php
// turnos_extras.php
session_start();

// Verificación de sesión
if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit;
}

include 'lib/db.php'; // Debe definir $conexion (mysqli)

// Opcional: limitar acceso por rol
$rol = isset($_SESSION['rol_nombre']) ? $_SESSION['rol_nombre'] : '';
$roles_permitidos = ['ADMIN', 'SUPERVISOR', 'NOMINA'];
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
   1) GUARDAR AJUSTE MANUAL DE HORAS (BONO / HORA_MENOS)
   ============================================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'guardar_ajuste') {

    $personal_id      = isset($_POST['personal_id']) ? (int)$_POST['personal_id'] : 0;
    $tipo_ajuste      = isset($_POST['tipo_ajuste']) ? limpiar($_POST['tipo_ajuste']) : '';
    $horas            = isset($_POST['horas']) ? (float)$_POST['horas'] : 0;
    $fecha_aplicacion = isset($_POST['fecha_aplicacion']) ? limpiar($_POST['fecha_aplicacion']) : date('Y-m-d');
    $motivo           = isset($_POST['motivo']) ? limpiar($_POST['motivo']) : '';

    if ($personal_id <= 0 || $horas <= 0 || $motivo === '' || !in_array($tipo_ajuste, ['BONO', 'HORA_MENOS'])) {
        $mensaje_err = "Verifica: guardia, tipo de ajuste, horas (> 0) y motivo.";
    } else {

        $ts_f = strtotime($fecha_aplicacion);
        if ($ts_f === false) {
            $fecha_aplicacion = date('Y-m-d');
        } else {
            $fecha_aplicacion = date('Y-m-d', $ts_f);
        }

        $supervisor_id = (int)$_SESSION['usuario_id'];

        $sql_ins = "
            INSERT INTO ajustes_nomina
                (personal_id, supervisor_id, tipo_ajuste, monto, horas, motivo, fecha_aplicacion)
            VALUES (?, ?, ?, NULL, ?, ?, ?)
        ";

        if ($stmt = mysqli_prepare($conexion, $sql_ins)) {

            // i = int, i = int, s = string, d = double, s = string, s = string
            mysqli_stmt_bind_param(
                $stmt,
                "iisdss",
                $personal_id,
                $supervisor_id,
                $tipo_ajuste,
                $horas,
                $motivo,
                $fecha_aplicacion
            );

            if (mysqli_stmt_execute($stmt)) {
                $mensaje_ok = "Ajuste de horas guardado correctamente.";
            } else {
                $mensaje_err = "Error al guardar el ajuste: " . mysqli_error($conexion);
            }

            mysqli_stmt_close($stmt);
        } else {
            $mensaje_err = "Error al preparar la consulta de ajuste: " . mysqli_error($conexion);
        }
    }
}

/* ============================================================
   2) CATÁLOGOS: SITIOS Y GUARDIAS
   ============================================================ */

// Sitios
$sitios = [];
$sql_sitios = "SELECT id, nombre FROM sitios WHERE esta_activo = 1 ORDER BY nombre";
if ($res_s = mysqli_query($conexion, $sql_sitios)) {
    while ($row = mysqli_fetch_assoc($res_s)) {
        $sitios[] = $row;
    }
    mysqli_free_result($res_s);
}

// Guardias
$guardias = [];
$sql_guardias = "
    SELECT 
        p.id AS personal_id,
        p.nombres,
        p.apellidos,
        u.usuario
    FROM personal p
    INNER JOIN usuarios u ON u.id = p.usuario_id
    INNER JOIN roles r    ON r.id = u.rol_id
    WHERE p.estado = 'ACTIVO'
      AND r.nombre = 'GUARDIA'
    ORDER BY p.nombres, p.apellidos
";
if ($res_g = mysqli_query($conexion, $sql_guardias)) {
    while ($row = mysqli_fetch_assoc($res_g)) {
        $guardias[] = $row;
    }
    mysqli_free_result($res_g);
}

/* ============================================================
   3) FILTROS
   ============================================================ */

$f_sitio_id    = isset($_GET['sitio_id']) ? (int)$_GET['sitio_id'] : 0;
$f_personal_id = isset($_GET['personal_id']) ? (int)$_GET['personal_id'] : 0;
$f_desde       = isset($_GET['desde']) ? limpiar($_GET['desde']) : '';
$f_hasta       = isset($_GET['hasta']) ? limpiar($_GET['hasta']) : '';

/* ============================================================
   4) TURNOS EXTRA (AGENDA)
   ============================================================ */

$turnos_extra       = [];
$total_horas_turnos = 0;

$where_turnos = "WHERE t.es_turno_extra = 1";

if ($f_sitio_id > 0) {
    $where_turnos .= " AND t.sitio_id = " . (int)$f_sitio_id;
}
if ($f_personal_id > 0) {
    $where_turnos .= " AND t.personal_id = " . (int)$f_personal_id;
}
if ($f_desde !== '') {
    $ts = strtotime($f_desde);
    if ($ts !== false) {
        $desde_sql = date('Y-m-d', $ts);
        $where_turnos .= " AND DATE(t.hora_inicio) >= '" . mysqli_real_escape_string($conexion, $desde_sql) . "'";
    }
}
if ($f_hasta !== '') {
    $ts = strtotime($f_hasta);
    if ($ts !== false) {
        $hasta_sql = date('Y-m-d', $ts);
        $where_turnos .= " AND DATE(t.hora_inicio) <= '" . mysqli_real_escape_string($conexion, $hasta_sql) . "'";
    }
}

$sql_turnos = "
    SELECT
        t.id,
        t.hora_inicio,
        t.hora_fin,
        t.estado,
        s.nombre AS sitio_nombre,
        p.nombres,
        p.apellidos
    FROM turnos t
    INNER JOIN sitios   s ON s.id = t.sitio_id
    INNER JOIN personal p ON p.id = t.personal_id
    $where_turnos
    ORDER BY t.hora_inicio DESC
    LIMIT 200
";

if ($res_t = mysqli_query($conexion, $sql_turnos)) {
    while ($row = mysqli_fetch_assoc($res_t)) {
        $inicio_ts = strtotime($row['hora_inicio']);
        $fin_ts    = strtotime($row['hora_fin']);
        $horas     = 0;

        if ($inicio_ts !== false && $fin_ts !== false && $fin_ts > $inicio_ts) {
            $horas = round(($fin_ts - $inicio_ts) / 3600, 2);
        }

        $row['horas'] = $horas;
        $total_horas_turnos += $horas;

        $turnos_extra[] = $row;
    }
    mysqli_free_result($res_t);
}

/* ============================================================
   5) AJUSTES DE HORAS (BONO / HORA_MENOS)
   ============================================================ */

$ajustes             = [];
$total_horas_ajustes = 0;

$sql_ajustes = "
    SELECT 
        aj.id,
        aj.personal_id,
        aj.tipo_ajuste,
        aj.horas,
        aj.motivo,
        aj.fecha_aplicacion,
        p.nombres,
        p.apellidos,
        u.usuario AS supervisor_usuario
    FROM ajustes_nomina aj
    INNER JOIN personal p ON p.id = aj.personal_id
    INNER JOIN usuarios u ON u.id = aj.supervisor_id
    WHERE aj.tipo_ajuste IN ('BONO','HORA_MENOS')
";

if ($f_personal_id > 0) {
    $sql_ajustes .= " AND aj.personal_id = " . (int)$f_personal_id . " ";
}
if ($f_desde !== '') {
    $ts = strtotime($f_desde);
    if ($ts !== false) {
        $desde_sql = date('Y-m-d', $ts);
        $sql_ajustes .= " AND aj.fecha_aplicacion >= '" . mysqli_real_escape_string($conexion, $desde_sql) . "' ";
    }
}
if ($f_hasta !== '') {
    $ts = strtotime($f_hasta);
    if ($ts !== false) {
        $hasta_sql = date('Y-m-d', $ts);
        $sql_ajustes .= " AND aj.fecha_aplicacion <= '" . mysqli_real_escape_string($conexion, $hasta_sql) . "' ";
    }
}

$sql_ajustes .= " ORDER BY aj.fecha_aplicacion DESC, aj.id DESC LIMIT 200";

if ($res_a = mysqli_query($conexion, $sql_ajustes)) {
    while ($row = mysqli_fetch_assoc($res_a)) {
        $horas = (float)$row['horas'];

        if ($row['tipo_ajuste'] === 'HORA_MENOS') {
            $total_horas_ajustes -= $horas;
        } else { // BONO
            $total_horas_ajustes += $horas;
        }

        $ajustes[] = $row;
    }
    mysqli_free_result($res_a);
}

// TOTAL GLOBAL
$total_horas_global = $total_horas_turnos + $total_horas_ajustes;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, shrink-to-fit=no">
    <title>Horas Extra y Ajustes | Panel Supervisor</title>
    <link rel="icon" type="image/x-icon" href="../src/assets/img/favicon.ico"/>
    <link href="../layouts/vertical-light-menu/css/light/loader.css" rel="stylesheet" type="text/css" />
    <link href="../layouts/vertical-light-menu/css/dark/loader.css" rel="stylesheet" type="text/css" />
    <script src="../layouts/vertical-light-menu/loader.js"></script>

    <!-- GLOBAL STYLES -->
    <link href="https://fonts.googleapis.com/css?family=Nunito:400,600,700" rel="stylesheet">
    <link href="../src/bootstrap/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
    <link href="../layouts/vertical-light-menu/css/light/plugins.css" rel="stylesheet" type="text/css" />
    <link href="../layouts/vertical-light-menu/css/dark/plugins.css" rel="stylesheet" type="text/css" />

    <!-- DASHBOARD STYLES (para widgets estéticos) -->
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

                    <!-- BARRA SUPERIOR / BREADCRUMB -->
                    <div class="secondary-nav mb-3">
                        <div class="breadcrumbs-container" data-page-heading="Horas Extra">
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
                                    <h5 class="mb-0">Gestión de Horas Extra</h5>
                                    <small class="text-muted">Turnos extra, bonos y descuentos de horas para nómina.</small>
                                </div>
                            </header>
                        </div>
                    </div>

                    <!-- FILA DE WIDGETS RESUMEN -->
                    <div class="row layout-top-spacing mb-3">

                        <div class="col-xl-4 col-lg-4 col-md-4 col-sm-12 col-12 layout-spacing">
                            <div class="widget widget-one_hybrid widget-followers">
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
                                            <p class="w-value"><?php echo number_format($total_horas_turnos, 2); ?> h</p>
                                            <h5 class="">Turnos extra (agenda)</h5>
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
                                                 class="feather feather-sliders">
                                                <line x1="4" y1="21" x2="4" y2="14"></line>
                                                <line x1="4" y1="10" x2="4" y2="3"></line>
                                                <line x1="12" y1="21" x2="12" y2="12"></line>
                                                <line x1="12" y1="8" x2="12" y2="3"></line>
                                                <line x1="20" y1="21" x2="20" y2="16"></line>
                                                <line x1="20" y1="12" x2="20" y2="3"></line>
                                                <line x1="1" y1="14" x2="7" y2="14"></line>
                                                <line x1="9" y1="8" x2="15" y2="8"></line>
                                                <line x1="17" y1="16" x2="23" y2="16"></line>
                                            </svg>
                                        </div>
                                        <div class="">
                                            <p class="w-value">
                                                <?php echo number_format($total_horas_ajustes, 2); ?> h
                                            </p>
                                            <h5 class="">Ajustes netos (bonos / hora menos)</h5>
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
                                                <?php echo number_format($total_horas_global, 2); ?> h
                                            </p>
                                            <h5 class="">Total global para nómina</h5>
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

                    <!-- CUERPO PRINCIPAL -->
                    <div class="row layout-top-spacing">

                        <!-- IZQUIERDA: FILTROS + FORM -->
                        <div class="col-xl-4 col-lg-5 col-md-12 col-sm-12 layout-spacing">

                            <!-- Filtros -->
                            <div class="widget widget-card-one mb-4" style="padding: 20px!important;">
                                <div class="widget-content">
                                    <h6 class="mb-3">Filtros</h6>
                                    <form method="get" class="row g-2">
                                        <div class="col-12">
                                            <label class="form-label">Sitio</label>
                                            <select name="sitio_id" class="form-select form-select-sm">
                                                <option value="0">Todos</option>
                                                <?php foreach ($sitios as $s): ?>
                                                    <option value="<?php echo $s['id']; ?>"
                                                        <?php echo ($f_sitio_id == $s['id']) ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($s['nombre']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label">Guardia</label>
                                            <select name="personal_id" class="form-select form-select-sm">
                                                <option value="0">Todos</option>
                                                <?php foreach ($guardias as $g): ?>
                                                    <option value="<?php echo $g['personal_id']; ?>"
                                                        <?php echo ($f_personal_id == $g['personal_id']) ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($g['nombres'] . ' ' . $g['apellidos']); ?>
                                                        (<?php echo htmlspecialchars($g['usuario']); ?>)
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label">Desde</label>
                                            <input type="date" name="desde" class="form-control form-control-sm"
                                                   value="<?php echo htmlspecialchars($f_desde); ?>">
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label">Hasta</label>
                                            <input type="date" name="hasta" class="form-control form-control-sm"
                                                   value="<?php echo htmlspecialchars($f_hasta); ?>">
                                        </div>
                                        <div class="col-12 text-end mt-2">
                                            <button type="submit" class="btn btn-primary btn-sm">Aplicar</button>
                                            <a href="turnos_extras.php" class="btn btn-light btn-sm">Limpiar</a>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            <!-- Formulario de ajustes -->
                            <div class="widget widget-card-two" style="padding: 20px!important;">
                                <div class="widget-content">
                                    <div class="media mb-2">
                                        <div class="w-img">
                                            <img src="../src/assets/img/money-bag.png" alt="ajustes">
                                        </div>
                                        <div class="media-body">
                                            <h6>Registrar ajuste de horas</h6>
                                            <p class="meta-date-time">Bono (pago extra) o Hora menos (descuento).</p>
                                        </div>
                                    </div>

                                    <form method="post">
                                        <input type="hidden" name="accion" value="guardar_ajuste">

                                        <div class="mb-2">
                                            <label class="form-label">Guardia</label>
                                            <select name="personal_id" class="form-select form-select-sm" required>
                                                <option value="">Selecciona un guardia</option>
                                                <?php foreach ($guardias as $g): ?>
                                                    <option value="<?php echo $g['personal_id']; ?>">
                                                        <?php echo htmlspecialchars($g['nombres'] . ' ' . $g['apellidos']); ?>
                                                        (<?php echo htmlspecialchars($g['usuario']); ?>)
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div class="mb-2">
                                            <label class="form-label">Tipo de ajuste</label>
                                            <select name="tipo_ajuste" class="form-select form-select-sm" required>
                                                <option value="BONO">Bono (paga horas extra)</option>
                                                <option value="HORA_MENOS">Hora menos (descuento)</option>
                                            </select>
                                        </div>

                                        <div class="mb-2">
                                            <label class="form-label">Horas</label>
                                            <input type="number" step="0.01" min="0.01" name="horas"
                                                   class="form-control form-control-sm" required>
                                        </div>

                                        <div class="mb-2">
                                            <label class="form-label">Fecha de aplicación</label>
                                            <input type="date" name="fecha_aplicacion"
                                                   class="form-control form-control-sm"
                                                   value="<?php echo date('Y-m-d'); ?>" required>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Motivo</label>
                                            <textarea name="motivo" rows="3" class="form-control form-control-sm"
                                                      placeholder="Ej. Guardia apoyó en evento especial, se descuenta por salida anticipada, etc."
                                                      required></textarea>
                                        </div>

                                        <div class="text-end">
                                            <button type="submit" class="btn btn-success btn-sm">
                                                Guardar ajuste
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>

                        </div>

                        <!-- DERECHA: TABLAS -->
                        <div class="col-xl-8 col-lg-7 col-md-12 col-sm-12 layout-spacing">

                            <!-- Turnos extra -->
                            <div class="widget widget-table-one mb-4">
                                <div class="widget-heading d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="">Turnos extra (por agenda)</h6>
                                        <small class="text-muted">
                                            Turnos marcados como extra para los guardias seleccionados.
                                        </small>
                                    </div>
                                </div>
                                <div class="widget-content">
                                    <div class="table-responsive">
                                        <table class="table table-sm mb-0 align-middle">
                                            <thead>
                                                <tr>
                                                    <th>Sitio</th>
                                                    <th>Guardia</th>
                                                    <th>Inicio</th>
                                                    <th>Fin</th>
                                                    <th>Horas</th>
                                                    <th>Estado</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (empty($turnos_extra)): ?>
                                                    <tr>
                                                        <td colspan="6" class="text-center text-muted py-3">
                                                            No hay turnos extra con los filtros seleccionados.
                                                        </td>
                                                    </tr>
                                                <?php else: ?>
                                                    <?php foreach ($turnos_extra as $t): ?>
                                                        <tr>
                                                            <td><?php echo htmlspecialchars($t['sitio_nombre']); ?></td>
                                                            <td><?php echo htmlspecialchars($t['nombres'] . ' ' . $t['apellidos']); ?></td>
                                                            <td><?php echo date('d/m/Y H:i', strtotime($t['hora_inicio'])); ?></td>
                                                            <td><?php echo date('d/m/Y H:i', strtotime($t['hora_fin'])); ?></td>
                                                            <td><?php echo number_format($t['horas'], 2); ?> h</td>
                                                            <td>
                                                                <span class="badge
                                                                    <?php
                                                                        switch ($t['estado']) {
                                                                            case 'EN_PROGRESO': echo 'bg-info'; break;
                                                                            case 'COMPLETADO':  echo 'bg-success'; break;
                                                                            case 'AUSENTE':     echo 'bg-danger'; break;
                                                                            default:            echo 'bg-secondary'; break;
                                                                        }
                                                                    ?>">
                                                                    <?php echo htmlspecialchars($t['estado']); ?>
                                                                </span>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <!-- Ajustes manuales -->
                            <div class="widget widget-table-one">
                                <div class="widget-heading d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="">Ajustes manuales de horas</h6>
                                        <small class="text-muted">
                                            Bonos (pago extra) y Horas menos (descuento) aplicados a los guardias.
                                        </small>
                                    </div>
                                </div>
                                <div class="widget-content">
                                    <div class="table-responsive">
                                        <table class="table table-sm mb-0 align-middle">
                                            <thead>
                                                <tr>
                                                    <th>Guardia</th>
                                                    <th>Tipo</th>
                                                    <th>Horas</th>
                                                    <th>Fecha</th>
                                                    <th>Supervisor</th>
                                                    <th>Motivo</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (empty($ajustes)): ?>
                                                    <tr>
                                                        <td colspan="6" class="text-center text-muted py-3">
                                                            No hay ajustes registrados con los filtros seleccionados.
                                                        </td>
                                                    </tr>
                                                <?php else: ?>
                                                    <?php foreach ($ajustes as $a): ?>
                                                        <tr>
                                                            <td><?php echo htmlspecialchars($a['nombres'] . ' ' . $a['apellidos']); ?></td>
                                                            <td>
                                                                <?php if ($a['tipo_ajuste'] === 'HORA_MENOS'): ?>
                                                                    <span class="badge bg-danger">Hora menos</span>
                                                                <?php else: ?>
                                                                    <span class="badge bg-success">Bono</span>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td><?php echo number_format($a['horas'], 2); ?> h</td>
                                                            <td><?php echo date('d/m/Y', strtotime($a['fecha_aplicacion'])); ?></td>
                                                            <td><?php echo htmlspecialchars($a['supervisor_usuario']); ?></td>
                                                            <td style="max-width: 260px;">
                                                                <small><?php echo nl2br(htmlspecialchars($a['motivo'])); ?></small>
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
