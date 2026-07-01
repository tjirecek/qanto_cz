-- Stabilizace shared newsletter users administrace.
CREATE TABLE IF NOT EXISTS news_users (
    id INT NOT NULL AUTO_INCREMENT,
    name VARCHAR(256) NOT NULL DEFAULT '',
    email VARCHAR(256) NOT NULL DEFAULT '',
    datum_od DATE NOT NULL DEFAULT '0000-00-00',
    datum_do DATE NOT NULL DEFAULT '0000-00-00',
    registered TINYINT(1) NOT NULL DEFAULT 1,
    valid TINYINT(1) NOT NULL DEFAULT 1,
    user_i VARCHAR(256) NOT NULL DEFAULT '',
    user_u VARCHAR(256) NOT NULL DEFAULT '',
    ts_i TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ts_u TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_czech_ci;

SET @sql := IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'news_users' AND COLUMN_NAME = 'user_i') = 0,
    'ALTER TABLE news_users ADD COLUMN user_i VARCHAR(256) NOT NULL DEFAULT '''' AFTER valid',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'news_users' AND COLUMN_NAME = 'user_u') = 0,
    'ALTER TABLE news_users ADD COLUMN user_u VARCHAR(256) NOT NULL DEFAULT '''' AFTER user_i',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'news_users' AND COLUMN_NAME = 'ts_i') = 0,
    'ALTER TABLE news_users ADD COLUMN ts_i TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER user_u',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'news_users' AND COLUMN_NAME = 'ts_u') = 0,
    'ALTER TABLE news_users ADD COLUMN ts_u TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER ts_i',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
    (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'news_users' AND INDEX_NAME = 'idx_news_users_email') = 0,
    'ALTER TABLE news_users ADD KEY idx_news_users_email (email)',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
    (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'news_users' AND INDEX_NAME = 'idx_news_users_valid_registered') = 0,
    'ALTER TABLE news_users ADD KEY idx_news_users_valid_registered (valid, registered, datum_od, id)',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

UPDATE settings
SET typ = 'main',
    popis_cz = 'Výchozí počet záznamů ve výpisu uživatelů newsletteru',
    hodnota = 500,
    hodnota_text = '',
    valid = 1,
    user_u = 'migration_news_users_admin'
WHERE name = 'limit_news-users';

INSERT INTO settings (typ, name, popis_cz, hodnota, hodnota_text, valid, user_i, user_u)
SELECT 'main', 'limit_news-users', 'Výchozí počet záznamů ve výpisu uživatelů newsletteru', 500, '', 1, 'migration_news_users_admin', 'migration_news_users_admin'
WHERE NOT EXISTS (SELECT 1 FROM settings WHERE name = 'limit_news-users');

UPDATE settings
SET typ = 'main',
    popis_cz = 'Počet uživatelů newsletteru pro starší části administrace',
    hodnota = 500,
    hodnota_text = '',
    valid = 1,
    user_u = 'migration_news_users_admin'
WHERE name = 'admin_newsusers_pocet';

INSERT INTO settings (typ, name, popis_cz, hodnota, hodnota_text, valid, user_i, user_u)
SELECT 'main', 'admin_newsusers_pocet', 'Počet uživatelů newsletteru pro starší části administrace', 500, '', 1, 'migration_news_users_admin', 'migration_news_users_admin'
WHERE NOT EXISTS (SELECT 1 FROM settings WHERE name = 'admin_newsusers_pocet');
