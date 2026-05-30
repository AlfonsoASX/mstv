<?php
require_once __DIR__ . '/lib/app.php';
require_once __DIR__ . '/lib/operations.php';

app_require_session();
app_require_roles(['ADMIN', 'SUPERVISOR', 'RH', 'NOMINA', 'DUEÑO', 'CLIENTE']);
app_support_bootstrap($conexion);

$resumen = [
    'checadas_hoy' => (int)app_db_value($conexion, "SELECT COUNT(*) FROM registros_asistencia WHERE DATE(fecha_hora) = CURDATE()", 0),
    'incidencias_hoy' => (int)app_db_value($conexion, "SELECT COUNT(*) FROM incidencias WHERE DATE(fecha_creacion) = CURDATE()", 0),
    'mensajes_hoy' => (int)app_db_value($conexion, "SELECT COUNT(*) FROM mensajes_chat WHERE DATE(fecha_creacion) = CURDATE()", 0),
];

$bitacora = app_db_all($conexion, "
    SELECT
        b.id,
        b.tipo_accion,
        b.tabla_afectada,
        b.registro_id,
        b.fecha_creacion,
        COALESCE(NULLIF(TRIM(CONCAT(p.nombres, ' ', p.apellidos)), ''), u.usuario, 'Sistema') AS usuario_nombre
    FROM bitacora_sistema b
    LEFT JOIN usuarios u ON u.id = b.usuario_id
    LEFT JOIN personal p ON p.usuario_id = u.id
    ORDER BY b.fecha_creacion DESC, b.id DESC
    LIMIT 80
");

$checadas = app_db_all($conexion, "
    SELECT
        r.id,
        r.tipo_evento,
        r.estado,
        r.fecha_hora,
        s.nombre AS sitio_nombre,
        p.id AS personal_id,
        p.fecha_contratacion,
        CONCAT(p.nombres, ' ', p.apellidos) AS colaborador
    FROM registros_asistencia r
    INNER JOIN sitios s ON s.id = r.sitio_id
    INNER JOIN personal p ON p.id = r.personal_id
    ORDER BY r.fecha_hora DESC, r.id DESC
    LIMIT 20
");

$incidencias = app_db_all($conexion, "
    SELECT
        i.id,
        i.tipo,
        i.prioridad,
        i.descripcion,
        i.fecha_creacion,
        s.nombre AS sitio_nombre,
        p.id AS personal_id,
        p.fecha_contratacion,
        CONCAT(p.nombres, ' ', p.apellidos) AS reportador
    FROM incidencias i
    INNER JOIN sitios s ON s.id = i.sitio_id
    INNER JOIN personal p ON p.id = i.reportador_id
    ORDER BY i.fecha_creacion DESC, i.id DESC
    LIMIT 20
");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, shrink-to-fit=no">
    <title>Bitácora global</title>
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
        .mini-stat {
            padding: 18px;
            border-radius: 16px;
            background: linear-gradient(135deg, rgba(0,173,181,0.12), rgba(0,0,0,0.02));
            border: 1px solid rgba(0,173,181,0.16);
        }
        .mini-stat h3 { margin: 0; font-size: 2rem; }
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
                        <div class="breadcrumbs-container" data-page-heading="Bitácora">
                            <header class="header navbar navbar-expand-sm">
                                <a href="javascript:void(0);" class="btn-toggle sidebarCollapse" data-placement="bottom">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
                                </a>
                                <div class="ms-3">
                                    <h5 class="mb-0">Bitácora global</h5>
                                    <small class="text-muted">Actividad reciente de checadas, incidencias, chat y eventos del sistema.</small>
                                </div>
                            </header>
                        </div>
                    </div>

                    <div class="row layout-top-spacing mb-3">
                        <div class="col-md-4 layout-spacing"><div class="mini-stat"><small class="text-muted">Checadas hoy</small><h3><?php echo $resumen['checadas_hoy']; ?></h3></div></div>
                        <div class="col-md-4 layout-spacing"><div class="mini-stat"><small class="text-muted">Incidencias hoy</small><h3><?php echo $resumen['incidencias_hoy']; ?></h3></div></div>
                        <div class="col-md-4 layout-spacing"><div class="mini-stat"><small class="text-muted">Mensajes hoy</small><h3><?php echo $resumen['mensajes_hoy']; ?></h3></div></div>
                    </div>

                    <div class="row layout-top-spacing">
                        <div class="col-xl-6 layout-spacing">
                            <div class="widget widget-table-one card-soft" style="padding:20px;">
                                <div class="widget-heading">
                                    <h5 class="mb-1">Eventos del sistema</h5>
                                    <small class="text-muted">Últimos 80 movimientos registrados en bitácora.</small>
                                </div>
                                <div class="widget-content">
                                    <input type="search" class="form-control form-control-sm mb-3" data-bitacora-search="#bitacora-sistema-table" placeholder="Buscar usuario, acción o tabla...">
                                    <div class="table-responsive">
                                        <table class="table table-sm table-striped mb-0" id="bitacora-sistema-table">
                                            <thead><tr><th>Fecha</th><th>Usuario</th><th>Acción</th><th>Tabla</th></tr></thead>
                                            <tbody>
                                            <?php foreach ($bitacora as $item): ?>
                                                <tr>
                                                    <td><?php echo app_datetime($item['fecha_creacion']); ?></td>
                                                    <td><?php echo app_h($item['usuario_nombre']); ?></td>
                                                    <td><?php echo app_h($item['tipo_accion']); ?></td>
                                                    <td><?php echo app_h(($item['tabla_afectada'] ?? '-') . ($item['registro_id'] ? ' #' . $item['registro_id'] : '')); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-6 layout-spacing">
                            <div class="widget widget-table-one card-soft" style="padding:20px;">
                                <div class="widget-heading">
                                    <h5 class="mb-1">Checadas recientes</h5>
                                    <small class="text-muted">Entradas y salidas registradas desde la app.</small>
                                </div>
                                <div class="widget-content">
                                    <input type="search" class="form-control form-control-sm mb-3" data-bitacora-search="#bitacora-checadas-table" placeholder="Buscar colaborador, No. empleado, sitio o estado...">
                                    <div class="table-responsive">
                                        <table class="table table-sm table-striped mb-0" id="bitacora-checadas-table">
                                            <thead><tr><th>Fecha</th><th>No. empleado</th><th>Colaborador</th><th>Sitio</th><th>Evento</th><th>Estado</th></tr></thead>
                                            <tbody>
                                            <?php foreach ($checadas as $item): ?>
                                                <tr>
                                                    <td><?php echo app_datetime($item['fecha_hora']); ?></td>
                                                    <td><?php echo app_h(app_employee_number($item)); ?></td>
                                                    <td><?php echo app_h($item['colaborador']); ?></td>
                                                    <td><?php echo app_h($item['sitio_nombre']); ?></td>
                                                    <td><?php echo app_h($item['tipo_evento']); ?></td>
                                                    <td><?php echo app_h($item['estado']); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 layout-spacing">
                            <div class="widget widget-table-one card-soft" style="padding:20px;">
                                <div class="widget-heading">
                                    <h5 class="mb-1">Incidencias recientes</h5>
                                    <small class="text-muted">Reportes enviados desde la app móvil.</small>
                                </div>
                                <div class="widget-content">
                                    <input type="search" class="form-control form-control-sm mb-3" data-bitacora-search="#bitacora-incidencias-table" placeholder="Buscar sitio, reportó, No. empleado, tipo o descripción...">
                                    <div class="table-responsive">
                                        <table class="table table-sm table-striped mb-0" id="bitacora-incidencias-table">
                                            <thead><tr><th>Fecha</th><th>Sitio</th><th>No. empleado</th><th>Reportó</th><th>Tipo</th><th>Prioridad</th><th>Descripción</th></tr></thead>
                                            <tbody>
                                            <?php foreach ($incidencias as $item): ?>
                                                <tr>
                                                    <td><?php echo app_datetime($item['fecha_creacion']); ?></td>
                                                    <td><?php echo app_h($item['sitio_nombre']); ?></td>
                                                    <td><?php echo app_h(app_employee_number($item)); ?></td>
                                                    <td><?php echo app_h($item['reportador']); ?></td>
                                                    <td><?php echo app_h($item['tipo']); ?></td>
                                                    <td><?php echo app_h($item['prioridad']); ?></td>
                                                    <td><?php echo app_h(mb_strimwidth((string)$item['descripcion'], 0, 120, '...')); ?></td>
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
    <script src="../src/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../src/plugins/src/perfect-scrollbar/perfect-scrollbar.min.js"></script>
    <script src="../src/plugins/src/mousetrap/mousetrap.min.js"></script>
    <script src="../layouts/vertical-light-menu/app.js"></script>
    <script>
        document.querySelectorAll('[data-bitacora-search]').forEach(function (input) {
            var table = document.querySelector(input.getAttribute('data-bitacora-search'));
            if (!table) return;
            input.addEventListener('input', function () {
                var term = input.value.toLowerCase().trim();
                table.querySelectorAll('tbody tr').forEach(function (row) {
                    row.style.display = !term || row.textContent.toLowerCase().indexOf(term) !== -1 ? '' : 'none';
                });
            });
        });
    </script>
</body>
</html>
