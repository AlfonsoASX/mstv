<?php

require_once __DIR__ . '/lib/app.php';
require_once __DIR__ . '/lib/ui.php';
require_once __DIR__ . '/lib/payroll.php';
require_once __DIR__ . '/lib/operations.php';

app_require_session();
app_require_roles(['ADMIN', 'RH', 'NOMINA', 'DUEÑO', 'SUPERVISOR']);

function app_store_base_photo(array $file, int $personalId): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'message' => 'Selecciona una imagen válida para la foto base.'];
    }

    $tmpPath = (string)($file['tmp_name'] ?? '');
    if ($tmpPath === '' || !is_uploaded_file($tmpPath)) {
        return ['ok' => false, 'message' => 'No se recibió correctamente el archivo de la foto base.'];
    }

    $imageInfo = @getimagesize($tmpPath);
    if (!$imageInfo) {
        return ['ok' => false, 'message' => 'El archivo seleccionado no es una imagen válida.'];
    }

    $mimeMap = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    $mimeType = (string)($imageInfo['mime'] ?? '');
    if (!isset($mimeMap[$mimeType])) {
        return ['ok' => false, 'message' => 'Solo se permiten imágenes JPG, PNG o WEBP.'];
    }

    $folder = __DIR__ . '/uploads/personal/base';
    if (!is_dir($folder) && !mkdir($folder, 0777, true) && !is_dir($folder)) {
        return ['ok' => false, 'message' => 'No fue posible crear la carpeta para fotos base.'];
    }

    $extension = $mimeMap[$mimeType];
    $filename = 'personal_' . $personalId . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
    $targetPath = $folder . '/' . $filename;

    if (!move_uploaded_file($tmpPath, $targetPath)) {
        return ['ok' => false, 'message' => 'No fue posible guardar la nueva foto base.'];
    }

    return [
        'ok' => true,
        'path' => $targetPath,
        'relative_path' => 'uploads/personal/base/' . $filename,
    ];
}

function personal_clean_date_field(string $key): ?string
{
    $value = app_clean_text(app_post($key, ''));
    return $value === '' ? null : $value;
}

function personal_sql_value(mysqli $conexion, $value): string
{
    if ($value === null || $value === '') {
        return 'NULL';
    }

    return "'" . mysqli_real_escape_string($conexion, (string)$value) . "'";
}

$messages = ['success' => '', 'error' => ''];
$configs = app_get_config_map($conexion);

$personalId = (int)app_get('personal_id', 0);
if ($personalId <= 0) {
    $first = app_db_one($conexion, "SELECT id FROM personal ORDER BY nombres, apellidos LIMIT 1");
    if ($first) {
        app_redirect('personas-ficha.php?personal_id=' . (int)$first['id']);
    }
    app_redirect('personas-lista.php');
}

$personal = app_get_personal($conexion, $personalId);
if (!$personal) {
    app_redirect('personas-lista.php');
}

$currentBounds = app_current_period_bounds();
$currentPeriodId = app_get_or_create_period($conexion, $currentBounds['inicio'], $currentBounds['fin']);
$currentPeriod = app_get_period($conexion, $currentPeriodId);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = app_post('accion', '');

    if ($accion === 'actualizar_perfil') {
        $nombres = app_clean_text(app_post('nombres', $personal['nombres'] ?? ''));
        $apellidos = app_clean_text(app_post('apellidos', $personal['apellidos'] ?? ''));
        $email = app_clean_text(app_post('email', $personal['email'] ?? ''));
        $telefono = app_clean_text(app_post('telefono', $personal['telefono'] ?? ''));
        $fechaContratacion = personal_clean_date_field('fecha_contratacion');
        $fechaNacimiento = personal_clean_date_field('fecha_nacimiento');
        $puestoOperativo = app_clean_text(app_post('puesto_operativo', ''));
        $turnoBase = app_clean_text(app_post('turno_base', ''));
        $servicioAsignado = app_clean_text(app_post('servicio_asignado', ''));
        $infospeEstatus = app_clean_text(app_post('infospe_estatus', ''));
        $ceccegEstatus = app_clean_text(app_post('cecceg_estatus', ''));
        $sexo = app_clean_text(app_post('sexo', ''));
        $estadoCivil = app_clean_text(app_post('estado_civil', ''));
        $domicilio = app_clean_text(app_post('domicilio', ''));
        $codigoPostal = app_clean_text(app_post('codigo_postal', ''));
        $nss = app_clean_text(app_post('nss', ''));
        $rfc = app_clean_text(app_post('rfc', ''));
        $curp = app_clean_text(app_post('curp', ''));
        $cuentaBancaria = app_clean_text(app_post('cuenta_bancaria', ''));
        $banco = app_clean_text(app_post('banco', ''));
        $tallaCamisa = app_clean_text(app_post('talla_camisa', ''));
        $tallaPantalon = app_clean_text(app_post('talla_pantalon', ''));
        $tallaCalzado = app_clean_text(app_post('talla_calzado', ''));
        $contactoEmergencia = app_clean_text(app_post('contacto_emergencia', ''));
        $contactoEmergenciaParentesco = app_clean_text(app_post('contacto_emergencia_parentesco', ''));
        $contactoEmergenciaTelefono = app_clean_text(app_post('contacto_emergencia_telefono', ''));
        $tieneHijos = app_post('tiene_hijos') ? 1 : 0;
        $edadesHijos = app_clean_text(app_post('edades_hijos', ''));
        $vacaciones2024Notas = app_clean_text(app_post('vacaciones_2024_notas', $personal['vacaciones_2024_notas'] ?? ''));
        $vacaciones2025Notas = app_clean_text(app_post('vacaciones_2025_notas', $personal['vacaciones_2025_notas'] ?? ''));
        $vacaciones2026Notas = app_clean_text(app_post('vacaciones_2026_notas', $personal['vacaciones_2026_notas'] ?? ''));
        $salarioDiario = (float)app_post('salario_diario', 0);
        $salarioHora = (float)app_post('salario_hora', 0);
        $participaCaja = app_post('participa_caja_ahorro') ? 1 : 0;
        $aportacionCaja = (float)app_post('aportacion_caja_ahorro_quincenal', 0);
        $saldoCajaInicial = (float)app_post('saldo_caja_ahorro_inicial', 0);
        $tieneInfonavit = app_post('tiene_infonavit') ? 1 : 0;
        $montoInfonavit = (float)app_post('monto_infonavit_quincenal', 0);
        $tieneFonacot = app_post('tiene_fonacot') ? 1 : 0;
        $montoFonacot = (float)app_post('monto_fonacot_quincenal', 0);
        $limiteAdelanto = app_clean_text(app_post('limite_adelanto_nomina', ''));
        $tipoContrato = app_clean_text(app_post('tipo_contrato', ''));
        $activoNomina = app_post('activo_en_nomina') ? 1 : 0;

        $limiteAdelantoValue = $limiteAdelanto === '' ? 0.0 : (float)$limiteAdelanto;

        if ($nombres === '' || $apellidos === '') {
            $messages['error'] = 'Captura nombre y apellidos del colaborador.';
        } else {
            $sqlUsuario = "
                UPDATE usuarios
                SET email = " . personal_sql_value($conexion, $email) . "
                WHERE id = " . (int)$personal['usuario_id'] . "
                LIMIT 1
            ";
            mysqli_query($conexion, $sqlUsuario);

            $sql = "
            UPDATE personal
            SET nombres = " . personal_sql_value($conexion, $nombres) . ",
                apellidos = " . personal_sql_value($conexion, $apellidos) . ",
                telefono = " . personal_sql_value($conexion, $telefono) . ",
                fecha_contratacion = " . personal_sql_value($conexion, $fechaContratacion) . ",
                salario_diario = " . $salarioDiario . ",
                salario_hora = " . $salarioHora . ",
                participa_caja_ahorro = " . $participaCaja . ",
                aportacion_caja_ahorro_quincenal = " . $aportacionCaja . ",
                saldo_caja_ahorro_inicial = " . $saldoCajaInicial . ",
                tiene_infonavit = " . $tieneInfonavit . ",
                monto_infonavit_quincenal = " . $montoInfonavit . ",
                tiene_fonacot = " . $tieneFonacot . ",
                monto_fonacot_quincenal = " . $montoFonacot . ",
                limite_adelanto_nomina = " . $limiteAdelantoValue . ",
                tipo_contrato = " . personal_sql_value($conexion, $tipoContrato) . ",
                activo_en_nomina = " . $activoNomina . ",
                puesto_operativo = " . personal_sql_value($conexion, $puestoOperativo) . ",
                turno_base = " . personal_sql_value($conexion, $turnoBase) . ",
                servicio_asignado = " . personal_sql_value($conexion, $servicioAsignado) . ",
                infospe_estatus = " . personal_sql_value($conexion, $infospeEstatus) . ",
                cecceg_estatus = " . personal_sql_value($conexion, $ceccegEstatus) . ",
                sexo = " . personal_sql_value($conexion, $sexo) . ",
                estado_civil = " . personal_sql_value($conexion, $estadoCivil) . ",
                domicilio = " . personal_sql_value($conexion, $domicilio) . ",
                codigo_postal = " . personal_sql_value($conexion, $codigoPostal) . ",
                nss = " . personal_sql_value($conexion, $nss) . ",
                rfc = " . personal_sql_value($conexion, $rfc) . ",
                curp = " . personal_sql_value($conexion, $curp) . ",
                cuenta_bancaria = " . personal_sql_value($conexion, $cuentaBancaria) . ",
                banco = " . personal_sql_value($conexion, $banco) . ",
                fecha_nacimiento = " . personal_sql_value($conexion, $fechaNacimiento) . ",
                talla_camisa = " . personal_sql_value($conexion, $tallaCamisa) . ",
                talla_pantalon = " . personal_sql_value($conexion, $tallaPantalon) . ",
                talla_calzado = " . personal_sql_value($conexion, $tallaCalzado) . ",
                contacto_emergencia = " . personal_sql_value($conexion, $contactoEmergencia) . ",
                contacto_emergencia_parentesco = " . personal_sql_value($conexion, $contactoEmergenciaParentesco) . ",
                contacto_emergencia_telefono = " . personal_sql_value($conexion, $contactoEmergenciaTelefono) . ",
                tiene_hijos = " . $tieneHijos . ",
                edades_hijos = " . personal_sql_value($conexion, $edadesHijos) . ",
                vacaciones_2024_notas = " . personal_sql_value($conexion, $vacaciones2024Notas) . ",
                vacaciones_2025_notas = " . personal_sql_value($conexion, $vacaciones2025Notas) . ",
                vacaciones_2026_notas = " . personal_sql_value($conexion, $vacaciones2026Notas) . "
            WHERE id = " . $personalId . "
            LIMIT 1
        ";

            if (mysqli_query($conexion, $sql)) {
                $messages['success'] = 'Perfil actualizado.';
            } else {
                $messages['error'] = 'No fue posible actualizar el perfil.';
            }
        }
    }

    if ($accion === 'actualizar_foto_base') {
        $file = $_FILES['foto_base'] ?? null;

        if (!$file) {
            $messages['error'] = 'Selecciona una imagen para la foto base.';
        } else {
            $upload = app_store_base_photo($file, $personalId);

            if (!$upload['ok']) {
                $messages['error'] = $upload['message'];
            } else {
                $newPath = (string)$upload['relative_path'];
                $previousPath = trim((string)($personal['url_foto_base'] ?? ''));

                $sql = "UPDATE personal SET url_foto_base = ? WHERE id = ? LIMIT 1";
                if ($stmt = mysqli_prepare($conexion, $sql)) {
                    mysqli_stmt_bind_param($stmt, 'si', $newPath, $personalId);
                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_close($stmt);

                    if ($previousPath !== '' && strpos($previousPath, 'uploads/personal/base/') === 0) {
                        $previousAbsolute = __DIR__ . '/' . $previousPath;
                        if (is_file($previousAbsolute)) {
                            @unlink($previousAbsolute);
                        }
                    }

                    $messages['success'] = 'Foto base actualizada correctamente.';
                } else {
                    @unlink($upload['path']);
                    $messages['error'] = 'No fue posible guardar la ruta de la foto base.';
                }
            }
        }
    }

    if ($accion === 'registrar_adelanto') {
        $fecha = app_clean_text(app_post('fecha_adelanto', date('Y-m-d')));
        $monto = (float)app_post('monto_adelanto', 0);
        $motivo = app_clean_text(app_post('motivo_adelanto', 'Adelanto manual'));

        $earned = app_get_employee_earned_to_date($conexion, $personalId, $currentBounds['inicio'], min($fecha, date('Y-m-d')), $configs);
        $existing = app_get_total_adelantos_periodo($conexion, $currentPeriodId, $personalId);
        $available = max(0.0, $earned - $existing);
        if ((float)$personal['limite_adelanto_nomina'] > 0) {
            $available = min($available, (float)$personal['limite_adelanto_nomina']);
        }

        if ($monto <= 0) {
            $messages['error'] = 'Captura un monto válido para el adelanto.';
        } elseif ($monto > $available) {
            $messages['error'] = 'El adelanto excede el saldo disponible generado por el sueldo. Disponible: ' . app_money($available);
        } else {
            $sql = "
                INSERT INTO adelantos_nomina (personal_id, periodo_id, fecha_solicitud, fecha_aplicacion, monto, motivo, tipo, estado)
                VALUES (?, ?, ?, ?, ?, ?, 'INDIVIDUAL', 'APLICADO')
            ";
            if ($stmt = mysqli_prepare($conexion, $sql)) {
                mysqli_stmt_bind_param($stmt, 'iissds', $personalId, $currentPeriodId, $fecha, $fecha, $monto, $motivo);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
                $messages['success'] = 'Adelanto registrado.';
            }
        }
    }

    if ($accion === 'registrar_prestamo') {
        $monto = (float)app_post('monto_prestamo', 0);
        $plazo = max(1, (int)app_post('plazo_quincenas', 1));
        $fechaInicio = app_clean_text(app_post('fecha_inicio_prestamo', date('Y-m-d')));
        $observaciones = app_clean_text(app_post('observaciones_prestamo', ''));

        $poolDisponible = app_total_savings_pool($conexion);
        $capacidad = app_get_prestamo_capacidad($personal, $poolDisponible, $configs, $fechaInicio);
        $mesInicio = str_pad((string)app_config_int($configs, 'prestamos_mes_inicio', 3), 2, '0', STR_PAD_LEFT);
        $diaInicio = str_pad((string)app_config_int($configs, 'prestamos_dia_inicio', 1), 2, '0', STR_PAD_LEFT);
        $fechaMinima = date('Y-' . $mesInicio . '-' . $diaInicio, strtotime($fechaInicio));
        if ($fechaInicio < $fechaMinima) {
            $fechaInicio = $fechaMinima;
        }

        $mesLimite = str_pad((string)app_config_int($configs, 'prestamos_mes_limite', 12), 2, '0', STR_PAD_LEFT);
        $diaLimite = str_pad((string)app_config_int($configs, 'prestamos_dia_limite', 15), 2, '0', STR_PAD_LEFT);
        $fechaLimite = date('Y-' . $mesLimite . '-' . $diaLimite, strtotime($fechaInicio));
        $diasDisponibles = max(1, (int)floor((strtotime($fechaLimite) - strtotime($fechaInicio)) / 86400));
        $maxPlazo = max(1, (int)floor($diasDisponibles / 15));
        $plazo = min($plazo, $maxPlazo);
        $descuentoQuincenal = round($monto / max(1, $plazo), 2);

        if ($monto <= 0) {
            $messages['error'] = 'Captura un monto válido para el préstamo.';
        } elseif ($monto > $capacidad['max_allowed']) {
            $messages['error'] = 'El préstamo excede el máximo permitido. Disponible para prestar hoy: ' . app_money($capacidad['max_allowed']);
        } else {
            $tasa = app_config_float($configs, 'prestamos_tasa_quincenal', 4.0);
            $sql = "
                INSERT INTO prestamos_personal
                    (personal_id, fecha_inicio, monto_autorizado, saldo_inicial, saldo_insoluto, tasa_porcentual, plazo_quincenas, descuento_quincenal, fecha_limite, estado, observaciones)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'ACTIVO', ?)
            ";
            if ($stmt = mysqli_prepare($conexion, $sql)) {
                mysqli_stmt_bind_param(
                    $stmt,
                    'isddddidss',
                    $personalId,
                    $fechaInicio,
                    $monto,
                    $monto,
                    $monto,
                    $tasa,
                    $plazo,
                    $descuentoQuincenal,
                    $fechaLimite,
                    $observaciones
                );
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
                $messages['success'] = 'Préstamo registrado. El plazo quedó ajustado a ' . $plazo . ' quincenas si era necesario.';
            }
        }
    }

    if ($accion === 'registrar_movimiento_caja') {
        $fecha = app_clean_text(app_post('fecha_movimiento_caja', date('Y-m-d')));
        $tipo = app_clean_text(app_post('tipo_movimiento_caja', 'AJUSTE'));
        $monto = (float)app_post('monto_movimiento_caja', 0);
        $descripcion = app_clean_text(app_post('descripcion_movimiento_caja', ''));

        if (!in_array($tipo, ['APORTACION', 'RETIRO', 'AJUSTE'], true) || $monto <= 0) {
            $messages['error'] = 'Verifica el tipo y el monto del movimiento de caja.';
        } else {
            $signedMonto = $tipo === 'RETIRO' ? -1 * abs($monto) : abs($monto);
            $sql = "
                INSERT INTO caja_ahorro_movimientos (personal_id, periodo_id, fecha_aplicacion, tipo_movimiento, monto, descripcion)
                VALUES (?, NULL, ?, ?, ?, ?)
            ";
            if ($stmt = mysqli_prepare($conexion, $sql)) {
                mysqli_stmt_bind_param($stmt, 'issds', $personalId, $fecha, $tipo, $signedMonto, $descripcion);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
                $messages['success'] = 'Movimiento de caja registrado.';
            }
        }
    }

    if ($accion === 'eliminar_movimiento_caja') {
        $movimientoId = (int)app_post('movimiento_caja_id', 0);
        $movimiento = $movimientoId > 0
            ? app_db_one($conexion, "SELECT * FROM caja_ahorro_movimientos WHERE id = " . $movimientoId . " AND personal_id = " . $personalId . " LIMIT 1")
            : null;

        if (!$movimiento) {
            $messages['error'] = 'No se encontró el movimiento de caja.';
        } elseif ($movimiento['periodo_id'] !== null && $movimiento['periodo_id'] !== '') {
            $messages['error'] = 'Solo se pueden eliminar movimientos manuales de caja. Los generados por nómina se conservan para no descuadrar periodos.';
        } else {
            mysqli_query($conexion, "DELETE FROM caja_ahorro_movimientos WHERE id = " . $movimientoId . " AND personal_id = " . $personalId . " AND periodo_id IS NULL LIMIT 1");
            app_log_system($conexion, (int)($_SESSION['usuario_id'] ?? 0), 'CAJA_MOVIMIENTO_ELIMINA', 'caja_ahorro_movimientos', $movimientoId, null, $movimiento);
            $messages['success'] = 'Movimiento de caja eliminado.';
        }
    }

    if ($accion === 'registrar_vacacion') {
        $fechaInicio = app_clean_text(app_post('fecha_inicio_vacacion', date('Y-m-d')));
        $fechaFin = app_clean_text(app_post('fecha_fin_vacacion', $fechaInicio));
        $dias = (float)app_post('dias_vacacion', 0);
        $tipo = app_clean_text(app_post('tipo_vacacion', 'GOZADAS'));
        $notas = app_clean_text(app_post('notas_vacacion', ''));
        $tipoDb = $tipo;
        $montoPago = 0.0;
        $montoPrima = 0.0;
        $primaPct = 0.0;

        if ($fechaInicio === '') {
            $messages['error'] = 'Captura la fecha del movimiento de vacaciones.';
        } elseif (in_array($tipo, ['GOZADAS', 'PAGADAS'], true) && ($dias <= 0 || strtotime($fechaFin) < strtotime($fechaInicio))) {
            $messages['error'] = 'Captura un rango y número de días válidos para vacaciones.';
        } elseif (in_array($tipo, ['AJUSTE_FAVOR', 'AJUSTE_CONTRA'], true) && $dias <= 0) {
            $messages['error'] = 'Captura los días del ajuste.';
        } elseif ($tipo === 'NOTA' && $notas === '') {
            $messages['error'] = 'Captura una nota histórica para vacaciones.';
        } elseif (!in_array($tipo, ['GOZADAS', 'PAGADAS', 'AJUSTE_FAVOR', 'AJUSTE_CONTRA', 'NOTA'], true)) {
            $messages['error'] = 'Tipo de movimiento de vacaciones no válido.';
        } else {
            if ($tipo === 'AJUSTE_FAVOR') {
                $tipoDb = 'AJUSTE';
                $dias = abs($dias);
                $fechaFin = $fechaInicio;
            } elseif ($tipo === 'AJUSTE_CONTRA') {
                $tipoDb = 'AJUSTE';
                $dias = -1 * abs($dias);
                $fechaFin = $fechaInicio;
            } elseif ($tipo === 'NOTA') {
                $tipoDb = 'NOTA';
                $dias = 0.0;
                $fechaFin = $fechaInicio;
            } else {
                $primaPct = app_config_float($configs, 'vacaciones_prima_porcentaje', 25.0);
                $montoPago = $dias * app_salary_diario($personal, $configs);
                $montoPrima = $montoPago * ($primaPct / 100);
            }

            $sql = "
                INSERT INTO vacaciones_movimientos
                    (personal_id, fecha_solicitud, fecha_inicio, fecha_fin, dias, tipo, prima_porcentual, monto_prima, monto_pago, estado, notas, origen)
                VALUES (?, CURDATE(), ?, ?, ?, ?, ?, ?, ?, 'APLICADO', ?, 'CAPTURA_RH')
            ";
            if ($stmt = mysqli_prepare($conexion, $sql)) {
                mysqli_stmt_bind_param(
                    $stmt,
                    'issdsddds',
                    $personalId,
                    $fechaInicio,
                    $fechaFin,
                    $dias,
                    $tipoDb,
                    $primaPct,
                    $montoPrima,
                    $montoPago,
                    $notas
                );
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
                app_recalculate_vacation_balance($conexion, $personalId, $configs);
                $messages['success'] = 'Movimiento de vacaciones aplicado.';
            }
        }
    }

    if ($accion === 'cancelar_vacacion') {
        $movimientoId = (int)app_post('vacacion_id', 0);
        if ($movimientoId <= 0) {
            $messages['error'] = 'Movimiento de vacaciones no válido.';
        } else {
            mysqli_query(
                $conexion,
                "UPDATE vacaciones_movimientos
                 SET estado = 'CANCELADO'
                 WHERE id = " . $movimientoId . "
                   AND personal_id = " . $personalId . "
                 LIMIT 1"
            );
            app_recalculate_vacation_balance($conexion, $personalId, $configs);
            $messages['success'] = 'Movimiento de vacaciones cancelado.';
        }
    }

    if ($accion === 'registrar_falta') {
        $fecha = app_clean_text(app_post('fecha_falta', date('Y-m-d')));
        $categoria = app_clean_text(app_post('categoria_falta', 'FALTA'));
        $tipo = app_clean_text(app_post('tipo_falta', 'JUSTIFICADA'));
        $goceSueldo = app_post('goce_sueldo') ? 1 : 0;
        $motivo = app_clean_text(app_post('motivo_falta', ''));

        $map = [
            'JUSTIFICADA' => app_config_float($configs, 'faltas_descuento_justificada', 400.0),
            'INJUSTIFICADA' => app_config_float($configs, 'faltas_descuento_injustificada', 700.0),
            'AJUSTADA' => app_config_float($configs, 'faltas_descuento_ajustada', 0.0),
        ];

        if (!in_array($categoria, ['FALTA', 'DESCANSO'], true)) {
            $messages['error'] = 'Categoría no válida.';
        } elseif ($categoria === 'FALTA' && !isset($map[$tipo])) {
            $messages['error'] = 'Tipo de falta no válido.';
        } else {
            if ($categoria === 'DESCANSO') {
                $tipo = 'AJUSTADA';
                $monto = $goceSueldo === 1
                    ? 0.0
                    : app_config_float(
                        $configs,
                        'descansos_descuento_sin_goce',
                        app_config_float($configs, 'faltas_descuento_injustificada', 700.0)
                    );
            } else {
                $goceSueldo = 0;
                $monto = $map[$tipo];
            }

            $sql = "
                INSERT INTO faltas_personal (personal_id, periodo_id, categoria, goce_sueldo, fecha_falta, tipo, monto_descuento, motivo)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ";
            if ($stmt = mysqli_prepare($conexion, $sql)) {
                $periodoIdFalta = $currentPeriodId;
                mysqli_stmt_bind_param($stmt, 'iisissds', $personalId, $periodoIdFalta, $categoria, $goceSueldo, $fecha, $tipo, $monto, $motivo);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
                $messages['success'] = $categoria === 'DESCANSO' ? 'Descanso registrado.' : 'Falta registrada.';
            }
        }
    }

    if ($accion === 'registrar_sancion') {
        $fecha = app_clean_text(app_post('fecha_sancion', date('Y-m-d')));
        $motivo = app_clean_text(app_post('motivo_sancion', ''));
        $monto = (float)app_post('monto_sancion', 0);
        $quincenas = max(1, (int)app_post('quincenas_sancion', 1));
        $montoQuincena = round($monto / $quincenas, 2);

        if ($monto <= 0 || $motivo === '') {
            $messages['error'] = 'Captura motivo y monto para la sanción.';
        } else {
            $sql = "
                INSERT INTO sanciones_personal
                    (personal_id, fecha_aplicacion, motivo, monto_total, quincenas_total, quincenas_restantes, monto_por_quincena, estado)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'ACTIVA')
            ";
            if ($stmt = mysqli_prepare($conexion, $sql)) {
                mysqli_stmt_bind_param($stmt, 'issdiid', $personalId, $fecha, $motivo, $monto, $quincenas, $quincenas, $montoQuincena);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
                $messages['success'] = 'Sanción registrada.';
            }
        }
    }

    if ($accion === 'registrar_material') {
        $fecha = app_clean_text(app_post('fecha_material', date('Y-m-d')));
        $tipoMaterial = app_clean_text(app_post('tipo_material', 'Botas'));
        $monto = (float)app_post('monto_material', 0);
        $quincenas = max(1, (int)app_post('quincenas_material', app_config_int($configs, 'material_quincenas_default', 2)));
        $montoQuincena = round($monto / $quincenas, 2);

        if ($monto <= 0) {
            $messages['error'] = 'Captura un monto válido para descuento de material.';
        } else {
            $sql = "
                INSERT INTO descuentos_material
                    (personal_id, fecha_aplicacion, tipo_material, monto_total, quincenas_total, quincenas_restantes, monto_por_quincena, estado)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'ACTIVO')
            ";
            if ($stmt = mysqli_prepare($conexion, $sql)) {
                mysqli_stmt_bind_param($stmt, 'issdiid', $personalId, $fecha, $tipoMaterial, $monto, $quincenas, $quincenas, $montoQuincena);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
                $messages['success'] = 'Descuento de material registrado.';
            }
        }
    }

    if ($accion === 'registrar_bono') {
        $fecha = app_clean_text(app_post('fecha_bono', date('Y-m-d')));
        $categoria = app_clean_text(app_post('categoria_bono', 'BONO'));
        $motivo = app_clean_text(app_post('motivo_bono', ''));
        $monto = (float)app_post('monto_bono', 0);

        if ($monto <= 0 || $motivo === '') {
            $messages['error'] = 'Captura motivo y monto para el bono.';
        } else {
            $sql = "
                INSERT INTO bonos_personal (personal_id, periodo_id, fecha_aplicacion, categoria, monto, motivo)
                VALUES (?, ?, ?, ?, ?, ?)
            ";
            if ($stmt = mysqli_prepare($conexion, $sql)) {
                mysqli_stmt_bind_param($stmt, 'iissds', $personalId, $currentPeriodId, $fecha, $categoria, $monto, $motivo);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
                $messages['success'] = 'Bono registrado.';
            }
        }
    }

    if ($accion === 'registrar_incapacidad') {
        $fechaInicio = app_clean_text(app_post('fecha_inicio_incapacidad', date('Y-m-d')));
        $fechaFin = app_clean_text(app_post('fecha_fin_incapacidad', $fechaInicio));
        $motivo = app_clean_text(app_post('motivo_incapacidad', ''));
        $days = max(1, (int)app_post('dias_incapacidad', 1));
        $montoDia = (float)app_post('monto_por_dia_incapacidad', app_salary_diario($personal, $configs));

        $sql = "
            INSERT INTO incapacidades_personal (personal_id, fecha_inicio, fecha_fin, dias, monto_por_dia, motivo, pagada)
            VALUES (?, ?, ?, ?, ?, ?, 1)
        ";
        if ($stmt = mysqli_prepare($conexion, $sql)) {
            mysqli_stmt_bind_param($stmt, 'issdds', $personalId, $fechaInicio, $fechaFin, $days, $montoDia, $motivo);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            $messages['success'] = 'Incapacidad registrada.';
        }
    }

    if ($accion === 'registrar_baja') {
        $fecha = app_clean_text(app_post('fecha_baja', date('Y-m-d')));
        $motivo = app_clean_text(app_post('motivo_baja', ''));
        $finiquitoCapturado = (float)app_post('finiquito_baja', 0);
        $estatusPago = app_clean_text(app_post('estatus_pago_baja', 'PENDIENTE'));
        $vacacionesRestantes = app_recalculate_vacation_balance($conexion, $personalId, $configs, $fecha);
        $salarioDia = app_salary_diario($personal, $configs);
        $primaPct = app_config_float($configs, 'vacaciones_prima_porcentaje', 25.0);
        $vacacionesPendientesMonto = $vacacionesRestantes * $salarioDia;
        $vacacionesPendientesPrima = $vacacionesPendientesMonto * ($primaPct / 100);
        $finiquitoTotal = $finiquitoCapturado + $vacacionesPendientesMonto + $vacacionesPendientesPrima;

        mysqli_query(
            $conexion,
            "UPDATE personal
             SET estado = 'INACTIVO',
                 activo_en_nomina = 0,
                 fecha_baja = '" . mysqli_real_escape_string($conexion, $fecha) . "',
                 motivo_baja = '" . mysqli_real_escape_string($conexion, $motivo) . "',
                 finiquito_monto = " . $finiquitoTotal . ",
                 estatus_finiquito = '" . mysqli_real_escape_string($conexion, $estatusPago) . "'
             WHERE id = " . $personalId
        );
        mysqli_query($conexion, "UPDATE usuarios SET esta_activo = 0 WHERE id = " . (int)$personal['usuario_id']);

        $sql = "
            INSERT INTO bajas_personal (personal_id, usuario_id, fecha_baja, motivo, finiquito_monto, estatus_pago)
            VALUES (?, ?, ?, ?, ?, ?)
        ";
        if ($stmt = mysqli_prepare($conexion, $sql)) {
            $usuarioId = (int)($_SESSION['usuario_id'] ?? 0);
            mysqli_stmt_bind_param($stmt, 'iissds', $personalId, $usuarioId, $fecha, $motivo, $finiquitoTotal, $estatusPago);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }

        $messages['success'] = 'Baja registrada con finiquito y vacaciones pendientes. Se desactivaron sus accesos.';
    }

    $personal = app_get_personal($conexion, $personalId);
}

$vacacionesDisponibles = app_recalculate_vacation_balance($conexion, $personalId, $configs);
$vacationSummary = app_vacation_summary($conexion, $personalId, $configs);
$vacacionesDisponibles = (float)$vacationSummary['balance'];
$saldoCaja = app_get_savings_balance($conexion, $personalId);
$poolDisponible = app_total_savings_pool($conexion);
$capacidadPrestamo = app_get_prestamo_capacidad($personal, $poolDisponible, $configs);
$prestamos = app_get_prestamos_activos($conexion, $personalId);
$vacacionesRows = app_db_all($conexion, "SELECT * FROM vacaciones_movimientos WHERE personal_id = " . $personalId . " ORDER BY fecha_inicio DESC, id DESC LIMIT 50");
$faltasRows = app_db_all($conexion, "SELECT * FROM faltas_personal WHERE personal_id = " . $personalId . " ORDER BY fecha_falta DESC, id DESC LIMIT 20");
$bonosRows = app_db_all($conexion, "SELECT * FROM bonos_personal WHERE personal_id = " . $personalId . " ORDER BY fecha_aplicacion DESC, id DESC LIMIT 20");
$materialRows = app_db_all($conexion, "SELECT * FROM descuentos_material WHERE personal_id = " . $personalId . " ORDER BY fecha_aplicacion DESC, id DESC LIMIT 20");
$sancionRows = app_db_all($conexion, "SELECT * FROM sanciones_personal WHERE personal_id = " . $personalId . " ORDER BY fecha_aplicacion DESC, id DESC LIMIT 20");
$adelantoRows = app_db_all($conexion, "SELECT * FROM adelantos_nomina WHERE personal_id = " . $personalId . " ORDER BY fecha_aplicacion DESC, id DESC LIMIT 20");
$cajaRows = app_db_all($conexion, "SELECT * FROM caja_ahorro_movimientos WHERE personal_id = " . $personalId . " ORDER BY fecha_aplicacion DESC, id DESC LIMIT 20");
$incapacidadRows = app_db_all($conexion, "SELECT * FROM incapacidades_personal WHERE personal_id = " . $personalId . " ORDER BY fecha_inicio DESC, id DESC LIMIT 20");
$bajaRows = app_db_all($conexion, "SELECT * FROM bajas_personal WHERE personal_id = " . $personalId . " ORDER BY fecha_baja DESC, id DESC LIMIT 10");
$earnedCurrent = app_get_employee_earned_to_date($conexion, $personalId, $currentBounds['inicio'], date('Y-m-d'), $configs);
$adelantosCurrent = app_get_total_adelantos_periodo($conexion, $currentPeriodId, $personalId);
$adelantoDisponible = max(0.0, $earnedCurrent - $adelantosCurrent);
if ((float)$personal['limite_adelanto_nomina'] > 0) {
    $adelantoDisponible = min($adelantoDisponible, (float)$personal['limite_adelanto_nomina']);
}

app_render_page_start(
    'Ficha del colaborador',
    'Ficha del colaborador',
    'Gestión integral de perfil, adelantos, préstamos, caja, vacaciones, faltas, sanciones, bonos e incapacidades.'
);
app_render_alerts($messages);
?>

<div class="row layout-top-spacing mb-3">
    <div class="col-md-3">
	        <div class="card summary-card p-3">
	            <div class="summary-label">Colaborador</div>
	            <div class="summary-value" style="font-size:1.2rem;"><?php echo app_h($personal['nombres'] . ' ' . $personal['apellidos']); ?></div>
	            <div class="text-muted">
                    No. <?php echo app_h(app_employee_number($personal)); ?> · <?php echo app_h($personal['usuario']); ?>
                </div>
                <a class="btn btn-outline-primary btn-sm mt-2" target="_blank" href="personas-ficha-tecnica.php?personal_id=<?php echo (int)$personalId; ?>">
                    Ficha técnica PDF
                </a>
	        </div>
    </div>
    <div class="col-md-3">
        <div class="card summary-card p-3">
            <div class="summary-label">Caja ahorro</div>
            <div class="summary-value"><?php echo app_money($saldoCaja); ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card summary-card p-3">
            <div class="summary-label">Vacaciones disponibles</div>
            <div class="summary-value"><?php echo app_number($vacacionesDisponibles); ?></div>
            <div class="<?php echo $vacacionesDisponibles < 0 ? 'text-danger' : 'text-muted'; ?>">
                <?php echo $vacacionesDisponibles < 0 ? 'saldo sobregirado' : 'días'; ?>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card summary-card p-3">
            <div class="summary-label">Adelanto disponible</div>
            <div class="summary-value"><?php echo app_money($adelantoDisponible); ?></div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-xl-6">
        <div class="card content-card mb-4">
            <div class="card-body">
                <h5 class="mb-3">Foto base</h5>
                <div class="row align-items-center">
                    <div class="col-md-4 mb-3 mb-md-0">
                        <?php if (!empty($personal['url_foto_base'])): ?>
                            <img
                                src="<?php echo app_h($personal['url_foto_base']); ?>"
                                alt="Foto base del colaborador"
                                style="width: 100%; max-width: 180px; aspect-ratio: 1 / 1; object-fit: cover; border-radius: 16px; border: 1px solid rgba(0,0,0,0.08);">
                        <?php else: ?>
                            <div class="d-flex align-items-center justify-content-center text-muted"
                                 style="width: 100%; max-width: 180px; aspect-ratio: 1 / 1; border-radius: 16px; border: 1px dashed rgba(0,0,0,0.2);">
                                Sin foto base
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-8">
                        <form method="post" enctype="multipart/form-data">
                            <input type="hidden" name="accion" value="actualizar_foto_base">
                            <div class="mb-3">
                                <label class="form-label">Cargar o reemplazar foto base</label>
                                <input type="file" name="foto_base" class="form-control" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" required>
                                <small class="text-muted">Esta imagen se usará como referencia para comparar la selfie registrada desde la app.</small>
                            </div>
                            <button type="submit" class="btn btn-outline-primary">Guardar foto base</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="card content-card mb-4">
            <div class="card-body">
	                <h5 class="mb-3">Perfil de nómina</h5>
	                <form method="post">
	                    <input type="hidden" name="accion" value="actualizar_perfil">
	                    <h6 class="text-uppercase text-muted mb-3">Datos generales</h6>
	                    <div class="row">
	                        <div class="col-md-6 mb-3">
	                            <label class="form-label">Nombres</label>
	                            <input type="text" name="nombres" class="form-control" value="<?php echo app_h($personal['nombres']); ?>" required>
	                        </div>
	                        <div class="col-md-6 mb-3">
	                            <label class="form-label">Apellidos</label>
	                            <input type="text" name="apellidos" class="form-control" value="<?php echo app_h($personal['apellidos']); ?>" required>
	                        </div>
	                        <div class="col-md-4 mb-3">
	                            <label class="form-label">No. empleado</label>
	                            <input type="text" class="form-control" value="<?php echo app_h(app_employee_number($personal)); ?>" readonly>
	                        </div>
	                        <div class="col-md-4 mb-3">
	                            <label class="form-label">Fecha ingreso</label>
	                            <input type="date" name="fecha_contratacion" class="form-control" value="<?php echo app_h($personal['fecha_contratacion'] ?? ''); ?>">
	                        </div>
	                        <div class="col-md-6 mb-3">
	                            <label class="form-label">Teléfono</label>
	                            <input type="text" name="telefono" class="form-control" value="<?php echo app_h($personal['telefono'] ?? ''); ?>">
	                        </div>
	                        <div class="col-md-6 mb-3">
	                            <label class="form-label">Correo</label>
	                            <input type="email" name="email" class="form-control" value="<?php echo app_h($personal['email'] ?? ''); ?>">
	                        </div>
	                    </div>

	                    <h6 class="text-uppercase text-muted mb-3">Datos laborales</h6>
	                    <div class="row">
	                        <div class="col-md-6 mb-3">
	                            <label class="form-label">Puesto operativo</label>
	                            <input type="text" name="puesto_operativo" class="form-control" value="<?php echo app_h($personal['puesto_operativo'] ?? ''); ?>">
	                        </div>
	                        <div class="col-md-3 mb-3">
	                            <label class="form-label">Turno base</label>
	                            <input type="text" name="turno_base" class="form-control" value="<?php echo app_h($personal['turno_base'] ?? ''); ?>">
	                        </div>
	                        <div class="col-md-3 mb-3">
	                            <label class="form-label">Servicio</label>
	                            <input type="text" name="servicio_asignado" class="form-control" value="<?php echo app_h($personal['servicio_asignado'] ?? ''); ?>">
	                        </div>
	                        <div class="col-md-6 mb-3">
	                            <label class="form-label">INFOSPE</label>
	                            <input type="text" name="infospe_estatus" class="form-control" value="<?php echo app_h($personal['infospe_estatus'] ?? ''); ?>">
	                        </div>
	                        <div class="col-md-6 mb-3">
	                            <label class="form-label">CECCEG</label>
	                            <input type="text" name="cecceg_estatus" class="form-control" value="<?php echo app_h($personal['cecceg_estatus'] ?? ''); ?>">
	                        </div>
	                    </div>

	                    <h6 class="text-uppercase text-muted mb-3">Datos personales</h6>
	                    <div class="row">
	                        <div class="col-md-4 mb-3">
	                            <label class="form-label">Sexo</label>
	                            <input type="text" name="sexo" class="form-control" value="<?php echo app_h($personal['sexo'] ?? ''); ?>">
	                        </div>
	                        <div class="col-md-4 mb-3">
	                            <label class="form-label">Estado civil</label>
	                            <input type="text" name="estado_civil" class="form-control" value="<?php echo app_h($personal['estado_civil'] ?? ''); ?>">
	                        </div>
	                        <div class="col-md-4 mb-3">
	                            <label class="form-label">Fecha nacimiento</label>
	                            <input type="date" name="fecha_nacimiento" class="form-control" value="<?php echo app_h($personal['fecha_nacimiento'] ?? ''); ?>">
	                        </div>
	                        <div class="col-md-8 mb-3">
	                            <label class="form-label">Domicilio</label>
	                            <textarea name="domicilio" class="form-control" rows="2"><?php echo app_h($personal['domicilio'] ?? ''); ?></textarea>
	                        </div>
	                        <div class="col-md-4 mb-3">
	                            <label class="form-label">Código postal</label>
	                            <input type="text" name="codigo_postal" class="form-control" value="<?php echo app_h($personal['codigo_postal'] ?? ''); ?>">
	                        </div>
	                        <div class="col-md-4 mb-3">
	                            <label class="form-label">NSS</label>
	                            <input type="text" name="nss" class="form-control" value="<?php echo app_h($personal['nss'] ?? ''); ?>">
	                        </div>
	                        <div class="col-md-4 mb-3">
	                            <label class="form-label">RFC</label>
	                            <input type="text" name="rfc" class="form-control" value="<?php echo app_h($personal['rfc'] ?? ''); ?>">
	                        </div>
	                        <div class="col-md-4 mb-3">
	                            <label class="form-label">CURP</label>
	                            <input type="text" name="curp" class="form-control" value="<?php echo app_h($personal['curp'] ?? ''); ?>">
	                        </div>
	                    </div>

	                    <h6 class="text-uppercase text-muted mb-3">Banco, uniforme y emergencia</h6>
	                    <div class="row">
	                        <div class="col-md-6 mb-3">
	                            <label class="form-label">Cuenta bancaria</label>
	                            <input type="text" name="cuenta_bancaria" class="form-control" value="<?php echo app_h($personal['cuenta_bancaria'] ?? ''); ?>">
	                        </div>
	                        <div class="col-md-6 mb-3">
	                            <label class="form-label">Banco</label>
	                            <input type="text" name="banco" class="form-control" value="<?php echo app_h($personal['banco'] ?? ''); ?>">
	                        </div>
	                        <div class="col-md-4 mb-3">
	                            <label class="form-label">Talla camisa</label>
	                            <input type="text" name="talla_camisa" class="form-control" value="<?php echo app_h($personal['talla_camisa'] ?? ''); ?>">
	                        </div>
	                        <div class="col-md-4 mb-3">
	                            <label class="form-label">Talla pantalón</label>
	                            <input type="text" name="talla_pantalon" class="form-control" value="<?php echo app_h($personal['talla_pantalon'] ?? ''); ?>">
	                        </div>
	                        <div class="col-md-4 mb-3">
	                            <label class="form-label">Talla calzado</label>
	                            <input type="text" name="talla_calzado" class="form-control" value="<?php echo app_h($personal['talla_calzado'] ?? ''); ?>">
	                        </div>
	                        <div class="col-md-5 mb-3">
	                            <label class="form-label">Contacto emergencia</label>
	                            <input type="text" name="contacto_emergencia" class="form-control" value="<?php echo app_h($personal['contacto_emergencia'] ?? ''); ?>">
	                        </div>
	                        <div class="col-md-3 mb-3">
	                            <label class="form-label">Parentesco</label>
	                            <input type="text" name="contacto_emergencia_parentesco" class="form-control" value="<?php echo app_h($personal['contacto_emergencia_parentesco'] ?? ''); ?>">
	                        </div>
	                        <div class="col-md-4 mb-3">
	                            <label class="form-label">Teléfono emergencia</label>
	                            <input type="text" name="contacto_emergencia_telefono" class="form-control" value="<?php echo app_h($personal['contacto_emergencia_telefono'] ?? ''); ?>">
	                        </div>
	                        <div class="col-md-3 mb-3">
	                            <label class="form-label">Tiene hijos</label><br>
	                            <input type="checkbox" name="tiene_hijos" <?php echo (int)($personal['tiene_hijos'] ?? 0) === 1 ? 'checked' : ''; ?>>
	                        </div>
	                        <div class="col-md-9 mb-3">
	                            <label class="form-label">Edades de hijos</label>
	                            <input type="text" name="edades_hijos" class="form-control" value="<?php echo app_h($personal['edades_hijos'] ?? ''); ?>">
	                        </div>
	                    </div>



	                    <hr>
	                    <h6 class="text-uppercase text-muted mb-3">Nómina</h6>
	                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Salario diario</label>
                            <input type="number" step="0.01" min="0" name="salario_diario" class="form-control" value="<?php echo app_h($personal['salario_diario']); ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Salario por hora</label>
                            <input type="number" step="0.01" min="0" name="salario_hora" class="form-control" value="<?php echo app_h($personal['salario_hora']); ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Tipo de contrato</label>
                            <input type="text" name="tipo_contrato" class="form-control" value="<?php echo app_h($personal['tipo_contrato']); ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Límite manual adelanto</label>
                            <input type="number" step="0.01" min="0" name="limite_adelanto_nomina" class="form-control" value="<?php echo app_h($personal['limite_adelanto_nomina']); ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Aportación quincenal caja</label>
                            <input type="number" step="0.01" min="0" name="aportacion_caja_ahorro_quincenal" class="form-control" value="<?php echo app_h($personal['aportacion_caja_ahorro_quincenal']); ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Saldo caja inicial</label>
                            <input type="number" step="0.01" min="0" name="saldo_caja_ahorro_inicial" class="form-control" value="<?php echo app_h($personal['saldo_caja_ahorro_inicial']); ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Activo en nómina</label><br>
                            <input type="checkbox" name="activo_en_nomina" <?php echo (int)$personal['activo_en_nomina'] === 1 ? 'checked' : ''; ?>>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Participa en caja</label><br>
                            <input type="checkbox" name="participa_caja_ahorro" <?php echo (int)$personal['participa_caja_ahorro'] === 1 ? 'checked' : ''; ?>>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Tiene Infonavit</label><br>
                            <input type="checkbox" name="tiene_infonavit" <?php echo (int)$personal['tiene_infonavit'] === 1 ? 'checked' : ''; ?>>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Tiene Fonacot</label><br>
                            <input type="checkbox" name="tiene_fonacot" <?php echo (int)$personal['tiene_fonacot'] === 1 ? 'checked' : ''; ?>>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Monto quincenal Infonavit</label>
                            <input type="number" step="0.01" min="0" name="monto_infonavit_quincenal" class="form-control" value="<?php echo app_h($personal['monto_infonavit_quincenal']); ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Monto quincenal Fonacot</label>
                            <input type="number" step="0.01" min="0" name="monto_fonacot_quincenal" class="form-control" value="<?php echo app_h($personal['monto_fonacot_quincenal']); ?>">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">Guardar perfil</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-xl-6">
        <div class="card content-card mb-4" id="adelantos">
            <div class="card-body">
                <h5 class="mb-3">Adelanto de nómina</h5>
                <div class="row mb-3">
                    <div class="col-md-4">
                        <div class="summary-label">Quincena actual</div>
                        <strong><?php echo app_quincena_label($currentBounds['inicio'], $currentBounds['fin']); ?></strong>
                    </div>
                    <div class="col-md-4">
                        <div class="summary-label">Generado hasta hoy</div>
                        <strong><?php echo app_money($earnedCurrent); ?></strong>
                    </div>
                    <div class="col-md-4">
                        <div class="summary-label">Disponible</div>
                        <strong><?php echo app_money($adelantoDisponible); ?></strong>
                    </div>
                </div>
                <form method="post">
                    <input type="hidden" name="accion" value="registrar_adelanto">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Fecha</label>
                            <input type="date" name="fecha_adelanto" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Monto</label>
                            <input type="number" step="0.01" min="0" name="monto_adelanto" class="form-control">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Motivo</label>
                            <input type="text" name="motivo_adelanto" class="form-control" value="Adelanto manual">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-outline-primary">Registrar adelanto</button>
                </form>

                <div class="table-responsive mt-4">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Tipo</th>
                                <th>Monto</th>
                                <th>Motivo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($adelantoRows as $row): ?>
                                <tr>
                                    <td><?php echo app_date($row['fecha_aplicacion']); ?></td>
                                    <td><?php echo app_h($row['tipo']); ?></td>
                                    <td><?php echo app_money($row['monto']); ?></td>
                                    <td><?php echo app_h($row['motivo']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (!$adelantoRows): ?>
                                <tr><td colspan="4" class="text-center text-muted">Sin adelantos registrados.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-xl-6">
        <div class="card content-card mb-4">
            <div class="card-body">
                <h5 class="mb-3">Préstamos</h5>
                <div class="row mb-3">
                    <div class="col-md-4">
                        <div class="summary-label">Antigüedad</div>
                        <strong><?php echo app_h(app_tenure_label($personal['fecha_contratacion'] ?? null)); ?></strong>
                    </div>
                    <div class="col-md-4">
                        <div class="summary-label">Caja disponible global</div>
                        <strong><?php echo app_money($poolDisponible); ?></strong>
                    </div>
                    <div class="col-md-4">
                        <div class="summary-label">Máximo autorizado</div>
                        <strong><?php echo app_money($capacidadPrestamo['max_allowed']); ?></strong>
                    </div>
                </div>

                <form method="post">
                    <input type="hidden" name="accion" value="registrar_prestamo">
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Monto</label>
                            <input type="number" step="0.01" min="0" name="monto_prestamo" class="form-control">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Plazo (quincenas)</label>
                            <input type="number" min="1" name="plazo_quincenas" class="form-control" value="4">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Fecha inicio</label>
                            <input type="date" name="fecha_inicio_prestamo" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Observaciones</label>
                            <input type="text" name="observaciones_prestamo" class="form-control">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-outline-primary">Registrar préstamo</button>
                </form>

                <div class="table-responsive mt-4">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Inicio</th>
                                <th>Monto</th>
                                <th>Saldo</th>
                                <th>Plazo</th>
                                <th>Límite</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($prestamos as $prestamo): ?>
                                <tr>
                                    <td><?php echo app_date($prestamo['fecha_inicio']); ?></td>
                                    <td><?php echo app_money($prestamo['monto_autorizado']); ?></td>
                                    <td><?php echo app_money($prestamo['saldo_insoluto']); ?></td>
                                    <td><?php echo (int)$prestamo['plazo_quincenas']; ?></td>
                                    <td><?php echo app_date($prestamo['fecha_limite']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (!$prestamos): ?>
                                <tr><td colspan="5" class="text-center text-muted">No hay préstamos activos.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-6">
        <div class="card content-card mb-4">
            <div class="card-body">
                <h5 class="mb-3">Caja de ahorro</h5>
                <form method="post">
                    <input type="hidden" name="accion" value="registrar_movimiento_caja">
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Fecha</label>
                            <input type="date" name="fecha_movimiento_caja" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Tipo</label>
                            <select name="tipo_movimiento_caja" class="form-select">
                                <option value="APORTACION">Aportación</option>
                                <option value="RETIRO">Retiro</option>
                                <option value="AJUSTE">Ajuste</option>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Monto</label>
                            <input type="number" step="0.01" min="0" name="monto_movimiento_caja" class="form-control">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Descripción</label>
                            <input type="text" name="descripcion_movimiento_caja" class="form-control">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-outline-primary">Registrar movimiento</button>
                </form>

                <div class="table-responsive mt-4">
                    <table class="table table-sm">
                        <thead>
	                            <tr>
	                                <th>Fecha</th>
	                                <th>Tipo</th>
	                                <th>Monto</th>
	                                <th>Descripción</th>
	                                <th class="text-end">Acciones</th>
	                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cajaRows as $row): ?>
                                <tr>
	                                    <td><?php echo app_date($row['fecha_aplicacion']); ?></td>
	                                    <td><?php echo app_h($row['tipo_movimiento']); ?></td>
	                                    <td><?php echo app_money($row['monto']); ?></td>
	                                    <td><?php echo app_h($row['descripcion']); ?></td>
	                                    <td class="text-end">
                                            <?php if ($row['periodo_id'] === null || $row['periodo_id'] === ''): ?>
                                                <form method="post" class="d-inline" onsubmit="return confirm('¿Eliminar este movimiento manual de caja?');">
                                                    <input type="hidden" name="accion" value="eliminar_movimiento_caja">
                                                    <input type="hidden" name="movimiento_caja_id" value="<?php echo (int)$row['id']; ?>">
                                                    <button type="submit" class="btn btn-outline-danger btn-sm">Eliminar</button>
                                                </form>
                                            <?php else: ?>
                                                <span class="badge bg-light text-dark">Nómina</span>
                                            <?php endif; ?>
                                        </td>
	                                </tr>
                            <?php endforeach; ?>
                            <?php if (!$cajaRows): ?>
	                                <tr><td colspan="5" class="text-center text-muted">Sin movimientos de caja.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-xl-6">
            <div class="card content-card mb-4">
            <div class="card-body">
                <h5 class="mb-3">Vacaciones</h5>
                <div class="row g-2 mb-4">
                    <div class="col-md-4">
                        <div class="border rounded p-2 h-100">
                            <div class="summary-label">Generadas</div>
                            <strong><?php echo app_number($vacationSummary['generated']); ?> días</strong>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="border rounded p-2 h-100">
                            <div class="summary-label">Gozadas</div>
                            <strong><?php echo app_number($vacationSummary['gozadas']); ?> días</strong>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="border rounded p-2 h-100">
                            <div class="summary-label">Pagadas</div>
                            <strong><?php echo app_number($vacationSummary['pagadas']); ?> días</strong>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="border rounded p-2 h-100">
                            <div class="summary-label">Ajustes</div>
                            <strong><?php echo app_number($vacationSummary['ajustes']); ?> días</strong>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="border rounded p-2 h-100">
                            <div class="summary-label">Notas históricas</div>
                            <strong><?php echo (int)$vacationSummary['notas']; ?></strong>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="border rounded p-2 h-100 <?php echo $vacationSummary['balance'] < 0 ? 'border-danger text-danger' : ''; ?>">
                            <div class="summary-label">Saldo actual</div>
                            <strong><?php echo app_number($vacationSummary['balance']); ?> días</strong>
                        </div>
                    </div>
                </div>
                <form method="post">
                    <input type="hidden" name="accion" value="registrar_vacacion">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Movimiento</label>
                            <select name="tipo_vacacion" id="tipo_vacacion" class="form-select">
                                <option value="GOZADAS">Vacaciones gozadas</option>
                                <option value="PAGADAS">Vacaciones pagadas</option>
                                <option value="AJUSTE_FAVOR">Ajuste a favor</option>
                                <option value="AJUSTE_CONTRA">Ajuste en contra</option>
                                <option value="NOTA">Nota histórica</option>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Inicio</label>
                            <input type="date" name="fecha_inicio_vacacion" id="fecha_inicio_vacacion" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Fin</label>
                            <input type="date" name="fecha_fin_vacacion" id="fecha_fin_vacacion" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="col-md-2 mb-3">
                            <label class="form-label">Días</label>
                            <input type="number" step="0.5" min="0" name="dias_vacacion" id="dias_vacacion" class="form-control">
                            <div class="form-hint">Se sugiere por rango, puedes editarlo.</div>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Notas</label>
                            <textarea name="notas_vacacion" id="notas_vacacion" class="form-control" rows="2" placeholder="Motivo, observación o referencia del movimiento"></textarea>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-outline-primary">Registrar movimiento</button>
                </form>

                <div class="table-responsive mt-4">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Periodo</th>
                                <th>Tipo</th>
                                <th>Impacto</th>
                                <th>Pago</th>
                                <th>Prima</th>
                                <th>Estado</th>
                                <th>Notas</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($vacacionesRows as $row): ?>
                                <?php
                                $tipoMovimiento = $row['tipo'];
                                $impacto = 0.0;
                                $badge = 'bg-secondary';
                                if ($tipoMovimiento === 'GOZADAS') {
                                    $impacto = -1 * (float)$row['dias'];
                                    $badge = 'bg-primary';
                                } elseif ($tipoMovimiento === 'PAGADAS') {
                                    $impacto = -1 * (float)$row['dias'];
                                    $badge = 'bg-info';
                                } elseif ($tipoMovimiento === 'AJUSTE') {
                                    $impacto = (float)$row['dias'];
                                    $tipoMovimiento = $impacto >= 0 ? 'AJUSTE A FAVOR' : 'AJUSTE EN CONTRA';
                                    $badge = $impacto >= 0 ? 'bg-success' : 'bg-warning text-dark';
                                } elseif ($tipoMovimiento === 'NOTA') {
                                    $badge = 'bg-dark';
                                }
                                ?>
                                <tr>
                                    <td><?php echo app_date($row['fecha_inicio']); ?> - <?php echo app_date($row['fecha_fin']); ?></td>
                                    <td><span class="badge <?php echo $badge; ?>"><?php echo app_h($tipoMovimiento); ?></span></td>
                                    <td><?php echo $impacto === 0.0 ? '-' : app_number($impacto) . ' días'; ?></td>
                                    <td><?php echo app_money($row['monto_pago']); ?></td>
                                    <td><?php echo app_money($row['monto_prima']); ?></td>
                                    <td><?php echo app_h($row['estado']); ?></td>
                                    <td>
                                        <?php echo app_h($row['notas']); ?>
                                        <?php if (!empty($row['origen'])): ?>
                                            <div class="text-muted small"><?php echo app_h($row['origen']); ?> <?php echo app_h($row['referencia']); ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <?php if ($row['estado'] !== 'CANCELADO'): ?>
                                            <form method="post" onsubmit="return confirm('¿Cancelar este movimiento de vacaciones?');">
                                                <input type="hidden" name="accion" value="cancelar_vacacion">
                                                <input type="hidden" name="vacacion_id" value="<?php echo (int)$row['id']; ?>">
                                                <button type="submit" class="btn btn-outline-danger btn-sm">Cancelar</button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (!$vacacionesRows): ?>
                                <tr><td colspan="8" class="text-center text-muted">Sin movimientos de vacaciones.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-6">
        <div class="card content-card mb-4">
            <div class="card-body">
                <h5 class="mb-3">Faltas y descansos</h5>
                <form method="post">
                    <input type="hidden" name="accion" value="registrar_falta">
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Fecha</label>
                            <input type="date" name="fecha_falta" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Categoría</label>
                            <select name="categoria_falta" class="form-select">
                                <option value="FALTA">Falta</option>
                                <option value="DESCANSO">Descanso</option>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Tipo</label>
                            <select name="tipo_falta" class="form-select">
                                <option value="JUSTIFICADA">Justificada</option>
                                <option value="INJUSTIFICADA">Injustificada</option>
                                <option value="AJUSTADA">Ajustada</option>
                            </select>
                            <div class="form-hint">Solo aplica para faltas.</div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label d-block">Descanso</label>
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" name="goce_sueldo" id="goce_sueldo">
                                <label class="form-check-label" for="goce_sueldo">Con goce de sueldo</label>
                            </div>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Motivo</label>
                            <input type="text" name="motivo_falta" class="form-control">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-outline-primary">Registrar incidencia</button>
                </form>

                <div class="table-responsive mt-4">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Categoría</th>
                                <th>Tipo</th>
                                <th>Goce</th>
                                <th>Monto</th>
                                <th>Motivo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($faltasRows as $row): ?>
                                <tr>
                                    <td><?php echo app_date($row['fecha_falta']); ?></td>
                                    <td><?php echo app_h($row['categoria'] ?? 'FALTA'); ?></td>
                                    <td><?php echo app_h($row['tipo']); ?></td>
                                    <td><?php echo (int)($row['goce_sueldo'] ?? 0) === 1 ? 'Sí' : 'No'; ?></td>
                                    <td><?php echo app_money($row['monto_descuento']); ?></td>
                                    <td><?php echo app_h($row['motivo']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (!$faltasRows): ?>
                                <tr><td colspan="6" class="text-center text-muted">Sin faltas o descansos registrados.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-xl-4">
        <div class="card content-card mb-4">
            <div class="card-body">
                <h5 class="mb-3">Sanciones</h5>
                <form method="post">
                    <input type="hidden" name="accion" value="registrar_sancion">
                    <div class="mb-3">
                        <label class="form-label">Fecha</label>
                        <input type="date" name="fecha_sancion" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Motivo</label>
                        <input type="text" name="motivo_sancion" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Monto</label>
                        <input type="number" step="0.01" min="0" name="monto_sancion" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Quincenas</label>
                        <input type="number" min="1" name="quincenas_sancion" class="form-control" value="1">
                    </div>
                    <button type="submit" class="btn btn-outline-primary w-100">Guardar sanción</button>
                </form>

                <div class="table-responsive mt-4">
                    <table class="table table-sm">
                        <thead><tr><th>Motivo</th><th>Monto/qna</th><th>Restan</th></tr></thead>
                        <tbody>
                            <?php foreach ($sancionRows as $row): ?>
                                <tr>
                                    <td><?php echo app_h($row['motivo']); ?></td>
                                    <td><?php echo app_money($row['monto_por_quincena']); ?></td>
                                    <td><?php echo (int)$row['quincenas_restantes']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (!$sancionRows): ?>
                                <tr><td colspan="3" class="text-center text-muted">Sin sanciones.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="card content-card mb-4">
            <div class="card-body">
                <h5 class="mb-3">Descuento de material</h5>
                <form method="post">
                    <input type="hidden" name="accion" value="registrar_material">
                    <div class="mb-3">
                        <label class="form-label">Fecha</label>
                        <input type="date" name="fecha_material" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tipo de material</label>
                        <input type="text" name="tipo_material" class="form-control" value="Botas">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Monto total</label>
                        <input type="number" step="0.01" min="0" name="monto_material" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Quincenas</label>
                        <input type="number" min="1" name="quincenas_material" class="form-control" value="<?php echo app_config_int($configs, 'material_quincenas_default', 2); ?>">
                    </div>
                    <button type="submit" class="btn btn-outline-primary w-100">Guardar descuento</button>
                </form>

                <div class="table-responsive mt-4">
                    <table class="table table-sm">
                        <thead><tr><th>Material</th><th>Monto/qna</th><th>Restan</th></tr></thead>
                        <tbody>
                            <?php foreach ($materialRows as $row): ?>
                                <tr>
                                    <td><?php echo app_h($row['tipo_material']); ?></td>
                                    <td><?php echo app_money($row['monto_por_quincena']); ?></td>
                                    <td><?php echo (int)$row['quincenas_restantes']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (!$materialRows): ?>
                                <tr><td colspan="3" class="text-center text-muted">Sin descuentos de material.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="card content-card mb-4">
            <div class="card-body">
                <h5 class="mb-3">Bonos e incapacidades</h5>
                <form method="post">
                    <input type="hidden" name="accion" value="registrar_bono">
                    <div class="mb-3">
                        <label class="form-label">Fecha</label>
                        <input type="date" name="fecha_bono" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Categoría</label>
                        <select name="categoria_bono" class="form-select">
                            <option value="BONO">Bono</option>
                            <option value="VACACIONES">Vacaciones</option>
                            <option value="INCAPACIDAD">Incapacidad</option>
                            <option value="OTRO">Otro</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Motivo</label>
                        <input type="text" name="motivo_bono" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Monto</label>
                        <input type="number" step="0.01" min="0" name="monto_bono" class="form-control">
                    </div>
                    <button type="submit" class="btn btn-outline-primary w-100">Guardar bono</button>
                </form>

                <div class="table-responsive mt-4">
                    <table class="table table-sm">
                        <thead><tr><th>Fecha</th><th>Categoría</th><th>Monto</th></tr></thead>
                        <tbody>
                            <?php foreach ($bonosRows as $row): ?>
                                <tr>
                                    <td><?php echo app_date($row['fecha_aplicacion']); ?></td>
                                    <td><?php echo app_h($row['categoria']); ?></td>
                                    <td><?php echo app_money($row['monto']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (!$bonosRows): ?>
                                <tr><td colspan="3" class="text-center text-muted">Sin bonos registrados.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <hr>

                <form method="post">
                    <input type="hidden" name="accion" value="registrar_incapacidad">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Inicio incapacidad</label>
                            <input type="date" name="fecha_inicio_incapacidad" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Fin incapacidad</label>
                            <input type="date" name="fecha_fin_incapacidad" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Días</label>
                            <input type="number" min="1" name="dias_incapacidad" class="form-control" value="1">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Monto por día</label>
                            <input type="number" step="0.01" min="0" name="monto_por_dia_incapacidad" class="form-control" value="<?php echo app_h(app_salary_diario($personal, $configs)); ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Motivo</label>
                            <input type="text" name="motivo_incapacidad" class="form-control">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-outline-secondary w-100">Registrar incapacidad</button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-xl-6">
        <div class="card content-card mb-4">
            <div class="card-body">
                <h5 class="mb-3">Incapacidades registradas</h5>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead><tr><th>Periodo</th><th>Días</th><th>Monto/día</th><th>Motivo</th></tr></thead>
                        <tbody>
                            <?php foreach ($incapacidadRows as $row): ?>
                                <tr>
                                    <td><?php echo app_date($row['fecha_inicio']); ?> - <?php echo app_date($row['fecha_fin']); ?></td>
                                    <td><?php echo app_number($row['dias']); ?></td>
                                    <td><?php echo app_money($row['monto_por_dia']); ?></td>
                                    <td><?php echo app_h($row['motivo']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (!$incapacidadRows): ?>
                                <tr><td colspan="4" class="text-center text-muted">Sin incapacidades registradas.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-6">
        <div class="card content-card mb-4">
            <div class="card-body">
                <h5 class="mb-3">Baja del colaborador</h5>
                <p class="text-muted">
                    Al registrar la baja se desactiva el acceso del usuario, se calculan vacaciones pendientes
                    para finiquito y se marca el estatus del pago.
                </p>
                <form method="post">
                    <input type="hidden" name="accion" value="registrar_baja">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Fecha de baja</label>
                            <input type="date" name="fecha_baja" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Finiquito capturado</label>
                            <input type="number" step="0.01" min="0" name="finiquito_baja" class="form-control">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Estatus pago</label>
                            <select name="estatus_pago_baja" class="form-select">
                                <option value="PENDIENTE">Pendiente</option>
                                <option value="CERRADO">Cerrado</option>
                            </select>
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label">Motivo</label>
                            <input type="text" name="motivo_baja" class="form-control">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-danger" onclick="return confirm('¿Registrar la baja y desactivar accesos?');">
                        Registrar baja
                    </button>
                </form>

                <div class="table-responsive mt-4">
                    <table class="table table-sm">
                        <thead><tr><th>Fecha</th><th>Finiquito</th><th>Estatus</th><th>Motivo</th></tr></thead>
                        <tbody>
                            <?php foreach ($bajaRows as $row): ?>
                                <tr>
                                    <td><?php echo app_date($row['fecha_baja']); ?></td>
                                    <td><?php echo app_money($row['finiquito_monto']); ?></td>
                                    <td><?php echo app_h($row['estatus_pago']); ?></td>
                                    <td><?php echo app_h($row['motivo']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (!$bajaRows): ?>
                                <tr><td colspan="4" class="text-center text-muted">Sin bajas registradas.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const tipo = document.getElementById('tipo_vacacion');
    const inicio = document.getElementById('fecha_inicio_vacacion');
    const fin = document.getElementById('fecha_fin_vacacion');
    const dias = document.getElementById('dias_vacacion');

    function diasNaturalesInclusivos() {
        if (!inicio.value || !fin.value) {
            return 0;
        }

        const start = new Date(inicio.value + 'T00:00:00');
        const end = new Date(fin.value + 'T00:00:00');
        if (Number.isNaN(start.getTime()) || Number.isNaN(end.getTime()) || end < start) {
            return 0;
        }

        return Math.floor((end - start) / 86400000) + 1;
    }

    function actualizarVacaciones() {
        if (!tipo || !inicio || !fin || !dias) {
            return;
        }

        const usaRango = tipo.value === 'GOZADAS' || tipo.value === 'PAGADAS';
        fin.disabled = !usaRango;
        dias.disabled = tipo.value === 'NOTA';

        if (!usaRango) {
            fin.value = inicio.value;
        }

        if (tipo.value === 'NOTA') {
            dias.value = '0';
            return;
        }

        if (usaRango) {
            const sugeridos = diasNaturalesInclusivos();
            if (sugeridos > 0) {
                dias.value = sugeridos;
            }
        } else if (!dias.value || Number(dias.value) <= 0) {
            dias.value = '1';
        }
    }

    [tipo, inicio, fin].forEach((element) => {
        if (element) {
            element.addEventListener('change', actualizarVacaciones);
        }
    });

    actualizarVacaciones();
});
</script>

<?php
app_render_page_end();
