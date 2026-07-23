-- Shared příznak automatického překladu pro galerie, jejich typy a fotografie.

SET @schema := DATABASE();

SET @sql := (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE galerie_typ ADD COLUMN auto_translate_en TINYINT(1) NOT NULL DEFAULT 1 AFTER valid',
        'SELECT 1'
    )
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @schema AND TABLE_NAME = 'galerie_typ' AND COLUMN_NAME = 'auto_translate_en'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql := (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE galerie ADD COLUMN auto_translate_en TINYINT(1) NOT NULL DEFAULT 1 AFTER valid',
        'SELECT 1'
    )
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @schema AND TABLE_NAME = 'galerie' AND COLUMN_NAME = 'auto_translate_en'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql := (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE galerie_photo ADD COLUMN auto_translate_en TINYINT(1) NOT NULL DEFAULT 1 AFTER valid',
        'SELECT 1'
    )
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @schema AND TABLE_NAME = 'galerie_photo' AND COLUMN_NAME = 'auto_translate_en'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
