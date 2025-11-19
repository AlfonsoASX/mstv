<?php
/**
 * ===============================================
 * PANEL ADMIN → GESTIÓN DE USUARIOS (TODOS LOS ROLES)
 * Archivo: backend/admin/usuarios.php
 * ===============================================
 */

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
require_once __DIR__ . '/../config/database.php';

$db = Database::getInstance()->getConnection();

// Obtener usuarios
$stmt = $db->query("
    SELECT u.id, u.usuario, u.nombre, u.apellido, u.email, u.rol, u.activo,
           DATE(u.created_at) AS creado,
           (SELECT COUNT(*) FROM checadas c WHERE c.guardia_id = u.id) AS checadas,
           (SELECT COUNT(*) FROM incidencias i WHERE i.guardia_id = u.id) AS incidencias
    FROM usuarios u
    ORDER BY FIELD(u.rol,'admin','rh','nomina','supervisor','cliente','guardia'), u.apellido
");
$usuarios = $stmt->fetchAll();
?>

<div class="container">

    <h3 class="mb-4">Usuarios del Sistema</h3>

    <div class="text-end mb-3">
        <a href="usuarios_nuevo.php" class="btn btn-success">➕ Crear Usuario</a>
    </div>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Nombre</th>
                        <th>Usuario</th>
                        <th>Rol</th>
                        <th>Email</th>
                        <th>Estado</th>
                        <th>Creado</th>
                        <th>Checadas</th>
                        <th>Incidencias</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!$usuarios): ?>
                    <tr><td colspan="9" class="text-center py-4">No hay usuarios registrados.</td></tr>
                <?php else: ?>
                    <?php foreach ($usuarios as $u): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($u['nombre'].' '.$u['apellido']) ?></strong></td>
                        <td><?= htmlspecialchars($u['usuario']) ?></td>
                        <td>
                            <span class="badge
                                <?= $u['rol']=='admin' ? 'bg-dark' : ($u['rol']=='supervisor' ? 'bg-primary' :
                                ($u['rol']=='guardia' ? 'bg-secondary' : 'bg-info')) ?>">
                                <?= strtoupper($u['rol']) ?>
                            </span>
                        </td>
                        <td><?= htmlspecialchars($u['email']) ?></td>
                        <td>
                            <span class="badge bg-<?= $u['activo'] ? 'success' : 'secondary' ?>">
                                <?= $u['activo'] ? 'Activo' : 'Inactivo' ?>
                            </span>
                        </td>
                        <td><?= $u['creado'] ?></td>
                        <td><?= $u['checadas'] ?></td>
                        <td><?= $u['incidencias'] ?></td>

                        <td>
                            <a href="usuarios_ver.php?id=<?= $u['id'] ?>" 
                               class="btn btn-sm btn-outline-info">Ver</a>
                            <a href="usuarios_editar.php?id=<?= $u['id'] ?>" 
                               class="btn btn-sm btn-outline-warning">Editar</a>
                            <a href="usuarios_permisos.php?id=<?= $u['id'] ?>" 
                               class="btn btn-sm btn-outline-primary">Permisos</a>
                            <a href="usuarios_baja.php?id=<?= $u['id'] ?>" 
                               class="btn btn-sm btn-outline-danger"
                               onclick="return confirm('¿Dar de baja a este usuario?')">
                               Baja
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
