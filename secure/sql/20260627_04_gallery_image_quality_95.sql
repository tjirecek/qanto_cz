UPDATE settings
SET typ = 'main',
    popis_cz = 'Kvalita ukládaných JPG/WebP obrázků ve fotogalerii v procentech',
    hodnota = 95,
    hodnota_text = '',
    valid = 1,
    user_u = 'migration_gallery_quality_95'
WHERE name = 'galerie_image_quality';

INSERT INTO settings (typ, name, popis_cz, hodnota, hodnota_text, valid, user_i, user_u)
SELECT 'main', 'galerie_image_quality', 'Kvalita ukládaných JPG/WebP obrázků ve fotogalerii v procentech', 95, '', 1, 'migration_gallery_quality_95', 'migration_gallery_quality_95'
WHERE NOT EXISTS (SELECT 1 FROM settings WHERE name = 'galerie_image_quality');
