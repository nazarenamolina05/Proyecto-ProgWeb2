<?php

namespace app\controller;

use app\model\AdministradorModel;
use app\model\EditorModel;
use app\model\PartidaModel;
use app\model\UsuarioInternoModel;
use DateTimeImmutable;
use InvalidArgumentException;
use Log;
use PDOException;
use Redirect;

class EditorController
{
    private const PANEL = '/index.php?controller=editor&method=show';

    public function __construct(
        private EditorModel $model,
        private PartidaModel $partida,
        private UsuarioInternoModel $usuarios,
        private AdministradorModel $administracion,
        private $render
    ) {
    }

    public function show(): void
    {
        $seccion = $_GET['seccion'] ?? '';
        $rolRequerido = match ($seccion) {
            'cuentas', 'estadisticas' => 'administrador',
            'reportes', 'sugerencias' => 'editor',
            default => null,
        };
        if (!$this->autorizar($rolRequerido)) {
            return;
        }

        if ($seccion === 'estadisticas') {
            $this->mostrarEstadisticas();
            return;
        }

        $administrador = $_SESSION['rol'] === 'administrador';
        $contenido = match ($seccion) {
            'preguntas' => ['ver_preguntas' => true, 'preguntas' => $this->model->listarPreguntas()],
            'sugerencias' => ['ver_sugerencias' => true, 'sugerencias' => $this->model->listarSugerencias()],
            'reportes' => ['ver_reportes' => true, 'reportes' => $this->model->listarReportes()],
            'cuentas' => ['ver_cuentas' => true, 'editores' => $this->usuarios->listarEditores()],
            default => ['inicio' => true],
        };

        $this->render->renderiza('editorPanel', $contenido + [
            'titulo' => $administrador ? 'Panel de administración' : 'Panel de edición',
            'nombre_editor' => $_SESSION['usuario'] ?? null,
            'administrador' => $administrador,
            'editor' => $_SESSION['rol'] === 'editor',
            'token' => $this->token(),
            'mensaje' => $_SESSION['editor_mensaje'] ?? null,
            'error' => $_SESSION['editor_error'] ?? null,
        ]);
        unset($_SESSION['editor_mensaje'], $_SESSION['editor_error']);
    }

    public function formulario(): void
    {
        if (!$this->autorizar()) {
            return;
        }

        $id = $this->id($_GET['id'] ?? null);
        $pregunta = $id === null ? [] : $this->model->obtenerPregunta($id);
        $origen = $_SESSION['rol'] === 'editor' && ($_GET['origen'] ?? null) === 'reportes' ? 'reportes' : 'preguntas';
        if (isset($_GET['id']) && ($id === null || $pregunta === null)) {
            $this->volver('No se encontró la pregunta.', true, $origen);
            return;
        }

        $this->mostrarFormulario($id, $pregunta, $origen);
    }

    public function guardar(): void
    {
        if (!$this->validarPost()) {
            return;
        }

        $idRecibido = $_POST['id'] ?? '';
        $id = $this->id($idRecibido);
        $origen = $_SESSION['rol'] === 'editor' && ($_POST['origen'] ?? null) === 'reportes' ? 'reportes' : 'preguntas';
        if ($idRecibido !== '' && ($id === null || $this->model->obtenerPregunta($id) === null)) {
            $this->volver('No se encontró la pregunta.', true, $origen);
            return;
        }

        $pregunta = [];
        foreach (['enunciado', 'opcion_1', 'opcion_2', 'opcion_3', 'correcta', 'categoria_id', 'dificultad', 'imagen'] as $campo) {
            $valor = $_POST[$campo] ?? '';
            $pregunta[$campo] = is_string($valor) ? trim($valor) : '';
        }

        $error = $this->validarPregunta($pregunta);
        if ($error !== null) {
            $this->mostrarFormulario($id, $pregunta, $origen, $error);
            return;
        }

        $pregunta['imagen'] = $pregunta['imagen'] === '' ? null : $pregunta['imagen'];
        try {
            $this->model->guardarPregunta($id, array_values($pregunta));
        } catch (PDOException $ex) {
            Log::error('Error al guardar pregunta. SQLSTATE: ' . $ex->getCode());
            $this->mostrarFormulario($id, $pregunta, $origen, 'No se pudo guardar la pregunta. Volvé a intentarlo.');
            return;
        }

        $this->volver($id === null ? 'Pregunta publicada.' : 'Pregunta actualizada.', false, $origen);
    }

    public function estadoPregunta(): void
    {
        if (!$this->validarPost()) {
            return;
        }

        $id = $this->id($_POST['id'] ?? null);
        $accion = $_POST['accion'] ?? null;
        if ($id === null || !in_array($accion, ['baja', 'activar'], true) || $this->model->obtenerPregunta($id) === null) {
            $this->volver('La pregunta o la acción no es válida.', true);
            return;
        }

        $activa = $accion === 'activar';
        $this->model->cambiarEstadoPregunta($id, $activa);
        $this->volver($activa ? 'Pregunta reactivada.' : 'Pregunta dada de baja.');
    }

    public function resolverReporte(): void
    {
        if (!$this->validarPost('editor')) {
            return;
        }

        $id = $this->id($_POST['id'] ?? null);
        $decision = $_POST['decision'] ?? null;
        if ($id === null || !in_array($decision, ['conservar', 'baja'], true)) {
            $this->volver('El reporte o la decisión no es válida.', true, 'reportes');
            return;
        }

        try {
            $resuelto = $this->model->resolverReporte($id, $decision);
        } catch (PDOException $ex) {
            Log::error('Error al resolver reporte. SQLSTATE: ' . $ex->getCode());
            $this->volver('No se pudo resolver el reporte. Volvé a intentarlo.', true, 'reportes');
            return;
        }

        $mensaje = $decision === 'baja' ? 'Reporte resuelto y pregunta dada de baja.' : 'Reporte resuelto. Se conservó la pregunta.';
        $this->volver($resuelto ? $mensaje : 'El reporte ya no está pendiente.', !$resuelto, 'reportes');
    }

    public function resolverSugerencia(): void
    {
        if (!$this->validarPost('editor')) {
            return;
        }

        $id = $this->id($_POST['id'] ?? null);
        $decision = $_POST['decision'] ?? null;
        if ($id === null || !in_array($decision, ['aprobar', 'rechazar'], true)) {
            $this->volver('La sugerencia o la decisión no es válida.', true, 'sugerencias');
            return;
        }

        $aprobada = $decision === 'aprobar';
        try {
            $resuelta = $this->model->resolverSugerencia($id, $aprobada);
        } catch (PDOException $ex) {
            Log::error('Error al resolver sugerencia. SQLSTATE: ' . $ex->getCode());
            $this->volver('No se pudo resolver la sugerencia. Volvé a intentarlo.', true, 'sugerencias');
            return;
        }

        $mensaje = $aprobada ? 'Sugerencia aprobada y publicada.' : 'Sugerencia rechazada.';
        $this->volver($resuelta ? $mensaje : 'La sugerencia ya no está pendiente.', !$resuelta, 'sugerencias');
    }

    public function crearCuenta(): void
    {
        if (!$this->validarPost('administrador')) {
            return;
        }

        $usuario = $_POST['usuario'] ?? null;
        $password = $_POST['password'] ?? null;
        if (!is_string($usuario) || !is_string($password)) {
            $this->volver('Completá los datos del editor.', true, 'cuentas');
            return;
        }

        try {
            $this->usuarios->crear(trim($usuario), $password, 'editor');
        } catch (InvalidArgumentException $ex) {
            $this->volver($ex->getMessage(), true, 'cuentas');
            return;
        } catch (PDOException $ex) {
            $duplicado = ($ex->errorInfo[1] ?? null) === 1062;
            Log::error('Error al crear editor. SQLSTATE: ' . $ex->getCode());
            $this->volver($duplicado ? 'Ese nombre de usuario ya existe.' : 'No se pudo crear la cuenta.', true, 'cuentas');
            return;
        }

        $this->volver('Editor creado. Ya puede iniciar sesión desde el inicio.', false, 'cuentas');
    }

    public function estadoEditor(): void
    {
        if (!$this->validarPost('administrador')) {
            return;
        }

        $id = $this->id($_POST['id'] ?? null);
        $accion = $_POST['accion'] ?? null;
        if ($id === null || !in_array($accion, ['baja', 'activar'], true)) {
            $this->volver('El editor o la acción no es válida.', true, 'cuentas');
            return;
        }

        $habilitado = $accion === 'activar';
        try {
            $actualizado = $this->usuarios->cambiarEstadoEditor($id, $habilitado);
        } catch (PDOException $ex) {
            Log::error('Error al cambiar estado del editor. SQLSTATE: ' . $ex->getCode());
            $this->volver('No se pudo cambiar el estado del editor.', true, 'cuentas');
            return;
        }

        $mensaje = $habilitado ? 'Editor reactivado.' : 'Editor dado de baja.';
        $this->volver($actualizado ? $mensaje : 'No se encontró el editor.', !$actualizado, 'cuentas');
    }

    private function mostrarEstadisticas(): void
    {
        $etiquetas = ['dia' => 'Día', 'semana' => 'Semana', 'mes' => 'Mes', 'anio' => 'Año'];
        $periodo = $_GET['periodo'] ?? null;
        $periodo = is_string($periodo) && isset($etiquetas[$periodo]) ? $periodo : 'mes';
        $hoy = new DateTimeImmutable('today');
        $fechaEntrada = $_GET['fecha'] ?? null;
        $fecha = is_string($fechaEntrada) && preg_match('/^\d{4}-\d{2}-\d{2}$/D', $fechaEntrada)
            ? DateTimeImmutable::createFromFormat('!Y-m-d', $fechaEntrada)
            : false;
        if ($fecha === false || $fecha->format('Y-m-d') !== $fechaEntrada || $fecha > $hoy || $fecha->format('Y') < 1900) {
            $fecha = $hoy;
        }

        [$inicio, $fin] = match ($periodo) {
            'dia' => [$fecha, $fecha->modify('+1 day')],
            'semana' => [$fecha->modify('monday this week'), $fecha->modify('monday next week')],
            'mes' => [$fecha->modify('first day of this month'), $fecha->modify('first day of next month')],
            'anio' => [$fecha->setDate((int) $fecha->format('Y'), 1, 1), $fecha->setDate((int) $fecha->format('Y') + 1, 1, 1)],
        };

        $opciones = [];
        foreach ($etiquetas as $valor => $nombre) {
            $opciones[] = ['valor' => $valor, 'nombre' => $nombre, 'seleccionada' => $valor === $periodo];
        }

        $this->render->renderiza('adminEstadisticas', $this->administracion->estadisticas($inicio, $fin) + [
            'periodos' => $opciones,
            'fecha' => $fecha->format('Y-m-d'),
            'hoy' => $hoy->format('Y-m-d'),
            'desde' => $inicio->format('d/m/Y'),
            'hasta' => $fin->modify('-1 day')->format('d/m/Y'),
        ]);
    }

    private function mostrarFormulario(?int $id, array $pregunta, string $origen, ?string $error = null): void
    {
        $categorias = $this->partida->obtenerCategorias();
        foreach ($categorias as &$categoria) {
            $categoria['seleccionada'] = (string) $categoria['id'] === (string) ($pregunta['categoria_id'] ?? '');
        }
        unset($categoria);

        $dificultades = [];
        foreach (['FACIL' => 'Fácil', 'INTERMEDIO' => 'Intermedio', 'DIFICIL' => 'Difícil'] as $valor => $nombre) {
            $dificultades[] = [
                'valor' => $valor,
                'nombre' => $nombre,
                'seleccionada' => $valor === ($pregunta['dificultad'] ?? 'INTERMEDIO'),
            ];
        }

        $this->render->renderiza('editorPregunta', $pregunta + [
            'titulo' => $id === null ? 'Nueva pregunta' : 'Editar pregunta',
            'id' => $id,
            'origen' => $origen,
            'categorias' => $categorias,
            'dificultades' => $dificultades,
            'correcta_1' => (string) ($pregunta['correcta'] ?? '') === '1',
            'correcta_2' => (string) ($pregunta['correcta'] ?? '') === '2',
            'correcta_3' => (string) ($pregunta['correcta'] ?? '') === '3',
            'token' => $this->token(),
            'error' => $error,
        ]);
    }

    private function validarPregunta(array $pregunta): ?string
    {
        foreach (['enunciado' => 255, 'opcion_1' => 150, 'opcion_2' => 150, 'opcion_3' => 150] as $campo => $maximo) {
            $longitud = mb_strlen($pregunta[$campo], 'UTF-8');
            if ($longitud === 0 || $longitud > $maximo) {
                return 'Completá el enunciado y las tres opciones dentro del límite indicado.';
            }
        }

        $categoriaId = $this->id($pregunta['categoria_id']);
        if (!in_array($pregunta['correcta'], ['1', '2', '3'], true) ||
            !in_array($pregunta['dificultad'], ['FACIL', 'INTERMEDIO', 'DIFICIL'], true) ||
            $categoriaId === null || $this->partida->obtenerCategoria($categoriaId) === null) {
            return 'Elegí una respuesta correcta, una categoría y una dificultad válidas.';
        }

        $imagen = $pregunta['imagen'];
        if ($imagen !== '' && (strlen($imagen) > 500 || !filter_var($imagen, FILTER_VALIDATE_URL) ||
            !in_array(strtolower((string) parse_url($imagen, PHP_URL_SCHEME)), ['http', 'https'], true))) {
            return 'Ingresá una URL HTTP o HTTPS válida para la imagen.';
        }

        return null;
    }

    private function autorizar(?string $rolRequerido = null): bool
    {
        if (!isset($_SESSION['usuario_id'])) {
            Redirect::to('/index.php?controller=home&method=show#acceso');
            return false;
        }

        $rol = $_SESSION['rol'] ?? 'jugador';
        if (!UsuarioInternoModel::puedeEditar($rol) || ($rolRequerido !== null && $rol !== $rolRequerido)) {
            http_response_code(403);
            echo 'No tenés permiso para acceder a esta función.';
            return false;
        }

        return true;
    }

    private function validarPost(?string $rolRequerido = null): bool
    {
        if (!$this->autorizar($rolRequerido)) {
            return false;
        }

        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            http_response_code(405);
            header('Allow: POST');
            return false;
        }

        $token = $_POST['token'] ?? null;
        if (!is_string($token) || !hash_equals($this->token(), $token)) {
            http_response_code(403);
            echo 'El formulario venció. Volvé a abrir el panel.';
            return false;
        }

        return true;
    }

    private function token(): string
    {
        return $_SESSION['editor_token'] ??= bin2hex(random_bytes(32));
    }

    private function id(mixed $valor): ?int
    {
        $id = filter_var($valor, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        return $id === false ? null : $id;
    }

    private function volver(string $mensaje, bool $error = false, string $seccion = 'preguntas'): void
    {
        $_SESSION[$error ? 'editor_error' : 'editor_mensaje'] = $mensaje;
        Redirect::to(self::PANEL . '&seccion=' . $seccion);
    }
}
