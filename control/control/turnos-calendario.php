<?php
// turnos-calendario.php
session_start();
include 'lib/db.php';

// Verificar sesión
if (!isset($_SESSION['usuario_id']) || empty($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit;
}

// Opcional: podrías usar el rol para permisos finos
$rol_nombre = isset($_SESSION['rol_nombre']) ? $_SESSION['rol_nombre'] : '';

// =============================
//   CONSULTA DE TURNOS
// =============================
$eventos = [];

$sql = "
    SELECT 
        t.id,
        t.sitio_id,
        t.personal_id,
        t.supervisor_id,
        t.hora_inicio,
        t.hora_fin,
        t.estado,
        t.es_turno_extra,
        s.nombre AS sitio_nombre,
        p.nombres,
        p.apellidos
    FROM turnos t
    INNER JOIN sitios s   ON s.id = t.sitio_id
    INNER JOIN personal p ON p.id = t.personal_id
    ORDER BY t.hora_inicio ASC
";

if ($resultado = mysqli_query($conexion, $sql)) {
    while ($fila = mysqli_fetch_assoc($resultado)) {

        $titulo = $fila['nombres'] . ' ' . $fila['apellidos'] . ' - ' . $fila['sitio_nombre'];

        // Colores simples según estado / turno extra
        $colorFondo = '#4361ee'; // default
        if ($fila['estado'] === 'AUSENTE') {
            $colorFondo = '#e7515a';
        } elseif ($fila['estado'] === 'EN_PROGRESO') {
            $colorFondo = '#00ab55';
        } elseif (!empty($fila['es_turno_extra']) && (int)$fila['es_turno_extra'] === 1) {
            $colorFondo = '#f7b731';
        }

        $eventos[] = [
            'id'    => (int)$fila['id'],
            'title' => $titulo,
            'start' => $fila['hora_inicio'], // formateado YYYY-MM-DD HH:MM:SS
            'end'   => $fila['hora_fin'],
            'backgroundColor' => $colorFondo,
            'borderColor'     => $colorFondo,
            'textColor'       => '#ffffff',
            'extendedProps'   => [
                'sitio'         => $fila['sitio_nombre'],
                'guardia'       => $fila['nombres'] . ' ' . $fila['apellidos'],
                'estado'        => $fila['estado'],
                'es_turno_extra'=> (int)$fila['es_turno_extra'],
            ],
        ];
    }
    mysqli_free_result($resultado);
}

// No cerramos la conexión por si algún include la usa más abajo
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, shrink-to-fit=no">
    <title>Calendario de Turnos | MSTV Control de Guardias</title>
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

    <!-- BEGIN PAGE LEVEL STYLE (FULLCALENDAR) -->
    <link href="../src/plugins/src/fullcalendar/fullcalendar.min.css" rel="stylesheet" type="text/css" />
    <link href="../src/plugins/css/light/fullcalendar/custom-fullcalendar.css" rel="stylesheet" type="text/css" />
    <link href="../src/assets/css/light/components/modal.css" rel="stylesheet" type="text/css">

    <link href="../src/plugins/css/dark/fullcalendar/custom-fullcalendar.css" rel="stylesheet" type="text/css" />
    <link href="../src/assets/css/dark/components/modal.css" rel="stylesheet" type="text/css">
    <!-- END PAGE LEVEL STYLE -->

    <style>
        .calendar-container {
            background: #fff;
            border-radius: 8px;
            padding: 20px;
        }
        body.dark .calendar-container {
            background: #1b1b1b;
        }
        .fc-event-title {
            font-size: 0.8rem;
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
                        <div class="breadcrumbs-container" data-page-heading="Calendario de Turnos">
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
                                    <h5 class="mb-0">Calendario de Turnos</h5>
                                    <small class="text-muted">Agenda general de guardias y turnos programados</small>
                                </div>
                            </header>
                        </div>
                    </div>
                    <!--  END BREADCRUMBS  -->

                    <div class="row layout-top-spacing layout-spacing" id="cancel-row">
                        <div class="col-xl-12 col-lg-12 col-md-12">
                            <div class="calendar-container">
                                <div class="calendar"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Detalle de Turno -->
                    <div class="modal fade" id="modalTurno" tabindex="-1" aria-labelledby="modalTurnoLabel" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="modalTurnoLabel">Detalle del turno</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="mb-2">
                                        <strong>Guardia:</strong> <span id="t-guardia"></span>
                                    </div>
                                    <div class="mb-2">
                                        <strong>Sitio:</strong> <span id="t-sitio"></span>
                                    </div>
                                    <div class="mb-2">
                                        <strong>Horario:</strong> <span id="t-horario"></span>
                                    </div>
                                    <div class="mb-2">
                                        <strong>Estado:</strong> <span id="t-estado" class="badge bg-secondary"></span>
                                    </div>
                                    <div class="mb-2">
                                        <strong>Tipo:</strong> <span id="t-tipo" class="badge bg-info"></span>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cerrar</button>
                                    <!-- Aquí después puedes agregar botón para editar el turno -->
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

    <!-- BEGIN PAGE LEVEL SCRIPTS (FULLCALENDAR) -->
    <script src="../src/plugins/src/fullcalendar/fullcalendar.min.js"></script>
    <script src="../src/plugins/src/uuid/uuid4.min.js"></script>
    <!-- END PAGE LEVEL SCRIPTS -->

    <script>
        // Pasar eventos desde PHP a JS
        window.turnosEventos = <?php echo json_encode($eventos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var calendarEl = document.querySelector('.calendar');

            if (!calendarEl) return;

            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',

                // 👇 Forzamos español
                locale: 'es',
                buttonText: {
                    today:    'Hoy',
                    month:    'Mes',
                    week:     'Semana',
                    day:      'Día',
                    list:     'Lista'
                },
                weekText: 'Sm',
                allDayText: 'Todo el día',
                moreLinkText: function(n) { return '+ ver ' + n + ' más'; },
                noEventsText: 'No hay turnos para mostrar',

                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek'
                },

                // Forzamos nombres de días en español en el encabezado
                dayHeaderContent: function(arg) {
                    return arg.date.toLocaleDateString('es-MX', { weekday: 'short' });
                },

                navLinks: true,
                selectable: false,
                editable: false,
                events: window.turnosEventos || [],

                eventClick: function (info) {
                    var ev = info.event;
                    var props = ev.extendedProps || {};

                    document.getElementById('t-guardia').textContent = props.guardia || '';
                    document.getElementById('t-sitio').textContent   = props.sitio || '';

                    var inicio = ev.start;
                    var fin    = ev.end;

                    var opciones = {
                        hour: '2-digit',
                        minute: '2-digit',
                        day: '2-digit',
                        month: '2-digit',
                        year: 'numeric'
                    };

                    var txtHorario = '';
                    if (inicio) {
                        txtHorario += inicio.toLocaleString('es-MX', opciones);
                    }
                    if (fin) {
                        txtHorario += '  -  ' + fin.toLocaleString('es-MX', opciones);
                    }

                    document.getElementById('t-horario').textContent = txtHorario;

                    var estadoSpan = document.getElementById('t-estado');
                    estadoSpan.textContent = props.estado || '';
                    estadoSpan.className = 'badge';
                    switch (props.estado) {
                        case 'PROGRAMADO':
                            estadoSpan.classList.add('bg-secondary');
                            break;
                        case 'EN_PROGRESO':
                            estadoSpan.classList.add('bg-success');
                            break;
                        case 'COMPLETADO':
                            estadoSpan.classList.add('bg-primary');
                            break;
                        case 'AUSENTE':
                            estadoSpan.classList.add('bg-danger');
                            break;
                        default:
                            estadoSpan.classList.add('bg-secondary');
                    }

                    var tipoSpan = document.getElementById('t-tipo');
                    if (props.es_turno_extra && parseInt(props.es_turno_extra) === 1) {
                        tipoSpan.textContent = 'Turno extra';
                        tipoSpan.className = 'badge bg-warning text-dark';
                    } else {
                        tipoSpan.textContent = 'Turno ordinario';
                        tipoSpan.className = 'badge bg-info';
                    }

                    var modal = new bootstrap.Modal(document.getElementById('modalTurno'));
                    modal.show();
                }
            });

            calendar.render();
        });
    </script>


</body>
</html>
