<?php

namespace app\model;

class PartidaModel
{
    private const PREGUNTAS_EVALUACION = 10;
    private const RESPUESTAS_RECIENTES = 20;
    private const MIN_RESPUESTAS_PREGUNTA = 5;

    private $database;

    public function __construct($database)
    {
        $this->database = $database;
    }

    public function obtenerCategorias(): array
    {
        return $this->database->queryAll(
            "SELECT id, nombre FROM categorias ORDER BY
                CASE
                    WHEN nombre LIKE 'Artistas%' THEN 1
                    WHEN nombre LIKE 'Canciones%' THEN 2
                    WHEN nombre LIKE 'Premios%' THEN 3
                    WHEN nombre LIKE 'Curiosidades%' THEN 4
                    ELSE 5
                END, nombre"
        );
    }

    public function obtenerCategoria(int $id): ?array
    {
        return $this->database->queryOnePrepared(
            'SELECT id, nombre FROM categorias WHERE id = ?',
            [$id]
        );
    }

    // El rendimiento se calcula por jugador y categoría (últimas 20 respuestas).
    public function obtenerProgresoUsuario(string $jugador, int $categoriaId): array
    {
        $respuestas = $this->database->queryAllPrepared(
            'SELECT up.correcta
             FROM usuario_pregunta up
             INNER JOIN preguntas p ON p.id = up.pregunta_id
             WHERE up.jugador = ? AND p.categoria_id = ?
               AND up.correcta IS NOT NULL
             ORDER BY up.id DESC LIMIT ' . self::RESPUESTAS_RECIENTES,
            [$jugador, $categoriaId]
        );

        $total = count($respuestas);
        $correctas = array_sum(array_column($respuestas, 'correcta'));
        $porcentaje = $total > 0 ? $correctas * 100 / $total : 0;
        $nivel = 'PRINCIPIANTE';

        if ($total >= self::PREGUNTAS_EVALUACION) {
            if ($porcentaje > 70) {
                $nivel = 'AVANZADO';
            } elseif ($porcentaje >= 30) {
                $nivel = 'INTERMEDIO';
            }
        }

        $texto = rtrim(rtrim(number_format($porcentaje, 2, '.', ''), '0'), '.');
        return [
            'nivel_usuario' => $nivel,
            'clase_nivel' => strtolower($nivel),
            'porcentaje' => $texto,
            'texto_progreso' => $total === 0
                ? 'Todavía no jugaste en esta categoría'
                : ($total < self::PREGUNTAS_EVALUACION
                    ? 'Evaluación inicial: ' . $total . ' de ' . self::PREGUNTAS_EVALUACION . ' respuestas'
                    : ''),
            'total_respuestas' => $total,
            'en_evaluacion' => $total < self::PREGUNTAS_EVALUACION,
            'respuestas_evaluacion' => min($total, self::PREGUNTAS_EVALUACION),
            'meta_evaluacion' => self::PREGUNTAS_EVALUACION
        ];
    }

    public function obtenerNivelUsuario(string $jugador, int $categoriaId): string
    {
        return $this->obtenerProgresoUsuario($jugador, $categoriaId)['nivel_usuario'];
    }

    public function obtenerDificultadSegunNivel(string $nivel): string
    {
        return match ($nivel) {
            'AVANZADO' => 'DIFICIL',
            'INTERMEDIO' => 'INTERMEDIO',
            default => 'FACIL'
        };
    }

    public function registrarPreguntaVista(string $jugador, int $preguntaId): int
    {
        $this->database->executePrepared(
            'INSERT INTO usuario_pregunta (jugador, pregunta_id) VALUES (?, ?)',
            [$jugador, $preguntaId]
        );
        return (int) $this->database->lastInsertId();
    }

    public function obtenerPreguntasVistas(string $jugador, int $categoriaId): array
    {
        $vistas = $this->database->queryAllPrepared(
            'SELECT DISTINCT up.pregunta_id
             FROM usuario_pregunta up JOIN preguntas p ON p.id = up.pregunta_id
             WHERE up.jugador = ? AND p.categoria_id = ? AND p.activa = 1',
            [$jugador, $categoriaId]
        );

        return array_map('intval', array_column($vistas, 'pregunta_id'));
    }

    public function registrarRespuesta(int $historialId, bool $esCorrecta): void
    {
        $this->database->executePrepared(
            'UPDATE usuario_pregunta SET correcta = ? WHERE id = ? AND correcta IS NULL',
            [$esCorrecta ? 1 : 0, $historialId]
        );

        // Actualiza la columna de dificultad de la pregunta recién respondida.
        $this->database->executePrepared(
            "UPDATE preguntas p
             JOIN usuario_pregunta intento ON intento.pregunta_id = p.id
             JOIN (
                 SELECT pregunta_id, COUNT(correcta) AS total, SUM(correcta) AS aciertos
                 FROM usuario_pregunta GROUP BY pregunta_id
             ) e ON e.pregunta_id = p.id
             SET p.dificultad = CASE
                 WHEN e.total < ? THEN NULL
                 WHEN e.aciertos * 100 > e.total * 70 THEN 'FACIL'
                 WHEN e.aciertos * 100 < e.total * 30 THEN 'DIFICIL'
                 ELSE 'INTERMEDIO'
             END
             WHERE intento.id = ?",
            [self::MIN_RESPUESTAS_PREGUNTA, $historialId]
        );
    }

    public function entregarPregunta(int $categoriaId, string $dificultad, string $jugador, array $preguntasVistas): ?array
    {
        return $this->database->enTransaccion(function () use ($categoriaId, $dificultad, $jugador, $preguntasVistas) {
            $pregunta = $this->obtenerPreguntaAleatoria($categoriaId, $dificultad, $jugador, $preguntasVistas);
            if ($pregunta === null) {
                return null;
            }

            return [
                'pregunta' => $pregunta,
                'historial_id' => $this->registrarPreguntaVista($jugador, $pregunta['id']),
            ];
        });
    }

    public function obtenerPreguntaAleatoria(
        int $categoriaId,
        string $dificultad,
        string $jugador,
        array $preguntasVistas = []
    ): ?array {
        $progreso = $this->obtenerProgresoUsuario($jugador, $categoriaId);
        $enEvaluacion = $progreso['en_evaluacion'] ? 1 : 0;
        $dificultad = $this->obtenerDificultadSegunNivel($progreso['nivel_usuario']);

        $excluirVistas = '';
        if ($preguntasVistas !== []) {
            $parametros = implode(', ', array_fill(0, count($preguntasVistas), '?'));
            $excluirVistas = "AND q.id NOT IN ($parametros)";
        }

        // El historial determina la dificultad. Solo las preguntas de la partida actual se excluyen.
        $sql = "
            SELECT q.* FROM (
                SELECT p.*,
                    CASE
                        WHEN COALESCE(e.total, 0) < ? THEN NULL
                        WHEN e.aciertos * 100 > e.total * 70 THEN 'FACIL'
                        WHEN e.aciertos * 100 < e.total * 30 THEN 'DIFICIL'
                        ELSE 'INTERMEDIO'
                    END AS dificultad_real
                FROM preguntas p
                LEFT JOIN (
                    SELECT pregunta_id, COUNT(correcta) AS total, SUM(correcta) AS aciertos
                    FROM usuario_pregunta GROUP BY pregunta_id
                ) e ON e.pregunta_id = p.id
            ) q
            WHERE q.categoria_id = ? AND q.activa = 1 $excluirVistas
            ORDER BY
                CASE
                    WHEN ? = 1 THEN 0
                    WHEN q.dificultad_real = ? THEN 0
                    WHEN q.dificultad_real IS NULL THEN 1
                    ELSE 2 + ABS(
                        FIELD(q.dificultad_real, 'FACIL', 'INTERMEDIO', 'DIFICIL')
                        - FIELD(?, 'FACIL', 'INTERMEDIO', 'DIFICIL')
                    )
                END ASC,
                RAND()
            LIMIT 1";

        $fila = $this->database->queryOnePrepared(
            $sql,
            [self::MIN_RESPUESTAS_PREGUNTA, $categoriaId, ...$preguntasVistas, $enEvaluacion, $dificultad, $dificultad]
        );
        if ($fila === null) {
            return null;
        }

        return [
            'id' => (int) $fila['id'],
            'texto' => $fila['enunciado'],
            'imagen' => $fila['imagen'] ?? null,
            'dificultad' => $fila['dificultad_real'] ?? 'SIN CLASIFICAR',
            'opciones' => [1 => $fila['opcion_1'], 2 => $fila['opcion_2'], 3 => $fila['opcion_3']],
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

    public function guardarPartida(
        int $usuarioId,
        int $puntaje,
        int $correctas,
        int $totalPreguntas,
        string $dificultad,
        int $categoriaId
    ): bool {
        return $this->database->executePrepared(
            'INSERT INTO partidas (usuario_id, puntaje, correctas, total_preguntas, dificultad, categoria_id)
             VALUES (?, ?, ?, ?, ?, ?)',
            [$usuarioId, $puntaje, $correctas, $totalPreguntas, $dificultad, $categoriaId]
        );
    }
}
