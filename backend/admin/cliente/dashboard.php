<?php
/**
 * ===========================================
 * PORTAL CLIENTE → DASHBOARD
 * Vista tipo web (PHP + Bootstrap)
 * Proyecto: Seguridad Privada - ASX
 * Archivo: backend/admin/cliente/dashboard.php
 * ===========================================
 */

// Seguridad básica (cliente logueado, token válido)
require_once __DIR__ . '/../../core/middleware.php';
require_once __DIR__ . '/../../core/helpers.php';
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/database.php';

$user = Middleware::secure([ ROLES['CLIENTE'] ]); // solo rol cliente

$db = Database::getInstance()->getConnection();

// Obtener información del cliente y sus sitios
$sql = "
    SELECT s.id, s.nombre, s.ubicacion
    FROM sitios s
    WHERE s.cliente_id = :cliente_id
";
$stmt = $db->prepare($sql);
$stmt->execute(['cliente_id' => $user['id']]);
$sitios = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel Cliente - Seguridad Privada</title>
    <link rel="stylesheet" 
          href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">

<div class="container py-4">

    <!-- Encabezado -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Panel Cliente</h2>
        <span class="text-muted">Bienvenido, <?= htmlspecialchars($user['nombre']) ?></span>
    </div>

    <!-- KPIs -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card text-center p-3 shadow-sm">
                <h6>Guardias activos</h6>
                <h3 id="kpi_activos">--</h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center p-3 shadow-sm">
                <h6>Puntualidad</h6>
                <h3 id="kpi_puntualidad">--%</h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center p-3 shadow-sm">
                <h6>Incidencias activas</h6>
                <h3 id="kpi_incidencias">--</h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center p-3 shadow-sm">
                <h6>Horas Extra (mes)</h6>
                <h3 id="kpi_extras">--</h3>
            </div>
        </div>
    </div>


    <!-- Selección de sitio -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <h5>Mis Sitios</h5>
            <select id="sitio_select" class="form-select">
                <?php foreach ($sitios as $s): ?>
                    <option value="<?= $s['id'] ?>">
                        <?= htmlspecialchars($s['nombre']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>


    <!-- Últimas Checadas -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-primary text-white">Últimas Checadas</div>
        <div class="card-body" id="tablaChecadas">
            Cargando...
        </div>
    </div>


    <!-- Incidencias -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-danger text-white">Incidencias Recientes</div>
        <div class="card-body" id="tablaIncidencias">
            Cargando...
        </div>
    </div>
</div>


<script>
    async function cargarKPIs() {
        const resp = await fetch('../../api/kpi/generales.php', {
            headers: { 'Authorization': 'Bearer <?= $_SERVER['HTTP_AUTHORIZATION'] ?? '' ?>' }
        });
        const data = await resp.json();
        if (data.status === 'success') {
            document.getElementById('kpi_activos').innerText = data.data.guardias_activos || '--';
            document.getElementById('kpi_puntualidad').innerText = (data.data.puntualidad || '--') + '%';
            document.getElementById('kpi_incidencias').innerText = data.data.incidencias_urgentes || '--';
            document.getElementById('kpi_extras').innerText = data.data.horas_extras_totales || '--';
        }
    }

    async function cargarChecadas(sitio_id) {
        const resp = await fetch(`../../api/checadas/listar.php?sitio_id=${sitio_id}`);
        const data = await resp.json();
        if (data.status === 'success' && data.data.length) {
            let html = `<table class="table table-sm">
                <thead><tr>
                    <th>Guardia</th><th>Tipo</th><th>Hora</th><th>Foto</th>
                </tr></thead><tbody>`;
            data.data.forEach(r => {
                html += `<tr>
                    <td>${r.guardia_nombre}</td>
                    <td>${r.tipo}</td>
                    <td>${r.fecha_hora}</td>
                    <td>${r.foto ? '<img src="/fotos/' + r.foto + '" width="60">' : '-'}</td>
                </tr>`;
            });
            html += `</tbody></table>`;
            document.getElementById('tablaChecadas').innerHTML = html;
        } else {
            document.getElementById('tablaChecadas').innerHTML = `No hay registros.`;
        }
    }

    async function cargarIncidencias(sitio_id) {
        const resp = await fetch(`../../api/supervisor/incidencias_pendientes.php?sitio_id=${sitio_id}`);
        const data = await resp.json();
        if (data.status === 'success' && data.data.length) {
            let html = `<table class="table table-sm">
                <thead><tr>
                    <th>Guardia</th><th>Tipo</th><th>Prioridad</th><th>Fecha</th><th>Foto</th>
                </tr></thead><tbody>`;
            data.data.forEach(i => {
                html += `<tr>
                    <td>${i.guardia_nombre}</td>
                    <td>${i.tipo}</td>
                    <td><span class="badge bg-${i.prioridad === 'alta' ? 'danger' : 'secondary'}">
                        ${i.prioridad}
                    </span></td>
                    <td>${i.creado_en}</td>
                    <td>${i.foto ? '<img src="/fotos/' + i.foto + '" width="60">' : '-'}</td>
                </tr>`;
            });
            html += `</tbody></table>`;
            document.getElementById('tablaIncidencias').innerHTML = html;
        } else {
            document.getElementById('tablaIncidencias').innerHTML = `No hay incidencias activas.`;
        }
    }

    // Eventos
    document.getElementById('sitio_select').addEventListener('change', function () {
        cargarChecadas(this.value);
        cargarIncidencias(this.value);
    });

    // Inicial
    cargarKPIs();
    cargarChecadas(document.getElementById('sitio_select').value);
    cargarIncidencias(document.getElementById('sitio_select').value);
</script>

</body>
</html>
