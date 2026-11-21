<?php

namespace app\model;

class ContribucionModel
{
    private $database;

    public function __construct($database)
    {
        $this->database = $database;
    }

    
    // REPORTAR UNA PREGUNTA
    

    public function guardarReporte(
        int $usuarioId,
        int $preguntaId,
        ?string $motivo = null
    ): bool {

        // Verificar que la pregunta exista
        $pregunta = $this->database->queryOnePrepared(
            "SELECT id FROM preguntas WHERE id = ?",
            [$preguntaId]
        );

        if ($pregunta === null) {
            return false;
        }

        // Evitar que un usuario reporte
        // dos veces la misma pregunta
        $reporteExistente = $this->database->queryOnePrepared(
            "SELECT id
             FROM reportes_preguntas
             WHERE usuario_id = ?
               AND pregunta_id = ?",
            [$usuarioId, $preguntaId]
        );

        if ($reporteExistente !== null) {
            return false;
        }

        // El motivo es opcional
        $motivo = trim($motivo ?? '');

        if ($motivo === '') {
            $motivo = null;
        }

        return $this->database->executePrepared(
            "INSERT INTO reportes_preguntas
                (usuario_id, pregunta_id, motivo)
             VALUES (?, ?, ?)",
            [$usuarioId, $preguntaId, $motivo]
        );
    }

   
    // SUGERIR UNA PREGUNTA
   

    public function guardarSugerencia(
        int $usuarioId,
        string $enunciado,
        string $opcion1,
        string $opcion2,
        string $opcion3,
        int $correcta,
        int $categoriaId,
        string $dificultad
    ): bool {

        $enunciado = trim($enunciado);
        $opcion1 = trim($opcion1);
        $opcion2 = trim($opcion2);
        $opcion3 = trim($opcion3);

        // Validar campos obligatorios
        if (
            $enunciado === '' ||
            $opcion1 === '' ||
            $opcion2 === '' ||
            $opcion3 === '' ||
            mb_strlen($enunciado, 'UTF-8') > 255 ||
            mb_strlen($opcion1, 'UTF-8') > 150 ||
            mb_strlen($opcion2, 'UTF-8') > 150 ||
            mb_strlen($opcion3, 'UTF-8') > 150
        ) {
            return false;
        }

        // Validar la respuesta correcta
        if (!in_array($correcta, [1, 2, 3], true)) {
            return false;
        }

        // Validar dificultad
        if (!in_array(
            $dificultad,
            ['FACIL', 'INTERMEDIO', 'DIFICIL'],
            true
        )) {
            return false;
        }

        // Verificar que exista la categoría
        $categoria = $this->database->queryOnePrepared(
            "SELECT id FROM categorias WHERE id = ?",
            [$categoriaId]
        );

        if ($categoria === null) {
            return false;
        }

        return $this->database->executePrepared(
            "INSERT INTO preguntas_sugeridas
                (
                    usuario_id,
                    enunciado,
                    opcion_1,
                    opcion_2,
                    opcion_3,
                    correcta,
                    categoria_id,
                    dificultad
                )
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $usuarioId,
                $enunciado,
                $opcion1,
                $opcion2,
                $opcion3,
                $correcta,
                $categoriaId,
                $dificultad
            ]
        );
    }

    // OBTENER CATEGORÍAS PARA EL FORMULARIO
    

    public function obtenerCategorias(): array
    {
        return $this->database->queryAll(
            "SELECT id, nombre
             FROM categorias
             ORDER BY nombre"
        );
    }
}
