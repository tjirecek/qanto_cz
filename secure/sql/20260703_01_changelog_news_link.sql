-- ChangeLog evidence plus optional link to a related news/manual record.
CREATE TABLE IF NOT EXISTS changelog (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    title VARCHAR(180) NOT NULL,
    description TEXT NULL,
    status ENUM('zaevidovano','naplanovano','probiha','nasazeno') NOT NULL DEFAULT 'zaevidovano',
    category ENUM('expedice','oz','maloobchod','centrala','system') NOT NULL DEFAULT 'system',
    news_id INT UNSIGNED NULL,
    priority TINYINT UNSIGNED NOT NULL DEFAULT 50,
    recorded_on DATE NOT NULL,
    planned_year SMALLINT UNSIGNED NULL,
    planned_month TINYINT UNSIGNED NULL,
    done_on DATE NULL,
    active_l TINYINT(1) NOT NULL DEFAULT 1,
    created_by VARCHAR(120) NOT NULL DEFAULT '',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_by VARCHAR(120) NULL,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_changelog_status (status),
    KEY idx_changelog_category (category),
    KEY idx_changelog_news_id (news_id),
    KEY idx_changelog_planned (planned_year, planned_month),
    KEY idx_changelog_active_priority (active_l, priority, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @sql := IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'changelog' AND COLUMN_NAME = 'news_id') = 0,
    'ALTER TABLE changelog ADD COLUMN news_id INT UNSIGNED NULL AFTER category',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
    (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'changelog' AND INDEX_NAME = 'idx_changelog_news_id') = 0,
    'ALTER TABLE changelog ADD KEY idx_changelog_news_id (news_id)',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
