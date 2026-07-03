-- Move ChangeLog categories from hardcoded ENUM to editable DB lookup.
CREATE TABLE IF NOT EXISTS changelog_cat (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    code VARCHAR(64) NOT NULL,
    name VARCHAR(120) NOT NULL,
    badge_class VARCHAR(64) NOT NULL DEFAULT 'text-bg-secondary',
    sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 50,
    active_l TINYINT(1) NOT NULL DEFAULT 1,
    created_by VARCHAR(120) NOT NULL DEFAULT '',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_by VARCHAR(120) NULL,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_changelog_cat_code (code),
    KEY idx_changelog_cat_active_sort (active_l, sort_order, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO changelog_cat (code, name, badge_class, sort_order, active_l, created_by, updated_by)
VALUES
    ('expedice', 'Expedice', 'text-bg-warning', 10, 1, 'migration_changelog_cat', 'migration_changelog_cat'),
    ('oz', 'OZ', 'text-bg-success', 20, 1, 'migration_changelog_cat', 'migration_changelog_cat'),
    ('maloobchod', 'Maloobchod', 'text-bg-primary', 30, 1, 'migration_changelog_cat', 'migration_changelog_cat'),
    ('centrala', 'Centrála', 'text-bg-dark', 40, 1, 'migration_changelog_cat', 'migration_changelog_cat'),
    ('system', 'Systém', 'text-bg-secondary', 50, 1, 'migration_changelog_cat', 'migration_changelog_cat')
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    badge_class = VALUES(badge_class),
    sort_order = VALUES(sort_order),
    active_l = 1,
    updated_by = 'migration_changelog_cat';

INSERT INTO changelog_cat (code, name, badge_class, sort_order, active_l, created_by, updated_by)
SELECT DISTINCT c.category, c.category, 'text-bg-secondary', 900, 1, 'migration_changelog_cat', 'migration_changelog_cat'
FROM changelog c
WHERE c.category IS NOT NULL
  AND c.category <> ''
  AND NOT EXISTS (
      SELECT 1
      FROM changelog_cat cc
      WHERE cc.code = c.category
  );

SET @sql := IF(
    (SELECT COUNT(*)
     FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME = 'changelog'
       AND COLUMN_NAME = 'category'
       AND DATA_TYPE = 'enum') > 0,
    'ALTER TABLE changelog MODIFY category VARCHAR(64) NOT NULL DEFAULT ''system''',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
