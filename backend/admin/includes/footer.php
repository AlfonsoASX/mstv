<?php
/**
 * ===============================================
 * Footer global (Portal Cliente / Admin / Supervisor)
 * Archivo: backend/admin/includes/footer.php
 * ===============================================
 */
?>

<footer class="mt-5 py-4 text-center border-top bg-light">
    <div class="container">

        <small class="text-muted d-block">
            Sistema Integral de Control de Guardias y Turnos <br>
            <strong>SEGURIDAD PRIVADA</strong>
        </small>

        <small class="text-muted d-block mt-2">
            © <?= date('Y') ?> — Todos los derechos reservados.
        </small>

        <small class="text-muted">
            Versión 1.0.0
        </small>

    </div>
</footer>

<!-- JS CORE (Opcional y reutilizable) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- JS adicional del módulo (si se declara antes) -->
<?php if (!empty($extra_js)) echo $extra_js; ?>

</body>
</html>
