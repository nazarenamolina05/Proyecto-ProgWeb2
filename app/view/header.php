<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Partido Despedida de Messi - Entradas</title>
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
    <style>
        :root {
            --celeste: #75AADB;
            --celeste-oscuro: #4A7AAE;
            --blanco: #FFFFFF;
            --verde-cancha: #2E7D32;
            --dorado: #F4C542;
        }

        
body {
    background: #101010;
    background-attachment: fixed;
    font-family: 'Segoe UI', Arial, sans-serif;
    color: #f5f5f5;
}


      
.header-evento {
    background-color: #171717;
    border-bottom: 4px solid #d4af37;
}


        .header-evento h1 {
            color: var(--blanco);
        }

        .card-contenido {
            background-color: #171717;
            color: #f5f5f5;
            border-radius: 8px;
            border-top: 4px solid #d4af37;
        }

        .btn-cancha {
            background-color: var(--verde-cancha) !important;
            color: var(--blanco) !important;
        }

        .btn-cancha:hover {
            background-color: #1B5E20 !important;
        }

  
.footer-evento {
    background-color: #171717;
    color: #f5f5f5;
    border-top: 1px solid #d4af37;
}
        .card-contenido .w3-striped tbody tr:nth-child(even),
        .card-contenido .w3-table-all tr:nth-child(even) {
            background-color: #222222;
        }

    </style>
    <?php if (($vistaActual ?? "") === "registro"): ?>
        <link rel="stylesheet" href="/Proyecto-ProgWeb2/public/css/Registro.css">
    <?php endif; ?>
    <?php if (($vistaActual ?? "") === "login"): ?>
    <link rel="stylesheet" href="public/css/Login.css">
<?php endif; ?>
</head>
<body>

<div class="w3-bar" style="background-color:#2E7D32">
    <a href="/Proyecto-ProgWeb2/index.php" class="w3-bar-item w3-button">🏠 Inicio</a>
    <a href="/Proyecto-ProgWeb2/index.php?controller=registro&amp;method=show" class="w3-bar-item w3-button">Registrarme</a>

    <?php if (isset($_SESSION['usuario'])): ?>
        <a href="/Proyecto-ProgWeb2/index.php?controller=lugares&amp;method=show" class="w3-bar-item w3-button">Empezar a jugar</a>
        <a href="/Proyecto-ProgWeb2/perfil/show?id=<?= (int) $_SESSION['usuario_id'] ?>" class="w3-bar-item w3-button">👤 Perfil</a>
        <a href="/Proyecto-ProgWeb2/index.php?controller=login&amp;method=logout" class="w3-bar-item w3-button w3-right">🚪 Salir</a>
    <?php endif; ?>
</div>

<div class="w3-container header-evento w3-padding-32 w3-center">

    <h1>Preguntados musical</h1>
    <p class="w3-large" style="color: white;">Gana un Grammy!</p>
</div>
