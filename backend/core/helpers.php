<?php
/**
 * ===========================================
 *  FUNCIONES AUXILIARES (HELPERS)
 *  Proyecto: Seguridad Privada - ASX
 *  Archivo: core/helpers.php
 * ===========================================
 */

require_once __DIR__ . '/../config/config.php';

class Helpers {

    /* -------------------------------------------
       Sanitizar Strings
    ------------------------------------------- */
    public static function cleanString($str)
    {
        if ($str === null) return null;

        $str = trim($str);
        $str = strip_tags($str);
        $str = htmlspecialchars($str, ENT_QUOTES, 'UTF-8');

        return $str;
    }

    /* -------------------------------------------
       Sanitizar Arrays Recursivo
    ------------------------------------------- */
    public static function cleanArray($array)
    {
        $clean = [];

        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $clean[$key] = self::cleanArray($value);
            } else {
                $clean[$key] = self::cleanString($value);
            }
        }

        return $clean;
    }

    /* -------------------------------------------
       Validar si un valor está vacío
    ------------------------------------------- */
    public static function isEmpty($value)
    {
        return (!isset($value) || $value === '' || $value === null);
    }

    /* -------------------------------------------
       Generar nombre único de archivo
    ------------------------------------------- */
    public static function generarNombreArchivo($extension = "jpg")
    {
        return time() . "_" . bin2hex(random_bytes(5)) . "." . $extension;
    }

    /* -------------------------------------------
       Guardar imagen desde base64
    ------------------------------------------- */
    public static function guardarBase64($base64, $rutaDestino)
    {
        if (strpos($base64, ',') !== false) {
            $base64 = explode(',', $base64)[1]; 
        }

        $img = base64_decode($base64);

        if (!$img) return false;

        return file_put_contents($rutaDestino, $img) !== false;
    }

    /* -------------------------------------------
       Registrar logs del sistema
    ------------------------------------------- */
    public static function log($mensaje, $archivo = "system.log")
    {
        $ruta = LOGS_PATH . $archivo;

        $texto = "[" . date('Y-m-d H:i:s') . "] " . $mensaje . PHP_EOL;

        file_put_contents($ruta, $texto, FILE_APPEND);
    }

    /* -------------------------------------------
       Verificar si un array tiene todos los campos requeridos
    ------------------------------------------- */
    public static function validarCampos($data, $camposRequeridos = [])
    {
        foreach ($camposRequeridos as $campo) {
            if (!isset($data[$campo]) || empty($data[$campo])) {
                return false;
            }
        }
        return true;
    }

    /* -------------------------------------------
       Detectar extensión desde base64
    ------------------------------------------- */
    public static function extensionBase64($base64)
    {
        if (preg_match("/^data:image\/(\w+);base64,/i", $base64, $m)) {
            return strtolower($m[1]);
        }
        return "jpg";
    }

    /* -------------------------------------------
       Normalizar JSON recibido desde Flutter
    ------------------------------------------- */
    public static function getJsonInput()
    {
        $json = file_get_contents("php://input");
        return json_decode($json, true) ?: [];
    }
}

