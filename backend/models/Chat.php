<?php
/**
 * ===============================================
 * MODELO: Chat (reconstruido según SQL real)
 * Chat operativo vinculado a sitio (chat grupal)
 * ===============================================
 */

require_once __DIR__ . '/../config/database.php';

class Chat
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Registrar un mensaje en el chat de un sitio
     */
    public function enviarMensaje($sitio_id, $emisor_id, $mensaje)
    {
        $stmt = $this->db->prepare("
            INSERT INTO chat_mensajes (sitio_id, emisor_id, mensaje)
            VALUES (:sitio, :emisor, :mensaje)
        ");

        return $stmt->execute([
            'sitio'   => $sitio_id,
            'emisor'  => $emisor_id,
            'mensaje' => $mensaje
        ]);
    }

    /**
     * Obtener mensajes del chat de un sitio (chat grupal)
     */
    public function obtenerMensajesPorSitio($sitio_id, $limite = 50)
    {
        $stmt = $this->db->prepare("
            SELECT m.id,
                   m.mensaje,
                   m.moderado,
                   m.creado_en,
                   CONCAT(u.nombre,' ',u.apellido) AS emisor_nombre,
                   u.rol AS emisor_rol
            FROM chat_mensajes m
            INNER JOIN usuarios u ON u.id = m.emisor_id
            WHERE m.sitio_id = :sitio
            ORDER BY m.creado_en DESC
            LIMIT :lim
        ");

        $stmt->bindValue(':sitio', $sitio_id, PDO::PARAM_INT);
        $stmt->bindValue(':lim', (int)$limite, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Listar últimos chats por sitio (para panel admin)
     */
    public function obtenerChatsRecientes($limite = 20)
    {
        $stmt = $this->db->prepare("
            SELECT s.id AS sitio_id,
                   s.nombre AS sitio_nombre,
                   COUNT(m.id) AS total_mensajes,
                   MAX(m.creado_en) AS ultimo_mensaje
            FROM chat_mensajes m
            INNER JOIN sitios s ON s.id = m.sitio_id
            GROUP BY s.id, s.nombre
            ORDER BY ultimo_mensaje DESC
            LIMIT :lim
        ");

        $stmt->bindValue(':lim', (int)$limite, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Moderar (marcar como revisado) un mensaje
     */
    public function moderarMensaje($id)
    {
        $stmt = $this->db->prepare("
            UPDATE chat_mensajes
            SET moderado = 1
            WHERE id = :id
        ");
        return $stmt->execute(['id' => $id]);
    }

    /**
     * Eliminar mensaje del chat
     */
    public function eliminarMensaje($id)
    {
        $stmt = $this->db->prepare("DELETE FROM chat_mensajes WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }
}
