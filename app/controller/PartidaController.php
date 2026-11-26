<?php

namespace app\controller;

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
        $jugador = $this->obtenerJugador();
        $categorias = [];
        foreach ($this->model->obtenerCategorias() as $categoria) {
            $categorias[] = array_merge($categoria, $this->model->obtenerProgresoUsuario($jugador, (int) $categoria['id']));
        }
        $this->render->renderiza('comenzarPartida', [
            'elegir_categoria' => true,
            'derrota' => $_SESSION['partida_derrota'] ?? null,
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
        // Si se abandona una pregunta vencida, se registra su vencimiento antes de cambiar.
        $this->registrarVencimiento();
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
        if (isset($_SESSION['partida']) && !$vistaAutorizada) {
            $this->finalizarPartida(true);
            return;
        }

        $this->registrarVencimiento();
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
            'error' => $_SESSION['partida_error'] ?? null
        ];
        unset($_SESSION['partida_error']);

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
                $data['pregunta_id'] = (int) $pregunta['id'];
                $data['contribucion_token'] = $_SESSION['contribucion_token'] ??= bin2hex(random_bytes(32));
                if (!empty($partida['agotado'])) {
                    $data['agotado'] = true;
                } else {
                    $data['correcta'] = $this->model->esCorrecta($pregunta, $partida['respuesta_id']);
                    $data['audio_partida'] = $data['correcta']
                        ? 'respuesta-correcta.mp3'
                        : 'respuesta-incorrecta.mp3';
                    $data['respuesta_elegida'] = $pregunta['opciones'][$partida['respuesta_id']];
                }
                $data['respuesta_correcta'] = $pregunta['opciones'][$pregunta['correcta']];
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
            && (string) ($_POST['tiempo_agotado'] ?? '') !== '1') {
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
        if (!isset($_SESSION['partida']) || !$this->estaPendiente($_SESSION['partida'])) {
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
        $vencioEnEstaSolicitud = $this->registrarVencimiento();
        $this->actualizarNivelUsuario();
        $resumen = $_SESSION['partida_resumen'];
        $correctas = (int) $resumen['correctas'];
        $totalPreguntas = (int) $resumen['total_preguntas'];
        $descuento = $porRecarga && !$vencioEnEstaSolicitud ? 1 : 0;
        $puntaje = max(0, (int) ($_SESSION['partida_puntaje'] ?? 0) - $descuento);
        $guardarResultado = $usuarioId !== null && ($porRecarga || $totalPreguntas > 0);
        if ($guardarResultado) {
            $guardada = $this->model->guardarPartida(
                (int) $usuarioId, $puntaje, $correctas, $totalPreguntas,
                $_SESSION['partida_dificultad'], (int) $_SESSION['partida_categoria']['id']
            );
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

    private function irAPartida(): void
    {
        $_SESSION['partida_vista_autorizada'] = true;
        Redirect::to('/index.php?controller=partida&method=show');
    }

    private function crearPartida()
    {
        $jugador = $_SESSION['partida_jugador'] ?? $this->obtenerJugador();
        $categoriaId = (int) $_SESSION['partida_categoria']['id'];
        $this->actualizarNivelUsuario();
        $pregunta = $this->model->obtenerPreguntaAleatoria($categoriaId, $_SESSION['partida_dificultad'], $jugador);
        if ($pregunta === null) {
            unset($_SESSION['partida']);
            return;
        }
        $historialId = $this->model->registrarPreguntaVista($jugador, $pregunta['id']);
        $_SESSION['partida'] = [
            'pregunta' => $pregunta,
            'historial_id' => $historialId,
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
