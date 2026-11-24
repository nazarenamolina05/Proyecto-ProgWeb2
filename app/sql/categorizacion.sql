
USE entradas_messi;



SET @existe = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'preguntas'
      AND COLUMN_NAME = 'dificultad'
);

SET @sql = IF(
    @existe = 0,
    'ALTER TABLE preguntas
     ADD COLUMN dificultad
     ENUM(''FACIL'', ''INTERMEDIO'', ''DIFICIL'')
     NOT NULL DEFAULT ''INTERMEDIO''
     AFTER categoria_id',
    'SELECT 1'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;




SET @existe = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'partidas'
      AND COLUMN_NAME = 'correctas'
);

SET @sql = IF(
    @existe = 0,
    'ALTER TABLE partidas
     ADD COLUMN correctas INT UNSIGNED NOT NULL DEFAULT 0',
    'SELECT 1'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;



SET @existe = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'partidas'
      AND COLUMN_NAME = 'total_preguntas'
);

SET @sql = IF(
    @existe = 0,
    'ALTER TABLE partidas
     ADD COLUMN total_preguntas INT UNSIGNED NOT NULL DEFAULT 0',
    'SELECT 1'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;



SET @existe = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'partidas'
      AND COLUMN_NAME = 'dificultad'
);

SET @sql = IF(
    @existe = 0,
    'ALTER TABLE partidas
     ADD COLUMN dificultad
     ENUM(''FACIL'', ''INTERMEDIO'', ''DIFICIL'') NULL',
    'SELECT 1'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;



SET @existe = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'partidas'
      AND COLUMN_NAME = 'categoria_id'
);

SET @sql = IF(
    @existe = 0,
    'ALTER TABLE partidas
     ADD COLUMN categoria_id INT UNSIGNED NULL',
    'SELECT 1'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;





DESCRIBE preguntas;
DESCRIBE partidas;
