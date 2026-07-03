-- Allow newly created news to have no newsletter send date yet.
-- Strict MySQL refuses INSERT into news when info_send is NOT NULL without a default.

SET @sql := IF(
    (SELECT COUNT(*)
     FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME = 'news'
       AND COLUMN_NAME = 'info_send'
       AND IS_NULLABLE = 'NO') > 0,
    'ALTER TABLE news MODIFY info_send DATE NULL DEFAULT NULL',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

UPDATE news
SET info_send = NULL
WHERE CAST(info_send AS CHAR) = '0000-00-00';
