USE entradas_messi;

-- TABLA DE REPORTES
CREATE TABLE IF NOT EXISTS reportes_preguntas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    pregunta_id INT UNSIGNED NOT NULL,
    motivo TEXT NULL,
    estado ENUM('pendiente', 'resuelto') NOT NULL DEFAULT 'pendiente',
    decision ENUM('conservar', 'baja') NULL,
    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
    FOREIGN KEY (pregunta_id) REFERENCES preguntas(id),

    UNIQUE KEY reporte_usuario_pregunta (usuario_id, pregunta_id)
);

-- TABLA DE SUGERENCIAS
CREATE TABLE IF NOT EXISTS preguntas_sugeridas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    enunciado VARCHAR(255) NOT NULL,
    opcion_1 VARCHAR(150) NOT NULL,
    opcion_2 VARCHAR(150) NOT NULL,
    opcion_3 VARCHAR(150) NOT NULL,
    correcta ENUM('1', '2', '3') NOT NULL,
    categoria_id INT UNSIGNED NOT NULL,
    dificultad ENUM('FACIL', 'INTERMEDIO', 'DIFICIL')
        NOT NULL DEFAULT 'INTERMEDIO',
    estado ENUM('pendiente', 'aprobada', 'rechazada')
        NOT NULL DEFAULT 'pendiente',
    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
    FOREIGN KEY (categoria_id) REFERENCES categorias(id)
);
