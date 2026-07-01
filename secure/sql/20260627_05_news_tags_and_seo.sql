CREATE TABLE IF NOT EXISTS news_tag (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    poradi INT NOT NULL DEFAULT 0,
    nazev_cz VARCHAR(256) NOT NULL DEFAULT '',
    nazev_en VARCHAR(256) NOT NULL DEFAULT '',
    slug_cz VARCHAR(256) NOT NULL DEFAULT '',
    slug_en VARCHAR(256) NOT NULL DEFAULT '',
    color VARCHAR(64) NOT NULL DEFAULT '',
    valid TINYINT(1) NOT NULL DEFAULT 1,
    user_i VARCHAR(100) NOT NULL DEFAULT '',
    user_u VARCHAR(100) NOT NULL DEFAULT '',
    ts_i TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ts_u TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_news_tag_slug_cz (slug_cz),
    KEY idx_news_tag_valid_poradi (valid, poradi, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_czech_ci;

CREATE TABLE IF NOT EXISTS news_tag_rel (
    news_id INT NOT NULL,
    tag_id INT UNSIGNED NOT NULL,
    user_i VARCHAR(100) NOT NULL DEFAULT '',
    ts_i TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (news_id, tag_id),
    KEY idx_news_tag_rel_tag (tag_id),
    CONSTRAINT fk_news_tag_rel_tag FOREIGN KEY (tag_id) REFERENCES news_tag (id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_czech_ci;

SET @sql := IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'news' AND COLUMN_NAME = 'seo_title_cz') = 0,
    'ALTER TABLE news ADD COLUMN seo_title_cz VARCHAR(256) NOT NULL DEFAULT '''' AFTER text_en',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'news' AND COLUMN_NAME = 'seo_title_en') = 0,
    'ALTER TABLE news ADD COLUMN seo_title_en VARCHAR(256) NOT NULL DEFAULT '''' AFTER seo_title_cz',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'news' AND COLUMN_NAME = 'seo_description_cz') = 0,
    'ALTER TABLE news ADD COLUMN seo_description_cz TEXT NULL AFTER seo_title_en',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'news' AND COLUMN_NAME = 'seo_description_en') = 0,
    'ALTER TABLE news ADD COLUMN seo_description_en TEXT NULL AFTER seo_description_cz',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
