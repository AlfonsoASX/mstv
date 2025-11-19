<?php
/**
 * ===============================================
 * MODELO: Nomina
 * Cálculo de horas normales, extras y descuentos
 * según turnos y checadas válidas.
 * ===============================================
 */

require_once __DIR__ . '/../config/database.php';

class Nomina
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Registrar evento manual o automático (desde checadas)
     */
    public function registrarEvento($guardia_id, $turno_id, $checada_id, $horas, $tipo)
    {
        $stmt = $this->db->prepare("
            INSERT INTO nomina_eventos (guardia_id, turno_id, checada_id, horas, tipo)
            VALUES (:guardia, :turno, :checada, :horas, :tipo)
        ");

        return $stmt->execute([
            'guardia'  => $guardia_id,
            'turno'    => $turno_id,
            'checada'  => $checada_id,
            'horas'    => $horas,
            'tipo'     => $tipo
        ]);
    }

    /**
     * Consolidar nómina por periodo (para dashboard/exportación)
     */
    public function consolidar($desde, $hasta)
    {
        $stmt = $this->db->prepare("
            SELECT 
                u.id AS guardia_id,
                CONCAT(u.nombre,' ',u.apellido) AS guardia,
                SUM(CASE WHEN e.tipo='normal' THEN e.horas ELSE 0 END) AS horas_normales,
                SUM(CASE WHEN e.tipo='extra' THEN e.horas ELSE 0 END) AS horas_extra,
                SUM(CASE WHEN e.tipo='descuento' THEN e.horas ELSE 0 END) AS descuentos,
                COUNT(DISTINCT e.turno_id) AS turnos_realizados
            FROM nomina_eventos e
            INNER JOIN usuarios u ON u.id = e.guardia_id
            WHERE DATE(e.creado_en) BETWEEN :desde AND :hasta
            GROUP BY u.id
            ORDER BY u.apellido ASC
        ");
        
        $stmt->execute([
            'desde' => $desde,
            'hasta' => $hasta
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Detalle completo por guardia
     */
    public function detalleGuardia($guardia_id, $desde, $hasta)
    {
        $stmt = $this->db->prepare("
            SELECT e.*, c.fecha_hora,
                   t.tipo AS turno_tipo,
                   s.nombre AS sitio,
                   CONCAT(u.nombre,' ',u.apellido) AS guardia
            FROM nomina_eventos e
            LEFT JOIN checadas c ON c.id = e.checada_id
            LEFT JOIN turnos t   ON t.id = e.turno_id
            LEFT JOIN sitios s   ON s.id = c.sitio_id
            LEFT JOIN usuarios u ON u.id = e.guardia_id
            WHERE e.guardia_id = :id
              AND DATE(e.creado_en) BETWEEN :desde AND :hasta
            ORDER BY e.creado_en ASC
        ");
        $stmt->execute([
            'id'    => $guardia_id,
            'desde' => $desde,
            'hasta' => $hasta
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Generar totales de nómina para exportación (Excel / PDF)
     */
    public function resumenExportacion($desde, $hasta)
    {
        $stmt = $this->db->prepare("
            SELECT 
                u.usuario,
                CONCAT(u.nombre,' ',u.apellido) AS guardia,
                SUM(CASE WHEN e.tipo='normal' THEN e.horas ELSE 0 END) AS horas_normales,
                SUM(CASE WHEN e.tipo='extra' THEN e.horas ELSE 0 END) AS horas_extra,
                SUM(CASE WHEN e.tipo='descuento' THEN e.horas ELSE 0 END) AS descuentos
            FROM nomina_eventos e
            INNER JOIN usuarios u ON u.id = e.guardia_id
            WHERE DATE(e.creado_en) BETWEEN :desde AND :hasta
            GROUP BY u.id
        ");
        $stmt->execute([
            'desde' => $desde,
            'hasta' => $hasta
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Total de horas por guardia (acumulado general)
     */
    public function totalesGuardia($guardia_id)
    {
        $stmt = $this->db->prepare("
            SELECT 
                SUM(CASE WHEN tipo='normal' THEN horas ELSE 0 END) AS total_normales,
                SUM(CASE WHEN tipo='extra' THEN horas ELSE 0 END) AS total_extra,
                SUM(CASE WHEN tipo='descuento' THEN horas ELSE 0 END) AS total_descuentos
            FROM nomina_eventos
            WHERE guardia_id = :id
        ");
        $stmt->execute(['id' => $guardia_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Generar nómina desde checadas válidas (GPS + facial)
     */
    public function procesarDesdeChecadas($desde, $hasta)
    {
        $stmt = $this->db->prepare("
            SELECT id, guardia_id, turno_id, fecha_hora
            FROM checadas
            WHERE DATE(fecha_hora) BETWEEN :desde AND :hasta
              AND validado_geo = 1
              AND validado_facial = 1
        ");
        $stmt->execute(['desde' => $desde, 'hasta' => $hasta]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
