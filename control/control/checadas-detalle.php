<?php
// checadas-detalle.php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit;
}

include 'lib/db.php'; // Debe definir $conexion (mysqli)

// Roles que pueden ver checadas
$rol = isset($_SESSION['rol_nombre']) ? $_SESSION['rol_nombre'] : '';
$roles_permitidos = ['ADMIN', 'SUPERVISOR', 'RH', 'NOMINA', 'DUEÑO', 'CLIENTE'];

if (!in_array($rol, $roles_permitidos)) {
    echo "No tienes permisos para ver esta página.";
    exit;
}

// =======================
//  HELPERS
// =======================
function limpiar($txt) {
    return trim(htmlspecialchars($txt, ENT_QUOTES, 'UTF-8'));
}

function badgeEstadoChecada($estado) {
    $estado = strtoupper((string)$estado);
    switch ($estado) {
        case 'ACEPTADO':
            return '<span class="badge bg-success">Aceptado</span>';
        case 'RECHAZADO_ROSTRO':
            return '<span class="badge bg-danger">Rechazado rostro</span>';
        case 'RECHAZADO_GPS':
            return '<span class="badge bg-warning text-dark">Rechazado GPS</span>';
        case 'PENDIENTE_REVISION':
            return '<span class="badge bg-secondary">Pendiente revisión</span>';
        default:
            return '<span class="badge bg-light text-dark">'.htmlspecialchars($estado).'</span>';
    }
}

function labelTipoEvento($tipo) {
    switch ($tipo) {
        case 'ENTRADA': return 'Entrada';
        case 'SALIDA':  return 'Salida';
        case 'RONDIN':  return 'Rondín';
        default:        return $tipo;
    }
}

// =======================
//  OBTENER ID (DETALLE OPCIONAL)
// =======================
$detalle_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$detalle = null;

if ($detalle_id > 0) {
    $sql_det = "
        SELECT 
            ra.*,
            p.nombres,
            p.apellidos,
            u.usuario,
            s.nombre  AS sitio_nombre,
            s.direccion AS sitio_direccion,
            s.latitud AS sitio_latitud,
            s.longitud AS sitio_longitud,
            t.id       AS turno_id,
            t.hora_inicio,
            t.hora_fin,
            t.es_turno_extra
        FROM registros_asistencia ra
        INNER JOIN personal p  ON p.id       = ra.personal_id
        INNERJOIN usuarios u  ON u.id       = p.usuario_id
        INNER JOIN sitios s    ON s.id       = ra.sitio_id
        LEFT JOIN turnos t     ON t.id       = ra.turno_id
        WHERE ra.id = ?
        LIMIT 1
    ";

    // corregir INNERJOIN → INNER JOIN
    $sql_det = str_replace('INNERJOIN', 'INNER JOIN', $sql_det);

    if ($stmt = mysqli_prepare($conexion, $sql_det)) {
        mysqli_stmt_bind_param($stmt, "i", $detalle_id);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        if ($row = mysqli_fetch_assoc($res)) {
            $detalle = $row;
        }
        mysqli_free_result($res);
        mysqli_stmt_close($stmt);
    }
}

// =======================
//  LISTA DE CHECADAS (ULTIMAS N)
// =======================

// Podrías agregar filtros por fecha/guardia/sitio; de momento es un listado simple
$sql_lista = "
    SELECT 
        ra.id,
        ra.fecha_hora,
        ra.tipo_evento,
        ra.estado,
        ra.esta_dentro_geocerca,
        ra.puntaje_facial,
        ra.sitio_id,
        p.nombres,
        p.apellidos,
        s.nombre AS sitio_nombre,
        t.id     AS turno_id,
        t.es_turno_extra
    FROM registros_asistencia ra
    INNER JOIN personal p ON p.id = ra.personal_id
    INNER JOIN sitios s   ON s.id = ra.sitio_id
    LEFT JOIN turnos t    ON t.id = ra.turno_id
    ORDER BY ra.fecha_hora DESC
    LIMIT 300
";

$checadas = [];
if ($res_lista = mysqli_query($conexion, $sql_lista)) {
    while ($row = mysqli_fetch_assoc($res_lista)) {
        $checadas[] = $row;
    }
    mysqli_free_result($res_lista);
}

// =======================
//  SI HAY DETALLE, PREPARAMOS DATA
// =======================
$detalle_guardia = $detalle_sitio = $detalle_usuario = "";
$detalle_tipo = $detalle_estado = "";
$detalle_fecha_hora = "";
$detalle_lat = $detalle_lng = "";
$detalle_geocerca_ok = false;
$detalle_puntaje = null;
$detalle_liveness = false;
$detalle_comentarios = "";
$detalle_url_selfie = "";
$detalle_turno_id = null;
$detalle_turno_inicio = $detalle_turno_fin = "";
$detalle_turno_extra = false;
$detalle_link_mapa = "#";

if ($detalle) {
    $detalle_guardia = $detalle['nombres']." ".$detalle['apellidos'];
    $detalle_sitio   = $detalle['sitio_nombre'];
    $detalle_usuario = $detalle['usuario'];

    $detalle_tipo   = $detalle['tipo_evento'];
    $detalle_estado = $detalle['estado'];

    $detalle_fecha_hora = $detalle['fecha_hora'];
    $detalle_lat        = $detalle['latitud'];
    $detalle_lng        = $detalle['longitud'];
    $detalle_geocerca_ok = ((int)$detalle['esta_dentro_geocerca'] === 1);
    $detalle_puntaje    = $detalle['puntaje_facial'];
    $detalle_liveness   = ((int)$detalle['verificado_vida'] === 1);
    $detalle_comentarios= trim((string)$detalle['comentarios']);
    $detalle_url_selfie = trim((string)$detalle['url_selfie']);

    $detalle_turno_id   = $detalle['turno_id'];
    $detalle_turno_inicio = $detalle['hora_inicio'];
    $detalle_turno_fin    = $detalle['hora_fin'];
    $detalle_turno_extra  = ((int)$detalle['es_turno_extra'] === 1);

    $detalle_link_mapa = "https://www.google.com/maps?q=" . urlencode($detalle_lat . "," . $detalle_lng);
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, shrink-to-fit=no">
    <title>Checadas - Lista y detalle</title>
    <link rel="icon" type="image/x-icon" href="../src/assets/img/favicon.ico"/>
    <link href="../layouts/vertical-light-menu/css/light/loader.css" rel="stylesheet" type="text/css" />
    <link href="../layouts/vertical-light-menu/css/dark/loader.css" rel="stylesheet" type="text/css" />
    <script src="../layouts/vertical-light-menu/loader.js"></script>

    <!-- ESTILOS GLOBALES -->
    <link href="https://fonts.googleapis.com/css?family=Nunito:400,600,700" rel="stylesheet">
    <link href="../src/bootstrap/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
    <link href="../layouts/vertical-light-menu/css/light/plugins.css" rel="stylesheet" type="text/css" />
    <link href="../layouts/vertical-light-menu/css/dark/plugins.css" rel="stylesheet" type="text/css" />

    <link href="../src/assets/css/light/dashboard/dash_1.css" rel="stylesheet" type="text/css" />
    <link href="../src/assets/css/dark/dashboard/dash_1.css" rel="stylesheet" type="text/css" />

    <!-- DataTables (opcional, si lo usas) -->
    <link rel="stylesheet" type="text/css" href="../src/plugins/src/table/datatable/datatables.css">
    <link rel="stylesheet" type="text/css" href="../src/plugins/src/table/datatable/dt-global_style.css">

    <style>
        .selfie-detalle {
            max-width: 220px;
            border-radius: 8px;
            object-fit: cover;
        }
        .meta-label {
            font-size: 0.78rem;
            text-transform: uppercase;
            color: #999;
            margin-bottom: 3px;
        }
        .meta-value {
            font-size: 0.95rem;
            font-weight: 600;
        }
        .card-soft {
            border-radius: 12px;
        }
        .pill {
            padding: 2px 8px;
            font-size: 0.7rem;
            border-radius: 999px;
            background-color: rgba(0,0,0,0.05);
        }
        pre.comentarios {
            white-space: pre-wrap;
            font-size: 0.9rem;
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

        <!-- CONTENIDO -->
        <div id="content" class="main-content">
            <div class="layout-px-spacing">

                <div class="middle-content container-xxl p-0">

                    <!-- BARRA SUPERIOR -->
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
                                    <h5 class="mb-0">Checadas</h5>
                                    <small class="text-muted">
                                        Resumen de todas las checadas registradas en el sistema con acceso a detalle.
                                    </small>
                                </div>
                            </header>
                        </div>
                    </div>

                    <div class="row layout-top-spacing">

                        <!-- DETALLE (SOLO SI HAY ID) -->
                        <?php if ($detalle): ?>
                        <div class="col-12 layout-spacing">
                            <div class="widget widget-card-two card-soft">
                                <div class="widget-content">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="me-3">
                                                <div class="avatar rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width:48px;height:48px;">
                                                    <span><?php echo strtoupper(substr($detalle_guardia, 0, 2)); ?></span>
                                                </div>
                                            </div>
                                            <div>
                                                <h6 class="mb-0"><?php echo htmlspecialchars($detalle_guardia); ?></h6>
                                                <small class="text-muted">
                                                    Usuario: <?php echo htmlspecialchars($detalle_usuario); ?> ·
                                                    Rol: <?php echo htmlspecialchars($rol); ?>
                                                </small>
                                                <div class="mt-1">
                                                    <span class="pill">
                                                        <?php echo labelTipoEvento($detalle_tipo); ?>
                                                    </span>
                                                    &nbsp;
                                                    <?php echo badgeEstadoChecada($detalle_estado); ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div>
                                            <small class="text-muted d-block text-end">
                                                Checada #<?php echo (int)$detalle_id; ?>
                                            </small>
                                            <a href="checadas-detalle.php" class="btn btn-sm btn-outline-secondary mt-1">
                                                Cerrar detalle
                                            </a>
                                        </div>
                                    </div>

                                    <hr>

                                    <div class="row">
                                        <div class="col-md-7">

                                            <div class="mb-2">
                                                <div class="meta-label">Fecha y hora de registro</div>
                                                <div class="meta-value">
                                                    <?php echo htmlspecialchars($detalle_fecha_hora); ?>
                                                </div>
                                            </div>

                                            <div class="mb-2">
                                                <div class="meta-label">Sitio</div>
                                                <div class="meta-value">
                                                    <?php echo htmlspecialchars($detalle_sitio); ?>
                                                </div>
                                            </div>

                                            <div class="mb-2">
                                                <div class="meta-label">Coordenadas de checada</div>
                                                <div class="meta-value">
                                                    <?php echo htmlspecialchars($detalle_lat); ?> ,
                                                    <?php echo htmlspecialchars($detalle_lng); ?>
                                                </div>
                                                <a href="<?php echo htmlspecialchars($detalle_link_mapa); ?>" target="_blank" style="font-size:0.85rem;">
                                                    Ver en Google Maps
                                                </a>
                                            </div>

                                            <div class="mb-2">
                                                <div class="meta-label">Resultado de geocerca</div>
                                                <div class="meta-value">
                                                    <?php if ($detalle_geocerca_ok): ?>
                                                        <span class="badge bg-success">Dentro de geocerca</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-danger">Fuera de geocerca</span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>

                                            <div class="row mt-3">
                                                <div class="col-md-6 mb-2">
                                                    <div class="meta-label">Puntaje facial</div>
                                                    <div class="meta-value">
                                                        <?php
                                                            if ($detalle_puntaje !== null) {
                                                                echo number_format((float)$detalle_puntaje, 1) . " / 100";
                                                            } else {
                                                                echo "Sin dato";
                                                            }
                                                        ?>
                                                    </div>
                                                </div>
                                                <div class="col-md-6 mb-2">
                                                    <div class="meta-label">Verificación de vida (liveness)</div>
                                                    <div class="meta-value">
                                                        <?php if ($detalle_liveness): ?>
                                                            <span class="badge bg-success">Validado</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-secondary">No validado</span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>

                                            <?php if ($detalle_turno_id): ?>
                                            <div class="mt-3 p-2 border rounded">
                                                <div class="meta-label">Turno asociado</div>
                                                <div class="meta-value mb-1">
                                                    Turno #<?php echo (int)$detalle_turno_id; ?>
                                                    <?php if ($detalle_turno_extra): ?>
                                                        <span class="badge bg-info ms-1">Turno extra</span>
                                                    <?php endif; ?>
                                                </div>
                                                <?php if ($detalle_turno_inicio && $detalle_turno_fin): ?>
                                                    <small class="text-muted">
                                                        Inicio: <?php echo htmlspecialchars($detalle_turno_inicio); ?> ·
                                                        Fin: <?php echo htmlspecialchars($detalle_turno_fin); ?>
                                                    </small>
                                                <?php endif; ?>
                                            </div>
                                            <?php endif; ?>

                                        </div>
                                        <div class="col-md-5">
                                            <div class="mb-2">
                                                <div class="meta-label">Selfie capturada</div>
                                            </div>
                                            <div class="text-center mb-3">
                                                <?php if ($detalle_url_selfie): ?>
                                                    <a href="<?php echo htmlspecialchars($detalle_url_selfie); ?>" target="_blank">
                                                        <img src="<?php echo htmlspecialchars($detalle_url_selfie); ?>"
                                                             alt="Selfie de checada"
                                                             class="selfie-detalle shadow-sm">
                                                    </a>
                                                    <div class="mt-1" style="font-size:0.8rem;">
                                                        Haz clic para abrir en tamaño completo.
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-muted">Sin foto registrada</span>
                                                <?php endif; ?>
                                            </div>

                                            <div class="mt-3">
                                                <div class="meta-label">Comentario del guardia</div>
                                                <pre class="comentarios border rounded p-2 bg-light mb-0">
<?php
if ($detalle_comentarios === '') {
    echo "Sin comentarios.";
} else {
    echo htmlspecialchars($detalle_comentarios);
}
?>
                                                </pre>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- LISTADO GENERAL -->
                        <div class="col-12 layout-spacing">
                            <div class="widget widget-table-one card-soft" style="padding:20px!important">

                                <div class="widget-heading d-flex justify-content-between align-items-center">
                                    <h5 class="">Listado de checadas</h5>
                                    <small class="text-muted">
                                        Mostrando las últimas <?php echo count($checadas); ?> checadas.
                                    </small>
                                </div>

                                <div class="widget-content">
                                    <div class="table-responsive">
                                        <table id="tablaChecadas" class="table table-striped table-hover mb-0">
                                            <thead>
                                                <tr>
                                                    <th>ID</th>
                                                    <th>Fecha / Hora</th>
                                                    <th>Guardia</th>
                                                    <th>Sitio</th>
                                                    <th>Tipo</th>
                                                    <th>Estado</th>
                                                    <th>Geocerca</th>
                                                    <th>Puntaje facial</th>
                                                    <th>Turno</th>
                                                    <th>Acciones</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (empty($checadas)): ?>
                                                    <tr>
                                                        <td colspan="10" class="text-center text-muted">
                                                            No hay checadas registradas.
                                                        </td>
                                                    </tr>
                                                <?php else: ?>
                                                    <?php foreach ($checadas as $c): ?>
                                                        <?php
                                                            $nombre_guardia = $c['nombres'].' '.$c['apellidos'];
                                                            $tipo_lbl       = labelTipoEvento($c['tipo_evento']);
                                                            $estado_badge   = badgeEstadoChecada($c['estado']);
                                                            $geocerca_ok    = ((int)$c['esta_dentro_geocerca'] === 1);
                                                            $puntaje        = $c['puntaje_facial'];
                                                            $turno_id       = $c['turno_id'];
                                                            $turno_extra    = ((int)$c['es_turno_extra'] === 1);
                                                        ?>
                                                        <tr>
                                                            <td><?php echo (int)$c['id']; ?></td>
                                                            <td><?php echo htmlspecialchars($c['fecha_hora']); ?></td>
                                                            <td><?php echo htmlspecialchars($nombre_guardia); ?></td>
                                                            <td><?php echo htmlspecialchars($c['sitio_nombre']); ?></td>
                                                            <td><?php echo htmlspecialchars($tipo_lbl); ?></td>
                                                            <td><?php echo $estado_badge; ?></td>
                                                            <td>
                                                                <?php if ($geocerca_ok): ?>
                                                                    <span class="badge bg-success">Dentro</span>
                                                                <?php else: ?>
                                                                    <span class="badge bg-danger">Fuera</span>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td>
                                                                <?php
                                                                    if ($puntaje !== null) {
                                                                        echo number_format((float)$puntaje, 1);
                                                                    } else {
                                                                        echo '-';
                                                                    }
                                                                ?>
                                                            </td>
                                                            <td>
                                                                <?php if ($turno_id): ?>
                                                                    #<?php echo (int)$turno_id; ?>
                                                                    <?php if ($turno_extra): ?>
                                                                        <span class="badge bg-info ms-1">Extra</span>
                                                                    <?php endif; ?>
                                                                <?php else: ?>
                                                                    <span class="text-muted">N/A</span>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td>
                                                                <a href="checadas-detalle.php?id=<?php echo (int)$c['id']; ?>" class="btn btn-sm btn-outline-primary">
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

                </div>

            </div>

            <?php include 'partes/footer.php'; ?>

        </div>

    </div>

    <!-- SCRIPTS GLOBALES -->
    <script src="../src/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../src/plugins/src/perfect-scrollbar/perfect-scrollbar.min.js"></script>
    <script src="../src/plugins/src/mousetrap/mousetrap.min.js"></script>
    <script src="../src/plugins/src/waves/waves.min.js"></script>
    <script src="../layouts/vertical-light-menu/app.js"></script>

    <!-- DataTables (opcional) -->
    <script src="../src/plugins/src/table/datatable/datatables.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (window.jQuery && $('#tablaChecadas').length) {
                $('#tablaChecadas').DataTable({
                    pageLength: 25,
                    order: [[1, 'desc']],
                    language: {
                        url: '//cdn.datatables.net/plug-ins/1.13.1/i18n/es-MX.json'
                    }
                });
            }
        });
    </script>
</body>
</html>
