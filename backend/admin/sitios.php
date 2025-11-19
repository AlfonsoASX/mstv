<?php
/**
 * ===============================================
 * PANEL ADMIN → GESTIÓN DE SITIOS
 * Archivo: backend/admin/sitios.php
 * ===============================================
 */

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/helpers.php';

$db = Database::getInstance()->getConnection();

$stmt = $db->query("
    SELECT s.id, s.nombre, s.ubicacion, s.lat, s.lng, s.estado, 
           c.nombre AS cliente,
           (SELECT COUNT(*) FROM usuarios u WHERE u.sitio_asignado = s.id AND rol='guardia') AS guardias_asignados,
           (SELECT GROUP_CONCAT(DISTINCT CONCAT(u.nombre,' ',u.apellido) SEPARATOR ', ')
              FROM usuarios u WHERE u.sitio_asignado=s.id AND rol='supervisor') AS supervisores
    FROM sitios s
    LEFT JOIN clientes c ON c.id = s.cliente_id
    ORDER BY s.nombre
");
$sitios = $stmt->fetchAll();
?>

<div class="container">

    <h3 class="mb-4">Sitios de Servicio / Casetas</h3>

    <div class="mb-3 text-end">
        <a href="sitios_nuevo.php" class="btn btn-success">➕ Registrar Sitio</a>
    </div>

    <div class="card shadow-sm mb-5">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Nombre del Sitio</th>
                        <th>Cliente</th>
                        <th>Ubicación</th>
                        <th>Guardias</th>
                        <th>Supervisor(es)</th>
                        <th>Estado</th>
                        <th>Mapa</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$sitios): ?>
                        <tr><td colspan="8" class="text-center py-4">No hay sitios registrados.</td></tr>
                    <?php else: ?>
                        <?php foreach ($sitios as $s): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($s['nombre']) ?></strong></td>
                            <td><?= $s['cliente'] ? htmlspecialchars($s['cliente']) : '<em>Sin cliente</em>' ?></td>
                            <td><?= htmlspecialchars($s['ubicacion']) ?></td>
                            <td>
                                <span class="badge bg-primary"><?= $s['guardias_asignados'] ?></span>
                            </td>
                            <td><?= $s['supervisores'] ?: '<em>No asignado</em>' ?></td>
                            <td>
                                <span class="badge bg-<?= $s['estado']=='activo'?'success':'secondary' ?>">
                                    <?= ucfirst($s['estado']) ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($s['lat'] && $s['lng']): ?>
                                    <a href="https://www.google.com/maps?q=<?= $s['lat'] ?>,<?= $s['lng'] ?>" 
                                       target="_blank" class="btn btn-sm btn-outline-info">
                                        Ver Mapa
                                    </a>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="sitios_ver.php?id=<?= $s['id'] ?>" 
                                   class="btn btn-sm btn-outline-primary">Ver</a>

                                <a href="sitios_editar.php?id=<?= $s['id'] ?>" 
                                   class="btn btn-sm btn-outline-warning">Editar</a>

                                <a href="geocercas.php?sitio=<?= $s['id'] ?>"
                                   class="btn btn-sm btn-outline-success">Geocerca</a>

                                <a href="reporte_servicio.php?id=<?= $s['id'] ?>" 
                                   class="btn btn-sm btn-outline-info">Reportes</a>
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
