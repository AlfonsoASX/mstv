<?php
// sitios-editor-geocerca.php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit;
}

include 'lib/db.php'; // Debe definir $conexion (mysqli)

// Roles que pueden administrar geocercas (ajusta a tu gusto)
$rol = isset($_SESSION['rol_nombre']) ? $_SESSION['rol_nombre'] : '';
$roles_permitidos = ['ADMIN', 'SUPERVISOR', 'DUEÑO'];

if (!in_array($rol, $roles_permitidos)) {
    echo "No tienes permisos para ver esta página.";
    exit;
}

// Helper
function limpiar($txt) {
    return trim(htmlspecialchars($txt, ENT_QUOTES, 'UTF-8'));
}

$mensaje_ok    = '';
$mensaje_error = '';

// =======================
//  GUARDAR (INSERT/UPDATE)
// =======================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'guardar') {

    $sitio_id   = isset($_POST['sitio_id']) ? (int)$_POST['sitio_id'] : 0;
    $cliente_id = isset($_POST['cliente_id']) ? (int)$_POST['cliente_id'] : 0;
    $nombre     = isset($_POST['nombre']) ? limpiar($_POST['nombre']) : '';
    $direccion  = isset($_POST['direccion']) ? limpiar($_POST['direccion']) : '';
    $latitud    = isset($_POST['latitud']) ? (float)$_POST['latitud'] : 0;
    $longitud   = isset($_POST['longitud']) ? (float)$_POST['longitud'] : 0;
    $radio      = isset($_POST['radio_geocerca']) ? (int)$_POST['radio_geocerca'] : 0;

    if ($cliente_id <= 0 || $nombre === '' || !$latitud || !$longitud || $radio <= 0) {
        $mensaje_error = "Completa cliente, nombre, ubicación y radio.";
    } else {
        if ($sitio_id > 0) {
            // UPDATE
            $sql_up = "
                UPDATE sitios
                SET cliente_id = ?,
                    nombre = ?,
                    direccion = ?,
                    latitud = ?,
                    longitud = ?,
                    radio_geocerca = ?
                WHERE id = ?
            ";
            if ($stmt = mysqli_prepare($conexion, $sql_up)) {
                mysqli_stmt_bind_param(
                    $stmt,
                    "issdidi",
                    $cliente_id,
                    $nombre,
                    $direccion,
                    $latitud,
                    $longitud,
                    $radio,
                    $sitio_id
                );
                if (mysqli_stmt_execute($stmt)) {
                    $mensaje_ok = "Sitio actualizado correctamente.";
                } else {
                    $mensaje_error = "Error al actualizar el sitio: " . mysqli_error($conexion);
                }
                mysqli_stmt_close($stmt);
            } else {
                $mensaje_error = "Error interno al preparar UPDATE.";
            }
        } else {
            // INSERT
            $sql_in = "
                INSERT INTO sitios (cliente_id, nombre, direccion, latitud, longitud, tipo_geocerca, radio_geocerca, esta_activo)
                VALUES (?, ?, ?, ?, ?, 'CIRCULO', ?, 1)
            ";
            if ($stmt = mysqli_prepare($conexion, $sql_in)) {
                mysqli_stmt_bind_param(
                    $stmt,
                    "issddi",
                    $cliente_id,
                    $nombre,
                    $direccion,
                    $latitud,
                    $longitud,
                    $radio
                );
                if (mysqli_stmt_execute($stmt)) {
                    $mensaje_ok = "Sitio creado correctamente.";
                } else {
                    $mensaje_error = "Error al crear el sitio: " . mysqli_error($conexion);
                }
                mysqli_stmt_close($stmt);
            } else {
                $mensaje_error = "Error interno al preparar INSERT.";
            }
        }
    }
}

// =======================
//  CARGAR CATÁLOGOS
// =======================
$lista_clientes = [];
$sql_c = "SELECT id, nombre_empresa FROM clientes ORDER BY nombre_empresa";
if ($res_c = mysqli_query($conexion, $sql_c)) {
    while ($row = mysqli_fetch_assoc($res_c)) {
        $lista_clientes[] = $row;
    }
    mysqli_free_result($res_c);
}

// Sitios existentes
$lista_sitios   = [];
$sql_s = "
    SELECT 
        s.id,
        s.cliente_id,
        s.nombre,
        s.direccion,
        s.latitud,
        s.longitud,
        s.radio_geocerca,
        c.nombre_empresa AS cliente_nombre
    FROM sitios s
    INNER JOIN clientes c ON c.id = s.cliente_id
    ORDER BY c.nombre_empresa, s.nombre
";
if ($res_s = mysqli_query($conexion, $sql_s)) {
    while ($row = mysqli_fetch_assoc($res_s)) {
        $lista_sitios[] = $row;
    }
    mysqli_free_result($res_s);
}

// Para JS
$sitios_js = [];
foreach ($lista_sitios as $s) {
    $sitios_js[] = [
        'id'            => (int)$s['id'],
        'cliente_id'    => (int)$s['cliente_id'],
        'cliente'       => $s['cliente_nombre'],
        'nombre'        => $s['nombre'],
        'direccion'     => $s['direccion'],
        'latitud'       => (float)$s['latitud'],
        'longitud'      => (float)$s['longitud'],
        'radio_geocerca'=> (int)$s['radio_geocerca'],
    ];
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, shrink-to-fit=no">
    <title>Editor de geocercas de sitios</title>
    <link rel="icon" type="image/x-icon" href="../src/assets/img/favicon.ico"/>
    <link href="../layouts/vertical-light-menu/css/light/loader.css" rel="stylesheet" type="text/css" />
    <link href="../layouts/vertical-light-menu/css/dark/loader.css" rel="stylesheet" type="text/css" />
    <script src="../layouts/vertical-light-menu/loader.js"></script>

    <!-- ESTILOS GLOBALES -->
    <link href="https://fonts.googleapis.com/css?family=Nunito:400,600,700" rel="stylesheet">
    <link href="../src/bootstrap/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
    <link href="../layouts/vertical-light-menu/css/light/plugins.css" rel="stylesheet" type="text/css" />
    <link href="../layouts/vertical-light-menu/css/dark/plugins.css" rel="stylesheet" type="text/css" />

    <!-- LEAFLET -->
    <link href="../src/plugins/src/leaflet/leaflet.css" rel="stylesheet" type="text/css" />

    <style>
        #mapa-geocerca {
            width: 100%;
            height: 520px;
            border-radius: 8px;
        }
        .leaflet-container {
            border-radius: 8px;
        }
        .form-section-title {
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: #888;
            margin-bottom: .3rem;
        }
        .badge-cliente {
            font-size: 0.7rem;
            text-transform: uppercase;
        }
        .tabla-sitios td {
            vertical-align: middle;
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
                        <div class="breadcrumbs-container" data-page-heading="Sitios y geocercas">
                            <header class="header navbar navbar-expand-sm">
                                <a href="javascript:void(0);" class="btn-toggle sidebarCollapse" data-placement="bottom">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                         viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                         stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                         class="feather feather-menu">
                                        <line x1="3" y1="12" x2="21" y2="12"></line>
                                        <line x1="3" y1="6"  x2="21" y2="6"></line>
                                        <line x1="3" y1="18" x2="21" y2="18"></line>
                                    </svg>
                                </a>

                                <div class="d-flex breadcrumb-content">
                                    <h4 class="mb-0">Editor de geocercas</h4>
                                    <div class="page-header ml-3">
                                        <nav class="breadcrumb-one" aria-label="breadcrumb">
                                            <ol class="breadcrumb">
                                                <li class="breadcrumb-item">
                                                    <a href="dashboard.php">Dashboard</a>
                                                </li>
                                                <li class="breadcrumb-item">
                                                    <a href="sitios-lista.php">Sitios</a>
                                                </li>
                                                <li class="breadcrumb-item active" aria-current="page">
                                                    Editor de geocercas
                                                </li>
                                            </ol>
                                        </nav>
                                    </div>
                                </div>
                            </header>
                        </div>
                    </div>
                    <!-- /BARRA SUPERIOR -->

                    <!-- CONTENIDO PRINCIPAL -->

                    
                    <div class="row layout-top-spacing">

                        <!-- COLUMNA MAPA -->
                        <div class="col-xl-7 col-lg-7 col-md-12 mb-4">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <div>
                                        <h5 class="mb-0">Mapa de sitios</h5>
                                        <small class="text-muted">Da clic en el mapa para elegir la ubicación del sitio.</small>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div id="mapa-geocerca"></div>
                                </div>
                            </div>
                        </div>

                        <!-- COLUMNA FORM + LISTA -->
                        <div class="col-xl-5 col-lg-5 col-md-12 mb-4">
                            <!-- MENSAJES -->
                            <?php if ($mensaje_ok): ?>
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    <?php echo $mensaje_ok; ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            <?php endif; ?>

                            <?php if ($mensaje_error): ?>
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <?php echo $mensaje_error; ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            <?php endif; ?>

                            <!-- FORM SITIO -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h6 class="mb-0">Datos del sitio / geocerca</h6>
                                </div>
                                <div class="card-body">
                                    <form method="post" id="form-sitio">
                                        <input type="hidden" name="accion" value="guardar">
                                        <input type="hidden" name="sitio_id" id="sitio_id" value="0">

                                        <div class="mb-2">
                                            <div class="form-section-title">Cliente</div>
                                            <select name="cliente_id" id="cliente_id" class="form-control form-control-sm" required>
                                                <option value="">-- Selecciona cliente --</option>
                                                <?php foreach ($lista_clientes as $c): ?>
                                                    <option value="<?php echo (int)$c['id']; ?>">
                                                        <?php echo htmlspecialchars($c['nombre_empresa']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div class="mb-2">
                                            <div class="form-section-title">Nombre del sitio</div>
                                            <input type="text" name="nombre" id="nombre" class="form-control form-control-sm" required>
                                        </div>

                                        <div class="mb-2">
                                            <div class="form-section-title">Dirección (opcional)</div>
                                            <textarea name="direccion" id="direccion" rows="2" class="form-control form-control-sm"></textarea>
                                        </div>

                                        <div class="mb-2">
                                            <div class="form-section-title">Ubicación</div>
                                            <div class="row g-1">
                                                <div class="col-6">
                                                    <label class="form-label mb-1">Latitud</label>
                                                    <input type="text" name="latitud" id="latitud" class="form-control form-control-sm" readonly required>
                                                </div>
                                                <div class="col-6">
                                                    <label class="form-label mb-1">Longitud</label>
                                                    <input type="text" name="longitud" id="longitud" class="form-control form-control-sm" readonly required>
                                                </div>
                                            </div>
                                            <small class="text-muted">Se rellenan automáticamente al hacer clic en el mapa.</small>
                                        </div>

                                        <div class="mb-3">
                                            <div class="form-section-title">Radio de geocerca (metros)</div>
                                            <input type="number" min="10" step="10" name="radio_geocerca" id="radio_geocerca" class="form-control form-control-sm" required>
                                            <small class="text-muted">Ejemplo: 50, 100, 200 metros. Se dibuja un círculo alrededor del punto.</small>
                                        </div>

                                        <div class="d-flex justify-content-between">
                                            <button type="submit" class="btn btn-primary btn-sm">
                                                Guardar sitio
                                            </button>
                                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="resetFormulario();">
                                                Nuevo sitio
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>



                        </div>

                    </div>
                    <!-- /CONTENIDO PRINCIPAL -->
                            <!-- LISTA DE SITIOS -->
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0">Sitios configurados</h6>
                                    <span class="badge bg-light text-dark"><?php echo count($lista_sitios); ?> sitios</span>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-sm mb-0 tabla-sitios">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Sitio</th>
                                                    <th>Cliente</th>
                                                    <th>Radio</th>
                                                    <th class="text-end">Acciones</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (empty($lista_sitios)): ?>
                                                    <tr>
                                                        <td colspan="4" class="text-center text-muted py-3">
                                                            No hay sitios configurados todavía.
                                                        </td>
                                                    </tr>
                                                <?php else: ?>
                                                    <?php foreach ($lista_sitios as $s): ?>
                                                        <tr>
                                                            <td>
                                                                <strong><?php echo htmlspecialchars($s['nombre']); ?></strong><br>
                                                                <small class="text-muted">
                                                                    <?php echo htmlspecialchars($s['direccion']); ?>
                                                                </small>
                                                            </td>
                                                            <td>
                                                                <span class="badge bg-primary badge-cliente">
                                                                    <?php echo htmlspecialchars($s['cliente_nombre']); ?>
                                                                </span>
                                                            </td>
                                                            <td>
                                                                <?php echo (int)$s['radio_geocerca']; ?> m
                                                            </td>
                                                            <td class="text-end">
                                                                <button
                                                                    type="button"
                                                                    class="btn btn-outline-primary btn-xs mb-1"
                                                                    onclick="cargarSitio(
                                                                        <?php echo (int)$s['id']; ?>,
                                                                        <?php echo (int)$s['cliente_id']; ?>,
                                                                        '<?php echo htmlspecialchars($s['nombre'], ENT_QUOTES); ?>',
                                                                        '<?php echo htmlspecialchars($s['direccion'], ENT_QUOTES); ?>',
                                                                        '<?php echo $s['latitud']; ?>',
                                                                        '<?php echo $s['longitud']; ?>',
                                                                        '<?php echo (int)$s['radio_geocerca']; ?>'
                                                                    );">
                                                                    Editar
                                                                </button>
                                                                <button
                                                                    type="button"
                                                                    class="btn btn-outline-secondary btn-xs"
                                                                    onclick="centrarSitio(<?php echo (int)$s['id']; ?>);">
                                                                    Ver en mapa
                                                                </button>
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

    <!-- LEAFLET JS -->
    <script src="../src/plugins/src/leaflet/leaflet.js"></script>

    <script>
        var sitiosData = <?php echo json_encode($sitios_js, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;

        var map;
        var tempMarker = null;
        var tempCircle = null;
        var markersById = {};

        document.addEventListener('DOMContentLoaded', function () {
            initMapa();
            initEventosFormulario();
        });

        function initMapa() {
            // Centro por defecto (León, Gto aprox.)
            map = L.map('mapa-geocerca').setView([21.123, -101.68], 12);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap'
            }).addTo(map);

            // Renderizar sitios existentes
            sitiosData.forEach(function (s) {
                var marker = L.marker([s.latitud, s.longitud]).addTo(map)
                    .bindPopup('<strong>' + s.nombre + '</strong><br><small>' + (s.cliente || '') + '</small><br>' + (s.direccion || ''));

                var circle = L.circle([s.latitud, s.longitud], {
                    radius: s.radio_geocerca || 50,
                    color: '#007bff',
                    fillColor: '#007bff',
                    fillOpacity: 0.15
                }).addTo(map);

                markersById[s.id] = {
                    marker: marker,
                    circle: circle
                };
            });

            // Click en mapa para geocerca nueva / mover
            map.on('click', function (e) {
                colocarPreview(e.latlng.lat, e.latlng.lng);
            });
        }

        function colocarPreview(lat, lng) {
            if (tempMarker) {
                map.removeLayer(tempMarker);
            }
            if (tempCircle) {
                map.removeLayer(tempCircle);
            }

            tempMarker = L.marker([lat, lng]).addTo(map);
            var radio = parseInt(document.getElementById('radio_geocerca').value || '0', 10);

            if (radio > 0) {
                tempCircle = L.circle([lat, lng], {
                    radius: radio,
                    color: '#28a745',
                    fillColor: '#28a745',
                    fillOpacity: 0.15
                }).addTo(map);
            }

            document.getElementById('latitud').value = lat.toFixed(6);
            document.getElementById('longitud').value = lng.toFixed(6);
        }

        function actualizarPreviewRadio() {
            var lat = parseFloat(document.getElementById('latitud').value || '0');
            var lng = parseFloat(document.getElementById('longitud').value || '0');
            if (!lat || !lng) return;
            colocarPreview(lat, lng);
        }

        function initEventosFormulario() {
            var radioInput = document.getElementById('radio_geocerca');
            if (radioInput) {
                radioInput.addEventListener('change', actualizarPreviewRadio);
                radioInput.addEventListener('keyup', actualizarPreviewRadio);
            }
        }

        function resetFormulario() {
            document.getElementById('form-sitio').reset();
            document.getElementById('sitio_id').value = 0;
            document.getElementById('latitud').value = '';
            document.getElementById('longitud').value = '';

            if (tempMarker) { map.removeLayer(tempMarker); tempMarker = null; }
            if (tempCircle) { map.removeLayer(tempCircle); tempCircle = null; }
        }

        function cargarSitio(id, clienteId, nombre, direccion, lat, lng, radio) {
            document.getElementById('sitio_id').value = id;
            document.getElementById('cliente_id').value = clienteId;
            document.getElementById('nombre').value = nombre;
            document.getElementById('direccion').value = direccion;
            document.getElementById('latitud').value = parseFloat(lat).toFixed(6);
            document.getElementById('longitud').value = parseFloat(lng).toFixed(6);
            document.getElementById('radio_geocerca').value = radio;

            map.setView([lat, lng], 15);
            colocarPreview(parseFloat(lat), parseFloat(lng));
        }

        function centrarSitio(id) {
            var s = sitiosData.find(function (x) { return x.id === id; });
            if (!s) return;
            map.setView([s.latitud, s.longitud], 15);

            if (markersById[id] && markersById[id].marker) {
                markersById[id].marker.openPopup();
            }
        }
    </script>
</body>
</html>
