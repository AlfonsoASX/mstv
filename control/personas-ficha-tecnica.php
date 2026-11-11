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

$empresa = app_db_one($conexion, "
    SELECT clave_configuracion, valor_configuracion
    FROM configuracion_sistema
    WHERE clave_configuracion like 'Empresa_%';
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
    <!--link href="../src/bootstrap/css/bootstrap.min.css" rel="stylesheet" -->
    <!-- style>
        :root {
            --black: #0b0b0b;
            --gray: #d9d9d9;
            --panel: #e7e7e7;
            --header-gold-1: #c9a24a;
            --header-gold-2: #8a6922;
            --header-gold-3: #f4d37b;
            --line: #1d1d1d;
            --soft: #f6f6f6;
        }

        body {
            background: #d9d9d9;
            color: #111;
            font-family: Arial, Helvetica, sans-serif;
            margin: 0;
        }

        .sheet {
            background: #ececec;
            border: 2px solid var(--line);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.12);
            margin: 24px auto;
            max-width: 1100px;
            overflow: hidden;
        }

        .top-banner {
            background: linear-gradient(90deg, #121212 0%, #2a2a2a 20%, #1d1d1d 100%);
            border-bottom: 4px solid var(--line);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            min-height: 180px;
            padding: 18px 24px 14px;
        }

        .emblem-block {
            display: flex;
            align-items: center;
            justify-content: center;
            min-width: 220px;
        }

        .emblem {
            width: 170px;
            height: 170px;
            background: linear-gradient(135deg, #e0c067 0%, #b5892f 38%, #f7db8a 100%);
            border: 6px solid #d7b966;
            border-radius: 50% 50% 50% 50% / 45% 45% 55% 55%;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            box-shadow: inset 0 0 18px rgba(0,0,0,0.18);
        }

        .emblem::before,
        .emblem::after {
            content: "";
            position: absolute;
            inset: 12px;
            border: 2px solid rgba(0,0,0,0.45);
            border-radius: 50%;
        }

        .emblem-inner {
            position: relative;
            z-index: 1;
            text-align: center;
            color: #111;
            font-weight: 800;
            line-height: 1;
        }

        .emblem-inner .brand {
            display: block;
            font-size: 2.2rem;
            letter-spacing: 1px;
        }

        .emblem-inner .brand-small {
            display: block;
            font-size: 0.68rem;
            letter-spacing: 4px;
            margin-top: 8px;
        }

        .title-wrap {
            flex: 1;
            color: #f4f4f4;
            text-align: center;
            padding: 0 12px;
        }

        .title-wrap .small-title {
            display: block;
            font-size: 0.95rem;
            letter-spacing: 4px;
            text-transform: uppercase;
            margin-bottom: 10px;
            color: #e9c96c;
        }

        .title-wrap h1 {
            margin: 0;
            font-size: clamp(2.1rem, 3vw, 4.2rem);
            line-height: 1.05;
            letter-spacing: 1px;
            text-transform: uppercase;
            font-weight: 800;
            color: #f2f2f2;
        }

        .title-wrap h1 small {
            display: block;
            font-size: 0.6em;
        }

        .photo-box {
            width: 220px;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .photo {
            width: 180px;
            height: 150px;
            border: 3px solid #d8be73;
            background: #d7d7d7;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            box-shadow: inset 0 0 10px rgba(0,0,0,0.15);
        }

        .photo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .photo .placeholder {
            font-size: 1rem;
            color: #6b6b6b;
            font-weight: 700;
        }

        .table-wrap {
            background: #f1f1f1;
            border-collapse: collapse;
            width: 100%;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            border-top: 3px solid #111;
        }

        .data-table th,
        .data-table td {
            border: 1px solid #111;
            padding: 10px 12px;
            vertical-align: middle;
            text-align: left;
        }

        .data-table th {
            background: #d8d8d8;
            font-weight: 700;
            color: #111;
            width: 26%;
        }

        .data-table td {
            background: rgba(255,255,255,0.34);
            font-weight: 600;
            font-size: 0.96rem;
        }

        .data-table .triple th {
            width: 33.333%;
            text-align: center;
        }

        .data-table .triple td {
            text-align: center;
            width: 33.333%;
            text-transform: uppercase;
        }

        .data-table .single-label {
            width: 26%;
            background: #d6d6d6;
            font-weight: 700;
            text-transform: none;
        }

        .data-table .single-value {
            width: 74%;
            text-transform: uppercase;
        }

        .data-table .section-head {
            background: #d6d6d6;
            color: #111;
            font-size: 0.9rem;
            text-align: center;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            font-weight: 800;
        }

        .print-actions {
            margin: 18px auto 0;
            max-width: 1100px;
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
                border-width: 1px;
            }
        }
    </style-->
    <style>
:root{--gold:#c8961e;--dark:#0b0b0b;--head:#d9d9d9;--line:#222;--bg:#eee;--paper:#fff;--ink:#111;box-sizing:border-box;padding-top:env(safe-area-inset-top,0px);padding-bottom:env(safe-area-inset-bottom,0px)}
@media (prefers-color-scheme:dark){:root:not([data-theme="light"]){--bg:#1a1a1a}}
*{box-sizing:border-box}
body{margin:0;background:var(--bg);font-family:Arial,Helvetica,sans-serif;color:var(--ink);padding:16px}
.ficha{max-width:760px;margin:0 auto;background:var(--paper)}
.top{background:linear-gradient(90deg,#000,#222 35%,var(--gold));color:#fff;text-align:center;padding:8px;font-size:clamp(14px,3vw,19px);letter-spacing:.5px;border-bottom:2px solid #888}
.banner{background:linear-gradient(90deg,#000 0%,#2a1c05 40%,var(--gold) 100%);display:flex;align-items:center;justify-content:space-between;padding:20px 22px;gap:12px}
.logo{width:120px;text-align:center;color:#e6c26a;font-size:10px;font-weight:bold;flex:none}
.logo svg{width:100px;height:auto}
.name{color:#fff;font-family:Georgia,serif;font-size:clamp(20px,4.6vw,34px);text-align:center;flex:1;line-height:1.2;text-transform:uppercase}
.photo{width:144px;height:144px;background:#ccc;border:2px solid #fff;flex:none;display:flex;align-items:center;justify-content:center;color:#555;font-size:12px;text-align:center;overflow:hidden;cursor:pointer;position:relative}
.photo img{width:100%;height:100%;object-fit:cover}
.wrap{overflow-x:auto;margin-top:12px}
table{width:100%;border-collapse:collapse;font-size:15px;min-width:520px}
td,th{border:1px solid var(--line);padding:7px 8px;text-align:center}
th,.lbl{background:var(--head);font-weight:normal}
.val{font-style:italic}
.val.b{font-weight:bold}
td[contenteditable]{outline:none}
td[contenteditable]:focus{background:#fff8dc}
.sec{background:var(--head)}
.left{text-align:center}
.print{display:block;margin:14px auto;padding:10px 22px;background:var(--gold);border:0;color:#000;font-weight:bold;border-radius:6px;cursor:pointer}
@media print{.print{display:none}body{background:#fff;padding:0}}
@media (max-width:560px){.banner{flex-wrap:wrap;justify-content:center}}
</style>
</head>
<body>
    <div class="ficha">
  <div class="top">FICHA TECNICA DE ELEMENTO DE SEGURIDAD</div>
  <div class="banner">
    <div class="logo">
      <svg viewBox="0 0 100 120"><path d="M50 4 94 18v40c0 30-20 48-44 58C26 106 6 88 6 58V18z" fill="#8a6414" stroke="#e6c26a" stroke-width="3"/><text x="50" y="40" text-anchor="middle" fill="#fff" font-style="italic" font-size="18" font-family="Georgia">MSTV</text><circle cx="50" cy="72" r="14" fill="none" stroke="#fff" stroke-width="3"/></svg>
      SEGURIDAD PRIVADA
    </div>
    <div class="name" id="nombre" contenteditable>
        <?php echo app_h(strtoupper(trim((string)($personal['nombres'] ?? '') . ' ' . (string)($personal['apellidos'] ?? '')))); ?></div>
    <label class="photo" title="Clic para cargar foto"><span id="ph"> <?php if (!empty($personal['url_foto_base'])): ?>
                        <img src="<?php echo app_h($personal['url_foto_base']); ?>" alt="Foto del empleado">
                    <?php else: ?>
                        <span class="placeholder">Sin foto</span>
                    <?php endif; ?><br>(clic para cargar)</span>
      <input type="file" accept="image/*" id="file" hidden>
    </label>
  </div>

  <div class="wrap">
  <table>
    <tr><td class="lbl" style="width:24%">Servicio:</td><td class="val b" colspan="3" contenteditable><?php echo app_h($personal['servicio_asignado'] ?? 'AMBERES 2'); ?></td></tr>
    <tr><td class="lbl">Empresa</td><td class="val" colspan="3" contenteditable><?php echo app_h($personal['empresa'] ?? 'Bienes y servicios MSTV SA DE CV'); ?></td></tr>
    <tr><td class="lbl"><i>Marca</i></td><td class="val" colspan="3" contenteditable>MSTV Seguridad Privada</td></tr>
    <tr><td class="lbl"><i>Giro</i></td><td class="val" colspan="3" contenteditable>Servicio de investigación, protección y custodia.</td></tr>
    <tr><td class="lbl"><i>REPSE</i></td><td class="val" colspan="3" contenteditable>AR000000</td></tr>
    <tr><td class="lbl">Domicilio institucional</td><td class="val" colspan="3" contenteditable>C. Aquiles Serdán 220, Centro C.P. 37000</td></tr>
    <tr><td class="lbl">TSP</td><td colspan="3" contenteditable>&nbsp;</td></tr>
    <tr><th colspan="1">Estado civil</th><th colspan="1">Nivel académico</th><th colspan="2">Inducción</th></tr>
    <tr>
        <td contenteditable><?php echo app_h($personal['estado_civil'] ?? 'SEPARADO'); ?></td>
        <td contenteditable><?php echo app_h($personal['nivel_academico'] ?? 'SECUNDARIA'); ?></td>
        <td colspan="2" contenteditable><?php echo app_h($personal['induccion'] ?? 'APROBADA'); ?></td>
    </tr>
    <tr><th>CURP</th><th>NSS</th><th colspan="2">RFC</th></tr>
    <tr>
        <td contenteditable><?php echo app_h($personal['curp'] ?? ''); ?></td>
        <td contenteditable><?php echo app_h($personal['nss'] ?? ''); ?></td>
        <td colspan="2" contenteditable><?php echo app_h($personal['rfc'] ?? ''); ?></td>
    </tr>
    <tr><th>Puesto</th><th>Departamento</th><th colspan="2">Base</th></tr>
    <tr>
        <td contenteditable><?php echo app_h($personal['puesto_operativo'] ?? $personal['puesto'] ?? 'TSP'); ?></td>
        <td contenteditable><?php echo app_h($personal['departamento'] ?? 'Operaciones'); ?></td>
        <td colspan="2" contenteditable><?php echo app_h($personal['base'] ?? 'León Guanajuato.'); ?></td>
    </tr>
    <tr><td class="sec" colspan="4">Detalles de servicio</td></tr>
    <tr><th>Jornada</th><th>Actividad</th><th colspan="2">Armas</th></tr>
    <tr><td contenteditable><?php echo app_h($personal['jornada'] ?? '24 Hrs'); ?></td>
        <td contenteditable><?php echo app_h($personal['actividad'] ?? 'Consignas'); ?></td>
        <td colspan="2" contenteditable><?php echo app_h($personal['armas'] ?? 'No'); ?></td>
    </tr>
    <tr><td class="sec" colspan="4">Actividades generales</td></tr>
    <tr><td colspan="4" contenteditable><?php echo app_h($personal['actividades_generales'] ?? 'Alcance como técnico en seguridad privada intramuros, en base a consignas específicas'); ?></td></tr>
  </table>
  </div>
</div>
<button class="print" onclick="window.print()">Imprimir / Guardar PDF</button>
<script>
document.getElementById('file').addEventListener('change',function(e){
  var f=e.target.files[0];if(!f)return;
  var r=new FileReader();
  r.onload=function(){var l=document.querySelector('.photo');l.querySelector('img')&&l.querySelector('img').remove();document.getElementById('ph').style.display='none';var i=document.createElement('img');i.src=r.result;l.appendChild(i)};
  r.readAsDataURL(f);
});
</script>

    <!--div class="print-actions d-flex justify-content-between">
        <a href="personas-ficha.php?personal_id=<?php echo (int)$personalId; ?>" class="btn btn-outline-secondary">Volver</a>
        <button type="button" class="btn btn-primary" onclick="window.print()">Imprimir / Guardar PDF</button>
    </div>

    <main class="sheet">
        <section class="top-banner">
            <div class="emblem-block">
                <div class="emblem">
                    <div class="emblem-inner">
                        <span class="brand">MSTV</span>
                        <span class="brand-small">SA DE CV</span>
                    </div>
                </div>
            </div>

            <div class="title-wrap">
                <span class="small-title">Ficha técnica de elemento de seguridad</span>
                <h1>
                    <?php echo app_h(strtoupper(trim((string)($personal['nombres'] ?? '') . ' ' . (string)($personal['apellidos'] ?? '')))); ?>
                    <small><?php echo app_h(strtoupper($numeroEmpleado ?: 'N/A')); ?></small>
                </h1>
            </div>

            <div class="photo-box">
                <div class="photo">
                    <?php if (!empty($personal['url_foto_base'])): ?>
                        <img src="<?php echo app_h($personal['url_foto_base']); ?>" alt="Foto del empleado">
                    <?php else: ?>
                        <span class="placeholder">Sin foto</span>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <div class="table-wrap">
            <table class="data-table">
                <tr>
                    <th class="single-label">Servicio:</th>
                    <td class="single-value"><?php echo app_h($personal['servicio_asignado'] ?? 'AMBERES 2'); ?></td>
                </tr>
                <tr>
                    <th class="single-label">Empresa</th>
                    <td class="single-value"><?php echo app_h($personal['empresa'] ?? 'Bienes y servicios MSTV SA DE CV'); ?></td>
                </tr>
                <tr>
                    <th class="single-label">Marca</th>
                    <td class="single-value"><?php echo app_h($personal['marca'] ?? 'MSTV Seguridad Privada'); ?></td>
                </tr>
                <tr>
                    <th class="single-label">Giro</th>
                    <td class="single-value"><?php echo app_h($personal['giro'] ?? 'Servicio de investigación, protección y custodia.'); ?></td>
                </tr>
                <tr>
                    <th class="single-label">REPSE</th>
                    <td class="single-value"><?php echo app_h($personal['repse'] ?? 'AR113429'); ?></td>
                </tr>
                <tr>
                    <th class="single-label">Domicilio institucional</th>
                    <td class="single-value"><?php echo app_h($personal['domicilio_institucional'] ?? 'C. Aquiles Serdán 220, Centro C.P. 37000'); ?></td>
                </tr>
                <tr>
                    <th class="single-label">TSP</th>
                    <td class="single-value"><?php echo app_h($personal['tsp'] ?? ''); ?></td>
                </tr>
                <tr class="triple">
                    <th>Estado civil</th>
                    <th>Nivel académico</th>
                    <th>Inducción</th>
                </tr>
                <tr class="triple">
                    <td><?php echo app_h($personal['estado_civil'] ?? 'SEPARADO'); ?></td>
                    <td><?php echo app_h($personal['nivel_academico'] ?? 'SECUNDARIA'); ?></td>
                    <td><?php echo app_h($personal['induccion'] ?? 'APROBADA'); ?></td>
                </tr>
                <tr class="triple">
                    <th>CURP</th>
                    <th>NSS</th>
                    <th>RFC</th>
                </tr>
                <tr class="triple">
                    <td><?php echo app_h($personal['curp'] ?? ''); ?></td>
                    <td><?php echo app_h($personal['nss'] ?? ''); ?></td>
                    <td><?php echo app_h($personal['rfc'] ?? ''); ?></td>
                </tr>
                <tr class="triple">
                    <th>Puesto</th>
                    <th>Departamento</th>
                    <th>Base</th>
                </tr>
                <tr class="triple">
                    <td><?php echo app_h($personal['puesto_operativo'] ?? $personal['puesto'] ?? 'TSP'); ?></td>
                    <td><?php echo app_h($personal['departamento'] ?? 'Operaciones'); ?></td>
                    <td><?php echo app_h($personal['base'] ?? 'León Guanajuato.'); ?></td>
                </tr>
                <tr class="triple">
                    <th>Jornada</th>
                    <th>Actividad</th>
                    <th>Armas</th>
                </tr>
                <tr class="triple">
                    <td><?php echo app_h($personal['jornada'] ?? '24 Hrs'); ?></td>
                    <td><?php echo app_h($personal['actividad'] ?? 'Consignas'); ?></td>
                    <td><?php echo app_h($personal['armas'] ?? 'No'); ?></td>
                </tr>
                <tr>
                    <td colspan="3" class="section-head">Actividades generales</td>
                </tr>
                <tr>
                    <td colspan="3"><?php echo app_h($personal['actividades_generales'] ?? 'Alcance como técnico en seguridad privada intramuros, en base a consignas específicas'); ?></td>
                </tr>
            </table>
        </div>
    </main-->

</body>
</html>
