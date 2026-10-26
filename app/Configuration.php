<?php

namespace app;

use app\controller\EventoController;
use app\controller\LoginController;
use app\controller\LugaresController;
use app\controller\ReservaController;
use app\model\EventoModel;
use app\model\LoginModel;
use app\model\LugaresModel;
use app\model\ReservaModel;
use MustacheRender;
use MyDatabase;
use Router;
use app\controller\RegistroController;
use app\model\RegistroModel;
use app\controller\PerfilController;
use app\model\PerfilModel;
use app\service\MapaService;
use app\service\QrService;
require_once("helper/Autoloader.php");




class Configuration
{

    public function __construct()
    {
    }

    public function getLugaresController()
    {
        return new LugaresController(
            $this->getLugaresModel(),
            $this->gerRender()
        );
    }
    public function getReservaController()
    {
        return new ReservaController(
            $this->getReservaModel(),
            $this->gerRender()
        );
    }

    public function getEventoController()
    {
        return new EventoController(
            $this->getEventoModel(),
            $this->gerRender()
        );
    }

    public function getLoginController()
    {
        return new LoginController(
            $this->getLoginModel(),
            $this->gerRender()
        );
    }

    // Los privados
    private function getReservaModel()
    {
        return new ReservaModel(
            $this->getDatabase()
        );
    }

    private function getLugaresModel()
    {
        return new LugaresModel(
            $this->getDatabase()
        );
    }

        private function getDatabase()
    {
        $config = parse_ini_file("config/config.ini");

        $host = $config["db_host"] . ";port=" . $config["db_port"];

        return new MyDatabase(
            $host,
            $config["db_user"],
            $config["db_pass"],
            $config["db_name"]
        );
    }

    private function getEventoModel()
    {
        return new EventoModel(
            $this->getDatabase()
        );
    }

    private function getLoginModel()
    {
        return new LoginModel(
            $this->getDatabase()
        );
    }
    private function getRegistroModel()
    {
        return new RegistroModel(
            $this->getDatabase()
        );
    }

    public function getRouter()
    {
        return new Router($this, "evento", "show");
    }
    public function getRegistroController(): RegistroController
    {
        return new RegistroController(
            $this->getRegistroModel(),
            $this->gerRender()
        );
    }

    private function getPerfilModel()
    {
        return new PerfilModel(
            $this->getDatabase()
        );
    }

    public function getPerfilController()
    {
        return new PerfilController(
            $this->getPerfilModel(),
            $this->gerRender(),
            $this->getMapaService(),
            $this->getQrService()
        );
    }

    private function gerRender()
    {
        return new MustacheRender();
    }


    private function getMapaService()
    {
        return new MapaService();
    }

    private function getQrService()
    {
        return new QrService();
    }


}
