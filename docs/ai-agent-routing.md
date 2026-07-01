# AI Agent Routing

Tento dokument je primarni rozcestnik pro AI agenty pracujici v hlavnim shared admin projektu `qanto_cz`.

Cil: pred kazdou zmenou jednoznacne urcit, zda zmena patri do shared admin baseline, do budouci project vrstvy qanto.cz, nebo do cilovych projektu QRS/QANTOPLUS.

Autoritativni role projektu:

- `qanto_cz`: `/Users/tjirecek/www_dev/qanto_cz`
- Tento projekt je hlavni zdroj shared/system administrace.
- QRS a QANTOPLUS jsou cilove projekty, do kterych se shared admin zmeny prenaseji po kontrole rozdilu.
- Project vrstvy cilovych projektu (`rep_*`, `sec_rep_*`, project routy/menu/cron/importy/objednavky) se do `qanto_cz` neprenaseji.

## 1. Prvni Rozhodnuti

Pred kazdou zmenou rozhodni:

1. Je zmena shared admin baseline?
2. Je zmena budoucí project cast noveho webu qanto.cz?
3. Je zmena urcena jen pro QRS nebo QANTOPLUS?
4. Je zmena frontend, nebo administrace?
5. Pokud DB: je schema zmena potvrzena a existuje migrace v `secure/sql/`?

Aktualni stav: `qanto_cz` obsahuje shared admin baseline a oddelenou project vrstvu noveho webu qanto.cz. Stav project vrstvy je popsany v `docs/qanto-cz-admin-plan.md`. Routing administrace pouziva pevne sekce: `01` shared, `02` project, `09` system; `page` zacina v kazde sekci od `01`.

## 2. Shared Admin Baseline

Shared admin patri sem a je autoritativni v tomto projektu.

Typicke shared soubory:

- shared router: `secure/functions/pages_include.php`
- shared menu: `secure/inc/menu/mm_dashboard.php`, `secure/inc/menu/mm_all.php`, `secure/inc/menu/mm_system.php`
- shared settings: `secure/inc/settings/*`
- shared pages: `secure/inc/pages/news/*`, `secure/inc/pages/stattexty/*`, `secure/inc/pages/galerie/*`, `secure/inc/pages/kontakty/*`, `secure/inc/pages/napiste_nam/*`
- shared helpery: `secure/functions/*.php` bez `fun_rep_*`
- shared AJAX: `secure/functions/ajax/*.php` bez `rep_*`
- shared admin CSS: `assets/css/secure.css`
- shared admin JS: `assets/js/sec_*`, `assets/js/sec/*`
- shared frontend/helper casti pouzivane administraci: `functions/bootstrap.php`, `functions/fun_mailer.php`, `functions/fun_email_log.php`, `functions/fun_users_password_reset.php`, `functions/settings.php`

`secure/index.php` neni shared baseline soubor. Je to projektovy admin shell s brandingem,
favicon/logem, footerem, volitelnym project menu, project assety a default dashboardem.
Sdilene zmeny patri do routeru, menu, helperu, assetu a agendovych include souboru,
ne do automatickeho byte-identical portu celeho shellu.

Pravidla:

- Shared zmenu udelej nejdrive zde.
- Po overeni ji prenes do QRS/QANTOPLUS jen v odpovidajicich shared souborech.
- Pokud cilovy projekt potrebuje odchylku kvuli project casti, zdokumentuj ji a neprepisuj ji slepe.

## 3. Project Vrstvy

Project cast noveho webu qanto.cz je povolena pouze pro tento projekt a musi zustat oddelena od shared baseline. QRS/QANTOPLUS project moduly do `qanto_cz` nepatri.

Do `qanto_cz` nepatri project casti QRS/QANTOPLUS:

- `secure/functions/fun_rep_*`
- `secure/inc/pages/rep_*`
- `assets/js/rep_*`
- `assets/js/sec_rep_*`
- `assets/css/rep_*`
- `assets/css/sec_rep_*`
- project importy/exporty
- project cron skripty
- DB tabulky `rep_*`
- QRS/QANTOPLUS business moduly

Vyjimka pro qanto.cz project vrstvu:

- `secure/functions/pages_include_rep.php`
- `secure/inc/menu/mm_project.php`
- `secure/inc/pages/rep_qanto/*`

## 4. Budouci Project Cast qanto.cz

Pracovni plan je v `docs/qanto-cz-admin-plan.md`.

Pri rozvoji noveho webu qanto.cz:

1. Nejdřív aktualizuj `docs/qanto-cz-admin-plan.md`.
2. Zaved project vrstvu oddelene od shared baseline.
3. Pouzij stejne prefixy jako v cilovych projektech: `rep_*` pro project soubory a `sec_rep_*` pro admin/project assety.
4. Project zmeny qanto.cz se nesmi automaticky portovat do QRS/QANTOPLUS.
5. Shared baseline musi zustat rozpoznatelna a prenositelna.

## 5. Porovnavani s QRS/QANTOPLUS

Pravidla porovnani jsou v `docs/shared-admin-porovnani.md`.

Porovnavat jako shared:

- shared/system soubory bez `rep_*`
- `assets/css/secure.css`
- `assets/js/sec_*`
- `assets/js/sec/*`
- shared settings/news/stattexty/kontakty pages
- shared helpery a AJAX endpointy

Nezahrnovat jako shared:

- project routy/menu/helpery/AJAX/pages
- `rep_*`, `sec_rep_*`
- frontend/project assety a verejne sablony
- project cron/import/export skripty
- project DB tabulky

## 6. Databaze

- Lokální DB: `xqanto_cz_main`.
- Shared DB tabulky nemaji prefix `rep_*`.
- Project tabulky `rep_*` sem nepatri, dokud nebude vyslovne otevrena project cast qanto.cz.
- DB schema nemen bez potvrzeni.
- Kazda schema zmena patri do idempotentni migrace v `secure/sql/`.

## 7. Overeni

Po zmene:

1. Spust `php -l` na upravenych PHP souborech.
2. U JS spust `node --check`, pokud dava smysl.
3. U DB zmen priprav SQL migraci a popis dopadu.
4. Zkontroluj `git status`.
5. Aktualizuj relevantni dokumentaci ve stejne zmene.
