<?php

require_once __DIR__ . '/lib/app.php';
require_once __DIR__ . '/lib/ui.php';

app_require_session();
app_require_page_permission('roles-permisos.php');

$catalog = app_permission_catalog();
$messages = ['success' => '', 'error' => '', 'warning' => ''];

$fetchRoles = static function (mysqli $conexion): array {
    return app_db_all(
        $conexion,
        "SELECT id, nombre, descripcion, matriz_permisos
         FROM roles
         ORDER BY FIELD(nombre, 'DUEÑO', 'ADMIN', 'RH', 'NOMINA', 'SUPERVISOR', 'CLIENTE'), nombre"
    );
};

$roles = $fetchRoles($conexion);
$selectedRoleId = isset($_POST['role_id']) ? (int)$_POST['role_id'] : (int)app_get('role_id', 0);
if ($selectedRoleId <= 0 && !empty($roles)) {
    $selectedRoleId = (int)$roles[0]['id'];
}

$postedAction = (string)app_post('accion', '');
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($postedAction, ['guardar_permisos', 'restaurar_heredados'], true)) {
    $selectedRoleId = (int)app_post('role_id', 0);
    $selectedRole = null;
    foreach ($roles as $role) {
        if ((int)$role['id'] === $selectedRoleId) {
            $selectedRole = $role;
            break;
        }
    }

    if ($selectedRole === null) {
        $messages['error'] = 'No se encontró el rol seleccionado.';
    } elseif (app_is_super_role($selectedRole['nombre'])) {
        $messages['warning'] = 'El rol DUEÑO conserva acceso total y no se puede modificar.';
    } elseif ($postedAction === 'restaurar_heredados') {
        if ($selectedRoleId === app_current_role_id()) {
            $messages['warning'] = 'No restauré tu propio rol para evitar que pierdas acceso a Roles y permisos.';
        } else {
            $sql = "UPDATE roles SET matriz_permisos = NULL WHERE id = ?";
            if ($stmt = mysqli_prepare($conexion, $sql)) {
                mysqli_stmt_bind_param($stmt, 'i', $selectedRoleId);
                if (mysqli_stmt_execute($stmt)) {
                    $messages['success'] = 'Permisos heredados restaurados correctamente.';
                    app_role_permissions($conexion, $selectedRoleId, (string)$selectedRole['nombre'], true);
                    $roles = $fetchRoles($conexion);
                } else {
                    $messages['error'] = 'No fue posible restaurar los permisos heredados.';
                }
                mysqli_stmt_close($stmt);
            } else {
                $messages['error'] = 'No fue posible preparar la restauración de permisos.';
            }
        }
    } else {
        $postedSections = is_array(app_post('sections', [])) ? app_post('sections', []) : [];
        $postedPages = is_array(app_post('pages', [])) ? app_post('pages', []) : [];
        $matrix = ['version' => 1, 'sections' => [], 'pages' => []];

        foreach ($catalog as $sectionKey => $section) {
            $sectionHasPages = false;
            $sectionRequested = array_key_exists($sectionKey, $postedSections);

            foreach (array_keys($section['pages']) as $page) {
                $pageAllowed = $sectionRequested && array_key_exists($page, $postedPages);
                $matrix['pages'][$page] = $pageAllowed;
                $sectionHasPages = $sectionHasPages || $pageAllowed;
            }

            $matrix['sections'][$sectionKey] = $sectionHasPages;
        }

        if ($selectedRoleId === app_current_role_id()) {
            $matrix['pages']['roles-permisos.php'] = true;
            $matrix['sections']['usuarios'] = true;
            $messages['warning'] = 'Conservé activo tu acceso a Roles y permisos para evitar que tu usuario quede bloqueado.';
        }

        $json = json_encode($matrix, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        if ($json === false) {
            $messages['error'] = 'No fue posible preparar la matriz de permisos.';
        } else {
            $sql = "UPDATE roles SET matriz_permisos = ? WHERE id = ?";
            if ($stmt = mysqli_prepare($conexion, $sql)) {
                mysqli_stmt_bind_param($stmt, 'si', $json, $selectedRoleId);
                if (mysqli_stmt_execute($stmt)) {
                    $messages['success'] = 'Permisos guardados correctamente.';
                    app_role_permissions($conexion, $selectedRoleId, (string)$selectedRole['nombre'], true);
                    $roles = $fetchRoles($conexion);
                } else {
                    $messages['error'] = 'No fue posible guardar los permisos.';
                }
                mysqli_stmt_close($stmt);
            } else {
                $messages['error'] = 'No fue posible preparar el guardado de permisos.';
            }
        }
    }
}

$selectedRole = null;
foreach ($roles as $role) {
    if ((int)$role['id'] === $selectedRoleId) {
        $selectedRole = $role;
        break;
    }
}
if ($selectedRole === null && !empty($roles)) {
    $selectedRole = $roles[0];
    $selectedRoleId = (int)$selectedRole['id'];
}

$selectedMatrix = $selectedRole
    ? app_role_permissions($conexion, (int)$selectedRole['id'], (string)$selectedRole['nombre'])
    : app_permission_matrix_from_pages([]);
$selectedCounts = app_permission_counts($selectedMatrix);
$isSuperRole = $selectedRole ? app_is_super_role($selectedRole['nombre']) : false;
$isOwnRole = $selectedRole && (int)$selectedRole['id'] === app_current_role_id();
$selectedHasCustomMatrix = $selectedRole && trim((string)($selectedRole['matriz_permisos'] ?? '')) !== '';

$extraHead = <<<HTML
<style>
    .permissions-shell {
        display: grid;
        grid-template-columns: minmax(240px, 300px) minmax(0, 1fr);
        gap: 20px;
    }
    .role-list-card,
    .permission-panel,
    .permission-section-card {
        border: 1px solid rgba(70, 84, 101, 0.10);
        border-radius: 20px;
        box-shadow: 0 14px 34px rgba(31, 45, 61, 0.08);
    }
    .role-list-card {
        position: sticky;
        top: 90px;
    }
    .role-link {
        border: 1px solid transparent;
        border-radius: 14px;
        color: #344054;
        display: block;
        padding: 12px;
        text-decoration: none;
        transition: all .18s ease;
    }
    .role-link:hover,
    .role-link.active {
        background: #eef4ff;
        border-color: #c7d7fe;
        color: #1d4ed8;
        transform: translateY(-1px);
    }
    .permission-section-card {
        background: linear-gradient(180deg, #ffffff 0%, #fbfcff 100%);
    }
    .permission-page-row {
        align-items: center;
        border-top: 1px solid #eef2f7;
        display: flex;
        justify-content: space-between;
        gap: 12px;
        padding: 10px 0;
    }
    .permission-page-row:first-child {
        border-top: 0;
    }
    .permission-muted {
        color: #667085;
        font-size: .84rem;
    }
    .permission-badge {
        border-radius: 999px;
        font-size: .74rem;
        padding: .35rem .55rem;
    }
    .permission-toolbar {
        background: #f8fbff;
        border: 1px solid #e6edf7;
        border-radius: 18px;
        padding: 14px;
    }
    .permission-search {
        border-radius: 999px;
        min-height: 40px;
    }
    .permission-help-grid {
        display: grid;
        gap: 12px;
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }
    .permission-help-item {
        background: #f8fafc;
        border: 1px solid #e6edf7;
        border-radius: 16px;
        padding: 12px;
    }
    .permission-section-card {
        transition: border-color .18s ease, box-shadow .18s ease, opacity .18s ease;
    }
    .permission-section-card.permission-complete {
        border-color: rgba(25, 135, 84, .32);
    }
    .permission-section-card.permission-partial {
        border-color: rgba(255, 193, 7, .45);
    }
    .permission-section-card.permission-empty {
        opacity: .86;
    }
    .permission-section-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
    }
    .permission-hidden {
        display: none !important;
    }
    .permission-no-results {
        display: none;
    }
    .permission-no-results.visible {
        display: block;
    }
    @media (max-width: 991.98px) {
        .permissions-shell {
            grid-template-columns: 1fr;
        }
        .role-list-card {
            position: static;
        }
        .permission-help-grid {
            grid-template-columns: 1fr;
        }
    }
</style>
HTML;

app_render_page_start(
    'Roles y permisos',
    'Roles y permisos',
    'Define qué secciones y páginas puede ver cada rol.',
    $extraHead
);

app_render_alerts($messages);
?>

<?php if (empty($roles)): ?>
    <div class="alert alert-warning">
        No hay roles registrados para configurar permisos.
    </div>
<?php else: ?>
    <div class="permissions-shell">
        <aside class="card role-list-card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div>
                        <div class="summary-label">Roles</div>
                        <h5 class="mb-0">Matriz de acceso</h5>
                    </div>
                    <span class="badge bg-primary permission-badge"><?php echo count($roles); ?></span>
                </div>
                <input
                    type="search"
                    class="form-control form-control-sm permission-search mb-3"
                    id="roleSearch"
                    placeholder="Buscar rol..."
                    aria-label="Buscar rol"
                >

                <div class="d-grid gap-2">
                    <?php foreach ($roles as $role): ?>
                        <?php
                        $roleId = (int)$role['id'];
                        $roleName = (string)$role['nombre'];
                        $roleMatrix = app_role_permissions($conexion, $roleId, $roleName);
                        $roleCounts = app_permission_counts($roleMatrix);
                        $hasCustomMatrix = trim((string)($role['matriz_permisos'] ?? '')) !== '';
                        $isActive = $roleId === $selectedRoleId;
                        ?>
                        <a
                            class="role-link <?php echo $isActive ? 'active' : ''; ?>"
                            href="roles-permisos.php?role_id=<?php echo $roleId; ?>"
                            data-role-card
                            data-search-text="<?php echo app_h($roleName . ' ' . ($role['descripcion'] ?? '')); ?>"
                        >
                            <div class="d-flex align-items-center justify-content-between">
                                <strong><?php echo app_h($roleName); ?></strong>
                                <?php if (app_is_super_role($roleName)): ?>
                                    <span class="badge bg-dark permission-badge">Total</span>
                                <?php elseif ($hasCustomMatrix): ?>
                                    <span class="badge bg-success permission-badge">Personalizado</span>
                                <?php else: ?>
                                    <span class="badge bg-light text-dark permission-badge">Heredado</span>
                                <?php endif; ?>
                            </div>
                            <div class="permission-muted mt-1">
                                <?php echo (int)$roleCounts['sections']; ?> secciones · <?php echo (int)$roleCounts['pages']; ?> páginas
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </aside>

        <section class="card permission-panel">
            <div class="card-body">
                <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-4">
                    <div>
                        <div class="summary-label">Rol seleccionado</div>
                        <h4 class="mb-1"><?php echo app_h($selectedRole['nombre'] ?? ''); ?></h4>
                        <p class="text-muted mb-0">
                            <?php echo app_h($selectedRole['descripcion'] ?? 'Sin descripción'); ?>
                        </p>
                        <div class="mt-2">
                            <?php if ($isSuperRole): ?>
                                <span class="badge bg-dark permission-badge">Acceso total fijo</span>
                            <?php elseif ($selectedHasCustomMatrix): ?>
                                <span class="badge bg-success permission-badge">Permisos personalizados</span>
                            <?php else: ?>
                                <span class="badge bg-light text-dark permission-badge">Permisos heredados</span>
                            <?php endif; ?>
                            <?php if ($isOwnRole): ?>
                                <span class="badge bg-warning text-dark permission-badge">Tu rol actual</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="text-md-end">
                        <span class="badge bg-primary permission-badge" id="totalSectionsBadge">
                            <?php echo (int)$selectedCounts['sections']; ?> secciones
                        </span>
                        <span class="badge bg-info permission-badge" id="totalPagesBadge">
                            <?php echo (int)$selectedCounts['pages']; ?> páginas
                        </span>
                    </div>
                </div>

                <div class="permission-help-grid mb-4">
                    <div class="permission-help-item">
                        <strong>Sección visible</strong>
                        <div class="permission-muted">Aparece en el menú cuando al menos una página visible está permitida.</div>
                    </div>
                    <div class="permission-help-item">
                        <strong>Página permitida</strong>
                        <div class="permission-muted">Permite abrir la pantalla por menú o por URL directa.</div>
                    </div>
                    <div class="permission-help-item">
                        <strong>Página interna</strong>
                        <div class="permission-muted">No aparece en menú, pero debe permitirse para fichas, recibos o detalles.</div>
                    </div>
                </div>

                <?php if ($isSuperRole): ?>
                    <div class="alert alert-info">
                        El rol DUEÑO tiene acceso total fijo. Sus permisos se muestran como referencia y no son editables.
                    </div>
                <?php elseif ($isOwnRole): ?>
                    <div class="alert alert-warning">
                        Estás editando tu propio rol. El acceso a esta pantalla se mantendrá activo automáticamente.
                    </div>
                <?php endif; ?>

                <form method="post" id="permissionsForm">
                    <input type="hidden" name="role_id" value="<?php echo (int)$selectedRoleId; ?>">

                    <div class="permission-toolbar mb-3">
                        <div class="row g-2 align-items-center">
                            <div class="col-12 col-lg">
                                <input
                                    type="search"
                                    class="form-control permission-search"
                                    id="permissionSearch"
                                    placeholder="Buscar sección, página o archivo PHP..."
                                    aria-label="Buscar permisos"
                                >
                            </div>
                            <div class="col-12 col-lg-auto d-flex flex-wrap gap-2">
                        <button type="button" class="btn btn-outline-primary btn-sm" id="selectAllPermissions" <?php echo $isSuperRole ? 'disabled' : ''; ?>>
                            Seleccionar todo
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="clearPermissions" <?php echo $isSuperRole ? 'disabled' : ''; ?>>
                            Limpiar
                        </button>
                                <button
                                    type="submit"
                                    name="accion"
                                    value="restaurar_heredados"
                                    class="btn btn-outline-warning btn-sm"
                                    id="restoreInherited"
                                    <?php echo ($isSuperRole || $isOwnRole || !$selectedHasCustomMatrix) ? 'disabled' : ''; ?>
                                >
                                    Restaurar heredados
                                </button>
                        <button type="submit" name="accion" value="guardar_permisos" class="btn btn-primary btn-sm" <?php echo $isSuperRole ? 'disabled' : ''; ?>>
                            Guardar cambios
                        </button>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-warning d-none" id="permissionChangeWarning">
                        Revisa los cambios antes de guardar. Quitar páginas puede ocultar menús y bloquear acceso directo por URL.
                    </div>

                    <div class="row g-3" id="permissionsGrid">
                        <?php foreach ($catalog as $sectionKey => $section): ?>
                            <?php
                            $sectionAllowed = !empty($selectedMatrix['sections'][$sectionKey]);
                            $sectionPages = $section['pages'];
                            $sectionSelected = 0;
                            $sectionSearchText = (string)$section['label'] . ' ' . (string)$section['group'];
                            foreach (array_keys($sectionPages) as $page) {
                                if (!empty($selectedMatrix['pages'][$page])) {
                                    $sectionSelected++;
                                }
                                $sectionSearchText .= ' ' . $page . ' ' . (string)($sectionPages[$page]['label'] ?? '');
                            }
                            ?>
                            <div
                                class="col-12 col-xl-6"
                                data-permission-section-card
                                data-section="<?php echo app_h($sectionKey); ?>"
                                data-search-text="<?php echo app_h($sectionSearchText); ?>"
                            >
                                <div class="permission-section-card p-3 h-100">
                                    <div class="d-flex align-items-start justify-content-between gap-3 mb-2">
                                        <div>
                                            <div class="summary-label"><?php echo app_h($section['group']); ?></div>
                                            <h6 class="mb-1"><?php echo app_h($section['label']); ?></h6>
                                            <div class="permission-muted" data-section-summary="<?php echo app_h($sectionKey); ?>">
                                                <span data-section-count="<?php echo app_h($sectionKey); ?>"><?php echo $sectionSelected; ?></span>
                                                de
                                                <span data-section-total="<?php echo app_h($sectionKey); ?>"><?php echo count($sectionPages); ?></span>
                                                páginas activas
                                            </div>
                                        </div>
                                        <div class="text-end">
                                            <span class="badge permission-badge mb-2" data-section-status="<?php echo app_h($sectionKey); ?>">Sin permisos</span>
                                            <div class="form-check form-switch d-flex justify-content-end">
                                                <input
                                                    class="form-check-input permission-section"
                                                    type="checkbox"
                                                    role="switch"
                                                    name="sections[<?php echo app_h($sectionKey); ?>]"
                                                    value="1"
                                                    data-section="<?php echo app_h($sectionKey); ?>"
                                                    data-total="<?php echo count($sectionPages); ?>"
                                                    <?php echo $sectionAllowed ? 'checked' : ''; ?>
                                                    <?php echo $isSuperRole ? 'disabled' : ''; ?>
                                                >
                                            </div>
                                        </div>
                                    </div>

                                    <div class="permission-section-actions mb-2">
                                        <button type="button" class="btn btn-light btn-sm" data-section-select="<?php echo app_h($sectionKey); ?>" <?php echo $isSuperRole ? 'disabled' : ''; ?>>
                                            Seleccionar sección
                                        </button>
                                        <button type="button" class="btn btn-light btn-sm" data-section-clear="<?php echo app_h($sectionKey); ?>" <?php echo $isSuperRole ? 'disabled' : ''; ?>>
                                            Limpiar sección
                                        </button>
                                        <button type="button" class="btn btn-light btn-sm" data-section-toggle="<?php echo app_h($sectionKey); ?>">
                                            Contraer
                                        </button>
                                    </div>

                                    <div data-section-pages="<?php echo app_h($sectionKey); ?>">
                                        <?php foreach ($sectionPages as $page => $pageMeta): ?>
                                            <?php
                                            $pageAllowed = !empty($selectedMatrix['pages'][$page]);
                                            $isMenuPage = !isset($pageMeta['menu']) || (bool)$pageMeta['menu'];
                                            $isSelfProtectionPage = $isOwnRole && $page === 'roles-permisos.php';
                                            ?>
                                            <label
                                                class="permission-page-row"
                                                data-page-row
                                                data-search-text="<?php echo app_h($page . ' ' . ($pageMeta['label'] ?? '') . ' ' . $section['label'] . ' ' . $section['group']); ?>"
                                            >
                                                <span>
                                                    <span class="d-block fw-semibold"><?php echo app_h($pageMeta['label']); ?></span>
                                                    <span class="permission-muted">
                                                        <?php echo app_h($page); ?>
                                                        <?php if (!$isMenuPage): ?>
                                                            · <span class="badge bg-light text-dark permission-badge">página interna</span>
                                                        <?php endif; ?>
                                                        <?php if ($isSelfProtectionPage): ?>
                                                            · <span class="badge bg-warning text-dark permission-badge">protegido</span>
                                                        <?php endif; ?>
                                                    </span>
                                                </span>
                                                <input
                                                    class="form-check-input permission-page"
                                                    type="checkbox"
                                                    name="pages[<?php echo app_h($page); ?>]"
                                                    value="1"
                                                    data-section="<?php echo app_h($sectionKey); ?>"
                                                    <?php echo $pageAllowed ? 'checked' : ''; ?>
                                                    <?php echo ($isSuperRole || $isSelfProtectionPage) ? 'disabled' : ''; ?>
                                                >
                                                <?php if ($isSelfProtectionPage): ?>
                                                    <input type="hidden" name="pages[roles-permisos.php]" value="1">
                                                <?php endif; ?>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="d-flex justify-content-end mt-4">
                        <button type="submit" name="accion" value="guardar_permisos" class="btn btn-primary" <?php echo $isSuperRole ? 'disabled' : ''; ?>>
                            Guardar cambios
                        </button>
                    </div>
                </form>
            </div>
        </section>
    </div>
<?php endif; ?>

<?php
$extraScripts = <<<HTML
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('permissionsForm');
    const sections = Array.from(document.querySelectorAll('.permission-section'));
    const pages = Array.from(document.querySelectorAll('.permission-page'));
    const selectAll = document.getElementById('selectAllPermissions');
    const clearAll = document.getElementById('clearPermissions');
    const restoreInherited = document.getElementById('restoreInherited');
    const permissionSearch = document.getElementById('permissionSearch');
    const roleSearch = document.getElementById('roleSearch');
    const warning = document.getElementById('permissionChangeWarning');
    const totalSectionsBadge = document.getElementById('totalSectionsBadge');
    const totalPagesBadge = document.getElementById('totalPagesBadge');
    const initialPageStates = new Map();

    pages.forEach(function (pageInput) {
        initialPageStates.set(pageInput.name, pageInput.checked);
    });

    function normalize(value) {
        return (value || '')
            .toString()
            .normalize('NFD')
            .replace(/[\\u0300-\\u036f]/g, '')
            .toLowerCase();
    }

    function getPagesForSection(sectionKey) {
        return Array.from(document.querySelectorAll('.permission-page[data-section="' + sectionKey + '"]'));
    }

    function updateTotals() {
        const activeSections = sections.filter(function (sectionInput) { return sectionInput.checked; }).length;
        const activePages = pages.filter(function (pageInput) { return pageInput.checked; }).length;

        if (totalSectionsBadge) {
            totalSectionsBadge.textContent = activeSections + ' secciones';
        }
        if (totalPagesBadge) {
            totalPagesBadge.textContent = activePages + ' páginas';
        }
    }

    function markChanged() {
        const changed = pages.some(function (pageInput) {
            return initialPageStates.get(pageInput.name) !== pageInput.checked;
        });
        if (warning) {
            warning.classList.toggle('d-none', !changed);
        }
    }

    function updateSection(sectionKey) {
        const sectionInput = document.querySelector('.permission-section[data-section="' + sectionKey + '"]');
        const pageInputs = getPagesForSection(sectionKey);
        const countTarget = document.querySelector('[data-section-count="' + sectionKey + '"]');
        const statusTarget = document.querySelector('[data-section-status="' + sectionKey + '"]');
        const card = document.querySelector('[data-permission-section-card][data-section="' + sectionKey + '"] .permission-section-card');
        if (!sectionInput || pageInputs.length === 0) {
            return;
        }

        const checkedCount = pageInputs.filter(function (input) { return input.checked; }).length;
        sectionInput.checked = checkedCount > 0;
        sectionInput.indeterminate = checkedCount > 0 && checkedCount < pageInputs.length;

        if (countTarget) {
            countTarget.textContent = checkedCount;
        }

        if (statusTarget) {
            statusTarget.className = 'badge permission-badge mb-2';
            if (checkedCount === pageInputs.length) {
                statusTarget.classList.add('bg-success');
                statusTarget.textContent = 'Completo';
            } else if (checkedCount > 0) {
                statusTarget.classList.add('bg-warning', 'text-dark');
                statusTarget.textContent = 'Parcial';
            } else {
                statusTarget.classList.add('bg-light', 'text-dark');
                statusTarget.textContent = 'Sin permisos';
            }
        }

        if (card) {
            card.classList.toggle('permission-complete', checkedCount === pageInputs.length);
            card.classList.toggle('permission-partial', checkedCount > 0 && checkedCount < pageInputs.length);
            card.classList.toggle('permission-empty', checkedCount === 0);
        }

        updateTotals();
    }

    function updateAllSections() {
        sections.forEach(function (sectionInput) {
            updateSection(sectionInput.dataset.section);
        });
        markChanged();
    }

    sections.forEach(function (sectionInput) {
        sectionInput.addEventListener('change', function () {
            const sectionKey = sectionInput.dataset.section;
            getPagesForSection(sectionKey).forEach(function (pageInput) {
                if (!pageInput.disabled) {
                    pageInput.checked = sectionInput.checked;
                }
            });
            updateSection(sectionKey);
            markChanged();
        });
    });

    pages.forEach(function (pageInput) {
        pageInput.addEventListener('change', function () {
            updateSection(pageInput.dataset.section);
            markChanged();
        });
    });

    if (selectAll) {
        selectAll.addEventListener('click', function () {
            document.querySelectorAll('.permission-section, .permission-page').forEach(function (input) {
                if (!input.disabled) {
                    input.checked = true;
                }
            });
            updateAllSections();
        });
    }

    if (clearAll) {
        clearAll.addEventListener('click', function () {
            if (!window.confirm('¿Limpiar todos los permisos editables de este rol? Esta acción puede ocultar menús y bloquear accesos.')) {
                return;
            }
            document.querySelectorAll('.permission-section, .permission-page').forEach(function (input) {
                if (!input.disabled) {
                    input.checked = false;
                }
            });
            updateAllSections();
        });
    }

    document.querySelectorAll('[data-section-select]').forEach(function (button) {
        button.addEventListener('click', function () {
            const sectionKey = button.dataset.sectionSelect;
            getPagesForSection(sectionKey).forEach(function (pageInput) {
                if (!pageInput.disabled) {
                    pageInput.checked = true;
                }
            });
            updateSection(sectionKey);
            markChanged();
        });
    });

    document.querySelectorAll('[data-section-clear]').forEach(function (button) {
        button.addEventListener('click', function () {
            const sectionKey = button.dataset.sectionClear;
            getPagesForSection(sectionKey).forEach(function (pageInput) {
                if (!pageInput.disabled) {
                    pageInput.checked = false;
                }
            });
            updateSection(sectionKey);
            markChanged();
        });
    });

    document.querySelectorAll('[data-section-toggle]').forEach(function (button) {
        button.addEventListener('click', function () {
            const sectionKey = button.dataset.sectionToggle;
            const container = document.querySelector('[data-section-pages="' + sectionKey + '"]');
            if (!container) {
                return;
            }
            const isHidden = container.classList.toggle('permission-hidden');
            button.textContent = isHidden ? 'Expandir' : 'Contraer';
        });
    });

    if (permissionSearch) {
        permissionSearch.addEventListener('input', function () {
            const term = normalize(permissionSearch.value.trim());
            let visibleSections = 0;

            document.querySelectorAll('[data-permission-section-card]').forEach(function (sectionCard) {
                const sectionMatches = normalize(sectionCard.dataset.searchText).includes(term);
                let visibleRows = 0;

                sectionCard.querySelectorAll('[data-page-row]').forEach(function (row) {
                    const rowMatches = !term || sectionMatches || normalize(row.dataset.searchText).includes(term);
                    row.classList.toggle('permission-hidden', !rowMatches);
                    if (rowMatches) {
                        visibleRows += 1;
                    }
                });

                const showSection = !term || sectionMatches || visibleRows > 0;
                sectionCard.classList.toggle('permission-hidden', !showSection);
                if (showSection) {
                    visibleSections += 1;
                }
            });

            let empty = document.getElementById('permissionNoResults');
            if (!empty) {
                empty = document.createElement('div');
                empty.id = 'permissionNoResults';
                empty.className = 'alert alert-warning permission-no-results';
                empty.textContent = 'No se encontraron permisos con ese criterio.';
                document.getElementById('permissionsGrid').after(empty);
            }
            empty.classList.toggle('visible', visibleSections === 0);
        });
    }

    if (roleSearch) {
        roleSearch.addEventListener('input', function () {
            const term = normalize(roleSearch.value.trim());
            document.querySelectorAll('[data-role-card]').forEach(function (roleCard) {
                const matches = !term || normalize(roleCard.dataset.searchText).includes(term);
                roleCard.classList.toggle('permission-hidden', !matches);
            });
        });
    }

    if (restoreInherited) {
        restoreInherited.addEventListener('click', function (event) {
            if (!window.confirm('¿Restaurar permisos heredados para este rol? Se eliminará la matriz personalizada guardada.')) {
                event.preventDefault();
            }
        });
    }

    if (form) {
        form.addEventListener('submit', function (event) {
            const submitter = event.submitter;
            if (submitter && submitter.value === 'restaurar_heredados') {
                return;
            }

            const activePages = pages.filter(function (pageInput) { return pageInput.checked; }).length;
            const initiallyActivePages = Array.from(initialPageStates.values()).filter(Boolean).length;
            if (activePages === 0 && !window.confirm('Este rol quedará sin páginas permitidas. ¿Deseas guardar de todos modos?')) {
                event.preventDefault();
                return;
            }
            if (activePages < initiallyActivePages && !window.confirm('Estás reduciendo permisos del rol. ¿Confirmas guardar los cambios?')) {
                event.preventDefault();
            }
        });
    }

    updateAllSections();
});
</script>
HTML;

app_render_page_end($extraScripts);
