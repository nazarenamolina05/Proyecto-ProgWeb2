USE entradas_messi;

-- 1) Agregamos a usuarios los datos que pide el enunciado para el perfil
ALTER TABLE usuarios
    ADD COLUMN nombre_completo VARCHAR(100) NULL,
    ADD COLUMN anio_nacimiento INT NULL,
    ADD COLUMN sexo ENUM('Masculino','Femenino','Prefiero no cargarlo') NULL,
    ADD COLUMN pais VARCHAR(50) NULL,
    ADD COLUMN ciudad VARCHAR(50) NULL,
    ADD COLUMN latitud DECIMAL(9,6) NULL,
    ADD COLUMN longitud DECIMAL(9,6) NULL,
    ADD COLUMN foto VARCHAR(255) NULL;

-- 2) Tabla de partidas: de acá sale el puntaje total y las partidas realizadas
CREATE TABLE partidas (
                          id INT AUTO_INCREMENT PRIMARY KEY,
                          usuario_id INT NOT NULL,
                          puntaje INT NOT NULL,
                          fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
                          FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

-- 3) Datos de prueba
UPDATE usuarios SET nombre_completo = 'Facundo Pérez', anio_nacimiento = 1998, sexo = 'Masculino',
                    pais = 'Argentina', ciudad = 'Córdoba', latitud = -31.420100, longitud = -64.188800
WHERE usuario = 'facu';

UPDATE usuarios SET nombre_completo = 'Hernán Gómez', anio_nacimiento = 2001, sexo = 'Prefiero no cargarlo',
                    pais = 'Uruguay', ciudad = 'Montevideo', latitud = -34.901100, longitud = -56.164500
WHERE usuario = 'hincha';

INSERT INTO usuarios (usuario, password, nombre_completo, anio_nacimiento, sexo, pais, ciudad, latitud, longitud)
VALUES ('naza', MD5('1234'), 'Nazarena Molina', 2005, 'Femenino', 'Argentina', 'Buenos Aires', -34.603700, -58.381600);

INSERT INTO partidas (usuario_id, puntaje) SELECT id, 8 FROM usuarios WHERE usuario = 'naza';
INSERT INTO partidas (usuario_id, puntaje) SELECT id, 12 FROM usuarios WHERE usuario = 'naza';
INSERT INTO partidas (usuario_id, puntaje) SELECT id, 5 FROM usuarios WHERE usuario = 'facu';
INSERT INTO partidas (usuario_id, puntaje) SELECT id, 10 FROM usuarios WHERE usuario = 'hincha';