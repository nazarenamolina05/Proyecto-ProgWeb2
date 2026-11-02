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
        $nombreCompleto = Request::post("nombre_completo", "");
        $anioNacimiento = Request::post("anio_nacimiento", "");
        $sexo = Request::post("sexo", "");
        $pais = Request::post("pais", "");
        $ciudad = Request::post("ciudad", "");
        $latitud = Request::post("latitud", "");
        $longitud = Request::post("longitud", "");

        if (
            !is_string($usuario) || !is_string($password) ||
            !is_string($confirmarPassword) || !is_string($email) ||
            !is_string($nombreCompleto) || !is_string($anioNacimiento) ||
            !is_string($sexo) || !is_string($pais) || !is_string($ciudad) ||
            !is_string($latitud) || !is_string($longitud)
        ) {
            $this->mostrarError("Los datos enviados no son válidos.");
            return;
        }

        $usuario = trim($usuario);
        $email = trim($email);
        $nombreCompleto = trim($nombreCompleto);
        $anioNacimiento = trim($anioNacimiento);
        $pais = trim($pais);
        $ciudad = trim($ciudad);
        $latitud = trim($latitud);
        $longitud = trim($longitud);

        $datos = [
            "usuario" => $usuario,
            "email" => $email,
            "nombre_completo" => $nombreCompleto,
            "anio_nacimiento" => $anioNacimiento,
            "sexo" => $sexo,
            "pais" => $pais,
            "ciudad" => $ciudad,
            "latitud" => $latitud,
            "longitud" => $longitud
        ];

        if ($usuario === "" || $password === "" || $confirmarPassword === "" ||
            $email === "" || $nombreCompleto === "" || $anioNacimiento === "" ||
            $sexo === "" || $pais === "" || $ciudad === "") {
            $this->mostrarError("Completá todos los campos.", $datos);
            return;
        }

        if (!$this->model->usuarioValido($usuario)) {
            $this->mostrarError("El usuario debe tener entre 3 y 50 caracteres. Usá letras sin acentos, números o guion bajo.", $datos);
            return;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->mostrarError("Ingresá un correo electrónico válido.", $datos);
            return;
        }

        if (strlen($password) < 8) {
            $this->mostrarError("La contraseña debe tener al menos 8 caracteres.", $datos);
            return;
        }

        if ($password !== $confirmarPassword) {
            $this->mostrarError("Las contraseñas no coinciden.", $datos);
            return;
        }

        $errorPerfil = $this->validarDatosPerfil($nombreCompleto, $anioNacimiento, $sexo, $pais, $ciudad, $latitud, $longitud);

        if ($errorPerfil !== null) {
            $this->mostrarError($errorPerfil, $datos);
            return;
        }

        try {
            if ($this->model->existeUsuario($usuario)) {
                $this->mostrarError("Ese nombre de usuario ya está registrado.", $datos);
                return;
            }

            if ($this->model->existeEmail($email)) {
                $this->mostrarError("Ese correo electrónico ya está registrado.", $datos);
                return;
            }

            $this->model->registrar(
                $usuario, $password, $email, $nombreCompleto,
                $anioNacimiento, $sexo, $pais, $ciudad, $latitud, $longitud
            );

            $codigo = (string) random_int(100000, 999999);
            $this->model->guardarCodigo($email, $codigo);

            try {
                $enviado = $this->mailService->enviarCodigo($email, $usuario, $codigo);
            } catch (\Throwable $e) {
                error_log("Error al enviar el mail: " . $e->getMessage());
                $enviado = false;
            }

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
            $this->mostrarError("No se pudo completar el registro. Intentá nuevamente.", $datos);
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

    private function mostrarError($error, $datos = [])
    {
        $sexo = $datos["sexo"] ?? "";
        $datos["sexo_masculino"] = $sexo === "Masculino";
        $datos["sexo_femenino"] = $sexo === "Femenino";
        $datos["sexo_nodice"] = $sexo === "Prefiero no cargarlo";
        $datos["error"] = $error;

        $this->render->renderiza("registro", $datos);
    }
    private function mostrarVerificacionError($email, $error)
    {
        $this->render->renderiza("verificacion", [
            "email" => $email,
            "error" => $error
        ]);
    }

    private function validarDatosPerfil($nombreCompleto, $anioNacimiento, $sexo, $pais, $ciudad, $latitud, $longitud)
    {
        if (strlen($nombreCompleto) > 100 || strlen($pais) > 50 || strlen($ciudad) > 50) {
            return "Alguno de los datos supera la longitud permitida.";
        }

        if (!ctype_digit($anioNacimiento)
            || (int) $anioNacimiento < 1900
            || (int) $anioNacimiento > (int) date("Y") - 5) {
            return "Ingresá un año de nacimiento válido.";
        }

        $sexosPermitidos = ["Masculino", "Femenino", "Prefiero no cargarlo"];

        if (!in_array($sexo, $sexosPermitidos, true)) {
            return "Elegí una opción válida de sexo.";
        }

        if (!is_numeric($latitud) || !is_numeric($longitud)
            || (float) $latitud < -90 || (float) $latitud > 90
            || (float) $longitud < -180 || (float) $longitud > 180) {
            return "Seleccioná tu ubicación en el mapa.";
        }

        return null;
    }



}
