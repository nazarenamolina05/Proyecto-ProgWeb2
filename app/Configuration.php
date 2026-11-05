<?php

namespace app;

use app\controller\HomeController;
use app\controller\LoginController;
use app\controller\RegistroController;
use app\controller\PerfilController;
use app\controller\PartidaController;
use app\model\LoginModel;
use app\model\RegistroModel;
use app\model\PerfilModel;
use app\model\PartidaModel;
use app\service\MapaService;
use app\service\QrService;
use app\service\MailService;
use MustacheRender;
use MyDatabase;
use Router;

require_once("helper/Autoloader.php");

class Configuration
{
    public function __construct()
    {
    }

    // ───────────── Router ─────────────

    public function getRouter()
    {
        return new Router($this, "home", "show");
    }

    // ───────────── Controllers ─────────────

    public function getHomeController()
    {
        return new HomeController(
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

    public function getRegistroController(): RegistroController
    {
        return new RegistroController(
            $this->getRegistroModel(),
            $this->gerRender(),
            $this->getMailService()
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

    public function getPartidaController()
    {
        return new PartidaController(
            $this->getPartidaModel(),
            $this->gerRender()
        );
    }

    // ───────────── Models ─────────────

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

    private function getPerfilModel()
    {
        return new PerfilModel(
            $this->getDatabase()
        );
    }

    private function getPartidaModel()
    {
        return new PartidaModel(
            $this->getDatabase()
        );
    }

    // ───────────── Infraestructura y servicios ─────────────

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

    private function getMailService()
    {
        return new MailService();
    }
}