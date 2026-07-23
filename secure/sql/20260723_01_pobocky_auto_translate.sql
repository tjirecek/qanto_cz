-- Shared příznak automatického překladu pro pobočky a otevírací doby.

SET @schema := DATABASE();

SET @sql := (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE pobocky ADD COLUMN auto_translate_en TINYINT(1) NOT NULL DEFAULT 1 AFTER valid',
        'SELECT 1'
    )
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @schema AND TABLE_NAME = 'pobocky' AND COLUMN_NAME = 'auto_translate_en'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql := (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE pobocky_otevdoba ADD COLUMN auto_translate_en TINYINT(1) NOT NULL DEFAULT 1 AFTER valid',
        'SELECT 1'
    )
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @schema AND TABLE_NAME = 'pobocky_otevdoba' AND COLUMN_NAME = 'auto_translate_en'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql := (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE pobocky_otevdoba_vyjimky ADD COLUMN auto_translate_en TINYINT(1) NOT NULL DEFAULT 1 AFTER valid',
        'SELECT 1'
    )
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @schema AND TABLE_NAME = 'pobocky_otevdoba_vyjimky' AND COLUMN_NAME = 'auto_translate_en'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
