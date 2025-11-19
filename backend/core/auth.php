<?php
/**
 * ===========================================
 *  AUTENTICACIÓN Y TOKENS
 *  Proyecto: Seguridad Privada - ASX
 *  Archivo: core/auth.php
 * ===========================================
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/response.php';

class Auth {

    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * ===============================
     *  LOGIN (usuario + contraseña)
     * ===============================
     */
    public function login($usuario, $password)
    {
        $sql = "SELECT * FROM usuarios WHERE usuario = :usuario AND activo = 1 LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['usuario' => $usuario]);
        $user = $stmt->fetch();

        if (!$user) {
            return Response::error(MSG['ERROR_CREDENCIALES']);
        }

        if (!password_verify($password, $user['password'])) {
            return Response::error(MSG['ERROR_CREDENCIALES']);
        }

        // generar token nuevo
        $token = $this->crearTokenSeguro();

        // guardar token + fecha de expiración
        $this->guardarToken($user['id'], $token);

        unset($user['password']); // nunca se envía la contraseña

        return Response::success("Login exitoso", [
            'token' => $token,
            'usuario' => $user
        ]);
    }

    /**
     * ===============================
     *  GENERAR TOKEN SEGURO
     * ===============================
     */
    private function crearTokenSeguro()
    {
        return bin2hex(random_bytes(TOKEN_LENGTH / 2));
    }

    /**
     * ===============================
     *  GUARDAR TOKEN
     * ===============================
     */
    private function guardarToken($user_id, $token)
    {
        $expira = date('Y-m-d H:i:s', time() + (TOKEN_EXPIRACION_HORAS * 3600));

        $sql = "UPDATE usuarios 
                SET api_token = :token, actualizado_en = NOW()
                WHERE id = :id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'token' => $token,
            'id'    => $user_id
        ]);
    }

    /**
     * ===============================
     *  VALIDAR TOKEN EN API
     * ===============================
     */
    public function validarToken($token)
    {
        if (!$token) {
            Response::error(MSG['ERROR_TOKEN'], [], 401, true);
        }

        $sql = "SELECT * FROM usuarios WHERE api_token = :token AND activo = 1 LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['token' => $token]);

        $user = $stmt->fetch();

        if (!$user) {
            Response::error(MSG['ERROR_TOKEN'], [], 401, true);
        }

        return $user;
    }

    /**
     * ===============================
     *  VALIDAR ROL
     * ===============================
     */
    public function requiereRol($usuario, $roles = [])
    {
        if (!in_array($usuario['rol'], $roles)) {
            Response::error(MSG['ERROR_PERMISOS'], [], 403, true);
        }
    }

    /**
     * ===============================
     *  LOGOUT (Invalidar token)
     * ===============================
     */
    public function logout($token)
    {
        $sql = "UPDATE usuarios SET api_token = NULL WHERE api_token = :token";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['token' => $token]);

        return Response::success("Sesión cerrada.");
    }
}

