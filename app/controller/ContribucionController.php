<?php

namespace app\controller;

use Log;
use PDOException;
use Redirect;

class ContribucionController
{
    private $model;
    private $render;

    public function __construct($model, $render)
    {
        $this->model = $model;
        $this->render = $render;
    }

    // MOSTRAR FORMULARIO DE SUGERENCIA
   

    public function sugerir()
    {
        if (!$this->estaLogueado()) {
            return;
        }

        $categorias = $this->model->obtenerCategorias();

        $this->render->renderiza('sugerirPregunta', [
            'categorias' => $categorias,
            'hay_categorias' => count($categorias) > 0,
            'token' => $this->getToken(),
            'volver_partida' => isset($_SESSION['partida']),
            'error' => $_SESSION['contribucion_error'] ?? null,
            'mensaje' => $_SESSION['contribucion_mensaje'] ?? null
        ]);

        unset(
            $_SESSION['contribucion_error'],
            $_SESSION['contribucion_mensaje']
        );
    }

   
    // GUARDAR SUGERENCIA DEL USUARIO
    

    public function guardarSugerencia()
    {
        if (!$this->estaLogueado()) {
            return;
        }

        if (!$this->validarPost()) {
            return;
        }

        $enunciado = $_POST['enunciado'] ?? null;
        $opcion1 = $_POST['opcion_1'] ?? null;
        $opcion2 = $_POST['opcion_2'] ?? null;
        $opcion3 = $_POST['opcion_3'] ?? null;

        $correcta = filter_var(
            $_POST['correcta'] ?? null,
            FILTER_VALIDATE_INT
        );

        $categoriaId = filter_var(
            $_POST['categoria_id'] ?? null,
            FILTER_VALIDATE_INT
        );

        $dificultad = $_POST['dificultad'] ?? '';

        if (
            !is_string($enunciado) ||
            !is_string($opcion1) ||
            !is_string($opcion2) ||
            !is_string($opcion3) ||
            !is_string($dificultad) ||
            $correcta === false ||
            $categoriaId === false
        ) {
            $_SESSION['contribucion_error'] =
                'Completá todos los campos obligatorios.';

            Redirect::to(
                '/index.php?controller=contribucion&method=sugerir'
            );
            return;
        }

        try {
            $guardada = $this->model->guardarSugerencia(
                (int) $_SESSION['usuario_id'],
                $enunciado,
                $opcion1,
                $opcion2,
                $opcion3,
                $correcta,
                $categoriaId,
                $dificultad
            );
        } catch (PDOException $ex) {
            Log::error('Error al guardar sugerencia. SQLSTATE: ' . $ex->getCode());
            $guardada = false;
        }

        if ($guardada) {
            $_SESSION['contribucion_mensaje'] =
                '¡Tu pregunta fue sugerida correctamente! '
                . 'Quedó pendiente de revisión.';
        } else {
            $_SESSION['contribucion_error'] =
                'No se pudo guardar la sugerencia. '
                . 'Revisá los datos ingresados.';
        }

        Redirect::to(
            '/index.php?controller=contribucion&method=sugerir'
        );
    }


    // REPORTAR PREGUNTA ACTUAL
   

    public function reportar()
    {
        if (!$this->estaLogueado()) {
            return;
        }

        if (!$this->validarPost()) {
            return;
        }

        $partida = $_SESSION['partida'] ?? null;

        // Debe existir una pregunta actual
        if (
            !is_array($partida) ||
            !isset($partida['pregunta']['id'])
        ) {
            $_SESSION['partida_error'] =
                'No hay una pregunta para reportar.';

            Redirect::to(
                '/index.php?controller=partida&method=show'
            );
            return;
        }

        // Solo se puede reportar después de responder
        // o cuando se terminó el tiempo
        $respondida =
            ($partida['respuesta_id'] ?? null) !== null;

        $agotada = !empty($partida['agotado']);

        if (!$respondida && !$agotada) {
            $_SESSION['partida_error'] =
                'Primero tenés que terminar la pregunta.';

            Redirect::to(
                '/index.php?controller=partida&method=show'
            );
            return;
        }

        $preguntaId = filter_var(
            $_POST['pregunta_id'] ?? null,
            FILTER_VALIDATE_INT
        );

        // Impedir que se reporte otra pregunta
        if (
            $preguntaId === false ||
            (int) $preguntaId !==
                (int) $partida['pregunta']['id']
        ) {
            $_SESSION['partida_error'] =
                'La pregunta que intentás reportar no es válida.';

            Redirect::to(
                '/index.php?controller=partida&method=show'
            );
            return;
        }

        // El motivo es opcional
        $motivo = $_POST['motivo'] ?? '';

        if (!is_string($motivo)) {
            $_SESSION['partida_error'] = 'Ingresá un motivo válido para el reporte.';
            Redirect::to('/index.php?controller=partida&method=show');
            return;
        }

        if (mb_strlen(trim($motivo), 'UTF-8') > 500) {
            $_SESSION['partida_error'] =
                'El motivo no puede superar los 500 caracteres.';

            Redirect::to(
                '/index.php?controller=partida&method=show'
            );
            return;
        }

        try {
            $guardado = $this->model->guardarReporte(
                (int) $_SESSION['usuario_id'],
                $preguntaId,
                $motivo
            );
        } catch (PDOException $ex) {
            Log::error('Error al guardar reporte. SQLSTATE: ' . $ex->getCode());
            $guardado = false;
        }

        if ($guardado) {
            $_SESSION['partida_mensaje'] =
                '¡Pregunta reportada correctamente!';
        } else {
            $_SESSION['partida_error'] =
                'No se pudo guardar el reporte. '
                . 'Es posible que ya hayas reportado esta pregunta.';
        }

        Redirect::to(
            '/index.php?controller=partida&method=show'
        );
    }

    // VALIDAR USUARIO LOGUEADO
   

    private function estaLogueado(): bool
    {
        if (!isset($_SESSION['usuario_id'])) {
            Redirect::to(
                '/index.php?controller=login&method=show'
            );
            return false;
        }

        return true;
    }

    // TOKEN DE SEGURIDAD
  

    private function getToken(): string
    {
        if (!isset($_SESSION['contribucion_token'])) {
            $_SESSION['contribucion_token'] =
                bin2hex(random_bytes(32));
        }

        return $_SESSION['contribucion_token'];
    }

    private function validarPost(): bool
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            http_response_code(405);
            header('Allow: POST');
            return false;
        }

        $tokenRecibido = $_POST['token'] ?? '';

        if (
            !is_string($tokenRecibido) ||
            !hash_equals(
                $this->getToken(),
                $tokenRecibido
            )
        ) {
            http_response_code(403);
            echo 'Solicitud no válida.';
            return false;
        }

        return true;
    }
}
