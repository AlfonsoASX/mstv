<?php
/**
 * ===============================================
 * PANEL → CAPACITACIÓN / ONBOARDING
 * Archivo: backend/admin/capacitacion.php
 * ===============================================
 */

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/helpers.php';

$db = Database::getInstance()->getConnection();

// Obtener videos
$stmt = $db->prepare("
    SELECT v.id, v.titulo, v.descripcion, v.archivo, v.perfil_asignado, v.created_at,
           COUNT(c.id) AS vistos
    FROM videos_capacitacion v
    LEFT JOIN capacitacion_completada c ON c.video_id = v.id
    GROUP BY v.id
    ORDER BY v.created_at DESC
");
$stmt->execute();
$videos = $stmt->fetchAll();
?>

<div class="container">

    <h3 class="mb-4">Centro de Capacitación / Onboarding</h3>

    <!-- SUBIR NUEVO VIDEO -->
    <div class="card mb-4 shadow-sm">
        <div class="card-header bg-primary text-white">Subir nuevo video</div>
        <div class="card-body">
            <form action="upload_video.php" method="POST" enctype="multipart/form-data">
                <div class="row">

                    <div class="col-md-4">
                        <label>Título</label>
                        <input type="text" name="titulo" class="form-control" required>
                    </div>

                    <div class="col-md-5">
                        <label>Descripción</label>
                        <input type="text" name="descripcion" class="form-control">
                    </div>

                    <div class="col-md-3">
                        <label>Asignar a Perfil</label>
                        <select name="perfil_asignado" class="form-select" required>
                            <option value="guardia">Guardia</option>
                            <option value="supervisor">Supervisor</option>
                            <option value="admin">Admin</option>
                            <option value="rh">RH</option>
                        </select>
                    </div>
                </div>

                <div class="mt-3">
                    <label>Archivo de video (MP4 o enlace YouTube)</label>
                    <input type="file" name="video" class="form-control" required>
                </div>

                <div class="mt-3 text-end">
                    <button class="btn btn-success">Subir Video</button>
                </div>
            </form>
        </div>
    </div>

    <!-- LISTADO DE VIDEOS -->
    <div class="card shadow-sm">
        <div class="card-header bg-secondary text-white">
            Videos Disponibles (Onboarding / Capacitación Continua)
        </div>

        <div class="table-responsive">
            <table class="table table-striped mb-0">
                <thead>
                    <tr>
                        <th>Título</th>
                        <th>Perfil Asignado</th>
                        <th>Subido</th>
                        <th>Vistos</th>
                        <th>Opciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!$videos): ?>
                    <tr><td colspan="5" class="text-center py-3">No hay videos cargados.</td></tr>
                <?php else: ?>
                    <?php foreach ($videos as $v): ?>
                    <tr>
                        <td>
                            <?= htmlspecialchars($v['titulo']) ?><br>
                            <small class="text-muted"><?= htmlspecialchars($v['descripcion']) ?></small>
                        </td>
                        <td><span class="badge bg-info"><?= strtoupper($v['perfil_asignado']) ?></span></td>
                        <td><?= date('d/m/Y', strtotime($v['created_at'])) ?></td>
                        <td><?= $v['vistos'] ?> vistos</td>
                        <td>
                            <a href="ver_detalle_capacitacion.php?id=<?= $v['id'] ?>" 
                               class="btn btn-sm btn-outline-primary">Ver Detalle</a>
                            <a href="delete_video.php?id=<?= $v['id'] ?>" 
                               class="btn btn-sm btn-outline-danger"
                               onclick="return confirm('¿Eliminar definitivamente?')">Eliminar</a>
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
