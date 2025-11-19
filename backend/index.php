<?php
/**
 * ===============================================
 * BACKEND API ROUTER PRINCIPAL (PHP PURO)
 * Archivo: backend/index.php
 * ===============================================
 */

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/core/response.php';
require_once __DIR__ . '/core/helpers.php';
require_once __DIR__ . '/core/auth.php';

// Obtener endpoint solicitado
$request = $_SERVER['REQUEST_URI'];
$method  = $_SERVER['REQUEST_METHOD'];
$basePath = dirname($_SERVER['SCRIPT_NAME']);   // normalmente /backend
$path = substr($request, strlen($basePath));

// Limpiar '/'
$path = trim($path, '/');

// Definir rutas básicas
$routes = [
    'api/login'                => 'api/login.php',
    'api/logout'               => 'api/logout.php',
    'api/checadas/checkin'     => 'api/checadas/checkin.php',
    'api/checadas/checkout'    => 'api/checadas/checkout.php',
    'api/incidencias/crear'    => 'api/incidencias/crear.php',
    'api/incidencias/listar'   => 'api/incidencias/listar.php',
    'api/chat/enviar'          => 'api/chat/enviar.php',
    'api/chat/obtener'         => 'api/chat/obtener.php',
    'api/supervisor/guardias'  => 'api/supervisor/guardias.php',
    'api/supervisor/monitoreo' => 'api/supervisor/monitoreo.php',
    'api/kpi/generales'        => 'api/kpi/generales.php',
    'api/nomina/calcular'      => 'api/nomina/calcular.php',
    'api/turnos/extra_iniciar' => 'api/turnos/extra_iniciar.php',
    'api/turnos/extra_cerrar'  => 'api/turnos/extra_cerrar.php',
];

// Resolver ruta
if (array_key_exists($path, $routes)) {
    require_once __DIR__ . '/' . $routes[$path];
    exit();
}

// Ruta base (opcional, bienvenida o salud del sistema)
if ($path === "" || $path === "api") {
    Response::json([
        'status' => 'ok',
        'message' => 'API Control Seguridad funcionando',
        'version' => '1.0',
        'time'    => date('Y-m-d H:i:s')
    ]);
    exit();
}

// Error 404 para rutas no definidas
Response::json([
    'status' => 'error',
    'message' => 'Endpoint no encontrado: ' . $path
], 404);
