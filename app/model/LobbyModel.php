<?php

namespace app\model;

class LobbyModel
{
    private $database;

    public function __construct($database)
    {
        $this->database = $database;
    }

    // Ranking global: UNA fila por jugador, con su MEJOR puntaje de partida.
    public function obtenerRanking(int $limite = 10): array
    {
        $limite = max(1, $limite);

        $sql = "
            SELECT
                u.id,
                u.usuario,
                u.foto,
                MAX(p.puntaje) AS mejor_puntaje,
                COUNT(p.id) AS partidas_jugadas
            FROM partidas p
            INNER JOIN usuarios u ON u.id = p.usuario_id
            WHERE u.rol = 'jugador'
              AND u.habilitado = 1
            GROUP BY u.id, u.usuario, u.foto
            ORDER BY mejor_puntaje DESC, u.usuario ASC
            LIMIT $limite
        ";

        return $this->database->queryAll($sql);
    }

    // Mejor puntaje de partida de un jugador (null si todavía no jugó).
    public function obtenerMejorPuntaje(int $usuarioId): ?int
    {
        $fila = $this->database->queryOnePrepared(
            "SELECT MAX(puntaje) AS mejor FROM partidas WHERE usuario_id = ?",
            [$usuarioId]
        );

        return ($fila === null || $fila['mejor'] === null)
            ? null
            : (int) $fila['mejor'];
    }

    // Posición = 1 + cantidad de jugadores con un mejor puntaje mayor.
    // Los empatados comparten posición.
    public function obtenerPosicion(int $mejorPuntaje): int
    {
        $sql = "
            SELECT COUNT(*) AS por_encima
            FROM (
                SELECT p.usuario_id, MAX(p.puntaje) AS mejor
                FROM partidas p
                INNER JOIN usuarios u ON u.id = p.usuario_id
                WHERE u.rol = 'jugador'
                  AND u.habilitado = 1
                GROUP BY p.usuario_id
            ) ranking
            WHERE ranking.mejor > ?
        ";

        $fila = $this->database->queryOnePrepared($sql, [$mejorPuntaje]);

        return ((int) $fila['por_encima']) + 1;
    }

    // Últimas partidas del usuario con el resultado de cada una.
    public function obtenerHistorial(int $usuarioId, int $limite = 10): array
    {
        $limite = max(1, $limite);

        $sql = "
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
            ORDER BY p.fecha DESC, p.id DESC
            LIMIT $limite
        ";

        return $this->database->queryAllPrepared($sql, [$usuarioId]);
    }
}