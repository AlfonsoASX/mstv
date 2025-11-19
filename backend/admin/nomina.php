<?php
/**
 * ===============================================
 * PANEL ADMIN → NÓMINA
 * Archivo: backend/admin/nomina.php
 * ===============================================
 */

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/helpers.php';

$db = Database::getInstance()->getConnection();

// Parámetros recibidos por POST para cálculo
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $desde = $_POST['desde'];
    $hasta = $_POST['hasta'];

    $nomina = Helpers::apiJson("../api/nomina/calcular.php?desde=$desde&hasta=$hasta");
}
?>

<div class="container">

    <h3 class="mb-4">Cálculo de Nómina</h3>

    <!-- Selección de fechas -->
    <div class="card shadow-sm mb-4 p-3">
        <form method="POST" class="row g-3">

            <div class="col-md-4">
                <label>Desde</label>
                <input type="date" name="desde" class="form-control" required>
            </div>

            <div class="col-md-4">
                <label>Hasta</label>
                <input type="date" name="hasta" class="form-control" required>
            </div>

            <div class="col-md-4 d-flex align-items-end">
                <button class="btn btn-success w-100">Calcular Nómina</button>
            </div>

        </form>
    </div>

    <?php if (!empty($nomina) && $nomina['status']=='success'): ?>
        
        <!-- Resultados de nómina -->
        <div class="card shadow-sm mb-3">
            <div class="card-header bg-secondary text-white">Resumen de Nómina</div>
            <div class="table-responsive">

                <table class="table table-bordered table-sm mb-0">
                    <thead>
                        <tr>
                            <th>Guardia</th>
                            <th>Horas Ordinarias</th>
                            <th>Horas Extra</th>
                            <th>Deducciones</th>
                            <th>Total a pagar</th>
                            <th>Detalles</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($nomina['data'] as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['guardia']) ?></td>
                            <td><?= $row['horas_ordinarias'] ?></td>
                            <td><?= $row['horas_extra'] ?></td>
                            <td>$<?= number_format($row['descuentos'],2) ?></td>
                            <td><strong>$<?= number_format($row['total'],2) ?></strong></td>
                            <td>
                                <a href="nomina_detalle.php?id=<?= $row['guardia_id'] ?>&desde=<?= $desde ?>&hasta=<?= $hasta ?>" 
                                   class="btn btn-outline-primary btn-sm">
                                   Ver detalle
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>

            </div>
        </div>

        <div class="text-end mb-5">
            <a href="export_nomina_excel.php?desde=<?= $desde ?>&hasta=<?= $hasta ?>" 
               class="btn btn-outline-success btn-sm">Exportar Excel</a>

            <a href="export_nomina_pdf.php?desde=<?= $desde ?>&hasta=<?= $hasta ?>" 
               class="btn btn-outline-danger btn-sm">Exportar PDF</a>
        </div>

    <?php elseif ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
        <div class="alert alert-warning">No se encontraron registros para ese periodo.</div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
