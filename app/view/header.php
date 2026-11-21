<?php
$esEstadisticas = ($vistaActual ?? "") === "adminEstadisticas";
$esPanelInterno = $esEstadisticas || in_array(($vistaActual ?? ""), ["editorPanel", "editorPregunta"], true);
$esInicio = ($vistaActual ?? '') === 'home';
$baseUrl = htmlspecialchars($base, ENT_QUOTES, 'UTF-8');
$clasesBody = 'pagina-grammys';
if ($esPanelInterno) {
    $clasesBody .= $esEstadisticas ? ' pagina-editor pagina-estadisticas' : ' pagina-editor';
}
?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>The Grammys Trivia</title>
        <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">

        <link rel="stylesheet" href="<?= $baseUrl ?>/public/css/Grammys.css">

        <?php if (in_array(($vistaActual ?? ""), ["registro", "verificacion", "perfilEditar"], true)): ?>
            <link rel="stylesheet" href="<?= $baseUrl ?>/public/css/Registro.css">
        <?php endif; ?>
        <?php if (($vistaActual ?? "") === "perfil"): ?>
            <link rel="stylesheet" href="<?= $baseUrl ?>/public/css/Perfil.css">
        <?php endif; ?>
        <?php if (($vistaActual ?? "") === "login"): ?>
            <link rel="stylesheet" href="<?= $baseUrl ?>/public/css/Login.css">
        <?php endif; ?>
        <?php if (($vistaActual ?? "") === "comenzarPartida"): ?>
            <link
                    rel="stylesheet"
                    href="<?= $baseUrl ?>/public/css/Partida.css"
            >
        <?php endif; ?>
        <?php if ($esPanelInterno): ?>
            <link rel="stylesheet" href="<?= $baseUrl ?>/public/css/Editor.css">
        <?php endif; ?>
    </head>
<body class="<?= $clasesBody ?>">

<nav class="grammys-nav" aria-label="Navegación principal">
    <a class="grammys-brand" href="<?= $baseUrl ?><?= $esPanelInterno ? '/index.php?controller=editor&amp;method=show' : '/' ?>" aria-label="The Grammys Trivia, <?= $esPanelInterno ? 'panel' : 'inicio' ?>">
        <span>THE<br>GRAMMYS</span><b>TRIVIA</b>
    </a>
    <div class="grammys-nav-links">
        <?php if ($esPanelInterno): ?>
            <a href="<?= $baseUrl ?>/index.php?controller=editor&amp;method=show">Panel</a>
            <a class="grammys-nav-cta" href="<?= $baseUrl ?>/index.php?controller=login&amp;method=logout">Salir</a>
        <?php else: ?>
            <a href="<?= $baseUrl ?>/">Inicio</a>
            <a href="<?= $esInicio ? '' : $baseUrl . '/' ?>#categorias">Categorías</a>
            <?php if (isset($_SESSION['usuario_id'])): ?>
                <a href="<?= $baseUrl ?>/index.php?controller=partida&amp;method=show">Empezar a jugar</a>
                <a href="<?= $baseUrl ?>/index.php?controller=perfil&amp;method=show&amp;id=<?= (int) $_SESSION['usuario_id'] ?>">Perfil</a>
                <a class="grammys-nav-cta" href="<?= $baseUrl ?>/index.php?controller=login&amp;method=logout">Salir</a>
            <?php else: ?>
                <a href="<?= $esInicio ? '' : $baseUrl . '/' ?>#acceso">Iniciar sesión</a>
                <a class="grammys-nav-cta" href="<?= $baseUrl ?>/index.php?controller=registro&amp;method=show">Registrarse</a>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</nav>
