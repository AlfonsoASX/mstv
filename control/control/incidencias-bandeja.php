<?php
// incidencias-bandeja.php
session_start();

// Conexión central
require_once 'lib/db.php';

// Si no hay sesión, fuera
if (!isset($_SESSION['usuario_id'])) {
    header('Location: index.php');
    exit;
}

// Rol del usuario (por si luego quieres restringir)
$rol_nombre = isset($_SESSION['rol_nombre']) ? $_SESSION['rol_nombre'] : null;

/* =========================================================
   Filtros desde GET
   ========================================================= */
$f_sitio     = isset($_GET['sitio_id'])     ? (int)$_GET['sitio_id'] : 0;
$f_estado    = isset($_GET['estado'])       ? trim($_GET['estado']) : '';
$f_tipo      = isset($_GET['tipo'])         ? trim($_GET['tipo']) : '';
$f_prioridad = isset($_GET['prioridad'])    ? trim($_GET['prioridad']) : '';
$f_desde     = isset($_GET['fecha_desde'])  ? trim($_GET['fecha_desde']) : '';
$f_hasta     = isset($_GET['fecha_hasta'])  ? trim($_GET['fecha_hasta']) : '';
$f_buscar    = isset($_GET['buscar'])       ? trim($_GET['buscar']) : '';

/* Whitelists para ENUM (evitar inyección tonta) */
$estados_validos    = ['PENDIENTE','EN_PROCESO','CERRADO'];
$tipos_validos      = ['SEGURIDAD','OPERACION','MANTENIMIENTO','URGENTE'];
$prioridades_validas= ['BAJA','MEDIA','ALTA','CRITICA'];

/* =========================================================
   Catálogo de sitios para filtros
   ========================================================= */
$lista_sitios = [];
$sql_sitios = "SELECT id, nombre FROM sitios WHERE esta_activo = 1 ORDER BY nombre ASC";
if ($rs_sitios = mysqli_query($conexion, $sql_sitios)) {
    while ($row = mysqli_fetch_assoc($rs_sitios)) {
        $lista_sitios[] = $row;
    }
    mysqli_free_result($rs_sitios);
}

/* =========================================================
   Construir consulta de incidencias
   ========================================================= */
$where = " WHERE 1=1 ";
if ($f_sitio > 0) {
    $where .= " AND i.sitio_id = " . (int)$f_sitio . " ";
}
if ($f_estado !== '' && in_array($f_estado, $estados_validos)) {
    $where .= " AND i.estado = '" . mysqli_real_escape_string($conexion, $f_estado) . "' ";
}
if ($f_tipo !== '' && in_array($f_tipo, $tipos_validos)) {
    $where .= " AND i.tipo = '" . mysqli_real_escape_string($conexion, $f_tipo) . "' ";
}
if ($f_prioridad !== '' && in_array($f_prioridad, $prioridades_validas)) {
    $where .= " AND i.prioridad = '" . mysqli_real_escape_string($conexion, $f_prioridad) . "' ";
}
if ($f_desde !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $f_desde)) {
    $where .= " AND DATE(i.fecha_creacion) >= '" . mysqli_real_escape_string($conexion, $f_desde) . "' ";
}
if ($f_hasta !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $f_hasta)) {
    $where .= " AND DATE(i.fecha_creacion) <= '" . mysqli_real_escape_string($conexion, $f_hasta) . "' ";
}
if ($f_buscar !== '') {
    $buscar_safe = mysqli_real_escape_string($conexion, $f_buscar);
    $where .= " AND (
        i.descripcion LIKE '%{$buscar_safe}%' OR
        i.notas_admin  LIKE '%{$buscar_safe}%' OR
        s.nombre       LIKE '%{$buscar_safe}%'
    ) ";
}

$sql_incidencias = "
    SELECT 
        i.id,
        i.sitio_id,
        s.nombre AS sitio_nombre,
        i.tipo,
        i.prioridad,
        i.estado,
        i.descripcion,
        i.url_foto,
        i.fecha_creacion,
        CONCAT(p.nombres,' ',p.apellidos) AS reportador
    FROM incidencias i
    INNER JOIN sitios   s ON s.id = i.sitio_id
    INNER JOIN personal p ON p.id = i.reportador_id
    {$where}
    ORDER BY i.fecha_creacion DESC
    LIMIT 500
";

$incidencias = [];
if ($rs_inc = mysqli_query($conexion, $sql_incidencias)) {
    while ($row = mysqli_fetch_assoc($rs_inc)) {
        $incidencias[] = $row;
    }
    mysqli_free_result($rs_inc);
}

/* Contadores rápidos */
$total_incidencias = count($incidencias);
$total_abiertas    = 0;
$total_urgentes    = 0;

foreach ($incidencias as $inc) {
    if (in_array($inc['estado'], ['PENDIENTE','EN_PROCESO'])) {
        $total_abiertas++;
    }
    if ($inc['tipo'] === 'URGENTE' || in_array($inc['prioridad'], ['ALTA','CRITICA'])) {
        $total_urgentes++;
    }
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, shrink-to-fit=no">
    <title>Bandeja de incidencias | Panel de Control</title>

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

    <!-- BEGIN PAGE LEVEL STYLES -->
    <link rel="stylesheet" type="text/css" href="../src/plugins/src/table/datatable/datatables.css">
    <link rel="stylesheet" type="text/css" href="../src/plugins/src/table/datatable/dt-global_style.css">
    <!-- END PAGE LEVEL STYLES -->

    <style>
        .page-title {
            font-weight: 700;
            font-size: 1.4rem;
        }
        .filters-card .form-label {
            font-size: 0.8rem;
            text-transform: uppercase;
            font-weight: 600;
            color: #888;
        }
        .filters-card {
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(31,45,61,0.12);
        }
        .badge-estado {
            font-size: 0.75rem;
            padding: 4px 8px;
            border-radius: 999px;
        }
        .badge-estado-pendiente {
            background: #fff3cd;
            color: #856404;
        }
        .badge-estado-proceso {
            background: #cce5ff;
            color: #004085;
        }
        .badge-estado-cerrado {
            background: #d4edda;
            color: #155724;
        }
        .badge-prioridad-critica {
            background: #721c24;
            color: #fff;
        }
        .badge-prioridad-alta {
            background: #f8d7da;
            color: #721c24;
        }
        .badge-prioridad-media {
            background: #fff3cd;
            color: #856404;
        }
        .badge-prioridad-baja {
            background: #d1ecf1;
            color: #0c5460;
        }
        .descripcion-corta {
            max-width: 420px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .table th {
            font-size: 0.78rem;
            text-transform: uppercase;
        }
        .table td {
            font-size: 0.82rem;
            vertical-align: middle;
        }
    </style>

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
                <div class="profile-info">
                    <!-- aquí puedes poner info del usuario si lo deseas -->
                </div>
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
                        <div class="breadcrumbs-container" data-page-heading="Incidencias">
                            <header class="header navbar navbar-expand-sm">
                                <a href="javascript:void(0);" class="btn-toggle sidebarCollapse" data-placement="bottom">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                         viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                         stroke-linecap="round" stroke-linejoin="round" class="feather feather-menu">
                                        <line x1="3" y1="12" x2="21" y2="12"></line>
                                        <line x1="3" y1="6" x2="21" y2="6"></line>
                                        <line x1="3" y1="18" x2="21" y2="18"></line>
                                    </svg>
                                </a>
                                <div class="d-flex align-items-center ms-3">
                                    <h1 class="page-title mb-0">Bandeja de incidencias</h1>
                                </div>
                            </header>
                        </div>
                    </div>
                    <!--  END BREADCRUMBS / HEADER  -->

                    <!-- CONTENIDO PRINCIPAL -->
                    <div class="row layout-top-spacing">

                        <!-- Tarjetas de resumen -->
                        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12 col-12 layout-spacing">
                            <div class="widget widget-card-four">
                                <div class="widget-content">
                                    <div class="w-header">
                                        <div class="w-info">
                                            <h6 class="value">Total incidencias</h6>
                                        </div>
                                    </div>
                                    <div class="w-content">
                                        <div class="w-info">
                                            <p class="value">
                                                <?php echo (int)$total_incidencias; ?>
                                                <span>en la vista actual</span>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12 col-12 layout-spacing">
                            <div class="widget widget-card-four">
                                <div class="widget-content">
                                    <div class="w-header">
                                        <div class="w-info">
                                            <h6 class="value">Abiertas</h6>
                                        </div>
                                    </div>
                                    <div class="w-content">
                                        <div class="w-info">
                                            <p class="value">
                                                <?php echo (int)$total_abiertas; ?>
                                                <span>Pendientes / En proceso</span>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12 col-12 layout-spacing">
                            <div class="widget widget-card-four">
                                <div class="widget-content">
                                    <div class="w-header">
                                        <div class="w-info">
                                            <h6 class="value">Urgentes / Críticas</h6>
                                        </div>
                                    </div>
                                    <div class="w-content">
                                        <div class="w-info">
                                            <p class="value">
                                                <?php echo (int)$total_urgentes; ?>
                                                <span>por tipo / prioridad</span>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Filtros -->
                        <div class="col-12 layout-spacing">
                            <div class="card filters-card p-3 mb-4">
                                <form class="row g-2" method="get" action="incidencias-bandeja.php">
                                    <div class="col-md-3 col-lg-2">
                                        <label class="form-label">Sitio</label>
                                        <select name="sitio_id" class="form-select form-select-sm">
                                            <option value="0">Todos</option>
                                            <?php foreach ($lista_sitios as $s): ?>
                                                <option value="<?php echo (int)$s['id']; ?>"
                                                    <?php echo ($f_sitio == (int)$s['id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($s['nombre']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="col-md-3 col-lg-2">
                                        <label class="form-label">Estado</label>
                                        <select name="estado" class="form-select form-select-sm">
                                            <option value="">Todos</option>
                                            <?php foreach ($estados_validos as $e): ?>
                                                <option value="<?php echo $e; ?>"
                                                    <?php echo ($f_estado === $e) ? 'selected' : ''; ?>>
                                                    <?php echo ucfirst(strtolower(str_replace('_',' ', $e))); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="col-md-3 col-lg-2">
                                        <label class="form-label">Tipo</label>
                                        <select name="tipo" class="form-select form-select-sm">
                                            <option value="">Todos</option>
                                            <?php foreach ($tipos_validos as $t): ?>
                                                <option value="<?php echo $t; ?>"
                                                    <?php echo ($f_tipo === $t) ? 'selected' : ''; ?>>
                                                    <?php echo ucfirst(strtolower($t)); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="col-md-3 col-lg-2">
                                        <label class="form-label">Prioridad</label>
                                        <select name="prioridad" class="form-select form-select-sm">
                                            <option value="">Todas</option>
                                            <?php foreach ($prioridades_validas as $p): ?>
                                                <option value="<?php echo $p; ?>"
                                                    <?php echo ($f_prioridad === $p) ? 'selected' : ''; ?>>
                                                    <?php echo ucfirst(strtolower($p)); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="col-md-3 col-lg-2">
                                        <label class="form-label">Desde</label>
                                        <input type="date" name="fecha_desde" value="<?php echo htmlspecialchars($f_desde); ?>"
                                               class="form-control form-control-sm">
                                    </div>

                                    <div class="col-md-3 col-lg-2">
                                        <label class="form-label">Hasta</label>
                                        <input type="date" name="fecha_hasta" value="<?php echo htmlspecialchars($f_hasta); ?>"
                                               class="form-control form-control-sm">
                                    </div>

                                    <div class="col-md-6 col-lg-4">
                                        <label class="form-label">Buscar</label>
                                        <input type="text" name="buscar" value="<?php echo htmlspecialchars($f_buscar); ?>"
                                               class="form-control form-control-sm"
                                               placeholder="Descripción, notas, nombre de sitio...">
                                    </div>

                                    <div class="col-md-6 col-lg-4 d-flex align-items-end justify-content-start">
                                        <button type="submit" class="btn btn-primary btn-sm me-2">
                                            Filtrar
                                        </button>
                                        <a href="incidencias-bandeja.php" class="btn btn-outline-secondary btn-sm">
                                            Limpiar
                                        </a>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- TABLA PRINCIPAL DE INCIDENCIAS -->
                        <div class="col-12 layout-spacing">
                            <div class="widget widget-table-one">
                                <div class="widget-heading mb-2 d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">Incidencias registradas</h5>
                                    <small class="text-muted">
                                        Mostrando máximo 500 registros ordenados por fecha más reciente.
                                    </small>
                                </div>
                                <div class="widget-content">
                                    <div class="table-responsive">
                                        <table id="tablaIncidencias" class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>ID</th>
                                                    <th>Fecha / Hora</th>
                                                    <th>Sitio</th>
                                                    <th>Reportado por</th>
                                                    <th>Tipo</th>
                                                    <th>Prioridad</th>
                                                    <th>Estado</th>
                                                    <th>Descripción</th>
                                                    <th>Acciones</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                            <?php if (empty($incidencias)): ?>
                                                <tr>
                                                    <td colspan="9" class="text-center text-muted">
                                                        No se encontraron incidencias con los filtros seleccionados.
                                                    </td>
                                                </tr>
                                            <?php else: ?>
                                                <?php foreach ($incidencias as $inc): ?>
                                                    <?php
                                                        $estado = $inc['estado'];
                                                        $prior  = $inc['prioridad'];

                                                        $clase_estado = 'badge-estado';
                                                        if ($estado === 'PENDIENTE') {
                                                            $clase_estado .= ' badge-estado-pendiente';
                                                        } elseif ($estado === 'EN_PROCESO') {
                                                            $clase_estado .= ' badge-estado-proceso';
                                                        } elseif ($estado === 'CERRADO') {
                                                            $clase_estado .= ' badge-estado-cerrado';
                                                        }

                                                        $clase_prioridad = '';
                                                        if ($prior === 'CRITICA') {
                                                            $clase_prioridad = 'badge badge-prioridad-critica';
                                                        } elseif ($prior === 'ALTA') {
                                                            $clase_prioridad = 'badge badge-prioridad-alta';
                                                        } elseif ($prior === 'MEDIA') {
                                                            $clase_prioridad = 'badge badge-prioridad-media';
                                                        } else {
                                                            $clase_prioridad = 'badge badge-prioridad-baja';
                                                        }
                                                    ?>
                                                    <tr>
                                                        <td>#<?php echo (int)$inc['id']; ?></td>
                                                        <td>
                                                            <?php
                                                                $fecha = date('d/m/Y H:i', strtotime($inc['fecha_creacion']));
                                                                echo $fecha;
                                                            ?>
                                                        </td>
                                                        <td><?php echo htmlspecialchars($inc['sitio_nombre']); ?></td>
                                                        <td><?php echo htmlspecialchars($inc['reportador']); ?></td>
                                                        <td><?php echo htmlspecialchars($inc['tipo']); ?></td>
                                                        <td>
                                                            <span class="<?php echo $clase_prioridad; ?>">
                                                                <?php echo htmlspecialchars($inc['prioridad']); ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <span class="<?php echo $clase_estado; ?>">
                                                                <?php echo str_replace('_',' ', htmlspecialchars($inc['estado'])); ?>
                                                            </span>
                                                        </td>
                                                        <td class="descripcion-corta" title="<?php echo htmlspecialchars($inc['descripcion']); ?>">
                                                            <?php echo htmlspecialchars($inc['descripcion']); ?>
                                                        </td>
                                                        <td>
                                                            <a href="incidencias-detalle.php?id=<?php echo (int)$inc['id']; ?>"
                                                               class="btn btn-outline-primary btn-sm">
                                                                Ver detalle
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
                    <!-- FIN CONTENIDO PRINCIPAL -->

                </div>
            </div>

            <!--  BEGIN FOOTER  -->
            <?php include 'partes/footer.php'; ?>
            <!--  END FOOTER  -->

        </div>
        <!--  END CONTENT AREA  -->

    </div>
    <!-- END MAIN CONTAINER -->

    <!-- BEGIN GLOBAL MANDATORY SCRIPTS -->
    <script src="../src/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../src/plugins/src/perfect-scrollbar/perfect-scrollbar.min.js"></script>
    <script src="../src/plugins/src/mousetrap/mousetrap.min.js"></script>
    <script src="../src/plugins/src/waves/waves.min.js"></script>
    <script src="../layouts/vertical-light-menu/app.js"></script>
    <!-- END GLOBAL MANDATORY SCRIPTS -->

    <!-- BEGIN PAGE LEVEL PLUGINS/CUSTOM SCRIPTS -->
    <script src="../src/plugins/src/table/datatable/datatables.js"></script>
    <script>
        // Si más adelante quieres activar DataTables:
        // let table = $('#tablaIncidencias').DataTable({
        //     pageLength: 25,
        //     order: [[1, 'desc']],
        //     language: {
        //         url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
        //     }
        // });
    </script>
    <!-- END PAGE LEVEL PLUGINS/CUSTOM SCRIPTS -->

</body>
</html>
