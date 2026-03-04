<?php
// pendiente-datos.php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit;
}

// Si necesitas conexión, descomenta:
// include 'lib/db.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, shrink-to-fit=no">
    <title>Pendiente de datos</title>
    <link rel="icon" type="image/x-icon" href="../src/assets/img/favicon.ico"/>
    <link href="../layouts/vertical-light-menu/css/light/loader.css" rel="stylesheet" type="text/css" />
    <link href="../layouts/vertical-light-menu/css/dark/loader.css" rel="stylesheet" type="text/css" />
    <script src="../layouts/vertical-light-menu/loader.js"></script>

    <!-- ESTILOS GLOBALES -->
    <link href="https://fonts.googleapis.com/css?family=Nunito:400,600,700" rel="stylesheet">
    <link href="../src/bootstrap/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
    <link href="../layouts/vertical-light-menu/css/light/plugins.css" rel="stylesheet" type="text/css" />
    <link href="../layouts/vertical-light-menu/css/dark/plugins.css" rel="stylesheet" type="text/css" />

    <style>
        .empty-state-wrapper {
            min-height: calc(100vh - 200px);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .empty-state-card {
            max-width: 480px;
            margin: 0 auto;
            text-align: center;
            padding: 2.5rem 2rem;
        }
        .empty-state-icon {
            width: 96px;
            height: 96px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
        }
        body.layout-boxed .empty-state-wrapper {
            min-height: calc(100vh - 260px);
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
        <!-- /SIDEBAR -->

        <!-- CONTENIDO -->
        <div id="content" class="main-content">
            <div class="layout-px-spacing">

                <div class="middle-content container-xxl p-0">

                    <!-- BARRA SUPERIOR / MIGAS -->
                    <div class="secondary-nav mb-3">
                        <div class="breadcrumbs-container" data-page-heading="Pendiente de datos">
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
                                    <h4 class="mb-0">Pendiente de datos</h4>
                                    <div class="page-header ml-3">
                                        <nav class="breadcrumb-one" aria-label="breadcrumb">
                                            <ol class="breadcrumb">
                                                <li class="breadcrumb-item">
                                                    <a href="dashboard.php">Dashboard</a>
                                                </li>
                                                <li class="breadcrumb-item active" aria-current="page">
                                                    Pendiente
                                                </li>
                                            </ol>
                                        </nav>
                                    </div>
                                </div>
                            </header>
                        </div>
                    </div>
                    <!-- /BARRA SUPERIOR -->

                    <!-- ESTADO VACÍO -->
                    <div class="empty-state-wrapper">
                        <div class="card empty-state-card shadow-sm">
                            <div class="empty-state-icon bg-light">
                                <!-- Ícono de reloj grande -->
                                <svg xmlns="http://www.w3.org/2000/svg" width="56" height="56"
                                     viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                     stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"
                                     class="feather feather-clock">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <polyline points="12 6 12 12 16 14"></polyline>
                                </svg>
                            </div>
                            <h3 class="mb-2">Aún no se cuentan con los datos necesarios</h3>
                            <p class="text-muted mb-3">
                                En cuanto se registren movimientos y configuraciones clave, esta sección mostrará la información correspondiente.
                            </p>
                            <div class="d-flex justify-content-center gap-2">
                                <a href="dashboard.php" class="btn btn-primary btn-sm">
                                    Volver al dashboard
                                </a>
                                <!-- Botón opcional para ir a configuración o carga de datos -->
                                <!-- <a href="configuracion-global.php" class="btn btn-outline-secondary btn-sm">
                                    Revisar configuración
                                </a> -->
                            </div>
                        </div>
                    </div>
                    <!-- /ESTADO VACÍO -->

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
