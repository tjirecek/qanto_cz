-- Stabilní slug poboček pro budoucí čisté frontendové URL.

SET @schema := DATABASE();

SET @sql := (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE pobocky ADD COLUMN slug VARCHAR(190) NULL AFTER typ',
        'SELECT 1'
    )
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @schema AND TABLE_NAME = 'pobocky' AND COLUMN_NAME = 'slug'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Bezpečný stabilní fallback; v administraci lze slug následně upravit na čitelný.
UPDATE pobocky
SET slug = CONCAT(typ, '-', id)
WHERE slug IS NULL OR TRIM(slug) = '';

SET @sql := (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE pobocky ADD UNIQUE INDEX uq_pobocky_typ_slug (typ, slug)',
        'SELECT 1'
    )
    FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = @schema AND TABLE_NAME = 'pobocky' AND INDEX_NAME = 'uq_pobocky_typ_slug'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
