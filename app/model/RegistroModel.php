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
        $sql = "SELECT id FROM usuarios WHERE usuario = ?";

        return $this->database->queryOnePrepared($sql, [$usuario]) !== null;
    }

    public function existeEmail($email)
    {
        $sql = "SELECT id FROM usuarios WHERE email = ?";

        return $this->database->queryOnePrepared($sql, [$email]) !== null;
    }

    public function registrar($usuario, $password, $email)
    {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        $sql = "INSERT INTO usuarios
                (usuario, password, email, verificado)
                VALUES (?, ?, ?, 0)";

        return $this->database->executePrepared(
            $sql,
            [$usuario, $passwordHash, $email]
        );
    }

    public function guardarCodigo($email, $codigo)
    {
        $codigoHash = password_hash($codigo, PASSWORD_DEFAULT);

        $sql = "UPDATE usuarios
                SET codigo_verificacion = ?,
                    expiracion_codigo = DATE_ADD(NOW(), INTERVAL 15 MINUTE)
                WHERE email = ? AND verificado = 0";

        return $this->database->executePrepared(
            $sql,
            [$codigoHash, $email]
        );
    }

    public function verificarCodigo($email, $codigo)
    {
        $sql = "SELECT id, codigo_verificacion, expiracion_codigo
                FROM usuarios
                WHERE email = ? AND verificado = 0";

        $usuario = $this->database->queryOnePrepared($sql, [$email]);

        if ($usuario === null || empty($usuario["codigo_verificacion"])) {
            return false;
        }

        if (
    empty($usuario["expiracion_codigo"]) ||
    strtotime($usuario["expiracion_codigo"]) === false ||
    strtotime($usuario["expiracion_codigo"]) < time()
) {
    return false;
}

        if (!password_verify($codigo, $usuario["codigo_verificacion"])) {
            return false;
        }

        $sql = "UPDATE usuarios
                SET verificado = 1,
                    codigo_verificacion = NULL,
                    expiracion_codigo = NULL
                WHERE id = ?";

        return $this->database->executePrepared(
            $sql,
            [$usuario["id"]]
        );
    }

    public function obtenerEmailPendiente($email)
    {
        $sql = "SELECT id FROM usuarios
                WHERE email = ? AND verificado = 0";

        return $this->database->queryOnePrepared($sql, [$email]);
    }
}
