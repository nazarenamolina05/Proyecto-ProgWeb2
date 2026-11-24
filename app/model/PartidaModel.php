<?php

namespace app\model;

class PartidaModel
{
    private $database;

    public function __construct($database)
    {
        $this->database = $database;
    }

    public function obtenerCategorias(): array
    {
        return $this->database->queryAll(
            "SELECT id, nombre FROM categorias ORDER BY nombre"
        );
    }

    public function obtenerCategoria(int $id): ?array
    {
        return $this->database->queryOnePrepared(
            "SELECT id, nombre FROM categorias WHERE id = ?",
            [$id]
        );
    }

    public function obtenerPreguntaAleatoria(
        int $categoriaId,
        string $dificultad = 'INTERMEDIO'
    ): ?array {
        $sql = "
            SELECT
                id,
                enunciado,
                opcion_1,
                opcion_2,
                opcion_3,
                correcta,
                imagen,
                dificultad
            FROM preguntas
            WHERE categoria_id = ?
              AND dificultad = ?
            ORDER BY RAND()
            LIMIT 1
        ";

        $fila = $this->database->queryOnePrepared(
            $sql,
            [$categoriaId, $dificultad]
        );

        if ($fila === null) {
            return null;
        }

        return [
            'id' => (int) $fila['id'],
            'texto' => $fila['enunciado'],
            'imagen' => $fila['imagen'] ?? null,
            'dificultad' => $fila['dificultad'],
            'opciones' => [
                1 => $fila['opcion_1'],
                2 => $fila['opcion_2'],
                3 => $fila['opcion_3']
            ],
            'correcta' => (int) $fila['correcta']
        ];
    }

    public function esOpcionValida(
        array $pregunta,
        int $opcionId
    ): bool {
        return isset($pregunta['opciones'][$opcionId]);
    }

    public function esCorrecta(
        array $pregunta,
        int $opcionId
    ): bool {
        return $pregunta['correcta'] === $opcionId;
    }

public function guardarPartida(
    int $usuarioId,
    int $puntaje,
    int $correctas,
    int $totalPreguntas,
    string $dificultad,
    int $categoriaId
): bool {
    $sql = "
        INSERT INTO partidas (
            usuario_id,
            puntaje,
            correctas,
            total_preguntas,
            dificultad,
            categoria_id
        ) VALUES (?, ?, ?, ?, ?, ?)
    ";

    return $this->database->executePrepared($sql, [
        $usuarioId,
        $puntaje,
        $correctas,
        $totalPreguntas,
        $dificultad,
        $categoriaId
    ]);
}

}
