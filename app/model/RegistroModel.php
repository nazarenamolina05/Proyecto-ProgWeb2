<?php

namespace app\model;

class RegistroModel
{
    private $database;

    public function __construct($database)
    {
        $this->database = $database;
    }

    public function usuarioValido($usuario)
    {
        return is_string($usuario)
            && preg_match('/\A[a-zA-Z0-9_]{3,50}\z/', $usuario) === 1;
    }

    public function existeUsuario($usuario)
    {
        $this->validarUsuario($usuario);

        $sql = "SELECT id
                FROM usuarios
                WHERE usuario = '$usuario'";

        return $this->database->queryOne($sql) !== null;
    }

    public function registrar($usuario, $password)
    {
        $this->validarUsuario($usuario);

        $passwordHash = md5($password);

        $sql = "INSERT INTO usuarios (usuario, password)
                VALUES ('$usuario', '$passwordHash')";

        return $this->database->execute($sql);
    }

    private function validarUsuario($usuario)
    {
        if (!$this->usuarioValido($usuario)) {
            throw new \InvalidArgumentException(
                "El nombre de usuario no es válido."
            );
        }
    }
}