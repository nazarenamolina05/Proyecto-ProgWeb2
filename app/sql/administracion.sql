USE entradas_messi;

ALTER TABLE usuarios
    ADD COLUMN IF NOT EXISTS habilitado TINYINT(1) NOT NULL DEFAULT 1,
    ADD COLUMN IF NOT EXISTS fecha_registro DATETIME(6) NULL;
ALTER TABLE usuarios ALTER COLUMN fecha_registro SET DEFAULT CURRENT_TIMESTAMP(6);

ALTER TABLE preguntas
    ADD COLUMN IF NOT EXISTS activa TINYINT(1) NOT NULL DEFAULT 1,
    ADD COLUMN IF NOT EXISTS fecha_creacion DATETIME(6) NULL;
ALTER TABLE preguntas ALTER COLUMN fecha_creacion SET DEFAULT CURRENT_TIMESTAMP(6);

ALTER TABLE reportes_preguntas
    ADD COLUMN IF NOT EXISTS decision ENUM('conservar', 'baja') NULL;

CREATE TABLE IF NOT EXISTS estadisticas_inicio (
    id TINYINT UNSIGNED PRIMARY KEY,
    fecha DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6)
);

INSERT IGNORE INTO estadisticas_inicio (id) VALUES (1);

CREATE TABLE IF NOT EXISTS pregunta_estados (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    pregunta_id INT UNSIGNED NOT NULL,
    activa TINYINT(1) NOT NULL,
    fecha DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    KEY pregunta_estados_fecha (pregunta_id, fecha, id),
    FOREIGN KEY (pregunta_id) REFERENCES preguntas(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TRIGGER IF NOT EXISTS registrar_alta_pregunta
    AFTER INSERT ON preguntas FOR EACH ROW
    INSERT INTO pregunta_estados (pregunta_id, activa, fecha)
    VALUES (NEW.id, NEW.activa, COALESCE(NEW.fecha_creacion, CURRENT_TIMESTAMP(6)));

CREATE TRIGGER IF NOT EXISTS registrar_estado_pregunta
    AFTER UPDATE ON preguntas FOR EACH ROW
    INSERT INTO pregunta_estados (pregunta_id, activa)
    SELECT NEW.id, NEW.activa WHERE OLD.activa <> NEW.activa;

INSERT INTO pregunta_estados (pregunta_id, activa, fecha)
SELECT p.id, p.activa, CURRENT_TIMESTAMP(6)
FROM preguntas p
WHERE NOT EXISTS (SELECT 1 FROM pregunta_estados e WHERE e.pregunta_id = p.id);
