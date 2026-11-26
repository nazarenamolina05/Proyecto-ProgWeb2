<?php

namespace app\model;

class EditorModel
{
    public function __construct(private $database)
    {
    }

    public function listarCategoriasPreguntas(): array
    {
        return $this->database->queryAll(
            "SELECT c.id, c.nombre, COUNT(p.id) AS total
             FROM categorias c LEFT JOIN preguntas p ON p.categoria_id = c.id
             GROUP BY c.id, c.nombre
             UNION ALL
             SELECT 'sin-categoria', 'Sin categoría', COUNT(*)
             FROM preguntas p LEFT JOIN categorias c ON c.id = p.categoria_id
             WHERE c.id IS NULL
             HAVING COUNT(*) > 0
             ORDER BY nombre"
        );
    }

    public function contarPreguntas(?int $categoriaId): int
    {
        $fila = $this->database->queryOnePrepared(
            'SELECT COUNT(*) AS total
             FROM preguntas p LEFT JOIN categorias c ON c.id = p.categoria_id
             WHERE c.id <=> ?',
            [$categoriaId]
        );

        return (int) $fila['total'];
    }

    public function listarPreguntas(?int $categoriaId, int $limite, int $desplazamiento): array
    {
        return $this->database->queryAllPrepared(
            "SELECT p.id, p.enunciado, p.dificultad, p.activa
             FROM preguntas p LEFT JOIN categorias c ON c.id = p.categoria_id
             WHERE c.id <=> ?
             ORDER BY p.activa DESC, p.id DESC
             LIMIT $limite OFFSET $desplazamiento",
            [$categoriaId]
        );
    }

    public function obtenerPregunta(int $id): ?array
    {
        return $this->database->queryOnePrepared(
            'SELECT id, enunciado, opcion_1, opcion_2, opcion_3, correcta, categoria_id, dificultad, imagen
             FROM preguntas WHERE id = ?',
            [$id]
        );
    }

    public function guardarPregunta(?int $id, array $datos): void
    {
        if ($id === null) {
            $this->database->executePrepared(
                'INSERT INTO preguntas (enunciado, opcion_1, opcion_2, opcion_3, correcta, categoria_id, dificultad, imagen)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
                $datos
            );
            return;
        }

        $this->database->executePrepared(
            'UPDATE preguntas
             SET enunciado = ?, opcion_1 = ?, opcion_2 = ?, opcion_3 = ?, correcta = ?, categoria_id = ?, dificultad = ?, imagen = ?
             WHERE id = ?',
            [...$datos, $id]
        );
    }

    public function cambiarEstadoPregunta(int $id, bool $activa): void
    {
        $this->database->executePrepared('UPDATE preguntas SET activa = ? WHERE id = ?', [(int) $activa, $id]);
    }

    public function listarReportes(): array
    {
        return $this->database->queryAll(
            "SELECT r.id, r.pregunta_id, r.motivo, p.enunciado, p.activa, u.usuario
             FROM reportes_preguntas r
             JOIN preguntas p ON p.id = r.pregunta_id
             JOIN usuarios u ON u.id = r.usuario_id
             WHERE r.estado = 'pendiente'
             ORDER BY r.fecha, r.id"
        );
    }

    public function resolverReporte(int $id, string $decision): bool
    {
        return $this->database->enTransaccion(function () use ($id, $decision) {
            $reporte = $this->database->queryOnePrepared(
                "SELECT pregunta_id FROM reportes_preguntas WHERE id = ? AND estado = 'pendiente' FOR UPDATE",
                [$id]
            );
            if ($reporte === null) {
                return false;
            }

            if ($decision === 'baja') {
                $this->cambiarEstadoPregunta((int) $reporte['pregunta_id'], false);
            }

            $this->database->executePrepared(
                "UPDATE reportes_preguntas SET estado = 'resuelto', decision = ? WHERE id = ?",
                [$decision, $id]
            );
            return true;
        });
    }

    public function listarSugerencias(): array
    {
        return $this->database->queryAll(
            "SELECT s.id, s.enunciado, s.opcion_1, s.opcion_2, s.opcion_3, s.correcta, s.dificultad,
                    c.nombre AS categoria, u.usuario
             FROM preguntas_sugeridas s
             JOIN categorias c ON c.id = s.categoria_id
             JOIN usuarios u ON u.id = s.usuario_id
             WHERE s.estado = 'pendiente'
             ORDER BY s.fecha, s.id"
        );
    }

    public function resolverSugerencia(int $id, bool $aprobada): bool
    {
        return $this->database->enTransaccion(function () use ($id, $aprobada) {
            $sugerencia = $this->database->queryOnePrepared(
                "SELECT id FROM preguntas_sugeridas WHERE id = ? AND estado = 'pendiente' FOR UPDATE",
                [$id]
            );
            if ($sugerencia === null) {
                return false;
            }

            if ($aprobada) {
                $this->database->executePrepared(
                    'INSERT INTO preguntas (enunciado, opcion_1, opcion_2, opcion_3, correcta, categoria_id, dificultad)
                     SELECT enunciado, opcion_1, opcion_2, opcion_3, correcta, categoria_id, dificultad
                     FROM preguntas_sugeridas WHERE id = ?',
                    [$id]
                );
            }

            $this->database->executePrepared(
                'UPDATE preguntas_sugeridas SET estado = ? WHERE id = ?',
                [$aprobada ? 'aprobada' : 'rechazada', $id]
            );
            return true;
        });
    }
}
