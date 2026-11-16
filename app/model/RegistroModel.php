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

    public function registrar($usuario, $password, $email, $nombreCompleto, $anioNacimiento, $sexo, $pais, $ciudad, $latitud, $longitud, $foto = null)
    {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        $latitud  = ($latitud  === '' || $latitud  === null) ? null : (float) $latitud;
        $longitud = ($longitud === '' || $longitud === null) ? null : (float) $longitud;

        $sql = "INSERT INTO usuarios
(usuario, password, email, verificado,
 nombre_completo, anio_nacimiento, sexo, pais, ciudad, latitud, longitud, foto)
VALUES (?, ?, ?, 0, ?, ?, ?, ?, ?, ?, ?, ?)";

        return $this->database->executePrepared(
            $sql,
            [
                $usuario,
                $passwordHash,
                $email,
                $nombreCompleto,
                (int) $anioNacimiento,
                $sexo,
                $pais,
                $ciudad,
                $latitud,
                $longitud,
                $foto
            ]
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
    \Log::info("DIAGNOSTICO VERIFICACION: " . ($usuario === null ? "USUARIO NULL" : "USUARIO ENCONTRADO"));

    if ($usuario === null) {
        return ["estado" => "usuario_no_encontrado"];
    }

    if (empty($usuario["codigo_verificacion"])) {
        return ["estado" => "sin_codigo"];
    }

    $sql = "SELECT expiracion_codigo >= NOW() AS vigente
            FROM usuarios
            WHERE id = ?";

    $resultado = $this->database->queryOnePrepared(
        $sql,
        [$usuario["id"]]
    );

    if (!$resultado || (int) $resultado["vigente"] !== 1) {
        return ["estado" => "codigo_vencido"];
    }

    if (!password_verify($codigo, $usuario["codigo_verificacion"])) {
        return ["estado" => "codigo_no_coincide"];
    }

    $sql = "UPDATE usuarios
            SET verificado = 1,
                codigo_verificacion = NULL,
                expiracion_codigo = NULL
            WHERE id = ?";

    $actualizado = $this->database->executePrepared(
        $sql,
        [$usuario["id"]]
    );

    return [
        "estado" => $actualizado ? "ok" : "error_actualizacion"
    ];
}


    public function obtenerEmailPendiente($email)
    {
        $sql = "SELECT id FROM usuarios
                WHERE email = ? AND verificado = 0";

        return $this->database->queryOnePrepared($sql, [$email]);
    }
}
