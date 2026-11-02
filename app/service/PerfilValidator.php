<?php
namespace app\service;

class PerfilValidator
{
    public function leer(array $post): array
    {
        return [
            'nombre_completo'  => trim($post['nombre_completo'] ?? ''),
            'anio_nacimiento'  => trim($post['anio_nacimiento'] ?? ''),
            'sexo'             => $post['sexo'] ?? '',
            'pais'             => trim($post['pais'] ?? ''),
            'ciudad'           => trim($post['ciudad'] ?? ''),
            'latitud'          => $post['latitud'] ?? '',
            'longitud'         => $post['longitud'] ?? '',
        ];
    }

    public function validar(array $d): array
    {
        $e = [];
        if ($d['nombre_completo'] === '') {
            $e[] = 'El nombre completo es obligatorio.';
        }
        $anio = (int) $d['anio_nacimiento'];
        if ($anio < 1900 || $anio > (int) date('Y')) {
            $e[] = 'Año de nacimiento inválido.';
        }
        return $e;
    }

    public function normalizar(array $d): array
    {
        $d['anio_nacimiento'] = (int) $d['anio_nacimiento'];
        $d['latitud']  = $d['latitud']  !== '' ? (float) $d['latitud']  : null;
        $d['longitud'] = $d['longitud'] !== '' ? (float) $d['longitud'] : null;
        return $d;
    }

    public function paraVista(array $d): array
    {
        $d['sexo_masculino'] = ($d['sexo'] ?? '') === 'Masculino';
        $d['sexo_femenino']  = ($d['sexo'] ?? '') === 'Femenino';
        $d['sexo_nodice']    = ($d['sexo'] ?? '') === 'Prefiero no decirlo';
        return $d;
    }
}