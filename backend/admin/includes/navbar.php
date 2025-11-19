<?php
/**
 * ===============================================
 * NAVBAR DINÁMICO SEGÚN ROL
 * Archivo: backend/admin/includes/navbar.php
 * ===============================================
 */
require_once __DIR__ . '/auth_guard.php'; // Garantiza token y rol

$rol = strtolower($AUTH_USER['rol'] ?? '');
?>

<!-- Navbar adicional (debajo del header) -->
<nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom shadow-sm mb-4">
  <div class="container-fluid">

    <button class="navbar-toggler" type="button"
            data-bs-toggle="collapse" data-bs-target="#navbarContent"
            aria-controls="navbarContent" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarContent">

      <ul class="navbar-nav me-auto mb-2 mb-lg-0">

        <!-- 🔹 Cliente -->
        <?php if ($rol === 'cliente'): ?>
          <li class="nav-item"><a href="dashboard.php" class="nav-link">Dashboard</a></li>
          <li class="nav-item"><a href="reporte_servicio.php" class="nav-link">Reporte del Servicio</a></li>
          <li class="nav-item"><a href="guardias_activos.php" class="nav-link">Guardias Activos</a></li>
          <li class="nav-item"><a href="incidencias.php" class="nav-link">Incidencias</a></li>
        <?php endif; ?>

        <!-- 🔹 Supervisor -->
        <?php if ($rol === 'supervisor'): ?>
          <li class="nav-item"><a href="dashboard_supervisor.php" class="nav-link">Dashboard</a></li>
          <li class="nav-item"><a href="monitoreo.php" class="nav-link">Monitoreo en Vivo</a></li>
          <li class="nav-item"><a href="incidencias.php" class="nav-link">Gestionar Incidencias</a></li>
          <li class="nav-item"><a href="extras.php" class="nav-link">Turnos Extra</a></li>
        <?php endif; ?>

        <!-- 🔹 RH -->
        <?php if ($rol === 'rh'): ?>
          <li class="nav-item"><a href="personas.php" class="nav-link">Personal</a></li>
          <li class="nav-item"><a href="documentos.php" class="nav-link">Documentación</a></li>
          <li class="nav-item"><a href="capacitacion.php" class="nav-link">Capacitación</a></li>
        <?php endif; ?>

        <!-- 🔹 Nómina -->
        <?php if ($rol === 'nomina'): ?>
          <li class="nav-item"><a href="calculo_nomina.php" class="nav-link">Calcular Nómina</a></li>
          <li class="nav-item"><a href="extras.php" class="nav-link">Horas Extra</a></li>
          <li class="nav-item"><a href="exportaciones.php" class="nav-link">Exportaciones</a></li>
        <?php endif; ?>

        <!-- 🔹 Admin (acceso total) -->
        <?php if ($rol === 'admin'): ?>
          <li class="nav-item"><a href="dashboard_admin.php" class="nav-link">Dashboard General</a></li>
          <li class="nav-item"><a href="usuarios.php" class="nav-link">Usuarios</a></li>
          <li class="nav-item"><a href="sitios.php" class="nav-link">Sitios y Geocercas</a></li>
          <li class="nav-item"><a href="clientes.php" class="nav-link">Clientes</a></li>
          <li class="nav-item"><a href="auditoria.php" class="nav-link">Auditoría</a></li>
          <li class="nav-item"><a href="configuracion.php" class="nav-link">Configuración</a></li>
        <?php endif; ?>

      </ul>

      <!-- 🔐 Logout siempre visible -->
      <div>
        <a href="logout.php" class="btn btn-sm btn-outline-danger">Salir</a>
      </div>

    </div>
  </div>
</nav>
