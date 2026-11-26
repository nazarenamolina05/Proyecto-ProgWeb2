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

    if (isset($_GET['verified'])) {
        $data['mensaje'] = '¡Tu correo fue verificado! Ya podés iniciar sesión.';
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
        return;
    }

    if (!empty($usuario['no_verificado'])) {
        $this->render->renderiza("login", [
            "error" => "Primero tenés que verificar tu correo electrónico.",
            "email_pendiente" => $usuario["email"]
        ]);
        return;
    }

    if (isset($_SESSION['usuario_id']) && (int) $_SESSION['usuario_id'] !== (int) $usuario['id']) {
        $_SESSION = [];
    }

    session_regenerate_id(true);
    unset($_SESSION['editor_token']);
    $_SESSION['usuario_id'] = $usuario['id'];
    $_SESSION['usuario'] = $usuario['usuario'];
    $_SESSION['rol'] = $usuario['rol'];

    Redirect::to('/index.php?controller=lobby&method=show');
}


    public function logout()
    {
        $_SESSION = [];
        session_destroy();
        Redirect::toIndex();
    }
}
