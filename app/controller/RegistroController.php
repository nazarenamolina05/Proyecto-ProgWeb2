<?php

namespace app\controller;

use Request;
use Redirect;

class RegistroController
{
    private $model;
    private $render;
    private $mailService;

    public function __construct($model, $render, $mailService)
    {
        $this->model = $model;
        $this->render = $render;
        $this->mailService = $mailService;
    }

    public function show()
    {
        $this->render->renderiza("registro");
    }

    public function registrar()
    {
        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            Redirect::to("/index.php?controller=registro&method=show");
            return;
        }

        $usuario = Request::post("usuario", "");
        $password = Request::post("password", "");
        $confirmarPassword = Request::post("confirmar_password", "");
        $email = Request::post("email", "");

        if (
            !is_string($usuario) ||
            !is_string($password) ||
            !is_string($confirmarPassword) ||
            !is_string($email)
        ) {
            $this->mostrarError("Los datos enviados no son válidos.");
            return;
        }

        $usuario = trim($usuario);
        $email = trim($email);

        if ($usuario === "" || $password === "" ||
            $confirmarPassword === "" || $email === "") {
            $this->mostrarError("Completá todos los campos.", $usuario, $email);
            return;
        }

        if (!$this->model->usuarioValido($usuario)) {
            $this->mostrarError("El usuario debe tener entre 3 y 50 caracteres. Usá letras sin acentos, números o guion bajo.", $usuario, $email);
            return;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->mostrarError("Ingresá un correo electrónico válido.", $usuario, $email);
            return;
        }

        if (strlen($password) < 8) {
            $this->mostrarError("La contraseña debe tener al menos 8 caracteres.", $usuario, $email);
            return;
        }

        if ($password !== $confirmarPassword) {
            $this->mostrarError("Las contraseñas no coinciden.", $usuario, $email);
            return;
        }

        try {
            if ($this->model->existeUsuario($usuario)) {
                $this->mostrarError("Ese nombre de usuario ya está registrado.", $usuario, $email);
                return;
            }

            if ($this->model->existeEmail($email)) {
                $this->mostrarError("Ese correo electrónico ya está registrado.", $usuario, $email);
                return;
            }

            $this->model->registrar($usuario, $password, $email);

            $codigo = (string) random_int(100000, 999999);

            $this->model->guardarCodigo($email, $codigo);

            $enviado = $this->mailService->enviarCodigo(
                $email,
                $usuario,
                $codigo
            );

            $mensaje = $enviado
                ? "Te enviamos un código de verificación. Revisá tu correo."
                : "Tu cuenta fue creada, pero no pudimos enviar el correo. Podés intentar reenviar el código.";

            $this->render->renderiza("verificacion", [
                "email" => $email,
                "mensaje" => $mensaje,
                "error" => !$enviado
            ]);

        } catch (\Throwable $e) {
            error_log("Error en el registro: " . $e->getMessage());
            $this->mostrarError("No se pudo completar el registro. Intentá nuevamente.", $usuario, $email);
        }
    }

    public function verificar()
    {
        $email = Request::post("email", "");
        $codigo = Request::post("codigo", "");

        if (!is_string($email) || !is_string($codigo)) {
            $this->mostrarVerificacionError("", "Los datos enviados no son válidos.");
            return;
        }

        $email = trim($email);
        $codigo = trim($codigo);

        if (!filter_var($email, FILTER_VALIDATE_EMAIL) ||
            !preg_match('/\A[0-9]{6}\z/', $codigo)) {
            $this->mostrarVerificacionError($email, "Ingresá un correo y un código de seis dígitos válidos.");
            return;
        }

        try {
            if ($this->model->verificarCodigo($email, $codigo)) {
                Redirect::to("/index.php?controller=login&method=show&verified=1");
                return;
            }
        } catch (\Throwable $e) {
            error_log("Error al verificar cuenta: " . $e->getMessage());
        }

        $this->mostrarVerificacionError(
            $email,
            "El código es incorrecto o venció. Solicitá uno nuevo."
        );
    }

    private function mostrarError($error, $usuario = "", $email = "")
    {
        $this->render->renderiza("registro", [
            "error" => $error,
            "usuario" => $usuario,
            "email" => $email
        ]);
    }

    private function mostrarVerificacionError($email, $error)
    {
        $this->render->renderiza("verificacion", [
            "email" => $email,
            "error" => $error
        ]);
    }
}
