<?php

require_once __DIR__ . '/lib/app.php';
require_once __DIR__ . '/lib/ui.php';
require_once __DIR__ . '/lib/payroll.php';

app_require_session();
app_require_roles(['ADMIN', 'SUPERVISOR', 'NOMINA', 'DUEÑO']);

$messages = ['success' => '', 'error' => ''];
$configs = app_get_config_map($conexion);

$sitios = app_db_all($conexion, "SELECT id, nombre FROM sitios WHERE esta_activo = 1 ORDER BY nombre");
$guardias = app_db_all(
    $conexion,
    "SELECT p.id, p.fecha_contratacion, p.nombres, p.apellidos
     FROM personal p
     INNER JOIN usuarios u ON u.id = p.usuario_id
     INNER JOIN roles r ON r.id = u.rol_id
     WHERE p.estado = 'ACTIVO'
       AND r.nombre = 'GUARDIA'
     ORDER BY p.nombres, p.apellidos"
);

$editingShiftId = (int)app_get('editar_turno', 0);
$editingShift = $editingShiftId > 0
    ? app_db_one($conexion, "SELECT * FROM turnos WHERE id = " . $editingShiftId . " LIMIT 1")
    : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = app_post('accion', '');

    if ($accion === 'guardar_turno_extra') {
        $turnoId = (int)app_post('turno_id', 0);
        $sitioId = (int)app_post('sitio_id', 0);
        $personalId = (int)app_post('personal_id', 0);
        $inicio = app_clean_text(app_post('hora_inicio', ''));
        $tipo = app_clean_text(app_post('tipo_turno_extra', 'MEDIO'));
        $horas = (float)app_post('horas_programadas', 0);
        $montoManual = app_clean_text(app_post('monto_turno_extra', ''));

        if ($tipo === 'MEDIO') {
            $horas = 12.0;
        } elseif ($tipo === 'COMPLETO') {
            $horas = 24.0;
        } elseif ($horas <= 0) {
            $horas = 12.0;
        }

        $monto = $montoManual === '' ? 0.0 : (float)$montoManual;
        if ($monto <= 0) {
            if ($tipo === 'MEDIO') {
                $monto = app_config_float($configs, 'turnos_extra_12h_monto', 400.0);
            } elseif ($tipo === 'COMPLETO') {
                $monto = app_config_float($configs, 'turnos_extra_24h_monto', 800.0);
            } else {
                $monto = round($horas * app_config_float($configs, 'nomina_valor_hora', 75.0), 2);
            }
        }

        if ($sitioId <= 0 || $personalId <= 0 || $inicio === '') {
            $messages['error'] = 'Captura sitio, guardia y fecha de inicio para el turno extra.';
        } else {
            $inicioSql = date('Y-m-d H:i:s', strtotime($inicio));
            $finSql = date('Y-m-d H:i:s', strtotime($inicio . ' +' . $horas . ' hours'));
            $supervisorId = (int)($_SESSION['usuario_id'] ?? 0);

            if ($turnoId > 0) {
                $sql = "
                    UPDATE turnos
                    SET sitio_id = ?,
                        personal_id = ?,
                        supervisor_id = ?,
                        hora_inicio = ?,
                        hora_fin = ?,
                        horas_programadas = ?,
                        es_turno_extra = 1,
                        tipo_turno_extra = ?,
                        monto_turno_extra = ?,
                        estado = 'PROGRAMADO'
                    WHERE id = ?
                ";
                if ($stmt = mysqli_prepare($conexion, $sql)) {
                    mysqli_stmt_bind_param($stmt, 'iiissdsdi', $sitioId, $personalId, $supervisorId, $inicioSql, $finSql, $horas, $tipo, $monto, $turnoId);
                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_close($stmt);
                    $messages['success'] = 'Turno extra actualizado.';
                }
            } else {
                $sql = "
                    INSERT INTO turnos
                        (sitio_id, personal_id, supervisor_id, hora_inicio, hora_fin, horas_programadas, es_turno_extra, tipo_turno_extra, monto_turno_extra, estado)
                    VALUES (?, ?, ?, ?, ?, ?, 1, ?, ?, 'PROGRAMADO')
                ";
                if ($stmt = mysqli_prepare($conexion, $sql)) {
                    mysqli_stmt_bind_param($stmt, 'iiissdsd', $sitioId, $personalId, $supervisorId, $inicioSql, $finSql, $horas, $tipo, $monto);
                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_close($stmt);
                    $messages['success'] = 'Turno extra registrado.';
                }
            }
        }
    }

    if ($accion === 'guardar_ajuste_horas') {
        $personalId = (int)app_post('ajuste_personal_id', 0);
        $tipo = app_clean_text(app_post('tipo_ajuste', 'BONO'));
        $horas = (float)app_post('horas_ajuste', 0);
        $fecha = app_clean_text(app_post('fecha_ajuste', date('Y-m-d')));
        $motivo = app_clean_text(app_post('motivo_ajuste', ''));
        $supervisorId = (int)($_SESSION['usuario_id'] ?? 0);

        if ($personalId <= 0 || $horas <= 0 || $motivo === '' || !in_array($tipo, ['BONO', 'HORA_MENOS'], true)) {
            $messages['error'] = 'Captura guardia, tipo, horas y motivo para el ajuste.';
        } else {
            $sql = "
                INSERT INTO ajustes_nomina
                    (personal_id, supervisor_id, tipo_ajuste, monto, horas, motivo, fecha_aplicacion)
                VALUES (?, ?, ?, NULL, ?, ?, ?)
            ";
            if ($stmt = mysqli_prepare($conexion, $sql)) {
                mysqli_stmt_bind_param($stmt, 'iisdss', $personalId, $supervisorId, $tipo, $horas, $motivo, $fecha);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
                $messages['success'] = 'Ajuste de horas guardado.';
            }
        }
    }
}

$turnosExtra = app_db_all(
    $conexion,
    "SELECT
        t.*,
        p.id AS personal_id,
        p.fecha_contratacion,
        s.nombre AS sitio_nombre,
        CONCAT(p.nombres, ' ', p.apellidos) AS guardia
     FROM turnos t
     INNER JOIN sitios s ON s.id = t.sitio_id
     INNER JOIN personal p ON p.id = t.personal_id
     WHERE t.es_turno_extra = 1
     ORDER BY t.hora_inicio DESC
     LIMIT 50"
);

$ajustes = app_db_all(
    $conexion,
    "SELECT
        aj.*,
        p.id AS personal_id,
        p.fecha_contratacion,
        CONCAT(p.nombres, ' ', p.apellidos) AS guardia
     FROM ajustes_nomina aj
     INNER JOIN personal p ON p.id = aj.personal_id
     WHERE aj.tipo_ajuste IN ('BONO', 'HORA_MENOS')
     ORDER BY aj.fecha_aplicacion DESC, aj.id DESC
     LIMIT 50"
);

app_render_page_start(
    'Turnos extra y horas extra',
    'Turnos extra y horas extra',
    'Registro de medios turnos, turnos completos y ajustes de horas con importes configurables.'
);
app_render_alerts($messages);
?>

<div class="row layout-top-spacing mb-3">
    <div class="col-md-4">
        <div class="card summary-card p-3">
            <div class="summary-label">Turno extra 12h</div>
            <div class="summary-value"><?php echo app_money(app_config_float($configs, 'turnos_extra_12h_monto', 400)); ?></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card summary-card p-3">
            <div class="summary-label">Turno extra 24h</div>
            <div class="summary-value"><?php echo app_money(app_config_float($configs, 'turnos_extra_24h_monto', 800)); ?></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card summary-card p-3">
            <div class="summary-label">Hora operativa</div>
            <div class="summary-value"><?php echo app_money(app_config_float($configs, 'nomina_valor_hora', 75)); ?></div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-xl-6">
        <div class="card content-card mb-4">
            <div class="card-body">
                <h5 class="mb-3"><?php echo $editingShift ? 'Editar turno extra' : 'Registrar turno extra'; ?></h5>
                <form method="post">
                    <input type="hidden" name="accion" value="guardar_turno_extra">
                    <input type="hidden" name="turno_id" value="<?php echo (int)($editingShift['id'] ?? 0); ?>">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Guardia</label>
                            <select name="personal_id" class="form-select" required>
                                <option value="">Selecciona</option>
                                <?php foreach ($guardias as $guardia): ?>
                                    <option value="<?php echo (int)$guardia['id']; ?>" <?php echo (int)($editingShift['personal_id'] ?? 0) === (int)$guardia['id'] ? 'selected' : ''; ?>>
                                        <?php echo app_h(app_employee_number($guardia) . ' - ' . $guardia['nombres'] . ' ' . $guardia['apellidos']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Sitio</label>
                            <select name="sitio_id" class="form-select" required>
                                <option value="">Selecciona</option>
                                <?php foreach ($sitios as $sitio): ?>
                                    <option value="<?php echo (int)$sitio['id']; ?>" <?php echo (int)($editingShift['sitio_id'] ?? 0) === (int)$sitio['id'] ? 'selected' : ''; ?>>
                                        <?php echo app_h($sitio['nombre']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Inicio</label>
                            <input
                                type="datetime-local"
                                name="hora_inicio"
                                class="form-control"
                                value="<?php echo $editingShift ? app_h(date('Y-m-d\TH:i', strtotime($editingShift['hora_inicio']))) : ''; ?>"
                                required
                            >
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Tipo</label>
                            <select name="tipo_turno_extra" class="form-select">
                                <option value="MEDIO" <?php echo ($editingShift['tipo_turno_extra'] ?? '') === 'MEDIO' ? 'selected' : ''; ?>>Medio turno</option>
                                <option value="COMPLETO" <?php echo ($editingShift['tipo_turno_extra'] ?? '') === 'COMPLETO' ? 'selected' : ''; ?>>Turno completo</option>
                                <option value="PERSONALIZADO" <?php echo ($editingShift['tipo_turno_extra'] ?? '') === 'PERSONALIZADO' ? 'selected' : ''; ?>>Personalizado</option>
                            </select>
                        </div>
                        <div class="col-md-2 mb-3">
                            <label class="form-label">Horas</label>
                            <input type="number" step="0.5" min="1" name="horas_programadas" class="form-control" value="<?php echo app_h($editingShift['horas_programadas'] ?? 12); ?>">
                        </div>
                        <div class="col-md-2 mb-3">
                            <label class="form-label">Monto</label>
                            <input type="number" step="0.01" min="0" name="monto_turno_extra" class="form-control" value="<?php echo app_h($editingShift['monto_turno_extra'] ?? ''); ?>">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary"><?php echo $editingShift ? 'Actualizar turno' : 'Registrar turno'; ?></button>
                    <?php if ($editingShift): ?>
                        <a href="turnos-extras.php" class="btn btn-outline-secondary">Cancelar edición</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>

    <div class="col-xl-6">
        <div class="card content-card mb-4">
            <div class="card-body">
                <h5 class="mb-3">Ajuste de horas</h5>
                <p class="text-muted">
                    Usa <strong>Bono</strong> para pagar horas extra desde una hora, y <strong>Hora menos</strong> para descuentos operativos bajo la misma tarifa por hora.
                </p>
                <form method="post">
                    <input type="hidden" name="accion" value="guardar_ajuste_horas">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Guardia</label>
                            <select name="ajuste_personal_id" class="form-select">
                                <?php foreach ($guardias as $guardia): ?>
                                    <option value="<?php echo (int)$guardia['id']; ?>">
                                        <?php echo app_h(app_employee_number($guardia) . ' - ' . $guardia['nombres'] . ' ' . $guardia['apellidos']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Tipo</label>
                            <select name="tipo_ajuste" class="form-select">
                                <option value="BONO">Bono</option>
                                <option value="HORA_MENOS">Hora menos</option>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Horas</label>
                            <input type="number" step="0.5" min="0.5" name="horas_ajuste" class="form-control">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Fecha</label>
                            <input type="date" name="fecha_ajuste" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="col-md-8 mb-3">
                            <label class="form-label">Motivo</label>
                            <input type="text" name="motivo_ajuste" class="form-control">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-outline-primary">Guardar ajuste</button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-xl-7">
        <div class="card content-card">
            <div class="card-body">
                <h5 class="mb-3">Turnos extra recientes</h5>
                <input type="search" class="form-control mb-3" data-table-search="#turnos-extra-table" placeholder="Buscar guardia, No. empleado, sitio o monto...">
                <div class="table-responsive">
                    <table class="table table-striped" id="turnos-extra-table">
                        <thead>
                            <tr>
                                <th>No. empleado</th>
                                <th>Guardia</th>
                                <th>Sitio</th>
                                <th>Inicio</th>
                                <th>Horas</th>
                                <th>Tipo</th>
                                <th>Monto</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($turnosExtra as $turno): ?>
                                <tr>
                                    <td><?php echo app_h(app_employee_number($turno)); ?></td>
                                    <td><?php echo app_h($turno['guardia']); ?></td>
                                    <td><?php echo app_h($turno['sitio_nombre']); ?></td>
                                    <td><?php echo app_datetime($turno['hora_inicio']); ?></td>
                                    <td><?php echo app_number($turno['horas_programadas']); ?></td>
                                    <td><?php echo app_h($turno['tipo_turno_extra']); ?></td>
                                    <td><?php echo app_money($turno['monto_turno_extra']); ?></td>
                                    <td class="text-end">
                                        <a href="?editar_turno=<?php echo (int)$turno['id']; ?>" class="btn btn-outline-secondary btn-sm">Editar</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (!$turnosExtra): ?>
                                <tr><td colspan="8" class="text-center text-muted">No hay turnos extra registrados.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-5">
        <div class="card content-card">
            <div class="card-body">
                <h5 class="mb-3">Ajustes recientes</h5>
                <input type="search" class="form-control mb-3" data-table-search="#ajustes-horas-table" placeholder="Buscar guardia, No. empleado, tipo o motivo...">
                <div class="table-responsive">
                    <table class="table table-sm" id="ajustes-horas-table">
                        <thead>
                            <tr>
                                <th>No. empleado</th>
                                <th>Guardia</th>
                                <th>Tipo</th>
                                <th>Horas</th>
                                <th>Fecha</th>
                                <th>Motivo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ajustes as $ajuste): ?>
                                <tr>
                                    <td><?php echo app_h(app_employee_number($ajuste)); ?></td>
                                    <td><?php echo app_h($ajuste['guardia']); ?></td>
                                    <td><?php echo app_h($ajuste['tipo_ajuste']); ?></td>
                                    <td><?php echo app_number($ajuste['horas']); ?></td>
                                    <td><?php echo app_date($ajuste['fecha_aplicacion']); ?></td>
                                    <td><?php echo app_h($ajuste['motivo']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (!$ajustes): ?>
                                <tr><td colspan="6" class="text-center text-muted">No hay ajustes registrados.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
app_render_page_end();
