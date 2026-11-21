<?php

namespace app;

use app\controller\HomeController;
use app\controller\LoginController;
use app\controller\RegistroController;
use app\controller\PerfilController;
use app\controller\PartidaController;
use app\controller\ContribucionController;
use app\controller\EditorController;

use app\model\LoginModel;
use app\model\RegistroModel;
use app\model\PerfilModel;
use app\model\PartidaModel;
use app\model\ContribucionModel;
use app\model\EditorModel;
use app\model\AdministradorModel;
use app\model\UsuarioInternoModel;

use app\service\MapaService;
use app\service\QrService;
use app\service\MailService;
use MustacheRender;
use MyDatabase;
use Router;
use app\service\FotoService;

require_once __DIR__ . "/../vendor/autoload.php";
require_once __DIR__ . "/../helper/Autoloader.php";

class Configuration
{
    private ?MyDatabase $database = null;

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
            $this->getMailService(),
            $this->getFotoService()
        );
    }

    public function getPerfilController()
    {
        return new PerfilController(
            $this->getPerfilModel(),
            $this->gerRender(),
            $this->getMapaService(),
            $this->getQrService(),
            $this->getFotoService()
        );
    }

    public function getPartidaController()
    {
        return new PartidaController(
            $this->getPartidaModel(),
            $this->gerRender()
        );
    }


public function getContribucionController()
{
    return new ContribucionController(
        $this->getContribucionModel(),
        $this->gerRender()
    );
}


    public function getEditorController(): EditorController
    {
        $database = $this->getDatabase();
        return new EditorController(
            new EditorModel($database),
            new PartidaModel($database),
            $this->getUsuarioInternoModel(),
            new AdministradorModel($database),
            $this->gerRender()
        );
    }

    // ───────────── Models ─────────────

    public function getUsuarioInternoModel(): UsuarioInternoModel
    {
        return new UsuarioInternoModel($this->getDatabase());
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


private function getContribucionModel()
{
    return new ContribucionModel(
        $this->getDatabase()
    );
}




    // ───────────── Infraestructura y servicios ─────────────

    private function getDatabase()
    {
        if ($this->database !== null) {
            return $this->database;
        }

        $config = parse_ini_file("config/config.ini");

        $host = $config["db_host"] . ";port=" . $config["db_port"];

        return $this->database = new MyDatabase(
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

    private function getFotoService()
    {
        return new FotoService();
    }

    private function getMailService()
    {
        return new MailService();
    }
}
