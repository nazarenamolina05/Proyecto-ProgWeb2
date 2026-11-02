<?php

namespace app\model;

class PartidaModel
{
    private $database;

    public function __construct($database)
    {
        $this->database = $database;
    }

    public function obtenerPreguntaAleatoria(): ?array
    {
        $sql = "
            SELECT
                id,
                enunciado,
                opcion_1,
                opcion_2,
                opcion_3,
                correcta
            FROM preguntas
            ORDER BY RAND()
            LIMIT 1
        ";

        $fila = $this->database->queryOne($sql);

        if ($fila === null) {
            return null;
        }

        return [
            'id' => (int) $fila['id'],
            'texto' => $fila['enunciado'],
            'opciones' => [
                1 => $fila['opcion_1'],
                2 => $fila['opcion_2'],
                3 => $fila['opcion_3']
            ],
            'correcta' => (int) $fila['correcta']
        ];
    }

    public function esOpcionValida(array $pregunta, int $opcionId): bool
    {
        return isset($pregunta['opciones'][$opcionId]);
    }

    public function esCorrecta(array $pregunta, int $opcionId): bool
    {
        return $pregunta['correcta'] === $opcionId;
    }
}