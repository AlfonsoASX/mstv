<?php
/**
 * ===============================================
 * PANEL → CHECADAS / ASISTENCIA
 * Archivo: backend/admin/checadas.php
 * ===============================================
 */

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
require_once __DIR__ . '/../core/helpers.php';
require_once __DIR__ . '/../config/database.php';

$db = Database::getInstance()->getConnection();

// Obtener filtros de GET
$filtros = [
    'guardia' => $_GET['guardia'] ?? '',
    'sitio'   => $_GET['sitio'] ?? '',
    'tipo'    => $_GET['tipo'] ?? '',
    'desde'   => $_GET['desde'] ?? '',
    'hasta'   => $_GET['hasta'] ?? ''
];

// Obtener guardias y sitios para los filtros
$guardias = $db->query("SELECT id, CONCAT(nombre,' ',apellido) AS nombre FROM usuarios WHERE rol='guardia'")->fetchAll();
$sitios   = $db->query("SELECT id, nombre FROM sitios")->fetchAll();

?>
<div class="container">

    <h3 class="mb-4">Checadas / Asistencia</h3>

    <!-- 🎯 FILTROS -->
    <div class="card p-3 mb-4 shadow-sm">
      <form method="GET" class="row g-3">

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

        <div class="col-md-2">
            <label>Tipo</label>
            <select name="tipo" class="form-select">
                <option value="">Todos</option>
                <option <?= $filtros['tipo']=='entrada'?'selected':'' ?>>entrada</option>
                <option <?= $filtros['tipo']=='salida'?'selected':'' ?>>salida</option>
                <option <?= $filtros['tipo']=='extra'?'selected':'' ?>>extra</option>
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

    <!-- 🗂 Tabla de checadas -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-secondary text-white">Registros Encontrados</div>
        <div class="table-responsive">
            <table class="table table-striped table-sm mb-0">
                <thead class="table-light">
                <tr>
                    <th>Guardia</th>
                    <th>Sitio</th>
                    <th>Tipo</th>
                    <th>Fecha/Hora</th>
                    <th>Validación</th>
                    <th>Selfie</th>
                </tr>
                </thead>
                <tbody>
                <?php
                // Construir consulta
                $sql = "
                  SELECT c.*, 
                         CONCAT(u.nombre,' ',u.apellido) AS guardia_nombre,
                         s.nombre AS sitio_nombre
                  FROM checadas c
                  LEFT JOIN usuarios u ON u.id = c.guardia_id
                  LEFT JOIN sitios s   ON s.id = c.sitio_id
                  WHERE 1=1
                ";

                $params = [];
                if ($filtros['guardia']) { $sql .= " AND c.guardia_id = :g"; $params['g'] = $filtros['guardia']; }
                if ($filtros['sitio'])   { $sql .= " AND c.sitio_id = :s";   $params['s'] = $filtros['sitio']; }
                if ($filtros['tipo'])    { $sql .= " AND c.tipo = :t";      $params['t'] = $filtros['tipo']; }
                if ($filtros['desde'])   { $sql .= " AND DATE(c.fecha_hora) >= :d"; $params['d'] = $filtros['desde']; }
                if ($filtros['hasta'])   { $sql .= " AND DATE(c.fecha_hora) <= :h"; $params['h'] = $filtros['hasta']; }

                $sql .= " ORDER BY c.fecha_hora DESC LIMIT 300";

                $stmt = $db->prepare($sql);
                $stmt->execute($params);
                $rows = $stmt->fetchAll();

                if (!$rows): ?>
                    <tr><td colspan="6" class="text-center py-3">Sin registros.</td></tr>
                <?php else:
                    foreach ($rows as $r): ?>
                    <tr>
                        <td><?= htmlspecialchars($r['guardia_nombre']) ?></td>
                        <td><?= htmlspecialchars($r['sitio_nombre']) ?></td>
                        <td><?= ucfirst($r['tipo']) ?></td>
                        <td><?= $r['fecha_hora'] ?></td>
                        <td>
                            <?php if ($r['validado_geo'] && $r['validado_facial']): ?>
                                <span class="badge bg-success">Válida</span>
                            <?php else: ?>
                                <span class="badge bg-danger">Rechazada</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($r['foto']): ?>
                                <img src="/fotos/<?= htmlspecialchars($r['foto']) ?>" width="60">
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Exportar -->
    <div class="text-end mb-5">
        <button class="btn btn-sm btn-outline-primary">Exportar Excel</button>
        <button class="btn btn-sm btn-outline-secondary">Exportar PDF</button>
    </div>

</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
