<?php

require_once __DIR__ . '/helpers.php';

function app_render_page_start(string $title, string $heading, string $subtitle = '', string $extraHead = ''): void
{
    ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, shrink-to-fit=no">
    <title><?php echo app_h($title); ?></title>
    <link rel="icon" type="image/x-icon" href="../src/assets/img/favicon.ico"/>
    <link href="../layouts/vertical-light-menu/css/light/loader.css" rel="stylesheet" type="text/css" />
    <link href="../layouts/vertical-light-menu/css/dark/loader.css" rel="stylesheet" type="text/css" />
    <script src="../layouts/vertical-light-menu/loader.js"></script>
    <link href="https://fonts.googleapis.com/css?family=Nunito:400,600,700" rel="stylesheet">
    <link href="../src/bootstrap/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
    <link href="../layouts/vertical-light-menu/css/light/plugins.css" rel="stylesheet" type="text/css" />
    <link href="../layouts/vertical-light-menu/css/dark/plugins.css" rel="stylesheet" type="text/css" />
    <link href="../src/assets/css/light/dashboard/dash_1.css" rel="stylesheet" type="text/css" />
    <link href="../src/assets/css/dark/dashboard/dash_1.css" rel="stylesheet" type="text/css" />
    <link rel="stylesheet" type="text/css" href="../src/plugins/src/table/datatable/datatables.css">
    <link rel="stylesheet" type="text/css" href="../src/plugins/src/table/datatable/dt-global_style.css">
    <style>
        .summary-card {
            border-radius: 16px;
            box-shadow: 0 12px 35px rgba(31, 45, 61, 0.08);
            border: 1px solid rgba(70, 84, 101, 0.08);
        }
        .summary-label {
            color: #667085;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .summary-value {
            font-size: 1.55rem;
            font-weight: 700;
            color: #101828;
        }
        .table thead th {
            text-transform: uppercase;
            font-size: 0.78rem;
            letter-spacing: 0.03em;
        }
        .badge-soft {
            border-radius: 999px;
            padding: 0.45rem 0.7rem;
        }
        .content-card {
            border-radius: 18px;
            border: 1px solid rgba(70, 84, 101, 0.08);
        }
        .form-hint {
            color: #667085;
            font-size: 0.8rem;
        }
    </style>
    <?php echo $extraHead; ?>
</head>
<body class="layout-boxed">
    <div id="load_screen">
        <div class="loader">
            <div class="loader-content">
                <div class="spinner-grow align-self-center"></div>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/../partes/nav.php'; ?>

    <div class="main-container" id="container">
        <div class="overlay"></div>
        <div class="search-overlay"></div>

        <div class="sidebar-wrapper sidebar-theme">
            <nav id="sidebar">
                <?php include __DIR__ . '/../partes/menu.php'; ?>
            </nav>
        </div>

        <div id="content" class="main-content">
            <div class="layout-px-spacing">
                <div class="middle-content container-xxl p-0">
                    <div class="secondary-nav mb-3">
                        <div class="breadcrumbs-container" data-page-heading="<?php echo app_h($heading); ?>">
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
                                    <h4 class="mb-0"><?php echo app_h($heading); ?></h4>
                                    <?php if ($subtitle !== ''): ?>
                                        <small class="text-muted"><?php echo app_h($subtitle); ?></small>
                                    <?php endif; ?>
                                </div>
                            </header>
                        </div>
                    </div>
    <?php
}

function app_render_alerts(array $messages): void
{
    foreach ($messages as $type => $message) {
        if ($message === '') {
            continue;
        }

        $class = $type === 'error' ? 'danger' : ($type === 'warning' ? 'warning' : 'success');
        ?>
        <div class="alert alert-<?php echo $class; ?> py-2">
            <?php echo app_h($message); ?>
        </div>
        <?php
    }
}

function app_render_page_end(string $extraScripts = ''): void
{
    $tableSearchScript = <<<HTML
    <script>
    document.addEventListener('input', function (event) {
        const input = event.target.closest('[data-table-search]');
        if (!input) {
            return;
        }

        const selector = input.getAttribute('data-table-search');
        const table = selector ? document.querySelector(selector) : null;
        if (!table) {
            return;
        }

        const needle = input.value.trim().toLowerCase();
        table.querySelectorAll('tbody tr').forEach(function (row) {
            if (row.querySelector('td[colspan]')) {
                return;
            }
            row.style.display = row.textContent.toLowerCase().includes(needle) ? '' : 'none';
        });
    });
    </script>
HTML;
    ?>
                </div>
            </div>
            <?php include __DIR__ . '/../partes/footer.php'; ?>
        </div>
    </div>

    <script src="../src/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../src/plugins/src/perfect-scrollbar/perfect-scrollbar.min.js"></script>
    <script src="../src/plugins/src/mousetrap/mousetrap.min.js"></script>
    <script src="../src/plugins/src/waves/waves.min.js"></script>
    <script src="../layouts/vertical-light-menu/app.js"></script>
    <?php echo $tableSearchScript; ?>
    <?php echo $extraScripts; ?>
</body>
</html>
    <?php
}
