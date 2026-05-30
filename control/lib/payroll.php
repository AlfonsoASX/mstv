<?php

require_once __DIR__ . '/helpers.php';

function app_calculate_tardiness(?string $horaProgramada, ?string $horaEntrada, int $toleranciaMinutos = 15, int $maxHoras = 4): array
{
    if (!$horaProgramada || !$horaEntrada) {
        return [
            'minutos' => 0,
            'horas' => 0,
            'cancelado' => false,
        ];
    }

    $programada = strtotime($horaProgramada);
    $entrada = strtotime($horaEntrada);

    if ($programada === false || $entrada === false || $entrada <= $programada) {
        return [
            'minutos' => 0,
            'horas' => 0,
            'cancelado' => false,
        ];
    }

    $minutos = (int)floor(($entrada - $programada) / 60);

    if ($minutos <= $toleranciaMinutos) {
        return [
            'minutos' => $minutos,
            'horas' => 0,
            'cancelado' => false,
        ];
    }

    $horas = (int)ceil(($minutos - $toleranciaMinutos) / 60);
    $cancelado = $horas > $maxHoras;

    return [
        'minutos' => $minutos,
        'horas' => $cancelado ? 0 : $horas,
        'cancelado' => $cancelado,
    ];
}

function app_period_meta(string $fechaInicio, string $fechaFin): array
{
    $inicio = strtotime($fechaInicio);
    $anio = (int)date('Y', $inicio);
    $numero = (int)date('d', $inicio) <= 15 ? 1 : 2;

    return [
        'anio' => $anio,
        'numero_quincena' => $numero,
        'clave' => sprintf('%s-Q%s-%s', $anio, $numero, date('m', $inicio)),
        'fecha_pago' => $fechaFin,
    ];
}

function app_current_period_bounds(?string $date = null): array
{
    $date = $date ?: date('Y-m-d');
    $day = (int)date('d', strtotime($date));
    $yearMonth = date('Y-m', strtotime($date));

    if ($day <= 15) {
        return [
            'inicio' => $yearMonth . '-01',
            'fin' => $yearMonth . '-15',
        ];
    }

    return [
        'inicio' => $yearMonth . '-16',
        'fin' => date('Y-m-t', strtotime($date)),
    ];
}

function app_get_or_create_period(mysqli $conexion, string $fechaInicio, string $fechaFin): int
{
    $meta = app_period_meta($fechaInicio, $fechaFin);
    $clave = $meta['clave'];

    $sql = "SELECT id FROM nomina_periodos WHERE clave = ? LIMIT 1";
    if ($stmt = mysqli_prepare($conexion, $sql)) {
        mysqli_stmt_bind_param($stmt, 's', $clave);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        if ($row) {
            return (int)$row['id'];
        }
    }

    $insert = "
        INSERT INTO nomina_periodos (clave, fecha_inicio, fecha_fin, fecha_pago, anio, numero_quincena, estado)
        VALUES (?, ?, ?, ?, ?, ?, 'ABIERTO')
    ";

    if ($stmt = mysqli_prepare($conexion, $insert)) {
        $fechaPago = $meta['fecha_pago'];
        $anio = (int)$meta['anio'];
        $numeroQuincena = (int)$meta['numero_quincena'];
        mysqli_stmt_bind_param(
            $stmt,
            'ssssii',
            $clave,
            $fechaInicio,
            $fechaFin,
            $fechaPago,
            $anio,
            $numeroQuincena
        );
        mysqli_stmt_execute($stmt);
        $id = (int)mysqli_insert_id($conexion);
        mysqli_stmt_close($stmt);

        return $id;
    }

    return 0;
}

function app_get_period(mysqli $conexion, int $periodoId): ?array
{
    $sql = "SELECT * FROM nomina_periodos WHERE id = " . (int)$periodoId . " LIMIT 1";
    return app_db_one($conexion, $sql);
}

function app_get_active_personal(mysqli $conexion): array
{
    $sql = "
        SELECT
            p.*,
            u.usuario,
            COALESCE(r.nombre, '') AS rol_nombre
        FROM personal p
        INNER JOIN usuarios u ON u.id = p.usuario_id
        LEFT JOIN roles r ON r.id = u.rol_id
        WHERE p.estado = 'ACTIVO'
          AND p.activo_en_nomina = 1
        ORDER BY p.nombres, p.apellidos
    ";

    return app_db_all($conexion, $sql);
}

function app_get_personal(mysqli $conexion, int $personalId): ?array
{
    $sql = "
        SELECT
            p.*,
            u.usuario,
            u.email,
            COALESCE(r.nombre, '') AS rol_nombre
        FROM personal p
        INNER JOIN usuarios u ON u.id = p.usuario_id
        LEFT JOIN roles r ON r.id = u.rol_id
        WHERE p.id = " . (int)$personalId . "
        LIMIT 1
    ";

    return app_db_one($conexion, $sql);
}

function app_salary_diario(array $personal, array $configs): float
{
    $salary = (float)($personal['salario_diario'] ?? 0);
    if ($salary > 0) {
        return $salary;
    }

    return app_config_float($configs, 'nomina_salario_minimo_diario', 0.0);
}

function app_salary_hora(array $personal, array $configs): float
{
    $salary = (float)($personal['salario_hora'] ?? 0);
    if ($salary > 0) {
        return $salary;
    }

    return app_config_float($configs, 'nomina_valor_hora', 75.0);
}

function app_vacation_days_for_year(int $year, array $configs): float
{
    if ($year <= 0) {
        return 0.0;
    }

    if ($year <= 5) {
        $defaults = [
            1 => 12.0,
            2 => 14.0,
            3 => 16.0,
            4 => 18.0,
            5 => 20.0,
        ];

        return app_config_float($configs, 'vacaciones_dias_anio_' . $year, $defaults[$year]);
    }

    $ranges = [
        [6, 10, 'vacaciones_dias_anios_6_10', 22.0],
        [11, 15, 'vacaciones_dias_anios_11_15', 24.0],
        [16, 20, 'vacaciones_dias_anios_16_20', 26.0],
        [21, 25, 'vacaciones_dias_anios_21_25', 28.0],
        [26, 30, 'vacaciones_dias_anios_26_30', 30.0],
    ];

    foreach ($ranges as [$min, $max, $configKey, $default]) {
        if ($year >= $min && $year <= $max) {
            return app_config_float($configs, $configKey, $default);
        }
    }

    return app_config_float($configs, 'vacaciones_dias_anios_26_30', 30.0);
}

function app_total_vacation_entitlement(int $years, array $configs): float
{
    $total = 0.0;

    for ($year = 1; $year <= $years; $year++) {
        $total += app_vacation_days_for_year($year, $configs);
    }

    return $total;
}

function app_vacation_summary(mysqli $conexion, int $personalId, array $configs, ?string $referenceDate = null): array
{
    $referenceDate = $referenceDate ?: date('Y-m-d');
    $personal = app_get_personal($conexion, $personalId);
    if (!$personal) {
        return [
            'generated' => 0.0,
            'gozadas' => 0.0,
            'pagadas' => 0.0,
            'ajustes' => 0.0,
            'notas' => 0,
            'balance' => 0.0,
            'years' => 0,
        ];
    }

    $years = app_years_between($personal['fecha_contratacion'] ?? null, $referenceDate);
    $generated = app_total_vacation_entitlement($years, $configs);
    $summary = [
        'generated' => $generated,
        'gozadas' => 0.0,
        'pagadas' => 0.0,
        'ajustes' => 0.0,
        'notas' => 0,
        'balance' => $generated,
        'years' => $years,
    ];

    $rows = app_db_all(
        $conexion,
        "SELECT tipo, dias, estado
         FROM vacaciones_movimientos
         WHERE personal_id = " . (int)$personalId
    );

    foreach ($rows as $row) {
        if (($row['estado'] ?? '') === 'CANCELADO') {
            continue;
        }

        $dias = (float)($row['dias'] ?? 0);
        switch ($row['tipo'] ?? '') {
            case 'GOZADAS':
                $summary['gozadas'] += $dias;
                break;
            case 'PAGADAS':
                $summary['pagadas'] += $dias;
                break;
            case 'AJUSTE':
                $summary['ajustes'] += $dias;
                break;
            case 'NOTA':
                $summary['notas']++;
                break;
        }
    }

    $summary['balance'] = $summary['generated'] - $summary['gozadas'] - $summary['pagadas'] + $summary['ajustes'];

    return $summary;
}

function app_recalculate_vacation_balance(mysqli $conexion, int $personalId, array $configs, ?string $referenceDate = null): float
{
    $referenceDate = $referenceDate ?: date('Y-m-d');
    $summary = app_vacation_summary($conexion, $personalId, $configs, $referenceDate);
    $balance = (float)$summary['balance'];

    $sql = "
        UPDATE personal
        SET dias_vacaciones_disponibles = ?,
            fecha_ultimo_calculo_vacaciones = ?
        WHERE id = ?
    ";

    if ($stmt = mysqli_prepare($conexion, $sql)) {
        mysqli_stmt_bind_param($stmt, 'dsi', $balance, $referenceDate, $personalId);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    return $balance;
}

function app_get_savings_balance(mysqli $conexion, int $personalId, ?string $hastaFecha = null): float
{
    $personal = app_get_personal($conexion, $personalId);
    $saldoInicial = (float)($personal['saldo_caja_ahorro_inicial'] ?? 0);
    $filtro = '';

    if ($hastaFecha) {
        $filtro = " AND fecha_aplicacion <= '" . mysqli_real_escape_string($conexion, $hastaFecha) . "'";
    }

    $movimientos = (float)app_db_value(
        $conexion,
        "SELECT COALESCE(SUM(monto), 0) AS total
         FROM caja_ahorro_movimientos
         WHERE personal_id = " . (int)$personalId . $filtro,
        0
    );

    return $saldoInicial + $movimientos;
}

function app_get_prestamo_capacidad(array $personal, float $saldoCaja, array $configs, ?string $referenceDate = null): array
{
    $years = app_years_between($personal['fecha_contratacion'] ?? null, $referenceDate ?: date('Y-m-d'));
    $maxByYears = $years <= 1
        ? app_config_float($configs, 'prestamos_monto_max_antiguedad_baja', 1000.0)
        : app_config_float($configs, 'prestamos_monto_max_antiguedad_alta', 5000.0);

    $maxAllowed = max(0.0, min($maxByYears, $saldoCaja));

    return [
        'years' => $years,
        'max_by_years' => $maxByYears,
        'max_allowed' => $maxAllowed,
    ];
}

function app_get_prestamos_activos(mysqli $conexion, int $personalId): array
{
    $sql = "
        SELECT *
        FROM prestamos_personal
        WHERE personal_id = " . (int)$personalId . "
          AND estado = 'ACTIVO'
        ORDER BY fecha_inicio DESC, id DESC
    ";

    return app_db_all($conexion, $sql);
}

function app_is_first_half_december(string $fechaInicio, string $fechaFin): bool
{
    $monthStart = (int)date('m', strtotime($fechaInicio));
    $monthEnd = (int)date('m', strtotime($fechaFin));
    $dayEnd = (int)date('d', strtotime($fechaFin));

    return $monthStart === 12 && $monthEnd === 12 && $dayEnd <= 15;
}

function app_build_turn_map(mysqli $conexion, int $personalId, string $fechaInicio, string $fechaFin): array
{
    $sql = "
        SELECT
            t.*,
            s.nombre AS sitio_nombre,
            (
                SELECT MIN(ra.fecha_hora)
                FROM registros_asistencia ra
                WHERE ra.turno_id = t.id
                  AND ra.tipo_evento = 'ENTRADA'
            ) AS entrada_real
        FROM turnos t
        INNER JOIN sitios s ON s.id = t.sitio_id
        WHERE t.personal_id = " . (int)$personalId . "
          AND t.hora_inicio BETWEEN '" . mysqli_real_escape_string($conexion, $fechaInicio . " 00:00:00") . "'
                               AND '" . mysqli_real_escape_string($conexion, $fechaFin . " 23:59:59") . "'
        ORDER BY t.hora_inicio ASC
    ";

    return app_db_all($conexion, $sql);
}

function app_get_period_manual_capture(mysqli $conexion, int $periodoId, int $personalId): ?array
{
    return app_db_one(
        $conexion,
        "SELECT *
         FROM nomina_capturas
         WHERE periodo_id = " . (int)$periodoId . "
           AND personal_id = " . (int)$personalId . "
         LIMIT 1"
    );
}

function app_get_total_adelantos_periodo(mysqli $conexion, int $periodoId, int $personalId): float
{
    return (float)app_db_value(
        $conexion,
        "SELECT COALESCE(SUM(monto), 0) AS total
         FROM adelantos_nomina
         WHERE personal_id = " . (int)$personalId . "
           AND periodo_id = " . (int)$periodoId . "
           AND estado = 'APLICADO'",
        0
    );
}

function app_get_employee_earned_to_date(mysqli $conexion, int $personalId, string $fechaInicio, string $fechaCorte, array $configs): float
{
    $personal = app_get_personal($conexion, $personalId);
    if (!$personal) {
        return 0.0;
    }

    $salarioDiario = app_salary_diario($personal, $configs);
    $sql = "
        SELECT COUNT(*) AS total
        FROM turnos
        WHERE personal_id = " . (int)$personalId . "
          AND es_turno_extra = 0
          AND hora_inicio BETWEEN '" . mysqli_real_escape_string($conexion, $fechaInicio . " 00:00:00") . "'
                               AND '" . mysqli_real_escape_string($conexion, $fechaCorte . " 23:59:59") . "'
          AND estado <> 'AUSENTE'
    ";
    $dias = (int)app_db_value($conexion, $sql, 0);

    return $dias * $salarioDiario;
}

function app_create_or_update_capture(
    mysqli $conexion,
    int $periodoId,
    int $personalId,
    float $infonavit,
    float $fonacot,
    float $otro,
    string $observaciones
): void {
    $sql = "
        INSERT INTO nomina_capturas (periodo_id, personal_id, descuento_infonavit, descuento_fonacot, descuento_manual_otro, observaciones)
        VALUES (?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            descuento_infonavit = VALUES(descuento_infonavit),
            descuento_fonacot = VALUES(descuento_fonacot),
            descuento_manual_otro = VALUES(descuento_manual_otro),
            observaciones = VALUES(observaciones)
    ";

    if ($stmt = mysqli_prepare($conexion, $sql)) {
        mysqli_stmt_bind_param($stmt, 'iiddds', $periodoId, $personalId, $infonavit, $fonacot, $otro, $observaciones);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}

function app_generate_mass_advances(mysqli $conexion, int $periodoId, array $configs): array
{
    $periodo = app_get_period($conexion, $periodoId);
    if (!$periodo) {
        return ['ok' => false, 'message' => 'Periodo no encontrado.'];
    }

    $monto = app_config_float($configs, 'adelanto_masivo_monto', 500.0);
    $cutoff = date(
        'Y-m-d',
        strtotime($periodo['fecha_pago'] . ' -' . app_config_int($configs, 'adelanto_masivo_dias_antes_nomina', 5) . ' days')
    );

    $inserted = 0;
    foreach (app_get_active_personal($conexion) as $personal) {
        $earned = app_get_employee_earned_to_date(
            $conexion,
            (int)$personal['id'],
            $periodo['fecha_inicio'],
            min(date('Y-m-d'), $cutoff),
            $configs
        );
        $existing = app_get_total_adelantos_periodo($conexion, $periodoId, (int)$personal['id']);
        $available = max(0.0, $earned - $existing);
        $toApply = min($monto, $available);

        if ($toApply <= 0) {
            continue;
        }

        $alreadyExists = (int)app_db_value(
            $conexion,
            "SELECT COUNT(*) AS total
             FROM adelantos_nomina
             WHERE personal_id = " . (int)$personal['id'] . "
               AND periodo_id = " . (int)$periodoId . "
               AND tipo = 'MASIVO'
               AND estado <> 'CANCELADO'",
            0
        );

        if ($alreadyExists > 0) {
            continue;
        }

        $sql = "
            INSERT INTO adelantos_nomina (personal_id, periodo_id, fecha_solicitud, fecha_aplicacion, monto, motivo, tipo, estado)
            VALUES (?, ?, ?, ?, ?, 'Adelanto masivo de nómina', 'MASIVO', 'APLICADO')
        ";

        if ($stmt = mysqli_prepare($conexion, $sql)) {
            $insertPersonalId = (int)$personal['id'];
            $fechaSolicitud = $cutoff;
            $fechaAplicacion = $cutoff;
            $montoInsert = $toApply;
            mysqli_stmt_bind_param(
                $stmt,
                'iissd',
                $insertPersonalId,
                $periodoId,
                $fechaSolicitud,
                $fechaAplicacion,
                $montoInsert
            );
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            $inserted++;
        }
    }

    return [
        'ok' => true,
        'message' => 'Adelantos masivos generados: ' . $inserted,
    ];
}

function app_save_nomina_concepto(
    mysqli $conexion,
    int $resumenId,
    int $periodoId,
    int $personalId,
    string $categoria,
    string $clave,
    string $descripcion,
    float $cantidad,
    float $monto,
    ?string $referenciaTabla = null,
    ?int $referenciaId = null
): void {
    $sql = "
        INSERT INTO nomina_conceptos
            (resumen_id, periodo_id, personal_id, categoria, clave, descripcion, cantidad, monto, referencia_tabla, referencia_id)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ";

    if ($stmt = mysqli_prepare($conexion, $sql)) {
        mysqli_stmt_bind_param(
            $stmt,
            'iiisssddsi',
            $resumenId,
            $periodoId,
            $personalId,
            $categoria,
            $clave,
            $descripcion,
            $cantidad,
            $monto,
            $referenciaTabla,
            $referenciaId
        );
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}

function app_upsert_nomina_resumen(mysqli $conexion, int $periodoId, int $personalId, array $data): int
{
    $salarioDiario = (float)$data['salario_diario'];
    $salarioHora = (float)$data['salario_hora'];
    $salarioBase = (float)$data['salario_base'];
    $retardosHoras = (float)$data['retardos_horas'];
    $descuentoRetardos = (float)$data['descuento_retardos'];
    $horasExtra = (float)$data['horas_extra'];
    $pagoHorasExtra = (float)$data['pago_horas_extra'];
    $turnosExtraMonto = (float)$data['turnos_extra_monto'];
    $vacacionesMonto = (float)$data['vacaciones_monto'];
    $primaVacacionalMonto = (float)$data['prima_vacacional_monto'];
    $diasFestivosMonto = (float)$data['dias_festivos_monto'];
    $incapacidadesMonto = (float)$data['incapacidades_monto'];
    $bonosMonto = (float)$data['bonos_monto'];
    $descuentosFaltas = (float)$data['descuentos_faltas'];
    $descuentosDescansos = (float)($data['descuentos_descansos'] ?? 0);
    $descuentosSanciones = (float)$data['descuentos_sanciones'];
    $descuentosMaterial = (float)$data['descuentos_material'];
    $descuentosInfonavit = (float)$data['descuentos_infonavit'];
    $descuentosFonacot = (float)$data['descuentos_fonacot'];
    $descuentosPrestamos = (float)$data['descuentos_prestamos'];
    $descuentosAdelantos = (float)$data['descuentos_adelantos'];
    $otrosDescuentos = (float)$data['otros_descuentos'];
    $finiquitoMonto = (float)$data['finiquito_monto'];
    $neto = (float)$data['neto'];
    $turnoCancelado = (int)$data['turno_cancelado'];

    $sql = "
        INSERT INTO nomina_resumen
        (
            periodo_id, personal_id, salario_diario, salario_hora, salario_base,
            retardos_horas, descuento_retardos, horas_extra, pago_horas_extra,
            turnos_extra_monto, vacaciones_monto, prima_vacacional_monto,
            dias_festivos_monto, incapacidades_monto, bonos_monto,
            descuentos_faltas, descuentos_descansos, descuentos_sanciones, descuentos_material,
            descuentos_infonavit, descuentos_fonacot, descuentos_prestamos,
            descuentos_adelantos, otros_descuentos, finiquito_monto, neto, turno_cancelado
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            salario_diario = VALUES(salario_diario),
            salario_hora = VALUES(salario_hora),
            salario_base = VALUES(salario_base),
            retardos_horas = VALUES(retardos_horas),
            descuento_retardos = VALUES(descuento_retardos),
            horas_extra = VALUES(horas_extra),
            pago_horas_extra = VALUES(pago_horas_extra),
            turnos_extra_monto = VALUES(turnos_extra_monto),
            vacaciones_monto = VALUES(vacaciones_monto),
            prima_vacacional_monto = VALUES(prima_vacacional_monto),
            dias_festivos_monto = VALUES(dias_festivos_monto),
            incapacidades_monto = VALUES(incapacidades_monto),
            bonos_monto = VALUES(bonos_monto),
            descuentos_faltas = VALUES(descuentos_faltas),
            descuentos_descansos = VALUES(descuentos_descansos),
            descuentos_sanciones = VALUES(descuentos_sanciones),
            descuentos_material = VALUES(descuentos_material),
            descuentos_infonavit = VALUES(descuentos_infonavit),
            descuentos_fonacot = VALUES(descuentos_fonacot),
            descuentos_prestamos = VALUES(descuentos_prestamos),
            descuentos_adelantos = VALUES(descuentos_adelantos),
            otros_descuentos = VALUES(otros_descuentos),
            finiquito_monto = VALUES(finiquito_monto),
            neto = VALUES(neto),
            turno_cancelado = VALUES(turno_cancelado)
    ";

    if ($stmt = mysqli_prepare($conexion, $sql)) {
        $types = 'ii' . str_repeat('d', 24) . 'i';
        mysqli_stmt_bind_param(
            $stmt,
            $types,
            $periodoId,
            $personalId,
            $salarioDiario,
            $salarioHora,
            $salarioBase,
            $retardosHoras,
            $descuentoRetardos,
            $horasExtra,
            $pagoHorasExtra,
            $turnosExtraMonto,
            $vacacionesMonto,
            $primaVacacionalMonto,
            $diasFestivosMonto,
            $incapacidadesMonto,
            $bonosMonto,
            $descuentosFaltas,
            $descuentosDescansos,
            $descuentosSanciones,
            $descuentosMaterial,
            $descuentosInfonavit,
            $descuentosFonacot,
            $descuentosPrestamos,
            $descuentosAdelantos,
            $otrosDescuentos,
            $finiquitoMonto,
            $neto,
            $turnoCancelado
        );
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    $row = app_db_one(
        $conexion,
        "SELECT id FROM nomina_resumen WHERE periodo_id = " . (int)$periodoId . " AND personal_id = " . (int)$personalId . " LIMIT 1"
    );

    return (int)($row['id'] ?? 0);
}

function app_cleanup_period_calculation(mysqli $conexion, int $periodoId): void
{
    mysqli_query($conexion, "DELETE FROM nomina_conceptos WHERE periodo_id = " . (int)$periodoId);
    mysqli_query($conexion, "DELETE FROM nomina_resumen WHERE periodo_id = " . (int)$periodoId);
    mysqli_query($conexion, "DELETE FROM prestamos_pagos WHERE periodo_id = " . (int)$periodoId);
    mysqli_query($conexion, "DELETE FROM caja_ahorro_movimientos WHERE periodo_id = " . (int)$periodoId . " AND tipo_movimiento IN ('APORTACION','PRESTAMO_CARGO','PRESTAMO_ABONO','INTERES')");
}

function app_calculate_nomina_period(mysqli $conexion, int $periodoId, array $configs): array
{
    $periodo = app_get_period($conexion, $periodoId);
    if (!$periodo) {
        return ['ok' => false, 'message' => 'Periodo no encontrado.'];
    }

    if (($periodo['estado'] ?? 'ABIERTO') !== 'ABIERTO') {
        return ['ok' => false, 'message' => 'El periodo ya fue calculado o cerrado. Crea uno nuevo si necesitas otro proceso.'];
    }

    app_cleanup_period_calculation($conexion, $periodoId);

    $personalList = app_get_active_personal($conexion);
    $tolerancia = app_config_int($configs, 'turnos_tolerancia_minutos', 15);
    $maxRetardoHoras = app_config_int($configs, 'turnos_max_retardo_horas', 4);
    $valorHora = app_config_float($configs, 'nomina_valor_hora', 75.0);
    $factorFestivo = app_config_float($configs, 'nomina_factor_dia_festivo', 2.0);

    foreach ($personalList as $personal) {
        $personalId = (int)$personal['id'];
        $salarioDiario = app_salary_diario($personal, $configs);
        $salarioHora = app_salary_hora($personal, $configs);
        $turnos = app_build_turn_map($conexion, $personalId, $periodo['fecha_inicio'], $periodo['fecha_fin']);

        $salarioBase = 0.0;
        $retardosHoras = 0.0;
        $descuentoRetardos = 0.0;
        $turnosExtraMonto = 0.0;
        $turnosCancelados = 0;

        foreach ($turnos as $turno) {
            $esExtra = (int)$turno['es_turno_extra'] === 1;
            $duracion = (float)($turno['horas_programadas'] ?? 0);
            if ($duracion <= 0) {
                $duracion = app_duration_hours($turno['hora_inicio'], $turno['hora_fin']);
            }

            if ($esExtra) {
                $montoExtra = (float)$turno['monto_turno_extra'];
                if ($montoExtra <= 0) {
                    if ($turno['tipo_turno_extra'] === 'MEDIO' || $duracion <= 12) {
                        $montoExtra = app_config_float($configs, 'turnos_extra_12h_monto', 400.0);
                    } elseif ($turno['tipo_turno_extra'] === 'COMPLETO' || $duracion >= 24) {
                        $montoExtra = app_config_float($configs, 'turnos_extra_24h_monto', 800.0);
                    } else {
                        $montoExtra = round($duracion * $valorHora, 2);
                    }
                }

                $turnosExtraMonto += $montoExtra;
                continue;
            }

            $salarioBase += $salarioDiario;

            $retardo = app_calculate_tardiness($turno['hora_inicio'], $turno['entrada_real'], $tolerancia, $maxRetardoHoras);
            if ($retardo['cancelado']) {
                $salarioBase -= $salarioDiario;
                $turnosCancelados++;
            } else {
                $retardosHoras += $retardo['horas'];
                $descuentoRetardos += $retardo['horas'] * $valorHora;
            }
        }

        $rowHoras = app_db_one(
            $conexion,
            "SELECT
                COALESCE(SUM(CASE WHEN tipo_ajuste = 'BONO' THEN horas ELSE 0 END), 0) AS horas_bono,
                COALESCE(SUM(CASE WHEN tipo_ajuste = 'HORA_MENOS' THEN horas ELSE 0 END), 0) AS horas_menos,
                COALESCE(SUM(CASE WHEN tipo_ajuste = 'DEDUCCION' THEN monto ELSE 0 END), 0) AS deducciones
             FROM ajustes_nomina
             WHERE personal_id = " . $personalId . "
               AND fecha_aplicacion BETWEEN '" . mysqli_real_escape_string($conexion, $periodo['fecha_inicio']) . "'
                                       AND '" . mysqli_real_escape_string($conexion, $periodo['fecha_fin']) . "'"
        ) ?: [];

        $horasExtra = (float)($rowHoras['horas_bono'] ?? 0);
        $horasMenos = (float)($rowHoras['horas_menos'] ?? 0);
        $otrosDescuentos = (float)($rowHoras['deducciones'] ?? 0);
        $pagoHorasExtra = $horasExtra * $salarioHora;
        $otrosDescuentos += $horasMenos * $salarioHora;

        $vacaciones = app_db_all(
            $conexion,
            "SELECT *
             FROM vacaciones_movimientos
             WHERE personal_id = " . $personalId . "
               AND estado <> 'CANCELADO'
               AND tipo IN ('GOZADAS', 'PAGADAS')
               AND fecha_inicio BETWEEN '" . mysqli_real_escape_string($conexion, $periodo['fecha_inicio']) . "'
                                   AND '" . mysqli_real_escape_string($conexion, $periodo['fecha_fin']) . "'"
        );
        $vacacionesMonto = 0.0;
        $primaVacacional = 0.0;
        foreach ($vacaciones as $movimiento) {
            $dias = (float)$movimiento['dias'];
            $monto = $dias * $salarioDiario;
            $prima = $monto * ((float)$movimiento['prima_porcentual'] / 100);
            $vacacionesMonto += $monto;
            $primaVacacional += $prima;
        }

        $bonosMonto = (float)app_db_value(
            $conexion,
            "SELECT COALESCE(SUM(monto), 0) AS total
             FROM bonos_personal
             WHERE personal_id = " . $personalId . "
               AND fecha_aplicacion BETWEEN '" . mysqli_real_escape_string($conexion, $periodo['fecha_inicio']) . "'
                                       AND '" . mysqli_real_escape_string($conexion, $periodo['fecha_fin']) . "'",
            0
        );

        $incapacidades = app_db_all(
            $conexion,
            "SELECT *
             FROM incapacidades_personal
             WHERE personal_id = " . $personalId . "
               AND fecha_inicio <= '" . mysqli_real_escape_string($conexion, $periodo['fecha_fin']) . "'
               AND fecha_fin >= '" . mysqli_real_escape_string($conexion, $periodo['fecha_inicio']) . "'"
        );
        $incapacidadesMonto = 0.0;
        foreach ($incapacidades as $incapacidad) {
            $dias = (float)$incapacidad['dias'];
            $montoDia = (float)$incapacidad['monto_por_dia'];
            if ($montoDia <= 0) {
                $montoDia = $salarioDiario;
            }
            $incapacidadesMonto += $dias * $montoDia;
        }

        $faltasMonto = (float)app_db_value(
            $conexion,
            "SELECT COALESCE(SUM(monto_descuento), 0) AS total
             FROM faltas_personal
             WHERE personal_id = " . $personalId . "
               AND categoria = 'FALTA'
               AND fecha_falta BETWEEN '" . mysqli_real_escape_string($conexion, $periodo['fecha_inicio']) . "'
                                   AND '" . mysqli_real_escape_string($conexion, $periodo['fecha_fin']) . "'",
            0
        );

        $descansosMonto = (float)app_db_value(
            $conexion,
            "SELECT COALESCE(SUM(monto_descuento), 0) AS total
             FROM faltas_personal
             WHERE personal_id = " . $personalId . "
               AND categoria = 'DESCANSO'
               AND goce_sueldo = 0
               AND fecha_falta BETWEEN '" . mysqli_real_escape_string($conexion, $periodo['fecha_inicio']) . "'
                                   AND '" . mysqli_real_escape_string($conexion, $periodo['fecha_fin']) . "'",
            0
        );

        $sancionesActivas = app_db_all(
            $conexion,
            "SELECT *
             FROM sanciones_personal
             WHERE personal_id = " . $personalId . "
               AND estado = 'ACTIVA'"
        );
        $descuentosSanciones = 0.0;
        foreach ($sancionesActivas as $sancion) {
            $descuentosSanciones += (float)$sancion['monto_por_quincena'];
        }

        $materialesActivos = app_db_all(
            $conexion,
            "SELECT *
             FROM descuentos_material
             WHERE personal_id = " . $personalId . "
               AND estado = 'ACTIVO'"
        );
        $descuentosMaterial = 0.0;
        foreach ($materialesActivos as $material) {
            $descuentosMaterial += (float)$material['monto_por_quincena'];
        }

        $capture = app_get_period_manual_capture($conexion, $periodoId, $personalId);
        $descuentosInfonavit = $capture ? (float)$capture['descuento_infonavit'] : (float)$personal['monto_infonavit_quincenal'];
        $descuentosFonacot = $capture ? (float)$capture['descuento_fonacot'] : (float)$personal['monto_fonacot_quincenal'];
        $otrosDescuentos += $capture ? (float)$capture['descuento_manual_otro'] : 0.0;

        $descuentosPrestamos = 0.0;
        foreach (app_get_prestamos_activos($conexion, $personalId) as $prestamo) {
            $saldo = (float)$prestamo['saldo_insoluto'];
            if ($saldo <= 0) {
                continue;
            }

            if (app_is_first_half_december($periodo['fecha_inicio'], $periodo['fecha_fin']) && strtotime($prestamo['fecha_limite']) <= strtotime($periodo['fecha_fin'])) {
                $saldoCaja = app_get_savings_balance($conexion, $personalId, $periodo['fecha_fin']);
                $montoCargo = min($saldoCaja, $saldo);

                if ($montoCargo > 0) {
                    $insertCaja = "
                        INSERT INTO caja_ahorro_movimientos (personal_id, periodo_id, fecha_aplicacion, tipo_movimiento, monto, descripcion)
                        VALUES (?, ?, ?, 'PRESTAMO_CARGO', ?, ?)
                    ";
                    $descripcion = 'Liquidación de préstamo con caja de ahorro';
                    if ($stmt = mysqli_prepare($conexion, $insertCaja)) {
                        $montoNegativo = -1 * $montoCargo;
                        $fechaAplicacionCaja = $periodo['fecha_fin'];
                        mysqli_stmt_bind_param($stmt, 'iisds', $personalId, $periodoId, $fechaAplicacionCaja, $montoNegativo, $descripcion);
                        mysqli_stmt_execute($stmt);
                        mysqli_stmt_close($stmt);
                    }

                    $saldoDespues = max(0.0, $saldo - $montoCargo);
                    mysqli_query(
                        $conexion,
                        "UPDATE prestamos_personal
                         SET saldo_insoluto = " . $saldoDespues . ",
                             estado = IF(" . $saldoDespues . " <= 0, 'LIQUIDADO', estado)
                         WHERE id = " . (int)$prestamo['id']
                    );

                    $insertPago = "
                        INSERT INTO prestamos_pagos (prestamo_id, personal_id, periodo_id, fecha_aplicacion, monto_capital, monto_interes, monto_total, saldo_despues, fuente)
                        VALUES (?, ?, ?, ?, ?, 0, ?, ?, 'CAJA_AHORRO')
                    ";
                    if ($stmt = mysqli_prepare($conexion, $insertPago)) {
                        $prestamoId = (int)$prestamo['id'];
                        $fechaPagoPrestamo = $periodo['fecha_fin'];
                        $montoCapitalPago = $montoCargo;
                        $montoTotalPago = $montoCargo;
                        mysqli_stmt_bind_param(
                            $stmt,
                            'iiisddd',
                            $prestamoId,
                            $personalId,
                            $periodoId,
                            $fechaPagoPrestamo,
                            $montoCapitalPago,
                            $montoTotalPago,
                            $saldoDespues
                        );
                        mysqli_stmt_execute($stmt);
                        mysqli_stmt_close($stmt);
                    }
                }
                continue;
            }

            $tasa = (float)$prestamo['tasa_porcentual'];
            $principalObjetivo = (float)$prestamo['monto_autorizado'] / max(1, (int)$prestamo['plazo_quincenas']);
            $interes = round($saldo * ($tasa / 100), 2);
            $montoCapital = min($saldo, $principalObjetivo);
            $montoTotal = $montoCapital + $interes;
            $saldoDespues = max(0.0, $saldo - $montoCapital);

            $descuentosPrestamos += $montoTotal;

            $insertPago = "
                INSERT INTO prestamos_pagos (prestamo_id, personal_id, periodo_id, fecha_aplicacion, monto_capital, monto_interes, monto_total, saldo_despues, fuente)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'NOMINA')
            ";
            if ($stmt = mysqli_prepare($conexion, $insertPago)) {
                $prestamoId = (int)$prestamo['id'];
                $fechaPagoPrestamo = $periodo['fecha_fin'];
                mysqli_stmt_bind_param(
                    $stmt,
                    'iiisdddd',
                    $prestamoId,
                    $personalId,
                    $periodoId,
                    $fechaPagoPrestamo,
                    $montoCapital,
                    $interes,
                    $montoTotal,
                    $saldoDespues
                );
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
            }

            mysqli_query(
                $conexion,
                "UPDATE prestamos_personal
                 SET saldo_insoluto = " . $saldoDespues . ",
                     estado = IF(" . $saldoDespues . " <= 0, 'LIQUIDADO', estado)
                 WHERE id = " . (int)$prestamo['id']
            );
        }

        $descuentosAdelantos = app_get_total_adelantos_periodo($conexion, $periodoId, $personalId);

        $participaCaja = (int)$personal['participa_caja_ahorro'] === 1;
        $aportacionCaja = $participaCaja ? (float)$personal['aportacion_caja_ahorro_quincenal'] : 0.0;
        if ($aportacionCaja > 0) {
            $sqlCaja = "
                INSERT INTO caja_ahorro_movimientos (personal_id, periodo_id, fecha_aplicacion, tipo_movimiento, monto, descripcion)
                VALUES (?, ?, ?, 'APORTACION', ?, 'Aportación quincenal de caja de ahorro')
            ";
            if ($stmt = mysqli_prepare($conexion, $sqlCaja)) {
                $fechaAportacion = $periodo['fecha_fin'];
                mysqli_stmt_bind_param($stmt, 'iisd', $personalId, $periodoId, $fechaAportacion, $aportacionCaja);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
            }
        }

        $holidays = app_db_all(
            $conexion,
            "SELECT *
             FROM dias_festivos
             WHERE fecha BETWEEN '" . mysqli_real_escape_string($conexion, $periodo['fecha_inicio']) . "'
                             AND '" . mysqli_real_escape_string($conexion, $periodo['fecha_fin']) . "'"
        );
        $diasFestivosMonto = 0.0;
        foreach ($holidays as $holiday) {
            foreach ($turnos as $turno) {
                $start = date('Y-m-d', strtotime($turno['hora_inicio']));
                $end = date('Y-m-d', strtotime($turno['hora_fin']));
                if ($holiday['fecha'] >= $start && $holiday['fecha'] <= $end) {
                    $diasFestivosMonto += $salarioDiario * max(0.0, $factorFestivo - 1.0);
                }
            }
        }

        $finiquitoMonto = 0.0;
        if (($personal['estatus_finiquito'] ?? 'NINGUNO') === 'PENDIENTE') {
            $finiquitoMonto = (float)$personal['finiquito_monto'];
        }

        $percepciones = $salarioBase + $pagoHorasExtra + $turnosExtraMonto + $vacacionesMonto + $primaVacacional + $diasFestivosMonto + $incapacidadesMonto + $bonosMonto + $finiquitoMonto;
        $deducciones = $descuentoRetardos + $faltasMonto + $descansosMonto + $descuentosSanciones + $descuentosMaterial + $descuentosInfonavit + $descuentosFonacot + $descuentosPrestamos + $descuentosAdelantos + $otrosDescuentos + $aportacionCaja;
        $neto = $percepciones - $deducciones;

        $resumenData = [
            'salario_diario' => $salarioDiario,
            'salario_hora' => $salarioHora,
            'salario_base' => $salarioBase,
            'retardos_horas' => $retardosHoras,
            'descuento_retardos' => $descuentoRetardos,
            'horas_extra' => $horasExtra,
            'pago_horas_extra' => $pagoHorasExtra,
            'turnos_extra_monto' => $turnosExtraMonto,
            'vacaciones_monto' => $vacacionesMonto,
            'prima_vacacional_monto' => $primaVacacional,
            'dias_festivos_monto' => $diasFestivosMonto,
            'incapacidades_monto' => $incapacidadesMonto,
            'bonos_monto' => $bonosMonto,
            'descuentos_faltas' => $faltasMonto,
            'descuentos_descansos' => $descansosMonto,
            'descuentos_sanciones' => $descuentosSanciones,
            'descuentos_material' => $descuentosMaterial,
            'descuentos_infonavit' => $descuentosInfonavit,
            'descuentos_fonacot' => $descuentosFonacot,
            'descuentos_prestamos' => $descuentosPrestamos,
            'descuentos_adelantos' => $descuentosAdelantos,
            'otros_descuentos' => $otrosDescuentos + $aportacionCaja,
            'finiquito_monto' => $finiquitoMonto,
            'neto' => $neto,
            'turno_cancelado' => $turnosCancelados,
        ];

        $resumenId = app_upsert_nomina_resumen($conexion, $periodoId, $personalId, $resumenData);

        app_save_nomina_concepto($conexion, $resumenId, $periodoId, $personalId, 'PERCEPCION', 'SALARIO_BASE', 'Salario base por turnos', count($turnos), $salarioBase);
        app_save_nomina_concepto($conexion, $resumenId, $periodoId, $personalId, 'DEDUCCION', 'RETARDOS', 'Descuento por retardos', $retardosHoras, $descuentoRetardos);
        app_save_nomina_concepto($conexion, $resumenId, $periodoId, $personalId, 'PERCEPCION', 'HORAS_EXTRA', 'Pago de horas extra', $horasExtra, $pagoHorasExtra);
        app_save_nomina_concepto($conexion, $resumenId, $periodoId, $personalId, 'PERCEPCION', 'TURNOS_EXTRA', 'Pago de turnos extra', 1, $turnosExtraMonto);
        app_save_nomina_concepto($conexion, $resumenId, $periodoId, $personalId, 'PERCEPCION', 'VACACIONES', 'Vacaciones pagadas', 1, $vacacionesMonto);
        app_save_nomina_concepto($conexion, $resumenId, $periodoId, $personalId, 'PERCEPCION', 'PRIMA_VACACIONAL', 'Prima vacacional', 1, $primaVacacional);
        app_save_nomina_concepto($conexion, $resumenId, $periodoId, $personalId, 'PERCEPCION', 'DIA_FESTIVO', 'Pago adicional por día festivo', 1, $diasFestivosMonto);
        app_save_nomina_concepto($conexion, $resumenId, $periodoId, $personalId, 'PERCEPCION', 'INCAPACIDAD', 'Pago de incapacidades', 1, $incapacidadesMonto);
        app_save_nomina_concepto($conexion, $resumenId, $periodoId, $personalId, 'PERCEPCION', 'BONOS', 'Bonos y complementos', 1, $bonosMonto);
        app_save_nomina_concepto($conexion, $resumenId, $periodoId, $personalId, 'DEDUCCION', 'FALTAS', 'Descuentos por faltas', 1, $faltasMonto);
        app_save_nomina_concepto($conexion, $resumenId, $periodoId, $personalId, 'DEDUCCION', 'DESCANSOS', 'Descansos sin goce', 1, $descansosMonto);
        app_save_nomina_concepto($conexion, $resumenId, $periodoId, $personalId, 'DEDUCCION', 'SANCIONES', 'Descuentos por sanciones', 1, $descuentosSanciones);
        app_save_nomina_concepto($conexion, $resumenId, $periodoId, $personalId, 'DEDUCCION', 'MATERIAL', 'Descuentos por material', 1, $descuentosMaterial);
        app_save_nomina_concepto($conexion, $resumenId, $periodoId, $personalId, 'DEDUCCION', 'INFONAVIT', 'Descuento Infonavit', 1, $descuentosInfonavit);
        app_save_nomina_concepto($conexion, $resumenId, $periodoId, $personalId, 'DEDUCCION', 'FONACOT', 'Descuento Fonacot', 1, $descuentosFonacot);
        app_save_nomina_concepto($conexion, $resumenId, $periodoId, $personalId, 'DEDUCCION', 'PRESTAMO', 'Descuento por préstamo', 1, $descuentosPrestamos);
        app_save_nomina_concepto($conexion, $resumenId, $periodoId, $personalId, 'DEDUCCION', 'ADELANTO', 'Descuento por adelanto de nómina', 1, $descuentosAdelantos);
        app_save_nomina_concepto($conexion, $resumenId, $periodoId, $personalId, 'DEDUCCION', 'CAJA_AHORRO', 'Aportación a caja de ahorro', 1, $aportacionCaja);
        app_save_nomina_concepto($conexion, $resumenId, $periodoId, $personalId, 'DEDUCCION', 'OTROS', 'Otros descuentos', 1, $otrosDescuentos);
        app_save_nomina_concepto($conexion, $resumenId, $periodoId, $personalId, 'PERCEPCION', 'FINIQUITO', 'Pago de finiquito', 1, $finiquitoMonto);

        foreach ($sancionesActivas as $sancion) {
            $restantes = max(0, ((int)$sancion['quincenas_restantes']) - 1);
            $estado = $restantes <= 0 ? 'LIQUIDADA' : 'ACTIVA';
            mysqli_query(
                $conexion,
                "UPDATE sanciones_personal
                 SET quincenas_restantes = " . $restantes . ",
                     estado = '" . $estado . "'
                 WHERE id = " . (int)$sancion['id']
            );
        }

        foreach ($materialesActivos as $material) {
            $restantes = max(0, ((int)$material['quincenas_restantes']) - 1);
            $estado = $restantes <= 0 ? 'LIQUIDADO' : 'ACTIVO';
            mysqli_query(
                $conexion,
                "UPDATE descuentos_material
                 SET quincenas_restantes = " . $restantes . ",
                     estado = '" . $estado . "'
                 WHERE id = " . (int)$material['id']
            );
        }

        app_recalculate_vacation_balance($conexion, $personalId, $configs, $periodo['fecha_fin']);
    }

    mysqli_query($conexion, "UPDATE nomina_periodos SET estado = 'CALCULADO' WHERE id = " . (int)$periodoId);

    return [
        'ok' => true,
        'message' => 'Nómina calculada correctamente para ' . count($personalList) . ' colaboradores.',
    ];
}

function app_nomina_rows(mysqli $conexion, int $periodoId): array
{
    $sql = "
        SELECT
            nr.*,
            p.numero_empleado,
            p.fecha_contratacion,
            p.nombres,
            p.apellidos,
            u.usuario
        FROM nomina_resumen nr
        INNER JOIN personal p ON p.id = nr.personal_id
        INNER JOIN usuarios u ON u.id = p.usuario_id
        WHERE nr.periodo_id = " . (int)$periodoId . "
        ORDER BY p.nombres, p.apellidos
    ";

    return app_db_all($conexion, $sql);
}

function app_nomina_totals(array $rows): array
{
    $totals = [
        'colaboradores' => count($rows),
        'salario_base' => 0.0,
        'percepciones' => 0.0,
        'deducciones' => 0.0,
        'neto' => 0.0,
    ];

    foreach ($rows as $row) {
        $percepciones = (float)$row['salario_base']
            + (float)$row['pago_horas_extra']
            + (float)$row['turnos_extra_monto']
            + (float)$row['vacaciones_monto']
            + (float)$row['prima_vacacional_monto']
            + (float)$row['dias_festivos_monto']
            + (float)$row['incapacidades_monto']
            + (float)$row['bonos_monto']
            + (float)$row['finiquito_monto'];

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

        $totals['salario_base'] += (float)$row['salario_base'];
        $totals['percepciones'] += $percepciones;
        $totals['deducciones'] += $deducciones;
        $totals['neto'] += (float)$row['neto'];
    }

    return $totals;
}

function app_total_savings_pool(mysqli $conexion): float
{
    $personal = app_get_active_personal($conexion);
    $total = 0.0;

    foreach ($personal as $row) {
        if ((int)$row['participa_caja_ahorro'] !== 1) {
            continue;
        }
        $total += app_get_savings_balance($conexion, (int)$row['id']);
    }

    $prestamosVigentes = (float)app_db_value(
        $conexion,
        "SELECT COALESCE(SUM(saldo_insoluto), 0) AS total
         FROM prestamos_personal
         WHERE estado = 'ACTIVO'",
        0
    );

    return max(0.0, $total - $prestamosVigentes);
}

function app_projected_savings_pool(mysqli $conexion, array $configs, ?string $referenceDate = null): array
{
    $referenceDate = $referenceDate ?: date('Y-m-d');
    $currentPool = app_total_savings_pool($conexion);
    $endOfYear = date('Y-12-31', strtotime($referenceDate));

    $remainingQuincenas = 0;
    $cursor = new DateTime($referenceDate);
    $limit = new DateTime($endOfYear);
    while ($cursor <= $limit) {
        $day = (int)$cursor->format('d');
        if ($day === 1 || $day === 16) {
            $remainingQuincenas++;
        }
        $cursor->modify('+1 day');
    }

    $projectedContributions = 0.0;
    foreach (app_get_active_personal($conexion) as $row) {
        if ((int)$row['participa_caja_ahorro'] !== 1) {
            continue;
        }
        $projectedContributions += ((float)$row['aportacion_caja_ahorro_quincenal']) * $remainingQuincenas;
    }

    $interestCollected = (float)app_db_value(
        $conexion,
        "SELECT COALESCE(SUM(monto_interes), 0) AS total
         FROM prestamos_pagos
         WHERE fecha_aplicacion BETWEEN '" . date('Y-01-01', strtotime($referenceDate)) . "'
                                     AND '" . $endOfYear . "'",
        0
    );

    $interestProjected = 0.0;
    $prestamosActivos = app_db_all($conexion, "SELECT saldo_insoluto, tasa_porcentual, fecha_limite FROM prestamos_personal WHERE estado = 'ACTIVO'");
    foreach ($prestamosActivos as $prestamo) {
        $fechaLimite = min($endOfYear, $prestamo['fecha_limite']);
        $diasRestantes = max(0, (int)floor((strtotime($fechaLimite) - strtotime($referenceDate)) / 86400));
        $quincenasRestantes = max(0, (int)ceil($diasRestantes / 15));
        $interestProjected += ((float)$prestamo['saldo_insoluto'] * ((float)$prestamo['tasa_porcentual'] / 100)) * $quincenasRestantes;
    }

    $interestPool = $interestCollected + $interestProjected;

    return [
        'current_pool' => $currentPool,
        'projected_contributions' => $projectedContributions,
        'interest_pool' => $interestPool,
        'projected_year_end' => $currentPool + $projectedContributions + $interestPool,
    ];
}

function app_distribute_savings_interest(mysqli $conexion, int $year, array $configs, ?string $fechaAplicacion = null): array
{
    $fechaAplicacion = $fechaAplicacion ?: ($year . '-12-16');
    $monthDay = app_month_day($fechaAplicacion);

    if ($monthDay < '12-16') {
        return ['ok' => false, 'message' => 'La distribución de intereses solo debe ejecutarse después de la primera quincena de diciembre.'];
    }

    $alreadyDistributed = (int)app_db_value(
        $conexion,
        "SELECT COUNT(*) AS total
         FROM caja_ahorro_movimientos
         WHERE tipo_movimiento = 'INTERES'
           AND fecha_aplicacion BETWEEN '" . $year . "-01-01'
                                     AND '" . $year . "-12-31'",
        0
    );

    if ($alreadyDistributed > 0) {
        return ['ok' => false, 'message' => 'Los intereses de caja ya fueron distribuidos para ' . $year . '.'];
    }

    $projection = app_projected_savings_pool($conexion, $configs, $year . '-12-16');
    $interestPool = (float)$projection['interest_pool'];
    if ($interestPool <= 0) {
        return ['ok' => false, 'message' => 'No hay intereses disponibles para distribuir.'];
    }

    $participants = [];
    $totalBalances = 0.0;
    $sql = "
        SELECT *
        FROM personal
        WHERE participa_caja_ahorro = 1
          AND (fecha_baja IS NULL OR fecha_baja > '" . $year . "-12-31')
    ";

    foreach (app_db_all($conexion, $sql) as $personal) {
        $saldo = app_get_savings_balance($conexion, (int)$personal['id'], $year . '-12-31');
        if ($saldo <= 0) {
            continue;
        }
        $participants[] = ['id' => (int)$personal['id'], 'saldo' => $saldo];
        $totalBalances += $saldo;
    }

    if ($totalBalances <= 0 || !$participants) {
        return ['ok' => false, 'message' => 'No hay participantes elegibles para repartir intereses.'];
    }

    foreach ($participants as $participant) {
        $share = round($interestPool * ($participant['saldo'] / $totalBalances), 2);
        if ($share <= 0) {
            continue;
        }

        $descripcion = 'Distribución anual de intereses de caja ' . $year;
        $sqlInsert = "
            INSERT INTO caja_ahorro_movimientos (personal_id, periodo_id, fecha_aplicacion, tipo_movimiento, monto, descripcion)
            VALUES (?, NULL, ?, 'INTERES', ?, ?)
        ";
        if ($stmt = mysqli_prepare($conexion, $sqlInsert)) {
            $participantId = $participant['id'];
            mysqli_stmt_bind_param($stmt, 'isds', $participantId, $fechaAplicacion, $share, $descripcion);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
    }

    return ['ok' => true, 'message' => 'Intereses de caja distribuidos para ' . count($participants) . ' colaboradores.'];
}
