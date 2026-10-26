<?php

namespace app\controller;

use Request;

class PerfilController
{
    private $model;
    private $render;
    private $mapa;
    private $qr;

    public function __construct($model, $render, $mapa, $qr)
    {
        $this->model = $model;
        $this->render = $render;
        $this->mapa = $mapa;
        $this->qr = $qr;
    }

    public function show()
    {
        $id = (int) Request::get('id');
        $usuario = $this->model->getUsuario($id);

        if (!$usuario) {
            $data = $this->armarDatosError();
        } else {
            $data = $this->armarDatosPerfil($usuario);
        }

        $this->render->renderiza("perfil", $data);
    }

    private function armarDatosError()
    {
        return ["error" => "No encontramos a ese jugador."];
    }

    private function armarDatosPerfil($usuario)
    {
        $data = $usuario;
        $data["error"] = "";
        $data["partidas"] = $this->getPartidasFormateadas($usuario["id"]);
        $data["mapa_url"] = $this->mapa->getUrl($usuario["latitud"], $usuario["longitud"]);
        $data["url_perfil"] = $this->armarUrlPerfil($usuario["id"]);
        $data["qr_url"] = $this->qr->getUrl($data["url_perfil"]);

        return $data;
    }

    private function getPartidasFormateadas($id)
    {
        $partidas = $this->model->getPartidas($id);

        foreach ($partidas as $clave => $partida) {
            $partidas[$clave]["fecha_formateada"] = date('d/m/Y H:i', strtotime($partida['fecha']));
        }

        return $partidas;
    }

    private function armarUrlPerfil($id)
    {
        $protocolo = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $carpeta = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');

        return $protocolo . "://" . $_SERVER['HTTP_HOST'] . $carpeta . "/perfil/show?id=" . (int) $id;
    }
}