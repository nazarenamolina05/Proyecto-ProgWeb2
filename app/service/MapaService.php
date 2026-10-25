<?php

namespace app\service;

class MapaService
{
    private const MARGEN = 0.05;

    public function getUrl($latitud, $longitud)
    {
        if ($latitud === null || $longitud === null) {
            return "";
        }

        $lat = (float) $latitud;
        $lon = (float) $longitud;

        return "https://www.openstreetmap.org/export/embed.html?bbox="
            . ($lon - self::MARGEN) . "," . ($lat - self::MARGEN) . ","
            . ($lon + self::MARGEN) . "," . ($lat + self::MARGEN)
            . "&layer=mapnik&marker=" . $lat . "," . $lon;
    }
}