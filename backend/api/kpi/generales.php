<?php
/**
 * ===========================================
 *  API | KPI → GENERALES
 *  Proyecto: Seguridad Privada - ASX
 *  Archivo: backend/api/kpi/generales.php
 * ===========================================
 */

require_once __DIR__ . '/../../core/middleware.php';
require_once __DIR__ . '/../../core/response.php';
require_once __DIR__ . '/../../config/database.php';

// 1️⃣ Solo Admin, RH, Nómina, Supervisor
$user = Middleware::secure([
    ROLES['ADMIN'],
    ROLES['RH'],
    ROLES['NOMINA'],
    ROLES['SUPERVISOR']
]);

$db = Database::getInstance()->getConnection();


// 2️⃣ KPIs principales
$result = [];


/** ============================
 * 1) PUNTUALIDAD (%)
 * ============================ */
$sql = "
    SELECT 
      ROUND(
        SUM(CASE WHEN validado_geo = 1 AND tipo = 'entrada' THEN 1 ELSE 0 END) /
        NULLIF(SUM(CASE WHEN tipo = 'entrada' THEN 1 ELSE 0 END), 0) * 100, 
      2
      ) AS puntualidad
    FROM checadas
";
$result['puntualidad'] = $db->query($sql)->fetchColumn() ?: 0;


/** ============================
 * 2) ABSENTISMO (Guardias que NO checaron en un turno asignado)
 * ============================ */
$sql = "
    SELECT COUNT(*) AS absentismo
    FROM turnos t
    LEFT JOIN checadas c 
      ON t.guardia_id = c.guardia_id 
     AND DATE(t.fecha) = DATE(c.fecha_hora)
    WHERE c.id IS NULL
";
$result['absentismo'] = $db->query($sql)->fetchColumn() ?: 0;


/** ============================
 * 3) INCIDENCIAS URGENTES
 * ============================ */
$sql = "
    SELECT COUNT(*) 
    FROM incidencias 
    WHERE prioridad = 'alta' AND estado = 'pendiente'
";
$result['incidencias_urgentes'] = $db->query($sql)->fetchColumn() ?: 0;


/** ============================
 * 4) HORAS EXTRAS TOTALES (del mes actual)
 * ============================ */
$sql = "
    SELECT 
      SUM(TIMESTAMPDIFF(HOUR, inicio, fin)) AS horas_extras
    FROM turnos_extras
    WHERE MONTH(inicio) = MONTH(NOW()) 
      AND YEAR(inicio) = YEAR(NOW())
";
$result['horas_extras_totales'] = $db->query($sql)->fetchColumn() ?: 0;


/** ============================
 * 5) GUARDIAS → activos vs totales
 * ============================ */
$sql = "SELECT COUNT(*) FROM usuarios WHERE rol = 'guardia'";
$result['guardias_totales'] = $db->query($sql)->fetchColumn() ?: 0;

$sql = "SELECT COUNT(*) FROM usuarios WHERE rol = 'guardia' AND activo = 1";
$result['guardias_activos'] = $db->query($sql)->fetchColumn() ?: 0;


/** ============================
 * 6) SITIOS ACTIVOS
 * ============================ */
$sql = "SELECT COUNT(*) FROM sitios WHERE activo = 1";
$result['sitios_activos'] = $db->query($sql)->fetchColumn() ?: 0;


// 3️⃣ Respuesta final
Response::success("KPIs generales", $result);
