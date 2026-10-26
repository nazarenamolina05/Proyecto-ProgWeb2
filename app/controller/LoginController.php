<?php

namespace app\controller;

use Redirect;
use Request;

class LoginController
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
        $data = [];

        if (isset($_GET['login_error'])) {
            $data['error'] = 'Usuario o contraseña incorrectos.';
        }

        $this->render->renderiza("login", $data);
    }

    public function login()
    {
        $usuario = $this->model->autenticar(
            Request::post('usuario'),
            Request::post('password')
        );

        if ($usuario === null) {
            Redirect::to('/index.php?controller=login&method=show&login_error=1');
        }

        $_SESSION['usuario_id'] = $usuario['id'];
        $_SESSION['usuario'] = $usuario['usuario'];

        Redirect::toIndex();
    }

    public function logout()
    {
        session_destroy();
        Redirect::toIndex();
    }
}
