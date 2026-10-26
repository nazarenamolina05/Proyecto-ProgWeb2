<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>The Grammys Trivia</title>
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
    <?php if (in_array(($vistaActual ?? ""), ["evento", "registro"], true)): ?>
        <link rel="stylesheet" href="/Proyecto-ProgWeb2/public/css/Grammys.css">
    <?php else: ?>
    <style>
        :root {
            --celeste: #75AADB;
            --celeste-oscuro: #4A7AAE;
            --blanco: #FFFFFF;
            --verde-cancha: #2E7D32;
            --dorado: #F4C542;
        }

        body {
            background: linear-gradient(180deg,
            var(--celeste) 0%, var(--celeste) 15%,
            var(--blanco) 15%, var(--blanco) 30%,
            var(--celeste) 30%, var(--celeste) 100%);
            background-attachment: fixed;
            font-family: 'Segoe UI', Arial, sans-serif;

        }

        .header-evento {
            background-color: var(--celeste-oscuro);
            border-bottom: 6px solid var(--dorado);
        }

        .header-evento h1 {
            color: var(--blanco);
        }

        .card-contenido {
            background-color: var(--blanco);
            border-radius: 8px;
            border-top: 6px solid var(--verde-cancha);
        }

        .btn-cancha {
            background-color: var(--verde-cancha) !important;
            color: var(--blanco) !important;
        }

        .btn-cancha:hover {
            background-color: #1B5E20 !important;
        }

        .footer-evento {
            background-color: var(--celeste-oscuro);
            color: var(--blanco);
        }
    </style>
    <?php endif; ?>
    <?php if (($vistaActual ?? "") === "registro"): ?>
        <link rel="stylesheet" href="/Proyecto-ProgWeb2/public/css/Registro.css">
    <?php endif; ?>
</head>
<body<?= in_array(($vistaActual ?? ""), ["evento", "registro"], true) ? ' class="pagina-grammys"' : "" ?>>

<?php if (in_array(($vistaActual ?? ""), ["evento", "registro"], true)): ?>
<nav class="grammys-nav" aria-label="Navegación principal">
    <a class="grammys-brand" href="/Proyecto-ProgWeb2/" aria-label="The Grammys Trivia, inicio">
        <span>THE<br>GRAMMYS</span><b>TRIVIA</b>
    </a>
    <div class="grammys-nav-links">
        <?php if (($vistaActual ?? "") === "evento"): ?>
            <a href="/Proyecto-ProgWeb2/">Inicio</a>
            <a href="#categorias">Categorías</a>
            <?php if (isset($_SESSION['usuario'])): ?>
                <a class="grammys-nav-cta" href="#acceso">Mi cuenta</a>
            <?php else: ?>
                <a href="#acceso">Iniciar sesión</a>
                <a class="grammys-nav-cta" href="/Proyecto-ProgWeb2/index.php?controller=registro&amp;method=show">Registrarse</a>
            <?php endif; ?>
        <?php else: ?>
            <a href="/Proyecto-ProgWeb2/">Inicio</a>
            <a href="/Proyecto-ProgWeb2/#categorias">Categorías</a>
            <a class="grammys-nav-cta" href="/Proyecto-ProgWeb2/#acceso">Iniciar sesión</a>
        <?php endif; ?>
    </div>
</nav>
<?php else: ?>
<div class="w3-bar" style="background-color:#2E7D32">
    <a href="/Proyecto-ProgWeb2/evento/show" class="w3-bar-item w3-button">🏠 Inicio</a>
    <a href="/Proyecto-ProgWeb2/index.php?controller=registro&amp;method=show" class="w3-bar-item w3-button">Registrarme</a>
    <?php if (isset($_SESSION['usuario'])): ?>
        <a href="/Proyecto-ProgWeb2/lugares" class="w3-bar-item w3-button">Empezar a jugar</a>
        <a href="/Proyecto-ProgWeb2/perfil/show?id=<?= (int) $_SESSION['usuario_id'] ?>" class="w3-bar-item w3-button">👤 Perfil</a>
        <a href="/Proyecto-ProgWeb2/login/logout" class="w3-bar-item w3-button w3-right">🚪 Salir</a>
    <?php endif; ?>
</div>

<div class="w3-container header-evento w3-padding-32 w3-center">

    <h1>Preguntados musical</h1>
    <p class="w3-large" style="color: white;">Gana un Grammy!</p>
</div>
<?php endif; ?>
