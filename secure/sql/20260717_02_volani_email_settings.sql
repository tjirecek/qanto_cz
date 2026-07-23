-- Systémové proměnné pro odesílání vyúčtování volání

INSERT INTO settings (typ, name, popis_cz, hodnota, hodnota_text, valid, user_i, user_u)
SELECT 'volani', 'volani_from_email', 'Odesílatel e-mailů vyúčtování volání', 0, 'volani@qanto.cz', 1, 'migration', 'migration'
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1
    FROM settings
    WHERE name = 'volani_from_email'
    LIMIT 1
);
