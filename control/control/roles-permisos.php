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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && app_post('accion') === 'guardar_permisos') {
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
    @media (max-width: 991.98px) {
        .permissions-shell {
            grid-template-columns: 1fr;
        }
        .role-list-card {
            position: static;
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
                        <a class="role-link <?php echo $isActive ? 'active' : ''; ?>" href="roles-permisos.php?role_id=<?php echo $roleId; ?>">
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
                    </div>
                    <div class="text-md-end">
                        <span class="badge bg-primary permission-badge">
                            <?php echo (int)$selectedCounts['sections']; ?> secciones
                        </span>
                        <span class="badge bg-info permission-badge">
                            <?php echo (int)$selectedCounts['pages']; ?> páginas
                        </span>
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
                    <input type="hidden" name="accion" value="guardar_permisos">
                    <input type="hidden" name="role_id" value="<?php echo (int)$selectedRoleId; ?>">

                    <div class="d-flex flex-wrap gap-2 mb-3">
                        <button type="button" class="btn btn-outline-primary btn-sm" id="selectAllPermissions" <?php echo $isSuperRole ? 'disabled' : ''; ?>>
                            Seleccionar todo
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="clearPermissions" <?php echo $isSuperRole ? 'disabled' : ''; ?>>
                            Limpiar
                        </button>
                        <button type="submit" class="btn btn-primary btn-sm ms-auto" <?php echo $isSuperRole ? 'disabled' : ''; ?>>
                            Guardar cambios
                        </button>
                    </div>

                    <div class="row g-3">
                        <?php foreach ($catalog as $sectionKey => $section): ?>
                            <?php
                            $sectionAllowed = !empty($selectedMatrix['sections'][$sectionKey]);
                            $sectionPages = $section['pages'];
                            $sectionSelected = 0;
                            foreach (array_keys($sectionPages) as $page) {
                                if (!empty($selectedMatrix['pages'][$page])) {
                                    $sectionSelected++;
                                }
                            }
                            ?>
                            <div class="col-12 col-xl-6">
                                <div class="permission-section-card p-3 h-100">
                                    <div class="d-flex align-items-start justify-content-between gap-3 mb-2">
                                        <div>
                                            <div class="summary-label"><?php echo app_h($section['group']); ?></div>
                                            <h6 class="mb-1"><?php echo app_h($section['label']); ?></h6>
                                            <div class="permission-muted">
                                                <?php echo $sectionSelected; ?> de <?php echo count($sectionPages); ?> páginas activas
                                            </div>
                                        </div>
                                        <div class="form-check form-switch">
                                            <input
                                                class="form-check-input permission-section"
                                                type="checkbox"
                                                role="switch"
                                                name="sections[<?php echo app_h($sectionKey); ?>]"
                                                value="1"
                                                data-section="<?php echo app_h($sectionKey); ?>"
                                                <?php echo $sectionAllowed ? 'checked' : ''; ?>
                                                <?php echo $isSuperRole ? 'disabled' : ''; ?>
                                            >
                                        </div>
                                    </div>

                                    <div>
                                        <?php foreach ($sectionPages as $page => $pageMeta): ?>
                                            <?php
                                            $pageAllowed = !empty($selectedMatrix['pages'][$page]);
                                            $isMenuPage = !isset($pageMeta['menu']) || (bool)$pageMeta['menu'];
                                            $isSelfProtectionPage = $isOwnRole && $page === 'roles-permisos.php';
                                            ?>
                                            <label class="permission-page-row">
                                                <span>
                                                    <span class="d-block fw-semibold"><?php echo app_h($pageMeta['label']); ?></span>
                                                    <span class="permission-muted">
                                                        <?php echo app_h($page); ?>
                                                        <?php if (!$isMenuPage): ?>
                                                            · página interna
                                                        <?php endif; ?>
                                                        <?php if ($isSelfProtectionPage): ?>
                                                            · protegido
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
                        <button type="submit" class="btn btn-primary" <?php echo $isSuperRole ? 'disabled' : ''; ?>>
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
    const sections = document.querySelectorAll('.permission-section');
    const pages = document.querySelectorAll('.permission-page');
    const selectAll = document.getElementById('selectAllPermissions');
    const clearAll = document.getElementById('clearPermissions');

    function updateSection(sectionKey) {
        const sectionInput = document.querySelector('.permission-section[data-section="' + sectionKey + '"]');
        const pageInputs = Array.from(document.querySelectorAll('.permission-page[data-section="' + sectionKey + '"]'));
        if (!sectionInput || pageInputs.length === 0 || sectionInput.disabled) {
            return;
        }

        sectionInput.checked = pageInputs.some(function (input) { return input.checked; });
    }

    sections.forEach(function (sectionInput) {
        sectionInput.addEventListener('change', function () {
            const sectionKey = sectionInput.dataset.section;
            document.querySelectorAll('.permission-page[data-section="' + sectionKey + '"]').forEach(function (pageInput) {
                if (!pageInput.disabled) {
                    pageInput.checked = sectionInput.checked;
                }
            });
        });
    });

    pages.forEach(function (pageInput) {
        pageInput.addEventListener('change', function () {
            updateSection(pageInput.dataset.section);
        });
    });

    if (selectAll) {
        selectAll.addEventListener('click', function () {
            document.querySelectorAll('.permission-section, .permission-page').forEach(function (input) {
                if (!input.disabled) {
                    input.checked = true;
                }
            });
        });
    }

    if (clearAll) {
        clearAll.addEventListener('click', function () {
            document.querySelectorAll('.permission-section, .permission-page').forEach(function (input) {
                if (!input.disabled) {
                    input.checked = false;
                }
            });
            document.querySelectorAll('.permission-page').forEach(function (input) {
                updateSection(input.dataset.section);
            });
        });
    }
});
</script>
HTML;

app_render_page_end($extraScripts);
