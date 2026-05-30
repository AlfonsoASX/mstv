<?php

require_once __DIR__ . '/lib/app.php';
require_once __DIR__ . '/lib/payroll.php';

app_require_session();
app_require_page_permission('personas-ficha.php');

$personalId = (int)app_get('personal_id', 0);
if ($personalId <= 0) {
    app_redirect('personas-lista.php');
}

$personal = app_db_one($conexion, "
    SELECT
        p.*,
        u.usuario,
        u.email,
        u.esta_activo,
        COALESCE(r.nombre, '') AS rol_nombre
    FROM personal p
    INNER JOIN usuarios u ON u.id = p.usuario_id
    LEFT JOIN roles r ON r.id = u.rol_id
    WHERE p.id = " . $personalId . "
    LIMIT 1
");

if (!$personal) {
    app_redirect('personas-lista.php');
}

$configs = app_get_config_map($conexion);
$numeroEmpleado = app_employee_number($personal);
$nombreCompleto = trim((string)$personal['nombres'] . ' ' . (string)$personal['apellidos']);
$saldoCaja = app_get_savings_balance($conexion, $personalId);
$vacaciones = app_vacation_summary($conexion, $personalId, $configs);
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ficha técnica <?php echo app_h($numeroEmpleado); ?></title>
    <link href="../src/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #eef2f7;
            color: #101828;
            font-family: "Nunito", Arial, sans-serif;
        }
        .sheet {
            background: #fff;
            box-shadow: 0 24px 70px rgba(15, 23, 42, .12);
            margin: 28px auto;
            max-width: 980px;
            padding: 34px;
        }
        .hero {
            border-bottom: 3px solid #101828;
            display: flex;
            gap: 24px;
            justify-content: space-between;
            padding-bottom: 20px;
        }
        .photo {
            align-items: center;
            border: 1px solid #d0d5dd;
            border-radius: 18px;
            display: flex;
            height: 150px;
            justify-content: center;
            overflow: hidden;
            width: 150px;
        }
        .photo img {
            height: 100%;
            object-fit: cover;
            width: 100%;
        }
        .section-title {
            border-bottom: 1px solid #eaecf0;
            color: #344054;
            font-size: .78rem;
            font-weight: 800;
            letter-spacing: .08em;
            margin: 26px 0 12px;
            padding-bottom: 6px;
            text-transform: uppercase;
        }
        .field {
            margin-bottom: 10px;
        }
        .label {
            color: #667085;
            display: block;
            font-size: .76rem;
            font-weight: 700;
            text-transform: uppercase;
        }
        .value {
            font-size: .98rem;
            font-weight: 600;
        }
        .print-actions {
            margin: 18px auto 0;
            max-width: 980px;
        }
        @media print {
            body {
                background: #fff;
            }
            .print-actions {
                display: none !important;
            }
            .sheet {
                box-shadow: none;
                margin: 0;
                max-width: none;
                padding: 0;
            }
            a {
                color: inherit;
                text-decoration: none;
            }
        }
    </style>
</head>
<body>
    <div class="print-actions d-flex justify-content-between">
        <a href="personas-ficha.php?personal_id=<?php echo $personalId; ?>" class="btn btn-outline-secondary">Volver</a>
        <button type="button" class="btn btn-primary" onclick="window.print()">Imprimir / Guardar PDF</button>
    </div>

    <main class="sheet">
        <section class="hero">
            <div>
                <div class="text-muted text-uppercase fw-bold">Ficha técnica de empleado</div>
                <h1 class="mb-1"><?php echo app_h($nombreCompleto); ?></h1>
                <h4 class="mb-2">No. empleado <?php echo app_h($numeroEmpleado); ?></h4>
                <div class="text-muted">
                    <?php echo app_h($personal['puesto_operativo'] ?: 'Sin puesto operativo'); ?>
                    · <?php echo app_h($personal['estado']); ?>
                </div>
            </div>
            <div class="photo">
                <?php if (!empty($personal['url_foto_base'])): ?>
                    <img src="<?php echo app_h($personal['url_foto_base']); ?>" alt="Foto base">
                <?php else: ?>
                    <span class="text-muted">Sin foto</span>
                <?php endif; ?>
            </div>
        </section>

        <div class="section-title">Datos generales</div>
        <div class="row">
            <div class="col-md-4 field"><span class="label">Usuario</span><span class="value"><?php echo app_h($personal['usuario']); ?></span></div>
            <div class="col-md-4 field"><span class="label">Rol</span><span class="value"><?php echo app_h($personal['rol_nombre']); ?></span></div>
            <div class="col-md-4 field"><span class="label">Ingreso</span><span class="value"><?php echo app_date($personal['fecha_contratacion']); ?></span></div>
            <div class="col-md-4 field"><span class="label">Teléfono</span><span class="value"><?php echo app_h($personal['telefono']); ?></span></div>
            <div class="col-md-4 field"><span class="label">Correo</span><span class="value"><?php echo app_h($personal['email']); ?></span></div>
            <div class="col-md-4 field"><span class="label">Fecha nacimiento</span><span class="value"><?php echo app_date($personal['fecha_nacimiento']); ?></span></div>
        </div>

        <div class="section-title">Laboral y certificaciones</div>
        <div class="row">
            <div class="col-md-4 field"><span class="label">Servicio asignado</span><span class="value"><?php echo app_h($personal['servicio_asignado']); ?></span></div>
            <div class="col-md-4 field"><span class="label">Turno base</span><span class="value"><?php echo app_h($personal['turno_base']); ?></span></div>
            <div class="col-md-4 field"><span class="label">Tipo contrato</span><span class="value"><?php echo app_h($personal['tipo_contrato']); ?></span></div>
            <div class="col-md-4 field"><span class="label">INFOSPE</span><span class="value"><?php echo app_h($personal['infospe_estatus']); ?></span></div>
            <div class="col-md-4 field"><span class="label">CECCEG</span><span class="value"><?php echo app_h($personal['cecceg_estatus']); ?></span></div>
            <div class="col-md-4 field"><span class="label">Activo nómina</span><span class="value"><?php echo (int)$personal['activo_en_nomina'] === 1 ? 'Sí' : 'No'; ?></span></div>
        </div>

        <div class="section-title">Identificación y domicilio</div>
        <div class="row">
            <div class="col-md-4 field"><span class="label">NSS</span><span class="value"><?php echo app_h($personal['nss']); ?></span></div>
            <div class="col-md-4 field"><span class="label">RFC</span><span class="value"><?php echo app_h($personal['rfc']); ?></span></div>
            <div class="col-md-4 field"><span class="label">CURP</span><span class="value"><?php echo app_h($personal['curp']); ?></span></div>
            <div class="col-md-8 field"><span class="label">Domicilio</span><span class="value"><?php echo app_h($personal['domicilio']); ?></span></div>
            <div class="col-md-4 field"><span class="label">Código postal</span><span class="value"><?php echo app_h($personal['codigo_postal']); ?></span></div>
        </div>

        <div class="section-title">Banco, uniforme y emergencia</div>
        <div class="row">
            <div class="col-md-4 field"><span class="label">Banco</span><span class="value"><?php echo app_h($personal['banco']); ?></span></div>
            <div class="col-md-4 field"><span class="label">Cuenta</span><span class="value"><?php echo app_h($personal['cuenta_bancaria']); ?></span></div>
            <div class="col-md-4 field"><span class="label">Uniforme</span><span class="value">Camisa <?php echo app_h($personal['talla_camisa']); ?> · Pantalón <?php echo app_h($personal['talla_pantalon']); ?> · Calzado <?php echo app_h($personal['talla_calzado']); ?></span></div>
            <div class="col-md-4 field"><span class="label">Emergencia</span><span class="value"><?php echo app_h($personal['contacto_emergencia']); ?></span></div>
            <div class="col-md-4 field"><span class="label">Parentesco</span><span class="value"><?php echo app_h($personal['contacto_emergencia_parentesco']); ?></span></div>
            <div class="col-md-4 field"><span class="label">Teléfono emergencia</span><span class="value"><?php echo app_h($personal['contacto_emergencia_telefono']); ?></span></div>
        </div>

        <div class="section-title">Nómina y saldos</div>
        <div class="row">
            <div class="col-md-3 field"><span class="label">Salario diario</span><span class="value"><?php echo app_money(app_salary_diario($personal, $configs)); ?></span></div>
            <div class="col-md-3 field"><span class="label">Salario hora</span><span class="value"><?php echo app_money($personal['salario_hora']); ?></span></div>
            <div class="col-md-3 field"><span class="label">Saldo caja</span><span class="value"><?php echo app_money($saldoCaja); ?></span></div>
            <div class="col-md-3 field"><span class="label">Vacaciones disponibles</span><span class="value"><?php echo app_number($vacaciones['available']); ?> días</span></div>
        </div>
    </main>
</body>
</html>
