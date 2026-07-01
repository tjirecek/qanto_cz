# Project Notes for Codex

Tento projekt je hlavní zdroj sdílené administrace pro Qanto projekty.

## Povinný Start

- Nejdřív otevři `docs/ai-agent-routing.md` a podle něj urči vrstvu změny.
- Potom otevři jen relevantní dokument z `docs/README.md`.
- Pokud se změna týká budoucí project vrstvy qanto.cz, otevři také `docs/qanto-cz-admin-plan.md`.
- Pokud se změna týká porovnávání/přenosu do QRS nebo QANTOPLUS, otevři `docs/shared-admin-porovnani.md`.

## Projekt

- Lokální doména: `https://qanto.test` přes Laravel Herd.
- Adresář: `/Users/tjirecek/www_dev/qanto_cz`.
- Veřejný frontend zde není cílem; kořenový `index.php` přesměruje do `/secure/`.
- Administrace je v `/secure`.
- Lokální web běží přes Laravel Herd/Nginx/PHP 8.4; lokální DB běží přes Docker/Colima jako MySQL 5.7.44 kontejner `qanto-mysql57`.
- Aktuální stav je shared admin baseline + oddělená project vrstva nového webu qanto.cz.
- Projektové moduly QRS/QANTOPLUS patří do cílových projektů, ne sem.
- Budoucí project moduly nového webu qanto.cz se smějí přidat jen po výslovném zadání a musí být oddělené od shared baseline.

## Shared Admin

Sdílené soubory se stabilizují zde a následně se portují do QRS/QANTOPLUS.

Shared admin obsahuje zejména:

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
- `secure/inc/pages/galerie/*`
- `secure/inc/pages/kontakty/*`
- `secure/inc/pages/napiste_nam/*`
- `assets/css/secure.css`
- `assets/js/sec_*`
- `assets/js/sec/*`

`secure/index.php` je projektový admin shell: obsahuje branding, favicon/logo, footer,
volitelné projektové menu, projektové admin assety a default dashboard. Neportuj ho
automaticky jako shared baseline; shared části uvnitř shellu jsou router/menu/helpery
a agendové stránky.

Agendové admin skripty patří do `assets/js/sec/*` a registrují se v `assets/js/sec/admin.js`. Stránky v `secure/inc/*` nemají obsahovat vlastní inline `<script>` bloky.

Admin styly nepiš inline do PHP (`<style>` ani `style=""`). Nejdřív používej Bootstrap komponenty a utility třídy; vlastní CSS přidávej až když Bootstrap nestačí nebo by markup výrazně zhoršil čitelnost. Sdílené a obecné styly patří do `assets/css/secure.css`; projektové styly QRS/QANTOPLUS patří do `assets/css/sec_rep_secure.css`. Před přidáním nové třídy ověř kolizi názvu a používej konkrétní prefix podle agendy. Výjimka jsou HTML e-mailové šablony, kde inline CSS vyžadují e-mail klienti.

## Zakázané projektové věci

Do tohoto projektu nepatří project části QRS/QANTOPLUS:

- `rep_*` PHP/JS/CSS soubory
- projektové importy/exporty
- frontend QANTOPLUS/QRS moduly
- frontend routing v `functions/settings.php` (`/cz` vs `/cz/main` je projektové rozhodnutí)
- projektové DB tabulky s prefixem `rep_`

Project vrstva qanto.cz je povolená pouze podle `docs/qanto-cz-admin-plan.md` a musí zůstat oddělená od shared baseline. Routing administrace používá sekce `01` shared, `02` project, `09` system; `page` začíná v každé sekci od `01`.

## Databáze

- Lokální DB: `xqanto_cz_main`.
- Lokální DB server: `127.0.0.1:3306`, Docker/Colima kontejner `qanto-mysql57`.
- Lokálně používej pouze `ini/config_local.ini`.
- Produkční DB neměň bez výslovného pokynu.
- SQL migrace jsou v `secure/sql/` a evidují se v `schema_migrations`.

## Ověření

- U upravených PHP souborů spouštěj `php -l path/to/file.php`.
- U DB změn vytvoř samostatnou idempotentní migraci v `secure/sql/`.
