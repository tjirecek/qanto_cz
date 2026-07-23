-- Shared příznak automatického překladu pro statické texty a výrazy.

SET @schema := DATABASE();

SET @sql := (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE stat_texty ADD COLUMN auto_translate_en TINYINT(1) NOT NULL DEFAULT 1 AFTER valid',
        'SELECT 1'
    )
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @schema AND TABLE_NAME = 'stat_texty' AND COLUMN_NAME = 'auto_translate_en'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql := (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE stat_vyrazy ADD COLUMN auto_translate_en TINYINT(1) NOT NULL DEFAULT 1 AFTER valid',
        'SELECT 1'
    )
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @schema AND TABLE_NAME = 'stat_vyrazy' AND COLUMN_NAME = 'auto_translate_en'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
