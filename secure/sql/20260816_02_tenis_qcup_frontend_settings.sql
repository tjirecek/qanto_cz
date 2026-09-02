INSERT INTO settings (typ, name, popis_cz, hodnota, hodnota_text, valid, user_i, user_u)
SELECT 'main', 'tenis_registrace-on', 'Zapnutí veřejné registrace TenisQcup (1 = zapnuto)', 0, '', 1, 'migration', 'migration'
WHERE NOT EXISTS (SELECT 1 FROM settings WHERE name = 'tenis_registrace-on');

INSERT INTO settings (typ, name, popis_cz, hodnota, hodnota_text, valid, user_i, user_u)
SELECT 'main', 'tenis_default-year', 'Aktuální ročník registrace TenisQcup', YEAR(CURDATE()), '', 1, 'migration', 'migration'
WHERE NOT EXISTS (SELECT 1 FROM settings WHERE name = 'tenis_default-year');

INSERT INTO settings (typ, name, popis_cz, hodnota, hodnota_text, valid, user_i, user_u)
SELECT 'main', 'tenis_default-email-main', 'Příjemci upozornění na novou registraci TenisQcup (oddělit čárkou, středníkem nebo novým řádkem)', 0, 'vodicka@qanto.cz', 1, 'migration', 'migration'
WHERE NOT EXISTS (SELECT 1 FROM settings WHERE name = 'tenis_default-email-main');

UPDATE settings
SET popis_cz = 'Příjemci upozornění na novou registraci TenisQcup (oddělit čárkou, středníkem nebo novým řádkem)',
    user_u = 'migration'
WHERE name = 'tenis_default-email-main';
