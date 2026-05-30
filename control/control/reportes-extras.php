<?php

require_once __DIR__ . '/lib/app.php';
require_once __DIR__ . '/lib/ui.php';
require_once __DIR__ . '/lib/payroll.php';

app_require_session();
app_require_roles(['ADMIN', 'RH', 'NOMINA', 'DUEÑO', 'SUPERVISOR']);

$configs = app_get_config_map($conexion);
$periodId = (int)app_get('periodo_id', 0);
if ($periodId <= 0) {
    $bounds = app_current_period_bounds();
    $periodId = app_get_or_create_period($conexion, $bounds['inicio'], $bounds['fin']);
}

$period = app_get_period($conexion, $periodId);
$periods = app_db_all($conexion, "SELECT * FROM nomina_periodos ORDER BY fecha_inicio DESC, id DESC");

$rows = [];
$totals = ['turnos_extra' => 0.0, 'horas_extra' => 0.0, 'horas_menos' => 0.0];

if ($period) {
    $sql = "
        SELECT
            p.id AS personal_id,
            p.fecha_contratacion,
            CONCAT(p.nombres, ' ', p.apellidos) AS colaborador,
            COALESCE(SUM(CASE WHEN t.es_turno_extra = 1 THEN t.monto_turno_extra ELSE 0 END), 0) AS total_turnos_extra,
            COALESCE(SUM(CASE WHEN aj.tipo_ajuste = 'BONO' THEN aj.horas ELSE 0 END), 0) AS horas_extra,
            COALESCE(SUM(CASE WHEN aj.tipo_ajuste = 'HORA_MENOS' THEN aj.horas ELSE 0 END), 0) AS horas_menos
        FROM personal p
        LEFT JOIN turnos t
            ON t.personal_id = p.id
           AND t.es_turno_extra = 1
           AND t.hora_inicio BETWEEN '" . mysqli_real_escape_string($conexion, $period['fecha_inicio'] . " 00:00:00") . "'
                                AND '" . mysqli_real_escape_string($conexion, $period['fecha_fin'] . " 23:59:59") . "'
        LEFT JOIN ajustes_nomina aj
            ON aj.personal_id = p.id
           AND aj.fecha_aplicacion BETWEEN '" . mysqli_real_escape_string($conexion, $period['fecha_inicio']) . "'
                                      AND '" . mysqli_real_escape_string($conexion, $period['fecha_fin']) . "'
           AND aj.tipo_ajuste IN ('BONO', 'HORA_MENOS')
        GROUP BY p.id, colaborador
        HAVING total_turnos_extra > 0 OR horas_extra > 0 OR horas_menos > 0
        ORDER BY colaborador
    ";

    foreach (app_db_all($conexion, $sql) as $row) {
        $pagoHorasExtra = ((float)$row['horas_extra']) * app_config_float($configs, 'nomina_valor_hora', 75.0);
        $descuentoHoras = ((float)$row['horas_menos']) * app_config_float($configs, 'nomina_valor_hora', 75.0);
            $rows[] = [
            'personal_id' => (int)$row['personal_id'],
            'fecha_contratacion' => $row['fecha_contratacion'],
            'colaborador' => $row['colaborador'],
            'turnos_extra' => (float)$row['total_turnos_extra'],
            'horas_extra' => (float)$row['horas_extra'],
            'horas_extra_pago' => $pagoHorasExtra,
            'horas_menos' => (float)$row['horas_menos'],
            'horas_menos_descuento' => $descuentoHoras,
        ];

        $totals['turnos_extra'] += (float)$row['total_turnos_extra'];
        $totals['horas_extra'] += $pagoHorasExtra;
        $totals['horas_menos'] += $descuentoHoras;
    }
}

app_render_page_start(
    'Costo de extras',
    'Costo de extras',
    'Costo operativo de turnos extra, horas extra y horas descontadas por quincena.'
);
?>

<div class="row layout-top-spacing mb-3">
    <div class="col-md-4">
        <div class="card summary-card p-3">
            <div class="summary-label">Turnos extra</div>
            <div class="summary-value"><?php echo app_money($totals['turnos_extra']); ?></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card summary-card p-3">
            <div class="summary-label">Horas extra pagadas</div>
            <div class="summary-value"><?php echo app_money($totals['horas_extra']); ?></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card summary-card p-3">
            <div class="summary-label">Horas menos descontadas</div>
            <div class="summary-value"><?php echo app_money($totals['horas_menos']); ?></div>
        </div>
    </div>
</div>

<div class="card content-card mb-4">
    <div class="card-body">
        <h5 class="mb-3">Periodo</h5>
        <div class="d-flex flex-wrap gap-2">
            <?php foreach ($periods as $item): ?>
                <a href="?periodo_id=<?php echo (int)$item['id']; ?>" class="btn btn-outline-secondary btn-sm">
                    <?php echo app_h($item['clave']); ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div class="card content-card">
    <div class="card-body">
        <h5 class="mb-3">Detalle por colaborador</h5>
        <input type="search" class="form-control mb-3" data-table-search="#extras-table" placeholder="Buscar colaborador o No. empleado...">
        <div class="table-responsive">
            <table class="table table-striped" id="extras-table">
                <thead>
                    <tr>
                        <th>Colaborador</th>
                        <th>No. empleado</th>
                        <th>Turnos extra</th>
                        <th>Horas extra</th>
                        <th>Pago horas extra</th>
                        <th>Horas menos</th>
                        <th>Descuento horas menos</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <td><?php echo app_h($row['colaborador']); ?></td>
                            <td><?php echo app_h(app_employee_number($row)); ?></td>
                            <td><?php echo app_money($row['turnos_extra']); ?></td>
                            <td><?php echo app_number($row['horas_extra']); ?></td>
                            <td><?php echo app_money($row['horas_extra_pago']); ?></td>
                            <td><?php echo app_number($row['horas_menos']); ?></td>
                            <td><?php echo app_money($row['horas_menos_descuento']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$rows): ?>
                        <tr><td colspan="7" class="text-center text-muted">Sin movimientos de extras para el periodo.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
app_render_page_end();
