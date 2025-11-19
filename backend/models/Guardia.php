<?php
/**
 * ===============================================
 * MODELO: Guardia
 * Gestión integral del personal operativo
 * ===============================================
 */

require_once __DIR__ . '/../config/database.php';

class Guardia
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Obtener todos los guardias
     */
    public function listar()
    {
        $stmt = $this->db->query("
            SELECT u.id, CONCAT(u.nombre,' ',u.apellido) AS nombre,
                   u.telefono, u.email, u.activo,
                   (SELECT COUNT(*) FROM checadas c WHERE c.guardia_id = u.id) AS checadas,
                   (SELECT COUNT(*) FROM incidencias i WHERE i.guardia_id = u.id) AS incidencias
            FROM usuarios u
            WHERE u.rol = 'guardia'
            ORDER BY u.apellido ASC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener detalle completo de un guardia
     */
    public function obtenerDetalle($id)
    {
        $stmt = $this->db->prepare("
            SELECT u.*, 
                   CONCAT(u.nombre,' ',u.apellido) AS nombre_completo,
                   s.nombre AS sitio_asignado,
                   c.nombre AS cliente_asignado
            FROM usuarios u
            LEFT JOIN asignaciones a ON a.guardia_id = u.id AND a.activo = 1
            LEFT JOIN sitios s ON s.id = a.sitio_id
            LEFT JOIN clientes c ON c.id = s.cliente_id
            WHERE u.id = :id AND u.rol = 'guardia'
        ");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener guardias asignados a un sitio
     */
    public function obtenerPorSitio($sitio_id)
    {
        $stmt = $this->db->prepare("
            SELECT g.id, CONCAT(g.nombre,' ',g.apellido) AS nombre,
                   g.telefono, g.email, g.activo
            FROM asignaciones a
            INNER JOIN usuarios g ON g.id = a.guardia_id
            WHERE a.sitio_id = :sitio AND a.activo = 1 AND g.rol='guardia'
        ");
        $stmt->execute(['sitio' => $sitio_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Guardias con turno hoy
     */
    public function guardiasConTurnoHoy($sitio_id)
    {
        $stmt = $this->db->prepare("
            SELECT t.id AS turno_id,
                   CONCAT(u.nombre,' ',u.apellido) AS guardia,
                   t.hora_inicio, t.hora_fin,
                   u.telefono
            FROM turnos t
            INNER JOIN usuarios u ON u.id = t.guardia_id
            WHERE t.sitio_id = :sitio AND t.fecha = CURDATE()
            ORDER BY t.hora_inicio
        ");
        $stmt->execute(['sitio' => $sitio_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * KPI: resumen operativo del guardia
     */
    public function kpiGuardia($guardia_id)
    {
        $stmt = $this->db->prepare("
            SELECT 
                (SELECT COUNT(*) FROM checadas 
                 WHERE guardia_id = :id AND validado_geo=1 AND validado_facial=1) AS checadas_validas,
                (SELECT COUNT(*) FROM incidencias 
                 WHERE guardia_id = :id AND prioridad='alta') AS incidencias_alta,
                (SELECT COUNT(*) FROM nomina_eventos 
                 WHERE guardia_id = :id AND tipo='extra') AS horas_extra,
                (SELECT COUNT(*) FROM turnos 
                 WHERE guardia_id = :id AND fecha = CURDATE()) AS turnos_hoy
        ");
        $stmt->execute(['id' => $guardia_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Registrar documentos legales del guardia (INE, contrato, foto base)
     */
    public function actualizarDocumentos($id, $foto_base, $docs)
    {
        $stmt = $this->db->prepare("
            UPDATE usuarios 
            SET foto_base = :foto,
                actualizado_en = NOW()
            WHERE id = :id AND rol='guardia'
        ");
        $stmt->execute([
            'id'   => $id,
            'foto' => $foto_base
        ]);

        // Aquí podrías insertar a tabla de documentos si la agregas
        return true;
    }

    /**
     * Verificar si un guardia tiene asignación activa
     */
    public function tieneAsignacionActiva($guardia_id)
    {
        $stmt = $this->db->prepare("
            SELECT id FROM asignaciones
            WHERE guardia_id = :id AND activo = 1
        ");
        $stmt->execute(['id' => $guardia_id]);
        return $stmt->fetchColumn() ? true : false;
    }

    /**
     * Activar / desactivar guardia
     */
    public function cambiarEstado($id, $activo)
    {
        $stmt = $this->db->prepare("
            UPDATE usuarios SET activo = :act WHERE id = :id AND rol='guardia'
        ");
        return $stmt->execute([
            'id'  => $id,
            'act' => $activo
        ]);
    }
}
