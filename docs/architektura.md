# Architektura

Projekt je PHP aplikace bez frameworku.

## Runtime

- `config.php` definuje `ROOT_DIR`, `INC_DIR`, `SEC_DIR` a `asset_version()`.
- `functions/bootstrap.php` řeší session a namespace administrace.
- `functions/mysql_connect.php` a `secure/functions/mysql_connect.php` načítají `ini/config_local.ini` na lokálu a `ini/config.ini` mimo lokál.
- Kořenový `index.php` přesměruje do `/secure/`.

## Administrace

- `secure/index.php` je projektový admin shell. Obsahuje branding, favicon/logo, footer, volitelné project menu, project assety a default dashboard; neportuje se automaticky jako shared baseline.
- `secure/functions/pages_include.php` je jediný admin router.
- `secure/inc/menu/*` obsahuje shared/system menu; `mm_project.php` je projektova vrstva noveho qanto.cz.
- `secure/functions/*` obsahuje sdílené helpery.
- `secure/inc/settings/*`, `secure/inc/pages/news/*`, `secure/inc/pages/stattexty/*`, `secure/inc/pages/galerie/*`, `secure/inc/pages/kontakty/*`, `secure/inc/pages/napiste_nam/*` jsou sdílené admin stránky.

## Assets

- `assets/css/secure.css` je shared admin CSS.
- Stránky administrace nesmí obsahovat inline `<style>` bloky ani `style=""` atributy. Primárně používej Bootstrap komponenty a utility třídy; vlastní CSS přidávej až po ověření, že Bootstrap nestačí. Sdílené/admin styly patří do `assets/css/secure.css`; projektové admin styly v cílových projektech patří do `assets/css/sec_rep_secure.css`.
- `assets/js/sec_*` jsou shared admin JS.
- `assets/js/sec/*` jsou shared admin JS po agendach; nove agendove skripty preferuj sem.
- `assets/js/sec/admin.js` je jediny loader agendovych admin skriptu. Stranky administrace nemaji obsahovat vlastni inline `<script>` bloky ani nacitat vsechny `assets/js/sec/*.js` najednou.
- `assets/lib/bootstrap` a `assets/lib/tinymce` jsou lokální knihovny.

Projektové prefixy `rep_*` a `sec_rep_*` zde nejsou povolené.

Výjimka pro inline CSS jsou HTML e-mailové šablony, protože kompatibilita e-mail klientů často vyžaduje inline styly.
