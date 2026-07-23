-- Shared DB přepis pevných frontendových textů používaných přes ui_text().

CREATE TABLE IF NOT EXISTS ui_texty (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    code VARCHAR(190) NOT NULL,
    cz TEXT NOT NULL,
    en TEXT NULL,
    valid TINYINT(1) NOT NULL DEFAULT 1,
    auto_translate_en TINYINT(1) NOT NULL DEFAULT 1,
    user_i VARCHAR(120) NOT NULL DEFAULT '',
    user_u VARCHAR(120) NOT NULL DEFAULT '',
    ts_i TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ts_u TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_ui_texty_code (code),
    KEY idx_ui_texty_valid (valid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
