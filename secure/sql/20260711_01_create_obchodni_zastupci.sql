-- Shared obchodní zástupci navázaní na pobočky.

CREATE TABLE IF NOT EXISTS obchodni_zastupci (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    pobocka_id INT UNSIGNED NOT NULL,
    oblast_id INT UNSIGNED NULL,
    poradi INT NOT NULL DEFAULT 0,
    jmeno VARCHAR(255) NOT NULL,
    mobil VARCHAR(50) NULL,
    email VARCHAR(255) NULL,
    web VARCHAR(255) NULL,
    image VARCHAR(255) NULL,
    popis_cz TEXT NULL,
    popis_en TEXT NULL,
    valid TINYINT(1) NOT NULL DEFAULT 1,
    auto_translate_en TINYINT(1) NOT NULL DEFAULT 1,
    user_i VARCHAR(100) NOT NULL DEFAULT '',
    user_u VARCHAR(100) NOT NULL DEFAULT '',
    ts_i TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ts_u TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_obchodni_zastupci_pobocka_id (pobocka_id),
    KEY idx_obchodni_zastupci_oblast_id (oblast_id),
    KEY idx_obchodni_zastupci_jmeno (jmeno),
    KEY idx_obchodni_zastupci_valid_poradi (valid, poradi)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
