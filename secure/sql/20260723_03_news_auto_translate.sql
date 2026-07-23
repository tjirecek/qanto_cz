-- Shared příznak automatického překladu pro aktuality, jejich typy a štítky.

SET @schema := DATABASE();

SET @sql := (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE news ADD COLUMN auto_translate_en TINYINT(1) NOT NULL DEFAULT 1 AFTER valid',
        'SELECT 1'
    )
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @schema AND TABLE_NAME = 'news' AND COLUMN_NAME = 'auto_translate_en'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql := (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE news_typ ADD COLUMN auto_translate_en TINYINT(1) NOT NULL DEFAULT 1 AFTER valid',
        'SELECT 1'
    )
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @schema AND TABLE_NAME = 'news_typ' AND COLUMN_NAME = 'auto_translate_en'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql := (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE news_tag ADD COLUMN auto_translate_en TINYINT(1) NOT NULL DEFAULT 1 AFTER valid',
        'SELECT 1'
    )
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @schema AND TABLE_NAME = 'news_tag' AND COLUMN_NAME = 'auto_translate_en'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
