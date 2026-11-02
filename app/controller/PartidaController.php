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

    public function show()
    {
        // Si todavía no hay una pregunta, buscamos una en MySQL.
        if (!isset($_SESSION['partida']['pregunta'])) {
            $this->crearPartida();
        }

        $data = [
            'token' => $this->getToken(),
            'error' => $_SESSION['partida_error'] ?? null
        ];

        unset($_SESSION['partida_error']);

        if (!isset($_SESSION['partida'])) {
            $data['sin_preguntas'] = true;
        } else {
            $partida = $_SESSION['partida'];
            $pregunta = $partida['pregunta'];

            $data['pregunta'] = $pregunta['texto'];

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

    public function responder()
    {
        if (!$this->validarPost()) {
            return;
        }

        $partida = $_SESSION['partida'] ?? null;

        // Solo se puede responder una pregunta pendiente.
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

        Redirect::to('/index.php?controller=partida&method=show');
    }

    public function comenzar()
    {
        if (!$this->validarPost()) {
            return;
        }

        // Crea otra partida solo si no hay una pregunta pendiente.
        if (
            !isset($_SESSION['partida']['pregunta']) ||
            $_SESSION['partida']['respuesta_id'] !== null
        ) {
            $this->crearPartida();
        }

        Redirect::to('/index.php?controller=partida&method=show');
    }

    private function crearPartida()
    {
        $pregunta = $this->model->obtenerPreguntaAleatoria();

        if ($pregunta === null) {
            unset($_SESSION['partida']);
            return;
        }

        $_SESSION['partida'] = [
            'pregunta' => $pregunta,
            'respuesta_id' => null
        ];

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

            Redirect::to('/index.php?controller=partida&method=show');
        }

        return true;
    }
}