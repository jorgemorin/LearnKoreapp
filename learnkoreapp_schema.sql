-- ============================================================================
-- LearnKoreapp — Esquema de Base de Datos (PostgreSQL)
-- Arquitectura: Diccionario Global + Colecciones de Usuario
-- ============================================================================

-- ---------------------------------------------------------------------------
-- 1. USUARIOS Y AUTENTICACIÓN
-- ---------------------------------------------------------------------------
CREATE TABLE users (
    id              BIGSERIAL PRIMARY KEY,
    name            VARCHAR(150)        NOT NULL,
    email           VARCHAR(255)        NOT NULL UNIQUE,
    password_hash   VARCHAR(255)        NOT NULL,
    role            VARCHAR(20)         NOT NULL DEFAULT 'user'
                        CHECK (role IN ('user', 'admin')),
    email_verified_at TIMESTAMP,
    created_at      TIMESTAMP           NOT NULL DEFAULT NOW(),
    updated_at      TIMESTAMP           NOT NULL DEFAULT NOW()
);

-- ---------------------------------------------------------------------------
-- 2. DICCIONARIO GLOBAL (conocimiento base, sin duplicados)
-- ---------------------------------------------------------------------------
CREATE TABLE entities (
    id              BIGSERIAL PRIMARY KEY,
    text            VARCHAR(255)        NOT NULL,
    type            VARCHAR(30)         NOT NULL
                        CHECK (type IN ('root', 'particle', 'word')),
    meaning         TEXT                NOT NULL,
    status          VARCHAR(20)         NOT NULL DEFAULT 'pending_review'
                        CHECK (status IN ('pending_review', 'verified')),
    created_at      TIMESTAMP           NOT NULL DEFAULT NOW(),
    updated_at      TIMESTAMP           NOT NULL DEFAULT NOW(),
    UNIQUE (text, type)
);
CREATE INDEX idx_entities_status ON entities(status);
CREATE INDEX idx_entities_type   ON entities(type);

CREATE TABLE compounds (
    id              BIGSERIAL PRIMARY KEY,
    full_text       VARCHAR(255)        NOT NULL UNIQUE,
    translation     TEXT                NOT NULL,
    status          VARCHAR(20)         NOT NULL DEFAULT 'pending_review'
                        CHECK (status IN ('pending_review', 'verified')),
    created_at      TIMESTAMP           NOT NULL DEFAULT NOW(),
    updated_at      TIMESTAMP           NOT NULL DEFAULT NOW()
);
CREATE INDEX idx_compounds_status ON compounds(status);

-- Tabla puente: qué entidades componen cada compuesto, y en qué orden
CREATE TABLE compound_entity (
    compound_id     BIGINT              NOT NULL REFERENCES compounds(id) ON DELETE CASCADE,
    entity_id       BIGINT              NOT NULL REFERENCES entities(id)  ON DELETE CASCADE,
    position_order  SMALLINT            NOT NULL,
    PRIMARY KEY (compound_id, entity_id)
);
CREATE INDEX idx_compound_entity_entity ON compound_entity(entity_id);

-- Etiquetas semánticas (Restaurante, Saludos, Educación, etc.)
CREATE TABLE tags (
    id              BIGSERIAL PRIMARY KEY,
    name            VARCHAR(100)        NOT NULL UNIQUE
);

-- Tabla polimórfica: asigna tags a entities o a compounds
CREATE TABLE taggables (
    tag_id          BIGINT              NOT NULL REFERENCES tags(id) ON DELETE CASCADE,
    taggable_id     BIGINT              NOT NULL,
    taggable_type   VARCHAR(20)         NOT NULL
                        CHECK (taggable_type IN ('entity', 'compound')),
    PRIMARY KEY (tag_id, taggable_id, taggable_type)
);
CREATE INDEX idx_taggables_morph ON taggables(taggable_type, taggable_id);

-- ---------------------------------------------------------------------------
-- 3. PROGRESO DE USUARIO (Sistema SRS)
-- ---------------------------------------------------------------------------
CREATE TABLE user_progress (
    id                  BIGSERIAL PRIMARY KEY,
    user_id             BIGINT      NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    item_id             BIGINT      NOT NULL,
    item_type           VARCHAR(20) NOT NULL
                            CHECK (item_type IN ('entity', 'compound')),
    next_review_date    DATE        NOT NULL DEFAULT CURRENT_DATE,
    ease_factor         REAL        NOT NULL DEFAULT 2.5,
    interval_days       INTEGER     NOT NULL DEFAULT 0,
    repetitions         INTEGER     NOT NULL DEFAULT 0,
    created_at          TIMESTAMP   NOT NULL DEFAULT NOW(),
    updated_at          TIMESTAMP   NOT NULL DEFAULT NOW(),
    UNIQUE (user_id, item_id, item_type)
);
-- Índice crítico para el RNF de <200ms en la generación de lotes de repaso
CREATE INDEX idx_user_progress_due ON user_progress(user_id, next_review_date);
CREATE INDEX idx_user_progress_morph ON user_progress(item_type, item_id);

-- Registro histórico e inmutable de cada intento
CREATE TABLE study_logs (
    id              BIGSERIAL PRIMARY KEY,
    user_id         BIGINT      NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    item_id         BIGINT      NOT NULL,
    item_type       VARCHAR(20) NOT NULL
                        CHECK (item_type IN ('entity', 'compound')),
    is_correct      BOOLEAN     NOT NULL,
    time_taken_ms   INTEGER     NOT NULL,
    created_at      TIMESTAMP   NOT NULL DEFAULT NOW()
);
CREATE INDEX idx_study_logs_user     ON study_logs(user_id, created_at);
CREATE INDEX idx_study_logs_morph    ON study_logs(item_type, item_id);

-- ---------------------------------------------------------------------------
-- 4. VISTAS DE ANALÍTICA (ejemplos para el Motor de Estadísticas)
-- ---------------------------------------------------------------------------

-- Tasa de acierto individual por entidad
CREATE VIEW v_accuracy_by_entity AS
SELECT
    item_id AS entity_id,
    COUNT(*) FILTER (WHERE is_correct)          AS aciertos,
    COUNT(*)                                    AS intentos,
    ROUND(100.0 * COUNT(*) FILTER (WHERE is_correct) / COUNT(*), 2) AS tasa_acierto
FROM study_logs
WHERE item_type = 'entity'
GROUP BY item_id;

-- Rendimiento semántico: acierto agrupado por tag
CREATE VIEW v_accuracy_by_tag AS
SELECT
    t.id   AS tag_id,
    t.name AS tag_name,
    COUNT(*) FILTER (WHERE sl.is_correct)       AS aciertos,
    COUNT(*)                                    AS intentos,
    ROUND(100.0 * COUNT(*) FILTER (WHERE sl.is_correct) / COUNT(*), 2) AS tasa_acierto
FROM study_logs sl
JOIN taggables tg ON tg.taggable_id = sl.item_id AND tg.taggable_type = sl.item_type
JOIN tags t       ON t.id = tg.tag_id
GROUP BY t.id, t.name;

-- Rendimiento estructural: acierto agrupado por tipo de entidad
CREATE VIEW v_accuracy_by_type AS
SELECT
    e.type,
    COUNT(*) FILTER (WHERE sl.is_correct)       AS aciertos,
    COUNT(*)                                    AS intentos,
    ROUND(100.0 * COUNT(*) FILTER (WHERE sl.is_correct) / COUNT(*), 2) AS tasa_acierto
FROM study_logs sl
JOIN entities e ON e.id = sl.item_id AND sl.item_type = 'entity'
GROUP BY e.type;

-- ---------------------------------------------------------------------------
-- 5. CONSULTA CRÍTICA DE RENDIMIENTO (RNF: < 200 ms)
-- ---------------------------------------------------------------------------
-- Siguiente lote de tarjetas a repasar para un usuario:
-- EXPLAIN ANALYZE
-- SELECT * FROM user_progress
-- WHERE user_id = :user_id AND next_review_date <= CURRENT_DATE
-- ORDER BY next_review_date ASC
-- LIMIT 20;
-- (Cubierta por idx_user_progress_due)
