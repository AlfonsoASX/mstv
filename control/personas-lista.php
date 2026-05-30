<?php

require_once __DIR__ . '/lib/app.php';
require_once __DIR__ . '/lib/ui.php';
require_once __DIR__ . '/lib/payroll.php';
require_once __DIR__ . '/lib/operations.php';

app_require_session();
app_require_roles(['ADMIN', 'RH', 'NOMINA', 'DUEÑO', 'SUPERVISOR']);
app_support_bootstrap($conexion);

function personas_role_options(mysqli $conexion): array
{
    return app_db_all($conexion, "SELECT id, nombre FROM roles ORDER BY nombre ASC");
}

function personas_clean_date_field(string $key): ?string
{
    $value = app_clean_text(app_post($key, ''));
    return $value === '' ? null : $value;
}

function personas_find_conflicts(mysqli $conexion, string $usuario, string $email, ?int $excludeUserId = null): array
{
    $conflicts = [];
    $excludeSql = $excludeUserId ? " AND id <> " . (int)$excludeUserId : '';

    if ($usuario !== '') {
        $row = app_db_one($conexion, "
            SELECT id FROM usuarios
            WHERE usuario = '" . mysqli_real_escape_string($conexion, $usuario) . "'
            {$excludeSql}
            LIMIT 1
        ");
        if ($row) {
            $conflicts[] = 'El nombre de usuario ya existe.';
        }
    }

    if ($email !== '') {
        $row = app_db_one($conexion, "
            SELECT id FROM usuarios
            WHERE email = '" . mysqli_real_escape_string($conexion, $email) . "'
            {$excludeSql}
            LIMIT 1
        ");
        if ($row) {
            $conflicts[] = 'El correo ya está asignado a otro usuario.';
        }
    }

    return $conflicts;
}

$messages = ['success' => '', 'error' => ''];
$configs = app_get_config_map($conexion);
$roleOptions = personas_role_options($conexion);
$defaultSalary = app_config_float($configs, 'nomina_salario_minimo_diario', 278.80);
$defaultHourly = app_config_float($configs, 'nomina_valor_hora', 75.0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = app_post('accion', '');

    if (in_array($accion, ['crear_personal', 'editar_personal'], true)) {
        $personalId = (int)app_post('personal_id', 0);
        $usuarioId = (int)app_post('usuario_id', 0);
        $nombres = app_clean_text(app_post('nombres', ''));
        $apellidos = app_clean_text(app_post('apellidos', ''));
        $usuario = app_clean_text(app_post('usuario', ''));
        $email = app_clean_text(app_post('email', ''));
        $telefono = app_clean_text(app_post('telefono', ''));
        $fechaContratacion = app_clean_text(app_post('fecha_contratacion', date('Y-m-d')));
        $rolId = (int)app_post('rol_id', 0);
        $estado = app_clean_text(app_post('estado', 'ACTIVO'));
        $estaActivo = app_post('esta_activo') ? 1 : 0;
        $activoNomina = app_post('activo_en_nomina') ? 1 : 0;
        $salarioDiario = (float)app_post('salario_diario', $defaultSalary);
        $salarioHora = (float)app_post('salario_hora', $defaultHourly);
        $tipoContrato = app_clean_text(app_post('tipo_contrato', ''));
        $password = (string)app_post('password_nuevo', '');
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
        $fechaNacimiento = personas_clean_date_field('fecha_nacimiento');
        $tallaCamisa = app_clean_text(app_post('talla_camisa', ''));
        $tallaPantalon = app_clean_text(app_post('talla_pantalon', ''));
        $tallaCalzado = app_clean_text(app_post('talla_calzado', ''));
        $contactoEmergencia = app_clean_text(app_post('contacto_emergencia', ''));
        $contactoEmergenciaParentesco = app_clean_text(app_post('contacto_emergencia_parentesco', ''));
        $contactoEmergenciaTelefono = app_clean_text(app_post('contacto_emergencia_telefono', ''));
        $tieneHijos = app_post('tiene_hijos') ? 1 : 0;
        $edadesHijos = app_clean_text(app_post('edades_hijos', ''));
        $vacaciones2024Notas = app_clean_text(app_post('vacaciones_2024_notas', ''));
        $vacaciones2025Notas = app_clean_text(app_post('vacaciones_2025_notas', ''));
        $vacaciones2026Notas = app_clean_text(app_post('vacaciones_2026_notas', ''));

        if ($estado !== 'ACTIVO') {
            $estaActivo = 0;
            $activoNomina = 0;
        }

        $errors = [];
        if ($nombres === '' || $apellidos === '') {
            $errors[] = 'Captura nombre y apellidos del colaborador.';
        }
        if ($usuario === '') {
            $errors[] = 'Captura el usuario de acceso.';
        }
        if ($rolId <= 0) {
            $errors[] = 'Selecciona un rol válido.';
        }
        if (!in_array($estado, ['ACTIVO', 'INACTIVO', 'SUSPENDIDO'], true)) {
            $errors[] = 'Selecciona un estado válido.';
        }
        if ($accion === 'crear_personal' && mb_strlen($password, 'UTF-8') < 6) {
            $errors[] = 'La contraseña inicial debe tener al menos 6 caracteres.';
        }

        $conflicts = personas_find_conflicts($conexion, $usuario, $email, $accion === 'editar_personal' ? $usuarioId : null);
        $errors = array_merge($errors, $conflicts);

        if ($errors) {
            $messages['error'] = implode(' ', $errors);
        } else {
            if ($accion === 'crear_personal') {
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                mysqli_begin_transaction($conexion);

                try {
                    $sqlUser = "
                        INSERT INTO usuarios (usuario, contrasena_hash, email, rol_id, esta_activo)
                        VALUES (?, ?, ?, ?, ?)
                    ";
                    if (!$stmtUser = mysqli_prepare($conexion, $sqlUser)) {
                        throw new RuntimeException('No fue posible crear el usuario.');
                    }

                    mysqli_stmt_bind_param($stmtUser, 'sssii', $usuario, $passwordHash, $email, $rolId, $estaActivo);
                    mysqli_stmt_execute($stmtUser);
                    $newUserId = (int)mysqli_insert_id($conexion);
                    mysqli_stmt_close($stmtUser);

                    $sqlPersonal = "
                        INSERT INTO personal
                            (usuario_id, nombres, apellidos, telefono, fecha_contratacion, estado, salario_diario, salario_hora, tipo_contrato, activo_en_nomina,
                             puesto_operativo, turno_base, servicio_asignado, infospe_estatus, cecceg_estatus, sexo, estado_civil, domicilio,
                             codigo_postal, nss, rfc, curp, cuenta_bancaria, banco, fecha_nacimiento, talla_camisa, talla_pantalon, talla_calzado,
                             contacto_emergencia, contacto_emergencia_parentesco, contacto_emergencia_telefono, tiene_hijos, edades_hijos,
                             vacaciones_2024_notas, vacaciones_2025_notas, vacaciones_2026_notas)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ";
                    if (!$stmtPersonal = mysqli_prepare($conexion, $sqlPersonal)) {
                        throw new RuntimeException('No fue posible crear el registro de personal.');
                    }

                    mysqli_stmt_bind_param(
                        $stmtPersonal,
                        'isssssddsisssssssssssssssssssssissss',
                        $newUserId,
                        $nombres,
                        $apellidos,
                        $telefono,
                        $fechaContratacion,
                        $estado,
                        $salarioDiario,
                        $salarioHora,
                        $tipoContrato,
                        $activoNomina,
                        $puestoOperativo,
                        $turnoBase,
                        $servicioAsignado,
                        $infospeEstatus,
                        $ceccegEstatus,
                        $sexo,
                        $estadoCivil,
                        $domicilio,
                        $codigoPostal,
                        $nss,
                        $rfc,
                        $curp,
                        $cuentaBancaria,
                        $banco,
                        $fechaNacimiento,
                        $tallaCamisa,
                        $tallaPantalon,
                        $tallaCalzado,
                        $contactoEmergencia,
                        $contactoEmergenciaParentesco,
                        $contactoEmergenciaTelefono,
                        $tieneHijos,
                        $edadesHijos,
                        $vacaciones2024Notas,
                        $vacaciones2025Notas,
                        $vacaciones2026Notas
                    );
                    mysqli_stmt_execute($stmtPersonal);
                    $newPersonalId = (int)mysqli_insert_id($conexion);
                    mysqli_stmt_close($stmtPersonal);

                    mysqli_commit($conexion);
                    app_log_system($conexion, (int)($_SESSION['usuario_id'] ?? 0), 'PERSONAL_ALTA', 'personal', $newPersonalId, [
                        'usuario_id' => $newUserId,
                        'estado' => $estado,
                    ]);
                    $messages['success'] = 'Nuevo colaborador registrado correctamente.';
                } catch (Throwable $exception) {
                    mysqli_rollback($conexion);
                    $messages['error'] = $exception->getMessage();
                }
            }

            if ($accion === 'editar_personal' && $personalId > 0 && $usuarioId > 0) {
                mysqli_begin_transaction($conexion);

                try {
                    $sqlUser = "
                        UPDATE usuarios
                        SET usuario = ?,
                            email = ?,
                            rol_id = ?,
                            esta_activo = ?
                        WHERE id = ?
                        LIMIT 1
                    ";
                    if (!$stmtUser = mysqli_prepare($conexion, $sqlUser)) {
                        throw new RuntimeException('No fue posible actualizar el usuario.');
                    }

                    mysqli_stmt_bind_param($stmtUser, 'ssiii', $usuario, $email, $rolId, $estaActivo, $usuarioId);
                    mysqli_stmt_execute($stmtUser);
                    mysqli_stmt_close($stmtUser);

                    $sqlPersonal = "
                        UPDATE personal
                        SET nombres = ?,
                            apellidos = ?,
                            telefono = ?,
                            fecha_contratacion = ?,
                            estado = ?,
                            salario_diario = ?,
                            salario_hora = ?,
                            tipo_contrato = ?,
                            activo_en_nomina = ?,
                            puesto_operativo = ?,
                            turno_base = ?,
                            servicio_asignado = ?,
                            infospe_estatus = ?,
                            cecceg_estatus = ?,
                            sexo = ?,
                            estado_civil = ?,
                            domicilio = ?,
                            codigo_postal = ?,
                            nss = ?,
                            rfc = ?,
                            curp = ?,
                            cuenta_bancaria = ?,
                            banco = ?,
                            fecha_nacimiento = ?,
                            talla_camisa = ?,
                            talla_pantalon = ?,
                            talla_calzado = ?,
                            contacto_emergencia = ?,
                            contacto_emergencia_parentesco = ?,
                            contacto_emergencia_telefono = ?,
                            tiene_hijos = ?,
                            edades_hijos = ?,
                            vacaciones_2024_notas = ?,
                            vacaciones_2025_notas = ?,
                            vacaciones_2026_notas = ?
                        WHERE id = ?
                        LIMIT 1
                    ";
                    if (!$stmtPersonal = mysqli_prepare($conexion, $sqlPersonal)) {
                        throw new RuntimeException('No fue posible actualizar el registro de personal.');
                    }

                    mysqli_stmt_bind_param(
                        $stmtPersonal,
                        'sssssddsisssssssssssssssssssssissssi',
                        $nombres,
                        $apellidos,
                        $telefono,
                        $fechaContratacion,
                        $estado,
                        $salarioDiario,
                        $salarioHora,
                        $tipoContrato,
                        $activoNomina,
                        $puestoOperativo,
                        $turnoBase,
                        $servicioAsignado,
                        $infospeEstatus,
                        $ceccegEstatus,
                        $sexo,
                        $estadoCivil,
                        $domicilio,
                        $codigoPostal,
                        $nss,
                        $rfc,
                        $curp,
                        $cuentaBancaria,
                        $banco,
                        $fechaNacimiento,
                        $tallaCamisa,
                        $tallaPantalon,
                        $tallaCalzado,
                        $contactoEmergencia,
                        $contactoEmergenciaParentesco,
                        $contactoEmergenciaTelefono,
                        $tieneHijos,
                        $edadesHijos,
                        $vacaciones2024Notas,
                        $vacaciones2025Notas,
                        $vacaciones2026Notas,
                        $personalId
                    );
                    mysqli_stmt_execute($stmtPersonal);
                    mysqli_stmt_close($stmtPersonal);

                    mysqli_commit($conexion);
                    app_log_system($conexion, (int)($_SESSION['usuario_id'] ?? 0), 'PERSONAL_EDITA', 'personal', $personalId, [
                        'usuario_id' => $usuarioId,
                        'estado' => $estado,
                    ]);
                    $messages['success'] = 'Datos del colaborador actualizados.';
                } catch (Throwable $exception) {
                    mysqli_rollback($conexion);
                    $messages['error'] = $exception->getMessage();
                }
            }
        }
    }
}

$periodoActualFechas = app_current_period_bounds();
$periodoActualId = app_get_or_create_period($conexion, $periodoActualFechas['inicio'], $periodoActualFechas['fin']);

$personalRows = app_db_all($conexion, "
    SELECT
        p.*,
        u.usuario,
        u.email,
        u.esta_activo,
        u.id AS usuario_id,
        COALESCE(r.nombre, '') AS rol_nombre,
        COALESCE(r.id, 0) AS rol_id
    FROM personal p
    INNER JOIN usuarios u ON u.id = p.usuario_id
    LEFT JOIN roles r ON r.id = u.rol_id
    ORDER BY
        CASE WHEN p.estado = 'ACTIVO' THEN 0 ELSE 1 END,
        p.nombres,
        p.apellidos
");

$rows = [];
foreach ($personalRows as $personal) {
    $personalId = (int)$personal['id'];
    $vacaciones = (float)($personal['dias_vacaciones_disponibles'] ?? 0);
    $saldoCaja = app_get_savings_balance($conexion, $personalId);
    $prestamosActivos = app_get_prestamos_activos($conexion, $personalId);
    $saldoPrestamos = 0.0;
    foreach ($prestamosActivos as $prestamo) {
        $saldoPrestamos += (float)$prestamo['saldo_insoluto'];
    }

    $adelantosPeriodo = app_get_total_adelantos_periodo($conexion, $periodoActualId, $personalId);
    $ganado = app_get_employee_earned_to_date($conexion, $personalId, $periodoActualFechas['inicio'], date('Y-m-d'), $configs);
    $limiteManual = (float)($personal['limite_adelanto_nomina'] ?? 0);
    $disponible = max(0.0, $ganado - $adelantosPeriodo);
    if ($limiteManual > 0) {
        $disponible = min($disponible, $limiteManual);
    }

    $rows[] = [
        'base' => $personal,
        'vacaciones' => $vacaciones,
        'saldo_caja' => $saldoCaja,
        'saldo_prestamos' => $saldoPrestamos,
        'adelanto_disponible' => $disponible,
    ];
}

$activeCount = 0;
foreach ($rows as $row) {
    if (($row['base']['estado'] ?? '') === 'ACTIVO') {
        $activeCount++;
    }
}

$extraHead = <<<HTML
<style>
    .person-chip {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 0.3rem 0.65rem;
        border-radius: 999px;
        background: rgba(0, 173, 181, 0.08);
        color: #0f172a;
        font-size: 0.78rem;
        font-weight: 600;
    }
    .modal .form-check-input {
        margin-top: 0.2rem;
    }
</style>
HTML;

app_render_page_start(
    'Base de personal',
    'Base de personal',
    'Alta y edición de colaboradores con acceso rápido a ficha, caja, préstamos, vacaciones y adelantos.',
    $extraHead
);
app_render_alerts($messages);
?>

<div class="row layout-top-spacing mb-3">
    <div class="col-md-3">
        <div class="card summary-card p-3">
            <div class="summary-label">Colaboradores activos</div>
            <div class="summary-value"><?php echo $activeCount; ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card summary-card p-3">
            <div class="summary-label">Total registrados</div>
            <div class="summary-value"><?php echo count($rows); ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card summary-card p-3">
            <div class="summary-label">Caja acumulada</div>
            <div class="summary-value"><?php echo app_money(app_total_savings_pool($conexion)); ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card summary-card p-3">
            <div class="summary-label">Promedio salario diario</div>
            <div class="summary-value">
                <?php
                $promedio = 0.0;
                if ($rows) {
                    $sum = 0.0;
                    foreach ($rows as $row) {
                        $sum += app_salary_diario($row['base'], $configs);
                    }
                    $promedio = $sum / count($rows);
                }
                echo app_money($promedio);
                ?>
            </div>
        </div>
    </div>
</div>

<div class="card content-card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h5 class="mb-0">Catálogo de personal</h5>
                <small class="text-muted">Da de alta nuevos colaboradores o edita los existentes desde esta misma pantalla.</small>
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-primary" id="btnNuevoPersonal" data-bs-toggle="modal" data-bs-target="#modalPersonal">
                    Nuevo colaborador
                </button>
                <a href="nomina-calculo.php" class="btn btn-outline-primary">Ir a nómina</a>
            </div>
	        </div>
            <div class="mt-3">
                <input type="search" class="form-control" data-table-search="#personas-table" placeholder="Buscar por nombre, No. empleado, usuario, teléfono, estado...">
            </div>

	        <div class="table-responsive">
	            <table class="table table-striped" id="personas-table">
                <thead>
                    <tr>
	                        <th>Colaborador</th>
	                        <th>No. empleado</th>
	                        <th>Usuario</th>
                        <th>Contacto</th>
                        <th>Antigüedad</th>
                        <th>Salario diario</th>
                        <th>Caja ahorro</th>
                        <th>Vacaciones</th>
                        <th>Estado</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $row): ?>
                        <?php
                        $personal = $row['base'];
                        $personalId = (int)$personal['id'];
                        $estado = (string)$personal['estado'];
                        ?>
                        <tr>
                            <td>
                                <strong><?php echo app_h($personal['nombres'] . ' ' . $personal['apellidos']); ?></strong><br>
	                                <small class="text-muted">
	                                    <?php echo app_h($personal['rol_nombre']); ?>
	                                </small>
	                            </td>
	                            <td><?php echo app_h(app_employee_number($personal)); ?></td>
	                            <td><?php echo app_h($personal['usuario']); ?></td>
                            <td>
                                <div><?php echo app_h($personal['email']); ?></div>
                                <small class="text-muted"><?php echo app_h($personal['telefono']); ?></small>
                            </td>
                            <td><?php echo app_h(app_tenure_label($personal['fecha_contratacion'] ?? null)); ?></td>
                            <td><?php echo app_money(app_salary_diario($personal, $configs)); ?></td>
                            <td><?php echo app_money($row['saldo_caja']); ?></td>
                            <td><?php echo app_number($row['vacaciones']); ?> días</td>
                            <td>
                                <?php if ($estado === 'ACTIVO'): ?>
                                    <span class="badge bg-success">Activo</span>
                                <?php elseif ($estado === 'SUSPENDIDO'): ?>
                                    <span class="badge bg-warning text-dark">Suspendido</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Inactivo</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <button
                                    type="button"
                                    class="btn btn-outline-primary btn-sm btn-editar-personal"
                                    data-bs-toggle="modal"
                                    data-bs-target="#modalPersonal"
                                    data-personal-id="<?php echo $personalId; ?>"
                                    data-usuario-id="<?php echo (int)$personal['usuario_id']; ?>"
                                    data-nombres="<?php echo app_h($personal['nombres']); ?>"
                                    data-apellidos="<?php echo app_h($personal['apellidos']); ?>"
                                    data-usuario="<?php echo app_h($personal['usuario']); ?>"
                                    data-email="<?php echo app_h($personal['email']); ?>"
                                    data-telefono="<?php echo app_h($personal['telefono']); ?>"
                                    data-fecha-contratacion="<?php echo app_h($personal['fecha_contratacion']); ?>"
                                    data-rol-id="<?php echo (int)$personal['rol_id']; ?>"
                                    data-estado="<?php echo app_h($personal['estado']); ?>"
                                    data-esta-activo="<?php echo (int)$personal['esta_activo']; ?>"
                                    data-activo-nomina="<?php echo (int)$personal['activo_en_nomina']; ?>"
                                    data-salario-diario="<?php echo app_h($personal['salario_diario']); ?>"
                                    data-salario-hora="<?php echo app_h($personal['salario_hora']); ?>"
                                    data-tipo-contrato="<?php echo app_h($personal['tipo_contrato']); ?>"
                                    data-puesto-operativo="<?php echo app_h($personal['puesto_operativo'] ?? ''); ?>"
                                    data-turno-base="<?php echo app_h($personal['turno_base'] ?? ''); ?>"
                                    data-servicio-asignado="<?php echo app_h($personal['servicio_asignado'] ?? ''); ?>"
                                    data-infospe-estatus="<?php echo app_h($personal['infospe_estatus'] ?? ''); ?>"
                                    data-cecceg-estatus="<?php echo app_h($personal['cecceg_estatus'] ?? ''); ?>"
                                    data-sexo="<?php echo app_h($personal['sexo'] ?? ''); ?>"
                                    data-estado-civil="<?php echo app_h($personal['estado_civil'] ?? ''); ?>"
                                    data-domicilio="<?php echo app_h($personal['domicilio'] ?? ''); ?>"
                                    data-codigo-postal="<?php echo app_h($personal['codigo_postal'] ?? ''); ?>"
                                    data-nss="<?php echo app_h($personal['nss'] ?? ''); ?>"
                                    data-rfc="<?php echo app_h($personal['rfc'] ?? ''); ?>"
                                    data-curp="<?php echo app_h($personal['curp'] ?? ''); ?>"
                                    data-cuenta-bancaria="<?php echo app_h($personal['cuenta_bancaria'] ?? ''); ?>"
                                    data-banco="<?php echo app_h($personal['banco'] ?? ''); ?>"
                                    data-fecha-nacimiento="<?php echo app_h($personal['fecha_nacimiento'] ?? ''); ?>"
                                    data-talla-camisa="<?php echo app_h($personal['talla_camisa'] ?? ''); ?>"
                                    data-talla-pantalon="<?php echo app_h($personal['talla_pantalon'] ?? ''); ?>"
                                    data-talla-calzado="<?php echo app_h($personal['talla_calzado'] ?? ''); ?>"
                                    data-contacto-emergencia="<?php echo app_h($personal['contacto_emergencia'] ?? ''); ?>"
                                    data-contacto-emergencia-parentesco="<?php echo app_h($personal['contacto_emergencia_parentesco'] ?? ''); ?>"
                                    data-contacto-emergencia-telefono="<?php echo app_h($personal['contacto_emergencia_telefono'] ?? ''); ?>"
                                    data-tiene-hijos="<?php echo (int)($personal['tiene_hijos'] ?? 0); ?>"
                                    data-edades-hijos="<?php echo app_h($personal['edades_hijos'] ?? ''); ?>"
                                    data-vacaciones-2024-notas="<?php echo app_h($personal['vacaciones_2024_notas'] ?? ''); ?>"
                                    data-vacaciones-2025-notas="<?php echo app_h($personal['vacaciones_2025_notas'] ?? ''); ?>"
                                    data-vacaciones-2026-notas="<?php echo app_h($personal['vacaciones_2026_notas'] ?? ''); ?>">
                                    Editar
                                </button>
                                <a href="personas-ficha.php?personal_id=<?php echo $personalId; ?>" class="btn btn-primary btn-sm">
                                    Abrir ficha
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$rows): ?>
                        <tr>
	                            <td colspan="10" class="text-center text-muted">No hay personal registrado.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="modalPersonal" tabindex="-1" aria-labelledby="modalPersonalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content" style="background: #fff;">
            <form method="post" id="formPersonal">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalPersonalLabel">Nuevo colaborador</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="accion" id="personal_accion" value="crear_personal">
                    <input type="hidden" name="personal_id" id="personal_id" value="0">
                    <input type="hidden" name="usuario_id" id="usuario_id" value="0">

                    <h6 class="text-uppercase text-muted mb-3">Datos generales</h6>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nombres</label>
                            <input type="text" class="form-control" name="nombres" id="nombres" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Apellidos</label>
                            <input type="text" class="form-control" name="apellidos" id="apellidos" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Teléfono</label>
                            <input type="text" class="form-control" name="telefono" id="telefono">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Fecha contratación</label>
                            <input type="date" class="form-control" name="fecha_contratacion" id="fecha_contratacion" value="<?php echo date('Y-m-d'); ?>">
                        </div>
                    </div>

                    <hr>

                    <h6 class="text-uppercase text-muted mb-3">Acceso al sistema</h6>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Usuario</label>
                            <input type="text" class="form-control" name="usuario" id="usuario" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" id="email">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Rol</label>
                            <select class="form-select" name="rol_id" id="rol_id" required>
                                <option value="">Selecciona...</option>
                                <?php foreach ($roleOptions as $role): ?>
                                    <option value="<?php echo (int)$role['id']; ?>"><?php echo app_h($role['nombre']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="row" id="row_password_nuevo">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Contraseña inicial</label>
                            <input type="password" class="form-control" name="password_nuevo" id="password_nuevo" minlength="6">
                            <small class="form-hint">Solo se solicita al dar de alta. Después el cambio se hace desde reset de contraseñas.</small>
                        </div>
                    </div>

                    <hr>

                    <h6 class="text-uppercase text-muted mb-3">Datos laborales</h6>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Estado</label>
                            <select class="form-select" name="estado" id="estado">
                                <option value="ACTIVO">Activo</option>
                                <option value="INACTIVO">Inactivo</option>
                                <option value="SUSPENDIDO">Suspendido</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Salario diario</label>
                            <input type="number" step="0.01" min="0" class="form-control" name="salario_diario" id="salario_diario" value="<?php echo app_h($defaultSalary); ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Salario por hora</label>
                            <input type="number" step="0.01" min="0" class="form-control" name="salario_hora" id="salario_hora" value="<?php echo app_h($defaultHourly); ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Tipo de contrato</label>
                            <input type="text" class="form-control" name="tipo_contrato" id="tipo_contrato">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Puesto operativo</label>
                            <input type="text" class="form-control" name="puesto_operativo" id="puesto_operativo">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Turno base</label>
                            <input type="text" class="form-control" name="turno_base" id="turno_base">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Servicio asignado</label>
                            <input type="text" class="form-control" name="servicio_asignado" id="servicio_asignado">
                        </div>
                        <div class="col-md-2 mb-3">
                            <label class="form-label">INFOSPE</label>
                            <input type="text" class="form-control" name="infospe_estatus" id="infospe_estatus">
                        </div>
                        <div class="col-md-2 mb-3">
                            <label class="form-label">CECCEG</label>
                            <input type="text" class="form-control" name="cecceg_estatus" id="cecceg_estatus">
                        </div>
                        <div class="col-md-3 mb-3 d-flex align-items-end">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="esta_activo" id="esta_activo" checked>
                                <label class="form-check-label" for="esta_activo">Acceso al sistema activo</label>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3 d-flex align-items-end">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="activo_en_nomina" id="activo_en_nomina" checked>
                                <label class="form-check-label" for="activo_en_nomina">Activo en nómina</label>
                            </div>
                        </div>
                    </div>

                    <hr>

                    <h6 class="text-uppercase text-muted mb-3">Datos personales</h6>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Sexo</label>
                            <input type="text" class="form-control" name="sexo" id="sexo">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Estado civil</label>
                            <input type="text" class="form-control" name="estado_civil" id="estado_civil">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Fecha de nacimiento</label>
                            <input type="date" class="form-control" name="fecha_nacimiento" id="fecha_nacimiento">
                        </div>
                        <div class="col-md-8 mb-3">
                            <label class="form-label">Domicilio</label>
                            <textarea class="form-control" name="domicilio" id="domicilio" rows="2"></textarea>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Código postal</label>
                            <input type="text" class="form-control" name="codigo_postal" id="codigo_postal">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">NSS</label>
                            <input type="text" class="form-control" name="nss" id="nss">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">RFC</label>
                            <input type="text" class="form-control" name="rfc" id="rfc">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">CURP</label>
                            <input type="text" class="form-control" name="curp" id="curp">
                        </div>
                    </div>

                    <hr>

                    <h6 class="text-uppercase text-muted mb-3">Datos bancarios y uniforme</h6>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Cuenta bancaria</label>
                            <input type="text" class="form-control" name="cuenta_bancaria" id="cuenta_bancaria">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Banco</label>
                            <input type="text" class="form-control" name="banco" id="banco">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Talla camisa</label>
                            <input type="text" class="form-control" name="talla_camisa" id="talla_camisa">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Talla pantalón</label>
                            <input type="text" class="form-control" name="talla_pantalon" id="talla_pantalon">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Talla calzado</label>
                            <input type="text" class="form-control" name="talla_calzado" id="talla_calzado">
                        </div>
                    </div>

                    <hr>

                    <h6 class="text-uppercase text-muted mb-3">Emergencia y familia</h6>
                    <div class="row">
                        <div class="col-md-5 mb-3">
                            <label class="form-label">Contacto de emergencia</label>
                            <input type="text" class="form-control" name="contacto_emergencia" id="contacto_emergencia">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Parentesco</label>
                            <input type="text" class="form-control" name="contacto_emergencia_parentesco" id="contacto_emergencia_parentesco">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Teléfono emergencia</label>
                            <input type="text" class="form-control" name="contacto_emergencia_telefono" id="contacto_emergencia_telefono">
                        </div>
                        <div class="col-md-3 mb-3 d-flex align-items-end">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="tiene_hijos" id="tiene_hijos">
                                <label class="form-check-label" for="tiene_hijos">Tiene hijos</label>
                            </div>
                        </div>
                        <div class="col-md-9 mb-3">
                            <label class="form-label">Edades de los hijos</label>
                            <input type="text" class="form-control" name="edades_hijos" id="edades_hijos">
                        </div>
                    </div>

                    <hr>

                    <input type="hidden" name="vacaciones_2024_notas" id="vacaciones_2024_notas">
                    <input type="hidden" name="vacaciones_2025_notas" id="vacaciones_2025_notas">
                    <input type="hidden" name="vacaciones_2026_notas" id="vacaciones_2026_notas">
                    <div class="alert alert-info mb-0">
                        Las vacaciones se capturan desde la ficha del colaborador, en el historial operativo.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="btn_guardar_personal">Guardar colaborador</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$extraScripts = <<<HTML
<script>
    const modalPersonal = document.getElementById('modalPersonal');
    const titlePersonal = document.getElementById('modalPersonalLabel');
    const accionPersonal = document.getElementById('personal_accion');
    const rowPassword = document.getElementById('row_password_nuevo');
	    const inputPassword = document.getElementById('password_nuevo');
	    const formPersonal = document.getElementById('formPersonal');
	    const extraPersonalFields = [
	        'puesto_operativo',
	        'turno_base',
	        'servicio_asignado',
	        'infospe_estatus',
	        'cecceg_estatus',
	        'sexo',
	        'estado_civil',
	        'domicilio',
	        'codigo_postal',
	        'nss',
	        'rfc',
	        'curp',
	        'cuenta_bancaria',
	        'banco',
	        'fecha_nacimiento',
	        'talla_camisa',
	        'talla_pantalon',
	        'talla_calzado',
	        'contacto_emergencia',
	        'contacto_emergencia_parentesco',
	        'contacto_emergencia_telefono',
	        'edades_hijos',
	        'vacaciones_2024_notas',
	        'vacaciones_2025_notas',
	        'vacaciones_2026_notas'
	    ];

	    function datasetKey(fieldName) {
	        return fieldName.replace(/_([a-z0-9])/g, (_, char) => char.toUpperCase());
	    }

    function resetModalPersonal() {
        formPersonal.reset();
        accionPersonal.value = 'crear_personal';
        document.getElementById('personal_id').value = '0';
        document.getElementById('usuario_id').value = '0';
        document.getElementById('fecha_contratacion').value = new Date().toISOString().slice(0, 10);
        document.getElementById('salario_diario').value = '{$defaultSalary}';
        document.getElementById('salario_hora').value = '{$defaultHourly}';
        document.getElementById('estado').value = 'ACTIVO';
        document.getElementById('esta_activo').checked = true;
        document.getElementById('activo_en_nomina').checked = true;
        titlePersonal.textContent = 'Nuevo colaborador';
        rowPassword.style.display = '';
        inputPassword.required = true;
        document.getElementById('btn_guardar_personal').textContent = 'Guardar colaborador';
    }

    document.getElementById('btnNuevoPersonal').addEventListener('click', () => {
        resetModalPersonal();
    });

    document.querySelectorAll('.btn-editar-personal').forEach((button) => {
        button.addEventListener('click', () => {
            resetModalPersonal();
            accionPersonal.value = 'editar_personal';
            titlePersonal.textContent = 'Editar colaborador';
            document.getElementById('btn_guardar_personal').textContent = 'Guardar cambios';
            document.getElementById('personal_id').value = button.dataset.personalId || '0';
            document.getElementById('usuario_id').value = button.dataset.usuarioId || '0';
            document.getElementById('nombres').value = button.dataset.nombres || '';
            document.getElementById('apellidos').value = button.dataset.apellidos || '';
            document.getElementById('telefono').value = button.dataset.telefono || '';
            document.getElementById('fecha_contratacion').value = button.dataset.fechaContratacion || '';
            document.getElementById('usuario').value = button.dataset.usuario || '';
            document.getElementById('email').value = button.dataset.email || '';
            document.getElementById('rol_id').value = button.dataset.rolId || '';
            document.getElementById('estado').value = button.dataset.estado || 'ACTIVO';
            document.getElementById('esta_activo').checked = button.dataset.estaActivo === '1';
            document.getElementById('activo_en_nomina').checked = button.dataset.activoNomina === '1';
	            document.getElementById('salario_diario').value = button.dataset.salarioDiario || '{$defaultSalary}';
	            document.getElementById('salario_hora').value = button.dataset.salarioHora || '{$defaultHourly}';
	            document.getElementById('tipo_contrato').value = button.dataset.tipoContrato || '';
	            document.getElementById('tiene_hijos').checked = button.dataset.tieneHijos === '1';
	            extraPersonalFields.forEach((fieldName) => {
	                const field = document.getElementById(fieldName);
	                if (field) {
	                    field.value = button.dataset[datasetKey(fieldName)] || '';
	                }
	            });
	            rowPassword.style.display = 'none';
            inputPassword.required = false;
            inputPassword.value = '';
        });
    });

    modalPersonal.addEventListener('hidden.bs.modal', () => {
        resetModalPersonal();
    });

    resetModalPersonal();
</script>
HTML;

app_render_page_end($extraScripts);
