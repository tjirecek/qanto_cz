UPDATE settings
SET typ = 'main',
    popis_cz = 'Maximální šířka hlavní fotografie ve fotogalerii v px',
    hodnota = 1920,
    hodnota_text = '',
    valid = 1,
    user_u = 'migration_gallery_settings'
WHERE name = 'galerie_orig_width';

INSERT INTO settings (typ, name, popis_cz, hodnota, hodnota_text, valid, user_i, user_u)
SELECT 'main', 'galerie_orig_width', 'Maximální šířka hlavní fotografie ve fotogalerii v px', 1920, '', 1, 'migration_gallery_settings', 'migration_gallery_settings'
WHERE NOT EXISTS (SELECT 1 FROM settings WHERE name = 'galerie_orig_width');

UPDATE settings
SET typ = 'main',
    popis_cz = 'Maximální výška hlavní fotografie ve fotogalerii v px',
    hodnota = 1920,
    hodnota_text = '',
    valid = 1,
    user_u = 'migration_gallery_settings'
WHERE name = 'galerie_orig_height';

INSERT INTO settings (typ, name, popis_cz, hodnota, hodnota_text, valid, user_i, user_u)
SELECT 'main', 'galerie_orig_height', 'Maximální výška hlavní fotografie ve fotogalerii v px', 1920, '', 1, 'migration_gallery_settings', 'migration_gallery_settings'
WHERE NOT EXISTS (SELECT 1 FROM settings WHERE name = 'galerie_orig_height');

UPDATE settings
SET typ = 'main',
    popis_cz = 'Maximální šířka náhledu fotografie ve fotogalerii v px',
    hodnota = 480,
    hodnota_text = '',
    valid = 1,
    user_u = 'migration_gallery_settings'
WHERE name = 'galerie_thumb_width';

INSERT INTO settings (typ, name, popis_cz, hodnota, hodnota_text, valid, user_i, user_u)
SELECT 'main', 'galerie_thumb_width', 'Maximální šířka náhledu fotografie ve fotogalerii v px', 480, '', 1, 'migration_gallery_settings', 'migration_gallery_settings'
WHERE NOT EXISTS (SELECT 1 FROM settings WHERE name = 'galerie_thumb_width');

UPDATE settings
SET typ = 'main',
    popis_cz = 'Maximální výška náhledu fotografie ve fotogalerii v px',
    hodnota = 480,
    hodnota_text = '',
    valid = 1,
    user_u = 'migration_gallery_settings'
WHERE name = 'galerie_thumb_height';

INSERT INTO settings (typ, name, popis_cz, hodnota, hodnota_text, valid, user_i, user_u)
SELECT 'main', 'galerie_thumb_height', 'Maximální výška náhledu fotografie ve fotogalerii v px', 480, '', 1, 'migration_gallery_settings', 'migration_gallery_settings'
WHERE NOT EXISTS (SELECT 1 FROM settings WHERE name = 'galerie_thumb_height');

UPDATE settings
SET typ = 'main',
    popis_cz = 'Kvalita ukládaných JPG/WebP obrázků ve fotogalerii v procentech',
    hodnota = 85,
    hodnota_text = '',
    valid = 1,
    user_u = 'migration_gallery_settings'
WHERE name = 'galerie_image_quality';

INSERT INTO settings (typ, name, popis_cz, hodnota, hodnota_text, valid, user_i, user_u)
SELECT 'main', 'galerie_image_quality', 'Kvalita ukládaných JPG/WebP obrázků ve fotogalerii v procentech', 85, '', 1, 'migration_gallery_settings', 'migration_gallery_settings'
WHERE NOT EXISTS (SELECT 1 FROM settings WHERE name = 'galerie_image_quality');
