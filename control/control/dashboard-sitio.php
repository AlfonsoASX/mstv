<?php
// dashboard-sitio.php
// Ahora: Dashboard general de TODOS los sitios
session_start();

include 'lib/db.php'; // Debe dejar $conexion listo (mysqli)

// =======================================
// VALIDAR SESIÓN
// =======================================
if (!isset($_SESSION['usuario_id']) || empty($_SESSION['usuario_id'])) {
    header('Location: index.php');
    exit;
}

// =======================================
// RANGOS DE FECHAS
// =======================================
$hoy    = date('Y-m-d 23:59:59');
$hace30 = date('Y-m-d 00:00:00', strtotime('-30 days'));
$hace14 = date('Y-m-d 00:00:00', strtotime('-14 days'));

// =======================================
// KPI: PUNTUALIDAD GLOBAL
// Entradas ACEPATDO + dentro geocerca / total entradas
// =======================================
$kpi_puntualidad = "N/D";

$sqlPuntualidad = "
    SELECT 
        SUM(CASE WHEN estado = 'ACEPTADO' AND esta_dentro_geocerca = 1 THEN 1 ELSE 0 END) AS checadas_validas,
        SUM(CASE WHEN tipo_evento = 'ENTRADA' THEN 1 ELSE 0 END) AS total_entradas
    FROM registros_asistencia
    WHERE tipo_evento = 'ENTRADA'
      AND fecha_hora BETWEEN ? AND ?
";

if ($stmt = mysqli_prepare($conexion, $sqlPuntualidad)) {
    mysqli_stmt_bind_param($stmt, "ss", $hace30, $hoy);
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

// =======================================
// KPI: INCIDENCIAS GLOBALES (30 días)
// =======================================
$kpi_incidencias_mes = 0;

$sqlIncMes = "
    SELECT COUNT(*) AS total_incidencias
    FROM incidencias
    WHERE fecha_creacion BETWEEN ? AND ?
";

if ($stmt = mysqli_prepare($conexion, $sqlIncMes)) {
    mysqli_stmt_bind_param($stmt, "ss", $hace30, $hoy);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);

    if ($row = mysqli_fetch_assoc($res)) {
        $kpi_incidencias_mes = (int)$row['total_incidencias'];
    }
    mysqli_stmt_close($stmt);
}

// =======================================
// KPI: GUARDIAS ACTIVOS GLOBALES (30 días)
// Guardias que han hecho al menos una checada
// =======================================
$kpi_guardias_activos = 0;

$sqlGuardias = "
    SELECT COUNT(DISTINCT personal_id) AS guardias_activos
    FROM registros_asistencia
    WHERE fecha_hora BETWEEN ? AND ?
";

if ($stmt = mysqli_prepare($conexion, $sqlGuardias)) {
    mysqli_stmt_bind_param($stmt, "ss", $hace30, $hoy);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);

    if ($row = mysqli_fetch_assoc($res)) {
        $kpi_guardias_activos = (int)$row['guardias_activos'];
    }
    mysqli_stmt_close($stmt);
}

// =======================================
// KPI: TURNOS EXTRA GLOBALES (30 días)
// =======================================
$kpi_turnos_extras = 0;

$sqlExtras = "
    SELECT COUNT(*) AS total_extras
    FROM turnos
    WHERE es_turno_extra = 1
      AND hora_inicio BETWEEN ? AND ?
";

if ($stmt = mysqli_prepare($conexion, $sqlExtras)) {
    mysqli_stmt_bind_param($stmt, "ss", $hace30, $hoy);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);

    if ($row = mysqli_fetch_assoc($res)) {
        $kpi_turnos_extras = (int)$row['total_extras'];
    }
    mysqli_stmt_close($stmt);
}

// =======================================
// GRÁFICO: CHECADAS POR DÍA GLOBAL (últimos 14 días)
// =======================================
$fechas           = [];
$serie_validas    = [];
$serie_rechazadas = [];

$sqlChecadasDia = "
    SELECT 
        DATE(fecha_hora) AS fecha,
        SUM(CASE WHEN estado = 'ACEPTADO' AND esta_dentro_geocerca = 1 THEN 1 ELSE 0 END) AS checadas_validas,
        SUM(CASE WHEN estado <> 'ACEPTADO' OR esta_dentro_geocerca = 0 THEN 1 ELSE 0 END) AS checadas_rechazadas
    FROM registros_asistencia
    WHERE fecha_hora BETWEEN ? AND ?
    GROUP BY DATE(fecha_hora)
    ORDER BY fecha
";

if ($stmt = mysqli_prepare($conexion, $sqlChecadasDia)) {
    mysqli_stmt_bind_param($stmt, "ss", $hace14, $hoy);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);

    while ($row = mysqli_fetch_assoc($res)) {
        $fechas[]           = $row['fecha'];
        $serie_validas[]    = (int)$row['checadas_validas'];
        $serie_rechazadas[] = (int)$row['checadas_rechazadas'];
    }
    mysqli_stmt_close($stmt);
}

// =======================================
// TABLA: CHECADAS RECIENTES GLOBALES
// =======================================
$checadas_recientes = [];

$sqlChecadasRecientes = "
    SELECT 
        ra.fecha_hora,
        ra.tipo_evento,
        ra.estado,
        ra.comentarios,
        p.nombres,
        p.apellidos,
        s.nombre AS sitio_nombre
    FROM registros_asistencia ra
    INNER JOIN personal p ON p.id = ra.personal_id
    INNER JOIN sitios  s ON s.id = ra.sitio_id
    ORDER BY ra.fecha_hora DESC
    LIMIT 8
";

if ($stmt = mysqli_prepare($conexion, $sqlChecadasRecientes)) {
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);

    while ($row = mysqli_fetch_assoc($res)) {
        $checadas_recientes[] = $row;
    }
    mysqli_stmt_close($stmt);
}

// =======================================
// TABLA: INCIDENCIAS RECIENTES GLOBALES
// =======================================
$incidencias_recientes = [];

$sqlIncRecientes = "
    SELECT 
        i.fecha_creacion,
        i.tipo,
        i.prioridad,
        i.estado,
        i.descripcion,
        s.nombre AS sitio_nombre
    FROM incidencias i
    INNER JOIN sitios s ON s.id = i.sitio_id
    ORDER BY i.fecha_creacion DESC
    LIMIT 6
";

if ($stmt = mysqli_prepare($conexion, $sqlIncRecientes)) {
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);

    while ($row = mysqli_fetch_assoc($res)) {
        $incidencias_recientes[] = $row;
    }
    mysqli_stmt_close($stmt);
}

// =======================================
// RESUMEN POR SITIO (30 días)
// Sitios + cliente + guardias activos + checadas
// =======================================
$sitios_resumen = [];

$sqlSitiosResumen = "
    SELECT 
        s.id,
        s.nombre AS sitio_nombre,
        c.nombre_empresa AS cliente_nombre,
        COUNT(DISTINCT ra.personal_id) AS guardias_activos,
        COUNT(ra.id) AS checadas_totales,
        SUM(CASE WHEN ra.estado = 'ACEPTADO' AND ra.esta_dentro_geocerca = 1 THEN 1 ELSE 0 END) AS checadas_validas
    FROM sitios s
    LEFT JOIN clientes c ON c.id = s.cliente_id
    LEFT JOIN registros_asistencia ra 
        ON ra.sitio_id = s.id
       AND ra.fecha_hora BETWEEN ? AND ?
    GROUP BY s.id, s.nombre, c.nombre_empresa
    ORDER BY checadas_validas DESC, checadas_totales DESC
";

if ($stmt = mysqli_prepare($conexion, $sqlSitiosResumen)) {
    mysqli_stmt_bind_param($stmt, "ss", $hace30, $hoy);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);

    while ($row = mysqli_fetch_assoc($res)) {
        $sitios_resumen[] = $row;
    }
    mysqli_stmt_close($stmt);
}

// =======================================
// DATOS DE SESIÓN
// =======================================
$usuario_nombre = isset($_SESSION['usuario']) ? $_SESSION['usuario'] : 'Usuario';
$rol_nombre     = isset($_SESSION['rol_nombre']) ? $_SESSION['rol_nombre'] : 'SIN ROL';

// JSON para JS
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
    <title>Dashboard Sitios - Resumen General</title>

    <link rel="icon" type="image/x-icon" href="../src/assets/img/favicon.ico"/>
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
        <!-- /SIDEBAR -->

        <!-- CONTENT -->
        <div id="content" class="main-content">
            <div class="layout-px-spacing">

                <div class="middle-content container-xxl p-0">

                    <!-- TOP BAR -->
                    <div class="secondary-nav">
                        <div class="breadcrumbs-container" data-page-heading="Dashboard Sitios">
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
                                    <div>
                                        <h4 class="mb-0">
                                            Resumen general de todos los sitios
                                        </h4>
                                        <small class="text-muted">
                                            Usuario: <?php echo htmlspecialchars($usuario_nombre); ?> (<?php echo htmlspecialchars($rol_nombre); ?>)
                                            
                                        </small>
                                    </div>
                                </div>
                            </header>
                        </div>
                    </div>
                    <br>
                    <h1>Ventana de análisis: últimos 30 días (KPIs) y 14 días (gráfico diario)</h1>

                    <div class="row layout-top-spacing">

                        <!-- KPIs -->
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
                                            <p class="w-value"><?php echo htmlspecialchars($kpi_puntualidad); ?></p>
                                            <h5>Puntualidad global (30 días)</h5>
                                        </div>
                                    </div>
                                </div>
                                <div class="widget-content">
                                    <div class="w-chart">
                                        <div id="chart-sitio-puntualidad"></div>
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
                                        <div id="chart-sitio-incidencias"></div>
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
                                        <div id="chart-sitio-guardias"></div>
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
                                        <div id="chart-sitio-extras"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- GRÁFICO CHECADAS POR DÍA -->
                        <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12 layout-spacing">
                            <div class="widget widget-chart-three">
                                <div class="widget-heading">
                                    <div>
                                        <h5>Checadas de todos los sitios</h5>
                                        <small class="text-muted">
                                            Últimos 14 días (válidas vs. rechazadas/invalidas)
                                        </small>
                                    </div>
                                </div>

                                <div class="widget-content">
                                    <div id="chart-sitio-checadas"></div>
                                </div>
                            </div>
                        </div>

                        <!-- RESUMEN POR SITIO -->
                        <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12 layout-spacing">
                            <div class="widget widget-four">
                                <div class="widget-heading">
                                    <h5>Resumen por sitio</h5>
                                    <small class="text-muted">Checadas válidas vs. total (30 días)</small>
                                </div>
                                <div class="widget-content">
                                    <div class="table-responsive">
                                        <table class="table table-sm mb-0">
                                            <thead>
                                                <tr>
                                                    <th>Sitio</th>
                                                    <th>Cliente</th>
                                                    <th>Válidas / Total</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                            <?php if (!empty($sitios_resumen)): ?>
                                                <?php foreach ($sitios_resumen as $s): 
                                                    $total = (int)$s['checadas_totales'];
                                                    $valid = (int)$s['checadas_validas'];
                                                    $porc  = $total > 0 ? round($valid / $total * 100) : 0;
                                                ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($s['sitio_nombre']); ?></td>
                                                        <td><?php echo htmlspecialchars($s['cliente_nombre'] ?: '-'); ?></td>
                                                        <td><?php echo $valid; ?> / <?php echo $total; ?> (<?php echo $porc; ?>%)</td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="3" class="text-center text-muted">
                                                        Aún no hay sitios o checadas registradas.
                                                    </td>
                                                </tr>
                                            <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- INCIDENCIAS RECIENTES -->
                        <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12 layout-spacing">
                            <div class="widget widget-five">
                                <div class="widget-heading">
                                    <a href="javascript:void(0)" class="task-info">
                                        <div class="usr-avatar">
                                            <span>IN</span>
                                        </div>
                                        <div class="w-title">
                                            <h5>Incidencias recientes</h5>
                                            <span>Todos los sitios</span>
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

           

                    </div> <!-- /row -->

                </div> <!-- /middle-content -->

            </div> <!-- /layout-px-spacing -->

            <?php include 'partes/footer.php'; ?>

        </div> <!-- /content -->

    </div> <!-- /container -->

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

            let fechas          = <?php echo $json_fechas; ?>;
            let serieValidas    = <?php echo $json_serie_validas; ?>;
            let serieRechazadas = <?php echo $json_serie_rechazadas; ?>;

            // Puntualidad
            new ApexCharts(
                document.querySelector("#chart-sitio-puntualidad"),
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

            // Incidencias
            new ApexCharts(
                document.querySelector("#chart-sitio-incidencias"),
                {
                    chart: { type: 'bar', height: 120, sparkline: { enabled: true } },
                    series: [{
                        name: 'Incidencias',
                        data: [<?php echo (int)$kpi_incidencias_mes; ?>]
                    }]
                }
            ).render();

            // Guardias
            new ApexCharts(
                document.querySelector("#chart-sitio-guardias"),
                {
                    chart: { type: 'line', height: 120, sparkline: { enabled: true } },
                    series: [{
                        name: 'Guardias activos',
                        data: [<?php echo (int)$kpi_guardias_activos; ?>]
                    }]
                }
            ).render();

            // Turnos extra
            new ApexCharts(
                document.querySelector("#chart-sitio-extras"),
                {
                    chart: { type: 'bar', height: 120, sparkline: { enabled: true } },
                    series: [{
                        name: 'Turnos extra',
                        data: [<?php echo (int)$kpi_turnos_extras; ?>]
                    }]
                }
            ).render();

            // Checadas por día (global)
            new ApexCharts(
                document.querySelector("#chart-sitio-checadas"),
                {
                    chart: {
                        type: 'line',
                        height: 260,
                        toolbar: { show: false }
                    },
                    series: [
                        { name: 'Válidas', data: serieValidas },
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
