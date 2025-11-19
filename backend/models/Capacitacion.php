<?php
/**
 * ===============================================
 * MODELO: Capacitacion
 * Gestión de videos y seguimiento de capacitación (onboarding)
 * ===============================================
 */

require_once __DIR__ . '/../config/database.php';

class Capacitacion {

    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Obtener todos los videos de capacitación (por rol opcional)
     */
    public function listarVideos($rol = null) {
        $sql = "SELECT * FROM videos_capacitacion";
        $params = [];

        if ($rol) {
            $sql .= " WHERE perfil_asignado = :rol";
            $params['rol'] = $rol;
        }

        $sql .= " ORDER BY created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Registrar video nuevo
     */
    public function agregarVideo($titulo, $descripcion, $archivo, $perfil_asignado) {
        $stmt = $this->db->prepare("
            INSERT INTO videos_capacitacion (titulo, descripcion, archivo, perfil_asignado)
            VALUES (:titulo, :descripcion, :archivo, :perfil)
        ");
        return $stmt->execute([
            'titulo' => $titulo,
            'descripcion' => $descripcion,
            'archivo' => $archivo,
            'perfil' => $perfil_asignado
        ]);
    }

    /**
     * Eliminar video
     */
    public function eliminarVideo($id) {
        $stmt = $this->db->prepare("DELETE FROM videos_capacitacion WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    /**
     * Registrar visualización de video
     */
    public function registrarVisto($usuario_id, $video_id) {
        // Evitar duplicados
        $stmt = $this->db->prepare("
            SELECT id FROM capacitacion_completada 
            WHERE usuario_id = :u AND video_id = :v
        ");
        $stmt->execute(['u' => $usuario_id, 'v' => $video_id]);

        if ($stmt->fetch()) return true;

        // Insertar registro
        $stmt = $this->db->prepare("
            INSERT INTO capacitacion_completada (usuario_id, video_id)
            VALUES (:u, :v)
        ");
        return $stmt->execute(['u' => $usuario_id, 'v' => $video_id]);
    }

    /**
     * Obtener lista de cumplimiento de un video
     * (quién ya lo vio y quién no)
     */
    public function cumplimientoPorVideo($video_id) {
        $sql = "
            SELECT u.id, CONCAT(u.nombre,' ',u.apellido) AS nombre,
                   CASE WHEN c.id IS NOT NULL THEN 1 ELSE 0 END AS visto,
                   c.created_at AS fecha_visto
            FROM usuarios u
            LEFT JOIN capacitacion_completada c
                ON c.usuario_id = u.id AND c.video_id = :id
            WHERE u.rol IN ('guardia','supervisor','admin','rh')
            ORDER BY nombre ASC
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $video_id]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener videos pendientes por usuario
     */
    public function videosPendientes($usuario_id, $rol) {
        $stmt = $this->db->prepare("
            SELECT v.*, 
                   CASE WHEN c.id IS NULL THEN 0 ELSE 1 END AS visto
            FROM videos_capacitacion v
            LEFT JOIN capacitacion_completada c
                ON c.video_id = v.id AND c.usuario_id = :u
            WHERE v.perfil_asignado = :rol
        ");
        $stmt->execute(['u' => $usuario_id, 'rol' => $rol]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener porcentaje de cumplimiento de un usuario
     */
    public function porcentajeCumplimiento($usuario_id, $rol) {
        $stmt = $this->db->prepare("
            SELECT 
              (SELECT COUNT(*) FROM videos_capacitacion WHERE perfil_asignado = :rol) AS total,
              (SELECT COUNT(*) FROM capacitacion_completada c
               JOIN videos_capacitacion v ON v.id=c.video_id
               WHERE c.usuario_id = :u AND v.perfil_asignado = :rol) AS vistos
        ");
        $stmt->execute(['rol' => $rol, 'u' => $usuario_id]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
