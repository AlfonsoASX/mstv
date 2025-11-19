<?php
/**
 * ===========================================
 *  RECONOCIMIENTO FACIAL (1:1)
 *  Proyecto: Seguridad Privada - ASX
 *  Archivo: core/face.php
 * ===========================================
 *
 *  Este módulo NO hace reconocimiento facial por sí solo.
 *  Se conecta con:
 *   - Python + dlib/face_recognition
 *   - Python + OpenCV
 *   - TensorFlow Lite
 * 
 *  compareFaces($foto_registro, $foto_selfie)
 *  Retorna:
 *      true  -> si coincide
 *      false -> si NO coincide o hubo error
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/response.php';

class FaceRecognition
{
    /**
     * =======================================
     *  COMPARAR FOTOS (1:1)
     * =======================================
     * 
     * @param string $foto_registro  Ruta absoluta a la foto base
     * @param string $foto_selfie    Ruta absoluta a la selfie tomada
     * @return bool
     */
    public static function compareFaces($foto_registro, $foto_selfie)
    {
        // Validar existencia de archivos
        if (!file_exists($foto_registro) || !file_exists($foto_selfie)) {
            error_log("[FACE] Archivos no encontrados.");
            return false;
        }

        // Comando Python definido en config.php
        $cmd = FACIAL_CMD . ' ' . escapeshellarg($foto_registro) . ' ' . escapeshellarg($foto_selfie);

        // Ejecutar script
        $output = [];
        $return_var = 0;

        exec($cmd, $output, $return_var);

        if ($return_var !== 0) {
            error_log("[FACE] Error ejecutando Python: " . implode(" ", $output));
            return false;
        }

        // Python retorna un número (distancia o score)
        if (!isset($output[0])) {
            error_log("[FACE] Python no devolvió resultado.");
            return false;
        }

        $score = floatval(trim($output[0]));

        // Comparación según umbral
        return ($score <= FACIAL_THRESHOLD);
    }

    /**
     * =======================================
     *  GUARDAR SELFIE TEMPORAL
     * =======================================
     */
    public static function guardarSelfieBase64($base64, $rutaDestino)
    {
        $data = explode(',', $base64);
        $imgData = base64_decode(end($data));

        if (!$imgData) return false;

        return file_put_contents($rutaDestino, $imgData) !== false;
    }
}
