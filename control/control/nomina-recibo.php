<?php

require_once __DIR__ . '/lib/app.php';
require_once __DIR__ . '/lib/payroll.php';

app_require_session();
app_require_roles(['ADMIN', 'RH', 'NOMINA', 'DUEÑO']);

$periodoId = (int)app_get('periodo_id', 0);
$personalId = (int)app_get('personal_id', 0);

$periodo = $periodoId > 0 ? app_get_period($conexion, $periodoId) : null;
$recibo = null;

if ($periodo && $personalId > 0) {
    $recibo = app_db_one(
        $conexion,
        "SELECT
            nr.*,
            p.numero_empleado,
            p.nombres,
            p.apellidos,
            p.telefono,
            p.fecha_contratacion,
            u.usuario,
            u.email
         FROM nomina_resumen nr
         INNER JOIN personal p ON p.id = nr.personal_id
         INNER JOIN usuarios u ON u.id = p.usuario_id
         WHERE nr.periodo_id = " . $periodoId . "
           AND nr.personal_id = " . $personalId . "
         LIMIT 1"
    );
}

$conceptos = $recibo
    ? app_db_all(
        $conexion,
        "SELECT *
         FROM nomina_conceptos
         WHERE periodo_id = " . $periodoId . "
           AND personal_id = " . $personalId . "
           AND monto <> 0
         ORDER BY FIELD(categoria, 'PERCEPCION', 'DEDUCCION', 'INFORMATIVO'), id ASC"
    )
    : [];

function recibo_numero_empleado(array $recibo): string
{
    return app_employee_number($recibo);
}

function recibo_percepciones(array $row): float
{
    return (float)$row['salario_base']
        + (float)$row['pago_horas_extra']
        + (float)$row['turnos_extra_monto']
        + (float)$row['vacaciones_monto']
        + (float)$row['prima_vacacional_monto']
        + (float)$row['dias_festivos_monto']
        + (float)$row['incapacidades_monto']
        + (float)$row['bonos_monto']
        + (float)$row['finiquito_monto'];
}

function recibo_deducciones(array $row): float
{
    return (float)$row['descuento_retardos']
        + (float)$row['descuentos_faltas']
        + (float)($row['descuentos_descansos'] ?? 0)
        + (float)$row['descuentos_sanciones']
        + (float)$row['descuentos_material']
        + (float)$row['descuentos_infonavit']
        + (float)$row['descuentos_fonacot']
        + (float)$row['descuentos_prestamos']
        + (float)$row['descuentos_adelantos']
        + (float)$row['otros_descuentos'];
}

$percepciones = $recibo ? recibo_percepciones($recibo) : 0.0;
$deducciones = $recibo ? recibo_deducciones($recibo) : 0.0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Recibo de nómina</title>
    <link href="../src/bootstrap/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
    <style>
        body {
            background: #eef2f7;
            color: #111827;
            font-family: "Nunito", Arial, sans-serif;
            padding: 28px;
        }
        .receipt {
            background: #fff;
            border: 1px solid #d9e2ec;
            border-radius: 18px;
            margin: 0 auto;
            max-width: 920px;
            padding: 34px;
            box-shadow: 0 18px 45px rgba(15, 23, 42, 0.08);
        }
        .brand {
            color: #0f766e;
            font-size: 0.85rem;
            font-weight: 800;
            letter-spacing: 0.12em;
            text-transform: uppercase;
        }
        .muted-label {
            color: #64748b;
            font-size: 0.78rem;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }
        .amount-box {
            border-radius: 14px;
            padding: 16px;
            background: #f8fafc;
        }
        .neto {
            background: #0f766e;
            color: #fff;
        }
        @media print {
            body {
                background: #fff;
                padding: 0;
            }
            .receipt {
                border: none;
                box-shadow: none;
                max-width: none;
                padding: 0;
            }
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body>
    <div class="receipt">
        <div class="d-flex justify-content-between align-items-start mb-4">
            <div>
                <div class="brand">MSTV Control</div>
                <h1 class="h3 mb-1">Recibo interno de nómina</h1>
                <?php if ($periodo): ?>
                    <div class="text-muted"><?php echo app_h($periodo['clave']); ?> · <?php echo app_quincena_label($periodo['fecha_inicio'], $periodo['fecha_fin']); ?></div>
                <?php endif; ?>
            </div>
            <div class="no-print d-flex gap-2">
                <button type="button" class="btn btn-primary" onclick="window.print()">Imprimir / Guardar PDF</button>
                <a href="nomina-calculo.php?periodo_id=<?php echo (int)$periodoId; ?>" class="btn btn-outline-secondary">Volver</a>
            </div>
        </div>

        <?php if (!$periodo || !$recibo): ?>
            <div class="alert alert-warning mb-0">No se encontró el recibo para el periodo y colaborador seleccionados.</div>
        <?php else: ?>
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <div class="muted-label">Colaborador</div>
                    <strong><?php echo app_h($recibo['nombres'] . ' ' . $recibo['apellidos']); ?></strong>
                    <div class="text-muted">No. empleado: <?php echo app_h(recibo_numero_empleado($recibo)); ?></div>
                    <div class="text-muted">Usuario: <?php echo app_h($recibo['usuario']); ?></div>
                </div>
                <div class="col-md-3">
                    <div class="muted-label">Fecha de pago</div>
                    <strong><?php echo app_date($periodo['fecha_pago']); ?></strong>
                </div>
                <div class="col-md-3">
                    <div class="muted-label">Estado periodo</div>
                    <strong><?php echo app_h($periodo['estado']); ?></strong>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="amount-box">
                        <div class="muted-label">Percepciones</div>
                        <h3 class="mb-0"><?php echo app_money($percepciones); ?></h3>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="amount-box">
                        <div class="muted-label">Deducciones</div>
                        <h3 class="mb-0"><?php echo app_money($deducciones); ?></h3>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="amount-box neto">
                        <div class="muted-label text-white-50">Neto a pagar</div>
                        <h3 class="mb-0"><?php echo app_money($recibo['neto']); ?></h3>
                    </div>
                </div>
            </div>

            <div class="table-responsive mb-4">
                <table class="table table-sm align-middle">
                    <thead>
                        <tr>
                            <th>Categoría</th>
                            <th>Concepto</th>
                            <th class="text-end">Cantidad</th>
                            <th class="text-end">Monto</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($conceptos as $concepto): ?>
                            <tr>
                                <td><?php echo app_h($concepto['categoria']); ?></td>
                                <td>
                                    <strong><?php echo app_h($concepto['clave']); ?></strong><br>
                                    <span class="text-muted"><?php echo app_h($concepto['descripcion']); ?></span>
                                </td>
                                <td class="text-end"><?php echo app_number($concepto['cantidad']); ?></td>
                                <td class="text-end"><?php echo app_money($concepto['monto']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$conceptos): ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted">No hay conceptos para mostrar.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <div class="border rounded-3 p-3 h-100">
                        <div class="muted-label mb-4">Firma colaborador</div>
                        <div style="height:46px;"></div>
                        <div class="border-top pt-2 text-muted">Nombre y firma</div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="border rounded-3 p-3 h-100">
                        <div class="muted-label">Observaciones</div>
                        <p class="mb-0 text-muted">Recibo interno generado desde MSTV Control. No sustituye un CFDI de nómina.</p>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
