<?php

require_once __DIR__ . '/lib/app.php';
require_once __DIR__ . '/lib/ui.php';
require_once __DIR__ . '/lib/payroll.php';

app_require_session();
app_require_roles(['ADMIN', 'RH', 'NOMINA', 'DUEÑO']);

$messages = ['success' => '', 'error' => ''];

$configGroups = [
    'Turnos y retardos' => [
        'turnos_tolerancia_minutos' => 'Tolerancia en minutos',
        'turnos_max_retardo_horas' => 'Máximo de horas antes de cancelar turno',
        'nomina_valor_hora' => 'Valor por hora para retardos y horas extra',
        'turnos_extra_12h_monto' => 'Monto por turno extra de 12 horas',
        'turnos_extra_24h_monto' => 'Monto por turno extra de 24 horas',
        'nomina_factor_dia_festivo' => 'Factor de pago en día festivo',
    ],
    'Prestamos y caja' => [
        'prestamos_tasa_quincenal' => 'Tasa quincenal sobre saldo insoluto (%)',
        'prestamos_monto_max_antiguedad_baja' => 'Tope préstamo hasta 1 año',
        'prestamos_monto_max_antiguedad_alta' => 'Tope préstamo mayor a 1 año',
        'prestamos_mes_inicio' => 'Mes de arranque de préstamos',
        'prestamos_dia_inicio' => 'Día de arranque de préstamos',
        'prestamos_mes_limite' => 'Mes límite de pago',
        'prestamos_dia_limite' => 'Día límite de pago',
        'caja_pago_desde_dia' => 'Día inicial de entrega anual de caja',
        'caja_pago_hasta_dia' => 'Día final de entrega anual de caja',
    ],
    'Vacaciones, faltas y adelantos' => [
        'vacaciones_dias_anio_1' => 'Días de vacaciones del año 1',
        'vacaciones_dias_anio_2' => 'Días de vacaciones del año 2',
        'vacaciones_dias_anio_3' => 'Días de vacaciones del año 3',
        'vacaciones_dias_anio_4' => 'Días de vacaciones del año 4',
        'vacaciones_dias_anio_5' => 'Días de vacaciones del año 5',
        'vacaciones_dias_anios_6_10' => 'Días por año cumplido del 6 al 10',
        'vacaciones_dias_anios_11_15' => 'Días por año cumplido del 11 al 15',
        'vacaciones_dias_anios_16_20' => 'Días por año cumplido del 16 al 20',
        'vacaciones_dias_anios_21_25' => 'Días por año cumplido del 21 al 25',
        'vacaciones_dias_anios_26_30' => 'Días por año cumplido del 26 en adelante',
        'vacaciones_incremento_anual' => 'Incremento anual heredado (compatibilidad)',
        'vacaciones_prima_porcentaje' => 'Prima vacacional (%)',
        'faltas_descuento_justificada' => 'Descuento falta justificada',
        'faltas_descuento_injustificada' => 'Descuento falta injustificada',
        'faltas_descuento_ajustada' => 'Descuento falta ajustada',
        'descansos_descuento_sin_goce' => 'Descuento descanso sin goce',
        'adelanto_masivo_monto' => 'Monto del adelanto masivo',
        'adelanto_masivo_dias_antes_nomina' => 'Días antes de nómina para adelanto masivo',
        'material_quincenas_default' => 'Quincenas por defecto para material',
        'nomina_salario_minimo_diario' => 'Salario diario operativo por defecto',
    ],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = app_post('accion', '');

    if ($accion === 'guardar_configuracion') {
        foreach ($configGroups as $keys) {
            foreach ($keys as $clave => $label) {
                $valor = app_clean_text(app_post($clave, ''));
                $descripcion = $label;
                $sql = "
                    INSERT INTO configuracion_sistema (clave_configuracion, valor_configuracion, descripcion)
                    VALUES (?, ?, ?)
                    ON DUPLICATE KEY UPDATE
                        valor_configuracion = VALUES(valor_configuracion),
                        descripcion = VALUES(descripcion)
                ";
                if ($stmt = mysqli_prepare($conexion, $sql)) {
                    mysqli_stmt_bind_param($stmt, 'sss', $clave, $valor, $descripcion);
                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_close($stmt);
                }
            }
        }

        $messages['success'] = 'Parámetros globales actualizados.';
    }

    if ($accion === 'agregar_festivo') {
        $fecha = app_clean_text(app_post('fecha_festivo', ''));
        $nombre = app_clean_text(app_post('nombre_festivo', ''));
        $factor = (float)app_post('factor_festivo', 2);

        if ($fecha === '' || $nombre === '') {
            $messages['error'] = 'La fecha y el nombre del día festivo son obligatorios.';
        } else {
            $sql = "
                INSERT INTO dias_festivos (fecha, nombre, pago_factor)
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    nombre = VALUES(nombre),
                    pago_factor = VALUES(pago_factor)
            ";
            if ($stmt = mysqli_prepare($conexion, $sql)) {
                mysqli_stmt_bind_param($stmt, 'ssd', $fecha, $nombre, $factor);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
                $messages['success'] = 'Día festivo guardado correctamente.';
            } else {
                $messages['error'] = 'No fue posible guardar el día festivo.';
            }
        }
    }

    if ($accion === 'distribuir_intereses') {
        $year = max(2024, (int)app_post('anio_intereses', date('Y')));
        $fechaAplicacion = app_clean_text(app_post('fecha_intereses', $year . '-12-16'));
        $result = app_distribute_savings_interest($conexion, $year, $configs, $fechaAplicacion);
        $messages[$result['ok'] ? 'success' : 'error'] = $result['message'];
    }
}

if (isset($_GET['del_festivo'])) {
    $festivoId = (int)$_GET['del_festivo'];
    if ($festivoId > 0) {
        mysqli_query($conexion, "DELETE FROM dias_festivos WHERE id = " . $festivoId);
        $messages['success'] = 'Día festivo eliminado.';
    }
}

$configs = app_get_config_map($conexion);
$holidays = app_db_all($conexion, "SELECT * FROM dias_festivos ORDER BY fecha ASC");
$projection = app_projected_savings_pool($conexion, $configs);

app_render_page_start(
    'Configuración global',
    'Configuración global',
    'Reglas operativas de nómina, retardos, préstamos, caja de ahorro y días festivos.'
);
app_render_alerts($messages);
?>

<div class="row layout-top-spacing mb-3">
    <div class="col-md-3">
        <div class="card summary-card p-3">
            <div class="summary-label">Reglas activas</div>
            <div class="summary-value"><?php echo count($configs); ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card summary-card p-3">
            <div class="summary-label">Días festivos</div>
            <div class="summary-value"><?php echo count($holidays); ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card summary-card p-3">
            <div class="summary-label">Caja actual</div>
            <div class="summary-value"><?php echo app_money($projection['current_pool']); ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card summary-card p-3">
            <div class="summary-label">Caja proyectada 31 dic</div>
            <div class="summary-value"><?php echo app_money($projection['projected_year_end']); ?></div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-xl-8">
        <div class="card content-card">
            <div class="card-body">
                <h5 class="mb-3">Parámetros operativos</h5>
                <form method="post">
                    <input type="hidden" name="accion" value="guardar_configuracion">

                    <?php foreach ($configGroups as $groupTitle => $fields): ?>
                        <div class="mb-4">
                            <h6 class="mb-3"><?php echo app_h($groupTitle); ?></h6>
                            <div class="row">
                                <?php foreach ($fields as $key => $label): ?>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label"><?php echo app_h($label); ?></label>
                                        <input
                                            type="text"
                                            name="<?php echo app_h($key); ?>"
                                            class="form-control"
                                            value="<?php echo app_h($configs[$key] ?? ''); ?>"
                                        >
                                        <div class="form-hint"><?php echo app_h($key); ?></div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary">Guardar parámetros</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="card content-card mb-4">
            <div class="card-body">
                <h5 class="mb-3">Agregar día festivo</h5>
                <form method="post">
                    <input type="hidden" name="accion" value="agregar_festivo">

                    <div class="mb-3">
                        <label class="form-label">Fecha</label>
                        <input type="date" name="fecha_festivo" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Nombre</label>
                        <input type="text" name="nombre_festivo" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Factor de pago</label>
                        <input type="number" name="factor_festivo" class="form-control" step="0.01" min="1" value="2">
                    </div>

                    <button type="submit" class="btn btn-outline-primary w-100">Guardar festivo</button>
                </form>
            </div>
        </div>

        <div class="card content-card">
            <div class="card-body">
                <h5 class="mb-3">Calendario festivo</h5>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Nombre</th>
                                <th>Factor</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($holidays as $holiday): ?>
                                <tr>
                                    <td><?php echo app_date($holiday['fecha']); ?></td>
                                    <td><?php echo app_h($holiday['nombre']); ?></td>
                                    <td><?php echo app_number($holiday['pago_factor']); ?></td>
                                    <td class="text-end">
                                        <a
                                            href="?del_festivo=<?php echo (int)$holiday['id']; ?>"
                                            class="btn btn-outline-danger btn-sm"
                                            onclick="return confirm('¿Eliminar este día festivo?');"
                                        >
                                            Eliminar
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (!$holidays): ?>
                                <tr>
                                    <td colspan="4" class="text-center text-muted">No hay festivos capturados.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card content-card mt-4">
            <div class="card-body">
                <h5 class="mb-3">Distribución anual de intereses</h5>
                <p class="text-muted">
                    Reparte los intereses generados por préstamos entre quienes ahorraron en la caja.
                    El sistema evita duplicados por año y solo permite aplicarlo después del 16 de diciembre.
                </p>
                <form method="post">
                    <input type="hidden" name="accion" value="distribuir_intereses">
                    <div class="mb-3">
                        <label class="form-label">Año</label>
                        <input type="number" min="2024" name="anio_intereses" class="form-control" value="<?php echo date('Y'); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Fecha de aplicación</label>
                        <input type="date" name="fecha_intereses" class="form-control" value="<?php echo date('Y') . '-12-16'; ?>">
                    </div>
                    <button type="submit" class="btn btn-outline-dark w-100">Distribuir intereses</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
app_render_page_end();
