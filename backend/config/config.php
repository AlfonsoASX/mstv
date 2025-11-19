<?php
/**
 * ===========================================
 *  CONFIGURACIÓN GLOBAL DEL SISTEMA
 *  Proyecto: Seguridad Privada - ASX
 *  Archivo: config.php
 * ===========================================
 */

date_default_timezone_set('America/Mexico_City');

/* -------------------------------------------
   BASE DE DATOS
------------------------------------------- */
define('DB_HOST', 'localhost');
define('DB_NAME', 'control_seguridad');
define('DB_USER', 'root');
define('DB_PASS', '');

/* -------------------------------------------
   URL BASE DEL BACKEND / API
------------------------------------------- */
define('BASE_URL', 'https://tu-dominio.com/seguridad_proyecto/backend/');
define('API_URL', BASE_URL . 'api/');

/* -------------------------------------------
   RUTAS DEL SISTEMA (ABSOLUTAS EN SERVIDOR)
------------------------------------------- */
define('STORAGE_PATH', __DIR__ . '/../storage/');
define('FOTOS_SELFIE_PATH', STORAGE_PATH . 'fotos_selfie/');
define('FOTOS_INCIDENCIAS_PATH', STORAGE_PATH . 'fotos_incidencias/');
define('FOTOS_REGISTRO_PATH', STORAGE_PATH . 'fotos_registro/');
define('LOGS_PATH', STORAGE_PATH . 'logs/');

/* -------------------------------------------
   CONFIG DE ARCHIVOS
------------------------------------------- */
define('MAX_FILE_SIZE_MB', 5);
define('MAX_FILE_SIZE', MAX_FILE_SIZE_MB * 1024 * 1024);

/* -------------------------------------------
   AUTENTICACIÓN / TOKENS
------------------------------------------- */
define('TOKEN_LENGTH', 64); // caracteres
define('TOKEN_EXPIRACION_HORAS', 72);

/* -------------------------------------------
   RECONOCIMIENTO FACIAL
------------------------------------------- */
define('FACIAL_THRESHOLD', 0.78); // nivel de similitud 0–1
define('FACIAL_CMD', '/usr/bin/python3 ' . __DIR__ . '/../python/compare_faces.py');

/* -------------------------------------------
   GEOCERCA
------------------------------------------- */
define('DISTANCIA_MAXIMA_METROS', 120);

/* -------------------------------------------
   CORREO SMTP (OPCIONAL)
------------------------------------------- */
define('SMTP_HOST', 'mail.tu-dominio.com');
define('SMTP_USER', 'noreply@tu-dominio.com');
define('SMTP_PASS', '');
define('SMTP_PORT', 465);

/* -------------------------------------------
   CONTROL DE ERRORES
------------------------------------------- */
define('DEBUG_MODE', true);

if (DEBUG_MODE) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    error_reporting(0);
}

/* -------------------------------------------
   FUNCIÓN AUXILIAR PARA CREAR DIRECTORIOS
------------------------------------------- */
$paths_to_check = [
    STORAGE_PATH,
    FOTOS_SELFIE_PATH,
    FOTOS_INCIDENCIAS_PATH,
    FOTOS_REGISTRO_PATH,
    LOGS_PATH
];

foreach ($paths_to_check as $folder) {
    if (!is_dir($folder)) {
        mkdir($folder, 0775, true);
    }
}

