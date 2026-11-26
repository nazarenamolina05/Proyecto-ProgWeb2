<?php

namespace app\controller;

use Log;
use PDOException;
use Redirect;

class PartidaController
{
    private const DURACION_PREGUNTA_SEGUNDOS = 20;

    private $model;
    private $render;

    public function __construct($model, $render)
    {
        $this->model = $model;
        $this->render = $render;
    }

    private function obtenerJugador(): string
    {
        if (isset($_SESSION['usuario_id'])) {
            return 'usuario_' . (int) $_SESSION['usuario_id'];
        }
        if (!isset($_SESSION['jugador_invitado'])) {
            $_SESSION['jugador_invitado'] = 'invitado_' . bin2hex(random_bytes(16));
        }
        return $_SESSION['jugador_invitado'];
    }

    private function actualizarNivelUsuario(): array
    {
        $progreso = $this->model->obtenerProgresoUsuario(
            $_SESSION['partida_jugador'] ?? $this->obtenerJugador(),
            (int) $_SESSION['partida_categoria']['id']
        );
        $_SESSION['partida_nivel'] = $progreso['nivel_usuario'];
        $_SESSION['partida_dificultad'] = $this->model->obtenerDificultadSegunNivel($progreso['nivel_usuario']);
        return $progreso;
    }

    public function categorias()
    {
        if ($this->registrarVencimiento()) {
            $this->irAPartida();
            return;
        }

        $jugador = $this->obtenerJugador();
        $categorias = [];
        foreach ($this->model->obtenerCategorias() as $categoria) {
            $categorias[] = array_merge($categoria, $this->model->obtenerProgresoUsuario($jugador, (int) $categoria['id']));
        }
        $derrota = $_SESSION['partida_derrota'] ?? null;
        $this->render->renderiza('comenzarPartida', [
            'elegir_categoria' => true,
            'derrota' => $derrota === null ? null : $derrota + ['motivo' => 'La partida terminó porque volviste a abrir el desafío.'],
            'audio_partida' => $derrota === null ? null : 'respuesta-incorrecta.mp3',
            'categorias' => $categorias,
            'hay_categorias' => count($categorias) > 0,
            'token' => $this->getToken(),
            'error' => $_SESSION['partida_error'] ?? null,
            'mensaje' => $_SESSION['partida_mensaje'] ?? null
        ]);
        unset($_SESSION['partida_error'], $_SESSION['partida_mensaje'], $_SESSION['partida_derrota']);
    }

    public function elegirCategoria()
    {
        if (!$this->validarPost()) {
            return;
        }
        $id = filter_var($_POST['categoria_id'] ?? null, FILTER_VALIDATE_INT);
        $categoria = $id ? $this->model->obtenerCategoria($id) : null;
        if ($categoria === null) {
            $_SESSION['partida_error'] = 'Elegí una categoría válida.';
            Redirect::to('/index.php?controller=partida&method=categorias');
            return;
        }
        if ($this->registrarVencimiento()
            || ($this->estaTerminada() && !$this->guardarPartidaTerminada())) {
            $this->irAPartida();
            return;
        }
        unset($_SESSION['partida']);
        $_SESSION['partida_categoria'] = $categoria;
        $_SESSION['partida_jugador'] = $this->obtenerJugador();
        $_SESSION['partida_resumen'] = ['correctas' => 0, 'total_preguntas' => 0];
        $_SESSION['partida_puntaje'] = 0;
        unset($_SESSION['partida_error']);
        $this->crearPartida();
        $this->irAPartida();
    }

    public function show()
    {
        if (!isset($_SESSION['partida_categoria'])) {
            Redirect::to('/index.php?controller=partida&method=categorias');
            return;
        }
        $vistaAutorizada = $_SESSION['partida_vista_autorizada'] ?? false;
        unset($_SESSION['partida_vista_autorizada']);
        $this->registrarVencimiento();
        if (isset($_SESSION['partida']) && !$this->estaTerminada() && !$vistaAutorizada) {
            $this->finalizarPartida(true);
            return;
        }

        $progreso = $this->actualizarNivelUsuario();
        $resumen = $_SESSION['partida_resumen'] ?? ['correctas' => 0, 'total_preguntas' => 0];
        $data = [
            'token' => $this->getToken(),
            'categoria_nombre' => $_SESSION['partida_categoria']['nombre'],
            'nivel_usuario' => $progreso['nivel_usuario'],
            'porcentaje' => $progreso['porcentaje'],
            'clase_nivel' => $progreso['clase_nivel'],
            'en_evaluacion' => $progreso['en_evaluacion'],
            'respuestas_evaluacion' => $progreso['respuestas_evaluacion'],
            'meta_evaluacion' => $progreso['meta_evaluacion'],
            'dificultad' => $_SESSION['partida_dificultad'],
            'correctas' => $resumen['correctas'],
            'total_preguntas' => $resumen['total_preguntas'],
            'mostrar_puntaje' => true,
            'puntaje' => (int) ($_SESSION['partida_puntaje'] ?? 0),
            'error' => $_SESSION['partida_error'] ?? null,
            'mensaje' => $_SESSION['partida_mensaje'] ?? null,
        ];
        unset($_SESSION['partida_error'], $_SESSION['partida_mensaje']);

        if ($this->estaTerminada()) {
            $data['partida_terminada'] = true;
            if (!$this->guardarPartidaTerminada()) {
                $data['error'] ??= 'No se pudo guardar el resultado. Recargá esta pantalla para volver a intentarlo.';
            }
        }

        if (!isset($_SESSION['partida'])) {
            $data['sin_preguntas'] = true;
        } else {
            $partida = $_SESSION['partida'];
            $pregunta = $partida['pregunta'];
            $data['dificultad'] = $pregunta['dificultad'] ?? $data['dificultad'];
            $data['pregunta'] = $pregunta['texto'];
            $data['imagen'] = $pregunta['imagen'] ?? null;
            if ($this->estaPendiente($partida)) {
                $data['en_curso'] = true;
                $data['audio_partida'] = 'temporizador.mp3';
                $data['duracion_pregunta'] = self::DURACION_PREGUNTA_SEGUNDOS;
                $restante = max(0, (float) $partida['vence_en'] - microtime(true));
                $data['segundos_restantes'] = (int) ceil($restante);
                $data['milisegundos_restantes'] = (int) ceil($restante * 1000);
                $data['opciones'] = [];
                foreach ($pregunta['opciones'] as $id => $texto) {
                    $data['opciones'][] = ['id' => $id, 'texto' => $texto];
                }
            } else {
                $data['finalizada'] = true;
                $data['sin_mas_preguntas'] = $partida['sin_mas_preguntas'] ?? false;
                $data['pregunta_id'] = (int) $pregunta['id'];
                $data['contribucion_token'] = $_SESSION['contribucion_token'] ??= bin2hex(random_bytes(32));
                if (!empty($partida['agotado'])) {
                    $data['agotado'] = true;
                } else {
                    $data['correcta'] = $this->model->esCorrecta($pregunta, $partida['respuesta_id']);
                    if ($data['correcta']) {
                        $data['audio_partida'] = 'respuesta-correcta.mp3';
                    }
                    $data['respuesta_elegida'] = $pregunta['opciones'][$partida['respuesta_id']];
                }
                $data['respuesta_correcta'] = $pregunta['opciones'][$pregunta['correcta']];
                if (!empty($data['partida_terminada']) && empty($data['correcta']) && empty($partida['derrota_mostrada'])) {
                    $data['derrota'] = [
                        'motivo' => !empty($data['agotado']) ? 'Se agotó el tiempo.' : 'La respuesta fue incorrecta.',
                        'puntaje_final' => $data['puntaje'],
                        'respuesta_correcta' => $data['respuesta_correcta'],
                        'guardada' => !empty($_SESSION['partida']['resultado_guardado']),
                        'revisar_resultado' => true,
                    ];
                    $data['audio_partida'] = 'respuesta-incorrecta.mp3';
                    $_SESSION['partida']['derrota_mostrada'] = true;
                }
            }
        }
        $this->render->renderiza('comenzarPartida', $data);
    }

    public function responder()
    {
        if (!$this->validarPost()) {
            return;
        }
        $this->registrarVencimiento();
        $partida = $_SESSION['partida'] ?? null;
        if (is_array($partida) && $this->estaPendiente($partida)
            && ($_POST['tiempo_agotado'] ?? '') !== '1') {
            $respuesta = filter_var($_POST['respuesta'] ?? null, FILTER_VALIDATE_INT);
            if ($respuesta === false || !$this->model->esOpcionValida($partida['pregunta'], $respuesta)) {
                $_SESSION['partida_error'] = 'Seleccioná una de las tres respuestas.';
            } else {
                $this->registrarVencimiento();
                if ($this->estaPendiente($_SESSION['partida'])) {
                    $esCorrecta = $this->model->esCorrecta($partida['pregunta'], $respuesta);
                    if (isset($partida['historial_id'])) {
                        $this->model->registrarRespuesta($partida['historial_id'], $esCorrecta);
                    }
                    $_SESSION['partida']['respuesta_id'] = $respuesta;
                    $_SESSION['partida_resumen']['total_preguntas']++;
                    if ($esCorrecta) {
                        $_SESSION['partida_resumen']['correctas']++;
                    }
                    $_SESSION['partida_puntaje'] = max(0, (int) ($_SESSION['partida_puntaje'] ?? 0) + ($esCorrecta ? 1 : -1));
                    $this->actualizarNivelUsuario();
                    $_SESSION['partida_token'] = bin2hex(random_bytes(32));
                    if (!$esCorrecta) {
                        $this->cerrarPartida();
                    }
                }
            }
        }
        $this->irAPartida();
    }

    public function comenzar()
    {
        if (!$this->validarPost()) {
            return;
        }
        if (!isset($_SESSION['partida_categoria'])) {
            Redirect::to('/index.php?controller=partida&method=categorias');
            return;
        }
        $this->registrarVencimiento();
        if (!$this->estaTerminada() && (!isset($_SESSION['partida']) || !$this->estaPendiente($_SESSION['partida']))) {
            $this->crearPartida();
        }
        $this->irAPartida();
    }

    public function terminar()
    {
        if (!$this->validarPost()) {
            return;
        }
        if (!isset($_SESSION['usuario_id'])) {
            Redirect::to('/index.php?controller=login&method=show');
            return;
        }
        $this->finalizarPartida(false);
    }

    private function finalizarPartida(bool $porRecarga): void
    {
        $rutaCategorias = '/index.php?controller=partida&method=categorias';
        $usuarioId = $_SESSION['usuario_id'] ?? null;
        if (!isset($_SESSION['partida_categoria'], $_SESSION['partida_resumen'], $_SESSION['partida_dificultad'])) {
            $_SESSION['partida_error'] = 'No hay una partida activa para guardar.';
            Redirect::to($rutaCategorias);
            return;
        }
        $this->registrarVencimiento();
        if ($this->estaTerminada()) {
            $this->irAPartida();
            return;
        }
        $this->actualizarNivelUsuario();
        $resumen = $_SESSION['partida_resumen'];
        $totalPreguntas = (int) $resumen['total_preguntas'];
        $descuento = $porRecarga ? 1 : 0;
        $puntaje = max(0, (int) ($_SESSION['partida_puntaje'] ?? 0) - $descuento);
        $guardarResultado = $usuarioId !== null && ($porRecarga || $totalPreguntas > 0);
        if ($guardarResultado) {
            $guardada = $this->guardarPuntaje((int) $usuarioId, $puntaje);
            if (!$guardada) {
                $_SESSION['partida_error'] = 'No se pudo guardar la partida. Volvé a intentarlo.';
                $this->irAPartida();
                return;
            }
        }
        if ($porRecarga) {
            $_SESSION['partida_derrota'] = ['puntaje_final' => $puntaje, 'guardada' => $guardarResultado];
        } elseif ($guardarResultado) {
            $_SESSION['partida_mensaje'] = '¡Partida guardada correctamente!';
        } else {
            $_SESSION['partida_mensaje'] = 'La partida terminó sin respuestas y no se guardó ningún resultado.';
        }
        unset($_SESSION['partida'], $_SESSION['partida_categoria'], $_SESSION['partida_dificultad'],
            $_SESSION['partida_nivel'], $_SESSION['partida_jugador'], $_SESSION['partida_resumen'],
            $_SESSION['partida_puntaje'], $_SESSION['partida_vista_autorizada']);
        $_SESSION['partida_token'] = bin2hex(random_bytes(32));
        Redirect::to($rutaCategorias);
    }

    private function estaTerminada(): bool
    {
        return !empty($_SESSION['partida']['terminada']);
    }

    private function cerrarPartida(): void
    {
        $_SESSION['partida']['terminada'] = true;
        $this->guardarPartidaTerminada();
    }

    private function guardarPartidaTerminada(): bool
    {
        if (!empty($_SESSION['partida']['resultado_guardado']) || !isset($_SESSION['usuario_id'])) {
            return true;
        }

        $guardada = $this->guardarPuntaje((int) $_SESSION['usuario_id'], (int) $_SESSION['partida_puntaje']);
        $_SESSION['partida']['resultado_guardado'] = $guardada;
        return $guardada;
    }

    private function guardarPuntaje(int $usuarioId, int $puntaje): bool
    {
        $resumen = $_SESSION['partida_resumen'];
        try {
            return $this->model->guardarPartida(
                $usuarioId, $puntaje, (int) $resumen['correctas'], (int) $resumen['total_preguntas'],
                $_SESSION['partida_dificultad'], (int) $_SESSION['partida_categoria']['id']
            );
        } catch (PDOException $ex) {
            Log::error('Error al guardar la partida. SQLSTATE: ' . $ex->getCode());
            return false;
        }
    }

    private function irAPartida(): void
    {
        $_SESSION['partida_vista_autorizada'] = true;
        Redirect::to('/index.php?controller=partida&method=show');
    }

    private function crearPartida()
    {
        $jugador = $_SESSION['partida_jugador'] ?? $this->obtenerJugador();
        $categoriaId = (int) $_SESSION['partida_categoria']['id'];
        $preguntaAnterior = $_SESSION['partida']['pregunta']['id'] ?? null;
        $preguntasVistas = $_SESSION['partida']['preguntas_vistas'] ?? [];
        if ($preguntaAnterior !== null && $preguntasVistas === []) {
            // Una partida iniciada antes del cambio no tiene su propia lista de preguntas.
            $preguntasVistas = $this->model->obtenerPreguntasVistas($jugador, $categoriaId);
            if (!in_array((int) $preguntaAnterior, $preguntasVistas, true)) {
                $preguntasVistas[] = (int) $preguntaAnterior;
            }
        }
        $this->actualizarNivelUsuario();
        $turno = $this->model->entregarPregunta(
            $categoriaId,
            $_SESSION['partida_dificultad'],
            $jugador,
            $preguntasVistas
        );
        if ($turno === null) {
            if (isset($_SESSION['partida'])) {
                $_SESSION['partida']['sin_mas_preguntas'] = true;
                $_SESSION['partida_token'] = bin2hex(random_bytes(32));
                $this->cerrarPartida();
            }
            return;
        }
        $_SESSION['partida'] = $turno + [
            'preguntas_vistas' => [...$preguntasVistas, $turno['pregunta']['id']],
            'respuesta_id' => null,
            'agotado' => false,
            'vence_en' => microtime(true) + self::DURACION_PREGUNTA_SEGUNDOS
        ];
        $_SESSION['partida_token'] = bin2hex(random_bytes(32));
    }

    private function estaPendiente(array $partida): bool
    {
        return isset($partida['pregunta']) && array_key_exists('respuesta_id', $partida)
            && $partida['respuesta_id'] === null && empty($partida['agotado']);
    }

    private function registrarVencimiento(): bool
    {
        $partida = $_SESSION['partida'] ?? null;
        if (!is_array($partida) || !$this->estaPendiente($partida)
            || (float) ($partida['vence_en'] ?? 0) > microtime(true)) {
            return false;
        }
        if (isset($partida['historial_id'])) {
            $this->model->registrarRespuesta($partida['historial_id'], false);
        }
        $_SESSION['partida']['agotado'] = true;
        $_SESSION['partida_resumen']['total_preguntas']++;
        $_SESSION['partida_puntaje'] = max(0, (int) ($_SESSION['partida_puntaje'] ?? 0) - 1);
        $this->actualizarNivelUsuario();
        $_SESSION['partida_token'] = bin2hex(random_bytes(32));
        $this->cerrarPartida();
        return true;
    }

    private function getToken(): string
    {
        if (!isset($_SESSION['partida_token'])) {
            $_SESSION['partida_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['partida_token'];
    }

    private function validarPost(): bool
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            http_response_code(405);
            header('Allow: POST');
            echo 'Esta acción requiere enviar el formulario.';
            return false;
        }
        $token = $_POST['token'] ?? '';
        if (!is_string($token) || !hash_equals($this->getToken(), $token)) {
            $_SESSION['partida_error'] = 'El formulario venció. Volvé a intentar desde esta pantalla.';
            $this->irAPartida();
            return false;
        }
        return true;
    }
}
