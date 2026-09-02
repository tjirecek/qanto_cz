-- Normalize legacy zero dates in the qanto.cz action-subscriber table.

SET @original_sql_mode := @@SESSION.sql_mode;
SET SESSION sql_mode = 'NO_ENGINE_SUBSTITUTION';

ALTER TABLE rep_akce_users MODIFY COLUMN datum_od DATE NOT NULL;
ALTER TABLE rep_akce_users MODIFY COLUMN datum_do DATE NULL DEFAULT NULL;
UPDATE rep_akce_users SET datum_do = NULL WHERE CAST(datum_do AS CHAR) LIKE '0000-00-00%';

SET SESSION sql_mode = @original_sql_mode;
