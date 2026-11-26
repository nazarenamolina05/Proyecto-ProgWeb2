<?php

use app\model\UsuarioInternoModel;

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../../helper/Autoloader.php';

if ($argc !== 3 || !UsuarioInternoModel::puedeEditar($argv[1])) {
    fwrite(STDERR, "Uso: php app/cli/crearCuentaInterna.php <editor|administrador> <usuario>\n");
    exit(1);
}

$config = parse_ini_file(__DIR__ . '/../../config/config.ini');
if ($config === false) {
    fwrite(STDERR, "No se pudo leer la configuración de la base de datos.\n");
    exit(1);
}

try {
    $database = new MyDatabase(
        $config['db_host'] . ';port=' . $config['db_port'],
        $config['db_user'],
        $config['db_pass'],
        $config['db_name']
    );
    $password = bin2hex(random_bytes(16));
    (new UsuarioInternoModel($database))->crear($argv[2], $password, $argv[1]);
} catch (InvalidArgumentException $ex) {
    fwrite(STDERR, $ex->getMessage() . PHP_EOL);
    exit(1);
} catch (PDOException $ex) {
    $mensaje = ($ex->errorInfo[1] ?? null) === 1062
        ? 'Ese usuario ya existe.'
        : 'No se pudo crear la cuenta. Revisá la conexión y el esquema de usuarios.';
    fwrite(STDERR, $mensaje . PHP_EOL);
    exit(1);
}

echo "Usuario: {$argv[2]}", PHP_EOL;
echo "Contraseña inicial: $password", PHP_EOL;
