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

    $sql_usuario = "
        SELECT
            u.id,
            u.usuario,
            u.nombre_completo,
            u.anio_nacimiento,
            u.sexo,
            u.pais,
            u.ciudad,
            u.latitud,
            u.longitud,
            COALESCE(SUM(p.puntaje), 0) AS puntaje_total,
            COUNT(p.id) AS partidas_jugadas,
            COALESCE(SUM(p.correctas), 0) AS respuestas_correctas,
            COALESCE(SUM(p.total_preguntas), 0) AS respuestas_totales
        FROM usuarios u
        LEFT JOIN partidas p ON p.usuario_id = u.id
        WHERE u.id = ?
        GROUP BY u.id
    ";

    return $this->database->queryOnePrepared(
        $sql_usuario,
        [$id]
    );
}





public function getPartidas($id)
{
    $id = (int) $id;

    $sql_partidas = "
        SELECT
            p.id,
            p.puntaje,
            p.fecha,
            p.correctas,
            p.total_preguntas,
            p.dificultad,
            c.nombre AS categoria
        FROM partidas p
        LEFT JOIN categorias c ON c.id = p.categoria_id
        WHERE p.usuario_id = ?
        ORDER BY p.fecha DESC
    ";

    return $this->database->queryAllPrepared(
        $sql_partidas,
        [$id]
    );
}



    public function actualizar($id, $nombreCompleto, $anioNacimiento, $sexo, $pais, $ciudad, $latitud, $longitud)
    {
        $latitud  = ($latitud  === '' || $latitud  === null) ? null : (float) $latitud;
        $longitud = ($longitud === '' || $longitud === null) ? null : (float) $longitud;

        $sql = "UPDATE usuarios
        SET nombre_completo = ?, anio_nacimiento = ?, sexo = ?,
            pais = ?, ciudad = ?, latitud = ?, longitud = ?
        WHERE id = ?";

        return $this->database->executePrepared($sql, [
            $nombreCompleto, (int) $anioNacimiento, $sexo,
            $pais, $ciudad, $latitud, $longitud, (int) $id
        ]);
    }

}
