<?php

namespace app\model;

class LoginModel
{
    private $database;

    public function __construct($database)
    {
        $this->database = $database;
    }

    public function autenticar($usuario, $password)
    {
        if (!is_string($usuario) || !is_string($password)) {
            return null;
        }

        $sql = "SELECT id, usuario, password, email, verificado, rol
                FROM usuarios
                WHERE usuario = ? AND habilitado = 1";

        $datosUsuario = $this->database->queryOnePrepared(
            $sql,
            [$usuario]
        );

        if ($datosUsuario === null) {
            return null;
        }

        $passwordGuardada = $datosUsuario["password"];


        if (strlen($passwordGuardada) === 32 &&
            hash_equals($passwordGuardada, md5($password))) {

            $passwordValida = true;

            // Actualizamos la contraseña al formato seguro actual.
            $nuevoHash = password_hash($password, PASSWORD_DEFAULT);

            $this->database->executePrepared(
                "UPDATE usuarios SET password = ? WHERE id = ?",
                [$nuevoHash, $datosUsuario["id"]]
            );

        } else {
            $passwordValida = password_verify(
                $password,
                $passwordGuardada
            );
        }

        if (!$passwordValida) {
            return null;
        }

        if ((int) $datosUsuario["verificado"] !== 1) {
            return [
                "no_verificado" => true,
                "email" => $datosUsuario["email"]
            ];
        }

        return [
            "id" => $datosUsuario["id"],
            "usuario" => $datosUsuario["usuario"],
            "rol" => $datosUsuario["rol"]
        ];
    }
}
