-- Kompatibilita se starší pracovní verzí tabulky obchodních zástupců.

SET @schema := DATABASE();

SET @sql := (
    SELECT IF(
        COUNT(*) > 0,
        'ALTER TABLE obchodni_zastupci DROP COLUMN visible',
        'SELECT 1'
    )
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @schema
      AND TABLE_NAME = 'obchodni_zastupci'
      AND COLUMN_NAME = 'visible'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
