<?php

class MyDatabase
{
    private $conexion;

    public function __construct($host, $user, $pass, $db)
    {
        $this->conexion = new PDO(
            "mysql:host=$host;dbname=$db",
            $user,
            $pass
        );
    }

    public function queryOne(string $sql)
    {
        Log::info($sql);
        $resultado = $this->conexion->query($sql, PDO::FETCH_ASSOC);
        $fila = $resultado->fetch();

        return $fila === false ? null : $fila;
    }

    public function queryAll(string $sql)
    {
        Log::info($sql);
        $resultado = $this->conexion->query($sql, PDO::FETCH_ASSOC);

        return $resultado->fetchAll();
    }

    public function execute(string $sql)
    {
        Log::info($sql);

        return $this->conexion->exec($sql);
    }

    public function queryOnePrepared(string $sql, array $params = [])
    {
        Log::info($sql);

        $consulta = $this->conexion->prepare($sql);
        $consulta->execute($params);

        $fila = $consulta->fetch(PDO::FETCH_ASSOC);

        return $fila === false ? null : $fila;
    }

    public function executePrepared(string $sql, array $params = [])
    {
        Log::info($sql);

        $consulta = $this->conexion->prepare($sql);

        return $consulta->execute($params);
    }

    public function lastInsertId()
    {
        return $this->conexion->lastInsertId();
    }
}
