<?php

namespace app\controller;

use Request;
use Redirect;

class RegistroController
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
        $this->render->renderiza("registro");
    }

    public function registrar()
    {
        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            Redirect::to("/index.php?controller=registro&method=show");
        }

        $usuario = Request::post("usuario", "");
        $password = Request::post("password", "");
        $confirmarPassword = Request::post("confirmar_password", "");

        if (
            !is_string($usuario) ||
            !is_string($password) ||
            !is_string($confirmarPassword)
        ) {
            $this->mostrarError("Los datos enviados no son válidos.");
            return;
        }

        $usuario = trim($usuario);

        if (
            $usuario === "" ||
            $password === "" ||
            $confirmarPassword === ""
        ) {
            $this->mostrarError(
                "Completá todos los campos.",
                $usuario
            );
            return;
        }

        if (!$this->model->usuarioValido($usuario)) {
            $this->mostrarError(
                "El usuario debe tener entre 3 y 50 caracteres. Usá letras sin acentos, números o guion bajo.",
                $usuario
            );
            return;
        }

        if (strlen($password) < 8) {
            $this->mostrarError(
                "La contraseña debe tener al menos 8 caracteres.",
                $usuario
            );
            return;
        }

        if ($password !== $confirmarPassword) {
            $this->mostrarError(
                "Las contraseñas no coinciden.",
                $usuario
            );
            return;
        }

        try {
            if ($this->model->existeUsuario($usuario)) {
                $this->mostrarError(
                    "Ese nombre de usuario ya está registrado.",
                    $usuario
                );
                return;
            }

            $this->model->registrar($usuario, $password);

        } catch (\PDOException $e) {
            $this->mostrarError(
                "No se pudo registrar el usuario. Intentá nuevamente.",
                $usuario
            );
            return;
        }

        Redirect::toIndex();
    }

    private function mostrarError($error, $usuario = "")
    {
        $this->render->renderiza("registro", [
            "error" => $error,
            "usuario" => $usuario
        ]);
    }
}