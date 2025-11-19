<?php
/**
 * ===========================================
 * PORTAL CLIENTE → REPORTE DEL SERVICIO
 * Proyecto: Seguridad Privada - ASX
 * Archivo: backend/admin/cliente/reporte_servicio.php
 * ===========================================
 */

session_start();
require_once __DIR__ . '/../../core/middleware.php';
require_once __DIR__ . '/../../core/helpers.php';
require_once __DIR__ . '/../../config/database.php';

$user = Middleware::secure([ ROLES['CLIENTE'] ]);
$db   = Database::getInstance()->getConnection();

// 1️⃣ Obtener sitios del cliente
$sql = "
    SELECT id, nombre, ubicacion
    FROM sitios
    WHERE cliente_id = :cliente_id
";
$stmt = $db->prepare($sql);
$stmt->execute(['cliente_id' => $user['id']]);
$sitios = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Reporte del Servicio - Cliente</title>
<link rel="stylesheet" 
      href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<style>
    .titulo { font-weight: 600; font-size: 1.3rem; }
    .card-kpi { text-align:center; padding:1rem; }
    .kpi-num { font-size:1.6rem; font-weight:bold; }
</style>
</head>
<body class="bg-light">

<div class="container py-4">

    <h2 class="mb-4">Reporte del Servicio</h2>

    <!-- Selección de sitio y fechas -->
    <div class="card p-3 mb-4 shadow-sm">
        <div class="row">
            <div class="col-md-4">
                <label>Sitio</label>
                <select id="sitio_id" class="form-select">
                    <?php foreach ($sitios as $s): ?>
                        <option value="<?= $s['id'] ?>">
                            <?= htmlspecialchars($s['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label>Desde</label>
                <input type="date" class="form-control" id="desde">
            </div>
            <div class="col-md-3">
                <label>Hasta</label>
                <input type="date" class="form-control" id="hasta">
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button class="btn btn-primary w-100" onclick="generarReporte()">
                    Generar
                </button>
            </div>
        </div>
    </div>

    <!-- KPIs generales -->
    <div class="row g-3 mb-4" id="kpi_container" style="display:none;">
        <div class="col-md-3">
            <div class="card card-kpi shadow-sm">
                <div>Puntualidad</div>
                <div class="kpi-num" id="kpi_puntualidad">--%</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-kpi shadow-sm">
                <div>Incidencias</div>
                <div class="kpi-num" id="kpi_incidencias">--</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-kpi shadow-sm">
                <div>Horas Extra</div>
                <div class="kpi-num" id="kpi_extras">--</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-kpi shadow-sm">
                <div>Guardias Activos</div>
                <div class="kpi-num" id="kpi_guardias">--</div>
            </div>
        </div>
    </div>

    <!-- Checadas -->
    <div class="card shadow-sm mb-4" id="checada_block" style="display:none;">
        <div class="card-header bg-primary text-white">Checadas</div>
        <div class="card-body" id="tablaChecadas">Cargando...</div>
    </div>

    <!-- Incidencias -->
    <div class="card shadow-sm mb-4" id="incidencia_block" style="display:none;">
        <div class="card-header bg-danger text-white">Incidencias</div>
        <div class="card-body" id="tablaIncidencias">Cargando...</div>
    </div>

    <!-- Horas Extra -->
    <div class="card shadow-sm mb-4" id="extra_block" style="display:none;">
        <div class="card-header bg-warning">Turnos Extra</div>
        <div class="card-body" id="tablaExtras">Cargando...</div>
    </div>

    <!-- Exportar -->
    <div id="export_block" class="text-center mb-5" style="display:none;">
        <button class="btn btn-success me-3" onclick="exportar('excel')">Exportar Excel</button>
        <button class="btn btn-secondary" onclick="exportar('pdf')">Exportar PDF</button>
    </div>
</div>


<script>
async function generarReporte() {

    const sitio_id = document.getElementById('sitio_id').value;
    const desde    = document.getElementById('desde').value;
    const hasta    = document.getElementById('hasta').value;

    if (!desde || !hasta) {
        alert("Debe seleccionar el rango de fechas.");
        return;
    }

    // Mostrar secciones
    document.getElementById('kpi_container').style.display = 'flex';
    document.getElementById('checada_block').style.display = 'block';
    document.getElementById('incidencia_block').style.display = 'block';
    document.getElementById('extra_block').style.display = 'block';
    document.getElementById('export_block').style.display = 'block';

    // 1️⃣ KPIs
    const respKpi = await fetch(`../../api/kpi/generales.php`);
    const dataKpi = await respKpi.json();
    if (dataKpi.status === 'success') {
        document.getElementById('kpi_puntualidad').innerText = dataKpi.data.puntualidad + '%';
        document.getElementById('kpi_incidencias').innerText = dataKpi.data.incidencias_urgentes;
        document.getElementById('kpi_extras').innerText = dataKpi.data.horas_extras_totales;
        document.getElementById('kpi_guardias').innerText = dataKpi.data.guardias_activos;
    }

    // 2️⃣ Checadas
    const respChec = await fetch(`../../api/checadas/listar.php?sitio_id=${sitio_id}&desde=${desde}&hasta=${hasta}`);
    const dataChec = await respChec.json();

    if (dataChec.status === 'success' && dataChec.data.length) {
        let html = `<table class="table table-sm">
            <thead><tr>
                <th>Guardia</th><th>Tipo</th><th>Fecha / Hora</th><th>Foto</th>
            </tr></thead><tbody>`;
        dataChec.data.forEach(r => {
            html += `<tr>
                <td>${r.guardia_nombre}</td>
                <td>${r.tipo}</td>
                <td>${r.fecha_hora}</td>
                <td>${r.foto ? '<img src="/fotos/' + r.foto + '" width="50">' : '-'}</td>
            </tr>`;
        });
        html += `</tbody></table>`;
        document.getElementById('tablaChecadas').innerHTML = html;
    } else {
        document.getElementById('tablaChecadas').innerHTML = `Sin registros.`;
    }

    // 3️⃣ Incidencias
    const respInc = await fetch(`../../api/supervisor/incidencias_pendientes.php?sitio_id=${sitio_id}`);
    const dataInc = await respInc.json();

    let htmlInc = `<table class="table table-sm"><thead><tr>
            <th>Guardia</th><th>Tipo</th><th>Prioridad</th><th>Fecha</th><th>Foto</th></tr></thead><tbody>`;
    if (dataInc.status === 'success' && dataInc.data.length) {
        dataInc.data.forEach(i => {
            htmlInc += `<tr>
                <td>${i.guardia_nombre}</td>
                <td>${i.tipo}</td>
                <td><span class="badge bg-${i.prioridad === 'alta' ? 'danger' : 'secondary'}">
                    ${i.prioridad}</span>
                </td>
                <td>${i.creado_en}</td>
                <td>${i.foto ? '<img src="/fotos/' + i.foto + '" width="50">' : '-'}</td>
            </tr>`;
        });
    } else {
        htmlInc += `<tr><td colspan="5">Sin incidencias.</td></tr>`;
    }
    htmlInc += `</tbody></table>`;
    document.getElementById('tablaIncidencias').innerHTML = htmlInc;


    // 4️⃣ Horas Extra
    const respExt = await fetch(`../../api/turnos/extra_historial.php?sitio_id=${sitio_id}&desde=${desde}&hasta=${hasta}`);
    const dataExt = await respExt.json();

    let htmlExt = `<table class="table table-sm">
        <thead><tr><th>Guardia</th><th>Inicio</th><th>Fin</th><th>Horas</th></tr></thead><tbody>`;

    if (dataExt.status === 'success' && dataExt.data.length) {
        dataExt.data.forEach(e => {
            htmlExt += `<tr>
                <td>${e.guardia_nombre}</td>
                <td>${e.inicio}</td>
                <td>${e.fin}</td>
                <td>${e.horas_extra}h</td>
            </tr>`;
        });
    } else {
        htmlExt += `<tr><td colspan="4">No hay turnos extra.</td></tr>`;
    }
    htmlExt += `</tbody></table>`;
    document.getElementById('tablaExtras').innerHTML = htmlExt;
}


// ⬇️ Exportar (solo muestra idea; conectar con API real)
function exportar(tipo) {
    alert(`Exportar en formato: ${tipo.toUpperCase()} (En construcción)`);
}
</script>

</body>
</html>
