-- Shared agenda Napište nám: kategorie kontaktního formuláře a přijaté zprávy.

CREATE TABLE IF NOT EXISTS napiste_nam_kategorie (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    legacy_id INT UNSIGNED NULL,
    poradi INT NOT NULL DEFAULT 0,
    nazev_cz VARCHAR(255) NOT NULL,
    nazev_en VARCHAR(255) NOT NULL DEFAULT '',
    email_to TEXT NULL,
    email_copy TEXT NULL,
    type TINYINT UNSIGNED NOT NULL DEFAULT 1,
    visible TINYINT(1) NOT NULL DEFAULT 1,
    valid TINYINT(1) NOT NULL DEFAULT 1,
    auto_translate_en TINYINT(1) NOT NULL DEFAULT 1,
    user_i VARCHAR(100) NOT NULL DEFAULT '',
    user_u VARCHAR(100) NOT NULL DEFAULT '',
    ts_i TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ts_u TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_napiste_nam_kategorie_legacy_id (legacy_id),
    KEY idx_napiste_nam_kategorie_valid_visible_poradi (valid, visible, poradi),
    KEY idx_napiste_nam_kategorie_nazev_cz (nazev_cz)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS napiste_nam_zpravy (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    legacy_id INT UNSIGNED NULL,
    kategorie_id INT UNSIGNED NULL,
    legacy_kategorie_id INT UNSIGNED NULL,
    datum DATE NULL,
    name VARCHAR(512) NOT NULL DEFAULT '',
    email VARCHAR(512) NOT NULL DEFAULT '',
    telefon VARCHAR(512) NOT NULL DEFAULT '',
    text TEXT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    valid TINYINT(1) NOT NULL DEFAULT 1,
    user_i VARCHAR(100) NOT NULL DEFAULT '',
    user_u VARCHAR(100) NOT NULL DEFAULT '',
    ts_i TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ts_u TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_napiste_nam_zpravy_legacy_id (legacy_id),
    KEY idx_napiste_nam_zpravy_kategorie (kategorie_id),
    KEY idx_napiste_nam_zpravy_datum (datum),
    KEY idx_napiste_nam_zpravy_valid_read (valid, is_read)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
