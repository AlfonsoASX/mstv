<?php
/**
 * ===============================================
 * MODELO: Incidencias
 * Gestión integral de incidencias operativas
 * Basado en el SQL real
 * ===============================================
 */

require_once __DIR__ . '/../config/database.php';

class Incidencias
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Crear incidencia desde app guardia
     */
    public function crear($guardia_id, $sitio_id, $tipo, $prioridad, $descripcion, $foto = null)
    {
        $stmt = $this->db->prepare("
            INSERT INTO incidencias
                (guardia_id, sitio_id, tipo, prioridad, descripcion, foto)
            VALUES
                (:g, :s, :t, :p, :d, :f)
        ");
        return $stmt->execute([
            'g' => $guardia_id,
            's' => $sitio_id,
            't' => $tipo,
            'p' => $prioridad,
            'd' => $descripcion,
            'f' => $foto
        ]);
    }

    /**
     * Listar incidencias (filtros opcionales)
     */
    public function listar($estado = null, $sitio_id = null, $prioridad = null)
    {
        $sql = "
            SELECT i.*, 
                   CONCAT(u.nombre,' ',u.apellido) AS guardia,
                   s.nombre AS sitio
            FROM incidencias i
            INNER JOIN usuarios u ON u.id = i.guardia_id
            INNER JOIN sitios s ON s.id = i.sitio_id
            WHERE 1=1
        ";
        $params = [];

        if ($estado) {
            $sql .= " AND i.estado = :estado";
            $params['estado'] = $estado;
        }
        if ($sitio_id) {
            $sql .= " AND i.sitio_id = :sitio";
            $params['sitio'] = $sitio_id;
        }
        if ($prioridad) {
            $sql .= " AND i.prioridad = :prioridad";
            $params['prioridad'] = $prioridad;
        }

        $sql .= " ORDER BY i.creado_en DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener detalle completo de una incidencia
     */
    public function obtener($id)
    {
        $stmt = $this->db->prepare("
            SELECT i.*, 
                   CONCAT(u.nombre,' ',u.apellido) AS guardia,
                   s.nombre AS sitio
            FROM incidencias i
            INNER JOIN usuarios u ON u.id = i.guardia_id
            INNER JOIN sitios s ON s.id = i.sitio_id
            WHERE i.id = :id
        ");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Cambiar estado: pendiente → atendido → cerrado
     */
    public function cambiarEstado($id, $estado)
    {
        $stmt = $this->db->prepare("
            UPDATE incidencias
            SET estado = :estado
            WHERE id = :id
        ");
        return $stmt->execute([
            'estado' => $estado,
            'id'     => $id
        ]);
    }

    /**
     * Registrar seguimiento/comentario (auditoría)
     */
    public function agregarComentario($incidencia_id, $usuario_id, $comentario)
    {
        $stmt = $this->db->prepare("
            INSERT INTO bitacora (usuario_id, accion, entidad, entidad_id, detalles)
            VALUES (:u, 'comentario_incidencia', 'incidencias', :id, :coment)
        ");
        return $stmt->execute([
            'u'      => $usuario_id,
            'id'     => $incidencia_id,
            'coment' => $comentario
        ]);
    }

    /**
     * KPI operativos de incidencias (por sitio o global)
     */
    public function kpi($sitio_id = null)
    {
        $sql = "
            SELECT
                SUM(CASE WHEN prioridad='alta' THEN 1 ELSE 0 END) AS urgentes,
                SUM(CASE WHEN estado='pendiente' THEN 1 ELSE 0 END) AS pendientes,
                SUM(CASE WHEN estado='atendido' THEN 1 ELSE 0 END) AS atendidas,
                SUM(CASE WHEN estado='cerrado' THEN 1 ELSE 0 END) AS cerradas
            FROM incidencias
            WHERE 1=1
        ";

        if ($sitio_id) {
            $sql .= " AND sitio_id = :sitio";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['sitio' => $sitio_id]);
        } else {
            $stmt = $this->db->query($sql);
        }

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener historial de acciones desde bitácora
     */
    public function obtenerHistorial($incidencia_id)
    {
        $stmt = $this->db->prepare("
            SELECT b.*, CONCAT(u.nombre,' ',u.apellido) AS usuario
            FROM bitacora b
            INNER JOIN usuarios u ON u.id = b.usuario_id
            WHERE b.entidad='incidencias' AND b.entidad_id = :id
            ORDER BY b.creado_en DESC
        ");
        $stmt->execute(['id' => $incidencia_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
