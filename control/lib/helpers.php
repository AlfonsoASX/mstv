<?php

if (!function_exists('app_h')) {
    function app_h($value): string
    {
        return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('app_money')) {
    function app_money($amount): string
    {
        return '$' . number_format((float)$amount, 2);
    }
}

if (!function_exists('app_number')) {
    function app_number($value, int $decimals = 2): string
    {
        return number_format((float)$value, $decimals);
    }
}

if (!function_exists('app_employee_number')) {
    function app_employee_number(array $personal): string
    {
        $personalId = (int)($personal['id'] ?? $personal['personal_id'] ?? 0);
        $fechaIngreso = (string)($personal['fecha_contratacion'] ?? '');

        if ($personalId <= 0 || $fechaIngreso === '') {
            return '-';
        }

        $timestamp = strtotime($fechaIngreso);
        if ($timestamp === false) {
            return '-';
        }

        return substr(date('Y', $timestamp), -2) . str_pad((string)$personalId, 4, '0', STR_PAD_LEFT);
    }
}

if (!function_exists('app_get_config_map')) {
    function app_get_config_map(mysqli $conexion): array
    {
        $configs = [];
        $rows = app_db_all($conexion, "SELECT clave_configuracion, valor_configuracion FROM configuracion_sistema");

        foreach ($rows as $row) {
            $configs[$row['clave_configuracion']] = $row['valor_configuracion'];
        }

        return $configs;
    }
}

if (!function_exists('app_config')) {
    function app_config(array $configs, string $key, $default = null)
    {
        return $configs[$key] ?? $default;
    }
}

if (!function_exists('app_config_float')) {
    function app_config_float(array $configs, string $key, float $default = 0.0): float
    {
        return (float)app_config($configs, $key, $default);
    }
}

if (!function_exists('app_config_int')) {
    function app_config_int(array $configs, string $key, int $default = 0): int
    {
        return (int)app_config($configs, $key, $default);
    }
}

if (!function_exists('app_date')) {
    function app_date(?string $value, string $format = 'd/m/Y'): string
    {
        if ($value === null || $value === '') {
            return '-';
        }

        $timestamp = strtotime($value);
        if ($timestamp === false) {
            return app_h($value);
        }

        return date($format, $timestamp);
    }
}

if (!function_exists('app_datetime')) {
    function app_datetime(?string $value, string $format = 'd/m/Y H:i'): string
    {
        return app_date($value, $format);
    }
}

if (!function_exists('app_post')) {
    function app_post(string $key, $default = null)
    {
        return $_POST[$key] ?? $default;
    }
}

if (!function_exists('app_get')) {
    function app_get(string $key, $default = null)
    {
        return $_GET[$key] ?? $default;
    }
}

if (!function_exists('app_redirect')) {
    function app_redirect(string $url): void
    {
        header('Location: ' . $url);
        exit;
    }
}

if (!function_exists('app_require_session')) {
    function app_require_session(): void
    {
        if (!isset($_SESSION['usuario_id']) || empty($_SESSION['usuario_id'])) {
            app_redirect('index.php');
        }
    }
}

if (!function_exists('app_current_role')) {
    function app_current_role(): string
    {
        return (string)($_SESSION['rol_nombre'] ?? '');
    }
}

if (!function_exists('app_current_role_id')) {
    function app_current_role_id(): ?int
    {
        if (!isset($_SESSION['rol_id']) || $_SESSION['rol_id'] === '') {
            return null;
        }

        return (int)$_SESSION['rol_id'];
    }
}

if (!function_exists('app_require_roles')) {
    function app_require_roles(array $roles): void
    {
        global $conexion;

        $currentPage = function_exists('app_current_page_name') ? app_current_page_name() : basename($_SERVER['PHP_SELF'] ?? '');
        if (function_exists('app_permission_page_exists') && app_permission_page_exists($currentPage)) {
            app_require_page_permission($currentPage, ($conexion instanceof mysqli) ? $conexion : null);
            return;
        }

        if (!in_array(app_current_role(), $roles, true)) {
            http_response_code(403);
            echo 'No tienes permisos para ver esta página.';
            exit;
        }
    }
}

if (!function_exists('app_current_page_name')) {
    function app_current_page_name(): string
    {
        $page = $_SERVER['SCRIPT_NAME'] ?? $_SERVER['PHP_SELF'] ?? $_SERVER['REQUEST_URI'] ?? '';
        $path = parse_url((string)$page, PHP_URL_PATH);

        return basename((string)($path ?: $page));
    }
}

if (!function_exists('app_is_super_role')) {
    function app_is_super_role(?string $roleName = null): bool
    {
        $role = trim((string)($roleName ?? app_current_role()));
        $role = function_exists('mb_strtoupper') ? mb_strtoupper($role, 'UTF-8') : strtoupper($role);

        return $role === 'DUEÑO';
    }
}

if (!function_exists('app_permission_catalog')) {
    function app_permission_catalog(): array
    {
        return [
            'dashboard' => [
                'label' => 'Dashboard Ejecutivo',
                'group' => 'PRINCIPAL',
                'pages' => [
                    'dashboard.php' => ['label' => 'Visión general', 'menu' => true],
                    'dashboard-sitio.php' => ['label' => 'Dashboard por sitio', 'menu' => false],
                    'dashboard-cliente.php' => ['label' => 'Dashboard cliente', 'menu' => false],
                ],
            ],
            'turnos' => [
                'label' => 'Agenda y turnos',
                'group' => 'OPERACIÓN Y SUPERVISIÓN',
                'pages' => [
                    'turnos-calendario.php' => ['label' => 'Calendario de turnos', 'menu' => true],
                    'turnos-asignacion.php' => ['label' => 'Asignación y rotaciones', 'menu' => true],
                    'turnos-extras.php' => ['label' => 'Turnos extra', 'menu' => true],
                    'turnos-politicas.php' => ['label' => 'Políticas de jornadas', 'menu' => true],
                ],
            ],
            'checadas' => [
                'label' => 'Checadas y evidencias',
                'group' => 'OPERACIÓN Y SUPERVISIÓN',
                'pages' => [
                    'checadas-lista.php' => ['label' => 'Checadas (lista)', 'menu' => true],
                    'checadas-detalle.php' => ['label' => 'Detalle de evidencia', 'menu' => true],
                ],
            ],
            'incidencias' => [
                'label' => 'Incidencias',
                'group' => 'OPERACIÓN Y SUPERVISIÓN',
                'pages' => [
                    'incidencias-bandeja.php' => ['label' => 'Bandeja de incidencias', 'menu' => true],
                    'incidencias-urgentes.php' => ['label' => 'Urgentes (alertas)', 'menu' => false],
                    'incidencias-historico.php' => ['label' => 'Histórico', 'menu' => false],
                ],
            ],
            'bitacora' => [
                'label' => 'Bitácora global',
                'group' => 'OPERACIÓN Y SUPERVISIÓN',
                'pages' => [
                    'bitacora-sitios.php' => ['label' => 'Bitácora por sitio', 'menu' => true],
                ],
            ],
            'comunicacion' => [
                'label' => 'Comunicación interna',
                'group' => 'OPERACIÓN Y SUPERVISIÓN',
                'pages' => [
                    'chat-admin.php' => ['label' => 'Chat interno', 'menu' => true],
                    'chat-moderacion.php' => ['label' => 'Moderación', 'menu' => true],
                ],
            ],
            'personas' => [
                'label' => 'Personal',
                'group' => 'ADMINISTRACIÓN, RH Y NÓMINA',
                'pages' => [
                    'personas-lista.php' => ['label' => 'Base de personal', 'menu' => true],
                    'personas-ficha.php' => ['label' => 'Ficha de colaborador', 'menu' => false],
                    'personas-documentos.php' => ['label' => 'Documentos', 'menu' => false],
                    'personas-historial-sitios.php' => ['label' => 'Historial de sitios', 'menu' => false],
                ],
            ],
            'sitios' => [
                'label' => 'Sitios y geocercas',
                'group' => 'ADMINISTRACIÓN, RH Y NÓMINA',
                'pages' => [
                    'sitios-lista.php' => ['label' => 'Catálogo de sitios', 'menu' => false],
                    'sitios-editor-geocerca.php' => ['label' => 'Editor de geocercas', 'menu' => true],
                    'sitios-parametros-llegada.php' => ['label' => 'Parámetros de llegada', 'menu' => false],
                ],
            ],
            'nomina' => [
                'label' => 'Nómina',
                'group' => 'ADMINISTRACIÓN, RH Y NÓMINA',
                'pages' => [
                    'nomina-configuracion.php' => ['label' => 'Configuración de layout', 'menu' => false],
                    'nomina-calculo.php' => ['label' => 'Cálculo de nómina', 'menu' => true],
                    'nomina-exportacion.php' => ['label' => 'Exportación CSV/Excel', 'menu' => true],
                    'nomina-recibo.php' => ['label' => 'Recibo interno', 'menu' => false],
                ],
            ],
            'reportes' => [
                'label' => 'Reportes y KPIs',
                'group' => 'ADMINISTRACIÓN, RH Y NÓMINA',
                'pages' => [
                    'reportes-puntualidad.php' => ['label' => 'Puntualidad y absentismo', 'menu' => true],
                    'reportes-incidencias.php' => ['label' => 'Incidencias por sitio', 'menu' => true],
                    'reportes-extras.php' => ['label' => 'Costo de extras', 'menu' => true],
                    'reportes-exportaciones.php' => ['label' => 'Exportaciones', 'menu' => false],
                ],
            ],
            'usuarios' => [
                'label' => 'Usuarios y roles',
                'group' => 'SISTEMA',
                'pages' => [
                    'usuarios-lista.php' => ['label' => 'Catálogo de usuarios', 'menu' => true],
                    'roles-permisos.php' => ['label' => 'Roles y permisos', 'menu' => true],
                    'usuarios-reset-password.php' => ['label' => 'Reset de contraseñas', 'menu' => true],
                ],
            ],
            'configuracion' => [
                'label' => 'Configuración',
                'group' => 'SISTEMA',
                'pages' => [
                    'configuracion-global.php' => ['label' => 'Parámetros globales', 'menu' => true],
                    'configuracion-catalogos.php' => ['label' => 'Catálogos del sistema', 'menu' => false],
                ],
            ],
        ];
    }
}

if (!function_exists('app_permission_page_exists')) {
    function app_permission_page_exists(string $page): bool
    {
        $page = basename($page);
        foreach (app_permission_catalog() as $section) {
            if (isset($section['pages'][$page])) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('app_permission_page_section')) {
    function app_permission_page_section(?string $page): ?string
    {
        if ($page === null || $page === '') {
            return null;
        }

        $page = basename($page);
        foreach (app_permission_catalog() as $sectionKey => $section) {
            if (isset($section['pages'][$page])) {
                return $sectionKey;
            }
        }

        return null;
    }
}

if (!function_exists('app_permission_page_names')) {
    function app_permission_page_names(): array
    {
        $pages = [];
        foreach (app_permission_catalog() as $section) {
            foreach (array_keys($section['pages']) as $page) {
                $pages[] = $page;
            }
        }

        return $pages;
    }
}

if (!function_exists('app_permission_bool')) {
    function app_permission_bool($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        return in_array(strtolower((string)$value), ['1', 'true', 'on', 'si', 'sí', 'yes'], true);
    }
}

if (!function_exists('app_permission_matrix_all')) {
    function app_permission_matrix_all(): array
    {
        $matrix = ['version' => 1, 'sections' => [], 'pages' => []];
        foreach (app_permission_catalog() as $sectionKey => $section) {
            $matrix['sections'][$sectionKey] = true;
            foreach (array_keys($section['pages']) as $page) {
                $matrix['pages'][$page] = true;
            }
        }

        return $matrix;
    }
}

if (!function_exists('app_permission_matrix_from_pages')) {
    function app_permission_matrix_from_pages(array $allowedPages): array
    {
        $allowedLookup = array_fill_keys(array_map('basename', $allowedPages), true);
        $matrix = ['version' => 1, 'sections' => [], 'pages' => []];

        foreach (app_permission_catalog() as $sectionKey => $section) {
            $sectionAllowed = false;
            foreach (array_keys($section['pages']) as $page) {
                $isAllowed = !empty($allowedLookup[$page]);
                $matrix['pages'][$page] = $isAllowed;
                $sectionAllowed = $sectionAllowed || $isAllowed;
            }
            $matrix['sections'][$sectionKey] = $sectionAllowed;
        }

        return $matrix;
    }
}

if (!function_exists('app_permission_normalize_matrix')) {
    function app_permission_normalize_matrix(?array $matrix): array
    {
        $matrix = is_array($matrix) ? $matrix : [];
        $sourceSections = isset($matrix['sections']) && is_array($matrix['sections']) ? $matrix['sections'] : [];
        $sourcePages = isset($matrix['pages']) && is_array($matrix['pages']) ? $matrix['pages'] : [];

        $normalized = ['version' => 1, 'sections' => [], 'pages' => []];
        foreach (app_permission_catalog() as $sectionKey => $section) {
            $sectionExplicit = array_key_exists($sectionKey, $sourceSections)
                ? app_permission_bool($sourceSections[$sectionKey])
                : null;
            $sectionAllowed = false;

            foreach (array_keys($section['pages']) as $page) {
                if (array_key_exists($page, $sourcePages)) {
                    $pageAllowed = app_permission_bool($sourcePages[$page]);
                } else {
                    $pageAllowed = ($sectionExplicit === true);
                }

                $normalized['pages'][$page] = $pageAllowed;
                $sectionAllowed = $sectionAllowed || $pageAllowed;
            }

            $normalized['sections'][$sectionKey] = $sectionAllowed || ($sectionExplicit === true && empty($section['pages']));
        }

        return $normalized;
    }
}

if (!function_exists('app_permission_decode_matrix')) {
    function app_permission_decode_matrix(?string $json): ?array
    {
        $json = trim((string)$json);
        if ($json === '') {
            return null;
        }

        $decoded = json_decode($json, true);
        if (!is_array($decoded)) {
            return null;
        }

        return $decoded;
    }
}

if (!function_exists('app_legacy_permissions_for_role')) {
    function app_legacy_permissions_for_role(?string $roleName): array
    {
        $role = trim((string)$roleName);
        $role = function_exists('mb_strtoupper') ? mb_strtoupper($role, 'UTF-8') : strtoupper($role);

        if ($role === 'DUEÑO' || $role === 'ADMIN') {
            return app_permission_matrix_all();
        }

        $basePages = [
            'dashboard.php',
            'dashboard-sitio.php',
            'turnos-calendario.php',
            'turnos-asignacion.php',
            'incidencias-bandeja.php',
        ];

        $rolePages = [
            'SUPERVISOR' => [
                'turnos-extras.php',
                'checadas-lista.php',
                'checadas-detalle.php',
                'bitacora-sitios.php',
                'chat-admin.php',
                'chat-moderacion.php',
                'personas-lista.php',
                'personas-ficha.php',
                'sitios-editor-geocerca.php',
                'reportes-puntualidad.php',
                'reportes-incidencias.php',
                'reportes-extras.php',
            ],
            'RH' => [
                'turnos-politicas.php',
                'checadas-lista.php',
                'checadas-detalle.php',
                'bitacora-sitios.php',
                'chat-admin.php',
                'chat-moderacion.php',
                'personas-lista.php',
                'personas-ficha.php',
                'nomina-calculo.php',
                'nomina-exportacion.php',
                'nomina-recibo.php',
                'reportes-puntualidad.php',
                'reportes-incidencias.php',
                'reportes-extras.php',
                'usuarios-lista.php',
                'usuarios-reset-password.php',
                'configuracion-global.php',
            ],
            'NOMINA' => [
                'turnos-extras.php',
                'turnos-politicas.php',
                'checadas-lista.php',
                'checadas-detalle.php',
                'bitacora-sitios.php',
                'chat-admin.php',
                'personas-lista.php',
                'personas-ficha.php',
                'nomina-calculo.php',
                'nomina-exportacion.php',
                'nomina-recibo.php',
                'reportes-puntualidad.php',
                'reportes-incidencias.php',
                'reportes-extras.php',
                'configuracion-global.php',
            ],
            'CLIENTE' => [
                'dashboard-cliente.php',
                'checadas-lista.php',
                'checadas-detalle.php',
                'bitacora-sitios.php',
                'reportes-puntualidad.php',
                'reportes-incidencias.php',
            ],
        ];

        $allowedPages = array_merge($basePages, $rolePages[$role] ?? ['dashboard.php']);

        return app_permission_matrix_from_pages(array_values(array_unique($allowedPages)));
    }
}

if (!function_exists('app_role_permissions')) {
    function app_role_permissions(?mysqli $conexion = null, ?int $roleId = null, ?string $roleName = null, bool $forceRefresh = false): array
    {
        static $cache = [];

        $roleId = $roleId ?: app_current_role_id();
        $roleName = $roleName !== null ? $roleName : app_current_role();
        $cacheRoleName = function_exists('mb_strtoupper') ? mb_strtoupper($roleName, 'UTF-8') : strtoupper($roleName);
        $cacheKey = ($roleId ? 'id:' . $roleId : 'name:' . $cacheRoleName);

        if (!$forceRefresh && isset($cache[$cacheKey])) {
            return $cache[$cacheKey];
        }

        if (app_is_super_role($roleName)) {
            return $cache[$cacheKey] = app_permission_matrix_all();
        }

        $storedMatrix = null;
        if ($conexion instanceof mysqli && $roleId) {
            $sql = "SELECT nombre, matriz_permisos FROM roles WHERE id = ? LIMIT 1";
            if ($stmt = mysqli_prepare($conexion, $sql)) {
                mysqli_stmt_bind_param($stmt, 'i', $roleId);
                if (mysqli_stmt_execute($stmt)) {
                    $result = mysqli_stmt_get_result($stmt);
                    if ($row = mysqli_fetch_assoc($result)) {
                        $roleName = (string)($row['nombre'] ?? $roleName);
                        $storedMatrix = app_permission_decode_matrix($row['matriz_permisos'] ?? null);
                    }
                }
                mysqli_stmt_close($stmt);
            }
        }

        if (is_array($storedMatrix)) {
            return $cache[$cacheKey] = app_permission_normalize_matrix($storedMatrix);
        }

        return $cache[$cacheKey] = app_legacy_permissions_for_role($roleName);
    }
}

if (!function_exists('app_role_can_page')) {
    function app_role_can_page(?mysqli $conexion, string $page, ?int $roleId = null, ?string $roleName = null): bool
    {
        $page = basename($page);
        if (!app_permission_page_exists($page)) {
            return true;
        }

        $roleName = $roleName !== null ? $roleName : app_current_role();
        if (app_is_super_role($roleName)) {
            return true;
        }

        $matrix = app_role_permissions($conexion, $roleId, $roleName);

        return !empty($matrix['pages'][$page]);
    }
}

if (!function_exists('app_role_can_section')) {
    function app_role_can_section(?mysqli $conexion, string $sectionKey, ?int $roleId = null, ?string $roleName = null): bool
    {
        $catalog = app_permission_catalog();
        if (!isset($catalog[$sectionKey])) {
            return true;
        }

        $roleName = $roleName !== null ? $roleName : app_current_role();
        if (app_is_super_role($roleName)) {
            return true;
        }

        $matrix = app_role_permissions($conexion, $roleId, $roleName);

        return !empty($matrix['sections'][$sectionKey]);
    }
}

if (!function_exists('app_permission_counts')) {
    function app_permission_counts(array $matrix): array
    {
        $matrix = app_permission_normalize_matrix($matrix);
        $sectionCount = 0;
        $pageCount = 0;

        foreach ($matrix['sections'] as $allowed) {
            if ($allowed) {
                $sectionCount++;
            }
        }

        foreach ($matrix['pages'] as $allowed) {
            if ($allowed) {
                $pageCount++;
            }
        }

        return ['sections' => $sectionCount, 'pages' => $pageCount];
    }
}

if (!function_exists('app_require_page_permission')) {
    function app_require_page_permission(?string $page = null, ?mysqli $conexionOverride = null): void
    {
        global $conexion;

        $page = basename((string)($page ?: app_current_page_name()));
        if (!app_permission_page_exists($page)) {
            return;
        }

        $db = $conexionOverride instanceof mysqli ? $conexionOverride : (($conexion instanceof mysqli) ? $conexion : null);
        if (app_role_can_page($db, $page)) {
            return;
        }

        http_response_code(403);
        $safePage = app_h($page);
        echo '<!doctype html><html lang="es"><head><meta charset="utf-8"><title>Acceso restringido</title>';
        echo '<link href="../src/bootstrap/css/bootstrap.min.css" rel="stylesheet"></head>';
        echo '<body class="bg-light"><div class="container py-5"><div class="card border-0 shadow-sm mx-auto" style="max-width:560px">';
        echo '<div class="card-body p-4"><h1 class="h4 mb-2">Acceso restringido</h1>';
        echo '<p class="text-muted mb-3">Tu rol no tiene permiso para abrir <strong>' . $safePage . '</strong>.</p>';
        echo '<a class="btn btn-primary" href="dashboard.php">Volver al dashboard</a></div></div></div></body></html>';
        exit;
    }
}

if (!function_exists('app_db_all')) {
    function app_db_all(mysqli $conexion, string $sql): array
    {
        $rows = [];
        $result = mysqli_query($conexion, $sql);
        if (!$result) {
            return $rows;
        }

        while ($row = mysqli_fetch_assoc($result)) {
            $rows[] = $row;
        }

        mysqli_free_result($result);

        return $rows;
    }
}

if (!function_exists('app_db_one')) {
    function app_db_one(mysqli $conexion, string $sql): ?array
    {
        $result = mysqli_query($conexion, $sql);
        if (!$result) {
            return null;
        }

        $row = mysqli_fetch_assoc($result) ?: null;
        mysqli_free_result($result);

        return $row;
    }
}

if (!function_exists('app_db_value')) {
    function app_db_value(mysqli $conexion, string $sql, $default = null)
    {
        $row = app_db_one($conexion, $sql);
        if ($row === null) {
            return $default;
        }

        $values = array_values($row);

        return $values[0] ?? $default;
    }
}

if (!function_exists('app_clean_text')) {
    function app_clean_text($value): string
    {
        return trim((string)$value);
    }
}

if (!function_exists('app_duration_hours')) {
    function app_duration_hours(?string $inicio, ?string $fin): float
    {
        if (!$inicio || !$fin) {
            return 0.0;
        }

        $tsInicio = strtotime($inicio);
        $tsFin = strtotime($fin);

        if ($tsInicio === false || $tsFin === false || $tsFin <= $tsInicio) {
            return 0.0;
        }

        return round(($tsFin - $tsInicio) / 3600, 2);
    }
}

if (!function_exists('app_years_between')) {
    function app_years_between(?string $desde, ?string $hasta = null): int
    {
        if (!$desde) {
            return 0;
        }

        try {
            $start = new DateTime($desde);
            $end = new DateTime($hasta ?: date('Y-m-d'));
        } catch (Exception $exception) {
            return 0;
        }

        if ($end < $start) {
            return 0;
        }

        return max(0, (int)$start->diff($end)->y);
    }
}

if (!function_exists('app_tenure_label')) {
    function app_tenure_label(?string $desde, ?string $hasta = null): string
    {
        if (!$desde) {
            return '-';
        }

        try {
            $start = new DateTime($desde);
            $end = new DateTime($hasta ?: date('Y-m-d'));
        } catch (Exception $exception) {
            return '-';
        }

        if ($end < $start) {
            return 'Menos de 1 mes';
        }

        $diff = $start->diff($end);
        $years = max(0, (int)$diff->y);

        if ($years >= 1) {
            return $years . ' ' . ($years === 1 ? 'año' : 'años');
        }

        $months = max(0, (int)$diff->m);
        if ($months < 1) {
            return 'Menos de 1 mes';
        }

        return $months . ' ' . ($months === 1 ? 'mes' : 'meses');
    }
}

if (!function_exists('app_month_day')) {
    function app_month_day(?string $date): string
    {
        if (!$date) {
            return '';
        }

        $timestamp = strtotime($date);
        if ($timestamp === false) {
            return '';
        }

        return date('m-d', $timestamp);
    }
}

if (!function_exists('app_first_day_of_month')) {
    function app_first_day_of_month(?string $date = null): string
    {
        return date('Y-m-01', strtotime($date ?: date('Y-m-d')));
    }
}

if (!function_exists('app_last_day_of_month')) {
    function app_last_day_of_month(?string $date = null): string
    {
        return date('Y-m-t', strtotime($date ?: date('Y-m-d')));
    }
}

if (!function_exists('app_quincena_label')) {
    function app_quincena_label(string $fechaInicio, string $fechaFin): string
    {
        return app_date($fechaInicio) . ' - ' . app_date($fechaFin);
    }
}
