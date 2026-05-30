<?php

require_once __DIR__ . '/lib/app.php';
require_once __DIR__ . '/lib/ui.php';

app_require_session();
app_require_roles(['ADMIN', 'RH', 'NOMINA', 'DUEÑO', 'SUPERVISOR', 'CLIENTE']);

$desde = app_clean_text(app_get('desde', date('Y-m-01')));
$hasta = app_clean_text(app_get('hasta', date('Y-m-t')));

$sql = "
    SELECT
        s.nombre AS sitio_nombre,
        COUNT(i.id) AS total_incidencias,
        SUM(i.estado = 'PENDIENTE') AS pendientes,
        SUM(i.estado = 'EN_PROCESO') AS en_proceso,
        SUM(i.estado = 'CERRADO') AS cerradas,
        SUM(i.prioridad IN ('ALTA', 'CRITICA')) AS altas
    FROM sitios s
    LEFT JOIN incidencias i
        ON i.sitio_id = s.id
       AND DATE(i.fecha_creacion) BETWEEN '" . mysqli_real_escape_string($conexion, $desde) . "'
                                      AND '" . mysqli_real_escape_string($conexion, $hasta) . "'
    GROUP BY s.id, s.nombre
    HAVING total_incidencias > 0
    ORDER BY total_incidencias DESC, sitio_nombre
";

$rows = app_db_all($conexion, $sql);
$total = 0;
$altas = 0;
$pendientes = 0;
foreach ($rows as $row) {
    $total += (int)$row['total_incidencias'];
    $altas += (int)$row['altas'];
    $pendientes += (int)$row['pendientes'];
}

app_render_page_start(
    'Incidencias por sitio',
    'Incidencias por sitio',
    'Seguimiento de incidencias abiertas, críticas y cerradas por sitio.'
);
?>

<div class="row layout-top-spacing mb-3">
    <div class="col-md-4">
        <div class="card summary-card p-3">
            <div class="summary-label">Incidencias</div>
            <div class="summary-value"><?php echo $total; ?></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card summary-card p-3">
            <div class="summary-label">Pendientes</div>
            <div class="summary-value"><?php echo $pendientes; ?></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card summary-card p-3">
            <div class="summary-label">Altas o críticas</div>
            <div class="summary-value"><?php echo $altas; ?></div>
        </div>
    </div>
</div>

<div class="card content-card mb-4">
    <div class="card-body">
        <h5 class="mb-3">Filtros</h5>
        <form method="get">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Desde</label>
                    <input type="date" name="desde" class="form-control" value="<?php echo app_h($desde); ?>">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Hasta</label>
                    <input type="date" name="hasta" class="form-control" value="<?php echo app_h($hasta); ?>">
                </div>
                <div class="col-md-4 mb-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">Aplicar filtros</button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card content-card">
    <div class="card-body">
        <h5 class="mb-3">Detalle por sitio</h5>
        <input type="search" class="form-control mb-3" data-table-search="#incidencias-reporte-table" placeholder="Buscar sitio o estado...">
        <div class="table-responsive">
            <table class="table table-striped" id="incidencias-reporte-table">
                <thead>
                    <tr>
                        <th>Sitio</th>
                        <th>Total</th>
                        <th>Pendientes</th>
                        <th>En proceso</th>
                        <th>Cerradas</th>
                        <th>Altas/Críticas</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <td><?php echo app_h($row['sitio_nombre']); ?></td>
                            <td><?php echo (int)$row['total_incidencias']; ?></td>
                            <td><?php echo (int)$row['pendientes']; ?></td>
                            <td><?php echo (int)$row['en_proceso']; ?></td>
                            <td><?php echo (int)$row['cerradas']; ?></td>
                            <td><?php echo (int)$row['altas']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$rows): ?>
                        <tr><td colspan="6" class="text-center text-muted">No hay incidencias en el rango seleccionado.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
app_render_page_end();
