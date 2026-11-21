<?php

namespace app\model;

use InvalidArgumentException;

class UsuarioInternoModel
{
    public function __construct(private $database)
    {
    }

    public static function puedeEditar(string $rol): bool
    {
        return in_array($rol, ['editor', 'administrador'], true);
    }

    public function obtenerRol(int $usuarioId): ?string
    {
        $usuario = $this->database->queryOnePrepared(
            'SELECT rol FROM usuarios WHERE id = ? AND verificado = 1 AND habilitado = 1',
            [$usuarioId]
        );

        return $usuario['rol'] ?? null;
    }

    public function listarEditores(): array
    {
        return $this->database->queryAll(
            "SELECT id, usuario, habilitado FROM usuarios WHERE rol = 'editor' ORDER BY habilitado DESC, usuario"
        );
    }

    public function cambiarEstadoEditor(int $id, bool $habilitado): bool
    {
        $editor = $this->database->queryOnePrepared("SELECT id FROM usuarios WHERE id = ? AND rol = 'editor'", [$id]);
        if ($editor === null) {
            return false;
        }

        return $this->database->executePrepared(
            "UPDATE usuarios SET habilitado = ? WHERE id = ? AND rol = 'editor'",
            [(int) $habilitado, $id]
        );
    }

    public function crear(string $usuario, string $password, string $rol): void
    {
        if (!self::puedeEditar($rol) || !(new RegistroModel($this->database))->usuarioValido($usuario)) {
            throw new InvalidArgumentException('Ingresá un usuario de 3 a 50 caracteres con letras, números o guiones bajos y elegí un rol interno.');
        }

        $longitudBytes = strlen($password);
        $esEditor = $rol === 'editor';
        $cumpleMinimo = $esEditor ? mb_strlen($password, 'UTF-8') >= 3 : $longitudBytes >= 12;
        if (!$cumpleMinimo || $longitudBytes > 72 || str_contains($password, "\0")) {
            $mensaje = $esEditor
                ? 'La contraseña del editor debe tener al menos 3 caracteres, hasta 72 bytes y ningún carácter nulo.'
                : 'La contraseña debe tener entre 12 y 72 bytes.';
            throw new InvalidArgumentException($mensaje);
        }

        $this->database->executePrepared(
            'INSERT INTO usuarios (usuario, password, verificado, rol) VALUES (?, ?, 1, ?)',
            [$usuario, password_hash($password, PASSWORD_DEFAULT), $rol]
        );
    }
}
