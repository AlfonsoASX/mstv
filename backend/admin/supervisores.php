<?php
/**
 * ===============================================
 * PANEL ADMIN → GESTIÓN DE SUPERVISORES
 * Archivo: backend/admin/supervisores.php
 * ===============================================
 */

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/helpers.php';

$db = Database::getInstance()->getConnection();

// Obtener supervisores
$stmt = $db->query("
    SELECT u.id, CONCAT(u.nombre,' ',u.apellido) AS nombre, u.telefono, u.email, u.activo,
           s.nombre AS sitio,
           (SELECT COUNT(*) FROM usuarios g WHERE g.supervisor_id = u.id AND g.rol='guardia') AS guardias_a_cargo
    FROM usuarios u
    LEFT JOIN sitios s ON s.id = u.sitio_asignado
    WHERE u.rol='supervisor'
    ORDER BY u.apellido, u.nombre
");
$supervisores = $stmt->fetchAll();
?>

<div class="container">

    <h3 class="mb-4">Supervisores</h3>

    <div class="text-end mb-3">
        <a href="supervisores_nuevo.php" class="btn btn-success">➕ Agregar Supervisor</a>
    </div>

    <div class="card shadow-sm mb-5">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Nombre</th>
                        <th>Teléfono</th>
                        <th>Email</th>
                        <th>Sitio / Zona Asignada</th>
                        <th>Guardias a Cargo</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!$supervisores): ?>
                    <tr><td colspan="7" class="text-center py-4">No hay supervisores registrados.</td></tr>
                <?php else: ?>
                    <?php foreach ($supervisores as $s): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($s['nombre']) ?></strong></td>
                        <td><?= htmlspecialchars($s['telefono']) ?></td>
                        <td><?= htmlspecialchars($s['email']) ?></td>
                        <td><?= $s['sitio'] ?: '<em>No asignado</em>' ?></td>
                        <td>
                            <span class="badge bg-primary"><?= $s['guardias_a_cargo'] ?></span>
                        </td>
                        <td>
                            <span class="badge bg-<?= $s['activo']?'success':'secondary' ?>">
                                <?= $s['activo'] ? 'Activo' : 'Inactivo' ?>
                            </span>
                        </td>
                        <td>
                            <a href="supervisores_ver.php?id=<?= $s['id'] ?>" 
                               class="btn btn-sm btn-outline-info">Ver</a>

                            <a href="supervisores_editar.php?id=<?= $s['id'] ?>" 
                               class="btn btn-sm btn-outline-primary">Editar</a>

                            <a href="monitoreo.php?supervisor=<?= $s['id'] ?>" 
                               class="btn btn-sm btn-outline-success">Monitoreo</a>

                            <a href="supervisores_baja.php?id=<?= $s['id'] ?>" 
                               class="btn btn-sm btn-outline-danger"
                               onclick="return confirm('¿Dar de baja este supervisor?')">
                               Baja
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
