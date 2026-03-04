<?php
// dashboard-cliente.php
session_start();

include 'lib/db.php';

/* =========================================================
   1. VALIDAR SESIÓN
   ========================================================= */
if (!isset($_SESSION['usuario_id']) || empty($_SESSION['usuario_id'])) {
    header('Location: index.php');
    exit;
}


/* =========================================================
   3. OBTENER CLIENTE A CONSULTAR
   ========================================================= */
$cliente_id = isset($_GET['cliente_id']) ? (int) $_GET['cliente_id'] : 0;

if ($cliente_id <= 0) {
    die("Cliente no especificado. Usa la URL con ?cliente_id=ID.");
}

/* =========================================================
   4. CARGAR DATOS BÁSICOS DEL CLIENTE
   ========================================================= */
$cliente_nombre       = '';
$cliente_contacto     = '';
$cliente_telefono     = '';
$cliente_logo         = '';
$cliente_sitios       = 0;

$sqlCliente = "
    SELECT 
        nombre_empresa,
        nombre_contacto,
        telefono,
        url_logo
    FROM clientes
    WHERE id = ?
    LIMIT 1
";
if ($stmt = mysqli_prepare($conexion, $sqlCliente)) {
    mysqli_stmt_bind_param($stmt, "i", $cliente_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    if ($row = mysqli_fetch_assoc($res)) {
        $cliente_nombre   = $row['nombre_empresa'];
        $cliente_contacto = $row['nombre_contacto'];
        $cliente_telefono = $row['telefono'];
        $cliente_logo     = $row['url_logo'];
    } else {
        die("Cliente no encontrado.");
    }
    mysqli_stmt_close($stmt);
}

/* Número de sitios activos del cliente */
$sqlSitios = "SELECT COUNT(*) AS total_sitios FROM sitios WHERE cliente_id = ? AND esta_activo = 1";
if ($stmt = mysqli_prepare($conexion, $sqlSitios)) {
    mysqli_stmt_bind_param($stmt, "i", $cliente_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    if ($row = mysqli_fetch_assoc($res)) {
        $cliente_sitios = (int)$row['total_sitios'];
    }
    mysqli_stmt_close($stmt);
}

/* =========================================================
   5. RANGO DE FECHAS PARA KPIs (últimos 30 días)
   ========================================================= */
$hoy       = date('Y-m-d 23:59:59');
$hace30    = date('Y-m-d 00:00:00', strtotime('-30 days'));
$hace14    = date('Y-m-d 00:00:00', strtotime('-14 days'));

/* =========================================================
   6. KPI: PUNTUALIDAD (Entradas válidas / total Entradas)
   ========================================================= */
$kpi_puntualidad = "N/D";

$sqlPuntualidad = "
    SELECT 
        SUM(CASE WHEN ra.estado = 'ACEPTADO' AND ra.esta_dentro_geocerca = 1 THEN 1 ELSE 0 END) AS checadas_validas,
        SUM(CASE WHEN ra.tipo_evento = 'ENTRADA' THEN 1 ELSE 0 END) AS total_entradas
    FROM registros_asistencia ra
    INNER JOIN sitios s ON s.id = ra.sitio_id
    WHERE s.cliente_id = ?
      AND ra.tipo_evento = 'ENTRADA'
      AND ra.fecha_hora BETWEEN ? AND ?
";
if ($stmt = mysqli_prepare($conexion, $sqlPuntualidad)) {
    mysqli_stmt_bind_param($stmt, "iss", $cliente_id, $hace30, $hoy);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    if ($row = mysqli_fetch_assoc($res)) {
        $validas = (int)$row['checadas_validas'];
        $total   = (int)$row['total_entradas'];
        if ($total > 0) {
            $kpi_puntualidad = round(($validas / $total) * 100, 1) . "%";
        } else {
            $kpi_puntualidad = "Sin datos";
        }
    }
    mysqli_stmt_close($stmt);
}

/* =========================================================
   7. KPI: INCIDENCIAS ÚLTIMOS 30 DÍAS
   ========================================================= */
$kpi_incidencias_mes = 0;

$sqlIncMes = "
    SELECT COUNT(*) AS total_incidencias
    FROM incidencias i
    INNER JOIN sitios s ON s.id = i.sitio_id
    WHERE s.cliente_id = ?
      AND i.fecha_creacion BETWEEN ? AND ?
";
if ($stmt = mysqli_prepare($conexion, $sqlIncMes)) {
    mysqli_stmt_bind_param($stmt, "iss", $cliente_id, $hace30, $hoy);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    if ($row = mysqli_fetch_assoc($res)) {
        $kpi_incidencias_mes = (int)$row['total_incidencias'];
    }
    mysqli_stmt_close($stmt);
}

/* =========================================================
   8. KPI: GUARDIAS ACTIVOS (al menos una checada en 30 días)
   ========================================================= */
$kpi_guardias_activos = 0;

$sqlGuardias = "
    SELECT COUNT(DISTINCT ra.personal_id) AS guardias_activos
    FROM registros_asistencia ra
    INNER JOIN sitios s ON s.id = ra.sitio_id
    WHERE s.cliente_id = ?
      AND ra.fecha_hora BETWEEN ? AND ?
";
if ($stmt = mysqli_prepare($conexion, $sqlGuardias)) {
    mysqli_stmt_bind_param($stmt, "iss", $cliente_id, $hace30, $hoy);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    if ($row = mysqli_fetch_assoc($res)) {
        $kpi_guardias_activos = (int)$row['guardias_activos'];
    }
    mysqli_stmt_close($stmt);
}

/* =========================================================
   9. KPI: TURNOS EXTRA (últimos 30 días)
   ========================================================= */
$kpi_turnos_extras = 0;

$sqlExtras = "
    SELECT COUNT(*) AS total_extras
    FROM turnos t
    INNER JOIN sitios s ON s.id = t.sitio_id
    WHERE s.cliente_id = ?
      AND t.es_turno_extra = 1
      AND t.hora_inicio BETWEEN ? AND ?
";
if ($stmt = mysqli_prepare($conexion, $sqlExtras)) {
    mysqli_stmt_bind_param($stmt, "iss", $cliente_id, $hace30, $hoy);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    if ($row = mysqli_fetch_assoc($res)) {
        $kpi_turnos_extras = (int)$row['total_extras'];
    }
    mysqli_stmt_close($stmt);
}

/* =========================================================
   10. SITIOS DEL CLIENTE: puntualidad + incidencias por sitio
   ========================================================= */
$sitios_resumen = [];

$sqlSitiosDetalle = "
    SELECT 
        s.id,
        s.nombre,
        (
            SELECT 
                ROUND(
                    (SUM(CASE WHEN ra.estado = 'ACEPTADO' AND ra.esta_dentro_geocerca = 1 THEN 1 ELSE 0 END) /
                    NULLIF(SUM(CASE WHEN ra.tipo_evento = 'ENTRADA' THEN 1 ELSE 0 END), 0) * 100),
                1)
            FROM registros_asistencia ra
            WHERE ra.sitio_id = s.id
              AND ra.tipo_evento = 'ENTRADA'
              AND ra.fecha_hora BETWEEN ? AND ?
        ) AS puntualidad,
        (
            SELECT COUNT(*)
            FROM incidencias i
            WHERE i.sitio_id = s.id
              AND i.fecha_creacion BETWEEN ? AND ?
        ) AS incidencias
    FROM sitios s
    WHERE s.cliente_id = ?
      AND s.esta_activo = 1
    ORDER BY s.nombre
";

if ($stmt = mysqli_prepare($conexion, $sqlSitiosDetalle)) {
    mysqli_stmt_bind_param($stmt, "ssssi", $hace30, $hoy, $hace30, $hoy, $cliente_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($res)) {
        $sitios_resumen[] = $row;
    }
    mysqli_stmt_close($stmt);
}

/* =========================================================
   11. CHECADAS POR DÍA (últimos 14 días) PARA GRÁFICA
   ========================================================= */
$fechas = [];
$serie_validas = [];
$serie_rechazadas = [];

$sqlChecadasDia = "
    SELECT 
        DATE(ra.fecha_hora) AS fecha,
        SUM(CASE WHEN ra.estado = 'ACEPTADO' AND ra.esta_dentro_geocerca = 1 THEN 1 ELSE 0 END) AS checadas_validas,
        SUM(CASE WHEN ra.estado <> 'ACEPTADO' OR ra.esta_dentro_geocerca = 0 THEN 1 ELSE 0 END) AS checadas_rechazadas
    FROM registros_asistencia ra
    INNER JOIN sitios s ON s.id = ra.sitio_id
    WHERE s.cliente_id = ?
      AND ra.fecha_hora BETWEEN ? AND ?
    GROUP BY DATE(ra.fecha_hora)
    ORDER BY fecha
";

if ($stmt = mysqli_prepare($conexion, $sqlChecadasDia)) {
    mysqli_stmt_bind_param($stmt, "iss", $cliente_id, $hace14, $hoy);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($res)) {
        $fechas[]          = $row['fecha'];
        $serie_validas[]   = (int)$row['checadas_validas'];
        $serie_rechazadas[] = (int)$row['checadas_rechazadas'];
    }
    mysqli_stmt_close($stmt);
}

/* Si no hay datos, generamos arrays vacíos controlados para JS */
if (empty($fechas)) {
    $fechas            = [];
    $serie_validas     = [];
    $serie_rechazadas  = [];
}

/* =========================================================
   12. ÚLTIMAS INCIDENCIAS DEL CLIENTE
   ========================================================= */
$incidencias_recientes = [];

$sqlIncRecientes = "
    SELECT 
        i.fecha_creacion,
        s.nombre AS sitio_nombre,
        i.tipo,
        i.prioridad,
        i.estado
    FROM incidencias i
    INNER JOIN sitios s ON s.id = i.sitio_id
    WHERE s.cliente_id = ?
    ORDER BY i.fecha_creacion DESC
    LIMIT 5
";
if ($stmt = mysqli_prepare($conexion, $sqlIncRecientes)) {
    mysqli_stmt_bind_param($stmt, "i", $cliente_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($res)) {
        $incidencias_recientes[] = $row;
    }
    mysqli_stmt_close($stmt);
}

/* =========================================================
   13. ÚLTIMAS CHECADAS DEL CLIENTE
   ========================================================= */
$checadas_recientes = [];

$sqlChecadasRecientes = "
    SELECT 
        ra.fecha_hora,
        s.nombre AS sitio_nombre,
        ra.tipo_evento,
        ra.estado,
        ra.comentarios
    FROM registros_asistencia ra
    INNER JOIN sitios s ON s.id = ra.sitio_id
    WHERE s.cliente_id = ?
    ORDER BY ra.fecha_hora DESC
    LIMIT 6
";
if ($stmt = mysqli_prepare($conexion, $sqlChecadasRecientes)) {
    mysqli_stmt_bind_param($stmt, "i", $cliente_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($res)) {
        $checadas_recientes[] = $row;
    }
    mysqli_stmt_close($stmt);
}

/* =========================================================
   14. DATOS DE USUARIO EN SESIÓN
   ========================================================= */
$usuario_nombre = isset($_SESSION['usuario']) ? $_SESSION['usuario'] : 'Usuario';
$rol_nombre     = isset($_SESSION['rol_nombre']) ? $_SESSION['rol_nombre'] : 'SIN ROL';

/* JSON para JS */
$json_fechas           = json_encode($fechas);
$json_serie_validas    = json_encode($serie_validas);
$json_serie_rechazadas = json_encode($serie_rechazadas);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, shrink-to-fit=no">
    <title>Panel Cliente - Control de Guardias y Sitios</title>

    <link rel="icon" type="image/x-icon" href="../src/assets/img/favicon.ico"/>

    <!-- Loader -->
    <link href="../layouts/vertical-light-menu/css/light/loader.css" rel="stylesheet" type="text/css" />
    <link href="../layouts/vertical-light-menu/css/dark/loader.css" rel="stylesheet" type="text/css" />
    <script src="../layouts/vertical-light-menu/loader.js"></script>

    <!-- STYLES -->
    <link href="https://fonts.googleapis.com/css?family=Nunito:400,600,700" rel="stylesheet">
    <link href="../src/bootstrap/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
    <link href="../layouts/vertical-light-menu/css/light/plugins.css" rel="stylesheet" type="text/css" />
    <link href="../layouts/vertical-light-menu/css/dark/plugins.css" rel="stylesheet" type="text/css" />

    <!-- ApexCharts -->
    <link href="../src/plugins/src/apex/apexcharts.css" rel="stylesheet" type="text/css">
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

        <!-- CONTENT -->
        <div id="content" class="main-content">
            <div class="layout-px-spacing">

                <div class="middle-content container-xxl p-0">

                    <!-- TOP BAR / BREADCRUMBS -->
                    <div class="secondary-nav">
                        <div class="breadcrumbs-container" data-page-heading="Panel Cliente">
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

                                <div class="ms-3 d-flex align-items-center">
                                    <?php if (!empty($cliente_logo)): ?>
                                        <img src="<?php echo htmlspecialchars($cliente_logo); ?>" 
                                             alt="Logo cliente" 
                                             style="height:40px; margin-right:12px; border-radius:4px;">
                                    <?php endif; ?>
                                    <div>
                                        <h4 class="mb-0">
                                            Panel del Cliente: <?php echo htmlspecialchars($cliente_nombre); ?>
                                        </h4>
                                        <small class="text-muted">
                                            Contacto: <?php echo htmlspecialchars($cliente_contacto ?: 'N/A'); ?> • 
                                            Tel: <?php echo htmlspecialchars($cliente_telefono ?: 'N/A'); ?> • 
                                            Sitios activos: <?php echo (int)$cliente_sitios; ?><br>
                                            Usuario: <?php echo htmlspecialchars($usuario_nombre); ?> (<?php echo htmlspecialchars($rol_nombre); ?>)
                                        </small>
                                    </div>
                                </div>
                            </header>
                        </div>
                    </div>

                    <div class="row layout-top-spacing">

                        <!-- KPIs CLIENTE -->
                        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12 col-12 layout-spacing">
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
                                        <div>
                                            <p class="w-value">
                                                <?php echo htmlspecialchars($kpi_puntualidad); ?>
                                            </p>
                                            <h5>Puntualidad (últimos 30 días)</h5>
                                        </div>
                                    </div>
                                </div>
                                <div class="widget-content">
                                    <div class="w-chart">
                                        <div id="chart-cliente-puntualidad"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12 col-12 layout-spacing">
                            <div class="widget widget-one_hybrid widget-engagement">
                                <div class="widget-heading">
                                    <div class="w-title">
                                        <div class="w-icon">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                 viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                 stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                                 class="feather feather-alert-circle">
                                                <circle cx="12" cy="12" r="10"></circle>
                                                <line x1="12" y1="8" x2="12" y2="12"></line>
                                                <line x1="12" y1="16" x2="12.01" y2="16"></line>
                                            </svg>
                                        </div>
                                        <div>
                                            <p class="w-value"><?php echo (int)$kpi_incidencias_mes; ?></p>
                                            <h5>Incidencias (30 días)</h5>
                                        </div>
                                    </div>
                                </div>
                                <div class="widget-content">
                                    <div class="w-chart">
                                        <div id="chart-cliente-incidencias"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12 col-12 layout-spacing">
                            <div class="widget widget-one_hybrid widget-referral">
                                <div class="widget-heading">
                                    <div class="w-title">
                                        <div class="w-icon">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                 viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                 stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                                 class="feather feather-users">
                                                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                                <circle cx="9" cy="7" r="4"></circle>
                                                <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                                                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                                            </svg>
                                        </div>
                                        <div>
                                            <p class="w-value"><?php echo (int)$kpi_guardias_activos; ?></p>
                                            <h5>Guardias activos (30 días)</h5>
                                        </div>
                                    </div>
                                </div>
                                <div class="widget-content">
                                    <div class="w-chart">
                                        <div id="chart-cliente-guardias"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12 col-12 layout-spacing">
                            <div class="widget widget-one_hybrid widget-engagement">
                                <div class="widget-heading">
                                    <div class="w-title">
                                        <div class="w-icon">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                 viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                 stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                                 class="feather feather-briefcase">
                                                <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
                                                <path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"></path>
                                            </svg>
                                        </div>
                                        <div>
                                            <p class="w-value"><?php echo (int)$kpi_turnos_extras; ?></p>
                                            <h5>Turnos extra (30 días)</h5>
                                        </div>
                                    </div>
                                </div>
                                <div class="widget-content">
                                    <div class="w-chart">
                                        <div id="chart-cliente-extras"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- GRÁFICO CHECADAS CLIENTE -->
                        <div class="col-xl-8 col-lg-12 col-md-12 col-sm-12 col-12 layout-spacing">
                            <div class="widget widget-chart-three">
                                <div class="widget-heading">
                                    <div>
                                        <h5>Checadas por día</h5>
                                        <small class="text-muted">
                                            Últimos 14 días (válidas vs. rechazadas)
                                        </small>
                                    </div>
                                </div>

                                <div class="widget-content">
                                    <div id="chart-cliente-checadas"></div>
                                </div>
                            </div>
                        </div>

                        <!-- RESUMEN SITIOS DEL CLIENTE -->
                        <div class="col-xl-4 col-lg-12 col-md-12 col-sm-12 col-12 layout-spacing">
                            <div class="widget widget-four">
                                <div class="widget-heading">
                                    <h5>Sitios del cliente</h5>
                                    <small class="text-muted">Puntualidad e incidencias (30 días)</small>
                                </div>
                                <div class="widget-content">
                                    <div class="table-responsive">
                                        <table class="table table-sm mb-0">
                                            <thead>
                                                <tr>
                                                    <th>Sitio</th>
                                                    <th>Puntualidad</th>
                                                    <th>Incidencias</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                            <?php if (!empty($sitios_resumen)): ?>
                                                <?php foreach ($sitios_resumen as $s): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($s['nombre']); ?></td>
                                                        <td>
                                                            <?php
                                                            echo is_null($s['puntualidad'])
                                                                 ? 'Sin datos'
                                                                 : $s['puntualidad'] . '%';
                                                            ?>
                                                        </td>
                                                        <td><?php echo (int)$s['incidencias']; ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="3" class="text-center text-muted">
                                                        No hay sitios activos para este cliente.
                                                    </td>
                                                </tr>
                                            <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- TABLA INCIDENCIAS CLIENTE -->
                        <div class="col-xl-7 col-lg-12 col-md-12 col-sm-12 col-12 layout-spacing">
                            <div class="widget widget-five">
                                <div class="widget-heading">
                                    <a href="javascript:void(0);" class="task-info">
                                        <div class="usr-avatar">
                                            <span>IN</span>
                                        </div>
                                        <div class="w-title">
                                            <h5>Incidencias recientes</h5>
                                            <span>Solo lectura • PENDIENTE / EN PROCESO / CERRADO</span>
                                        </div>
                                    </a>
                                </div>

                                <div class="widget-content">
                                    <div class="table-responsive">
                                        <table class="table table-sm mb-0">
                                            <thead>
                                                <tr>
                                                    <th>Fecha</th>
                                                    <th>Sitio</th>
                                                    <th>Tipo</th>
                                                    <th>Prioridad</th>
                                                    <th>Estado</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                            <?php if (!empty($incidencias_recientes)): ?>
                                                <?php foreach ($incidencias_recientes as $inc): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($inc['fecha_creacion']); ?></td>
                                                        <td><?php echo htmlspecialchars($inc['sitio_nombre']); ?></td>
                                                        <td><?php echo htmlspecialchars($inc['tipo']); ?></td>
                                                        <td>
                                                            <span class="badge 
                                                                <?php 
                                                                    switch ($inc['prioridad']) {
                                                                        case 'CRITICA': echo 'badge-light-danger'; break;
                                                                        case 'ALTA': echo 'badge-light-warning'; break;
                                                                        case 'MEDIA': echo 'badge-light-info'; break;
                                                                        default: echo 'badge-light-secondary'; break;
                                                                    }
                                                                ?>">
                                                                <?php echo htmlspecialchars($inc['prioridad']); ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <span class="badge
                                                                <?php
                                                                    switch ($inc['estado']) {
                                                                        case 'PENDIENTE': echo 'badge-light-warning'; break;
                                                                        case 'EN_PROCESO': echo 'badge-light-info'; break;
                                                                        case 'CERRADO': echo 'badge-light-success'; break;
                                                                        default: echo 'badge-light-secondary'; break;
                                                                    }
                                                                ?>">
                                                                <?php echo htmlspecialchars($inc['estado']); ?>
                                                            </span>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="5" class="text-center text-muted">
                                                        No hay incidencias registradas.
                                                    </td>
                                                </tr>
                                            <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                            </div>
                        </div>

                        <!-- ÚLTIMAS CHECADAS DEL CLIENTE -->
                        <div class="col-xl-5 col-lg-12 col-md-12 col-sm-12 col-12 layout-spacing">
                            <div class="widget widget-card-one">
                                <div class="widget-content">

                                    <div class="media mb-3">
                                        <div class="w-img">
                                            <img src="../src/assets/img/profile-8.jpeg" alt="avatar">
                                        </div>
                                        <div class="media-body">
                                            <h6>Últimas checadas</h6>
                                            <p class="meta-date-time">Eventos recientes del cliente</p>
                                        </div>
                                    </div>

                                    <?php if (!empty($checadas_recientes)): ?>
                                        <ul class="list-unstyled mb-0">
                                            <?php foreach ($checadas_recientes as $che): ?>
                                                <li class="mb-2">
                                                    <strong><?php echo htmlspecialchars($che['tipo_evento']); ?></strong>
                                                    • <?php echo htmlspecialchars($che['sitio_nombre']); ?><br>
                                                    <small class="text-muted">
                                                        <?php echo htmlspecialchars($che['fecha_hora']); ?> • 
                                                        Estado: <?php echo htmlspecialchars($che['estado']); ?>
                                                        <?php if (!empty($che['comentarios'])): ?>
                                                            <br>Comentario: 
                                                            <?php echo htmlspecialchars($che['comentarios']); ?>
                                                        <?php endif; ?>
                                                    </small>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php else: ?>
                                        <p class="text-muted mb-0">
                                            No hay checadas registradas aún para este cliente.
                                        </p>
                                    <?php endif; ?>

                                </div>
                            </div>
                        </div>

                    </div> <!-- /.row -->

                </div> <!-- /.middle-content -->

            </div> <!-- /.layout-px-spacing -->

            <?php include 'partes/footer.php'; ?>

        </div> <!-- /#content -->

    </div> <!-- /#container -->

    <!-- SCRIPTS -->
    <script src="../src/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../src/plugins/src/perfect-scrollbar/perfect-scrollbar.min.js"></script>
    <script src="../src/plugins/src/mousetrap/mousetrap.min.js"></script>
    <script src="../src/plugins/src/waves/waves.min.js"></script>
    <script src="../layouts/vertical-light-menu/app.js"></script>

    <script src="../src/plugins/src/apex/apexcharts.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (!window.ApexCharts) return;

            // Datos PHP -> JS
            let fechas          = <?php echo $json_fechas; ?>;
            let serieValidas    = <?php echo $json_serie_validas; ?>;
            let serieRechazadas = <?php echo $json_serie_rechazadas; ?>;

            // KPI Puntualidad
            new ApexCharts(
                document.querySelector("#chart-cliente-puntualidad"),
                {
                    chart: { type: 'radialBar', height: 160, sparkline: { enabled: true } },
                    series: [
                        <?php
                        if (strpos($kpi_puntualidad, '%') !== false) {
                            echo floatval(str_replace('%', '', $kpi_puntualidad));
                        } else {
                            echo "0";
                        }
                        ?>
                    ],
                    labels: [''],
                    dataLabels: {
                        value: {
                            formatter: function (val) { return val.toFixed(1) + '%'; }
                        }
                    }
                }
            ).render();

            // KPI Incidencias (mini bar)
            new ApexCharts(
                document.querySelector("#chart-cliente-incidencias"),
                {
                    chart: { type: 'bar', height: 120, sparkline: { enabled: true } },
                    series: [{
                        name: 'Incidencias',
                        data: [<?php echo (int)$kpi_incidencias_mes; ?>]
                    }]
                }
            ).render();

            // KPI Guardias (mini line)
            new ApexCharts(
                document.querySelector("#chart-cliente-guardias"),
                {
                    chart: { type: 'line', height: 120, sparkline: { enabled: true } },
                    series: [{
                        name: 'Guardias',
                        data: [<?php echo (int)$kpi_guardias_activos; ?>]
                    }]
                }
            ).render();

            // KPI Turnos extra (mini column)
            new ApexCharts(
                document.querySelector("#chart-cliente-extras"),
                {
                    chart: { type: 'bar', height: 120, sparkline: { enabled: true } },
                    series: [{
                        name: 'Turnos extra',
                        data: [<?php echo (int)$kpi_turnos_extras; ?>]
                    }]
                }
            ).render();

            // Gráfica checadas últimos días
            new ApexCharts(
                document.querySelector("#chart-cliente-checadas"),
                {
                    chart: {
                        type: 'line',
                        height: 260,
                        toolbar: { show: false }
                    },
                    series: [
                        { name: 'Checadas válidas', data: serieValidas },
                        { name: 'Rechazadas/invalidas', data: serieRechazadas }
                    ],
                    xaxis: { categories: fechas },
                    stroke: { curve: 'smooth' }
                }
            ).render();
        });
    </script>
</body>
</html>
