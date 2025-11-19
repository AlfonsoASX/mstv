<?php
/**
 * ===============================================
 * MODELO: Checadas
 * Registro de asistencia con validación GPS y reconocimiento facial
 * ===============================================
 */

require_once __DIR__ . '/../config/database.php';

class Checadas
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Registrar checada
     */
    public function registrarChecada($guardia_id, $sitio_id, $tipo, $lat, $lng, $foto, $comentario = null, $validadoGeo = 0, $validadoFacial = 0, $turno_id = null)
    {
        $stmt = $this->db->prepare("
            INSERT INTO checadas (guardia_id, sitio_id, tipo, fecha_hora, lat, lng, foto,
                                  validado_geo, validado_facial, comentario, turno_id)
            VALUES (:guardia, :sitio, :tipo, NOW(), :lat, :lng, :foto,
                    :vgeo, :vfacial, :coment, :turno)
        ");

        return $stmt->execute([
            'guardia' => $guardia_id,
            'sitio'   => $sitio_id,
            'tipo'    => $tipo,
            'lat'     => $lat,
            'lng'     => $lng,
            'foto'    => $foto,
            'vgeo'    => $validadoGeo,
            'vfacial' => $validadoFacial,
            'coment'  => $comentario,
            'turno'   => $turno_id
        ]);
    }

    /**
     * Obtener checadas recientes por guardia
     */
    public function obtenerPorGuardia($guardia_id, $limite = 50)
    {
        $stmt = $this->db->prepare("
            SELECT c.*, s.nombre AS sitio_nombre
            FROM checadas c
            LEFT JOIN sitios s ON s.id = c.sitio_id
            WHERE c.guardia_id = :id
            ORDER BY c.fecha_hora DESC
            LIMIT :lim
        ");
        $stmt->bindValue(':id', $guardia_id, PDO::PARAM_INT);
        $stmt->bindValue(':lim', (int)$limite, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener checadas por sitio
     */
    public function obtenerPorSitio($sitio_id, $limite = 50)
    {
        $stmt = $this->db->prepare("
            SELECT c.*, CONCAT(u.nombre,' ',u.apellido) AS guardia,
                   u.rol, s.nombre AS sitio_nombre
            FROM checadas c
            INNER JOIN usuarios u ON u.id = c.guardia_id
            INNER JOIN sitios s ON s.id = c.sitio_id
            WHERE c.sitio_id = :sitio
            ORDER BY c.fecha_hora DESC
            LIMIT :lim
        ");
        $stmt->bindValue(':sitio', $sitio_id, PDO::PARAM_INT);
        $stmt->bindValue(':lim', (int)$limite, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener detalle de una checada
     */
    public function obtenerDetalle($id)
    {
        $stmt = $this->db->prepare("
            SELECT c.*, 
                   CONCAT(u.nombre,' ',u.apellido) AS guardia,
                   s.nombre AS sitio_nombre,
                   t.tipo AS turno_tipo
            FROM checadas c
            LEFT JOIN usuarios u ON u.id = c.guardia_id
            LEFT JOIN sitios s ON s.id = c.sitio_id
            LEFT JOIN turnos t ON t.id = c.turno_id
            WHERE c.id = :id
        ");
        $stmt->execute(['id' => $id]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Validar si está dentro de geocerca (radio en metros)
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
     * Calcular distancia entre 2 coordenadas (metros)
     * Fórmula de Haversine
     */
    private function calcularDistancia($lat1, $lon1, $lat2, $lon2)
    {
        $radioTierra = 6371000; // m
        $lat1 = deg2rad($lat1);
        $lon1 = deg2rad($lon1);
        $lat2 = deg2rad($lat2);
        $lon2 = deg2rad($lon2);
        
        $dlat = $lat2 - $lat1;
        $dlon = $lon2 - $lon1;

        $a = sin($dlat/2) * sin($dlat/2) +
             cos($lat1) * cos($lat2) *
             sin($dlon/2) * sin($dlon/2);
             
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        return $radioTierra * $c;
    }

    /**
     * Vincular checadas válidas a nómina_eventos
     */
    public function generarEventoNomina($guardia_id, $turno_id, $checada_id, $horas, $tipo)
    {
        $stmt = $this->db->prepare("
            INSERT INTO nomina_eventos (guardia_id, turno_id, checada_id, horas, tipo)
            VALUES (:guardia, :turno, :checada, :horas, :tipo)
        ");

        return $stmt->execute([
            'guardia' => $guardia_id,
            'turno'   => $turno_id,
            'checada' => $checada_id,
            'horas'   => $horas,
            'tipo'    => $tipo
        ]);
    }
}
