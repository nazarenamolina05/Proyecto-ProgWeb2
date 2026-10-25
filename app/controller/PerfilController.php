<?php

namespace app\controller;

use Request;

class PerfilController
{
    private $model;
    private $render;

    public function __construct($model, $render)
    {
        $this->model = $model;
        $this->render = $render;
    }

    public function show()
    {
        $id = (int) Request::get('id');
        $usuario = $this->model->getUsuario($id);

        if (!$usuario) {
            $data["error"] = "No encontramos a ese jugador.";
        } else {
            $data = $usuario;
            $data["error"] = "";

            $partidas = $this->model->getPartidas($id);
            foreach ($partidas as $clave => $partida) {
                $partidas[$clave]["fecha_formateada"] = date('d/m/Y H:i', strtotime($partida['fecha']));
            }
            $data["partidas"] = $partidas;

            $data["mapa_url"] = "";
            if ($usuario["latitud"] !== null && $usuario["longitud"] !== null) {
                $lat = (float) $usuario["latitud"];
                $lon = (float) $usuario["longitud"];
                $margen = 0.05;

                $data["mapa_url"] = "https://www.openstreetmap.org/export/embed.html?bbox="
                    . ($lon - $margen) . "," . ($lat - $margen) . ","
                    . ($lon + $margen) . "," . ($lat + $margen)
                    . "&layer=mapnik&marker=" . $lat . "," . $lon;
            }
        }

        $this->render->renderiza("perfil", $data);
    }
}