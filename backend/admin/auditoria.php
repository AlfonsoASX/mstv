<?php
/**
 * ===============================================
 * PANEL ADMIN → AUDITORÍA DEL SISTEMA
 * Archivo: backend/admin/auditoria.php
 * ===============================================
 */

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
require_once __DIR__ . '/../core/helpers.php';
require_once __DIR__ . '/../config/database.php';

$db = Database::getInstance()->getConnection();

// 🔎 Obtener filtros (GET)
$filtros = [
    'usuario' => $_GET['usuario'] ?? '',
    'modulo' => $_GET['modulo'] ?? '',
    'accion' => $_GET['accion'] ?? '',
    'desde'  => $_GET['desde']  ?? '',
    'hasta'  => $_GET['hasta']  ?? '',
];

?>
<div class="container">

    <h3 class="mb-4">Auditoría del Sistema</h3>

    <!-- 🎯 FILTROS -->
    <div class="card p-3 shadow-sm mb-4">
      <form method="GET" class="row g-3">

        <div class="col-md-3">
            <label>Usuario</label>
            <input type="text" name="usuario" class="form-control"
                   value="<?= htmlspecialchars($filtros['usuario']) ?>">
        </div>

        <div class="col-md-2">
            <label>Módulo</label>
            <input type="text" name="modulo" class="form-control"
                   placeholder="Ej: turnos" value="<?= htmlspecialchars($filtros['modulo']) ?>">
        </div>

        <div class="col-md-2">
            <label>Acción</label>
            <input type="text" name="accion" class="form-control"
                   placeholder="login, crear, editar"
                   value="<?= htmlspecialchars($filtros['accion']) ?>">
        </div>

        <div class="col-md-2">
            <label>Desde</label>
            <input type="date" name="desde" class="form-control" value="<?= $filtros['desde'] ?>">
        </div>

        <div class="col-md-2">
            <label>Hasta</label>
            <input type="date" name="hasta" class="form-control" value="<?= $filtros['hasta'] ?>">
        </div>

        <div class="col-md-1 d-flex align-items-end">
            <button class="btn btn-primary w-100">Filtrar</button>
        </div>
      </form>
    </div>

    <?php
    // 📌 Armado de consulta dinámica
    $sql = "SELECT a.*, u.nombre AS usuario_nombre
            FROM auditoria a
            LEFT JOIN usuarios u ON u.id = a.usuario_id
            WHERE 1=1";

    $params = [];

    if ($filtros['usuario']) {
        $sql .= " AND u.nombre LIKE :usuario";
        $params['usuario'] = '%' . $filtros['usuario'] . '%';
    }
    if ($filtros['modulo']) {
        $sql .= " AND a.modulo LIKE :modulo";
        $params['modulo'] = '%' . $filtros['modulo'] . '%';
    }
    if ($filtros['accion']) {
        $sql .= " AND a.accion LIKE :accion";
        $params['accion'] = '%' . $filtros['accion'] . '%';
    }
    if ($filtros['desde']) {
        $sql .= " AND DATE(a.fecha_hora) >= :desde";
        $params['desde'] = $filtros['desde'];
    }
    if ($filtros['hasta']) {
        $sql .= " AND DATE(a.fecha_hora) <= :hasta";
        $params['hasta'] = $filtros['hasta'];
    }

    $sql .= " ORDER BY a.fecha_hora DESC LIMIT 200";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $registros = $stmt->fetchAll();
    ?>

    <!-- 📋 TABLA DE AUDITORÍA -->
    <div class="card table-responsive shadow-sm">
        <table class="table table-sm table-striped mb-0">
            <thead class="table-light">
                <tr>
                    <th>Fecha/Hora</th>
                    <th>Usuario</th>
                    <th>Módulo</th>
                    <th>Acción</th>
                    <th>ID Referencia</th>
                    <th>Detalle</th>
                </tr>
            </thead>
            
            <tbody>
            <?php if (!$registros): ?>
                <tr><td colspan="6" class="text-center py-3">Sin registros encontrados</td></tr>
            <?php else: ?>
                <?php foreach ($registros as $r): ?>
                    <tr>
                        <td><?= $r['fecha_hora'] ?></td>
                        <td><?= htmlspecialchars($r['usuario_nombre']) ?></td>
                        <td><?= htmlspecialchars($r['modulo']) ?></td>
                        <td><span class="badge bg-secondary"><?= htmlspecialchars($r['accion']) ?></span></td>
                        <td><?= $r['referencia_id'] ?></td>
                        <td><?= nl2br(htmlspecialchars($r['detalle'])) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- 📤 Exportación -->
    <div class="mt-4 text-end">
        <button class="btn btn-sm btn-outline-primary">Exportar Excel</button>
        <button class="btn btn-sm btn-outline-secondary">Exportar PDF</button>
    </div>

</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
