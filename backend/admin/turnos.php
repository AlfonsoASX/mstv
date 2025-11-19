<?php
/**
 * ===============================================
 * PANEL ADMIN → GESTIÓN DE TURNOS
 * Archivo: backend/admin/turnos.php
 * ===============================================
 */

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/helpers.php';

$db = Database::getInstance()->getConnection();

// Obtener turnos con guardia, sitio y tipo
$stmt = $db->query("
    SELECT t.id, t.fecha, t.hora_inicio, t.hora_fin, t.tipo, 
           CONCAT(u.nombre,' ',u.apellido) AS guardia,
           s.nombre AS sitio,
           t.estado
    FROM turnos t
    LEFT JOIN usuarios u ON u.id = t.guardia_id
    LEFT JOIN sitios s   ON s.id = t.sitio_id
    ORDER BY t.fecha DESC, t.hora_inicio ASC
    LIMIT 200
");
$turnos = $stmt->fetchAll();
?>

<div class="container">

    <h3 class="mb-4">Gestión de Turnos y Jornadas</h3>

    <div class="mb-3 text-end">
        <a href="turnos_nuevo.php" class="btn btn-success">➕ Programar Turno</a>
    </div>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Fecha</th>
                        <th>Horario</th>
                        <th>Guardia</th>
                        <th>Sitio</th>
                        <th>Tipo</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!$turnos): ?>
                    <tr><td colspan="7" class="text-center py-4">No hay turnos registrados.</td></tr>
                <?php else: ?>
                    <?php foreach ($turnos as $t): ?>
                    <tr>
                        <td><?= date('d/m/Y', strtotime($t['fecha'])) ?></td>
                        <td><?= substr($t['hora_inicio'],0,5) ?> - <?= substr($t['hora_fin'],0,5) ?></td>
                        <td><?= htmlspecialchars($t['guardia']) ?></td>
                        <td><?= htmlspecialchars($t['sitio']) ?></td>
                        <td>
                            <span class="badge bg-<?= $t['tipo']=='extra'?'warning':'primary' ?>">
                                <?= ucfirst($t['tipo']) ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge bg-<?= 
                                $t['estado']=='programado'?'info':
                                ($t['estado']=='activo'?'success':
                                ($t['estado']=='cerrado'?'secondary':'danger'))
                            ?>">
                                <?= ucfirst($t['estado']) ?>
                            </span>
                        </td>
                        <td>
                            <a href="turnos_ver.php?id=<?= $t['id'] ?>" 
                               class="btn btn-sm btn-outline-primary">Ver</a>
                            <a href="turnos_editar.php?id=<?= $t['id'] ?>" 
                               class="btn btn-sm btn-outline-warning">Editar</a>
                            <a href="turnos_reemplazar.php?id=<?= $t['id'] ?>" 
                               class="btn btn-sm btn-outline-secondary">Reemplazar</a>
                            <a href="turnos_cancelar.php?id=<?= $t['id'] ?>" 
                               class="btn btn-sm btn-outline-danger"
                               onclick="return confirm('¿Cancelar este turno?')">Cancelar</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="text-end mt-3 mb-5">
        <a href="export_turnos_excel.php" class="btn btn-outline-success btn-sm">Exportar Excel</a>
        <a href="export_turnos_pdf.php" class="btn btn-outline-danger btn-sm">Exportar PDF</a>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
