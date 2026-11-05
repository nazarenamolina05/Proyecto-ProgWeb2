<?php

namespace app\controller;

use Request;

class HomeController
{
    private $render;

    public function __construct($render)
    {
        $this->render = $render;
    }

    public function show()
    {
        $data = [
            "error"    => Request::get('login_error') !== null ? "Usuario o contraseña incorrectos." : "",
            "logueado" => isset($_SESSION['usuario']),
            "usuario"  => $_SESSION['usuario'] ?? null,
        ];

        $this->render->renderiza("home", $data);
    }
}