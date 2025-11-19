<?php
/**
 * ===========================================
 *  API | NOMINA → CALCULAR
 *  Proyecto: Seguridad Privada - ASX
 *  Archivo: backend/api/nomina/calcular.php
 * ===========================================
 */

require_once __DIR__ . '/../../core/middleware.php';
require_once __DIR__ . '/../../core/response.php';
require_once __DIR__ . '/../../core/helpers.php';
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/database.php';

// 1️⃣ Roles permitidos
$user = Middleware::secure([
    ROLES['ADMIN'],
    ROLES['NOMINA'],
    ROLES['RH']
]);

$db = Database::getInstance()->getConnection();

// 2️⃣ Filtros esperados
$data = Helpers::getJsonInput();

if (!Helpers::validarCampos($data, ['desde', 'hasta'])) {
    Response::error("Se requieren los campos: desde, hasta (YYYY-MM-DD).");
}

$desde = $data['desde'];
$hasta = $data['hasta'];
$guardia_id = !empty($data['guardia_id']) ? intval($data['guardia_id']) : null;

// 3️⃣ Tarifa global (puede venir luego desde tabla turnos o usuarios)
$tarifa_hora       = 60;   // MXN por hora ordinaria
$tarifa_hora_extra = 90;   // MXN por hora extra

// 4️⃣ Query principal: checadas válidas (entrada + salida ligadas)
$sql = "
    SELECT 
        c1.guardia_id,
        CONCAT(u.nombre,' ',IFNULL(u.apellido,'')) AS guardia_nombre,
        c1.sitio_id,
        s.nombre AS sitio_nombre,
        c1.fecha_hora AS entrada,
        c2.fecha_hora AS salida,
        TIMESTAMPDIFF(HOUR, c1.fecha_hora, c2.fecha_hora) AS horas_trabajadas,
        CASE 
            WHEN TIMESTAMPDIFF(HOUR, c1.fecha_hora, c2.fecha_hora) > 8 
            THEN TIMESTAMPDIFF(HOUR, c1.fecha_hora, c2.fecha_hora) - 8
            ELSE 0 
        END AS horas_extra
    FROM checadas c1
    INNER JOIN checadas c2 ON c1.id = c2.turno_id AND c2.tipo = 'salida'
    INNER JOIN usuarios u ON c1.guardia_id = u.id
    INNER JOIN sitios s ON c1.sitio_id = s.id
    WHERE c1.tipo = 'entrada'
    AND DATE(c1.fecha_hora) BETWEEN :desde AND :hasta
    " . ($guardia_id ? "AND c1.guardia_id = :guardia_id" : "") . "
    ORDER BY c1.guardia_id, c1.fecha_hora
";

$stmt = $db->prepare($sql);

$params = ['desde' => $desde, 'hasta' => $hasta];
if ($guardia_id) $params['guardia_id'] = $guardia_id;

$stmt->execute($params);
$resultados = $stmt->fetchAll();

// 5️⃣ Procesamiento: cálculos económicos
$nomina = [];

foreach ($resultados as $row) {
    $gId = $row['guardia_id'];
    
    if (!isset($nomina[$gId])) {
        $nomina[$gId] = [
            'guardia_id'      => $gId,
            'guardia_nombre'  => $row['guardia_nombre'],
            'sitios'          => [],
            'horas_ordinarias'=> 0,
            'horas_extra'     => 0,
            'total_ordinario' => 0,
            'total_extra'     => 0,
            'total_pagar'     => 0
        ];
    }
    
    $horasOrd  = max($row['horas_trabajadas'] - $row['horas_extra'], 0);
    $horasExt  = $row['horas_extra'];
    
    $nomina[$gId]['horas_ordinarias'] += $horasOrd;
    $nomina[$gId]['horas_extra']     += $horasExt;
    
    $nomina[$gId]['total_ordinario'] += ($horasOrd * $tarifa_hora);
    $nomina[$gId]['total_extra']     += ($horasExt * $tarifa_hora_extra);
    
    $nomina[$gId]['total_pagar'] = $nomina[$gId]['total_ordinario'] + $nomina[$gId]['total_extra'];
}

// 6️⃣ Formato limpio para salida
$nomina_final = array_values($nomina);

// 7️⃣ Respuesta
Response::success("Cálculo de nómina generado.", [
    "periodo" => [
        "desde" => $desde,
        "hasta" => $hasta
    ],
    "tarifas" => [
        "hora_ordinaria" => $tarifa_hora,
        "hora_extra"     => $tarifa_hora_extra
    ],
    "resultados" => $nomina_final
]);
