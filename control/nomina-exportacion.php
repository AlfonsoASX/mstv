<?php

require_once __DIR__ . '/lib/app.php';
require_once __DIR__ . '/lib/ui.php';
require_once __DIR__ . '/lib/payroll.php';

app_require_session();
app_require_roles(['ADMIN', 'RH', 'NOMINA', 'DUEÑO']);

$periodId = (int)app_get('periodo_id', 0);
if ($periodId <= 0) {
    $latest = app_db_one($conexion, "SELECT id FROM nomina_periodos ORDER BY fecha_inicio DESC, id DESC LIMIT 1");
    $periodId = (int)($latest['id'] ?? 0);
}

$period = $periodId > 0 ? app_get_period($conexion, $periodId) : null;
$rows = $period ? app_nomina_rows($conexion, $periodId) : [];

if ($period && app_get('format', '') === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="nomina-' . $period['clave'] . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Colaborador', 'No. empleado', 'Usuario', 'Base', 'Horas extra', 'Turnos extra', 'Vacaciones', 'Prima vacacional', 'Bonos', 'Retardos', 'Faltas', 'Descansos', 'Sanciones', 'Material', 'Infonavit', 'Fonacot', 'Prestamos', 'Adelantos', 'Otros', 'Neto']);
    foreach ($rows as $row) {
        fputcsv($out, [
            $row['nombres'] . ' ' . $row['apellidos'],
            app_employee_number($row),
            $row['usuario'],
            $row['salario_base'],
            $row['pago_horas_extra'],
            $row['turnos_extra_monto'],
            $row['vacaciones_monto'],
            $row['prima_vacacional_monto'],
            $row['bonos_monto'],
            $row['descuento_retardos'],
            $row['descuentos_faltas'],
            $row['descuentos_descansos'] ?? 0,
            $row['descuentos_sanciones'],
            $row['descuentos_material'],
            $row['descuentos_infonavit'],
            $row['descuentos_fonacot'],
            $row['descuentos_prestamos'],
            $row['descuentos_adelantos'],
            $row['otros_descuentos'],
            $row['neto'],
        ]);
    }
    fclose($out);
    exit;
}

$periods = app_db_all($conexion, "SELECT * FROM nomina_periodos ORDER BY fecha_inicio DESC, id DESC");

app_render_page_start(
    'Exportación de nómina',
    'Exportación de nómina',
    'Vista previa y descarga CSV de la quincena calculada.'
);
?>

<div class="row layout-top-spacing mb-3">
    <div class="col-md-4">
        <div class="card summary-card p-3">
            <div class="summary-label">Periodo seleccionado</div>
            <div class="summary-value" style="font-size:1.2rem;"><?php echo app_h($period['clave'] ?? 'Sin periodo'); ?></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card summary-card p-3">
            <div class="summary-label">Registros</div>
            <div class="summary-value"><?php echo count($rows); ?></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card summary-card p-3">
            <div class="summary-label">Descarga</div>
            <div class="summary-value" style="font-size:1.1rem;">
                <?php if ($period): ?>
                    <a href="?periodo_id=<?php echo $periodId; ?>&format=csv">CSV</a>
                <?php else: ?>
                    -
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="card content-card mb-4">
    <div class="card-body">
        <h5 class="mb-3">Seleccionar periodo</h5>
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
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h5 class="mb-0">Vista previa</h5>
                <?php if ($period): ?>
                    <small class="text-muted"><?php echo app_quincena_label($period['fecha_inicio'], $period['fecha_fin']); ?></small>
                <?php endif; ?>
            </div>
            <?php if ($period): ?>
                <a href="?periodo_id=<?php echo $periodId; ?>&format=csv" class="btn btn-primary">Descargar CSV</a>
            <?php endif; ?>
	        </div>
            <input type="search" class="form-control mb-3" data-table-search="#nomina-exportacion-table" placeholder="Buscar colaborador, No. empleado o monto...">

	        <div class="table-responsive">
	            <table class="table table-striped" id="nomina-exportacion-table">
                <thead>
                    <tr>
                        <th>Colaborador</th>
                        <th>No. empleado</th>
                        <th>Base</th>
                        <th>Extras</th>
                        <th>Vacaciones</th>
                        <th>Bonos</th>
                        <th>Descansos</th>
                        <th>Deducciones</th>
                        <th>Neto</th>
                        <th>Recibo</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $row): ?>
                        <?php
                        $extras = (float)$row['pago_horas_extra'] + (float)$row['turnos_extra_monto'];
                        $vacaciones = (float)$row['vacaciones_monto'] + (float)$row['prima_vacacional_monto'];
                        $deducciones = (float)$row['descuento_retardos']
                            + (float)$row['descuentos_faltas']
                            + (float)($row['descuentos_descansos'] ?? 0)
                            + (float)$row['descuentos_sanciones']
                            + (float)$row['descuentos_material']
                            + (float)$row['descuentos_infonavit']
                            + (float)$row['descuentos_fonacot']
                            + (float)$row['descuentos_prestamos']
                            + (float)$row['descuentos_adelantos']
                            + (float)$row['otros_descuentos'];
                        ?>
                        <tr>
                            <td><?php echo app_h($row['nombres'] . ' ' . $row['apellidos']); ?></td>
                            <td><?php echo app_h(app_employee_number($row)); ?></td>
                            <td><?php echo app_money($row['salario_base']); ?></td>
                            <td><?php echo app_money($extras); ?></td>
                            <td><?php echo app_money($vacaciones); ?></td>
                            <td><?php echo app_money($row['bonos_monto']); ?></td>
                            <td><?php echo app_money($row['descuentos_descansos'] ?? 0); ?></td>
                            <td><?php echo app_money($deducciones); ?></td>
                            <td><strong><?php echo app_money($row['neto']); ?></strong></td>
                            <td>
                                <a
                                    href="nomina-recibo.php?periodo_id=<?php echo $periodId; ?>&personal_id=<?php echo (int)$row['personal_id']; ?>"
                                    class="btn btn-outline-primary btn-sm"
                                    target="_blank"
                                >
                                    Recibo
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$rows): ?>
                        <tr><td colspan="10" class="text-center text-muted">No hay datos calculados para este periodo.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
app_render_page_end();
