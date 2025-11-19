<?php
/**
 * ===============================================
 * PANEL ADMIN → REPORTES Y EXPORTACIONES
 * Archivo: backend/admin/reportes.php
 * ===============================================
 */

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/helpers.php';

$db = Database::getInstance()->getConnection();

// Obtener datos para filtros
$guardias = $db->query("SELECT id, CONCAT(nombre,' ',apellido) AS nombre FROM usuarios WHERE rol='guardia'")->fetchAll();
$sitios   = $db->query("SELECT id, nombre FROM sitios")->fetchAll();
?>

<div class="container">

    <h3 class="mb-4">Reportes y Exportaciones</h3>

    <!-- 🔎 Buscador de reportes -->
    <div class="card p-4 shadow-sm mb-4">
        <form method="GET" class="row g-3">

            <div class="col-md-4">
                <label>Tipo de Reporte</label>
                <select name="tipo" class="form-select" required>
                    <option value="">Seleccione...</option>
                    <optgroup label="Asistencia / Checadas">
                        <option value="puntualidad">Puntualidad General</option>
                        <option value="ausencias">Ausencias y Retardos</option>
                        <option value="validaciones">Validaciones GPS / Facial</option>
                    </optgroup>
                    <optgroup label="Incidencias">
                        <option value="incidencias_por_sitio">Incidencias por Sitio</option>
                        <option value="incidencias_urgentes">Incidencias Urgentes</option>
                    </optgroup>
                    <optgroup label="Nómina">
                        <option value="horas_extra">Horas Extra y Costos</option>
                        <option value="nomina_general">Resumen Nómina Global</option>
                    </optgroup>
                    <optgroup label="Capacitación">
                        <option value="onboarding">Cumplimiento de Capacitación</option>
                    </optgroup>
                </select>
            </div>

            <div class="col-md-3">
                <label>Desde</label>
                <input type="date" name="desde" value="<?= $_GET['desde'] ?? '' ?>" class="form-control">
            </div>

            <div class="col-md-3">
                <label>Hasta</label>
                <input type="date" name="hasta" value="<?= $_GET['hasta'] ?? '' ?>" class="form-control">
            </div>

            <div class="col-md-2 d-flex align-items-end">
                <button class="btn btn-primary w-100">Ver Reporte</button>
            </div>

        </form>
    </div>


    <?php 
    // Si se seleccionó un reporte, traer desde API correspondiente:
    if (!empty($_GET['tipo'])) {

        $tipo  = $_GET['tipo'];
        $desde = $_GET['desde'] ?? '';
        $hasta = $_GET['hasta'] ?? '';

        $url = "../api/kpi/generales.php";
        switch($tipo) {
            case 'puntualidad':
                $url = "../api/kpi/puntualidad.php?desde=$desde&hasta=$hasta";
                $titulo = "Reporte de Puntualidad";
            break;
            case 'ausencias':
                $url = "../api/kpi/ausencias.php?desde=$desde&hasta=$hasta";
                $titulo = "Ausencias y Retardos";
            break;
            case 'validaciones':
                $url = "../api/kpi/validaciones.php?desde=$desde&hasta=$hasta";
                $titulo = "Validaciones GPS/FACIAL";
            break;
            case 'incidencias_por_sitio':
                $url = "../api/incidencias/listar.php?tipo=sitio&desde=$desde&hasta=$hasta";
                $titulo = "Incidencias por Sitio";
            break;
            case 'incidencias_urgentes':
                $url = "../api/incidencias/listar.php?urgentes=1&desde=$desde&hasta=$hasta";
                $titulo = "Incidencias Urgentes";
            break;
            case 'horas_extra':
                $url = "../api/nomina/horas_extra.php?desde=$desde&hasta=$hasta";
                $titulo = "Horas Extra y Costos";
            break;
            case 'nomina_general':
                $url = "../api/nomina/calcular.php?desde=$desde&hasta=$hasta";
                $titulo = "Resumen Global de Nómina";
            break;
            case 'onboarding':
                $url = "../api/capacitacion/completados.php?desde=$desde&hasta=$hasta";
                $titulo = "Capacitación y Onboarding";
            break;
        }

        $data = Helpers::apiJson($url);
        ?>

        <div class="card shadow-sm mb-5">
            <div class="card-header bg-secondary text-white">
                <?= $titulo ?>
                <span class="float-end small text-white-50">
                    <?= $desde ?: 'Inicio' ?> → <?= $hasta ?: 'Hoy' ?>
                </span>
            </div>
            <div class="card-body">

            <?php if ($data['status'] != 'success' || empty($data['data'])): ?>
                <div class="alert alert-warning">No se encontraron registros para este reporte.</div>
            <?php else: ?>

                <!-- Mostrar tabla genérica según datos obtenidos -->
                <div class="table-responsive">
                    <table class="table table-striped table-sm">
                        <thead>
                           <tr>
                               <?php foreach (array_keys($data['data'][0]) as $col): ?>
                                   <th><?= ucfirst($col) ?></th>
                               <?php endforeach; ?>
                           </tr>
                        </thead>
                        <tbody>
                           <?php foreach ($data['data'] as $row): ?>
                               <tr>
                                   <?php foreach ($row as $value): ?>
                                       <td><?= htmlspecialchars($value) ?></td>
                                   <?php endforeach; ?>
                               </tr>
                           <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

            <?php endif; ?>

            </div>

            <!-- 🔽 Descargas -->
            <?php if ($data['status']=='success' && !empty($data['data'])): ?>
                <div class="card-footer text-end">
                    <a href="export_excel.php?tipo=<?= $tipo ?>&desde=<?= $desde ?>&hasta=<?= $hasta ?>" 
                       class="btn btn-success btn-sm">Exportar Excel</a>

                    <a href="export_pdf.php?tipo=<?= $tipo ?>&desde=<?= $desde ?>&hasta=<?= $hasta ?>" 
                       class="btn btn-danger btn-sm">Descargar PDF</a>
                </div>
            <?php endif; ?>

        </div>
    <?php } ?>

</div>


<?php include __DIR__ . '/includes/footer.php'; ?>
