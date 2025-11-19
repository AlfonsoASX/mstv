<?php
/**
 * ===============================================
 * PANEL ADMIN → CHAT INTERNO
 * Archivo: backend/admin/chat_admin.php
 * ===============================================
 */

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
require_once __DIR__ . '/../core/helpers.php';
require_once __DIR__ . '/../config/database.php';

$db = Database::getInstance()->getConnection();

// Obtener usuarios disponibles para chat (Guardias, Supervisores, RH)
$stmt = $db->prepare("
    SELECT id, nombre, apellido, rol
    FROM usuarios
    WHERE activo = 1 AND rol IN ('guardia', 'supervisor', 'admin', 'rh')
    ORDER BY rol, nombre
");
$stmt->execute();
$contactos = $stmt->fetchAll();
?>

<div class="container">
    <h3 class="mb-4">Chat Administrativo</h3>

    <div class="row">
        
        <!-- 📜 Lista de contactos -->
        <div class="col-md-3">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">Contactos</div>
                <div class="list-group" id="listaContactos">
                    <?php foreach ($contactos as $c): ?>
                        <button class="list-group-item list-group-item-action"
                                onclick="abrirChat(<?= $c['id'] ?>)">
                            <?= htmlspecialchars($c['nombre'] . ' ' . $c['apellido']) ?>
                            <span class="badge bg-secondary"><?= strtoupper($c['rol']) ?></span>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- 💬 Ventana de chat -->
        <div class="col-md-9">
            <div class="card shadow-sm">
                
                <div class="card-header bg-secondary text-white">
                    <strong id="chatCon">Seleccione un contacto</strong>
                </div>
                
                <div class="card-body" id="chatMensajes" 
                     style="height:400px; overflow-y:auto; background:#f9f9f9;">
                    <p class="text-muted text-center mt-5">No hay mensajes</p>
                </div>

                <div class="card-footer">
                    <form id="formChat" class="d-flex" onsubmit="enviarMensaje(event)">
                        <input type="hidden" id="destinatario_id">
                        <input type="text" id="mensaje" 
                               class="form-control me-2" 
                               placeholder="Escribe tu mensaje..." 
                               required>
                        <button class="btn btn-primary">Enviar</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

<script>
let actualChat = 0;
let refreshInterval = null;

// 📩 Abrir chat con un usuario específico
function abrirChat(id) {
    actualChat = id;
    document.getElementById('destinatario_id').value = id;
    document.getElementById('chatCon').innerText = "Chat con ID " + id;

    cargarMensajes();

    if (refreshInterval) clearInterval(refreshInterval);
    refreshInterval = setInterval(cargarMensajes, 4000); // Actualiza cada 4s
}

// 🔄 Cargar mensajes vía API
async function cargarMensajes() {
    if (!actualChat) return;

    const resp = await fetch(`../api/chat/obtener.php?remitente_id=${actualChat}`);
    const data = await resp.json();

    if (data.status === 'success') {
        const contenedor = document.getElementById('chatMensajes');
        contenedor.innerHTML = '';

        data.data.forEach(msg => {
            const div = document.createElement('div');
            div.className = msg.mine ? 'text-end mb-2' : 'text-start mb-2';

            div.innerHTML = `
                <div class="p-2 rounded 
                    ${msg.mine ? 'bg-primary text-white' : 'bg-light'}" 
                    style="display:inline-block; max-width:75%;">
                    ${msg.texto}
                    <br>
                    <small class="text-muted" 
                        style="font-size:0.7rem">${msg.fecha}</small>
                </div>
            `;

            contenedor.appendChild(div);
            contenedor.scrollTop = contenedor.scrollHeight;
        });
    }
}

// 📤 Enviar mensaje
async function enviarMensaje(e) {
    e.preventDefault();

    const texto = document.getElementById('mensaje').value;
    const destino = document.getElementById('destinatario_id').value;
    
    const resp = await fetch('../api/chat/enviar.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ destinatario_id: destino, texto })
    });
    
    const data = await resp.json();
    if (data.status === 'success') {
        document.getElementById('mensaje').value = '';
        cargarMensajes();
    } else {
        alert(data.message || "Error enviando mensaje");
    }
}
</script>
