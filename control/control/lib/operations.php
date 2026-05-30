<?php

require_once __DIR__ . '/helpers.php';

if (!function_exists('app_support_bootstrap')) {
    function app_support_bootstrap(mysqli $conexion): void
    {
        $queries = [
            "CREATE TABLE IF NOT EXISTS bitacora_sistema (
                id BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                usuario_id INT NULL,
                tipo_accion VARCHAR(50) NOT NULL,
                tabla_afectada VARCHAR(50) NULL,
                registro_id INT NULL,
                valor_anterior LONGTEXT NULL,
                valor_nuevo LONGTEXT NULL,
                direccion_ip VARCHAR(45) NULL,
                fecha_creacion TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                KEY idx_bitacora_tipo_fecha (tipo_accion, fecha_creacion),
                KEY idx_bitacora_usuario_fecha (usuario_id, fecha_creacion)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            "CREATE TABLE IF NOT EXISTS mensajes_chat (
                id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                remitente_id INT NOT NULL,
                destinatario_id INT NULL,
                tipo_canal ENUM('DIRECTO','CANAL_RH') NOT NULL DEFAULT 'DIRECTO',
                cuerpo_mensaje TEXT NOT NULL,
                contiene_groserias TINYINT(1) NOT NULL DEFAULT 0,
                es_leido TINYINT(1) NOT NULL DEFAULT 0,
                fecha_creacion TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                KEY idx_chat_origen_fecha (remitente_id, fecha_creacion),
                KEY idx_chat_destino_fecha (destinatario_id, fecha_creacion)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        ];

        foreach ($queries as $query) {
            @mysqli_query($conexion, $query);
        }
    }
}

if (!function_exists('app_detect_profanity')) {
    function app_detect_profanity(string $message): bool
    {
        $message = mb_strtolower(app_clean_text($message), 'UTF-8');
        if ($message === '') {
            return false;
        }

        $patterns = [
            '/\bpendej[oa]s?\b/u',
            '/\bching(?:a|ar|ado|ada|ados|adas|on|ue)\b/u',
            '/\bculer[oa]s?\b/u',
            '/\bidiot[ao]s?\b/u',
            '/\best[uú]pid[oa]s?\b/u',
            '/\bpinche?s?\b/u',
            '/\bverga\b/u',
            '/\bput[oa]s?\b/u',
            '/\bperr[oa]s?\b/u',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $message)) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('app_log_system')) {
    function app_log_system(
        mysqli $conexion,
        ?int $usuarioId,
        string $tipoAccion,
        ?string $tablaAfectada = null,
        ?int $registroId = null,
        ?array $valorNuevo = null,
        ?array $valorAnterior = null
    ): void {
        $sql = "
            INSERT INTO bitacora_sistema
                (usuario_id, tipo_accion, tabla_afectada, registro_id, valor_anterior, valor_nuevo, direccion_ip)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ";

        $valorAnteriorJson = $valorAnterior ? json_encode($valorAnterior, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null;
        $valorNuevoJson = $valorNuevo ? json_encode($valorNuevo, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null;
        $direccionIp = $_SERVER['REMOTE_ADDR'] ?? null;

        if ($stmt = mysqli_prepare($conexion, $sql)) {
            mysqli_stmt_bind_param(
                $stmt,
                'ississs',
                $usuarioId,
                $tipoAccion,
                $tablaAfectada,
                $registroId,
                $valorAnteriorJson,
                $valorNuevoJson,
                $direccionIp
            );
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
    }
}
