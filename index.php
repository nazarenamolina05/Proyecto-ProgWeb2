<?php
date_default_timezone_set('America/Argentina/Buenos_Aires');
error_reporting(E_ALL & ~E_DEPRECATED);
use app\Configuration;
use app\model\UsuarioInternoModel;

session_start();
require_once("app/Configuration.php");

$configuration = new Configuration();
$controller = Request::get('controller', 'home');
$method = Request::get('method', 'show');

$controller = is_string($controller) ? strtolower($controller) : 'home';
$method = is_string($method) ? $method : 'show';

$cerrandoSesion = $controller === 'login' && strcasecmp($method, 'logout') === 0;
if (isset($_SESSION['usuario_id']) && !$cerrandoSesion) {
    $rol = $configuration->getUsuarioInternoModel()->obtenerRol((int) $_SESSION['usuario_id']);
    if ($rol === null) {
        $_SESSION = [];
        session_regenerate_id(true);
    } else {
        $_SESSION['rol'] = $rol;
        if (UsuarioInternoModel::puedeEditar($rol) && $controller !== 'editor') {
            Redirect::to('/index.php?controller=editor&method=show');
        }
    }
}

if ($controller === 'partida' && !isset($_SESSION['usuario_id'])) {
    Redirect::to('/index.php?controller=login&method=show');
}

$configuration->getRouter()->dispatch($controller, $method);
