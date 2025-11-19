<?php
/**
 * ===============================================
 * MODELO: Sitios
 * Gestión de sitios, geocercas, asignaciones y monitoreo
 * ===============================================
 */

require_once __DIR__ . '/../config/database.php';

class Sitios
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Listar todos los sitios con su cliente y estado
     */
    public function listar()
    {
        $stmt = $this->db->query("
            SELECT s.id, s.nombre, s.direccion, s.lat, s.lng, s.radio_metros, s.activo,
                   c.nombre AS cliente
            FROM sitios s
            LEFT JOIN clientes c ON c.id = s.cliente_id
            ORDER BY s.nombre ASC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener detalle completo de un sitio
     */
    public function obtener($id)
    {
        $stmt = $this->db->prepare("
            SELECT s.*, 
                   c.nombre AS cliente,
                   c.telefono AS cliente_telefono,
                   c.email AS cliente_email
            FROM sitios s
            LEFT JOIN clientes c ON c.id = s.cliente_id
            WHERE s.id = :id
        ");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Crear sitio
     */
    public function crear($cliente_id, $nombre, $direccion, $lat, $lng, $radio_metros)
    {
        $stmt = $this->db->prepare("
            INSERT INTO sitios 
                (cliente_id, nombre, direccion, lat, lng, radio_metros)
            VALUES
                (:cliente, :nombre, :direccion, :lat, :lng, :radio)
        ");
        return $stmt->execute([
            'cliente'   => $cliente_id,
            'nombre'    => $nombre,
            'direccion' => $direccion,
            'lat'       => $lat,
            'lng'       => $lng,
            'radio'     => $radio_metros
        ]);
    }

    /**
     * Modificar datos de sitio
     */
    public function actualizar($id, $nombre, $direccion, $lat, $lng, $radio_metros)
    {
        $stmt = $this->db->prepare("
            UPDATE sitios
            SET nombre = :nombre,
                direccion = :direccion,
                lat = :lat,
                lng = :lng,
                radio_metros = :radio
            WHERE id = :id
        ");
        return $stmt->execute([
            'nombre' => $nombre,
            'direccion' => $direccion,
            'lat' => $lat,
            'lng' => $lng,
            'radio' => $radio_metros,
            'id' => $id
        ]);
    }

    /**
     * Activar / desactivar sitio
     */
    public function cambiarEstado($id, $activo)
    {
        $stmt = $this->db->prepare("
            UPDATE sitios SET activo = :act WHERE id = :id
        ");
        return $stmt->execute([
            'id'  => $id,
            'act' => $activo
        ]);
    }

    /**
     * Obtener guardias asignados a un sitio
     */
    public function guardiasAsignados($sitio_id)
    {
        $stmt = $this->db->prepare("
            SELECT u.id, CONCAT(u.nombre,' ',u.apellido) AS nombre,
                   u.telefono, u.email
            FROM asignaciones a
            INNER JOIN usuarios u ON u.id = a.guardia_id
            WHERE a.sitio_id = :sitio AND a.activo = 1
        ");
        $stmt->execute(['sitio' => $sitio_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener incidencias abiertas en un sitio
     */
    public function incidenciasActivas($sitio_id)
    {
        $stmt = $this->db->prepare("
            SELECT i.*, CONCAT(u.nombre,' ',u.apellido) AS guardia
            FROM incidencias i
            INNER JOIN usuarios u ON u.id = i.guardia_id
            WHERE i.sitio_id = :sitio
              AND i.estado != 'cerrado'
            ORDER BY i.creado_en DESC
        ");
        $stmt->execute(['sitio' => $sitio_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Validar si una coordenada está dentro del radio permitido
     * (Geocerca: usa fórmula Haversine)
     */
    public function validarGeocerca($sitio_id, $lat, $lng)
    {
        $stmt = $this->db->prepare("
            SELECT lat, lng, radio_metros
            FROM sitios
            WHERE id = :id
        ");
        $stmt->execute(['id' => $sitio_id]);
        $sitio = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$sitio) return false;

        $distancia = $this->calcularDistancia(
            $lat, $lng, $sitio['lat'], $sitio['lng']
        );

        return $distancia <= $sitio['radio_metros'];
    }

    /**
     * Fórmula de Haversine para distancia en metros
     */
    private function calcularDistancia($lat1, $lon1, $lat2, $lon2)
    {
        $radioTierra = 6371000; // metros
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $lat1 = deg2rad($lat1);
        $lat2 = deg2rad($lat2);

        $a = sin($dLat/2) * sin($dLat/2) +
             sin($dLon/2) * sin($dLon/2) *
             cos($lat1) * cos($lat2);

        $c = 2 * atan2(sqrt($a), sqrt(1-$a));

        return $radioTierra * $c;
    }
}
