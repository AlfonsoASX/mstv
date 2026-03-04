<?php
// turnos-asignacion.php
session_start();

// Verificar sesión
if (!isset($_SESSION['usuario_id']) || empty($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit;
}

include 'lib/db.php';

// Datos de sesión útiles
$usuario_id  = $_SESSION['usuario_id'];
$rol_nombre  = isset($_SESSION['rol_nombre']) ? $_SESSION['rol_nombre'] : '';
$usuario_nom = isset($_SESSION['usuario']) ? $_SESSION['usuario'] : '';

// =============================
//   CARGA DE CATÁLOGOS
// =============================

// Sitios activos
$sitios = [];
$sql_sitios = "SELECT id, nombre FROM sitios WHERE esta_activo = 1 ORDER BY nombre ASC";
if ($res_s = mysqli_query($conexion, $sql_sitios)) {
    while ($row = mysqli_fetch_assoc($res_s)) {
        $sitios[] = $row;
    }
    mysqli_free_result($res_s);
}

// Guardias activos (rol GUARDIA)
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

// =============================
//   MANEJO DEL FORMULARIO ALTA
// =============================

$mensaje_ok    = '';
$mensaje_error = '';

// Helper para limpiar texto
function limpiar_txt($txt) {
    return trim(htmlspecialchars($txt, ENT_QUOTES, 'UTF-8'));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'crear_turno') {

    $sitio_id       = isset($_POST['sitio_id']) ? (int)$_POST['sitio_id'] : 0;
    $personal_id    = isset($_POST['personal_id']) ? (int)$_POST['personal_id'] : 0;
    $hora_inicio_in = isset($_POST['hora_inicio']) ? limpiar_txt($_POST['hora_inicio']) : '';
    $hora_fin_in    = isset($_POST['hora_fin'])    ? limpiar_txt($_POST['hora_fin'])    : '';
    $es_turno_extra = isset($_POST['es_turno_extra']) ? 1 : 0;

    // Validaciones básicas
    if ($sitio_id <= 0 || $personal_id <= 0 || $hora_inicio_in === '' || $hora_fin_in === '') {
        $mensaje_error = "Todos los campos son obligatorios.";
    } else {

        $ts_inicio = strtotime($hora_inicio_in);
        $ts_fin    = strtotime($hora_fin_in);

        if ($ts_inicio === false || $ts_fin === false) {
            $mensaje_error = "Formato de fecha/hora no válido.";
        } elseif ($ts_fin <= $ts_inicio) {
            $mensaje_error = "La hora de fin debe ser mayor a la hora de inicio.";
        } else {
            $hora_inicio = date('Y-m-d H:i:s', $ts_inicio);
            $hora_fin    = date('Y-m-d H:i:s', $ts_fin);

            $sql_ins = "
                INSERT INTO turnos
                    (sitio_id, personal_id, supervisor_id, hora_inicio, hora_fin, es_turno_extra, estado)
                VALUES
                    (?, ?, ?, ?, ?, ?, 'PROGRAMADO')
            ";

            if ($stmt = mysqli_prepare($conexion, $sql_ins)) {
                mysqli_stmt_bind_param(
                    $stmt,
                    "iiissi",
                    $sitio_id,
                    $personal_id,
                    $usuario_id,
                    $hora_inicio,
                    $hora_fin,
                    $es_turno_extra
                );

                if (mysqli_stmt_execute($stmt)) {
                    $mensaje_ok = "Turno creado correctamente.";
                } else {
                    $mensaje_error = "Error al guardar el turno: " . mysqli_error($conexion);
                }

                mysqli_stmt_close($stmt);
            } else {
                $mensaje_error = "No se pudo preparar la consulta de inserción.";
            }
        }
    }
}

// =============================
//   FILTRO DE SITIO PARA LISTADO
// =============================

$filtro_sitio_id = 0;
if (isset($_GET['f_sitio_id'])) {
    $filtro_sitio_id = (int)$_GET['f_sitio_id'];
} elseif (isset($_POST['sitio_id']) && empty($mensaje_error)) {
    // Si se acaba de crear un turno, mantener ese sitio seleccionado
    $filtro_sitio_id = (int)$_POST['sitio_id'];
}

// Consulta de turnos del sitio (o todos si 0)
$turnos = [];
$sql_turnos = "
    SELECT
        t.id,
        t.hora_inicio,
        t.hora_fin,
        t.es_turno_extra,
        t.estado,
        s.nombre AS sitio_nombre,
        p.nombres,
        p.apellidos
    FROM turnos t
    INNER JOIN sitios s   ON s.id = t.sitio_id
    INNER JOIN personal p ON p.id = t.personal_id
";
if ($filtro_sitio_id > 0) {
    $sql_turnos .= " WHERE t.sitio_id = " . (int)$filtro_sitio_id . " ";
}
$sql_turnos .= " ORDER BY t.hora_inicio DESC LIMIT 50";

if ($res_t = mysqli_query($conexion, $sql_turnos)) {
    while ($row = mysqli_fetch_assoc($res_t)) {
        $turnos[] = $row;
    }
    mysqli_free_result($res_t);
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, shrink-to-fit=no">
    <title>Asignación de Turnos | MSTV Control de Guardias</title>
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

    <!-- PAGE LEVEL (si luego quieres agregar algo como select2, etc.) -->
    <style>
        .card-form {
            border-radius: 8px;
        }
        .badge-turno-extra {
            background-color: #f7b731;
            color: #000;
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
                <?php include 'partes/menu.php'; ?>
            </nav>
        </div>
        <!--  END SIDEBAR  -->

        <!--  BEGIN CONTENT AREA  -->
        <div id="content" class="main-content">
            <div class="layout-px-spacing">

                <div class="middle-content container-xxl p-0">

                    <!--  BEGIN BREADCRUMBS  -->
                    <div class="secondary-nav">
                        <div class="breadcrumbs-container" data-page-heading="Asignación de turnos">
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
                                    <h5 class="mb-0">Asignación de turnos</h5>
                                    <small class="text-muted">
                                        Define jornadas ordinarias y turnos extra para los guardias.
                                    </small>
                                </div>
                            </header>
                        </div>
                    </div>
                    <!--  END BREADCRUMBS  -->

                    <div class="row layout-top-spacing">

                        <!-- FORMULARIO ALTA DE TURNO -->
                        <div class="col-xl-4 col-lg-5 col-md-12 col-sm-12 col-12 layout-spacing">
                            <div class="card card-form">
                                <div class="card-body">
                                    <h6 class="mb-3">Crear nuevo turno</h6>

                                    <?php if ($mensaje_ok): ?>
                                        <div class="alert alert-success py-2">
                                            <?php echo $mensaje_ok; ?>
                                        </div>
                                    <?php endif; ?>

                                    <?php if ($mensaje_error): ?>
                                        <div class="alert alert-danger py-2">
                                            <?php echo $mensaje_error; ?>
                                        </div>
                                    <?php endif; ?>

                                    <form method="post" action="turnos-asignacion.php">
                                        <input type="hidden" name="accion" value="crear_turno">

                                        <div class="mb-3">
                                            <label for="sitio_id" class="form-label">Sitio / Caseta</label>
                                            <select name="sitio_id" id="sitio_id" class="form-select" required>
                                                <option value="">Selecciona un sitio</option>
                                                <?php foreach ($sitios as $s): ?>
                                                    <option value="<?php echo (int)$s['id']; ?>"
                                                        <?php echo ($filtro_sitio_id == (int)$s['id']) ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($s['nombre']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div class="mb-3">
                                            <label for="personal_id" class="form-label">Guardia</label>
                                            <select name="personal_id" id="personal_id" class="form-select" required>
                                                <option value="">Selecciona un guardia</option>
                                                <?php foreach ($guardias as $g): ?>
                                                    <option value="<?php echo (int)$g['personal_id']; ?>">
                                                        <?php
                                                        echo htmlspecialchars($g['nombres'] . ' ' . $g['apellidos']);
                                                        echo " (usuario: " . htmlspecialchars($g['usuario']) . ")";
                                                        ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div class="mb-3">
                                            <label for="hora_inicio" class="form-label">Hora de inicio</label>
                                            <input type="datetime-local" name="hora_inicio" id="hora_inicio"
                                                   class="form-control" required>
                                        </div>

                                        <div class="mb-3">
                                            <label for="hora_fin" class="form-label">Hora de fin</label>
                                            <input type="datetime-local" name="hora_fin" id="hora_fin"
                                                   class="form-control" required>
                                        </div>

                                        <div class="form-check form-switch mb-3">
                                            <input class="form-check-input" type="checkbox" id="es_turno_extra"
                                                   name="es_turno_extra">
                                            <label class="form-check-label" for="es_turno_extra">
                                                Turno extra
                                            </label>
                                        </div>

                                        <div class="d-grid">
                                            <button type="submit" class="btn btn-primary">
                                                Guardar turno
                                            </button>
                                        </div>
                                    </form>

                                    <hr class="my-4">

                                    <p class="mb-1"><strong>Supervisor actual:</strong> <?php echo htmlspecialchars($usuario_nom); ?></p>
                                    <p class="mb-0"><small class="text-muted">
                                        El turno se registrará a tu usuario como supervisor asignador.
                                    </small></p>
                                </div>
                            </div>
                        </div>

                        <!-- LISTADO DE TURNOS -->
                        <div class="col-xl-8 col-lg-7 col-md-12 col-sm-12 col-12 layout-spacing">
                            <div class="widget widget-table-one">

                                <div class="widget-heading d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="">Turnos recientes</h6>
                                        <small class="text-muted">
                                            Últimos 50 turnos<?php echo $filtro_sitio_id ? " del sitio seleccionado" : " (todos los sitios)"; ?>
                                        </small>
                                    </div>
                                    <form class="d-flex align-items-center" method="get" action="turnos-asignacion.php">
                                        <label for="f_sitio_id" class="me-2 mb-0 small">Filtrar sitio:</label>
                                        <select name="f_sitio_id" id="f_sitio_id" class="form-select form-select-sm me-2"
                                                onchange="this.form.submit()">
                                            <option value="0">Todos</option>
                                            <?php foreach ($sitios as $s): ?>
                                                <option value="<?php echo (int)$s['id']; ?>"
                                                    <?php echo ($filtro_sitio_id == (int)$s['id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($s['nombre']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </form>
                                </div>

                                <div class="widget-content">

                                    <div class="table-responsive">
                                        <table class="table table-sm mb-0 align-middle">
                                            <thead>
                                                <tr>
                                                    <th>Guardia</th>
                                                    <th>Sitio</th>
                                                    <th>Inicio</th>
                                                    <th>Fin</th>
                                                    <th>Tipo</th>
                                                    <th>Estado</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (empty($turnos)): ?>
                                                    <tr>
                                                        <td colspan="6" class="text-center text-muted py-3">
                                                            No hay turnos para mostrar.
                                                        </td>
                                                    </tr>
                                                <?php else: ?>
                                                    <?php foreach ($turnos as $t): ?>
                                                        <tr>
                                                            <td>
                                                                <?php echo htmlspecialchars($t['nombres'] . ' ' . $t['apellidos']); ?>
                                                            </td>
                                                            <td><?php echo htmlspecialchars($t['sitio_nombre']); ?></td>
                                                            <td>
                                                                <?php
                                                                $fi = date('d/m/Y H:i', strtotime($t['hora_inicio']));
                                                                echo $fi;
                                                                ?>
                                                            </td>
                                                            <td>
                                                                <?php
                                                                $ff = date('d/m/Y H:i', strtotime($t['hora_fin']));
                                                                echo $ff;
                                                                ?>
                                                            </td>
                                                            <td>
                                                                <?php if ((int)$t['es_turno_extra'] === 1): ?>
                                                                    <span class="badge badge-turno-extra">Extra</span>
                                                                <?php else: ?>
                                                                    <span class="badge bg-info">Ordinario</span>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td>
                                                                <?php
                                                                $estado = $t['estado'];
                                                                $clase  = 'bg-secondary';
                                                                if ($estado === 'EN_PROGRESO') $clase = 'bg-success';
                                                                elseif ($estado === 'COMPLETADO') $clase = 'bg-primary';
                                                                elseif ($estado === 'AUSENTE') $clase = 'bg-danger';
                                                                ?>
                                                                <span class="badge <?php echo $clase; ?>">
                                                                    <?php echo htmlspecialchars($estado); ?>
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
                        </div>

                    </div>

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

</body>
</html>
