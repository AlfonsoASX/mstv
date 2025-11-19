<?php
/**
 * ===========================================
 *  GEOLOCALIZACIÓN Y GEOCERCA
 *  Proyecto: Seguridad Privada - ASX
 *  Archivo: core/geo.php
 * ===========================================
 */

require_once __DIR__ . '/../config/config.php';

class Geo {

    /**
     * =======================================
     *  CÁLCULO DE DISTANCIA (Haversine)
     * =======================================
     * 
     * @return float distancia en metros
     */
    public static function calcularDistancia($lat1, $lng1, $lat2, $lng2)
    {
        $radioTierra = 6371000; // metros

        $lat1 = deg2rad($lat1);
        $lng1 = deg2rad($lng1);
        $lat2 = deg2rad($lat2);
        $lng2 = deg2rad($lng2);

        $difLat = $lat2 - $lat1;
        $difLng = $lng2 - $lng1;

        $a = sin($difLat / 2) * sin($difLat / 2) +
             cos($lat1) * cos($lat2) *
             sin($difLng / 2) * sin($difLng / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $radioTierra * $c; // metros
    }

    /**
     * =======================================
     *  VALIDAR GEOCERCA
     * =======================================
     * 
     * @param float $latGuardia
     * @param float $lngGuardia
     * @param float $latSitio
     * @param float $lngSitio
     * @param int|null $radioMetros
     * 
     * @return array [
     *      'dentro'   => true/false,
     *      'distancia'=> metros
     * ]
     */
    public static function validarGeocerca($latGuardia, $lngGuardia, $latSitio, $lngSitio, $radioMetros = null)
    {
        if ($radioMetros === null) {
            $radioMetros = DISTANCIA_MAXIMA_METROS; // definido en config.php
        }

        $distancia = self::calcularDistancia($latGuardia, $lngGuardia, $latSitio, $lngSitio);

        return [
            'dentro'   => ($distancia <= $radioMetros),
            'distancia'=> $distancia
        ];
    }
}
