<?php

require_once __DIR__ . '/helpers.php';

function app_ensure_schema_once(mysqli $conexion, int $ttlSeconds = 86400): void
{
    $mode = getenv('APP_SCHEMA_BOOTSTRAP') ?: 'once';

    if ($mode === 'never') {
        return;
    }

    if ($mode === 'always') {
        app_ensure_schema($conexion);
        return;
    }

    $schemaVersion = '2026-05-30-checkin-employee-number-search-v2';
    $marker = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR)
        . DIRECTORY_SEPARATOR
        . 'mstv_control_schema_' . sha1(__DIR__ . '|' . $schemaVersion) . '.ready';

    if (is_file($marker) && (time() - filemtime($marker)) < $ttlSeconds) {
        return;
    }

    app_ensure_schema($conexion);
    @touch($marker);
}

function app_ensure_schema(mysqli $conexion): void
{
    $queries = [
        //"ALTER TABLE personal ADD COLUMN IF NOT EXISTS numero_empleado VARCHAR(30) NULL AFTER id",
        //"ALTER TABLE personal ADD COLUMN IF NOT EXISTS salario_diario DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER estado",
        //"ALTER TABLE personal ADD COLUMN IF NOT EXISTS salario_hora DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER salario_diario",
        //"ALTER TABLE personal ADD COLUMN IF NOT EXISTS tipo_contrato VARCHAR(50) NULL AFTER salario_hora",
        //"ALTER TABLE personal ADD COLUMN IF NOT EXISTS participa_caja_ahorro TINYINT(1) NOT NULL DEFAULT 1 AFTER tipo_contrato",
        //"ALTER TABLE personal ADD COLUMN IF NOT EXISTS aportacion_caja_ahorro_quincenal DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER participa_caja_ahorro",
        //"ALTER TABLE personal ADD COLUMN IF NOT EXISTS saldo_caja_ahorro_inicial DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER aportacion_caja_ahorro_quincenal",
        //"ALTER TABLE personal ADD COLUMN IF NOT EXISTS dias_vacaciones_disponibles DECIMAL(8,2) NOT NULL DEFAULT 0.00 AFTER saldo_caja_ahorro_inicial",
        //"ALTER TABLE personal ADD COLUMN IF NOT EXISTS fecha_ultimo_calculo_vacaciones DATE NULL AFTER dias_vacaciones_disponibles",
        //"ALTER TABLE personal ADD COLUMN IF NOT EXISTS tiene_infonavit TINYINT(1) NOT NULL DEFAULT 0 AFTER fecha_ultimo_calculo_vacaciones",
        //"ALTER TABLE personal ADD COLUMN IF NOT EXISTS monto_infonavit_quincenal DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER tiene_infonavit",
        //"ALTER TABLE personal ADD COLUMN IF NOT EXISTS tiene_fonacot TINYINT(1) NOT NULL DEFAULT 0 AFTER monto_infonavit_quincenal",
        //"ALTER TABLE personal ADD COLUMN IF NOT EXISTS monto_fonacot_quincenal DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER tiene_fonacot",
        //"ALTER TABLE personal ADD COLUMN IF NOT EXISTS limite_adelanto_nomina DECIMAL(10,2) NULL AFTER monto_fonacot_quincenal",
        //"ALTER TABLE personal ADD COLUMN IF NOT EXISTS activo_en_nomina TINYINT(1) NOT NULL DEFAULT 1 AFTER limite_adelanto_nomina",
        //"ALTER TABLE personal ADD COLUMN IF NOT EXISTS fecha_baja DATE NULL AFTER activo_en_nomina",
        //"ALTER TABLE personal ADD COLUMN IF NOT EXISTS motivo_baja VARCHAR(255) NULL AFTER fecha_baja",
        //"ALTER TABLE personal ADD COLUMN IF NOT EXISTS finiquito_monto DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER motivo_baja",
        //"ALTER TABLE personal ADD COLUMN IF NOT EXISTS estatus_finiquito ENUM('NINGUNO','PENDIENTE','CERRADO') NOT NULL DEFAULT 'NINGUNO' AFTER finiquito_monto",
        //"ALTER TABLE personal ADD COLUMN IF NOT EXISTS vacaciones_2024_notas TEXT NULL AFTER estatus_finiquito",
        //"ALTER TABLE personal ADD COLUMN IF NOT EXISTS vacaciones_2025_notas TEXT NULL AFTER vacaciones_2024_notas",
        //"ALTER TABLE personal ADD COLUMN IF NOT EXISTS vacaciones_2026_notas TEXT NULL AFTER vacaciones_2025_notas",
        //"ALTER TABLE personal ADD COLUMN IF NOT EXISTS puesto_operativo VARCHAR(100) NULL AFTER vacaciones_2026_notas",
        //"ALTER TABLE personal ADD COLUMN IF NOT EXISTS turno_base VARCHAR(50) NULL AFTER puesto_operativo",
        //"ALTER TABLE personal ADD COLUMN IF NOT EXISTS servicio_asignado VARCHAR(150) NULL AFTER turno_base",
        //"ALTER TABLE personal ADD COLUMN IF NOT EXISTS infospe_estatus VARCHAR(50) NULL AFTER servicio_asignado",
        //"ALTER TABLE personal ADD COLUMN IF NOT EXISTS cecceg_estatus VARCHAR(50) NULL AFTER infospe_estatus",
        //"ALTER TABLE personal ADD COLUMN IF NOT EXISTS sexo VARCHAR(30) NULL AFTER cecceg_estatus",
        //"ALTER TABLE personal ADD COLUMN IF NOT EXISTS estado_civil VARCHAR(50) NULL AFTER sexo",
        //"ALTER TABLE personal ADD COLUMN IF NOT EXISTS domicilio TEXT NULL AFTER estado_civil",
        //"ALTER TABLE personal ADD COLUMN IF NOT EXISTS codigo_postal VARCHAR(10) NULL AFTER domicilio",
        //"ALTER TABLE personal ADD COLUMN IF NOT EXISTS nss VARCHAR(30) NULL AFTER codigo_postal",
        //"ALTER TABLE personal ADD COLUMN IF NOT EXISTS rfc VARCHAR(20) NULL AFTER nss",
        //"ALTER TABLE personal ADD COLUMN IF NOT EXISTS curp VARCHAR(25) NULL AFTER rfc",
        //"ALTER TABLE personal ADD COLUMN IF NOT EXISTS cuenta_bancaria VARCHAR(50) NULL AFTER curp",
        //"ALTER TABLE personal ADD COLUMN IF NOT EXISTS banco VARCHAR(80) NULL AFTER cuenta_bancaria",
        //"ALTER TABLE personal ADD COLUMN IF NOT EXISTS fecha_nacimiento DATE NULL AFTER banco",
        //"ALTER TABLE personal ADD COLUMN IF NOT EXISTS talla_camisa VARCHAR(30) NULL AFTER fecha_nacimiento",
        //"ALTER TABLE personal ADD COLUMN IF NOT EXISTS talla_pantalon VARCHAR(30) NULL AFTER talla_camisa",
        //"ALTER TABLE personal ADD COLUMN IF NOT EXISTS talla_calzado VARCHAR(30) NULL AFTER talla_pantalon",
        //"ALTER TABLE personal ADD COLUMN IF NOT EXISTS contacto_emergencia VARCHAR(150) NULL AFTER talla_calzado",
        //"ALTER TABLE personal ADD COLUMN IF NOT EXISTS contacto_emergencia_parentesco VARCHAR(80) NULL AFTER contacto_emergencia",
        //"ALTER TABLE personal ADD COLUMN IF NOT EXISTS contacto_emergencia_telefono VARCHAR(30) NULL AFTER contacto_emergencia_parentesco",
        //"ALTER TABLE personal ADD COLUMN IF NOT EXISTS tiene_hijos TINYINT(1) NOT NULL DEFAULT 0 AFTER contacto_emergencia_telefono",
        //"ALTER TABLE personal ADD COLUMN IF NOT EXISTS edades_hijos VARCHAR(255) NULL AFTER tiene_hijos",
        //"ALTER TABLE personal ADD COLUMN IF NOT EXISTS folio_base_excel VARCHAR(30) NULL AFTER edades_hijos",

        //"ALTER TABLE roles ADD COLUMN IF NOT EXISTS matriz_permisos LONGTEXT NULL AFTER descripcion",

        //"ALTER TABLE turnos ADD COLUMN IF NOT EXISTS horas_programadas DECIMAL(5,2) NOT NULL DEFAULT 12.00 AFTER hora_fin",
        //"ALTER TABLE turnos ADD COLUMN IF NOT EXISTS hora_entrada_real DATETIME NULL AFTER horas_programadas",
        //"ALTER TABLE turnos ADD COLUMN IF NOT EXISTS hora_salida_real DATETIME NULL AFTER hora_entrada_real",
        //"ALTER TABLE turnos ADD COLUMN IF NOT EXISTS retardo_minutos INT NOT NULL DEFAULT 0 AFTER hora_salida_real",
        //"ALTER TABLE turnos ADD COLUMN IF NOT EXISTS retardo_horas_cobradas INT NOT NULL DEFAULT 0 AFTER retardo_minutos",
        //"ALTER TABLE turnos ADD COLUMN IF NOT EXISTS turno_cancelado_por_retardo TINYINT(1) NOT NULL DEFAULT 0 AFTER retardo_horas_cobradas",
        //"ALTER TABLE turnos ADD COLUMN IF NOT EXISTS tipo_turno_extra ENUM('NINGUNO','MEDIO','COMPLETO','PERSONALIZADO') NOT NULL DEFAULT 'NINGUNO' AFTER turno_cancelado_por_retardo",
        //"ALTER TABLE turnos ADD COLUMN IF NOT EXISTS monto_turno_extra DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER tipo_turno_extra",
        //"ALTER TABLE turnos ADD COLUMN IF NOT EXISTS observaciones_nomina TEXT NULL AFTER monto_turno_extra",

        // "ALTER TABLE registros_asistencia ADD COLUMN IF NOT EXISTS puntaje_facial DECIMAL(5,2) NULL AFTER url_selfie",
        // "ALTER TABLE registros_asistencia ADD COLUMN IF NOT EXISTS verificado_vida TINYINT(1) DEFAULT 0 AFTER puntaje_facial",
        "ALTER TABLE registros_asistencia MODIFY COLUMN estado ENUM('ACEPTADO','RECHAZADO_ROSTRO','RECHAZADO_GPS','PENDIENTE_REVISION') DEFAULT 'ACEPTADO'",

        "CREATE TABLE IF NOT EXISTS nomina_periodos (
            id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
            clave VARCHAR(50) NOT NULL UNIQUE,
            fecha_inicio DATE NOT NULL,
            fecha_fin DATE NOT NULL,
            fecha_pago DATE NOT NULL,
            anio INT NOT NULL,
            numero_quincena INT NOT NULL,
            estado ENUM('ABIERTO','CALCULADO','CERRADO') NOT NULL DEFAULT 'ABIERTO',
            fecha_creacion TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        "CREATE TABLE IF NOT EXISTS nomina_resumen (
            id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
            periodo_id INT NOT NULL,
            personal_id INT NOT NULL,
            salario_diario DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            salario_hora DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            salario_base DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            retardos_horas DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            descuento_retardos DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            horas_extra DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            pago_horas_extra DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            turnos_extra_monto DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            vacaciones_monto DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            prima_vacacional_monto DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            dias_festivos_monto DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            incapacidades_monto DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            bonos_monto DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            descuentos_faltas DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            descuentos_descansos DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            descuentos_sanciones DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            descuentos_material DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            descuentos_infonavit DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            descuentos_fonacot DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            descuentos_prestamos DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            descuentos_adelantos DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            otros_descuentos DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            finiquito_monto DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            neto DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            turno_cancelado INT NOT NULL DEFAULT 0,
            fecha_calculo TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uk_nomina_resumen (periodo_id, personal_id),
            KEY idx_nomina_resumen_personal (personal_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

       // "ALTER TABLE nomina_resumen ADD COLUMN IF NOT EXISTS descuentos_descansos DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER descuentos_faltas",

        "CREATE TABLE IF NOT EXISTS nomina_conceptos (
            id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
            resumen_id INT NOT NULL,
            periodo_id INT NOT NULL,
            personal_id INT NOT NULL,
            categoria ENUM('PERCEPCION','DEDUCCION','INFORMATIVO') NOT NULL DEFAULT 'INFORMATIVO',
            clave VARCHAR(50) NOT NULL,
            descripcion VARCHAR(255) NOT NULL,
            cantidad DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            monto DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            referencia_tabla VARCHAR(80) NULL,
            referencia_id INT NULL,
            fecha_creacion TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            KEY idx_nomina_conceptos_periodo (periodo_id, personal_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        "CREATE TABLE IF NOT EXISTS nomina_capturas (
            id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
            periodo_id INT NOT NULL,
            personal_id INT NOT NULL,
            descuento_infonavit DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            descuento_fonacot DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            descuento_manual_otro DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            observaciones TEXT NULL,
            fecha_creacion TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uk_nomina_capturas (periodo_id, personal_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        "CREATE TABLE IF NOT EXISTS adelantos_nomina (
            id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
            personal_id INT NOT NULL,
            periodo_id INT NULL,
            fecha_solicitud DATE NOT NULL,
            fecha_aplicacion DATE NOT NULL,
            monto DECIMAL(10,2) NOT NULL,
            motivo TEXT NULL,
            tipo ENUM('MASIVO','INDIVIDUAL') NOT NULL DEFAULT 'INDIVIDUAL',
            estado ENUM('PENDIENTE','APLICADO','CANCELADO') NOT NULL DEFAULT 'APLICADO',
            fecha_creacion TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            KEY idx_adelantos_personal (personal_id, fecha_aplicacion)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        "CREATE TABLE IF NOT EXISTS faltas_personal (
            id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
            personal_id INT NOT NULL,
            periodo_id INT NULL,
            categoria ENUM('FALTA','DESCANSO') NOT NULL DEFAULT 'FALTA',
            goce_sueldo TINYINT(1) NOT NULL DEFAULT 0,
            fecha_falta DATE NOT NULL,
            tipo ENUM('JUSTIFICADA','INJUSTIFICADA','AJUSTADA') NOT NULL,
            monto_descuento DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            motivo TEXT NULL,
            fecha_creacion TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            KEY idx_faltas_personal (personal_id, fecha_falta)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        //"ALTER TABLE faltas_personal ADD COLUMN IF NOT EXISTS categoria ENUM('FALTA','DESCANSO') NOT NULL DEFAULT 'FALTA' AFTER periodo_id",
        //"ALTER TABLE faltas_personal ADD COLUMN IF NOT EXISTS goce_sueldo TINYINT(1) NOT NULL DEFAULT 0 AFTER categoria",

        "CREATE TABLE IF NOT EXISTS bonos_personal (
            id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
            personal_id INT NOT NULL,
            periodo_id INT NULL,
            fecha_aplicacion DATE NOT NULL,
            categoria ENUM('BONO','INCAPACIDAD','VACACIONES','OTRO') NOT NULL DEFAULT 'BONO',
            monto DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            motivo TEXT NULL,
            fecha_creacion TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            KEY idx_bonos_personal (personal_id, fecha_aplicacion)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        "CREATE TABLE IF NOT EXISTS descuentos_material (
            id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
            personal_id INT NOT NULL,
            fecha_aplicacion DATE NOT NULL,
            tipo_material VARCHAR(100) NOT NULL DEFAULT 'Botas',
            monto_total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            quincenas_total INT NOT NULL DEFAULT 2,
            quincenas_restantes INT NOT NULL DEFAULT 2,
            monto_por_quincena DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            estado ENUM('ACTIVO','LIQUIDADO','CANCELADO') NOT NULL DEFAULT 'ACTIVO',
            fecha_creacion TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            KEY idx_descuentos_material (personal_id, fecha_aplicacion)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        "CREATE TABLE IF NOT EXISTS sanciones_personal (
            id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
            personal_id INT NOT NULL,
            fecha_aplicacion DATE NOT NULL,
            motivo TEXT NOT NULL,
            monto_total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            quincenas_total INT NOT NULL DEFAULT 1,
            quincenas_restantes INT NOT NULL DEFAULT 1,
            monto_por_quincena DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            estado ENUM('ACTIVA','LIQUIDADA','CANCELADA') NOT NULL DEFAULT 'ACTIVA',
            fecha_creacion TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            KEY idx_sanciones_personal (personal_id, fecha_aplicacion)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        "CREATE TABLE IF NOT EXISTS vacaciones_movimientos (
            id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
            personal_id INT NOT NULL,
            fecha_solicitud DATE NOT NULL,
            fecha_inicio DATE NOT NULL,
            fecha_fin DATE NOT NULL,
            dias DECIMAL(8,2) NOT NULL DEFAULT 0.00,
            tipo ENUM('GOZADAS','PAGADAS','AJUSTE','NOTA') NOT NULL DEFAULT 'GOZADAS',
            prima_porcentual DECIMAL(5,2) NOT NULL DEFAULT 25.00,
            monto_prima DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            monto_pago DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            estado ENUM('CAPTURADO','APLICADO','CANCELADO') NOT NULL DEFAULT 'CAPTURADO',
            notas TEXT NULL,
            origen VARCHAR(50) NULL,
            referencia VARCHAR(80) NULL,
            fecha_creacion TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            KEY idx_vacaciones_personal (personal_id, fecha_inicio)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        "ALTER TABLE vacaciones_movimientos MODIFY COLUMN tipo ENUM('GOZADAS','PAGADAS','AJUSTE','NOTA') NOT NULL DEFAULT 'GOZADAS'",
        //"ALTER TABLE vacaciones_movimientos ADD COLUMN IF NOT EXISTS origen VARCHAR(50) NULL AFTER notas",
        //"ALTER TABLE vacaciones_movimientos ADD COLUMN IF NOT EXISTS referencia VARCHAR(80) NULL AFTER origen",

        "INSERT INTO vacaciones_movimientos
            (personal_id, fecha_solicitud, fecha_inicio, fecha_fin, dias, tipo, prima_porcentual, monto_prima, monto_pago, estado, notas, origen, referencia)
         SELECT p.id, CURDATE(), '2024-01-01', '2024-12-31', 0, 'NOTA', 0, 0, 0, 'APLICADO', p.vacaciones_2024_notas, 'EXCEL_LEGACY', 'vacaciones_2024_notas'
         FROM personal p
         WHERE p.vacaciones_2024_notas IS NOT NULL
           AND TRIM(p.vacaciones_2024_notas) <> ''
           AND NOT EXISTS (
               SELECT 1 FROM vacaciones_movimientos vm
               WHERE vm.personal_id = p.id
                 AND vm.origen = 'EXCEL_LEGACY'
                 AND vm.referencia = 'vacaciones_2024_notas'
           )",

        "INSERT INTO vacaciones_movimientos
            (personal_id, fecha_solicitud, fecha_inicio, fecha_fin, dias, tipo, prima_porcentual, monto_prima, monto_pago, estado, notas, origen, referencia)
         SELECT p.id, CURDATE(), '2025-01-01', '2025-12-31', 0, 'NOTA', 0, 0, 0, 'APLICADO', p.vacaciones_2025_notas, 'EXCEL_LEGACY', 'vacaciones_2025_notas'
         FROM personal p
         WHERE p.vacaciones_2025_notas IS NOT NULL
           AND TRIM(p.vacaciones_2025_notas) <> ''
           AND NOT EXISTS (
               SELECT 1 FROM vacaciones_movimientos vm
               WHERE vm.personal_id = p.id
                 AND vm.origen = 'EXCEL_LEGACY'
                 AND vm.referencia = 'vacaciones_2025_notas'
           )",

        "INSERT INTO vacaciones_movimientos
            (personal_id, fecha_solicitud, fecha_inicio, fecha_fin, dias, tipo, prima_porcentual, monto_prima, monto_pago, estado, notas, origen, referencia)
         SELECT p.id, CURDATE(), '2026-01-01', '2026-12-31', 0, 'NOTA', 0, 0, 0, 'APLICADO', p.vacaciones_2026_notas, 'EXCEL_LEGACY', 'vacaciones_2026_notas'
         FROM personal p
         WHERE p.vacaciones_2026_notas IS NOT NULL
           AND TRIM(p.vacaciones_2026_notas) <> ''
           AND NOT EXISTS (
               SELECT 1 FROM vacaciones_movimientos vm
               WHERE vm.personal_id = p.id
                 AND vm.origen = 'EXCEL_LEGACY'
                 AND vm.referencia = 'vacaciones_2026_notas'
           )",

        "CREATE TABLE IF NOT EXISTS caja_ahorro_movimientos (
            id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
            personal_id INT NOT NULL,
            periodo_id INT NULL,
            fecha_aplicacion DATE NOT NULL,
            tipo_movimiento ENUM('APORTACION','RETIRO','INTERES','AJUSTE','LIQUIDACION_ANUAL','PRESTAMO_CARGO','PRESTAMO_ABONO') NOT NULL,
            monto DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            descripcion TEXT NULL,
            fecha_creacion TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            KEY idx_caja_ahorro_personal (personal_id, fecha_aplicacion)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        "CREATE TABLE IF NOT EXISTS prestamos_personal (
            id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
            personal_id INT NOT NULL,
            fecha_inicio DATE NOT NULL,
            monto_autorizado DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            saldo_inicial DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            saldo_insoluto DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            tasa_porcentual DECIMAL(5,2) NOT NULL DEFAULT 4.00,
            plazo_quincenas INT NOT NULL DEFAULT 1,
            descuento_quincenal DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            fecha_limite DATE NOT NULL,
            estado ENUM('ACTIVO','LIQUIDADO','CANCELADO') NOT NULL DEFAULT 'ACTIVO',
            observaciones TEXT NULL,
            fecha_creacion TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            KEY idx_prestamos_personal (personal_id, fecha_inicio)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        "CREATE TABLE IF NOT EXISTS prestamos_pagos (
            id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
            prestamo_id INT NOT NULL,
            personal_id INT NOT NULL,
            periodo_id INT NULL,
            fecha_aplicacion DATE NOT NULL,
            monto_capital DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            monto_interes DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            monto_total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            saldo_despues DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            fuente ENUM('NOMINA','CAJA_AHORRO','LIQUIDACION','AJUSTE') NOT NULL DEFAULT 'NOMINA',
            fecha_creacion TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            KEY idx_prestamos_pagos (prestamo_id, fecha_aplicacion)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        "CREATE TABLE IF NOT EXISTS dias_festivos (
            id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
            fecha DATE NOT NULL UNIQUE,
            nombre VARCHAR(120) NOT NULL,
            pago_factor DECIMAL(5,2) NOT NULL DEFAULT 2.00,
            fecha_creacion TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        "CREATE TABLE IF NOT EXISTS incapacidades_personal (
            id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
            personal_id INT NOT NULL,
            fecha_inicio DATE NOT NULL,
            fecha_fin DATE NOT NULL,
            dias DECIMAL(8,2) NOT NULL DEFAULT 0.00,
            monto_por_dia DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            motivo TEXT NULL,
            pagada TINYINT(1) NOT NULL DEFAULT 1,
            fecha_creacion TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            KEY idx_incapacidades_personal (personal_id, fecha_inicio)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        "CREATE TABLE IF NOT EXISTS bajas_personal (
            id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
            personal_id INT NOT NULL,
            usuario_id INT NULL,
            fecha_baja DATE NOT NULL,
            motivo TEXT NULL,
            finiquito_monto DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            estatus_pago ENUM('PENDIENTE','CERRADO') NOT NULL DEFAULT 'PENDIENTE',
            fecha_creacion TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            KEY idx_bajas_personal (personal_id, fecha_baja)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    ];

    foreach ($queries as $query) {
        @mysqli_query($conexion, $query);
    }

    $descansoSinGoceDefault = '700';
    if ($res = @mysqli_query($conexion, "SELECT valor_configuracion FROM configuracion_sistema WHERE clave_configuracion = 'faltas_descuento_injustificada' LIMIT 1")) {
        if ($row = mysqli_fetch_assoc($res)) {
            $descansoSinGoceDefault = (string)$row['valor_configuracion'];
        }
        mysqli_free_result($res);
    }

    $defaults = [
        'turnos_tolerancia_minutos' => ['15', 'Minutos de tolerancia antes de generar retardo.'],
        'turnos_max_retardo_horas' => ['4', 'Máximo de horas de retardo antes de cancelar el turno.'],
        'checadas_minutos_anticipacion' => ['20', 'Minutos antes del inicio programado en los que se permite registrar entrada.'],
        'facial_puntaje_minimo' => ['35', 'Puntaje facial mínimo para aceptar automáticamente una checada.'],
        'facial_selfie_min_width' => ['180', 'Ancho mínimo de selfie para validar evidencia facial.'],
        'facial_selfie_min_height' => ['180', 'Alto mínimo de selfie para validar evidencia facial.'],
        'nomina_valor_hora' => ['75', 'Valor operativo por hora para retardos y horas extra.'],
        'nomina_salario_minimo_diario' => ['278.80', 'Salario diario por defecto configurable para personal nuevo.'],
        'prestamos_tasa_quincenal' => ['4', 'Porcentaje aplicado sobre saldo insoluto del préstamo por quincena.'],
        'prestamos_monto_max_antiguedad_baja' => ['1000', 'Monto máximo para personal con un año o menos de antigüedad.'],
        'prestamos_monto_max_antiguedad_alta' => ['5000', 'Monto máximo para personal con más de un año de antigüedad.'],
        'prestamos_mes_inicio' => ['03', 'Mes en el que arrancan los préstamos.'],
        'prestamos_dia_inicio' => ['01', 'Día en el que arrancan los préstamos.'],
        'prestamos_mes_limite' => ['12', 'Mes límite para liquidar los préstamos.'],
        'prestamos_dia_limite' => ['15', 'Día límite de la primera quincena de diciembre para liquidar préstamos.'],
        'caja_pago_desde_dia' => ['16', 'Día inicial para entrega anual de caja de ahorro.'],
        'caja_pago_hasta_dia' => ['24', 'Día final para entrega anual de caja de ahorro.'],
        'vacaciones_dias_anio_1' => ['12', 'Días de vacaciones ganados al cumplir el año 1.'],
        'vacaciones_dias_anio_2' => ['14', 'Días de vacaciones ganados al cumplir el año 2.'],
        'vacaciones_dias_anio_3' => ['16', 'Días de vacaciones ganados al cumplir el año 3.'],
        'vacaciones_dias_anio_4' => ['18', 'Días de vacaciones ganados al cumplir el año 4.'],
        'vacaciones_dias_anio_5' => ['20', 'Días de vacaciones ganados al cumplir el año 5.'],
        'vacaciones_dias_anios_6_10' => ['22', 'Días de vacaciones ganados por cada año cumplido del 6 al 10.'],
        'vacaciones_dias_anios_11_15' => ['24', 'Días de vacaciones ganados por cada año cumplido del 11 al 15.'],
        'vacaciones_dias_anios_16_20' => ['26', 'Días de vacaciones ganados por cada año cumplido del 16 al 20.'],
        'vacaciones_dias_anios_21_25' => ['28', 'Días de vacaciones ganados por cada año cumplido del 21 al 25.'],
        'vacaciones_dias_anios_26_30' => ['30', 'Días de vacaciones ganados por cada año cumplido del 26 en adelante.'],
        'vacaciones_incremento_anual' => ['2', 'Parámetro heredado por compatibilidad; el cálculo principal usa la tabla escalonada.'],
        'vacaciones_prima_porcentaje' => ['25', 'Porcentaje de prima vacacional aplicado al pago.'],
        'faltas_descuento_justificada' => ['400', 'Descuento por falta justificada.'],
        'faltas_descuento_injustificada' => ['700', 'Descuento por falta no justificada.'],
        'faltas_descuento_ajustada' => ['0', 'Descuento por falta ajustada.'],
        'descansos_descuento_sin_goce' => [$descansoSinGoceDefault, 'Descuento por descanso sin goce de sueldo.'],
        'turnos_extra_12h_monto' => ['400', 'Monto por turno extra de 12 horas.'],
        'turnos_extra_24h_monto' => ['800', 'Monto por turno extra de 24 horas.'],
        'adelanto_masivo_monto' => ['500', 'Monto del adelanto masivo de nómina.'],
        'adelanto_masivo_dias_antes_nomina' => ['5', 'Días antes de la nómina para habilitar el adelanto masivo.'],
        'material_quincenas_default' => ['2', 'Número de quincenas por defecto para descuentos de material.'],
        'nomina_factor_dia_festivo' => ['2', 'Factor de pago para días festivos.'],
    ];

    foreach ($defaults as $clave => $data) {
        [$valor, $descripcion] = $data;
        $sql = "
            INSERT INTO configuracion_sistema (clave_configuracion, valor_configuracion, descripcion)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE
                descripcion = VALUES(descripcion),
                valor_configuracion = IF(valor_configuracion IS NULL OR valor_configuracion = '', VALUES(valor_configuracion), valor_configuracion)
        ";

        if ($stmt = mysqli_prepare($conexion, $sql)) {
            mysqli_stmt_bind_param($stmt, 'sss', $clave, $valor, $descripcion);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
    }
}
