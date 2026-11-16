<?php

namespace app\controller;

use Redirect;
use Request;
use app\service\PerfilValidator;

class PerfilController
{
    private $model;
    private $render;
    private $mapa;
    private $qr;
    private $validator;
    private $fotoService;

    public function __construct($model, $render, $mapa, $qr, $fotoService)
    {
        $this->model = $model;
        $this->render = $render;
        $this->mapa = $mapa;
        $this->qr = $qr;
        $this->validator = new PerfilValidator();
        $this->fotoService = $fotoService;
    }

    public function show()
    {
        $id = (int) Request::get('id');
        if ($id === 0) {
            $id = $this->idSesion() ?? 0;
        }

        $usuario = $id ? $this->model->getUsuario($id) : null;

        if (!$usuario) {
            $data = $this->armarDatosError();
        } else {
            $data = $this->armarDatosPerfil($usuario);
        }

        $this->render->renderiza("perfil", $data);
    }

    public function editar()
    {
        $id = $this->idSesion();
        if (!$id) {
            header('Location: index.php?controller=login&method=show');
            exit;
        }

        $usuario = $this->model->getUsuario($id);
        if (!$usuario) {
            header('Location: index.php?controller=perfil&method=show');
            exit;
        }

        $data = $this->validator->paraVista($usuario);
        $data['anio_actual'] = date('Y');
        $data['vistaActual'] = 'perfil';

        $data['foto_url'] = $this->armarFotoUrl($usuario['foto'] ?? null);
        $this->render->renderiza('perfilEditar', $data);
    }

    public function actualizar()
    {
        $id = $this->idSesion();
        if (!$id) {
            header('Location: index.php?controller=login&method=show');
            exit;
        }

        $datos = $this->validator->leer($_POST);
        $errores = $this->validator->validar($datos);

        $resultadoFoto = $this->fotoService->guardar($_FILES['foto'] ?? null);
        if (!$resultadoFoto['ok']) {
            $errores[] = $resultadoFoto['error'];
        }

        if ($errores) {
            $this->fotoService->borrar($resultadoFoto['archivo'] ?? null);

            $actual = $this->model->getUsuario($id);
            $data = $this->validator->paraVista($datos);
            $data['errores'] = $errores;
            $data['anio_actual'] = date('Y');
            $data['vistaActual'] = 'perfil';
            $data['foto_url'] = $this->armarFotoUrl($actual['foto'] ?? null);
            $this->render->renderiza('perfilEditar', $data);
            return;
        }

        $d = $this->validator->normalizar($datos);

        $this->model->actualizar(
            $id,
            $d['nombre_completo'],
            $d['anio_nacimiento'],
            $d['sexo'],
            $d['pais'],
            $d['ciudad'],
            $d['latitud'],
            $d['longitud']
        );

        if ($resultadoFoto['archivo'] !== null) {
            $anterior = $this->model->getUsuario($id)['foto'] ?? null;
            $this->model->actualizarFoto($id, $resultadoFoto['archivo']);
            $this->fotoService->borrar($anterior);
        }

        header('Location: index.php?controller=perfil&method=show');
        exit;
    }

    private function idSesion()
    {
        return isset($_SESSION['usuario_id']) ? (int) $_SESSION['usuario_id'] : null;
    }

    private function armarDatosError()
    {
        return [
            "error" => "No encontramos a ese jugador.",
            "vistaActual" => "perfil"
        ];
    }

    private function armarDatosPerfil($usuario)
    {
        $data = $usuario;
        $data["error"] = "";
        $data["vistaActual"] = "perfil";
        $data["partidas"] = $this->getPartidasFormateadas($usuario["id"]);
        $data["tiene_partidas"] = !empty($data["partidas"]);
        $data["mapa_url"] = $this->mapa->getUrl($usuario["latitud"], $usuario["longitud"]);
        $data["url_perfil"] = $this->armarUrlPerfil($usuario["id"]);
        $data["url_editar"] = "index.php?controller=perfil&method=editar";
        $data["qr_url"] = $this->qr->getUrl($data["url_perfil"]);
        $data["es_propio"] = isset($_SESSION['usuario_id'])
            && (int) $_SESSION['usuario_id'] === (int) $usuario["id"];
        $data["foto_url"] = $this->armarFotoUrl($usuario["foto"] ?? null);
        $data["inicial"] = mb_strtoupper(mb_substr($usuario["usuario"], 0, 1));

        return $data;
    }

    private function getPartidasFormateadas($id)
    {
        $partidas = $this->model->getPartidas($id);

        foreach ($partidas as $clave => $partida) {
            $partidas[$clave]["fecha_formateada"] = date('d/m/Y H:i', strtotime($partida['fecha']));
        }

        return $partidas;
    }

    private function armarUrlPerfil($id)
    {
        $protocolo = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $carpeta = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');

        return $protocolo . "://" . $_SERVER['HTTP_HOST'] . $carpeta
            . "/index.php?controller=perfil&method=show&id=" . (int) $id;
    }

    private function armarFotoUrl($foto)
    {
        if (empty($foto)) {
            return null;
        }

        $carpeta = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');

        return $carpeta . '/public/uploads/perfiles/' . rawurlencode(basename($foto));
    }




}