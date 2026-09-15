<?php

require_once __DIR__ . '/lib/app.php';
require_once __DIR__ . '/lib/ui.php';
require_once __DIR__ . '/lib/payroll.php';

app_require_session();
app_require_roles(['ADMIN', 'RH', 'NOMINA', 'DUEÑO']);

$messages = ['success' => '', 'error' => ''];
$messages['success'] = (string)($_SESSION['nomina_calculo_previo_success'] ?? '');
unset($_SESSION['nomina_calculo_previo_success']);
$periodId = (int)app_get('periodo_id', 0);
if ($periodId <= 0) {
    $latest = app_db_one($conexion, "SELECT id FROM nomina_periodos ORDER BY fecha_inicio DESC, id DESC LIMIT 1");
    $periodId = (int)($latest['id'] ?? 0);
}

$editId = (int)app_get('editar_id', 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = app_post('accion', '');

    if ($accion === 'confirmar_nomina_previa') {
        $registroId = (int)app_post('registro_id', 0);
        $periodoIdPost = (int)app_post('periodo_id', 0);

        if ($registroId > 0 && $periodoIdPost > 0) {
            $stmt = mysqli_prepare($conexion, 'UPDATE nomina_calculo SET estatus = ? WHERE id = ? AND periodo_id = ?');
            if ($stmt) {
                $estatus = 'CONFIRMADO';
                mysqli_stmt_bind_param($stmt, 'sii', $estatus, $registroId, $periodoIdPost);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
                $messages['success'] = 'Registro confirmado correctamente.';
            }
        }
    }

    if ($accion === 'recalcular_nomina_colaborador') {
        $periodoIdPost = (int)app_post('periodo_id', 0);
        $personaIdPost = (int)app_post('persona_id', 0);

        if ($periodoIdPost <= 0 || $personaIdPost <= 0) {
            $messages['error'] = 'No fue posible identificar el periodo y colaborador seleccionados.';
        } else {
            $colaboradorPeriodo = app_db_one(
                $conexion,
                'SELECT id FROM nomina_calculo WHERE periodo_id = ' . $periodoIdPost
                    . ' AND persona_id = ' . $personaIdPost . ' LIMIT 1'
            );

            if (!$colaboradorPeriodo) {
                $messages['error'] = 'El colaborador no tiene un registro en el periodo seleccionado.';
            } else {
                $recalculateCall = mysqli_query(
                    $conexion,
                    'CALL ObtenerListaAsistenciaResumenGuardias(' . $periodoIdPost . ',' . $personaIdPost . ')'
                );

                if ($recalculateCall === false) {
                    $messages['error'] = 'No fue posible recalcular la nómina del colaborador.';
                } else {
                    if ($recalculateCall instanceof mysqli_result) {
                        mysqli_free_result($recalculateCall);
                    }
                    while (mysqli_more_results($conexion)) {
                        mysqli_next_result($conexion);
                    }

                    $_SESSION['nomina_calculo_previo_success'] = 'La nómina del colaborador se recalculó correctamente.';
                    app_redirect('nomina_calculo_previo.php?periodo_id=' . $periodoIdPost);
                }
            }
        }
    }

    if ($accion === 'confirmar_cerrar_periodo') {
        $periodoIdPost = (int)app_post('periodo_id', 0);
        $periodoCerrar = $periodoIdPost > 0
            ? app_db_one($conexion, 'SELECT estado FROM nomina_periodos WHERE id = ' . $periodoIdPost)
            : null;

        if (!$periodoCerrar) {
            $messages['error'] = 'El periodo seleccionado no existe.';
        } elseif (($periodoCerrar['estado'] ?? '') === 'CERRADO') {
            $messages['error'] = 'El periodo seleccionado ya está cerrado.';
        } else {
            $totalRegistros = app_db_one(
                $conexion,
                'SELECT COUNT(*) AS total FROM nomina_calculo WHERE periodo_id = ' . $periodoIdPost
            );

            if ((int)($totalRegistros['total'] ?? 0) === 0) {
                $messages['error'] = 'No hay registros de nómina para cerrar en este periodo.';
            } elseif (mysqli_begin_transaction($conexion)) {
                $confirmados = mysqli_query(
                    $conexion,
                    "UPDATE nomina_calculo SET estatus = 'CONFIRMADO' WHERE periodo_id = " . $periodoIdPost
                );

                $result = app_calculate_nomina_period($conexion, $periodoIdPost);
                $cerrado = $confirmados !== false
                    ? mysqli_query(
                        $conexion,
                        "UPDATE nomina_periodos SET estado = 'CERRADO' WHERE id = " . $periodoIdPost
                    )
                    : false;

                if ($confirmados !== false && $cerrado !== false) {
                    mysqli_commit($conexion);
                    $_SESSION['nomina_calculo_previo_success'] = 'Todos los registros fueron confirmados y el periodo se cerró correctamente.';
                    app_redirect('nomina_calculo_previo.php?periodo_id=' . $periodoIdPost);
                }

                mysqli_rollback($conexion);
                $messages['error'] = 'No fue posible confirmar los registros y cerrar el periodo.';
            } else {
                $messages['error'] = 'No fue posible iniciar la operación de cierre del periodo.';
            }
        }
    }

    if ($accion === 'guardar_nomina_previa') {
        $registroId = (int)app_post('registro_id', 0);
        $periodoIdPost = (int)app_post('periodo_id', 0);

        if ($registroId > 0 && $periodoIdPost > 0) {
            $currentRegistro = app_db_one($conexion, 'SELECT * FROM nomina_calculo WHERE id = ' . $registroId . ' AND periodo_id = ' . $periodoIdPost);
            $salarioHora = (float)app_post('salario_por_hora', (float)($currentRegistro['salario_por_hora'] ?? $currentRegistro['salario_hora'] ?? 0));
            $nuevasHorasRetardo = (float)app_post(
                'nuevas_horas_retardo',
                app_post('horas_retardo', (float)($currentRegistro['horas_retardo'] ?? 0))
            );
            $nuevodescHorasRetardo = $nuevasHorasRetardo * $salarioHora;
            $nuevashorasExtra = (float)app_post(
                'nuevas_horas_extra',
                app_post('horas_extra', (float)($currentRegistro['horas_extra'] ?? 0))
            );
            $nuevopagoHrsExtra = $nuevashorasExtra * $salarioHora;
            $nuevasdeducciones = (float)app_post(
                'nuevas_deducciones',
                app_post('deducciones', (float)($currentRegistro['deducciones'] ?? 0))
            );
            
            $nuevosPagoExtra = (float)app_post(
                'nuevos_pagos_extras',
                app_post('pagos_extras', (float)($currentRegistro['pagos_extras'] ?? 0))
            );

            $salarioDepositoOriginal = (float)($currentRegistro['salario_Deposito'] ?? 0);
            $ajusteSalarioDeposito = (float)app_post(
                'ajuste_salario_deposito',
                (float)($currentRegistro['ajustes'] ?? 0)
            );
            $nuevoAjusteSalarioDeposito = (float)app_post(
                'nuevo_ajuste_salario_deposito',
                $ajusteSalarioDeposito
            );
            $comentarios = app_clean_text(app_post('comentarios', ''));
            $salarioNetoBase = $salarioDepositoOriginal
                + (float)($currentRegistro['desc_horas_retardo'] ?? $currentRegistro['desc_hrs_tarde'] ?? 0)
                - $nuevodescHorasRetardo
                - (float)($currentRegistro['pago_hrs_extra'] ?? 0)
                + $nuevopagoHrsExtra
                - $ajusteSalarioDeposito
                + $nuevoAjusteSalarioDeposito;
            $nuevoSalarioNeto = $salarioNetoBase;
            
            $deduccionesOriginal = (float)($currentRegistro['deducciones'] ?? 0);
            $descHorasRetardoOriginal = (float)($currentRegistro['desc_horas_retardo'] ?? 0);
            $pagosextrasOriginal = (float)($currentRegistro['pagos_extras'] ?? 0);
            $salarionetooriginal =  (float)($currentRegistro['salario_Deposito'] ?? 0);
           // $nuevoSalarioNeto = $salarionetooriginal
           
    

            $fields = [
                'horas_retardo' => $nuevasHorasRetardo,
                'desc_hrs_tarde' => $nuevodescHorasRetardo,
                'horas_extra' => $nuevashorasExtra,
                'pago_hrs_extra' => $nuevopagoHrsExtra,
                'pagos_extras' => $nuevosPagoExtra,
                'deducciones' => $nuevasdeducciones,
                'ajustes' => $nuevoAjusteSalarioDeposito,
                'salario_Deposito' => $nuevoSalarioNeto,
                'comentarios' => $comentarios,
            ];

            

            $setClauses = [];
            $params = [];
            $types = '';
            foreach ($fields as $field => $value) {
                $setClauses[] = $field . ' = ?';
                if ($field === 'comentarios') {
                    $types .= 's';
                    $params[] = (string)$value;
                } else {
                    $types .= 'd';
                    $params[] = (float)$value;
                }
            }
            $params[] = $registroId;
            $params[] = $periodoIdPost;
            $types .= 'ii';

            $sql = 'UPDATE nomina_calculo SET ' . implode(', ', $setClauses) . ' WHERE id = ? AND periodo_id = ?';
            $stmt = mysqli_prepare($conexion, $sql);
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, $types, ...$params);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
                $_SESSION['nomina_calculo_previo_success'] = 'La actualización del registro se realizó con éxito.';
                app_redirect('nomina_calculo_previo.php?periodo_id=' . $periodoIdPost);
            }
        }
    }
}

$period = $periodId > 0 ? app_get_period($conexion, $periodId) : null;
$rows = [];
if ($period) {
    $seedCall = mysqli_query($conexion, 'CALL ObtenerListaAsistenciaResumenGuardias(' . $periodId . ',0)');
    if ($seedCall === false) {
        $messages['error'] = 'No fue posible ejecutar el cálculo previo de la nómina.';
    } else {
        while (mysqli_more_results($conexion)) {
            mysqli_next_result($conexion);
        }
    }

    $previewCall = mysqli_query($conexion, 'CALL get_nomina_calculo_previo(' . $periodId . ')');
    if ($previewCall !== false) {
        do {
            if ($result = mysqli_store_result($conexion)) {
                $rows = mysqli_fetch_all($result, MYSQLI_ASSOC);
                mysqli_free_result($result);
            }
        } while (mysqli_more_results($conexion) && mysqli_next_result($conexion));
    }

    if (!$rows) {
        $rows = app_db_all($conexion, "SELECT n.*, p.nombres AS nombres, p.apellidos AS apellidos, r.nombre AS rol_nombre FROM nomina_calculo n LEFT JOIN personal p ON p.id = n.persona_id LEFT JOIN roles r ON r.id = n.rol_id WHERE n.periodo_id = " . $periodId . " ORDER BY n.persona_id ASC");
    }
}

$personaCache = [];
$rolCache = [];
$rows = array_map(function ($row) use ($conexion, &$personaCache, &$rolCache) {
    $row['nombres'] = $row['nombres'] ?? $row['Nombre'] ?? $row['nombre'] ?? $row['nombres_persona'] ?? '';
    $row['apellidos'] = $row['apellidos'] ?? $row['Apellido'] ?? $row['apellido'] ?? $row['apellidos_persona'] ?? '';
    $row['rol_nombre'] = $row['rol_nombre'] ?? $row['Rol'] ?? $row['rol'] ?? $row['nombre_rol'] ?? '';

    $personaId = (int)($row['persona_id'] ?? 0);
    $rolId = (int)($row['rol_id'] ?? 0);

    if (($personaId > 0 && ($row['nombres'] === '' || $row['apellidos'] === '')) || ($rolId > 0 && $row['rol_nombre'] === '')) {
        if ($personaId > 0 && !isset($personaCache[$personaId])) {
            $persona = app_db_one($conexion, "SELECT nombres, apellidos FROM personal WHERE id = " . $personaId);
            $personaCache[$personaId] = $persona ?: ['nombres' => '', 'apellidos' => ''];
        }
        if ($rolId > 0 && !isset($rolCache[$rolId])) {
            $rol = app_db_one($conexion, "SELECT nombre FROM roles WHERE id = " . $rolId);
            $rolCache[$rolId] = $rol['nombre'] ?? '';
        }

        if ($personaId > 0 && ($row['nombres'] === '' || $row['apellidos'] === '')) {
            $row['nombres'] = $row['nombres'] ?: ($personaCache[$personaId]['nombres'] ?? '');
            $row['apellidos'] = $row['apellidos'] ?: ($personaCache[$personaId]['apellidos'] ?? '');
        }
        if ($rolId > 0 && $row['rol_nombre'] === '') {
            $row['rol_nombre'] = $rolCache[$rolId] ?? '';
        }
    }

    return $row;
}, $rows);

$editRegistro = null;
if ($editId > 0) {
    foreach ($rows as $row) {
        if ((int)($row['id'] ?? 0) === $editId) {
            $editRegistro = $row;
            break;
        }
    }
}

if ($period && app_get('format', '') === 'csv') {
    // Limpia cualquier salida previa
    if (ob_get_length()) {
        ob_clean();
    }

    // Evita que warnings rompan el Descarga CSV 
    ini_set('display_errors', 0);
    error_reporting(0);

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="nomina-previa-' . $period['clave'] . '.csv"');
    $out = fopen('php://output', 'w');
    // fputcsv($out, ['Colaborador', 'Rol', 'Turnos', 'Asistencias', 'Inasistencias', 'Horas de retardo', 'Salario diario', 'Bono', 'Pago hrs extra', 'Deducciones', 'Depósito', 'Estatus']);
    fputcsv($out, ['Colaborador','Rol','Periodo','Turnos',  'Asistencias','Faltas','Faltas Injustificadas','Faltas Justificadas','Vacaciones Días pagados prima','Vacaciones Días gozados','Horas retardo','Días festivos','Días incapacidad','Referidos','Salario hora','Salario base','Salario minimo diario','Descuento faltas injustificada','Descuento faltas justificada','Descuento hrs tarde','Infonavit','Fonacot','Abono a Prestamo','Sanciones','Adelantos de nomina','Descuento botas material','Aportacion caja de ahorro','Descuento otros','Bono','Importe por referido','Vacaciones y prima vacacional','Turnos extras especial','Pago turnos extras especial','Turnos extras 12','Pago turnos extras 12','Turnos extras 24','Pago turnos extras 24','Pago Días festivos','Horas extra','Pago hrs extra','Pagos extras','Deducciones','Ajustes','Salario Deposito','Estatus','Comentarios']);
    
    foreach ($rows as $row) {
        fputcsv($out, [
            ($row['nombres'] ?? '') . ' ' . ($row['apellidos'] ?? ''),
            $row['rol_nombre'] ?? '',
            $period['clave'] ?? '',
            $row['periodo_Turnos'] ?? 0,
            $row['asistencias'] ?? 0,
            $row['inasistencias'] ?? 0,
            $row['faltas_injustificadas'] ?? 0,
            $row['faltas_justificadas'] ?? 0,
            $row['vacaciones_dias_pagados_prima'] ?? 0,
            $row['vacaciones_dias_gozados'] ?? 0,
            $row['horas_retardo'] ?? 0,
            $row['dias_festivos'] ?? 0,
            $row['dias_incapacidad'] ?? 0,
            $row['referidos'] ?? 0,
            "$".$row['salario_hora'] ?? 0,
            "$".$row['salario_base'] ?? 0,
            "$".$row['salario_minimo_diario'] ?? 0,
            "$".$row['desc_faltas_injustificada'] ?? 0,
            "$".$row['desc_faltas_justificada'] ?? 0,
            "$".$row['desc_hrs_tarde'] ?? 0,
            "$".$row['infonavit'] ?? 0,
            "$".$row['fonacot'] ?? 0,
            "$".$row['abono_a_prestamo'] ?? 0,
            "$".$row['sanciones'] ?? 0,
            "$".$row['adelantos_de_nomina'] ?? 0,
            "$".$row['desc_botas_material'] ?? 0,
            "$".$row['aportacion_caja_de_ahorro'] ?? 0,
            "$".$row['desc_otros'] ?? 0,
            "$".$row['bono'] ?? 0,
            "$".$row['importe_por_referido'] ?? 0,
            "$".$row['vacaciones_y_prima_vacacional'] ?? 0,
            $row['turnos_extras_especial'] ?? 0,
            "$".$row['pago_turnos_extras_especial'] ?? 0,
            $row['turnos_extras_12'] ?? 0,
            "$".$row['pago_turnos_extras_12'] ?? 0,
            $row['turnos_extras_24'] ?? 0,
            "$".$row['pago_turnos_extras_24'] ?? 0,
            "$".$row['pago_dias_festivos'] ?? 0,
            $row['horas_extra'] ?? 0,
            "$".$row['pago_hrs_extra'] ?? 0,
            "$".$row['pagos_extras'] ?? 0,
            "$".$row['deducciones'] ?? 0,
            "$".$row['ajustes'] ?? 0,
            "$".$row['salario_Deposito'] ?? 0,
            $row['estatus'] ?? 'PENDIENTE',
            $row['comentarios'] ?? '',
        ], ',', '"', '\\');
    }
    fclose($out);
    exit;
}

$periods = app_db_all($conexion, "SELECT * FROM nomina_periodos WHERE estado <>'ABIERTO' ORDER BY fecha_inicio DESC, id DESC ");
$perPageOptions = [10, 20, 30, 50, 100];
$perPage = (int)app_get('per_page', 20);
if (!in_array($perPage, $perPageOptions, true)) {
    $perPage = 20;
}
$totalRows = count($rows);
$currentPage = max(1, (int)app_get('page', 1));
$totalPages = max(1, (int)ceil($totalRows / $perPage));
if ($currentPage > $totalPages) {
    $currentPage = $totalPages;
}
$offset = ($currentPage - 1) * $perPage;
$pageRows = array_slice($rows, $offset, $perPage);

$totals = [
    'count' => $totalRows,
    'deducciones' => array_sum(array_map(static fn($row) => (float)($row['deducciones'] ?? 0), $rows)),
    'deposito' => array_sum(array_map(static fn($row) => (float)($row['salario_Deposito'] ?? 0), $rows)),
];

app_render_page_start(
    'Cálculo previo de nómina',
    'Cálculo previo de nómina',
    'Ejecución del cálculo previo de asistencia y resumen de nómina para el periodo seleccionado.'
);
app_render_alerts($messages);
?>

<div class="row layout-top-spacing mb-3">
    <div class="col-md-4">
        <div class="card summary-card p-3">
            <div class="summary-label">Periodo seleccionado</div>
            <div class="summary-value" style="font-size:1.2rem;">
                <?php echo app_h($period['clave'] ?? 'Sin periodo'); ?>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card summary-card p-3">
            <div class="summary-label">Registros</div>
            <div class="summary-value"><?php echo (int)$totals['count']; ?></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card summary-card p-3">
            <div class="summary-label">Depósito acumulado</div>
            <div class="summary-value"><?php echo app_money($totals['deposito']); ?></div>
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
        <?php if ($period && ($period['estado'] ?? '') !== 'CERRADO'): ?>
            <form method="post" class="mt-3" onsubmit="return confirm('¿Confirmar todos los registros y cerrar este periodo? Esta acción no se puede deshacer.');">
                <input type="hidden" name="accion" value="confirmar_cerrar_periodo">
                <input type="hidden" name="periodo_id" value="<?php echo (int)$periodId; ?>">
                <button type="submit" class="btn btn-success">Confirmar todos y cerrar periodo</button>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php if ($editRegistro): ?>
    <div class="card content-card mb-4">
        <div class="card-body">
            <h5 class="mb-3">Editar registro</h5>
            <form method="post">
                <input type="hidden" name="accion" value="guardar_nomina_previa">
                <input type="hidden" name="registro_id" value="<?php echo (int)($editRegistro['id'] ?? 0); ?>">
                <input type="hidden" name="periodo_id" value="<?php echo (int)$periodId; ?>">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Colaborador</label>
                        <input class="form-control" value="<?php echo app_h(($editRegistro['nombres'] ?? '') . ' ' . ($editRegistro['apellidos'] ?? '')); ?>" disabled>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Rol</label>
                        <input class="form-control" value="<?php echo app_h($editRegistro['rol_nombre'] ?? ''); ?>" disabled>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Estatus</label>
                        <input class="form-control" value="<?php echo app_h($editRegistro['estatus'] ?? 'PENDIENTE'); ?>" disabled>
                    </div>
                    <div class="col-md-3 mb-3 d-none">
                        <label class="form-label">Horas retardo Actual</label>
                        <input type="number" step="1" class="form-control" name="horas_retardo" id="horas_retardo" value="<?php echo (int)($editRegistro['horas_retardo'] ?? 0); ?>" readonly>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Horas retardo</label>
                        <input type="number" step="1" class="form-control" name="nuevas_horas_retardo" id="nuevas_horas_retardo" value="<?php echo (int)($editRegistro['horas_retardo'] ?? 0); ?>">
                    </div>
                    <div class="col-md-3 mb-3 d-none">
                        <label class="form-label">Desc. horas retardo Actual</label>
                        <input type="number" step="0.01" class="form-control" name="desc_horas_retardo" id="desc_horas_retardo" value="<?php echo app_h($editRegistro['desc_horas_retardo'] ?? $editRegistro['desc_hrs_tarde'] ?? 0); ?>" readonly>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Desc. horas retardo</label>
                        <input type="number" step="0.01" class="form-control" name="nuevas_desc_horas_retardo" id="nuevas_desc_horas_retardo" value="<?php echo app_h($editRegistro['desc_horas_retardo'] ?? $editRegistro['desc_hrs_tarde'] ?? 0); ?>" readonly>
                    </div>
                    <div class="col-md-3 mb-3 d-none">
                        <label class="form-label">Horas extras Actual</label>
                        <input type="number" step="1" class="form-control" name="horas_extra" id="horas_extra" value="<?php echo (int)($editRegistro['horas_extra'] ?? 0); ?>" readonly>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Horas extras</label>
                        <input type="number" step="1" class="form-control" name="nuevas_horas_extra" id="nuevas_horas_extra" value="<?php echo (int)($editRegistro['horas_extra'] ?? 0); ?>">
                    </div>
                    <div class="col-md-3 mb-3 d-none">
                        <label class="form-label">Pago hrs extra Actual</label>
                        <input type="number" step="0.01" class="form-control" name="pago_hrs_extra" id="pago_hrs_extra" value="<?php echo app_h($editRegistro['pago_hrs_extra'] ?? 0); ?>" readonly>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Pago hrs extra</label>
                        <input type="number" step="0.01" class="form-control" name="nuevas_pago_hrs_extra" id="nuevas_pago_hrs_extra" value="<?php echo app_money($editRegistro['pago_hrs_extra'] ?? 0); ?>" readonly>
                    </div>                    
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Salario diario</label>
                        <input type="number" step="0.01" class="form-control" name="salario_diario" value="<?php echo app_h($editRegistro['salario_diario'] ?? 0); ?>" disabled>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Salario por hora</label>
                        <input type="number" step="0.01" class="form-control" name="salario_por_hora" id="salario_por_hora" value="<?php echo app_h($editRegistro['salario_por_hora'] ?? $editRegistro['salario_hora'] ?? 0); ?>" disabled>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Salario base</label>
                        <input type="number" step="0.01" class="form-control" name="salario_base" value="<?php echo app_h($editRegistro['salario_base'] ?? 0); ?>" disabled>
                    </div>
                    <div class="col-md-3 mb-3 d-none">
                        <label class="form-label">Pagos extras Actual</label>
                        <input type="number" step="0.01" class="form-control" name="pagos_extras" id="pagos_extra" value="<?php echo app_h($editRegistro['pagos_extras'] ?? 0); ?>" disabled>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Pagos extras</label>
                        <input type="number" step="0.01" class="form-control" name="nuevos_pagos_extras" id="nuevos_pagos_extras" value="<?php echo app_h($editRegistro['pagos_extras'] ?? 0); ?>" readonly>
                    </div>
                    <div class="col-md-3 mb-3 d-none">
                        <label class="form-label">Deducciones Actual</label>
                        <input type="number" step="0.01" class="form-control" name="deducciones" id="deducciones" value="<?php echo app_h($editRegistro['deducciones'] ?? 0); ?>" disabled>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Deducciones</label>
                        <input type="number" step="0.01" class="form-control" name="nuevas_deducciones" id="nuevas_deducciones" value="<?php echo app_h($editRegistro['deducciones'] ?? 0); ?>" readonly>
                    </div>
                    <div class="col-md-3 mb-3 d-none">
                        <label class="form-label">Neto Actual</label>
                        <input type="number" step="0.01" class="form-control" name="salario_Deposito" id="salario_Deposito" value="<?php echo app_h($editRegistro['salario_Deposito'] ?? 0); ?>" disabled>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Neto</label>
                        <input type="number" step="0.01" class="form-control" name="nuevo_salario_Deposito" id="nuevo_salario_Deposito" value="<?php echo app_h($editRegistro['salario_Deposito'] ?? 0); ?>" readonly>
                        <input type="hidden" name="salario_deposito_base_calculado" id="salario_deposito_base_calculado" value="<?php echo app_h($editRegistro['salario_Deposito'] ?? 0); ?>">
                    </div>
                    <div class="col-md-3 mb-3 d-none">
                        <label class="form-label">Ajuste al depósito Actual</label>
                        <input type="number" step="0.01" class="form-control" name="ajuste_salario_deposito" id="ajuste_salario_deposito" value="<?php echo app_h($editRegistro['ajustes'] ?? 0); ?>" disabled>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Ajuste al depósito</label>
                        <input type="number" step="0.01" class="form-control" name="nuevo_ajuste_salario_deposito" id="nuevo_ajuste_salario_deposito" value="<?php echo app_h($editRegistro['ajustes'] ?? 0); ?>" placeholder="Puede ser negativo">
                    </div>
                    <div class="col-12 mb-3">
                        <label class="form-label" for="comentarios">Comentarios</label>
                        <textarea class="form-control" name="comentarios" id="comentarios" rows="3" maxlength="8000"><?php echo app_h($editRegistro['comentarios'] ?? ''); ?></textarea>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Guardar cambios</button>
                    <a href="?periodo_id=<?php echo $periodId; ?>" class="btn btn-outline-secondary">Cancelar</a>
                </div>
            </form>
            <script>
                (function () {
                    const faltasInput = document.getElementById('faltas_injustificadas');
                    const montoInput = document.getElementById('faltas_desc_injustificada');
                    const descInput = document.getElementById('desc_faltas_injustificada');
                    if (faltasInput && montoInput && descInput) {
                        const updateDesc = function () {
                            const faltas = parseFloat(faltasInput.value) || 0;
                            const monto = parseFloat(montoInput.value) || 0;
                            descInput.value = (faltas * monto).toFixed(2);
                        };
                        faltasInput.addEventListener('input', updateDesc);
                        montoInput.addEventListener('input', updateDesc);
                        updateDesc();
                    }

                   const salarioHoraInput = document.getElementById('salario_por_hora');

                   const horasRetardoInput = document.getElementById('nuevas_horas_retardo');
                   const descHorasRetardoOriginalInput = document.getElementById('desc_horas_retardo');
                   const descHorasRetardoInput = document.getElementById('nuevas_desc_horas_retardo');
                   const deduccionesInput = document.getElementById('deducciones');
                   const nuevasDeduccionesInput = document.getElementById('nuevas_deducciones');
                   if (
                       horasRetardoInput &&
                       salarioHoraInput &&
                       descHorasRetardoOriginalInput &&
                       descHorasRetardoInput &&
                       deduccionesInput &&
                       nuevasDeduccionesInput
                   ) {
                       const updateDescHorasRetardo = function () {
                           const horasRetardo = parseFloat(horasRetardoInput.value) || 0;
                           const salarioHora = parseFloat(salarioHoraInput.value) || 0;
                           const nuevasDescHorasRetardo = horasRetardo * salarioHora;
                           const deducciones = parseFloat(deduccionesInput.value) || 0;
                           const descHorasRetardo = parseFloat(descHorasRetardoOriginalInput.value) || 0;
                           descHorasRetardoInput.value = nuevasDescHorasRetardo.toFixed(2);
                           nuevasDeduccionesInput.value = (
                               deducciones - descHorasRetardo + nuevasDescHorasRetardo
                           ).toFixed(2);
                       };
                       horasRetardoInput.addEventListener('input', function () {
                           updateDescHorasRetardo();
                           updateSalarioDeposito();
                       });
                       salarioHoraInput.addEventListener('change', updateDescHorasRetardo);
                       updateDescHorasRetardo();
                   }

                   const horasExtraInput = document.getElementById('nuevas_horas_extra');
                   const pagoHoraExtraInput = document.getElementById('nuevas_pago_hrs_extra');
                   const pagoHrsExtraOriginalInput = document.getElementById('pago_hrs_extra');
                   const pagosExtraDisplayInput = document.getElementById('pagos_extra');
                   const nuevosPagosExtraInput = document.getElementById('nuevos_pagos_extras');
                   const salarioDepositoInput = document.getElementById('salario_Deposito');
                   const nuevoSalarioDepositoInput = document.getElementById('nuevo_salario_Deposito');
                   const salarioDepositoBaseInput = document.getElementById('salario_deposito_base_calculado');
                   const ajusteSalarioDepositoInput = document.getElementById('ajuste_salario_deposito');
                   const nuevoAjusteSalarioDepositoInput = document.getElementById('nuevo_ajuste_salario_deposito');
                   const updateSalarioDeposito = function () {
                       if (
                           !salarioDepositoInput ||
                           !nuevoSalarioDepositoInput ||
                           !descHorasRetardoInput ||
                           !descHorasRetardoOriginalInput ||
                           !pagoHrsExtraOriginalInput ||
                           !pagoHoraExtraInput
                       ) {
                           return;
                       }
                       const salarioDeposito = parseFloat(salarioDepositoInput.value) || 0;
                       const descHorasRetardo = parseFloat(descHorasRetardoOriginalInput.value) || 0;
                       const nuevasDescHorasRetardo = parseFloat(descHorasRetardoInput.value) || 0;
                       const pagoHrsExtra = parseFloat(pagoHrsExtraOriginalInput.value) || 0;
                       const nuevasPagoHrsExtra = parseFloat(pagoHoraExtraInput.value) || 0;
                       const ajusteSalarioDeposito = ajusteSalarioDepositoInput
                           ? parseFloat(ajusteSalarioDepositoInput.value) || 0
                           : 0;
                       const nuevoAjusteSalarioDeposito = nuevoAjusteSalarioDepositoInput
                           ? parseFloat(nuevoAjusteSalarioDepositoInput.value) || 0
                           : 0;
                       const salarioDepositoBase = (
                           salarioDeposito +
                           descHorasRetardo -
                           nuevasDescHorasRetardo -
                           pagoHrsExtra +
                           nuevasPagoHrsExtra -
                           ajusteSalarioDeposito +
                           nuevoAjusteSalarioDeposito
                       );
                       if (salarioDepositoBaseInput) {
                           salarioDepositoBaseInput.value = salarioDepositoBase.toFixed(2);
                       }
                       nuevoSalarioDepositoInput.value = salarioDepositoBase.toFixed(2);
                   };
                   if (nuevoAjusteSalarioDepositoInput) {
                       nuevoAjusteSalarioDepositoInput.addEventListener('input', updateSalarioDeposito);
                   }
                   if (
                       horasExtraInput &&
                       salarioHoraInput &&
                       pagoHoraExtraInput &&
                       pagoHrsExtraOriginalInput &&
                       pagosExtraDisplayInput &&
                       nuevosPagosExtraInput
                   ) {
                       const updatePagoHorasExtra = function () {
                           const horasExtra = parseFloat(horasExtraInput.value) || 0;
                           const salarioHora = parseFloat(salarioHoraInput.value) || 0;
                           const nuevasPagoHrsExtra = horasExtra * salarioHora;
                           const pagosExtras = parseFloat(pagosExtraDisplayInput.value) || 0;
                           const pagoHrsExtra = parseFloat(pagoHrsExtraOriginalInput.value) || 0;
                           pagoHoraExtraInput.value = nuevasPagoHrsExtra.toFixed(2);
                           nuevosPagosExtraInput.value = (
                              pagosExtras - pagoHrsExtra + nuevasPagoHrsExtra
                           ).toFixed(2);
                           updateSalarioDeposito();
                       };
                       horasExtraInput.addEventListener('input', updatePagoHorasExtra);
                       salarioHoraInput.addEventListener('change', updatePagoHorasExtra);
                       updatePagoHorasExtra();
                   }
               })();
            </script>
        </div>
    </div>
<?php endif; ?>

<div class="card content-card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h5 class="mb-0">Vista previa del cálculo</h5>
                <?php if ($period): ?>
                    <small class="text-muted"><?php echo app_quincena_label($period['fecha_inicio'], $period['fecha_fin']); ?></small>
                <?php endif; ?>
            </div>
            <?php if ($period): ?>
                <div class="d-flex gap-2">
                    <a href="?periodo_id=<?php echo $periodId; ?>&format=csv" class="btn btn-primary">Descargar CSV</a>
                    <!-- a href="nomina-exportacion.php?periodo_id=<?php echo $periodId; ?>" class="btn btn-outline-secondary">Ver exportación</a -->
                </div>
            <?php endif; ?>
        </div>

        <input type="search" class="form-control mb-3" data-table-search="#nomina-previo-table" placeholder="Buscar colaborador, rol o monto...">

        <div class="table-wrapper mb-3" style="max-height: 520px; overflow: auto; border: 1px solid #e5e7eb; border-radius: 8px;">
            <div class="table-responsive" style="min-width: 1200px;">
                <table class="table table-striped mb-0" id="nomina-previo-table">
                    <thead style="position: sticky; top: 0; z-index: 1; background: #fff;">
                        <tr>
                            <th>Colaborador</th>
                            <th>Rol</th>
                            <th>Turnos</th>
                            <th>Asist.</th>
                            <th>Faltas</th>
                            <th>Días festivos</th>
                            <th>Vacaciones</th>
                            <th>Horas extra </th>
                            <th>Turnos extra esp.</th>
                            <th>Turnos extra 24</th>
                            <th>Turnos extra 12</th>
                            <th>Referidos</th>
                            <th>Horas Retardo</th>
                            <th>Faltas justificadas</th>
                            <th>Faltas injustificadas</th>
                            <th>Sanciones</th>
                            <th>Salario base</th>
                            <th>Bono</th>
                            <th>Pagos extra</th>
                            <th>Deducciones</th>
                            <th>Salario Neto</th>
                            <th>Estatus</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pageRows as $row): ?>
                            <?php
                            $status = strtoupper((string)($row['estatus'] ?? 'PENDIENTE'));
                            $canEdit = $status === 'PENDIENTE';
                            $canConfirm = $status === 'PENDIENTE';
                            ?>
                            <tr>
                                <td><?php echo app_h(($row['nombres'] ?? '') . ' ' . ($row['apellidos'] ?? '')); ?></td>
                                <td><?php echo app_h($row['rol_nombre'] ?? ''); ?></td>
                                <td><?php echo (int)($row['periodo_Turnos'] ?? 0); ?></td>
                                <td><?php echo (int)($row['asistencias'] ?? 0); ?></td>
                                <td><?php echo (int)($row['inasistencias'] ?? 0); ?></td>
                                <td><?php echo (int)($row['dias_festivos'] ?? 0); ?></td>
                                <td><?php echo (int)($row['vacaciones_dias_gozados'] ?? 0); ?></td>
                                <td><?php echo (int)($row['horas_extra'] ?? 0); ?></td>
                                <td><?php echo (int)($row['turnos_extras_especial'] ?? 0); ?></td>
                                <td><?php echo (int)($row['turnos_extras_24'] ?? 0); ?></td>
                                <td><?php echo (int)($row['turnos_extras_12'] ?? 0); ?></td>
                                <td><?php echo (int)($row['referidos'] ?? 0); ?></td>
                                <td><?php echo (int)($row['horas_retardo'] ?? 0); ?></td>
                                <td><?php echo (int)($row['faltas_justificadas'] ?? 0); ?></td>
                                <td><?php echo (int)($row['faltas_injustificadas'] ?? 0); ?></td>
                                <td><?php echo app_money($row['sanciones'] ?? 0); ?></td>
                                <td><?php echo app_money($row['salario_base'] ?? 0); ?></td>
                                <td><?php echo app_money($row['bono'] ?? 0); ?></td>
                                <td><?php echo app_money($row['pagos_extra'] ?? 0); ?></td>
                                <td><?php echo app_money($row['deducciones'] ?? 0); ?></td>
                                <td><?php echo app_money($row['salario_Deposito'] ?? 0); ?></td>
                                <td>
                                    <span class="badge <?php echo $status === 'CONFIRMADO' ? 'bg-success' : 'bg-warning text-dark'; ?>">
                                            <?php echo app_h($status); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex gap-2 flex-wrap">
                                            <a
                                                href="?periodo_id=<?php echo $periodId; ?>&editar_id=<?php echo (int)($row['id'] ?? 0); ?>"
                                                class="btn btn-outline-primary btn-sm"
                                                <?php echo $canEdit ? '' : 'tabindex="-1" aria-disabled="true" style="pointer-events:none; opacity:0.5;"'; ?>
                                            >
                                                Editar
                                            </a>
                                            <a
                                                href="nomina-recibo.php?periodo_id=<?php echo (int)$periodId; ?>&personal_id=<?php echo (int)($row['persona_id'] ?? 0); ?>&calculo_id=<?php echo (int)($row['id'] ?? 0); ?>"
                                                class="btn btn-outline-dark btn-sm"
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                <?php echo $status === 'CONFIRMADO' ? '' : 'tabindex="-1" aria-disabled="true" style="pointer-events:none; opacity:0.5;"'; ?>
                                            >
                                                Imprimir recibo
                                            </a>
                                            <form method="post" class="d-inline">
                                                <input type="hidden" name="accion" value="confirmar_nomina_previa">
                                                <input type="hidden" name="registro_id" value="<?php echo (int)($row['id'] ?? 0); ?>">
                                                <input type="hidden" name="periodo_id" value="<?php echo (int)$periodId; ?>">
                                                <button type="submit" class="btn btn-success btn-sm" <?php echo $canConfirm ? '' : 'disabled'; ?>>
                                                    Confirmar
                                                </button>
                                            </form>
                                            <form method="post" class="d-inline">
                                                <input type="hidden" name="accion" value="recalcular_nomina_colaborador">
                                                <input type="hidden" name="periodo_id" value="<?php echo (int)$periodId; ?>">
                                                <input type="hidden" name="persona_id" value="<?php echo (int)($row['persona_id'] ?? 0); ?>">
                                                <button type="submit" class="btn btn-outline-warning btn-sm" <?php echo $canConfirm ? '' : 'disabled'; ?>>
                                                    Recalcular nómina
                                                </button>
                                            </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$pageRows): ?>
                            <tr><td colspan="21" class="text-center text-muted">No hay cálculo previo para este periodo.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php if ($totalRows > 0): ?>
            <nav aria-label="Paginación de nómina previa" class="d-flex justify-content-between align-items-center mt-3 flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2 text-muted small">
                    <span>Mostrar</span>
                    <form method="get" class="d-flex align-items-center gap-2 mb-0">
                        <input type="hidden" name="periodo_id" value="<?php echo $periodId; ?>">
                        <select name="per_page" class="form-select form-select-sm" onchange="this.form.submit()">
                            <?php foreach ($perPageOptions as $option): ?>
                                <option value="<?php echo $option; ?>" <?php echo $perPage === $option ? 'selected' : ''; ?>>
                                    <?php echo $option; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <span>por página</span>
                    </form>
                </div>
                <div class="text-muted small">
                    Mostrando <?php echo min($offset + 1, $totalRows); ?>-<?php echo min($offset + count($pageRows), $totalRows); ?> de <?php echo $totalRows; ?> registros
                </div>
                <ul class="pagination pagination-sm mb-0">
                    <li class="page-item <?php echo $currentPage <= 1 ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?periodo_id=<?php echo $periodId; ?>&page=<?php echo max(1, $currentPage - 1); ?>&per_page=<?php echo $perPage; ?>">Anterior</a>
                    </li>
                    <?php for ($pageNumber = 1; $pageNumber <= $totalPages; $pageNumber++): ?>
                        <li class="page-item <?php echo $pageNumber === $currentPage ? 'active' : ''; ?>">
                            <a class="page-link" href="?periodo_id=<?php echo $periodId; ?>&page=<?php echo $pageNumber; ?>&per_page=<?php echo $perPage; ?>"><?php echo $pageNumber; ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?php echo $currentPage >= $totalPages ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?periodo_id=<?php echo $periodId; ?>&page=<?php echo min($totalPages, $currentPage + 1); ?>&per_page=<?php echo $perPage; ?>">Siguiente</a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</div>

<?php
app_render_page_end();
