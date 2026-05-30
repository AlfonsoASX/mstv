<?php
require_once __DIR__ . '/lib/app.php';
require_once __DIR__ . '/lib/operations.php';

app_require_session();
app_require_roles(['ADMIN', 'SUPERVISOR', 'RH', 'NOMINA', 'DUEÑO']);
app_support_bootstrap($conexion);

$usuarioActualId = (int)($_SESSION['usuario_id'] ?? 0);
$mensajeExito = '';
$mensajeError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $destinatarioId = (int)app_post('destinatario_id', 0);
    $mensaje = app_clean_text(app_post('mensaje', ''));

    if ($destinatarioId <= 0 || $mensaje === '') {
        $mensajeError = 'Selecciona una conversación y escribe un mensaje.';
    } else {
        $contieneGroserias = app_detect_profanity($mensaje) ? 1 : 0;
        $sql = "
            INSERT INTO mensajes_chat
                (remitente_id, destinatario_id, tipo_canal, cuerpo_mensaje, contiene_groserias, es_leido)
            VALUES (?, ?, 'DIRECTO', ?, ?, 0)
        ";

        if ($stmt = mysqli_prepare($conexion, $sql)) {
            mysqli_stmt_bind_param($stmt, 'iisi', $usuarioActualId, $destinatarioId, $mensaje, $contieneGroserias);
            if (mysqli_stmt_execute($stmt)) {
                $chatId = (int)mysqli_insert_id($conexion);
                mysqli_stmt_close($stmt);
                app_log_system(
                    $conexion,
                    $usuarioActualId,
                    'CHAT_CONTROL',
                    'mensajes_chat',
                    $chatId,
                    ['destinatario_id' => $destinatarioId, 'contiene_groserias' => $contieneGroserias]
                );
                header('Location: chat-admin.php?usuario_id=' . $destinatarioId . '&sent=1');
                exit;
            }
            $mensajeError = 'No se pudo enviar el mensaje.';
            mysqli_stmt_close($stmt);
        } else {
            $mensajeError = 'No fue posible preparar el envío del mensaje.';
        }
    }
}

if (isset($_GET['sent'])) {
    $mensajeExito = 'Mensaje enviado correctamente.';
}

$conversaciones = app_db_all($conexion, "
    SELECT
        u.id AS usuario_id,
        p.id AS personal_id,
        COALESCE(NULLIF(TRIM(CONCAT(p.nombres, ' ', p.apellidos)), ''), u.usuario) AS nombre,
        MAX(m.fecha_creacion) AS ultima_fecha,
        SUM(CASE WHEN m.remitente_id = u.id AND m.es_leido = 0 AND (m.destinatario_id IS NULL OR m.destinatario_id = {$usuarioActualId}) THEN 1 ELSE 0 END) AS pendientes
    FROM usuarios u
    INNER JOIN personal p ON p.usuario_id = u.id
    INNER JOIN mensajes_chat m ON m.remitente_id = u.id OR m.destinatario_id = u.id
    WHERE u.id <> {$usuarioActualId}
    GROUP BY u.id, p.id, nombre
    ORDER BY ultima_fecha DESC, nombre ASC
");

$usuarioSeleccionado = (int)app_get('usuario_id', 0);
if ($usuarioSeleccionado <= 0 && !empty($conversaciones)) {
    $usuarioSeleccionado = (int)$conversaciones[0]['usuario_id'];
}

$detalleColaborador = null;
$mensajes = [];

if ($usuarioSeleccionado > 0) {
    $detalleColaborador = app_db_one($conexion, "
        SELECT
            u.id AS usuario_id,
            p.id AS personal_id,
            COALESCE(NULLIF(TRIM(CONCAT(p.nombres, ' ', p.apellidos)), ''), u.usuario) AS nombre,
            u.usuario,
            p.telefono
        FROM usuarios u
        INNER JOIN personal p ON p.usuario_id = u.id
        WHERE u.id = {$usuarioSeleccionado}
        LIMIT 1
    ");

    mysqli_query($conexion, "
        UPDATE mensajes_chat
        SET es_leido = 1
        WHERE remitente_id = {$usuarioSeleccionado}
          AND es_leido = 0
          AND (destinatario_id IS NULL OR destinatario_id = {$usuarioActualId})
    ");

    $mensajes = app_db_all($conexion, "
        SELECT
            m.id,
            m.remitente_id,
            m.destinatario_id,
            m.tipo_canal,
            m.cuerpo_mensaje,
            m.contiene_groserias,
            m.es_leido,
            m.fecha_creacion,
            COALESCE(NULLIF(TRIM(CONCAT(p.nombres, ' ', p.apellidos)), ''), u.usuario, 'Usuario') AS remitente_nombre
        FROM mensajes_chat m
        LEFT JOIN usuarios u ON u.id = m.remitente_id
        LEFT JOIN personal p ON p.usuario_id = u.id
        WHERE m.remitente_id = {$usuarioSeleccionado}
           OR m.destinatario_id = {$usuarioSeleccionado}
        ORDER BY m.fecha_creacion ASC, m.id ASC
        LIMIT 200
    ");
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, shrink-to-fit=no">
    <title>Chat interno</title>
    <link rel="icon" type="image/x-icon" href="../src/assets/img/favicon.ico"/>
    <link href="../layouts/vertical-light-menu/css/light/loader.css" rel="stylesheet" type="text/css" />
    <link href="../layouts/vertical-light-menu/css/dark/loader.css" rel="stylesheet" type="text/css" />
    <script src="../layouts/vertical-light-menu/loader.js"></script>
    <link href="https://fonts.googleapis.com/css?family=Nunito:400,600,700" rel="stylesheet">
    <link href="../src/bootstrap/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
    <link href="../layouts/vertical-light-menu/css/light/plugins.css" rel="stylesheet" type="text/css" />
    <link href="../layouts/vertical-light-menu/css/dark/plugins.css" rel="stylesheet" type="text/css" />
    <style>
        .card-soft { border-radius: 16px; }
        .conversation-link {
            display: block;
            padding: 14px;
            border-radius: 14px;
            text-decoration: none;
            color: inherit;
            background: rgba(0, 0, 0, 0.02);
            border: 1px solid transparent;
            margin-bottom: 10px;
        }
        .conversation-link.active {
            border-color: rgba(0, 173, 181, 0.35);
            background: rgba(0, 173, 181, 0.08);
        }
        .bubble {
            max-width: 82%;
            padding: 12px 14px;
            border-radius: 16px;
            margin-bottom: 12px;
        }
        .bubble.me {
            margin-left: auto;
            background: rgba(0, 173, 181, 0.12);
        }
        .bubble.other {
            margin-right: auto;
            background: rgba(0, 0, 0, 0.04);
        }
        .thread-box {
            min-height: 520px;
            max-height: 520px;
            overflow-y: auto;
        }
    </style>
</head>
<body class="layout-boxed">
    <div id="load_screen">
        <div class="loader"><div class="loader-content"><div class="spinner-grow align-self-center"></div></div></div>
    </div>
    <?php include 'partes/nav.php'; ?>
    <div class="main-container" id="container">
        <div class="overlay"></div>
        <div class="search-overlay"></div>
        <div class="sidebar-wrapper sidebar-theme">
            <nav id="sidebar"><div class="sidebar-wrapper sidebar-theme"><?php include 'partes/menu.php'; ?></div></nav>
        </div>
        <div id="content" class="main-content">
            <div class="layout-px-spacing">
                <div class="middle-content container-xxl p-0">
                    <div class="secondary-nav mb-3">
                        <div class="breadcrumbs-container" data-page-heading="Chat">
                            <header class="header navbar navbar-expand-sm">
                                <a href="javascript:void(0);" class="btn-toggle sidebarCollapse" data-placement="bottom">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
                                </a>
                                <div class="ms-3">
                                    <h5 class="mb-0">Chat interno</h5>
                                    <small class="text-muted">Conversaciones recibidas desde la app para responder a colaboradores.</small>
                                </div>
                            </header>
                        </div>
                    </div>

                    <?php if ($mensajeExito): ?><div class="alert alert-success"><?php echo app_h($mensajeExito); ?></div><?php endif; ?>
                    <?php if ($mensajeError): ?><div class="alert alert-danger"><?php echo app_h($mensajeError); ?></div><?php endif; ?>

                    <div class="row layout-top-spacing">
                        <div class="col-xl-4 layout-spacing">
                            <div class="widget widget-card-two card-soft" style="padding:20px;">
                                <div class="widget-heading">
                                    <h5 class="mb-1">Conversaciones</h5>
                                    <small class="text-muted">Selecciona un colaborador para abrir su hilo.</small>
                                </div>
                                <div class="widget-content" id="conversation-list">
                                    <?php if (empty($conversaciones)): ?>
                                        <p class="text-muted mb-0">No hay mensajes recibidos todavía.</p>
                                    <?php else: ?>
                                        <?php foreach ($conversaciones as $row): ?>
                                            <a class="conversation-link <?php echo (int)$row['usuario_id'] === $usuarioSeleccionado ? 'active' : ''; ?>" href="chat-admin.php?usuario_id=<?php echo (int)$row['usuario_id']; ?>">
                                                <div class="d-flex justify-content-between align-items-start gap-2">
                                                    <div>
                                                        <strong><?php echo app_h($row['nombre']); ?></strong>
                                                        <div class="text-muted small">Último mensaje: <?php echo app_datetime($row['ultima_fecha']); ?></div>
                                                    </div>
                                                    <?php if ((int)$row['pendientes'] > 0): ?>
                                                        <span class="badge bg-danger"><?php echo (int)$row['pendientes']; ?></span>
                                                    <?php endif; ?>
                                                </div>
                                            </a>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-8 layout-spacing">
                            <div class="widget widget-card-two card-soft" style="padding:20px;">
                                <div class="widget-heading mb-3">
                                    <?php if ($detalleColaborador): ?>
                                        <h5 class="mb-1"><?php echo app_h($detalleColaborador['nombre']); ?></h5>
                                        <small class="text-muted">
                                            Usuario: <?php echo app_h($detalleColaborador['usuario']); ?>
                                            <?php if (!empty($detalleColaborador['telefono'])): ?>
                                                · Tel: <?php echo app_h($detalleColaborador['telefono']); ?>
                                            <?php endif; ?>
                                        </small>
                                    <?php else: ?>
                                        <h5 class="mb-1">Sin conversación seleccionada</h5>
                                    <?php endif; ?>
                                </div>

                                <div class="widget-content">
                                    <div class="thread-box mb-3" id="thread-box">
                                        <?php if (empty($mensajes)): ?>
                                            <p class="text-muted mb-0">Todavía no hay mensajes para mostrar.</p>
                                        <?php else: ?>
                                            <?php foreach ($mensajes as $mensaje): ?>
                                                <div class="bubble <?php echo (int)$mensaje['remitente_id'] === $usuarioActualId ? 'me' : 'other'; ?>">
                                                    <div class="fw-bold mb-1"><?php echo app_h($mensaje['remitente_nombre']); ?></div>
                                                    <div><?php echo nl2br(app_h($mensaje['cuerpo_mensaje'])); ?></div>
                                                    <div class="small text-muted mt-2">
                                                        <?php echo app_datetime($mensaje['fecha_creacion']); ?>
                                                        <?php if ((int)$mensaje['contiene_groserias'] === 1): ?>
                                                            · <span class="badge bg-warning text-dark">Antigroserías</span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>

                                    <?php if ($detalleColaborador): ?>
                                        <form method="post">
                                            <input type="hidden" name="destinatario_id" value="<?php echo (int)$detalleColaborador['usuario_id']; ?>">
                                            <div class="mb-2">
                                                <label class="form-label">Responder</label>
                                                <textarea name="mensaje" rows="4" class="form-control" placeholder="Escribe una respuesta para el colaborador..."></textarea>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <small class="text-muted">Las respuestas se reflejan en la app del colaborador.</small>
                                                <button type="submit" class="btn btn-primary">Enviar respuesta</button>
                                            </div>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php include 'partes/footer.php'; ?>
        </div>
    </div>
    <script src="../src/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../src/plugins/src/perfect-scrollbar/perfect-scrollbar.min.js"></script>
    <script src="../src/plugins/src/mousetrap/mousetrap.min.js"></script>
    <script src="../layouts/vertical-light-menu/app.js"></script>
    <script>
        const chatAdminUsuarioSeleccionado = <?php echo (int)$usuarioSeleccionado; ?>;
        const chatAdminPollMs = 9000;

        async function refrescarChatAdmin() {
            if (!chatAdminUsuarioSeleccionado || document.hidden) {
                return;
            }

            const threadBox = document.getElementById('thread-box');
            const conversationList = document.getElementById('conversation-list');
            if (!threadBox || !conversationList) {
                return;
            }

            const cercaDelFinal = threadBox.scrollHeight - threadBox.scrollTop - threadBox.clientHeight < 90;

            try {
                const response = await fetch(`chat-admin.php?usuario_id=${chatAdminUsuarioSeleccionado}&_=${Date.now()}`, {
                    headers: { 'X-Requested-With': 'fetch' }
                });
                const html = await response.text();
                const doc = new DOMParser().parseFromString(html, 'text/html');
                const nuevoThread = doc.getElementById('thread-box');
                const nuevaLista = doc.getElementById('conversation-list');

                if (nuevoThread) {
                    threadBox.innerHTML = nuevoThread.innerHTML;
                    if (cercaDelFinal) {
                        threadBox.scrollTop = threadBox.scrollHeight;
                    }
                }

                if (nuevaLista) {
                    conversationList.innerHTML = nuevaLista.innerHTML;
                }
            } catch (error) {
                // El siguiente intervalo volverá a intentar sin interrumpir al usuario.
            }
        }

        if (chatAdminUsuarioSeleccionado) {
            const threadBox = document.getElementById('thread-box');
            if (threadBox) {
                threadBox.scrollTop = threadBox.scrollHeight;
            }
            setInterval(refrescarChatAdmin, chatAdminPollMs);
        }
    </script>
</body>
</html>
