<?php
/**
 * ===========================================
 *  MIDDLEWARE DE AUTORIZACIÓN
 *  Proyecto: Seguridad Privada - ASX
 *  Archivo: core/middleware.php
 * ===========================================
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/response.php';
require_once __DIR__ . '/../config/constants.php';

class Middleware {

    /**
     * =======================================
     *  VERIFICAR TOKEN
     *  - Obtiene el token del header
     *  - Valida el usuario
     *  - Retorna el usuario autenticado
     * =======================================
     */
    public static function requireToken()
    {
        $headers = apache_request_headers();

        $token = null;

        // Header estándar: Authorization: Bearer XXXXX
        if (isset($headers['Authorization'])) {
            $partes = explode(" ", $headers['Authorization']);
            if (count($partes) == 2) {
                $token = trim($partes[1]);
            }
        }

        // Alternativa para Flutter (Authorization directa)
        if (!$token && isset($headers['authorization'])) {
            $partes = explode(" ", $headers['authorization']);
            if (count($partes) == 2) {
                $token = trim($partes[1]);
            }
        }

        if (!$token) {
            Response::error(MSG['ERROR_TOKEN'], [], 401, true);
        }

        $auth = new Auth();
        $user = $auth->validarToken($token);

        return $user;
    }


    /**
     * =======================================
     *  VERIFICAR ROLES PERMITIDOS
     * 
     *  Uso:
     *      Middleware::requireRole($user, [
     *          ROLES['ADMIN'],
     *          ROLES['SUPERVISOR']
     *      ]);
     * =======================================
     */
    public static function requireRole($user, $roles = [])
    {
        if (!isset($user['rol']) || !in_array($user['rol'], $roles)) {
            Response::error(MSG['ERROR_PERMISOS'], [], 403, true);
        }
    }


    /**
     * =======================================
     *  REQUIERE TOKEN + ROL
     *  (Versión compacta)
     * =======================================
     */
    public static function secure($roles = [])
    {
        $user = self::requireToken();

        if (!empty($roles)) {
            self::requireRole($user, $roles);
        }

        return $user;
    }
}
