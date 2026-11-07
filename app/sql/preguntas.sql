USE entradas_messi;
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS categorias (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(80) NOT NULL UNIQUE
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS preguntas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    enunciado VARCHAR(255) NOT NULL,
    opcion_1 VARCHAR(150) NOT NULL,
    opcion_2 VARCHAR(150) NOT NULL,
    opcion_3 VARCHAR(150) NOT NULL,
    correcta ENUM('1', '2', '3') NOT NULL,
    categoria_id INT UNSIGNED NULL,
    imagen VARCHAR(500) NULL
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Agrega las columnas solamente si todavía no existen.
SET @sql = IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'preguntas'
       AND COLUMN_NAME = 'categoria_id') = 0,
    'ALTER TABLE preguntas ADD COLUMN categoria_id INT UNSIGNED NULL',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'preguntas'
       AND COLUMN_NAME = 'imagen') = 0,
    'ALTER TABLE preguntas ADD COLUMN imagen VARCHAR(500) NULL',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

START TRANSACTION;

INSERT INTO categorias (nombre) VALUES
('Artistas y bandas'),
('Canciones y álbumes'),
('Premios y récords'),
('Curiosidades musicales')
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre);

SET @artistas = (SELECT id FROM categorias WHERE nombre = 'Artistas y bandas');
SET @canciones = (SELECT id FROM categorias WHERE nombre = 'Canciones y álbumes');
SET @premios = (SELECT id FROM categorias WHERE nombre = 'Premios y récords');
SET @curiosidades = (SELECT id FROM categorias WHERE nombre = 'Curiosidades musicales');

-- Conserva las preguntas anteriores y retira las cuatro categorías anteriores.
UPDATE preguntas p JOIN categorias c ON c.id = p.categoria_id
SET p.categoria_id = CASE
    WHEN c.nombre IN ('Rock', 'Pop') THEN @canciones
    ELSE @curiosidades
END
WHERE c.nombre IN ('General', 'Rock', 'Pop', 'Instrumentos');

UPDATE preguntas SET categoria_id = @curiosidades WHERE categoria_id IS NULL;
UPDATE preguntas SET categoria_id = @canciones
WHERE enunciado IN ('¿Qué banda interpretó Bohemian Rhapsody?', '¿Quién interpretó Thriller?');
UPDATE preguntas SET categoria_id = @curiosidades
WHERE enunciado = '¿Cuál de estos instrumentos tiene teclas?';

DELETE FROM categorias WHERE nombre IN ('General', 'Rock', 'Pop', 'Instrumentos');

DROP TEMPORARY TABLE IF EXISTS nuevas_preguntas_musicales;
CREATE TEMPORARY TABLE nuevas_preguntas_musicales (
    categoria VARCHAR(80) NOT NULL,
    enunciado VARCHAR(255) NOT NULL,
    opcion_1 VARCHAR(150) NOT NULL,
    opcion_2 VARCHAR(150) NOT NULL,
    opcion_3 VARCHAR(150) NOT NULL,
    correcta ENUM('1','2','3') NOT NULL,
    imagen VARCHAR(500) NULL
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

INSERT INTO nuevas_preguntas_musicales
(categoria, enunciado, opcion_1, opcion_2, opcion_3, correcta, imagen)
VALUES
('Canciones y álbumes', '¿Qué banda interpretó Bohemian Rhapsody?', 'Queen', 'The Beatles', 'Coldplay', '1', NULL),
('Canciones y álbumes', '¿Quién interpretó Thriller?', 'Prince', 'Michael Jackson', 'Bruno Mars', '2', NULL),
('Curiosidades musicales', '¿Cuál de estos instrumentos tiene teclas?', 'Violín', 'Batería', 'Piano', '3', NULL),
('Artistas y bandas', 'Taylor convirtió su año de nacimiento en el nombre de un álbum. ¿Qué año es?', '1987', '1989', '1991', '2', NULL),
('Artistas y bandas', '¿En qué ciudad inglesa se formó The Beatles?', 'Liverpool', 'Londres', 'Manchester', '1', NULL),
('Artistas y bandas', '¿Quién fue vocalista de Queen y compuso Bohemian Rhapsody?', 'David Bowie', 'Robert Plant', 'Freddie Mercury', '3', NULL),
('Artistas y bandas', '¿De qué país es originaria Shakira?', 'México', 'Colombia', 'Chile', '2', NULL),
('Artistas y bandas', '¿Quién fue el cantante y guitarrista de Nirvana?', 'Kurt Cobain', 'Eddie Vedder', 'Chris Cornell', '1', NULL),
('Artistas y bandas', '¿De qué banda fue vocalista Gustavo Cerati?', 'Los Fabulosos Cadillacs', 'Maná', 'Soda Stereo', '3', NULL),
('Artistas y bandas', 'En el escenario es Lady Gaga. ¿Qué nombre encontrarías en su documento?', 'Stefani Joanne Angelina Germanotta', 'Robyn Rihanna Fenty', 'Alicia Augello Cook', '1', NULL),
('Artistas y bandas', '¿En qué país nació Adele?', 'Canadá', 'Reino Unido', 'Australia', '2', NULL),
('Artistas y bandas', '¿En qué grupo se hizo conocida Beyoncé antes de su carrera solista?', 'Spice Girls', 'Little Mix', 'Destiny''s Child', '3', NULL),
('Artistas y bandas', '¿Qué artista es conocido como el Rey del Pop?', 'Michael Jackson', 'Elton John', 'Prince', '1', NULL),
('Artistas y bandas', '¿Quién es el vocalista de Coldplay?', 'Adam Levine', 'Chris Martin', 'Brandon Flowers', '2', NULL),
('Artistas y bandas', '¿De qué país es originario el grupo ABBA?', 'Noruega', 'Dinamarca', 'Suecia', '3', NULL),
('Artistas y bandas', 'Antes de conquistar estadios como solista, Harry Styles compartía micrófono en... ¿qué banda?', 'One Direction', 'Backstreet Boys', 'NSYNC', '1', NULL),
('Artistas y bandas', '¿De qué país es originario BTS?', 'Japón', 'Corea del Sur', 'China', '2', NULL),
('Artistas y bandas', 'Se baja del escenario el Conejo Malo. ¿Cuál es el nombre real de Bad Bunny?', 'Juan Luis Londoño Arias', 'Ramón Luis Ayala Rodríguez', 'Benito Antonio Martínez Ocasio', '3', NULL),
('Artistas y bandas', '¿Qué cantante es conocida como la Reina del Pop?', 'Madonna', 'Cher', 'Cyndi Lauper', '1', NULL),
('Artistas y bandas', '¿Quién es el cantante principal de U2?', 'Sting', 'Bono', 'Morrissey', '2', NULL),
('Artistas y bandas', '¿De qué país es originaria Rosalía?', 'Portugal', 'Argentina', 'España', '3', NULL),
('Artistas y bandas', 'Armamos una banda tributo a The Beatles: desde 1962, ¿a quién le toca sentarse detrás de la batería?', 'Ringo Starr', 'Phil Collins', 'Keith Moon', '1', NULL),
('Artistas y bandas', '¿En qué ciudad nació Fito Páez?', 'Córdoba', 'Rosario', 'Mendoza', '2', NULL),
('Artistas y bandas', '¿Qué artista argentino integró Sui Generis junto con Nito Mestre?', 'Andrés Calamaro', 'León Gieco', 'Charly García', '3', NULL),
('Artistas y bandas', 'Se apagan las luces y aparecen los cascos de Daft Punk. ¿De qué país viene este dúo?', 'Francia', 'Alemania', 'Italia', '1', NULL),
('Artistas y bandas', '¿En qué país nació Justin Bieber?', 'Estados Unidos', 'Canadá', 'Reino Unido', '2', NULL),
('Artistas y bandas', '¿Cuál de estos músicos fue bajista de The Beatles?', 'Eric Clapton', 'Jimmy Page', 'Paul McCartney', '3', NULL),
('Artistas y bandas', '¿De qué país es originaria la banda Maná?', 'México', 'Perú', 'Uruguay', '1', NULL),
('Canciones y álbumes', '¿Quién interpreta Like a Prayer?', 'Cyndi Lauper', 'Madonna', 'Cher', '2', NULL),
('Canciones y álbumes', '¿En qué álbum de Michael Jackson aparece Billie Jean?', 'Bad', 'Dangerous', 'Thriller', '3', NULL),
('Canciones y álbumes', '¿Qué banda lanzó el álbum The Dark Side of the Moon?', 'Pink Floyd', 'The Rolling Stones', 'Led Zeppelin', '1', NULL),
('Canciones y álbumes', 'Noche de karaoke: elegís Rolling in the Deep y preparás los pulmones. ¿A quién vas a intentar imitar?', 'Amy Winehouse', 'Adele', 'Duffy', '2', NULL),
('Canciones y álbumes', 'Reservaste una habitación imaginaria en Hotel California. ¿Qué banda puso la música?', 'Fleetwood Mac', 'The Doors', 'Eagles', '3', NULL),
('Canciones y álbumes', '¿En qué álbum de Taylor Swift apareció originalmente Shake It Off en 2014?', '1989', 'Red', 'Speak Now', '1', NULL),
('Canciones y álbumes', '¿Qué dúo francés lanzó One More Time?', 'Justice', 'Daft Punk', 'Air', '2', NULL),
('Canciones y álbumes', 'Te animás a cantar I Will Always Love You como en El guardaespaldas. ¿A qué cantante intentás alcanzar?', 'Mariah Carey', 'Céline Dion', 'Whitney Houston', '3', NULL),
('Canciones y álbumes', 'La juntada termina con todos cantando De música ligera. ¿Qué banda no puede faltar en la playlist?', 'Soda Stereo', 'Los Enanitos Verdes', 'Virus', '1', NULL),
('Canciones y álbumes', 'Sol, playa y un poquito de despecho: ¿quién lanzó Un verano sin ti?', 'J Balvin', 'Bad Bunny', 'Rauw Alejandro', '2', NULL),
('Canciones y álbumes', '¿Qué artista lanzó el álbum El mal querer?', 'Aitana', 'Nathy Peluso', 'Rosalía', '3', NULL),
('Canciones y álbumes', '¿Quién interpreta Blinding Lights?', 'The Weeknd', 'Bruno Mars', 'Drake', '1', NULL),
('Canciones y álbumes', '¿En qué álbum de Nirvana aparece Smells Like Teen Spirit?', 'In Utero', 'Nevermind', 'Bleach', '2', NULL),
('Canciones y álbumes', 'Suena Dancing Queen y nadie quiere quedarse sentado. ¿Qué grupo está sonando?', 'Boney M.', 'Bee Gees', 'ABBA', '3', NULL),
('Canciones y álbumes', '¿Quién lanzó el álbum Future Nostalgia?', 'Dua Lipa', 'Ariana Grande', 'Katy Perry', '1', NULL),
('Canciones y álbumes', 'Alguien trae una guitarra a la juntada y toca Wonderwall. ¿De qué banda es la versión original?', 'Blur', 'Oasis', 'Pulp', '2', NULL),
('Canciones y álbumes', '¿Quién interpreta Hips Don''t Lie junto con Wyclef Jean?', 'Jennifer Lopez', 'Gloria Estefan', 'Shakira', '3', NULL),
('Canciones y álbumes', '¿En qué álbum de Taylor Swift aparece cardigan?', 'folklore', 'Lover', 'Reputation', '1', NULL),
('Canciones y álbumes', '¿Quién interpreta bad guy, lanzada en 2019?', 'Olivia Rodrigo', 'Billie Eilish', 'Lorde', '2', NULL),
('Canciones y álbumes', '¿Qué grupo lanzó el álbum AM en 2013?', 'The Strokes', 'The Killers', 'Arctic Monkeys', '3', NULL),
('Canciones y álbumes', 'Te subís a la proa como en Titanic. ¿Quién canta My Heart Will Go On en la película?', 'Céline Dion', 'Whitney Houston', 'Shania Twain', '1', NULL),
('Canciones y álbumes', '¿Qué idea central propone Imagine, de John Lennon?', 'Celebrar una victoria deportiva', 'Imaginar un mundo en paz y sin divisiones', 'Narrar un viaje espacial', '2', NULL),
('Canciones y álbumes', '¿A quién buscaba consolar Paul McCartney con la canción que se convirtió en Hey Jude?', 'A Ringo Starr', 'A George Harrison', 'A Julian Lennon', '3', NULL),
('Canciones y álbumes', '¿Cuál de estos géneros se asocia con Thriller, la canción de Michael Jackson?', 'Pop', 'Heavy metal', 'Bluegrass', '1', NULL),
('Canciones y álbumes', 'Modo detective de portadas: cuatro Beatles cruzan una calle en 1969. ¿Qué álbum estás viendo?', 'Let It Be', 'Abbey Road', 'Rubber Soul', '2', 'https://www.thebeatles.com/sites/default/files/styles/max_2600x2600/public/2021-06/Abbey%20Road.jpg?itok=_AGlITc8'),
('Premios y récords', 'Ganaste un Grammy y estás haciendo lugar en la repisa. ¿Qué objeto representa la estatuilla?', 'Un micrófono', 'Una guitarra', 'Un gramófono', '3', NULL),
('Premios y récords', '¿En qué año se celebró la primera ceremonia de los Grammy?', '1959', '1969', '1979', '1', NULL),
('Premios y récords', 'Michael Jackson necesitó bastante espacio para los premios de la noche de 1984. ¿Cuántos Grammy ganó?', 'Seis', 'Ocho', 'Diez', '2', NULL),
('Premios y récords', '¿Qué disco ganó el Grammy a Álbum del Año en 1984?', 'Purple Rain', 'Born in the U.S.A.', 'Thriller', '3', NULL),
('Premios y récords', '¿Con qué álbum ganó Taylor Swift el Grammy a Álbum del Año en 2010?', 'Fearless', 'Red', '1989', '1', NULL),
('Premios y récords', '¿Qué álbum de Taylor Swift ganó el Grammy a Álbum del Año en 2016?', 'Speak Now', '1989', 'Reputation', '2', NULL),
('Premios y récords', '¿Qué álbum de Taylor Swift ganó el Grammy a Álbum del Año en 2024?', 'Lover', 'Evermore', 'Midnights', '3', NULL),
('Premios y récords', '¿Qué álbum de Adele ganó el Grammy a Álbum del Año en 2012?', '21', '19', '25', '1', NULL),
('Premios y récords', '¿Qué álbum de Adele ganó el Grammy a Álbum del Año en 2017?', '30', '25', '19', '2', NULL),
('Premios y récords', '¿Qué dúo ganó el Grammy a Álbum del Año en 2014 con Random Access Memories?', 'Pet Shop Boys', 'The Chemical Brothers', 'Daft Punk', '3', NULL),
('Premios y récords', '¿Qué álbum de Bruno Mars ganó el Grammy a Álbum del Año en 2018?', '24K Magic', 'Doo-Wops & Hooligans', 'Unorthodox Jukebox', '1', NULL),
('Premios y récords', '¿Quién ganó el Grammy a Álbum del Año en 2023 con Harry''s House?', 'Ed Sheeran', 'Harry Styles', 'Sam Smith', '2', NULL),
('Premios y récords', '¿Quién ganó las cuatro categorías generales principales de los Grammy en 2020?', 'Dua Lipa', 'Ariana Grande', 'Billie Eilish', '3', NULL),
('Premios y récords', '¿Quién fue el primer artista en ganar las cuatro categorías generales principales de los Grammy en una misma ceremonia, en 1981?', 'Christopher Cross', 'Elton John', 'Billy Joel', '1', NULL),
('Premios y récords', '¿Qué álbum de Santana ganó el Grammy a Álbum del Año en 2000?', 'Abraxas', 'Supernatural', 'Caravanserai', '2', NULL),
('Premios y récords', '¿Quién ganó el Grammy a Álbum del Año en 2003 con Come Away with Me?', 'Diana Krall', 'Alicia Keys', 'Norah Jones', '3', NULL),
('Premios y récords', '¿Quién ganó el Grammy a Álbum del Año en 1999 con The Miseducation of Lauryn Hill?', 'Lauryn Hill', 'Erykah Badu', 'Mary J. Blige', '1', NULL),
('Premios y récords', '¿Qué canción de Silk Sonic ganó el Grammy a Grabación del Año en 2022?', 'Skate', 'Leave the Door Open', 'Smokin Out the Window', '2', NULL),
('Premios y récords', '¿Qué canción de Mark Ronson con Bruno Mars ganó el Grammy a Grabación del Año en 2016?', 'Locked Out of Heaven', 'Treasure', 'Uptown Funk', '3', NULL),
('Premios y récords', '¿Qué canción de Lil Nas X llegó a acumular 19 semanas en el número uno del Billboard Hot 100 en 2019?', 'Old Town Road', 'Montero', 'Industry Baby', '1', NULL),
('Premios y récords', 'En noviembre de 2022, el top 10 completo del Hot 100 parecía una sola playlist. ¿De qué artista eran las diez canciones?', 'Adele', 'Taylor Swift', 'Beyoncé', '2', NULL),
('Premios y récords', '¿Qué álbum fue reconocido por Guinness en 1984 como el más vendido del mundo?', 'Back in Black', 'Rumours', 'Thriller', '3', NULL),
('Premios y récords', '¿Qué lista de Billboard clasifica canciones en Estados Unidos?', 'Hot 100', 'Billboard 200', 'Artist 100', '1', NULL),
('Premios y récords', '¿Qué lista de Billboard clasifica álbumes en Estados Unidos?', 'Hot 100', 'Billboard 200', 'Pop Airplay', '2', NULL),
('Premios y récords', '¿Qué cantante ganó el Grammy a Mejor Artista Nuevo en la ceremonia de 2000?', 'Britney Spears', 'Jennifer Lopez', 'Christina Aguilera', '3', NULL),
('Curiosidades musicales', 'Se cortó una cuerda de tu guitarra clásica estándar. ¿Cuántas tenía antes del desastre?', 'Seis', 'Cuatro', 'Ocho', '1', NULL),
('Curiosidades musicales', '¿A qué familia pertenece el violín?', 'Viento metal', 'Cuerda', 'Percusión', '2', NULL),
('Curiosidades musicales', '¿Qué instrumento se asocia especialmente con el tango rioplatense?', 'Banjo', 'Gaita', 'Bandoneón', '3', NULL),
('Curiosidades musicales', '¿Con qué parte del cuerpo se acciona normalmente el pedal del bombo de una batería?', 'El pie', 'El codo', 'La rodilla', '1', NULL),
('Curiosidades musicales', 'Se corta la música, pero vos seguís cantando sin instrumentos. ¿Cómo se llama eso?', 'Falsete', 'A capela', 'Vibrato', '2', NULL),
('Curiosidades musicales', '¿Qué significa hacer un cover de una canción?', 'Cambiar su velocidad de reproducción', 'Eliminar su voz', 'Interpretar una versión de una canción ya existente', '3', NULL),
('Curiosidades musicales', '¿Qué instrumento suele tener 88 teclas en su versión acústica moderna estándar?', 'Piano', 'Acordeón', 'Melódica', '1', NULL),
('Curiosidades musicales', '¿Quién colaboró con Luis Fonsi en la versión original de Despacito de 2017?', 'Maluma', 'Daddy Yankee', 'Ozuna', '2', NULL),
('Curiosidades musicales', 'Te toca hacer el dueto de Shallow. En la versión original, ¿quién acompaña a Lady Gaga?', 'Ryan Gosling', 'Joaquin Phoenix', 'Bradley Cooper', '3', NULL),
('Curiosidades musicales', '¿Qué dos artistas formaron Silk Sonic?', 'Bruno Mars y Anderson .Paak', 'Drake y The Weeknd', 'Pharrell Williams y Usher', '1', NULL),
('Curiosidades musicales', '¿Quién colaboró con Queen en Under Pressure?', 'Elton John', 'David Bowie', 'George Michael', '2', NULL),
('Curiosidades musicales', '¿Qué cantante participa en la versión de Walk This Way que Run-D.M.C. grabó con Aerosmith?', 'Axl Rose', 'Jon Bon Jovi', 'Steven Tyler', '3', NULL),
('Curiosidades musicales', '¿Qué músico es hermano y colaborador habitual de Billie Eilish?', 'FINNEAS', 'Charlie Puth', 'Troye Sivan', '1', NULL),
('Curiosidades musicales', '¿Qué indica habitualmente la abreviatura feat. en el título de una canción?', 'Que es una versión instrumental', 'Que participa un artista invitado', 'Que se grabó en vivo', '2', NULL),
('Curiosidades musicales', '¿Cómo se llama el conjunto de cinco líneas donde se escriben las notas musicales?', 'Acorde', 'Compás', 'Pentagrama', '3', NULL),
('Curiosidades musicales', 'Tu banda acelera sin querer en cada ensayo. ¿Qué aparato ayuda a mantener un tempo constante?', 'Metrónomo', 'Afinador', 'Amplificador', '1', NULL),
('Curiosidades musicales', '¿En qué unidad suele expresarse el tempo de una canción?', 'Decibelios', 'Pulsaciones por minuto, BPM', 'Voltios', '2', NULL),
('Curiosidades musicales', '¿Qué instrumento de viento utiliza una vara deslizante para variar la longitud del tubo?', 'Trompeta', 'Tuba', 'Trombón', '3', NULL),
('Curiosidades musicales', '¿Cuál de estos instrumentos se toca habitualmente con un arco?', 'Violonchelo', 'Trompeta', 'Xilófono', '1', NULL),
('Curiosidades musicales', '¿Qué productor trabajó con Michael Jackson en Off the Wall, Thriller y Bad?', 'Rick Rubin', 'Quincy Jones', 'Max Martin', '2', NULL),
('Curiosidades musicales', 'Pausa para hacer air guitar: ¿quién tocó el solo de Beat It de Michael Jackson?', 'Slash', 'Carlos Santana', 'Eddie Van Halen', '3', NULL),
('Curiosidades musicales', '¿Quiénes grabaron el dueto The Boy Is Mine de 1998?', 'Brandy y Monica', 'Beyoncé y Shakira', 'Mariah Carey y Whitney Houston', '1', NULL),
('Curiosidades musicales', '¿Qué cantante aparece junto con Shakira en Beautiful Liar?', 'Rihanna', 'Beyoncé', 'Jennifer Lopez', '2', NULL),
('Curiosidades musicales', 'Parece magia: movés las manos cerca de unas antenas y suena música, sin tocar el instrumento. ¿Cuál es?', 'Órgano', 'Sintetizador de teclado', 'Theremín', '3', NULL),
('Curiosidades musicales', '¿Quién colaboró con Elton John en Don''t Go Breaking My Heart, publicada en 1976?', 'Kiki Dee', 'Dolly Parton', 'Tina Turner', '1', NULL);

-- Actualiza la redacción anterior sin agregar preguntas duplicadas.
UPDATE preguntas SET enunciado = 'Taylor convirtió su año de nacimiento en el nombre de un álbum. ¿Qué año es?' WHERE enunciado = '¿En qué año nació Taylor Swift?';
UPDATE preguntas SET enunciado = 'En el escenario es Lady Gaga. ¿Qué nombre encontrarías en su documento?' WHERE enunciado = '¿Cuál es el nombre real de Lady Gaga?';
UPDATE preguntas SET enunciado = 'Antes de conquistar estadios como solista, Harry Styles compartía micrófono en... ¿qué banda?' WHERE enunciado = '¿En qué banda comenzó a ser conocido Harry Styles?';
UPDATE preguntas SET enunciado = 'Se baja del escenario el Conejo Malo. ¿Cuál es el nombre real de Bad Bunny?' WHERE enunciado = '¿Cuál es el nombre real de Bad Bunny?';
UPDATE preguntas SET enunciado = 'Armamos una banda tributo a The Beatles: desde 1962, ¿a quién le toca sentarse detrás de la batería?' WHERE enunciado = '¿Quién fue el baterista de The Beatles desde 1962?';
UPDATE preguntas SET enunciado = 'Se apagan las luces y aparecen los cascos de Daft Punk. ¿De qué país viene este dúo?' WHERE enunciado = '¿De qué país es originario el dúo Daft Punk?';
UPDATE preguntas SET enunciado = 'Noche de karaoke: elegís Rolling in the Deep y preparás los pulmones. ¿A quién vas a intentar imitar?' WHERE enunciado = '¿Quién interpreta Rolling in the Deep?';
UPDATE preguntas SET enunciado = 'Reservaste una habitación imaginaria en Hotel California. ¿Qué banda puso la música?' WHERE enunciado = '¿Qué banda lanzó Hotel California?';
UPDATE preguntas SET enunciado = 'Te animás a cantar I Will Always Love You como en El guardaespaldas. ¿A qué cantante intentás alcanzar?' WHERE enunciado = '¿Quién interpreta la versión de I Will Always Love You de la película El guardaespaldas?';
UPDATE preguntas SET enunciado = 'La juntada termina con todos cantando De música ligera. ¿Qué banda no puede faltar en la playlist?' WHERE enunciado = '¿Qué banda interpreta De música ligera?';
UPDATE preguntas SET enunciado = 'Sol, playa y un poquito de despecho: ¿quién lanzó Un verano sin ti?' WHERE enunciado = '¿Quién lanzó el álbum Un verano sin ti?';
UPDATE preguntas SET enunciado = 'Suena Dancing Queen y nadie quiere quedarse sentado. ¿Qué grupo está sonando?' WHERE enunciado = '¿Qué banda interpreta Dancing Queen?';
UPDATE preguntas SET enunciado = 'Alguien trae una guitarra a la juntada y toca Wonderwall. ¿De qué banda es la versión original?' WHERE enunciado = '¿Qué banda grabó la versión original de Wonderwall?';
UPDATE preguntas SET enunciado = 'Te subís a la proa como en Titanic. ¿Quién canta My Heart Will Go On en la película?' WHERE enunciado = '¿Quién interpreta My Heart Will Go On en la banda sonora de Titanic?';
UPDATE preguntas SET enunciado = 'Modo detective de portadas: cuatro Beatles cruzan una calle en 1969. ¿Qué álbum estás viendo?' WHERE enunciado = '¿Cómo se llama el álbum de The Beatles de 1969 cuya portada muestra al grupo cruzando una calle?';
UPDATE preguntas SET enunciado = 'Ganaste un Grammy y estás haciendo lugar en la repisa. ¿Qué objeto representa la estatuilla?' WHERE enunciado = '¿Qué objeto representa la estatuilla de los premios Grammy?';
UPDATE preguntas SET enunciado = 'Michael Jackson necesitó bastante espacio para los premios de la noche de 1984. ¿Cuántos Grammy ganó?' WHERE enunciado = '¿Cuántos Grammy ganó Michael Jackson en la ceremonia de 1984?';
UPDATE preguntas SET enunciado = 'En noviembre de 2022, el top 10 completo del Hot 100 parecía una sola playlist. ¿De qué artista eran las diez canciones?' WHERE enunciado = '¿Qué artista ocupó simultáneamente los diez primeros puestos del Billboard Hot 100 en noviembre de 2022?';
UPDATE preguntas SET enunciado = 'Se cortó una cuerda de tu guitarra clásica estándar. ¿Cuántas tenía antes del desastre?' WHERE enunciado = '¿Cuántas cuerdas tiene una guitarra clásica estándar?';
UPDATE preguntas SET enunciado = 'Se corta la música, pero vos seguís cantando sin instrumentos. ¿Cómo se llama eso?' WHERE enunciado = '¿Cómo se llama cantar sin acompañamiento instrumental?';
UPDATE preguntas SET enunciado = 'Te toca hacer el dueto de Shallow. En la versión original, ¿quién acompaña a Lady Gaga?' WHERE enunciado = '¿Quién cantó con Lady Gaga en Shallow?';
UPDATE preguntas SET enunciado = 'Tu banda acelera sin querer en cada ensayo. ¿Qué aparato ayuda a mantener un tempo constante?' WHERE enunciado = '¿Qué aparato se utiliza para marcar un tempo constante durante la práctica musical?';
UPDATE preguntas SET enunciado = 'Pausa para hacer air guitar: ¿quién tocó el solo de Beat It de Michael Jackson?' WHERE enunciado = '¿Qué guitarrista tocó el solo de guitarra de Beat It de Michael Jackson?';
UPDATE preguntas SET enunciado = 'Parece magia: movés las manos cerca de unas antenas y suena música, sin tocar el instrumento. ¿Cuál es?' WHERE enunciado = '¿Qué instrumento se toca sin contacto físico directo, moviendo las manos cerca de sus antenas?';

-- Si ejecutás otra vez este archivo, no duplica estas preguntas.
INSERT INTO preguntas
(enunciado, opcion_1, opcion_2, opcion_3, correcta, categoria_id, imagen)
SELECT n.enunciado, n.opcion_1, n.opcion_2, n.opcion_3, n.correcta, c.id, n.imagen
FROM nuevas_preguntas_musicales n
JOIN categorias c ON c.nombre = n.categoria
WHERE NOT EXISTS (
    SELECT 1 FROM preguntas p WHERE p.enunciado = n.enunciado
);

DROP TEMPORARY TABLE nuevas_preguntas_musicales;
COMMIT;

SELECT c.nombre, COUNT(p.id) AS cantidad_preguntas
FROM categorias c
LEFT JOIN preguntas p ON p.categoria_id = c.id
GROUP BY c.id, c.nombre
ORDER BY c.nombre;