<?php
/**
 * ===============================================
 * PANEL ADMIN → DASHBOARD EJECUTIVO
 * Archivo: backend/admin/dashboard.php
 * ===============================================
 */

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/helpers.php';

$db = Database::getInstance()->getConnection();

// Obtener KPIs desde API
$kpis = Helpers::apiJson('../../api/kpi/generales.php');
?>

<div class="container">

    <!-- 🔹 Fila de KPIs -->
    <div class="row g-3 mb-4">

        <div class="col-md-3">
            <div class="card shadow-sm text-center p-3">
                <h6>Puntualidad</h6>
                <h2><?= $kpis['puntualidad'] ?? '--' ?>%</h2>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm text-center p-3">
                <h6>Guardias Activos</h6>
                <h2><?= $kpis['guardias_activos'] ?? '--' ?></h2>
                <small class="text-muted">vs asignados <?= $kpis['guardias_asignados'] ?? '--' ?></small>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm text-center p-3">
                <h6>Incidencias Urgentes</h6>
                <h2><?= $kpis['incidencias_urgentes'] ?? '--' ?></h2>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm text-center p-3">
                <h6>Horas Extra (Mes)</h6>
                <h2><?= $kpis['horas_extras_totales'] ?? '--' ?></h2>
            </div>
        </div>
    </div>


    <!-- 🔸 Gráfica (simulada, lista para API) -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-primary text-white">Tendencia de Puntualidad (Últimos 7 días)</div>
        <div class="card-body">
            <canvas id="graficaPuntualidad"></canvas>
        </div>
    </div>


    <!-- 🔸 Tabla de incidencias recientes -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-danger text-white">Incidencias Recientes</div>
        <div class="card-body" id="tablaIncidencias">
            Cargando incidencias...
        </div>
    </div>


    <!-- 🔸 Próximos vencimientos -->
    <div class="card shadow-sm mb-5">
        <div class="card-header bg-warning">Documentos Próximos a Vencer</div>
        <div class="card-body" id="tablaVencimientos">
            Cargando...
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
// 🟢 Gráfica (ejemplo inicial)
const ctx = document.getElementById('graficaPuntualidad').getContext('2d');
new Chart(ctx, {
    type: 'line',
    data: {
        labels: ['Lun','Mar','Mié','Jue','Vie','Sáb','Dom'],
        datasets: [{
            label: 'Puntualidad (%)',
            data: [92, 88, 90, 95, 93, 91, 94],
            borderWidth: 2
        }]
    }
});

// 📂 Cargar Incidencias recientes
fetch('../api/supervisor/incidencias_pendientes.php')
.then(r => r.json())
.then(data => {
    if (data.status !== 'success' || !data.data.length) {
        document.getElementById('tablaIncidencias').innerHTML = `<em>No hay incidencias activas.</em>`;
        return;
    }

    let html = `<table class="table table-sm">
        <thead><tr>
            <th>Guardia</th><th>Tipo</th><th>Prioridad</th><th>Fecha</th>
        </tr></thead><tbody>`;

    data.data.forEach(i => {
        html += `<tr>
            <td>${i.guardia_nombre}</td>
            <td>${i.tipo}</td>
            <td><span class="badge bg-${i.prioridad=='alta'?'danger':'secondary'}">${i.prioridad}</span></td>
            <td>${i.creado_en}</td>
        </tr>`;
    });
    html += `</tbody></table>`;
    document.getElementById('tablaIncidencias').innerHTML = html;
});

// 📂 Cargar documentos próximos a vencer
fetch('../../api/documentos/vencimientos.php')
.then(r => r.json())
.then(data => {
    if (data.status !== 'success' || !data.data.length) {
        document.getElementById('tablaVencimientos').innerHTML = `<em>No se detectaron documentos próximos a vencerse.</em>`;
        return;
    }

    let html = `<table class="table table-sm">
        <thead><tr>
            <th>Guardia</th><th>Documento</th><th>Vence</th><th>Días restantes</th>
        </tr></thead><tbody>`;

    data.data.forEach(d => {
        html += `<tr>
            <td>${d.guardia}</td>
            <td>${d.tipo}</td>
            <td>${d.fecha_vencimiento}</td>
            <td>${d.dias_restantes}</td>
        </tr>`;
    });
    html += `</tbody></table>`;
    document.getElementById('tablaVencimientos').innerHTML = html;
});
</script>
