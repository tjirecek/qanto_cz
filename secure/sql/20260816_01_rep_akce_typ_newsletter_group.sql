-- Project qanto.cz: oddělení obsahového typu letáku od skupiny příjemců newsletteru.

SET @schema := DATABASE();

SET @sql := (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE rep_akce_typ ADD COLUMN newsletter_group VARCHAR(32) NULL DEFAULT NULL AFTER color',
        'SELECT 1'
    )
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @schema
      AND TABLE_NAME = 'rep_akce_typ'
      AND COLUMN_NAME = 'newsletter_group'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

UPDATE rep_akce_typ
SET newsletter_group = CASE
    WHEN code = 'markety' OR legacy_id IN (2, 3) THEN 'maloobchod'
    WHEN code IN ('velkoobchod', 'qantoplus') OR legacy_id = 1 THEN 'velkoobchod'
    ELSE newsletter_group
END
WHERE newsletter_group IS NULL OR newsletter_group = '';
