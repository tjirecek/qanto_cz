# Project Notes for Codex

Tento projekt je hlavní zdroj sdílené administrace pro Qanto projekty.

## Projekt

- Lokální doména: `qanto.local`.
- Adresář: `/Users/tjirecek/mamp/qanto_cz`.
- Veřejný frontend zde není cílem; kořenový `index.php` přesměruje do `/secure/`.
- Administrace je v `/secure`.
- Projektové moduly patří do cílových projektů, ne sem.

## Shared Admin

Sdílené soubory se stabilizují zde a následně se portují do QRS/QANTOPLUS.

Shared admin obsahuje zejména:

- `secure/index.php`
- `secure/functions/admin_login.php`
- `secure/functions/fun_default.php`
- `secure/functions/fun_system.php`
- `secure/functions/fun_migrations.php`
- `secure/functions/fun_news.php`
- `secure/functions/fun_stattexty.php`
- `secure/functions/fun_pobocky.php`
- `secure/functions/pages_include.php`
- `secure/inc/menu/mm_dashboard.php`
- `secure/inc/menu/mm_all.php`
- `secure/inc/menu/mm_system.php`
- `secure/inc/settings/*`
- `secure/inc/pages/news/*`
- `secure/inc/pages/stattexty/*`
- `secure/inc/pages/kontakty/*`
- `assets/css/secure.css`
- `assets/js/sec_*`

## Zakázané projektové věci

Do tohoto projektu nepatří:

- `rep_*` PHP/JS/CSS soubory
- `pages_include_rep.php`
- `mm_project.php`
- projektové importy/exporty
- frontend QANTOPLUS/QRS moduly
- projektové DB tabulky s prefixem `rep_`

## Databáze

- Lokální DB: `xqanto_cz_main`.
- Lokálně používej pouze `ini/config_local.ini`.
- Produkční DB neměň bez výslovného pokynu.
- SQL migrace jsou v `secure/sql/` a evidují se v `schema_migrations`.

## Ověření

- U upravených PHP souborů spouštěj `php -l path/to/file.php`.
- U DB změn vytvoř samostatnou idempotentní migraci v `secure/sql/`.
