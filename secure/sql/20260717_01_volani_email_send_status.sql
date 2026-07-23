SET @schema := DATABASE();

SET @sql := (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE volani_preuctovani ADD COLUMN email_sent_at DATETIME NULL AFTER imported_at',
        'SELECT 1'
    )
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @schema
      AND TABLE_NAME = 'volani_preuctovani'
      AND COLUMN_NAME = 'email_sent_at'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql := (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE volani_preuctovani ADD COLUMN email_sent_by VARCHAR(120) NOT NULL DEFAULT '''' AFTER email_sent_at',
        'SELECT 1'
    )
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @schema
      AND TABLE_NAME = 'volani_preuctovani'
      AND COLUMN_NAME = 'email_sent_by'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql := (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE volani_preuctovani ADD COLUMN email_send_attempts INT UNSIGNED NOT NULL DEFAULT 0 AFTER email_sent_by',
        'SELECT 1'
    )
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @schema
      AND TABLE_NAME = 'volani_preuctovani'
      AND COLUMN_NAME = 'email_send_attempts'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql := (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE volani_preuctovani ADD COLUMN email_last_error TEXT NULL AFTER email_send_attempts',
        'SELECT 1'
    )
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @schema
      AND TABLE_NAME = 'volani_preuctovani'
      AND COLUMN_NAME = 'email_last_error'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql := (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE volani_preuctovani ADD COLUMN email_log_id BIGINT UNSIGNED NULL AFTER email_last_error',
        'SELECT 1'
    )
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @schema
      AND TABLE_NAME = 'volani_preuctovani'
      AND COLUMN_NAME = 'email_log_id'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql := (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE volani_preuctovani ADD INDEX idx_volani_preuctovani_email_sent (email_sent_at)',
        'SELECT 1'
    )
    FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = @schema
      AND TABLE_NAME = 'volani_preuctovani'
      AND INDEX_NAME = 'idx_volani_preuctovani_email_sent'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
