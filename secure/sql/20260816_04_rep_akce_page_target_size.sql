INSERT INTO settings (typ, name, popis_cz, hodnota, hodnota_text, valid, user_i, user_u)
SELECT 'main', 'rep_akce_page_target_kb', 'Cílová maximální velikost jedné obrázkové strany letáku v kB', 400, '', 1, 'migration', 'migration'
WHERE NOT EXISTS (SELECT 1 FROM settings WHERE name = 'rep_akce_page_target_kb');

UPDATE settings
SET valid = 1,
    user_u = 'migration'
WHERE name = 'rep_akce_page_target_kb';
