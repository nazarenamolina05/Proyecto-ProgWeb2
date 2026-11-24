<?php

namespace app\controller;

use Redirect;

class PartidaController
{
    private $model;
    private $render;

    public function __construct($model, $render)
    {
        $this->model = $model;
        $this->render = $render;
    }

    // Muestra las categorías disponibles.
    public function categorias()
    {
        $categorias = $this->model->obtenerCategorias();

        $this->render->renderiza('comenzarPartida', [
            'elegir_categoria' => true,
            'derrota' => $_SESSION['partida_derrota'] ?? null,
            'categorias' => $categorias,
            'hay_categorias' => count($categorias) > 0,
            'token' => $this->getToken(),
            'error' => $_SESSION['partida_error'] ?? null
        ]);

        unset($_SESSION['partida_error']);
<<<<<<< Updated upstream
=======
        unset($_SESSION['partida_mensaje']);
        unset($_SESSION['partida_derrota']);
>>>>>>> Stashed changes
    }

    // Recibe la categoría elegida y busca la primera pregunta.
    public function elegirCategoria()
    {
        if (!$this->validarPost()) {
            return;
        }

        $id = filter_var(
            $_POST['categoria_id'] ?? null,
            FILTER_VALIDATE_INT
        );

        $categoria = $id
            ? $this->model->obtenerCategoria($id)
            : null;

        if ($categoria === null) {
            $_SESSION['partida_error'] =
                'Elegí una categoría válida.';

            Redirect::to(
                '/index.php?controller=partida&method=categorias'
            );
            return;
        }

        $_SESSION['partida_categoria'] = $categoria;

        unset($_SESSION['partida_error']);

        $this->crearPartida();

        $this->irAPartida();
    }

    // Muestra la pregunta pendiente o el resultado.
    public function show()
    {
        if (!isset($_SESSION['partida_categoria'])) {
            Redirect::to(
                '/index.php?controller=partida&method=categorias'
            );
            return;
        }
        $vistaAutorizada = $_SESSION['partida_vista_autorizada'] ?? false;
        unset($_SESSION['partida_vista_autorizada']);

        if (isset($_SESSION['partida']) && !$vistaAutorizada) {
            $this->finalizarPartida(true);
            return;
        }

        $data = [
            'token' => $this->getToken(),
            'categoria_nombre' =>
                $_SESSION['partida_categoria']['nombre'],
            'error' => $_SESSION['partida_error'] ?? null
        ];

        unset($_SESSION['partida_error']);

        if (!isset($_SESSION['partida'])) {
            $data['sin_preguntas'] = true;
        } else {
            $partida = $_SESSION['partida'];
            $pregunta = $partida['pregunta'];

            $data['pregunta'] = $pregunta['texto'];
            $data['imagen'] = $pregunta['imagen'] ?? null;

            if ($partida['respuesta_id'] === null) {
                $data['en_curso'] = true;
                $data['opciones'] = [];

                foreach ($pregunta['opciones'] as $id => $texto) {
                    $data['opciones'][] = [
                        'id' => $id,
                        'texto' => $texto
                    ];
                }
            } else {
                $data['finalizada'] = true;

                $data['correcta'] = $this->model->esCorrecta(
                    $pregunta,
                    $partida['respuesta_id']
                );

                $data['respuesta_elegida'] =
                    $pregunta['opciones'][$partida['respuesta_id']];

                $data['respuesta_correcta'] =
                    $pregunta['opciones'][$pregunta['correcta']];
            }
        }

        $this->render->renderiza('comenzarPartida', $data);
    }

    // Valida y guarda la respuesta elegida.
    public function responder()
    {
        if (!$this->validarPost()) {
            return;
        }

        $partida = $_SESSION['partida'] ?? null;

        // No permite responder nuevamente una pregunta contestada.
        if (
            isset($partida['pregunta']) &&
            $partida['respuesta_id'] === null
        ) {
            $respuesta = filter_var(
                $_POST['respuesta'] ?? null,
                FILTER_VALIDATE_INT
            );

            if (
                $respuesta === false ||
                !$this->model->esOpcionValida(
                    $partida['pregunta'],
                    $respuesta
                )
            ) {
                $_SESSION['partida_error'] =
                    'Seleccioná una de las tres respuestas.';
            } else {
                $_SESSION['partida']['respuesta_id'] = $respuesta;

                $_SESSION['partida_token'] =
                    bin2hex(random_bytes(32));
            }
        }

        $this->irAPartida();
    }

    // Busca otra pregunta de la categoría actual.
    public function comenzar()
    {
        if (!$this->validarPost()) {
            return;
        }

        if (!isset($_SESSION['partida_categoria'])) {
            Redirect::to(
                '/index.php?controller=partida&method=categorias'
            );
            return;
        }

        // Conserva la pregunta si todavía está pendiente.
        if (
            !isset($_SESSION['partida']['pregunta']) ||
            $_SESSION['partida']['respuesta_id'] !== null
        ) {
            $this->crearPartida();
        }

        $this->irAPartida();
    }

<<<<<<< Updated upstream
=======
    public function terminar()
    {
        if (!$this->validarPost()) {
            return;
        }

        $this->finalizarPartida(false);
    }

    private function finalizarPartida(bool $porRecarga): void
    {
        $rutaCategorias = '/index.php?controller=partida&method=categorias';
        $usuarioId = $_SESSION['usuario_id'] ?? null;

        $resumen = $_SESSION['partida_resumen'];
        $correctas = (int) $resumen['correctas'];
        $totalPreguntas = (int) $resumen['total_preguntas'];
        $puntaje = (int) ($_SESSION['partida_puntaje'] ?? 0);

        if ($porRecarga) {
            $puntaje--;
        }

        // Una derrota por recarga cuenta incluso antes de la primera respuesta.
        $guardarResultado = $usuarioId !== null
            && ($porRecarga || $totalPreguntas > 0);

        if ($guardarResultado) {
            $guardada = $this->model->guardarPartida(
                (int) $usuarioId,
                $puntaje,
                $correctas,
                $totalPreguntas,
                $_SESSION['partida_dificultad'],
                (int) $_SESSION['partida_categoria']['id']
            );

            if (!$guardada) {
                $_SESSION['partida_error'] =
                    'No se pudo guardar la partida. Volvé a intentarlo.';

                $this->irAPartida();
                return;
            }
        }

        if ($porRecarga) {
            $_SESSION['partida_derrota'] = [
                'puntaje_final' => $puntaje,
                'guardada' => $guardarResultado
            ];
        } elseif ($guardarResultado) {
            $_SESSION['partida_mensaje'] =
                '¡Partida guardada correctamente!';
        } else {
            $_SESSION['partida_mensaje'] =
                'La partida terminó sin respuestas y no se guardó ningún resultado.';
        }

        unset(
            $_SESSION['partida'],
            $_SESSION['partida_categoria'],
            $_SESSION['partida_dificultad'],
            $_SESSION['partida_resumen'],
            $_SESSION['partida_puntaje']
        );

        $_SESSION['partida_token'] = bin2hex(random_bytes(32));

        Redirect::to($rutaCategorias);
    }
    private function irAPartida(): void
    {
        $_SESSION['partida_vista_autorizada'] = true;

        Redirect::to('/index.php?controller=partida&method=show');
    }

>>>>>>> Stashed changes
    private function crearPartida()
    {
        $pregunta = $this->model->obtenerPreguntaAleatoria(
            (int) $_SESSION['partida_categoria']['id']
        );

        if ($pregunta === null) {
            unset($_SESSION['partida']);
            return;
        }

        $_SESSION['partida'] = [
            'pregunta' => $pregunta,
            'respuesta_id' => null
        ];

        $_SESSION['partida_token'] =
            bin2hex(random_bytes(32));
    }

    private function getToken(): string
    {
        if (!isset($_SESSION['partida_token'])) {
            $_SESSION['partida_token'] =
                bin2hex(random_bytes(32));
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

        if (
            !is_string($token) ||
            !hash_equals($this->getToken(), $token)
        ) {
            $_SESSION['partida_error'] =
                'El formulario venció. Volvé a intentar desde esta pantalla.';

            $this->irAPartida();
            return false;
        }

        return true;
    }
}