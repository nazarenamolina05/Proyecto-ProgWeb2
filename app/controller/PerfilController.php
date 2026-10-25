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
        }

        $this->render->renderiza("perfil", $data);
    }
}