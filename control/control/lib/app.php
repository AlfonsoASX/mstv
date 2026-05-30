<?php

$maxExecutionTime = (int)(getenv('APP_MAX_EXECUTION_TIME') ?: 120);
if ($maxExecutionTime > 0) {
    ini_set('max_execution_time', (string)$maxExecutionTime);
    if (function_exists('set_time_limit')) {
        set_time_limit($maxExecutionTime);
    }
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/runtime_schema.php';

app_ensure_schema_once($conexion);
