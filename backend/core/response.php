<?php
/**
 * ===========================================
 *  RESPUESTAS JSON ESTÁNDAR
 *  Proyecto: Seguridad Privada - ASX
 *  Archivo: core/response.php
 * ===========================================
 */

class Response {

    /**
     * =======================================
     *  RESPUESTA GENERAL JSON
     * =======================================
     */
    public static function json($status, $message = "", $data = [], $httpCode = 200, $exit = true)
    {
        http_response_code($httpCode);
        header('Content-Type: application/json; charset=utf-8');

        $payload = [
            'status'  => $status,
            'message' => $message,
            'data'    => $data
        ];

        echo json_encode($payload, JSON_UNESCAPED_UNICODE);

        if ($exit) exit;
    }


    /**
     * =======================================
     *  RESPUESTA SUCCESS
     * =======================================
     */
    public static function success($message = "OK", $data = [], $httpCode = 200, $exit = false)
    {
        return self::json('success', $message, $data, $httpCode, $exit);
    }


    /**
     * =======================================
     *  RESPUESTA ERROR
     * =======================================
     */
    public static function error($message = "Error", $data = [], $httpCode = 400, $exit = false)
    {
        return self::json('error', $message, $data, $httpCode, $exit);
    }
}
