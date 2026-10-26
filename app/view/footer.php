
<footer class="w3-container footer-evento w3-padding-16 w3-center w3-margin-top">
    <?php if (in_array(($vistaActual ?? ""), ["evento", "registro"], true)): ?>
        <p>The Grammys Trivia</p>
        <p class="w3-tiny">Una celebración del talento y la historia de la música.</p>
    <?php else: ?>
    <p>Curso de Programación Web - Ejemplo MVC</p>
    <p class="w3-tiny">Foto: <a href="https://commons.wikimedia.org/wiki/File:Lionel_Messi_WC2022.jpg"
                                style="color:white">Wikimedia Commons (CC BY 4.0)</a></p>
    <?php endif; ?>
</footer>

</body>
</html>
