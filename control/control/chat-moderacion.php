<?php
require_once __DIR__ . '/lib/app.php';
require_once __DIR__ . '/lib/operations.php';

app_require_session();
app_require_roles(['ADMIN', 'SUPERVISOR', 'RH', 'DUEÑO']);
app_support_bootstrap($conexion);

$mensajes = app_db_all($conexion, "
    SELECT
        m.id,
        m.cuerpo_mensaje,
        m.tipo_canal,
        m.es_leido,
        m.fecha_creacion,
        COALESCE(NULLIF(TRIM(CONCAT(pr.nombres, ' ', pr.apellidos)), ''), ur.usuario, 'Usuario') AS remitente_nombre,
        COALESCE(NULLIF(TRIM(CONCAT(pd.nombres, ' ', pd.apellidos)), ''), ud.usuario, 'Canal RH') AS destinatario_nombre
    FROM mensajes_chat m
    LEFT JOIN usuarios ur ON ur.id = m.remitente_id
    LEFT JOIN personal pr ON pr.usuario_id = ur.id
    LEFT JOIN usuarios ud ON ud.id = m.destinatario_id
    LEFT JOIN personal pd ON pd.usuario_id = ud.id
    WHERE m.contiene_groserias = 1
    ORDER BY m.fecha_creacion DESC, m.id DESC
    LIMIT 200
");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, shrink-to-fit=no">
    <title>Moderación de chat</title>
    <link rel="icon" type="image/x-icon" href="../src/assets/img/favicon.ico"/>
    <link href="../layouts/vertical-light-menu/css/light/loader.css" rel="stylesheet" type="text/css" />
    <link href="../layouts/vertical-light-menu/css/dark/loader.css" rel="stylesheet" type="text/css" />
    <script src="../layouts/vertical-light-menu/loader.js"></script>
    <link href="https://fonts.googleapis.com/css?family=Nunito:400,600,700" rel="stylesheet">
    <link href="../src/bootstrap/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
    <link href="../layouts/vertical-light-menu/css/light/plugins.css" rel="stylesheet" type="text/css" />
    <link href="../layouts/vertical-light-menu/css/dark/plugins.css" rel="stylesheet" type="text/css" />
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
                        <div class="breadcrumbs-container" data-page-heading="Moderación">
                            <header class="header navbar navbar-expand-sm">
                                <a href="javascript:void(0);" class="btn-toggle sidebarCollapse" data-placement="bottom">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
                                </a>
                                <div class="ms-3">
                                    <h5 class="mb-0">Moderación de chat</h5>
                                    <small class="text-muted">Mensajes marcados por el filtro antigroserías.</small>
                                </div>
                            </header>
                        </div>
                    </div>

                    <div class="row layout-top-spacing">
                        <div class="col-12 layout-spacing">
                            <div class="widget widget-table-one" style="padding:20px;">
                                <div class="widget-heading">
                                    <h5 class="mb-1">Mensajes observados</h5>
                                    <small class="text-muted">Estos mensajes no se bloquean, pero quedan visibles para revisión.</small>
                                </div>
                                <div class="widget-content">
                                    <div class="table-responsive">
                                        <table class="table table-striped mb-0">
                                            <thead>
                                                <tr>
                                                    <th>Fecha</th>
                                                    <th>Remitente</th>
                                                    <th>Destino</th>
                                                    <th>Canal</th>
                                                    <th>Mensaje</th>
                                                    <th>Leído</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (empty($mensajes)): ?>
                                                    <tr><td colspan="6" class="text-center text-muted py-4">No hay mensajes marcados actualmente.</td></tr>
                                                <?php else: ?>
                                                    <?php foreach ($mensajes as $mensaje): ?>
                                                        <tr>
                                                            <td><?php echo app_datetime($mensaje['fecha_creacion']); ?></td>
                                                            <td><?php echo app_h($mensaje['remitente_nombre']); ?></td>
                                                            <td><?php echo app_h($mensaje['destinatario_nombre']); ?></td>
                                                            <td><?php echo app_h($mensaje['tipo_canal']); ?></td>
                                                            <td><?php echo nl2br(app_h($mensaje['cuerpo_mensaje'])); ?></td>
                                                            <td>
                                                                <?php if ((int)$mensaje['es_leido'] === 1): ?>
                                                                    <span class="badge bg-success">Sí</span>
                                                                <?php else: ?>
                                                                    <span class="badge bg-warning text-dark">Pendiente</span>
                                                                <?php endif; ?>
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
    </div>
    <script src="../src/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../src/plugins/src/perfect-scrollbar/perfect-scrollbar.min.js"></script>
    <script src="../src/plugins/src/mousetrap/mousetrap.min.js"></script>
    <script src="../layouts/vertical-light-menu/app.js"></script>
</body>
</html>
