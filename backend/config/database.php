<?php
/**
 * ===========================================
 *  CONEXIÓN A BASE DE DATOS (PDO)
 *  Proyecto: Seguridad Privada - ASX
 *  Archivo: database.php
 * ===========================================
 */

require_once __DIR__ . '/config.php';

class Database {

    private static $instance = null;
    private $conn;

    private function __construct() 
    {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";

            $this->conn = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_PERSISTENT         => true,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
            ]);

        } catch (PDOException $e) {

            // En modo producción solo se registra el error, no se muestra
            $msg = "Error de conexión a la BD: " . $e->getMessage();

            if (DEBUG_MODE) {
                die($msg);
            } else {
                error_log($msg);
                die("Error interno del servidor.");
            }
        }
    }

    /**
     *  Singleton: obtiene instancia única
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    /**
     *  Retorna la conexión PDO
     */
    public function getConnection()
    {
        return $this->conn;
    }
}

