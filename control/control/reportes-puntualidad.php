<?php

require_once __DIR__ . '/lib/app.php';
require_once __DIR__ . '/lib/ui.php';
require_once __DIR__ . '/lib/payroll.php';

app_require_session();
app_require_roles(['ADMIN', 'RH', 'NOMINA', 'DUEÑO', 'SUPERVISOR', 'CLIENTE']);

$configs = app_get_config_map($conexion);
$periodId = (int)app_get('periodo_id', 0);
if ($periodId <= 0) {
    $bounds = app_current_period_bounds();
    $periodId = app_get_or_create_period($conexion, $bounds['inicio'], $bounds['fin']);
}

$period = app_get_period($conexion, $periodId);
$periods = app_db_all($conexion, "SELECT * FROM nomina_periodos ORDER BY fecha_inicio DESC, id DESC");

$tolerancia = app_config_int($configs, 'turnos_tolerancia_minutos', 15);
$maxHoras = app_config_int($configs, 'turnos_max_retardo_horas', 4);
$valorHora = app_config_float($configs, 'nomina_valor_hora', 75.0);

$report = [];
$totales = [
    'retardos_horas' => 0.0,
    'descuento' => 0.0,
    'turnos_cancelados' => 0,
    'faltas' => 0,
];

if ($period) {
    foreach (app_get_active_personal($conexion) as $personal) {
        $turnos = app_build_turn_map($conexion, (int)$personal['id'], $period['fecha_inicio'], $period['fecha_fin']);
        $retardoHoras = 0.0;
        $descuento = 0.0;
        $cancelados = 0;

        foreach ($turnos as $turno) {
            if ((int)$turno['es_turno_extra'] === 1) {
                continue;
            }
            $data = app_calculate_tardiness($turno['hora_inicio'], $turno['entrada_real'], $tolerancia, $maxHoras);
            if ($data['cancelado']) {
                $cancelados++;
            } else {
                $retardoHoras += $data['horas'];
                $descuento += $data['horas'] * $valorHora;
            }
        }

        $faltas = (int)app_db_value(
            $conexion,
            "SELECT COUNT(*) AS total
             FROM faltas_personal
             WHERE personal_id = " . (int)$personal['id'] . "
               AND fecha_falta BETWEEN '" . mysqli_real_escape_string($conexion, $period['fecha_inicio']) . "'
                                   AND '" . mysqli_real_escape_string($conexion, $period['fecha_fin']) . "'",
            0
        );

        if ($retardoHoras <= 0 && $cancelados <= 0 && $faltas <= 0) {
            continue;
        }

        $report[] = [
            'personal' => $personal,
            'retardo_horas' => $retardoHoras,
            'descuento' => $descuento,
            'cancelados' => $cancelados,
            'faltas' => $faltas,
        ];

        $totales['retardos_horas'] += $retardoHoras;
        $totales['descuento'] += $descuento;
        $totales['turnos_cancelados'] += $cancelados;
        $totales['faltas'] += $faltas;
    }
}

app_render_page_start(
    'Puntualidad y absentismo',
    'Puntualidad y absentismo',
    'Resumen de retardos por quincena con tolerancia, turnos cancelados y faltas capturadas.'
);
?>

<div class="row layout-top-spacing mb-3">
    <div class="col-md-3">
        <div class="card summary-card p-3">
            <div class="summary-label">Horas de retardo</div>
            <div class="summary-value"><?php echo app_number($totales['retardos_horas']); ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card summary-card p-3">
            <div class="summary-label">Descuento estimado</div>
            <div class="summary-value"><?php echo app_money($totales['descuento']); ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card summary-card p-3">
            <div class="summary-label">Turnos cancelados</div>
            <div class="summary-value"><?php echo $totales['turnos_cancelados']; ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card summary-card p-3">
            <div class="summary-label">Faltas</div>
            <div class="summary-value"><?php echo $totales['faltas']; ?></div>
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
        <?php if ($period): ?>
            <div class="mt-3 text-muted">
                Periodo activo: <?php echo app_quincena_label($period['fecha_inicio'], $period['fecha_fin']); ?>
                | Tolerancia: <?php echo $tolerancia; ?> minutos
                | Valor por hora: <?php echo app_money($valorHora); ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="card content-card">
    <div class="card-body">
        <h5 class="mb-3">Detalle por colaborador</h5>
        <input type="search" class="form-control mb-3" data-table-search="#puntualidad-table" placeholder="Buscar colaborador, No. empleado o concepto...">
        <div class="table-responsive">
            <table class="table table-striped" id="puntualidad-table">
                <thead>
                    <tr>
                        <th>Colaborador</th>
                        <th>No. empleado</th>
                        <th>Horas de retardo</th>
                        <th>Descuento</th>
                        <th>Turnos cancelados</th>
                        <th>Faltas</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($report as $row): ?>
                        <tr>
                            <td><?php echo app_h($row['personal']['nombres'] . ' ' . $row['personal']['apellidos']); ?></td>
                            <td><?php echo app_h(app_employee_number($row['personal'])); ?></td>
                            <td><?php echo app_number($row['retardo_horas']); ?></td>
                            <td><?php echo app_money($row['descuento']); ?></td>
                            <td><?php echo (int)$row['cancelados']; ?></td>
                            <td><?php echo (int)$row['faltas']; ?></td>
                            <td>
                                <a href="personas-ficha.php?personal_id=<?php echo (int)$row['personal']['id']; ?>" class="btn btn-outline-secondary btn-sm">
                                    Ver ficha
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$report): ?>
                        <tr><td colspan="7" class="text-center text-muted">No hay retardos o faltas para el periodo seleccionado.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
app_render_page_end();
