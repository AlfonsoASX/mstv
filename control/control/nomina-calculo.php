<?php

require_once __DIR__ . '/lib/app.php';
require_once __DIR__ . '/lib/ui.php';
require_once __DIR__ . '/lib/payroll.php';

app_require_session();
app_require_roles(['ADMIN', 'RH', 'NOMINA', 'DUEÑO']);

$messages = ['success' => '', 'error' => ''];
$configs = app_get_config_map($conexion);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = app_post('accion', '');

    if ($accion === 'crear_periodo') {
        $fechaInicio = app_clean_text(app_post('fecha_inicio', ''));
        $fechaFin = app_clean_text(app_post('fecha_fin', ''));

        if ($fechaInicio === '' || $fechaFin === '') {
            $messages['error'] = 'Captura fecha de inicio y fin para el periodo.';
        } else {
            $periodoIdCreado = app_get_or_create_period($conexion, $fechaInicio, $fechaFin);
            app_redirect('nomina-calculo.php?periodo_id=' . $periodoIdCreado);
        }
    }

    if ($accion === 'guardar_captura') {
        $periodoIdPost = (int)app_post('periodo_id', 0);
        $personalId = (int)app_post('personal_id', 0);
        $infonavit = (float)app_post('captura_infonavit', 0);
        $fonacot = (float)app_post('captura_fonacot', 0);
        $otro = (float)app_post('captura_otro', 0);
        $observaciones = app_clean_text(app_post('captura_observaciones', ''));

        if ($periodoIdPost <= 0 || $personalId <= 0) {
            $messages['error'] = 'Selecciona periodo y colaborador para guardar la captura.';
        } else {
            app_create_or_update_capture($conexion, $periodoIdPost, $personalId, $infonavit, $fonacot, $otro, $observaciones);
            $messages['success'] = 'Captura manual guardada para la quincena.';
        }
    }

    if ($accion === 'generar_adelanto_masivo') {
        $periodoIdPost = (int)app_post('periodo_id', 0);
        $result = app_generate_mass_advances($conexion, $periodoIdPost, $configs);
        $messages[$result['ok'] ? 'success' : 'error'] = $result['message'];
    }

    if ($accion === 'calcular_nomina') {
        $periodoIdPost = (int)app_post('periodo_id', 0);
        $result = app_calculate_nomina_period($conexion, $periodoIdPost, $configs);
        $messages[$result['ok'] ? 'success' : 'error'] = $result['message'];
    }

    if ($accion === 'cerrar_periodo') {
        $periodoIdPost = (int)app_post('periodo_id', 0);
        mysqli_query($conexion, "UPDATE nomina_periodos SET estado = 'CERRADO' WHERE id = " . $periodoIdPost);
        $messages['success'] = 'Periodo cerrado.';
    }
}

$periods = app_db_all($conexion, "SELECT * FROM nomina_periodos ORDER BY fecha_inicio DESC, id DESC");
$selectedPeriodId = (int)app_get('periodo_id', 0);
if ($selectedPeriodId <= 0) {
    if ($periods) {
        $selectedPeriodId = (int)$periods[0]['id'];
    } else {
        $bounds = app_current_period_bounds();
        $selectedPeriodId = app_get_or_create_period($conexion, $bounds['inicio'], $bounds['fin']);
        $periods = app_db_all($conexion, "SELECT * FROM nomina_periodos ORDER BY fecha_inicio DESC, id DESC");
    }
}

$selectedPeriod = app_get_period($conexion, $selectedPeriodId);
$nominaRows = $selectedPeriod ? app_nomina_rows($conexion, $selectedPeriodId) : [];
$totals = app_nomina_totals($nominaRows);
$projection = app_projected_savings_pool($conexion, $configs);
$personalRows = app_get_active_personal($conexion);
$captures = $selectedPeriodId > 0
    ? app_db_all($conexion, "SELECT * FROM nomina_capturas WHERE periodo_id = " . $selectedPeriodId . " ORDER BY personal_id ASC")
    : [];
$captureMap = [];
foreach ($captures as $capture) {
    $captureMap[(int)$capture['personal_id']] = $capture;
}

$cutoff = $selectedPeriod
    ? date(
        'Y-m-d',
        strtotime($selectedPeriod['fecha_pago'] . ' -' . app_config_int($configs, 'adelanto_masivo_dias_antes_nomina', 5) . ' days')
    )
    : date('Y-m-d');

app_render_page_start(
    'Cálculo de nómina',
    'Cálculo de nómina',
    'Preparación de quincenas, capturas manuales de Infonavit/Fonacot y cálculo consolidado.'
);
app_render_alerts($messages);
?>

<div class="row layout-top-spacing mb-3">
    <div class="col-md-3">
        <div class="card summary-card p-3">
            <div class="summary-label">Colaboradores</div>
            <div class="summary-value"><?php echo $totals['colaboradores']; ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card summary-card p-3">
            <div class="summary-label">Percepciones</div>
            <div class="summary-value"><?php echo app_money($totals['percepciones']); ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card summary-card p-3">
            <div class="summary-label">Deducciones</div>
            <div class="summary-value"><?php echo app_money($totals['deducciones']); ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card summary-card p-3">
            <div class="summary-label">Neto</div>
            <div class="summary-value"><?php echo app_money($totals['neto']); ?></div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-xl-4">
        <div class="card content-card mb-4">
            <div class="card-body">
                <h5 class="mb-3">Abrir o seleccionar quincena</h5>
                <form method="post" class="mb-4">
                    <input type="hidden" name="accion" value="crear_periodo">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Inicio</label>
                            <input type="date" name="fecha_inicio" class="form-control" value="<?php echo app_h(app_current_period_bounds()['inicio']); ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Fin</label>
                            <input type="date" name="fecha_fin" class="form-control" value="<?php echo app_h(app_current_period_bounds()['fin']); ?>">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Abrir quincena</button>
                </form>

                <div class="list-group">
                    <?php foreach ($periods as $period): ?>
                        <a
                            href="?periodo_id=<?php echo (int)$period['id']; ?>"
                            class="list-group-item list-group-item-action <?php echo $selectedPeriodId === (int)$period['id'] ? 'active' : ''; ?>"
                        >
                            <strong><?php echo app_h($period['clave']); ?></strong><br>
                            <small><?php echo app_quincena_label($period['fecha_inicio'], $period['fecha_fin']); ?> | <?php echo app_h($period['estado']); ?></small>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="card content-card">
            <div class="card-body">
                <h5 class="mb-3">Caja de ahorro</h5>
                <div class="summary-label">Disponible hoy</div>
                <div class="summary-value mb-2"><?php echo app_money($projection['current_pool']); ?></div>
                <div class="summary-label">Aportaciones proyectadas al 31 dic</div>
                <div class="mb-2"><strong><?php echo app_money($projection['projected_contributions']); ?></strong></div>
                <div class="summary-label">Intereses acumulados de préstamos</div>
                <div class="mb-2"><strong><?php echo app_money($projection['interest_pool']); ?></strong></div>
                <div class="summary-label">Proyección cierre anual</div>
                <div><strong><?php echo app_money($projection['projected_year_end']); ?></strong></div>
            </div>
        </div>
    </div>

    <div class="col-xl-8">
        <?php if ($selectedPeriod): ?>
            <div class="card content-card mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h5 class="mb-0"><?php echo app_h($selectedPeriod['clave']); ?></h5>
                            <small class="text-muted">
                                <?php echo app_quincena_label($selectedPeriod['fecha_inicio'], $selectedPeriod['fecha_fin']); ?>
                                | Pago: <?php echo app_date($selectedPeriod['fecha_pago']); ?>
                                | Estado: <?php echo app_h($selectedPeriod['estado']); ?>
                            </small>
                        </div>
                        <div class="d-flex gap-2">
                            <a href="nomina-exportacion.php?periodo_id=<?php echo (int)$selectedPeriod['id']; ?>" class="btn btn-outline-secondary">Exportar</a>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <form method="post">
                                <input type="hidden" name="accion" value="generar_adelanto_masivo">
                                <input type="hidden" name="periodo_id" value="<?php echo (int)$selectedPeriod['id']; ?>">
                                <button
                                    type="submit"
                                    class="btn btn-outline-primary w-100"
                                    <?php echo date('Y-m-d') < $cutoff || $selectedPeriod['estado'] !== 'ABIERTO' ? 'disabled' : ''; ?>
                                >
                                    Adelanto masivo de <?php echo app_money(app_config_float($configs, 'adelanto_masivo_monto', 500)); ?>
                                </button>
                                <div class="form-hint mt-1">Disponible desde <?php echo app_date($cutoff); ?></div>
                            </form>
                        </div>

                        <div class="col-md-4 mb-3">
                            <form method="post">
                                <input type="hidden" name="accion" value="calcular_nomina">
                                <input type="hidden" name="periodo_id" value="<?php echo (int)$selectedPeriod['id']; ?>">
                                <button type="submit" class="btn btn-primary w-100" <?php echo $selectedPeriod['estado'] !== 'ABIERTO' ? 'disabled' : ''; ?>>
                                    Calcular nómina
                                </button>
                            </form>
                        </div>

                        <div class="col-md-4 mb-3">
                            <form method="post">
                                <input type="hidden" name="accion" value="cerrar_periodo">
                                <input type="hidden" name="periodo_id" value="<?php echo (int)$selectedPeriod['id']; ?>">
                                <button type="submit" class="btn btn-outline-dark w-100" <?php echo $selectedPeriod['estado'] !== 'CALCULADO' ? 'disabled' : ''; ?>>
                                    Cerrar periodo
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card content-card mb-4">
                <div class="card-body">
                    <h5 class="mb-3">Capturas manuales de descuentos</h5>
                    <p class="text-muted">
                        Antes de calcular la nómina captura Infonavit, Fonacot y otros descuentos manuales tomando como base la quincena anterior.
                    </p>
                    <form method="post">
                        <input type="hidden" name="accion" value="guardar_captura">
                        <input type="hidden" name="periodo_id" value="<?php echo (int)$selectedPeriod['id']; ?>">
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Colaborador</label>
                                <select name="personal_id" class="form-select">
                                    <?php foreach ($personalRows as $personal): ?>
                                        <option value="<?php echo (int)$personal['id']; ?>">
                                            <?php echo app_h($personal['nombres'] . ' ' . $personal['apellidos']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2 mb-3">
                                <label class="form-label">Infonavit</label>
                                <input type="number" step="0.01" min="0" name="captura_infonavit" class="form-control">
                            </div>
                            <div class="col-md-2 mb-3">
                                <label class="form-label">Fonacot</label>
                                <input type="number" step="0.01" min="0" name="captura_fonacot" class="form-control">
                            </div>
                            <div class="col-md-2 mb-3">
                                <label class="form-label">Otro</label>
                                <input type="number" step="0.01" min="0" name="captura_otro" class="form-control">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Observaciones</label>
                                <input type="text" name="captura_observaciones" class="form-control">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-outline-primary">Guardar captura</button>
                    </form>

                    <div class="table-responsive mt-4">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Colaborador</th>
                                    <th>Infonavit</th>
                                    <th>Fonacot</th>
                                    <th>Otro</th>
                                    <th>Observaciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($personalRows as $personal): ?>
                                    <?php $capture = $captureMap[(int)$personal['id']] ?? null; ?>
                                    <tr>
                                        <td><?php echo app_h($personal['nombres'] . ' ' . $personal['apellidos']); ?></td>
                                        <td><?php echo app_money($capture['descuento_infonavit'] ?? $personal['monto_infonavit_quincenal']); ?></td>
                                        <td><?php echo app_money($capture['descuento_fonacot'] ?? $personal['monto_fonacot_quincenal']); ?></td>
                                        <td><?php echo app_money($capture['descuento_manual_otro'] ?? 0); ?></td>
                                        <td><?php echo app_h($capture['observaciones'] ?? ''); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card content-card">
                <div class="card-body">
                    <h5 class="mb-3">Resultado del cálculo</h5>
                    <input type="search" class="form-control mb-3" data-table-search="#nomina-resultado-table" placeholder="Buscar colaborador, No. empleado o concepto...">
                    <div class="table-responsive">
                        <table class="table table-striped" id="nomina-resultado-table">
                            <thead>
                                <tr>
                                    <th>Colaborador</th>
                                    <th>No. empleado</th>
                                    <th>Base</th>
                                    <th>Retardos</th>
                                    <th>Extras</th>
                                    <th>Vacaciones</th>
                                    <th>Descansos</th>
                                    <th>Deducciones</th>
                                    <th>Neto</th>
                                    <th>Recibo</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($nominaRows as $row): ?>
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
                                        <td><?php echo app_money($row['descuento_retardos']); ?></td>
                                        <td><?php echo app_money($extras); ?></td>
                                        <td><?php echo app_money($vacaciones); ?></td>
                                        <td><?php echo app_money($row['descuentos_descansos'] ?? 0); ?></td>
                                        <td><?php echo app_money($deducciones); ?></td>
                                        <td><strong><?php echo app_money($row['neto']); ?></strong></td>
                                        <td>
                                            <a
                                                href="nomina-recibo.php?periodo_id=<?php echo (int)$selectedPeriod['id']; ?>&personal_id=<?php echo (int)$row['personal_id']; ?>"
                                                class="btn btn-outline-primary btn-sm"
                                                target="_blank"
                                            >
                                                Recibo
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (!$nominaRows): ?>
                                    <tr>
                                        <td colspan="10" class="text-center text-muted">
                                            Aún no hay cálculo para esta quincena. Guarda capturas y ejecuta el cálculo.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
app_render_page_end();
