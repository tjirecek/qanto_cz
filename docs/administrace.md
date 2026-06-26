# Administrace

Administrace je v `/secure` a je jedinou funkční částí tohoto projektu.

## Routing

- Router: `secure/functions/pages_include.php`
- Dashboard: `secure/inc/dashboard/dashboard_main.php`
- Menu: `secure/inc/menu/mm_dashboard.php`, `secure/inc/menu/mm_all.php`, `secure/inc/menu/mm_system.php`

Projektové menu zde není. `mm_project.php` a `pages_include_rep.php` do tohoto projektu nepatří.

## Shared Moduly

- Přihlášení administrace a session namespace.
- Uživatelé a skupiny.
- Systémové proměnné.
- Systémové menu a práva skupin na menu.
- Novinky.
- Statické texty a výrazy.
- Kontakty a pobočky.
- Cron log.
- ChangeLog.
- DB migrace.
- E-mail log.
- TinyMCE a DataTables inicializace.
- Shared UI pro vypis cron uloh.

## Novinky

Stranky v `secure/inc/pages/news/` se aktualne drzi jako shared admin a prenaseji se z `qanto_cz` do QRS i QANTOPLUS.

Poznamka k dalsimu refaktoru: `secure/inc/pages/news/news_info_send.php` zatim obsahuje odesilani newsletteru i sablonu/branding. Pri dalsi uprave ho rozdelit na shared odesilaci logiku a projektovou sablonu/branding.

## Systemove Promenne

Typ systemove promenne je volne textove pole. Nepouzivat pevny vycet hodnot, protoze typy se mohou lisit podle projektu; prazdna hodnota se uklada jako `main`.

Vychozi limit vypisu systemovych promennych je 500 zaznamu. Rucni `limit=0` zustava zachovany pro nacteni vsech zaznamu.

## Cron

`secure/inc/settings/cron_vypis.php` je shared UI. Samotny seznam cron uloh zustava projektovy v `secure/functions/fun_rep_cron.php`.

Projektovy cron helper musi poskytovat:

- `app_cron_http_base_url(): ?string`
- `app_cron_jobs(): array`

Polozky z `app_cron_jobs()` mohou pouzivat `role` hodnoty `hosting`, `manual_child` a `legacy`. Provozni crony nastavene na hostingu maji mit `role = hosting`.

V cistem `qanto_cz` nemusi `secure/functions/fun_rep_cron.php` existovat. Shared UI v takovem pripade zobrazi prazdny vypis bez fatal chyby.

## DB Migrace

Pohled `Systémové proměnné > DB migrace` umí:

- porovnat SQL soubory v `secure/sql/` proti `schema_migrations`,
- spustit čekající migraci po opsání aktuálního `dbname`,
- vytvořit a mazat DB backupy,
- smazat evidenci migrace a odpovídající SQL soubor.

Smazání migrace není rollback databázového schématu.
