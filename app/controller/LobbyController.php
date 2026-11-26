<?php

namespace app\controller;

use Redirect;

class LobbyController
{
    private const CANTIDAD_RANKING_LOBBY = 5;
    private const CANTIDAD_RANKING_COMPLETO = 50;
    private const CANTIDAD_HISTORIAL = 10;

    private $model;
    private $render;

    public function __construct($model, $render)
    {
        $this->model = $model;
        $this->render = $render;
    }

    // Cabecera, navegación e historial del usuario logueado.
    public function show()
    {
        $usuarioId = $this->usuarioLogueado();
        if ($usuarioId === null) {
            Redirect::to('/index.php?controller=login&method=show');
            return;
        }

        $mejorPuntaje = $this->model->obtenerMejorPuntaje($usuarioId);
        $historial = $this->formatearHistorial(
            $this->model->obtenerHistorial($usuarioId, self::CANTIDAD_HISTORIAL)
        );
        $top = $this->armarRanking(self::CANTIDAD_RANKING_LOBBY, $usuarioId);

        $data = [
            'usuario' => $_SESSION['usuario'],
            'tiene_puntaje' => $mejorPuntaje !== null,
            'mejor_puntaje' => $mejorPuntaje,
            'posicion' => $mejorPuntaje !== null
                ? $this->model->obtenerPosicion($mejorPuntaje)
                : null,
            'historial' => $historial,
            'tiene_historial' => count($historial) > 0,
            'top' => $top,
            'hay_top' => count($top) > 0,
            'url_perfil_propio' => 'index.php?controller=perfil&method=show&id=' . $usuarioId,
        ];

        $this->render->renderiza('lobby', $data);
    }

    // Ranking global (un jugador = una fila).
    public function ranking()
    {
        $usuarioId = $this->usuarioLogueado();

        $ranking = $this->armarRanking(self::CANTIDAD_RANKING_COMPLETO, $usuarioId);

        $this->render->renderiza('ranking', [
            'ranking' => $ranking,
            'hay_ranking' => count($ranking) > 0,
            'logueado' => $usuarioId !== null,
        ]);
    }

    private function usuarioLogueado(): ?int
    {
        return isset($_SESSION['usuario_id'])
            ? (int) $_SESSION['usuario_id']
            : null;
    }

    private function armarRanking(int $limite, ?int $usuarioId): array
    {
        $filas = $this->model->obtenerRanking($limite);

        $posicion = 0;
        $puntajeAnterior = null;

        foreach ($filas as $indice => $fila) {
            // Empate: misma posición para el mismo mejor puntaje.
            if ($puntajeAnterior === null || (int) $fila['mejor_puntaje'] !== $puntajeAnterior) {
                $posicion = $indice + 1;
            }
            $puntajeAnterior = (int) $fila['mejor_puntaje'];

            $filas[$indice]['posicion'] = $posicion;
            $filas[$indice]['es_propio'] = $usuarioId !== null
                && (int) $fila['id'] === $usuarioId;
            $filas[$indice]['url_perfil'] =
                'index.php?controller=perfil&method=show&id=' . (int) $fila['id'];
        }

        return $filas;
    }

    private function formatearHistorial(array $partidas): array
    {
        foreach ($partidas as $clave => $partida) {
            $partidas[$clave]['fecha_formateada'] =
                date('d/m/Y H:i', strtotime($partida['fecha']));
            $partidas[$clave]['categoria'] = $partida['categoria'] ?? '—';
            $partidas[$clave]['dificultad'] = $partida['dificultad'] ?? '—';
        }

        return $partidas;
    }
}