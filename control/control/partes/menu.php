<?php
// menu.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!function_exists('app_permission_catalog')) {
    require_once __DIR__ . '/../lib/helpers.php';
}

$archivo_actual = app_current_page_name();
$menuConexion = (isset($conexion) && $conexion instanceof mysqli) ? $conexion : null;
$catalogoPermisos = app_permission_catalog();

if (!function_exists('menuActivo')) {
    function menuActivo(string $archivo): string
    {
        global $archivo_actual;
        return ($archivo_actual === $archivo) ? 'active' : '';
    }
}

if (!function_exists('menuSectionActive')) {
    function menuSectionActive(string $sectionKey): bool
    {
        global $archivo_actual;
        $currentPage = $archivo_actual ?: app_current_page_name();

        return app_permission_page_section($currentPage) === $sectionKey;
    }
}

if (!function_exists('menuIconSvg')) {
    function menuIconSvg(string $sectionKey): string
    {
        $icons = [
            'dashboard' => '<path d="M3 13h8V3H3v10z"></path><path d="M13 21h8V3h-8v18z"></path><path d="M3 21h8v-6H3v6z"></path>',
            'turnos' => '<rect x="3" y="4" width="18" height="18" rx="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line>',
            'checadas' => '<polyline points="9 11 12 14 22 4"></polyline><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>',
            'incidencias' => '<circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line>',
            'bitacora' => '<path d="M2 3h7a4 4 0 0 1 4 4v14H6a4 4 0 0 1-4-4V3z"></path><path d="M22 3h-7a4 4 0 0 0-4 4v14h7a4 4 0 0 0 4-4V3z"></path>',
            'comunicacion' => '<path d="M21 15a4 4 0 0 1-4 4H7l-4 4V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4z"></path>',
            'personas' => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>',
            'sitios' => '<polygon points="1 6 1 22 8 18 16 22 23 18 23 2 16 6 8 2 1 6"></polygon><line x1="8" y1="2" x2="8" y2="18"></line><line x1="16" y1="6" x2="16" y2="22"></line>',
            'nomina' => '<line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>',
            'reportes' => '<line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line>',
            'usuarios' => '<path d="M20 21v-2a4 4 0 0 0-3-3.87"></path><path d="M4 21v-2a4 4 0 0 1 3-3.87"></path><circle cx="12" cy="7" r="4"></circle>',
            'configuracion' => '<circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 8 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 3.6 15a1.65 1.65 0 0 0-1.51-1H2a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 3.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 8 3.6a1.65 1.65 0 0 0 1-1.51V2a2 2 0 0 1 4 0v.09A1.65 1.65 0 0 0 16 3.6a1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 20.4 9a1.65 1.65 0 0 0 1.51 1H22a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"></path>',
        ];

        $paths = $icons[$sectionKey] ?? '<circle cx="12" cy="12" r="10"></circle>';

        return '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" '
            . 'viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" '
            . 'stroke-linecap="round" stroke-linejoin="round" class="feather">' . $paths . '</svg>';
    }
}

$visibleGroups = [];
foreach ($catalogoPermisos as $sectionKey => $section) {
    $visiblePages = [];
    foreach ($section['pages'] as $page => $pageMeta) {
        $isMenuPage = !isset($pageMeta['menu']) || (bool)$pageMeta['menu'];
        if (!$isMenuPage) {
            continue;
        }

        if (app_role_can_page($menuConexion, $page)) {
            $visiblePages[$page] = $pageMeta;
        }
    }

    if (empty($visiblePages)) {
        continue;
    }

    $groupName = (string)($section['group'] ?? 'SISTEMA');
    $visibleGroups[$groupName][$sectionKey] = $section;
    $visibleGroups[$groupName][$sectionKey]['pages'] = $visiblePages;
}
?>

<ul class="list-unstyled menu-categories ps ps--active-y" id="accordionExample">
    <?php if (empty($visibleGroups)): ?>
        <li class="menu menu-heading">
            <div class="heading">
                <span>Sin módulos asignados</span>
            </div>
        </li>
    <?php endif; ?>

    <?php foreach ($visibleGroups as $groupName => $sections): ?>
        <?php if ($groupName !== 'PRINCIPAL'): ?>
            <li class="menu menu-heading">
                <div class="heading">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                         viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                         class="feather feather-minus">
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    <span><?php echo app_h($groupName); ?></span>
                </div>
            </li>
        <?php endif; ?>

        <?php foreach ($sections as $sectionKey => $section): ?>
            <?php
            $isOpen = menuSectionActive($sectionKey);
            $collapseId = 'menu-' . preg_replace('/[^a-z0-9_-]/i', '-', $sectionKey);
            ?>
            <li class="menu <?php echo $isOpen ? 'active' : ''; ?>">
                <a href="#<?php echo app_h($collapseId); ?>"
                   data-bs-toggle="collapse"
                   aria-expanded="<?php echo $isOpen ? 'true' : 'false'; ?>"
                   class="dropdown-toggle <?php echo $isOpen ? '' : 'collapsed'; ?>">
                    <div class="">
                        <?php echo menuIconSvg($sectionKey); ?>
                        <span><?php echo app_h($section['label']); ?></span>
                    </div>
                    <div>
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                             viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                             class="feather feather-chevron-right">
                            <polyline points="9 18 15 12 9 6"></polyline>
                        </svg>
                    </div>
                </a>
                <ul class="collapse submenu list-unstyled <?php echo $isOpen ? 'show' : ''; ?>"
                    id="<?php echo app_h($collapseId); ?>"
                    data-bs-parent="#accordionExample">
                    <?php foreach ($section['pages'] as $page => $pageMeta): ?>
                        <li class="<?php echo menuActivo($page); ?>">
                            <a href="<?php echo app_h($page); ?>"><?php echo app_h($pageMeta['label']); ?></a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </li>
        <?php endforeach; ?>
    <?php endforeach; ?>

    <li class="menu">
        <a href="logout.php" aria-expanded="false" class="dropdown-toggle">
            <div class="">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                     viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                     class="feather feather-log-out">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                    <polyline points="16 17 21 12 16 7"></polyline>
                    <line x1="21" y1="12" x2="9" y2="12"></line>
                </svg>
                <span>Salir</span>
            </div>
        </a>
    </li>
</ul>
