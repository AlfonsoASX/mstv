<?php
// turnos-asignacion.php
require_once __DIR__ . '/lib/app.php';

app_require_session();
app_require_page_permission();

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

// Personal activo disponible para asignar turnos y permitir checadas.
$personal_activo = [];
$sql_personal_activo = "
    SELECT 
        p.id AS personal_id,
        p.fecha_contratacion,
        p.nombres,
        p.apellidos,
        u.usuario,
        COALESCE(r.nombre, '') AS rol_nombre
    FROM personal p
    LEFT JOIN usuarios u ON u.id = p.usuario_id
    LEFT JOIN roles r    ON r.id = u.rol_id
    WHERE p.estado = 'ACTIVO'
    ORDER BY p.nombres, p.apellidos
";
if ($res_g = mysqli_query($conexion, $sql_personal_activo)) {
    while ($row = mysqli_fetch_assoc($res_g)) {
        $personal_activo[] = $row;
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

$dias_semana_recurrente = [
    1 => 'Lunes',
    2 => 'Martes',
    3 => 'Miércoles',
    4 => 'Jueves',
    5 => 'Viernes',
    6 => 'Sábado',
    7 => 'Domingo',
];

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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'generar_turnos') {

    $sitio_id       = isset($_POST['sitio_id_recurrente']) ? (int)$_POST['sitio_id_recurrente'] : 0;
    $personal_id    = isset($_POST['personal_id_recurrente']) ? (int)$_POST['personal_id_recurrente'] : 0;
    $fecha_base     = isset($_POST['fecha_base_recurrente']) ? limpiar_txt($_POST['fecha_base_recurrente']) : '';
    $hora_inicio_in = isset($_POST['hora_inicio_recurrente']) ? limpiar_txt($_POST['hora_inicio_recurrente']) : '';
    $hora_fin_in    = isset($_POST['hora_fin_recurrente']) ? limpiar_txt($_POST['hora_fin_recurrente']) : '';
    $rango          = isset($_POST['rango_recurrente']) ? limpiar_txt($_POST['rango_recurrente']) : 'semana';
    $dias_raw       = isset($_POST['dias_semana']) && is_array($_POST['dias_semana']) ? $_POST['dias_semana'] : [];
    $dias_elegidos  = array_values(array_intersect(array_map('intval', $dias_raw), array_keys($dias_semana_recurrente)));

    if ($sitio_id <= 0 || $personal_id <= 0 || $fecha_base === '' || $hora_inicio_in === '' || $hora_fin_in === '' || empty($dias_elegidos)) {
        $mensaje_error = "Selecciona colaborador, sitio, horario y al menos un día de la semana.";
    } else {
        $base_ts = strtotime($fecha_base);
        $inicio_valido = strtotime('2000-01-01 ' . $hora_inicio_in);
        $fin_valido = strtotime('2000-01-01 ' . $hora_fin_in);

        if ($base_ts === false || $inicio_valido === false || $fin_valido === false) {
            $mensaje_error = "Formato de fecha u hora no válido.";
        } else {
            $base_date = new DateTime(date('Y-m-d', $base_ts));

            if ($rango === 'mes') {
                $rango_inicio = new DateTime($base_date->format('Y-m-01'));
                $rango_fin = new DateTime($base_date->format('Y-m-t'));
            } else {
                $rango_inicio = clone $base_date;
                $rango_inicio->modify('monday this week');
                $rango_fin = clone $rango_inicio;
                $rango_fin->modify('sunday this week');
            }

            $sql_dup = "SELECT id FROM turnos WHERE personal_id = ? AND hora_inicio = ? LIMIT 1";
            $sql_ins = "
                INSERT INTO turnos
                    (sitio_id, personal_id, supervisor_id, hora_inicio, hora_fin, es_turno_extra, estado)
                VALUES
                    (?, ?, ?, ?, ?, 0, 'PROGRAMADO')
            ";

            $stmt_dup = mysqli_prepare($conexion, $sql_dup);
            $stmt_ins = mysqli_prepare($conexion, $sql_ins);

            if (!$stmt_dup || !$stmt_ins) {
                if ($stmt_dup) {
                    mysqli_stmt_close($stmt_dup);
                }
                if ($stmt_ins) {
                    mysqli_stmt_close($stmt_ins);
                }
                $mensaje_error = "No se pudo preparar la generación de turnos.";
            } else {
                $creados = 0;
                $omitidos = 0;
                $errores = 0;
                $actual = clone $rango_inicio;

                while ($actual <= $rango_fin) {
                    $dia_semana = (int)$actual->format('N');

                    if (in_array($dia_semana, $dias_elegidos, true)) {
                        $fecha_turno = $actual->format('Y-m-d');
                        $ts_inicio = strtotime($fecha_turno . ' ' . $hora_inicio_in);
                        $ts_fin = strtotime($fecha_turno . ' ' . $hora_fin_in);

                        if ($ts_inicio !== false && $ts_fin !== false) {
                            if ($ts_fin <= $ts_inicio) {
                                $ts_fin = strtotime('+1 day', $ts_fin);
                            }

                            $hora_inicio = date('Y-m-d H:i:s', $ts_inicio);
                            $hora_fin = date('Y-m-d H:i:s', $ts_fin);

                            mysqli_stmt_bind_param($stmt_dup, "is", $personal_id, $hora_inicio);
                            if (!mysqli_stmt_execute($stmt_dup)) {
                                $errores++;
                                $actual->modify('+1 day');
                                continue;
                            }
                            mysqli_stmt_store_result($stmt_dup);

                            if (mysqli_stmt_num_rows($stmt_dup) > 0) {
                                $omitidos++;
                                mysqli_stmt_free_result($stmt_dup);
                                $actual->modify('+1 day');
                                continue;
                            }

                            mysqli_stmt_free_result($stmt_dup);

                            mysqli_stmt_bind_param(
                                $stmt_ins,
                                "iiiss",
                                $sitio_id,
                                $personal_id,
                                $usuario_id,
                                $hora_inicio,
                                $hora_fin
                            );

                            if (mysqli_stmt_execute($stmt_ins)) {
                                $creados++;
                            } else {
                                $errores++;
                            }
                        } else {
                            $errores++;
                        }
                    }

                    $actual->modify('+1 day');
                }

                mysqli_stmt_close($stmt_dup);
                mysqli_stmt_close($stmt_ins);

                if ($errores > 0) {
                    $mensaje_error = "Se generaron {$creados} turnos, se omitieron {$omitidos} duplicados y hubo {$errores} errores.";
                } else {
                    $mensaje_ok = "Turnos generados: {$creados}. Duplicados omitidos: {$omitidos}.";
                }
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
} elseif (isset($_POST['sitio_id_recurrente']) && empty($mensaje_error)) {
    // Si se acaba de generar turnos, mantener ese sitio seleccionado
    $filtro_sitio_id = (int)$_POST['sitio_id_recurrente'];
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
        p.id AS personal_id,
        p.fecha_contratacion,
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
    <title>Asignación de Turnos | MSTV Control</title>
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
                                        Define jornadas ordinarias y turnos extra para cualquier colaborador activo.
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
                                            <label for="personal_id" class="form-label">Colaborador</label>
                                            <select name="personal_id" id="personal_id" class="form-select" required>
                                                <option value="">Selecciona un colaborador</option>
                                                <?php foreach ($personal_activo as $persona): ?>
                                                    <option value="<?php echo (int)$persona['personal_id']; ?>">
                                                        <?php
                                                        $detalles = [];
                                                        $detalles[] = 'No. ' . app_employee_number($persona);
                                                        if (!empty($persona['usuario'])) {
                                                            $detalles[] = 'usuario: ' . $persona['usuario'];
                                                        }
                                                        if (!empty($persona['rol_nombre'])) {
                                                            $detalles[] = 'rol: ' . $persona['rol_nombre'];
                                                        }
                                                        echo htmlspecialchars($persona['nombres'] . ' ' . $persona['apellidos']);
                                                        if ($detalles) {
                                                            echo htmlspecialchars(' (' . implode(' - ', $detalles) . ')');
                                                        }
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

                            <div class="card card-form mt-4">
                                <div class="card-body">
                                    <h6 class="mb-2">Generar horarios recurrentes</h6>
                                    <p class="text-muted small mb-3">
                                        Crea turnos reales por semana o mes. Si la hora de fin es menor o igual a la de inicio, se guardará como turno nocturno del día siguiente.
                                    </p>

                                    <form method="post" action="turnos-asignacion.php">
                                        <input type="hidden" name="accion" value="generar_turnos">

                                        <div class="mb-3">
                                            <label for="sitio_id_recurrente" class="form-label">Sitio / Caseta</label>
                                            <select name="sitio_id_recurrente" id="sitio_id_recurrente" class="form-select" required>
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
                                            <label for="personal_id_recurrente" class="form-label">Colaborador</label>
                                            <select name="personal_id_recurrente" id="personal_id_recurrente" class="form-select" required>
                                                <option value="">Selecciona un colaborador</option>
                                                <?php foreach ($personal_activo as $persona): ?>
                                                    <option value="<?php echo (int)$persona['personal_id']; ?>">
                                                        <?php
                                                        $detalles = [];
                                                        $detalles[] = 'No. ' . app_employee_number($persona);
                                                        if (!empty($persona['usuario'])) {
                                                            $detalles[] = 'usuario: ' . $persona['usuario'];
                                                        }
                                                        if (!empty($persona['rol_nombre'])) {
                                                            $detalles[] = 'rol: ' . $persona['rol_nombre'];
                                                        }
                                                        echo htmlspecialchars($persona['nombres'] . ' ' . $persona['apellidos']);
                                                        if ($detalles) {
                                                            echo htmlspecialchars(' (' . implode(' - ', $detalles) . ')');
                                                        }
                                                        ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="fecha_base_recurrente" class="form-label">Fecha base</label>
                                                <input type="date" name="fecha_base_recurrente" id="fecha_base_recurrente"
                                                       class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="rango_recurrente" class="form-label">Rango</label>
                                                <select name="rango_recurrente" id="rango_recurrente" class="form-select" required>
                                                    <option value="semana">Semana de la fecha base</option>
                                                    <option value="mes">Mes de la fecha base</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="hora_inicio_recurrente" class="form-label">Hora inicio</label>
                                                <input type="time" name="hora_inicio_recurrente" id="hora_inicio_recurrente"
                                                       class="form-control" required>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="hora_fin_recurrente" class="form-label">Hora fin</label>
                                                <input type="time" name="hora_fin_recurrente" id="hora_fin_recurrente"
                                                       class="form-control" required>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label d-block">Días de la semana</label>
                                            <?php foreach ($dias_semana_recurrente as $numero_dia => $nombre_dia): ?>
                                                <div class="form-check form-check-inline">
                                                    <input class="form-check-input" type="checkbox"
                                                           name="dias_semana[]"
                                                           id="dia_semana_<?php echo (int)$numero_dia; ?>"
                                                           value="<?php echo (int)$numero_dia; ?>"
                                                           <?php echo $numero_dia <= 5 ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="dia_semana_<?php echo (int)$numero_dia; ?>">
                                                        <?php echo htmlspecialchars($nombre_dia); ?>
                                                    </label>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>

                                        <div class="d-grid">
                                            <button type="submit" class="btn btn-outline-primary">
                                                Generar turnos
                                            </button>
                                        </div>
                                    </form>
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
                                    <input
                                        type="search"
                                        class="form-control form-control-sm mb-3"
                                        data-turnos-search="#turnos-recientes-table"
                                        placeholder="Buscar colaborador, No. empleado, sitio o estado..."
                                    >
                                    <div class="table-responsive">
                                        <table class="table table-sm mb-0 align-middle" id="turnos-recientes-table">
                                            <thead>
                                                <tr>
                                                    <th>No. empleado</th>
                                                    <th>Colaborador</th>
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
                                                        <td colspan="7" class="text-center text-muted py-3">
                                                            No hay turnos para mostrar.
                                                        </td>
                                                    </tr>
                                                <?php else: ?>
                                                    <?php foreach ($turnos as $t): ?>
                                                        <tr>
                                                            <td><?php echo htmlspecialchars(app_employee_number($t)); ?></td>
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
    <script>
        document.querySelectorAll('[data-turnos-search]').forEach(function (input) {
            var table = document.querySelector(input.getAttribute('data-turnos-search'));
            if (!table) return;
            input.addEventListener('input', function () {
                var term = input.value.toLowerCase().trim();
                table.querySelectorAll('tbody tr').forEach(function (row) {
                    row.style.display = !term || row.textContent.toLowerCase().indexOf(term) !== -1 ? '' : 'none';
                });
            });
        });
    </script>
    <!-- END GLOBAL MANDATORY SCRIPTS -->

</body>
</html>
