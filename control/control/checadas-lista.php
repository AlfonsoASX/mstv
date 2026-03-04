<?php
// checadas-lista.php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit;
}

include 'lib/db.php'; // Debe definir $conexion (mysqli)

// Roles que pueden ver checadas (ajusta a tu gusto)
$rol = isset($_SESSION['rol_nombre']) ? $_SESSION['rol_nombre'] : '';
$roles_permitidos = ['ADMIN', 'SUPERVISOR', 'RH', 'NOMINA', 'DUEÑO', 'CLIENTE'];

if (!in_array($rol, $roles_permitidos)) {
    echo "No tienes permisos para ver esta página.";
    exit;
}

// Helpers
function limpiar($txt) {
    return trim(htmlspecialchars($txt, ENT_QUOTES, 'UTF-8'));
}

// =======================
//  CARGAR CATÁLOGOS
// =======================
$lista_sitios   = [];
$lista_personal = [];

// Sitios
$sql_s = "SELECT id, nombre FROM sitios ORDER BY nombre";
if ($res_s = mysqli_query($conexion, $sql_s)) {
    while ($row = mysqli_fetch_assoc($res_s)) {
        $lista_sitios[] = $row;
    }
    mysqli_free_result($res_s);
}

// Personal (guardias/staff)
$sql_p = "
    SELECT p.id, p.nombres, p.apellidos, u.usuario
    FROM personal p
    INNER JOIN usuarios u ON u.id = p.usuario_id
    WHERE p.estado = 'ACTIVO'
    ORDER BY p.nombres, p.apellidos
";
if ($res_p = mysqli_query($conexion, $sql_p)) {
    while ($row = mysqli_fetch_assoc($res_p)) {
        $lista_personal[] = $row;
    }
    mysqli_free_result($res_p);
}

// =======================
//  LECTURA DE FILTROS
// =======================
$fecha_desde = isset($_GET['fecha_desde']) ? limpiar($_GET['fecha_desde']) : '';
$fecha_hasta = isset($_GET['fecha_hasta']) ? limpiar($_GET['fecha_hasta']) : '';
$sitio_id    = isset($_GET['sitio_id']) ? (int)$_GET['sitio_id'] : 0;
$personal_id = isset($_GET['personal_id']) ? (int)$_GET['personal_id'] : 0;
$tipo_evento = isset($_GET['tipo_evento']) ? limpiar($_GET['tipo_evento']) : '';
$estado      = isset($_GET['estado']) ? limpiar($_GET['estado']) : '';

// Validaciones simples
$tipos_validos   = ['ENTRADA','SALIDA','RONDIN'];
$estados_validos = ['ACEPTADO','RECHAZADO_ROSTRO','RECHAZADO_GPS','PENDIENTE_REVISION'];

if (!in_array($tipo_evento, $tipos_validos)) {
    $tipo_evento = '';
}
if (!in_array($estado, $estados_validos)) {
    $estado = '';
}

// =======================
//  CONDICIONES SQL
// =======================
$cond = " WHERE 1=1 ";

if ($sitio_id > 0) {
    $cond .= " AND ra.sitio_id = " . (int)$sitio_id . " ";
}
if ($personal_id > 0) {
    $cond .= " AND ra.personal_id = " . (int)$personal_id . " ";
}
if ($tipo_evento !== '') {
    $tipo_esc = mysqli_real_escape_string($conexion, $tipo_evento);
    $cond .= " AND ra.tipo_evento = '$tipo_esc' ";
}
if ($estado !== '') {
    $est_esc = mysqli_real_escape_string($conexion, $estado);
    $cond .= " AND ra.estado = '$est_esc' ";
}

if ($fecha_desde !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_desde)) {
    $fd_esc = mysqli_real_escape_string($conexion, $fecha_desde . " 00:00:00");
    $cond .= " AND ra.fecha_hora >= '$fd_esc' ";
}
if ($fecha_hasta !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_hasta)) {
    $fh_esc = mysqli_real_escape_string($conexion, $fecha_hasta . " 23:59:59");
    $cond .= " AND ra.fecha_hora <= '$fh_esc' ";
}

// =======================
//  PAGINACIÓN
// =======================
$pagina   = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
if ($pagina < 1) { $pagina = 1; }
$por_pagina = 50;
$offset     = ($pagina - 1) * $por_pagina;

// =======================
//  RESUMEN (totales)
// =======================
$total_registros   = 0;
$total_aceptadas   = 0;
$total_rechazadas  = 0;

$sql_resumen = "
    SELECT 
        COUNT(*) AS total,
        SUM(ra.estado = 'ACEPTADO') AS aceptadas,
        SUM(ra.estado <> 'ACEPTADO') AS rechazadas
    FROM registros_asistencia ra
    INNER JOIN personal p ON p.id = ra.personal_id
    INNER JOIN sitios s   ON s.id = ra.sitio_id
    $cond
";

if ($res_r = mysqli_query($conexion, $sql_resumen)) {
    if ($row = mysqli_fetch_assoc($res_r)) {
        $total_registros  = (int)$row['total'];
        $total_aceptadas  = (int)$row['aceptadas'];
        $total_rechazadas = (int)$row['rechazadas'];
    }
    mysqli_free_result($res_r);
}

$total_paginas = $total_registros > 0 ? ceil($total_registros / $por_pagina) : 1;

// =======================
//  CONSULTA PRINCIPAL
// =======================
$checadas = [];

$sql_lista = "
    SELECT 
        ra.*,
        p.nombres,
        p.apellidos,
        s.nombre AS sitio_nombre
    FROM registros_asistencia ra
    INNER JOIN personal p ON p.id = ra.personal_id
    INNER JOIN sitios s   ON s.id = ra.sitio_id
    $cond
    ORDER BY ra.fecha_hora DESC
    LIMIT $por_pagina OFFSET $offset
";

if ($res_l = mysqli_query($conexion, $sql_lista)) {
    while ($row = mysqli_fetch_assoc($res_l)) {
        $checadas[] = $row;
    }
    mysqli_free_result($res_l);
}

function badgeEstado($estado) {
    $estado = strtoupper($estado);
    switch ($estado) {
        case 'ACEPTADO':
            return '<span class="badge bg-success">Aceptado</span>';
        case 'RECHAZADO_ROSTRO':
            return '<span class="badge bg-danger">Rechazado rostro</span>';
        case 'RECHAZADO_GPS':
            return '<span class="badge bg-warning text-dark">Rechazado GPS</span>';
        case 'PENDIENTE_REVISION':
            return '<span class="badge bg-secondary">Pendiente</span>';
        default:
            return '<span class="badge bg-light text-dark">'.htmlspecialchars($estado).'</span>';
    }
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, shrink-to-fit=no">
    <title>Checadas y evidencias</title>
    <link rel="icon" type="image/x-icon" href="../src/assets/img/favicon.ico"/>
    <link href="../layouts/vertical-light-menu/css/light/loader.css" rel="stylesheet" type="text/css" />
    <link href="../layouts/vertical-light-menu/css/dark/loader.css" rel="stylesheet" type="text/css" />
    <script src="../layouts/vertical-light-menu/loader.js"></script>

    <!-- ESTILOS GLOBALES -->
    <link href="https://fonts.googleapis.com/css?family=Nunito:400,600,700" rel="stylesheet">
    <link href="../src/bootstrap/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
    <link href="../layouts/vertical-light-menu/css/light/plugins.css" rel="stylesheet" type="text/css" />
    <link href="../layouts/vertical-light-menu/css/dark/plugins.css" rel="stylesheet" type="text/css" />

    <!-- ESTILOS DASHBOARD -->
    <link href="../src/assets/css/light/dashboard/dash_1.css" rel="stylesheet" type="text/css" />
    <link href="../src/assets/css/dark/dashboard/dash_1.css" rel="stylesheet" type="text/css" />

    <style>
        .tabla-checadas img.selfie-thumb {
            max-width: 60px;
            max-height: 60px;
            border-radius: 4px;
            object-fit: cover;
        }
        .tabla-checadas td {
            vertical-align: middle;
        }
        .filter-label {
            font-size: 0.8rem;
            text-transform: uppercase;
            color: #888;
            margin-bottom: 2px;
        }

    </style>
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

    <!-- CONTENEDOR PRINCIPAL -->
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

        <!-- CONTENIDO -->
        <div id="content" class="main-content">
            <div class="layout-px-spacing">

                <div class="middle-content container-xxl p-0">

                    <!-- BARRA SUPERIOR / MIGAS -->
                    <div class="secondary-nav mb-3">
                        <div class="breadcrumbs-container" data-page-heading="Checadas">
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
                                    <h5 class="mb-0">Checadas y evidencias</h5>
                                    <small class="text-muted">
                                        Registros de entrada, salida y rondines con foto, geocerca y validación facial.
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
                                                 class="feather feather-list">
                                                <line x1="8" y1="6" x2="21" y2="6"></line>
                                                <line x1="8" y1="12" x2="21" y2="12"></line>
                                                <line x1="8" y1="18" x2="21" y2="18"></line>
                                                <line x1="3" y1="6" x2="3.01" y2="6"></line>
                                                <line x1="3" y1="12" x2="3.01" y2="12"></line>
                                                <line x1="3" y1="18" x2="3.01" y2="18"></line>
                                            </svg>
                                        </div>
                                        <div class="">
                                            <p class="w-value"><?php echo $total_registros; ?></p>
                                            <h5 class="">Checadas encontradas</h5>
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
                            <div class="widget widget-one_hybrid widget-engagement">
                                <div class="widget-heading">
                                    <div class="w-title">
                                        <div class="w-icon">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                 viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                 stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                                 class="feather feather-check-circle">
                                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                                <polyline points="22 4 12 14.01 9 11.01"></polyline>
                                            </svg>
                                        </div>
                                        <div class="">
                                            <p class="w-value"><?php echo $total_aceptadas; ?></p>
                                            <h5 class="">Aceptadas (rostro + GPS)</h5>
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
                            <div class="widget widget-one_hybrid widget-referral">
                                <div class="widget-heading">
                                    <div class="w-title">
                                        <div class="w-icon">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                 viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                 stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                                 class="feather feather-alert-triangle">
                                                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                                                <line x1="12" y1="9" x2="12" y2="13"></line>
                                                <line x1="12" y1="17" x2="12.01" y2="17"></line>
                                            </svg>
                                        </div>
                                        <div class="">
                                            <p class="w-value"><?php echo $total_rechazadas; ?></p>
                                            <h5 class="">Rechazadas / pendientes</h5>
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

                    <!-- FILTROS + TABLA -->
                    <div class="row layout-top-spacing">

                        <!-- FILTROS -->
                        <div class="col-xl-3 col-lg-4 col-md-12 col-sm-12 layout-spacing">
                            <div class="widget widget-card-two" style="
    padding: 20px!important;
">
                                <div class="widget-content">
                                    <div class="media mb-2">
                                        <div class="w-img">
                                            <img src="../src/assets/img/g-8.png" alt="filtros">
                                        </div>
                                        <div class="media-body">
                                            <h6>Filtros de búsqueda</h6>
                                            <p class="meta-date-time">Ajusta rango de fechas y criterios.</p>
                                        </div>
                                    </div>

                                    <form method="get" class="mt-2">
                                        <div class="mb-2">
                                            <div class="filter-label">Fecha desde</div>
                                            <input type="date" name="fecha_desde"
                                                   class="form-control form-control-sm"
                                                   value="<?php echo htmlspecialchars($fecha_desde); ?>">
                                        </div>
                                        <div class="mb-2">
                                            <div class="filter-label">Fecha hasta</div>
                                            <input type="date" name="fecha_hasta"
                                                   class="form-control form-control-sm"
                                                   value="<?php echo htmlspecialchars($fecha_hasta); ?>">
                                        </div>
                                        <div class="mb-2">
                                            <div class="filter-label">Sitio</div>
                                            <select name="sitio_id" class="form-select form-select-sm">
                                                <option value="0">Todos</option>
                                                <?php foreach ($lista_sitios as $s): ?>
                                                    <option value="<?php echo (int)$s['id']; ?>"
                                                        <?php echo $sitio_id == (int)$s['id'] ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($s['nombre']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="mb-2">
                                            <div class="filter-label">Guardia</div>
                                            <select name="personal_id" class="form-select form-select-sm">
                                                <option value="0">Todos</option>
                                                <?php foreach ($lista_personal as $p): ?>
                                                    <option value="<?php echo (int)$p['id']; ?>"
                                                        <?php echo $personal_id == (int)$p['id'] ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($p['nombres'] . ' ' . $p['apellidos']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="mb-2">
                                            <div class="filter-label">Tipo de evento</div>
                                            <select name="tipo_evento" class="form-select form-select-sm">
                                                <option value="">Todos</option>
                                                <option value="ENTRADA" <?php echo $tipo_evento=='ENTRADA'?'selected':''; ?>>Entrada</option>
                                                <option value="SALIDA" <?php echo $tipo_evento=='SALIDA'?'selected':''; ?>>Salida</option>
                                                <option value="RONDIN" <?php echo $tipo_evento=='RONDIN'?'selected':''; ?>>Rondín</option>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <div class="filter-label">Estado</div>
                                            <select name="estado" class="form-select form-select-sm">
                                                <option value="">Todos</option>
                                                <option value="ACEPTADO" <?php echo $estado=='ACEPTADO'?'selected':''; ?>>Aceptado</option>
                                                <option value="RECHAZADO_ROSTRO" <?php echo $estado=='RECHAZADO_ROSTRO'?'selected':''; ?>>Rechazado rostro</option>
                                                <option value="RECHAZADO_GPS" <?php echo $estado=='RECHAZADO_GPS'?'selected':''; ?>>Rechazado GPS</option>
                                                <option value="PENDIENTE_REVISION" <?php echo $estado=='PENDIENTE_REVISION'?'selected':''; ?>>Pendiente revisión</option>
                                            </select>
                                        </div>

                                        <div class="d-flex justify-content-between">
                                            <button type="submit" class="btn btn-primary btn-sm">Aplicar</button>
                                            <a href="checadas-lista.php" class="btn btn-light btn-sm">Limpiar</a>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- TABLA -->
                        <div class="col-xl-9 col-lg-8 col-md-12 col-sm-12 layout-spacing">
                            <div class="widget widget-table-one tabla-checadas" style="
    padding: 20px!important;
">
                                <div class="widget-heading d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="">Listado de checadas</h6>
                                        <small class="text-muted">
                                            Registros ordenados por fecha/hora (más reciente primero).
                                        </small>
                                    </div>
                                </div>
                                <div class="widget-content">

                                    <div class="table-responsive">
                                        <table class="table table-sm mb-0 align-middle">
                                            <thead>
                                                <tr>
                                                    <th>Fecha</th>
                                                    <th>Sitio</th>
                                                    <th>Guardia</th>
                                                    <th>Tipo</th>
                                                    <th>Estado</th>
                                                    <th>Geo</th>
                                                    <th>Puntaje</th>
                                                    <th>Liveness</th>
                                                    <th>Comentario</th>
                                                    <th>Selfie</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (empty($checadas)): ?>
                                                    <tr>
                                                        <td colspan="10" class="text-center text-muted py-3">
                                                            No se encontraron checadas con los filtros actuales.
                                                        </td>
                                                    </tr>
                                                <?php else: ?>
                                                    <?php foreach ($checadas as $c): ?>
                                                        <tr>
                                                            <td>
                                                                <?php
                                                                    $fh = $c['fecha_hora'];
                                                                    echo htmlspecialchars($fh);
                                                                ?>
                                                            </td>
                                                            <td><?php echo htmlspecialchars($c['sitio_nombre']); ?></td>
                                                            <td><?php echo htmlspecialchars($c['nombres'].' '.$c['apellidos']); ?></td>
                                                            <td>
                                                                <?php
                                                                    $t = $c['tipo_evento'];
                                                                    if ($t == 'ENTRADA') echo '<span class="badge bg-primary">Entrada</span>';
                                                                    elseif ($t == 'SALIDA') echo '<span class="badge bg-info">Salida</span>';
                                                                    else echo '<span class="badge bg-dark">Rondín</span>';
                                                                ?>
                                                            </td>
                                                            <td><?php echo badgeEstado($c['estado']); ?></td>
                                                            <td>
                                                                <?php if ((int)$c['esta_dentro_geocerca'] === 1): ?>
                                                                    <span class="badge bg-success">Dentro</span>
                                                                <?php else: ?>
                                                                    <span class="badge bg-danger">Fuera</span>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td>
                                                                <?php
                                                                    if ($c['puntaje_facial'] !== null) {
                                                                        echo number_format((float)$c['puntaje_facial'], 1) . ' / 100';
                                                                    } else {
                                                                        echo '--';
                                                                    }
                                                                ?>
                                                            </td>
                                                            <td>
                                                                <?php if ((int)$c['verificado_vida'] === 1): ?>
                                                                    <span class="badge bg-success">OK</span>
                                                                <?php else: ?>
                                                                    <span class="badge bg-secondary">No</span>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td style="max-width: 220px;">
                                                                <small class="text-muted">
                                                                    <?php
                                                                        $txt = trim((string)$c['comentarios']);
                                                                        if ($txt === '') {
                                                                            echo '—';
                                                                        } else {
                                                                            echo nl2br(htmlspecialchars(mb_strimwidth($txt, 0, 80, '...')));
                                                                        }
                                                                    ?>
                                                                </small>
                                                            </td>
                                                            <td>
                                                                <?php if (!empty($c['url_selfie'])): ?>
                                                                    <a href="<?php echo htmlspecialchars($c['url_selfie']); ?>"
                                                                       target="_blank">
                                                                        <img src="<?php echo htmlspecialchars($c['url_selfie']); ?>"
                                                                             alt="Selfie"
                                                                             class="selfie-thumb">
                                                                    </a>
                                                                <?php else: ?>
                                                                    <span class="text-muted">Sin foto</span>
                                                                <?php endif; ?>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>

                                    <!-- PAGINACIÓN -->
                                    <?php if ($total_paginas > 1): ?>
                                        <nav aria-label="Paginación" class="mt-3">
                                            <ul class="pagination pagination-sm justify-content-end mb-0">
                                                <?php
                                                    // build base query string sin "pagina"
                                                    $qs = $_GET;
                                                    unset($qs['pagina']);
                                                    $base_qs = http_build_query($qs);
                                                    $base_url = 'checadas-lista.php';
                                                    $sep = $base_qs ? '&' : '';
                                                ?>
                                                <li class="page-item <?php echo $pagina <= 1 ? 'disabled' : ''; ?>">
                                                    <a class="page-link"
                                                       href="<?php echo $pagina <= 1 ? '#' : $base_url.'?'.$base_qs.$sep.'pagina='.($pagina-1); ?>">
                                                        «
                                                    </a>
                                                </li>
                                                <?php
                                                // Pequeño rango de páginas
                                                $start = max(1, $pagina - 2);
                                                $end   = min($total_paginas, $pagina + 2);
                                                for ($i = $start; $i <= $end; $i++): ?>
                                                    <li class="page-item <?php echo $i == $pagina ? 'active' : ''; ?>">
                                                        <a class="page-link"
                                                           href="<?php echo $base_url.'?'.$base_qs.$sep.'pagina='.$i; ?>">
                                                            <?php echo $i; ?>
                                                        </a>
                                                    </li>
                                                <?php endfor; ?>
                                                <li class="page-item <?php echo $pagina >= $total_paginas ? 'disabled' : ''; ?>">
                                                    <a class="page-link"
                                                       href="<?php echo $pagina >= $total_paginas ? '#' : $base_url.'?'.$base_qs.$sep.'pagina='.($pagina+1); ?>">
                                                        »
                                                    </a>
                                                </li>
                                            </ul>
                                        </nav>
                                    <?php endif; ?>

                                </div>
                            </div>
                        </div>

                    </div>

                </div>

            </div>

            <?php include 'partes/footer.php'; ?>
        </div>
        <!-- /CONTENIDO -->

    </div>

    <!-- SCRIPTS -->
    <script src="../src/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../src/plugins/src/perfect-scrollbar/perfect-scrollbar.min.js"></script>
    <script src="../src/plugins/src/mousetrap/mousetrap.min.js"></script>
    <script src="../src/plugins/src/waves/waves.min.js"></script>
    <script src="../layouts/vertical-light-menu/app.js"></script>
</body>
</html>
