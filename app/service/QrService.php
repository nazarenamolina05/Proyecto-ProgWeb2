<?php

namespace app\service;

class QrService
{
    private const TAMANIO = "200x200";

    public function getUrl($contenido)
    {
        return "https://api.qrserver.com/v1/create-qr-code/?size=" . self::TAMANIO
            . "&data=" . urlencode($contenido);
    }
}