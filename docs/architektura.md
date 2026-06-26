# Architektura

Projekt je PHP aplikace bez frameworku.

## Runtime

- `config.php` definuje `ROOT_DIR`, `INC_DIR`, `SEC_DIR` a `asset_version()`.
- `functions/bootstrap.php` řeší session a namespace administrace.
- `functions/mysql_connect.php` a `secure/functions/mysql_connect.php` načítají `ini/config_local.ini` na lokálu a `ini/config.ini` mimo lokál.
- Kořenový `index.php` přesměruje do `/secure/`.

## Administrace

- `secure/index.php` je hlavní admin shell.
- `secure/functions/pages_include.php` je jediný admin router.
- `secure/inc/menu/*` obsahuje pouze shared/system menu.
- `secure/functions/*` obsahuje sdílené helpery.
- `secure/inc/settings/*`, `secure/inc/pages/news/*`, `secure/inc/pages/stattexty/*`, `secure/inc/pages/kontakty/*` jsou sdílené admin stránky.

## Assets

- `assets/css/secure.css` je shared admin CSS.
- `assets/js/sec_*` jsou shared admin JS.
- `assets/lib/bootstrap` a `assets/lib/tinymce` jsou lokální knihovny.

Projektové prefixy `rep_*` a `sec_rep_*` zde nejsou povolené.
