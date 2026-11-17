<?php

namespace app\service;

class FotoService
{
    private const MAX_BYTES = 2097152; // 2 MB

    private const TIPOS = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    ];

    private $carpeta;

    public function __construct()
    {
        $this->carpeta = dirname(__DIR__, 2) . '/public/uploads/perfiles/';
    }

    /**
     * Devuelve:
     *  ['ok' => true,  'archivo' => null]         si no se subió nada
     *  ['ok' => true,  'archivo' => 'abc123.jpg'] si se guardó
     *  ['ok' => false, 'error'   => '...']        si hubo un problema
     */
    public function guardar($archivo): array
    {
        if (!is_array($archivo) || ($archivo['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return ['ok' => true, 'archivo' => null];
        }

        if ($archivo['error'] !== UPLOAD_ERR_OK) {
            return $this->error('No se pudo subir la foto. Probá con una imagen de hasta 2 MB.');
        }

        if ($archivo['size'] > self::MAX_BYTES) {
            return $this->error('La foto no puede pesar más de 2 MB.');
        }

        if (!is_uploaded_file($archivo['tmp_name'])) {
            return $this->error('El archivo recibido no es válido.');
        }

        $info = @getimagesize($archivo['tmp_name']);

        if ($info === false || !isset(self::TIPOS[$info['mime']])) {
            return $this->error('La foto debe ser una imagen JPG, PNG o WEBP.');
        }

        if (!is_dir($this->carpeta) && !mkdir($this->carpeta, 0775, true)) {
            return $this->error('No se pudo guardar la foto. Intentá nuevamente.');
        }

        $nombre = bin2hex(random_bytes(16)) . '.' . self::TIPOS[$info['mime']];

        if (!move_uploaded_file($archivo['tmp_name'], $this->carpeta . $nombre)) {
            return $this->error('No se pudo guardar la foto. Intentá nuevamente.');
        }

        return ['ok' => true, 'archivo' => $nombre];
    }

    public function borrar($nombre): void
    {
        if (empty($nombre)) {
            return;
        }

        $ruta = $this->carpeta . basename($nombre);

        if (is_file($ruta)) {
            @unlink($ruta);
        }
    }

    private function error($mensaje): array
    {
        return ['ok' => false, 'error' => $mensaje];
    }
}