USE entradas_messi;
CREATE TABLE preguntas (
                           id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                           enunciado VARCHAR(255) NOT NULL,
                           opcion_1 VARCHAR(150) NOT NULL,
                           opcion_2 VARCHAR(150) NOT NULL,
                           opcion_3 VARCHAR(150) NOT NULL,
                           correcta ENUM('1', '2', '3') NOT NULL
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

INSERT INTO preguntas
(enunciado, opcion_1, opcion_2, opcion_3, correcta)
VALUES
    (
        '¿Qué banda interpretó Bohemian Rhapsody?',
        'Queen',
        'The Beatles',
        'Coldplay',
        '1'
    ),
    (
        '¿Quién interpretó Thriller?',
        'Prince',
        'Michael Jackson',
        'Bruno Mars',
        '2'
    ),
    (
        '¿Cuál de estos instrumentos tiene teclas?',
        'Violín',
        'Batería',
        'Piano',
        '3'
    );