<?php
/**
 * ===============================================
 * PANEL ADMIN → GESTIÓN DE GUARDIAS
 * Archivo: backend/admin/guardias.php
 * ===============================================
 */

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/helpers.php';

$db = Database::getInstance()->getConnection();

// Obtener guardias
$stmt = $db->prepare("
    SELECT g.id, g.nombre, g.apellido, g.telefono, g.activo, g.foto_base,
           (SELECT nombre FROM sitios s WHERE s.id = g.sitio_asignado) AS sitio,
           (SELECT COUNT(*) FROM documentos_persona d WHERE d.usuario_id=g.id AND d.vencimiento <= CURDATE()+INTERVAL 15 DAY) AS docs_vencimiento
    FROM usuarios g
    WHERE g.rol = 'guardia'
    ORDER BY g.apellido, g.nombre
");
$stmt->execute();
$guardias = $stmt->fetchAll();
?>

<div class="container">

    <h3 class="mb-4">Guardias Operativos</h3>

    <div class="mb-3 text-end">
        <a href="guardias_nuevo.php" class="btn btn-success">➕ Agregar Guardia</a>
    </div>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                <tr>
                    <th>Foto</th>
                    <th>Nombre</th>
                    <th>Teléfono</th>
                    <th>Sitio Asignado</th>
                    <th>Docs Vencidos/Próximos</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
                </thead>
                <tbody>

                <?php if (!$guardias): ?>
                    <tr><td colspan="7" class="text-center py-4">No hay guardias registrados.</td></tr>
                <?php else: ?>
                    <?php foreach ($guardias as $g): ?>
                        <tr>
                            <td>
                                <?php if ($g['foto_base']): ?>
                                    <img src="/fotos/<?= htmlspecialchars($g['foto_base']) ?>" 
                                         class="rounded" width="55" height="55">
                                <?php else: ?>
                                    <span class="text-muted">Sin foto</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?= htmlspecialchars($g['nombre'] . ' ' . $g['apellido']) ?></strong>
                            </td>
                            <td><?= htmlspecialchars($g['telefono']) ?></td>
                            <td><?= $g['sitio'] ?: '<em>No asignado</em>' ?></td>

                            <td>
                                <?php if ($g['docs_vencimiento'] > 0): ?>
                                    <span class="badge bg-danger"><?= $g['docs_vencimiento'] ?> alerta</span>
                                <?php else: ?>
                                    <span class="badge bg-success">Ok</span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?php if ($g['activo']): ?>
                                    <span class="badge bg-primary">Activo</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Inactivo</span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <a href="guardias_ver.php?id=<?= $g['id'] ?>" 
                                   class="btn btn-sm btn-outline-info">Ver</a>

                                <a href="guardias_editar.php?id=<?= $g['id'] ?>"
                                   class="btn btn-sm btn-outline-primary">Editar</a>

                                <a href="guardias_docs.php?id=<?= $g['id'] ?>"
                                   class="btn btn-sm btn-outline-warning">Docs</a>

                                <a href="guardias_baja.php?id=<?= $g['id'] ?>"
                                   class="btn btn-sm btn-outline-danger"
                                   onclick="return confirm('¿Dar de baja este guardia?')">Baja</a>
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
