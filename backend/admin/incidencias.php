<?php
/**
 * ===============================================
 * PANEL ADMIN → GESTIÓN DE INCIDENCIAS
 * Archivo: backend/admin/incidencias.php
 * ===============================================
 */

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/helpers.php';

$db = Database::getInstance()->getConnection();

// Obtener filtros de búsqueda
$filtros = [
    'estado'   => $_GET['estado'] ?? '',
    'guardia'  => $_GET['guardia'] ?? '',
    'sitio'    => $_GET['sitio'] ?? '',
    'prioridad'=> $_GET['prioridad'] ?? '',
    'desde'    => $_GET['desde'] ?? '',
    'hasta'    => $_GET['hasta'] ?? ''
];

// Obtener listas para selects
$guardias = $db->query("SELECT id, CONCAT(nombre,' ',apellido) AS nombre FROM usuarios WHERE rol='guardia'")->fetchAll();
$sitios = $db->query("SELECT id, nombre FROM sitios")->fetchAll();

?>
<div class="container">

    <h3 class="mb-4">Gestión de Incidencias</h3>

    <!-- 🔎 Filtros -->
    <div class="card p-3 shadow-sm mb-4">
        <form method="GET" class="row g-3">

            <div class="col-md-2">
                <label>Estado</label>
                <select name="estado" class="form-select">
                    <option value="">Todos</option>
                    <option <?= $filtros['estado']=='Pendiente'?'selected':'' ?>>Pendiente</option>
                    <option <?= $filtros['estado']=='Atendido'?'selected':'' ?>>Atendido</option>
                    <option <?= $filtros['estado']=='Cerrado'?'selected':'' ?>>Cerrado</option>
                </select>
            </div>

            <div class="col-md-2">
                <label>Prioridad</label>
                <select name="prioridad" class="form-select">
                    <option value="">Todas</option>
                    <option <?= $filtros['prioridad']=='alta'?'selected':'' ?>>Alta</option>
                    <option <?= $filtros['prioridad']=='media'?'selected':'' ?>>Media</option>
                    <option <?= $filtros['prioridad']=='baja'?'selected':'' ?>>Baja</option>
                </select>
            </div>

            <div class="col-md-3">
                <label>Sitio</label>
                <select name="sitio" class="form-select">
                    <option value="">Todos</option>
                    <?php foreach ($sitios as $s): ?>
                        <option value="<?= $s['id'] ?>" <?= ($filtros['sitio']==$s['id'])?'selected':'' ?>>
                            <?= htmlspecialchars($s['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-3">
                <label>Guardia</label>
                <select name="guardia" class="form-select">
                    <option value="">Todos</option>
                    <?php foreach ($guardias as $g): ?>
                        <option value="<?= $g['id'] ?>" <?= ($filtros['guardia']==$g['id'])?'selected':'' ?>>
                            <?= htmlspecialchars($g['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-2">
                <label>Desde</label>
                <input type="date" name="desde" class="form-control" value="<?= $filtros['desde'] ?>">
            </div>

            <div class="col-md-2">
                <label>Hasta</label>
                <input type="date" name="hasta" class="form-control" value="<?= $filtros['hasta'] ?>">
            </div>

            <div class="col-md-2 d-flex align-items-end">
                <button class="btn btn-primary w-100">Filtrar</button>
            </div>
        </form>
    </div>

    <!-- 🗃 Tabla de incidencias -->
    <div class="card shadow-sm mb-5">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                <tr>
                    <th>Guardia</th>
                    <th>Sitio</th>
                    <th>Tipo</th>
                    <th>Prioridad</th>
                    <th>Estado</th>
                    <th>Fecha</th>
                    <th>Evidencia</th>
                    <th>Acciones</th>
                </tr>
                </thead>
                <tbody>
                <?php
                $sql = "
                    SELECT i.*, 
                           CONCAT(u.nombre,' ',u.apellido) AS guardia_nombre,
                           s.nombre AS sitio_nombre
                    FROM incidencias i
                    LEFT JOIN usuarios u ON u.id = i.guardia_id
                    LEFT JOIN sitios s ON s.id = i.sitio_id
                    WHERE 1 = 1
                ";

                $params = [];
                if ($filtros['estado'])    { $sql .= " AND i.estado = :e";    $params['e'] = $filtros['estado']; }
                if ($filtros['prioridad']) { $sql .= " AND i.prioridad = :p"; $params['p'] = $filtros['prioridad']; }
                if ($filtros['guardia'])   { $sql .= " AND i.guardia_id = :g"; $params['g'] = $filtros['guardia']; }
                if ($filtros['sitio'])     { $sql .= " AND i.sitio_id = :s";   $params['s'] = $filtros['sitio']; }
                if ($filtros['desde'])     { $sql .= " AND DATE(i.creado_en) >= :d"; $params['d'] = $filtros['desde']; }
                if ($filtros['hasta'])     { $sql .= " AND DATE(i.creado_en) <= :h"; $params['h'] = $filtros['hasta']; }

                $sql .= " ORDER BY i.creado_en DESC LIMIT 200";

                $stmt = $db->prepare($sql);
                $stmt->execute($params);
                $rows = $stmt->fetchAll();

                if (!$rows): ?>
                    <tr><td colspan="8" class="text-center py-4">No hay incidencias registradas.</td></tr>
                <?php else:
                    foreach ($rows as $r): ?>
                        <tr>
                            <td><?= htmlspecialchars($r['guardia_nombre']) ?></td>
                            <td><?= htmlspecialchars($r['sitio_nombre']) ?></td>
                            <td><?= ucfirst($r['tipo']) ?></td>
                            <td>
                                <span class="badge bg-<?= $r['prioridad']=='alta'?'danger':($r['prioridad']=='media'?'warning':'secondary') ?>">
                                    <?= ucfirst($r['prioridad']) ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-<?= 
                                    $r['estado']=='Pendiente'?'danger':
                                    ($r['estado']=='Atendido'?'warning':'success')
                                ?>">
                                    <?= $r['estado'] ?>
                                </span>
                            </td>
                            <td><?= date('d/m/Y H:i', strtotime($r['creado_en'])) ?></td>

                            <td>
                                <?php if ($r['foto']): ?>
                                    <img src="/incidencias/<?= htmlspecialchars($r['foto']) ?>" 
                                         width="55" height="55" class="rounded">
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>

                            <td>
                                <a href="incidencia_detalle.php?id=<?= $r['id'] ?>" 
                                   class="btn btn-sm btn-outline-info">Ver</a>

                                <?php if ($r['estado'] != 'Cerrado'): ?>
                                    <a href="incidencia_cambiar_estado.php?id=<?= $r['id'] ?>&estado=Atendido" 
                                       class="btn btn-sm btn-outline-primary">Atendido</a>

                                    <a href="incidencia_cambiar_estado.php?id=<?= $r['id'] ?>&estado=Cerrado" 
                                       class="btn btn-sm btn-outline-success">Cerrar</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
