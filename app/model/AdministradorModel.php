<?php

namespace app\model;

use DateTimeImmutable;

class AdministradorModel
{
    private const JUGADORES_AL_CIERRE = "u.rol = 'jugador'
        AND COALESCE(u.fecha_registro, (SELECT fecha FROM estadisticas_inicio WHERE id = 1)) < ?";

    public function __construct(private $database)
    {
    }

    public function estadisticas(DateTimeImmutable $inicio, DateTimeImmutable $fin): array
    {
        return $this->database->enTransaccion(fn () => $this->consultarPeriodo($inicio, $fin));
    }

    private function consultarPeriodo(DateTimeImmutable $inicio, DateTimeImmutable $fin): array
    {
        $desde = $inicio->format('Y-m-d H:i:s');
        $hasta = $fin->format('Y-m-d H:i:s');
        $jugadores = $this->database->queryOnePrepared(
            'SELECT COUNT(*) AS total,
                    COUNT(CASE WHEN u.fecha_registro >= ? AND u.fecha_registro < ? THEN 1 END) AS nuevos
             FROM usuarios u WHERE ' . self::JUGADORES_AL_CIERRE,
            [$desde, $hasta, $hasta]
        );
        $partidas = $this->database->queryOnePrepared(
            "SELECT COUNT(*) AS total FROM partidas p JOIN usuarios u ON u.id = p.usuario_id
             WHERE u.rol = 'jugador' AND p.fecha >= ? AND p.fecha < ?",
            [$desde, $hasta]
        );
        $creadas = $this->database->queryOnePrepared(
            'SELECT COUNT(*) AS total FROM preguntas WHERE fecha_creacion >= ? AND fecha_creacion < ?',
            [$desde, $hasta]
        );
        $activas = $this->database->queryOnePrepared(
            'SELECT COUNT(*) AS total FROM pregunta_estados e
             WHERE e.fecha < ? AND e.activa = 1 AND NOT EXISTS (
                SELECT 1 FROM pregunta_estados posterior
                WHERE posterior.pregunta_id = e.pregunta_id AND posterior.fecha < ?
                  AND (posterior.fecha > e.fecha OR (posterior.fecha = e.fecha AND posterior.id > e.id))
             )',
            [$hasta, $hasta]
        );
        $rendimiento = $this->database->queryAllPrepared(
            'SELECT u.usuario, COALESCE(SUM(p.correctas), 0) AS correctas,
                    COALESCE(SUM(p.total_preguntas), 0) AS respondidas
             FROM usuarios u LEFT JOIN partidas p ON p.usuario_id = u.id AND p.fecha >= ? AND p.fecha < ?
             WHERE ' . self::JUGADORES_AL_CIERRE . '
             GROUP BY u.id, u.usuario ORDER BY u.usuario',
            [$desde, $hasta, $hasta]
        );
        foreach ($rendimiento as &$fila) {
            $respondidas = (int) $fila['respondidas'];
            $porcentaje = $respondidas > 0 ? round(100 * (int) $fila['correctas'] / $respondidas, 1) : null;
            $fila['barra'] = $porcentaje === null ? 0 : min(100, $porcentaje);
            $fila['porcentaje'] = $porcentaje === null ? '—' : number_format($porcentaje, 1, ',', '.') . ' %';
        }
        unset($fila);

        $anio = (int) $fin->modify('-1 second')->format('Y');
        $edad = "CASE
            WHEN u.anio_nacimiento IS NULL OR u.anio_nacimiento < 1900 OR u.anio_nacimiento > $anio THEN 'Sin dato'
            WHEN $anio - u.anio_nacimiento < 18 THEN 'Menores (hasta 17)'
            WHEN $anio - u.anio_nacimiento >= 65 THEN 'Jubilados (65 o más)'
            ELSE 'Grupo medio (18 a 64)'
        END";

        return [
            'rendimiento' => $rendimiento,
            'distribuciones' => [
                [
                    'titulo' => 'Resumen del juego',
                    'filas' => $this->conBarras([
                        ['grupo' => 'Jugadores registrados al cierre', 'total' => $jugadores['total']],
                        ['grupo' => 'Partidas jugadas registradas', 'total' => $partidas['total']],
                        ['grupo' => 'Preguntas en el juego al cierre', 'total' => $activas['total']],
                        ['grupo' => 'Preguntas creadas', 'total' => $creadas['total']],
                        ['grupo' => 'Usuarios nuevos', 'total' => $jugadores['nuevos']],
                    ]),
                ],
                [
                    'titulo' => 'Usuarios por país',
                    'filas' => $this->agruparJugadores("COALESCE(NULLIF(TRIM(u.pais), ''), 'Sin dato')", $hasta),
                ],
                [
                    'titulo' => 'Usuarios por sexo',
                    'filas' => $this->agruparJugadores("COALESCE(NULLIF(u.sexo, ''), 'Sin dato')", $hasta),
                ],
                [
                    'titulo' => 'Usuarios por grupo de edad',
                    'filas' => $this->agruparJugadores($edad, $hasta),
                ],
            ],
        ];
    }

    private function agruparJugadores(string $expresion, string $hasta): array
    {
        $filas = $this->database->queryAllPrepared(
            "SELECT $expresion AS grupo, COUNT(*) AS total
             FROM usuarios u WHERE " . self::JUGADORES_AL_CIERRE . '
             GROUP BY grupo ORDER BY total DESC, grupo',
            [$hasta]
        );

        return $this->conBarras($filas);
    }

    private function conBarras(array $filas): array
    {
        $maximo = max([1, ...array_map('intval', array_column($filas, 'total'))]);
        foreach ($filas as &$fila) {
            $fila['barra'] = round(100 * (int) $fila['total'] / $maximo, 1);
        }
        unset($fila);
        return $filas;
    }
}
