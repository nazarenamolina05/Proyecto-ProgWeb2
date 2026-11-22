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

    public function categorias()
    {
        $categorias = $this->model->obtenerCategorias();

        $this->render->renderiza('comenzarPartida', [
            'elegir_categoria' => true,
            'categorias' => $categorias,
            'hay_categorias' => count($categorias) > 0,
            'token' => $this->getToken(),
            'error' => $_SESSION['partida_error'] ?? null,
            'mensaje' => $_SESSION['partida_mensaje'] ?? null
        ]);

        unset($_SESSION['partida_error']);
        unset($_SESSION['partida_mensaje']);
    }

    public function elegirCategoria()
    {
        if (!$this->validarPost()) {
            return;
        }

        $id = filter_var(
            $_POST['categoria_id'] ?? null,
            FILTER_VALIDATE_INT
        );

        $dificultad = $_POST['dificultad'] ?? '';

        $dificultadesValidas = [
            'FACIL',
            'INTERMEDIO',
            'DIFICIL'
        ];

        $categoria = $id
            ? $this->model->obtenerCategoria($id)
            : null;

        if (
            $categoria === null ||
            !in_array($dificultad, $dificultadesValidas, true)
        ) {
            $_SESSION['partida_error'] =
                'Elegí una categoría y una dificultad válidas.';

            Redirect::to(
                '/index.php?controller=partida&method=categorias'
            );
            return;
        }

        $_SESSION['partida_categoria'] = $categoria;
        $_SESSION['partida_dificultad'] = $dificultad;

        // Inicializa los resultados de la nueva partida.
        $_SESSION['partida_resumen'] = [
            'correctas' => 0,
            'total_preguntas' => 0
        ];
        $_SESSION['partida_puntaje'] = 0;

        unset($_SESSION['partida_error']);

        $this->crearPartida();

        Redirect::to(
            '/index.php?controller=partida&method=show'
        );
    }

    public function show()
    {
        if (!isset($_SESSION['partida_categoria'])) {
            Redirect::to(
                '/index.php?controller=partida&method=categorias'
            );
            return;
        }

        $resumen = $_SESSION['partida_resumen'] ?? [
            'correctas' => 0,
            'total_preguntas' => 0
        ];

        $this->registrarVencimiento();

        $data = [
            'token' => $this->getToken(),
            'categoria_nombre' =>
                $_SESSION['partida_categoria']['nombre'],
            'dificultad' =>
                $_SESSION['partida_dificultad'] ?? 'INTERMEDIO',
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

            $data['pregunta'] = $pregunta['texto'];
            $data['imagen'] = $pregunta['imagen'] ?? null;

            if ($this->estaPendiente($partida)) {
                $data['en_curso'] = true;
                $data['segundos_restantes'] = max(
                    0,
                    (int) ceil(
                        (float) $partida['vence_en'] - microtime(true)
                    )
                );
                $data['milisegundos_restantes'] = max(
                    0,
                    (int) ceil(
                        ((float) $partida['vence_en'] - microtime(true)) *
                        1000
                    )
                );
                $data['opciones'] = [];

                foreach ($pregunta['opciones'] as $id => $texto) {
                    $data['opciones'][] = [
                        'id' => $id,
                        'texto' => $texto
                    ];
                }
            } else {
                $data['finalizada'] = true;

                if (!empty($partida['agotado'])) {
                    $data['agotado'] = true;
                } else {
                    $data['correcta'] = $this->model->esCorrecta(
                        $pregunta,
                        $partida['respuesta_id']
                    );

                    $data['respuesta_elegida'] =
                        $pregunta['opciones'][$partida['respuesta_id']];
                }

                $data['respuesta_correcta'] =
                    $pregunta['opciones'][$pregunta['correcta']];
            }
        }

        $this->render->renderiza('comenzarPartida', $data);
    }

    public function responder()
    {
        if (!$this->validarPost()) {
            return;
        }

        $partida = $_SESSION['partida'] ?? null;

        if (is_array($partida) && $this->estaPendiente($partida)) {
            $this->registrarVencimiento();
            $partida = $_SESSION['partida'];
        }

        if (
            is_array($partida) &&
            $this->estaPendiente($partida) &&
            (string) ($_POST['tiempo_agotado'] ?? '') !== '1'
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
                $this->registrarVencimiento();

                if ($this->estaPendiente($_SESSION['partida'])) {
                    $_SESSION['partida']['respuesta_id'] = $respuesta;

                    // Actualiza los resultados de la partida.
                    $_SESSION['partida_resumen']['total_preguntas']++;

                    $esCorrecta = $this->model->esCorrecta(
                        $partida['pregunta'],
                        $respuesta
                    );

                    if ($esCorrecta) {
                        $_SESSION['partida_resumen']['correctas']++;
                    }

                    $_SESSION['partida_puntaje'] = max(
                        0,
                        (int) ($_SESSION['partida_puntaje'] ?? 0) +
                        ($esCorrecta ? 1 : -1)
                    );

                    $_SESSION['partida_token'] =
                        bin2hex(random_bytes(32));
                }
            }
        }

        Redirect::to(
            '/index.php?controller=partida&method=show'
        );
    }

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

        if (isset($_SESSION['partida'])) {
            $this->registrarVencimiento();
        }

        // Conserva la pregunta si todavía está pendiente.
        if (
            !isset($_SESSION['partida']) ||
            !$this->estaPendiente($_SESSION['partida'])
        ) {
            $this->crearPartida();
        }

        Redirect::to(
            '/index.php?controller=partida&method=show'
        );
    }

    public function terminar()
    {
        if (!$this->validarPost()) {
            return;
        }

        if (!isset($_SESSION['usuario_id'])) {
            Redirect::to(
                '/index.php?controller=login&method=show'
            );
            return;
        }

        if (
            !isset($_SESSION['partida_categoria']) ||
            !isset($_SESSION['partida_resumen']) ||
            !isset($_SESSION['partida_dificultad'])
        ) {
            $_SESSION['partida_error'] =
                'No hay una partida activa para guardar.';

            Redirect::to(
                '/index.php?controller=partida&method=categorias'
            );
            return;
        }

        $resumen = $_SESSION['partida_resumen'];

        $correctas = (int) $resumen['correctas'];
        $totalPreguntas = (int) $resumen['total_preguntas'];
        $puntaje = max(0, (int) ($_SESSION['partida_puntaje'] ?? 0));

        if ($totalPreguntas === 0) {
            $_SESSION['partida_mensaje'] =
                'La partida terminó sin respuestas y no se guardó ningún resultado.';
        } else {
            $guardada = $this->model->guardarPartida(
                (int) $_SESSION['usuario_id'],
                $puntaje,
                $correctas,
                $totalPreguntas,
                $_SESSION['partida_dificultad'],
                (int) $_SESSION['partida_categoria']['id']
            );

            if (!$guardada) {
                $_SESSION['partida_error'] =
                    'No se pudo guardar la partida. Volvé a intentarlo.';

                Redirect::to(
                    '/index.php?controller=partida&method=show'
                );
                return;
            }

            $_SESSION['partida_mensaje'] =
                '¡Partida guardada correctamente! Tu resultado ya está registrado.';
        }

        unset(
            $_SESSION['partida'],
            $_SESSION['partida_categoria'],
            $_SESSION['partida_dificultad'],
            $_SESSION['partida_resumen'],
            $_SESSION['partida_puntaje']
        );

        $_SESSION['partida_token'] = bin2hex(random_bytes(32));

        Redirect::to(
            '/index.php?controller=partida&method=categorias'
        );
    }

    private function crearPartida()
    {
        $pregunta = $this->model->obtenerPreguntaAleatoria(
            (int) $_SESSION['partida_categoria']['id'],
            $_SESSION['partida_dificultad']
        );

        if ($pregunta === null) {
            unset($_SESSION['partida']);
            return;
        }

        $_SESSION['partida'] = [
            'pregunta' => $pregunta,
            'respuesta_id' => null,
            'agotado' => false,
            'vence_en' => microtime(true) + 20
        ];

        $_SESSION['partida_token'] =
            bin2hex(random_bytes(32));
    }

    private function estaPendiente(array $partida): bool
    {
        return isset($partida['pregunta']) &&
            array_key_exists('respuesta_id', $partida) &&
            $partida['respuesta_id'] === null &&
            empty($partida['agotado']);
    }

    private function registrarVencimiento(): void
    {
        $partida = $_SESSION['partida'] ?? null;

        if (
            !is_array($partida) ||
            !$this->estaPendiente($partida) ||
            (float) ($partida['vence_en'] ?? 0) > microtime(true)
        ) {
            return;
        }

        $_SESSION['partida']['agotado'] = true;

        $_SESSION['partida_puntaje'] = max(
            0,
            (int) ($_SESSION['partida_puntaje'] ?? 0) - 1
        );

        $_SESSION['partida_token'] = bin2hex(random_bytes(32));
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

            Redirect::to(
                '/index.php?controller=partida&method=show'
            );
            return false;
        }

        return true;
    }
}
