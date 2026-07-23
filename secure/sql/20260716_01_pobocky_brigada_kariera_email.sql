-- Shared příjemci pobočky pro notifikace brigád a kariéry.

SET @schema := DATABASE();

SET @sql := (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE pobocky ADD COLUMN email_brigada VARCHAR(255) NULL AFTER email',
        'SELECT 1'
    )
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @schema AND TABLE_NAME = 'pobocky' AND COLUMN_NAME = 'email_brigada'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql := (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE pobocky ADD COLUMN email_kariera VARCHAR(255) NULL AFTER email_brigada',
        'SELECT 1'
    )
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @schema AND TABLE_NAME = 'pobocky' AND COLUMN_NAME = 'email_kariera'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
