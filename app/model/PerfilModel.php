<?php

namespace app\model;

class PerfilModel
{
    private $database;

    public function __construct($database)
    {
        $this->database = $database;
    }

    public function getUsuario($id)
    {
        $id = (int) $id;

        $sql_usuario = "SELECT u.id, u.usuario, u.nombre_completo, u.anio_nacimiento, u.sexo,
                               u.pais, u.ciudad, u.latitud, u.longitud, u.foto,
                               COALESCE(SUM(p.puntaje), 0) AS puntaje_total,
                               COUNT(p.id) AS partidas_jugadas
                        FROM usuarios u
                        LEFT JOIN partidas p ON p.usuario_id = u.id
                        WHERE u.id = " . $id . "
                        GROUP BY u.id";

        return $this->database->queryOne($sql_usuario);
    }

    public function getPartidas($id)
    {
        $id = (int) $id;

        $sql_partidas = "SELECT id, puntaje, fecha
                     FROM partidas
                     WHERE usuario_id = " . $id . "
                     ORDER BY fecha DESC";

        return $this->database->queryAll($sql_partidas);
    }

}