-- Normalize legacy zero dates in shared tables for strict MySQL.

SET @original_sql_mode := @@SESSION.sql_mode;
SET SESSION sql_mode = 'NO_ENGINE_SUBSTITUTION';

ALTER TABLE news_users MODIFY COLUMN datum_do DATE NULL DEFAULT NULL;
UPDATE news_users SET datum_do = NULL WHERE CAST(datum_do AS CHAR) LIKE '0000-00-00%';

ALTER TABLE users MODIFY COLUMN ts_i TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP;
UPDATE users SET ts_i = NULL WHERE CAST(ts_i AS CHAR) LIKE '0000-00-00%';

ALTER TABLE news MODIFY COLUMN datum DATE NULL DEFAULT NULL;
UPDATE news SET datum = NULL WHERE CAST(datum AS CHAR) LIKE '0000-00-00%';

SET SESSION sql_mode = @original_sql_mode;
