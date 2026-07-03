# Administrace

Administrace je v `/secure` a je jedinou funkční částí tohoto projektu.

`secure/index.php` je projektový admin shell. Obsahuje branding, favicon/logo, footer,
volitelné project menu, project assety a default dashboard, proto se neportuje
automaticky jako shared baseline do QRS/QANTOPLUS. Shared změny patří do routeru,
menu, helperů, assetů a agendových include souborů.

## Routing

- Router: `secure/functions/pages_include.php`
- Dashboard menu: `secure/inc/menu/mm_dashboard.php`
- Dashboard obsah: `secure/inc/dashboard/*` je projektový a nesmí se automaticky portovat jako shared baseline.
- Shared menu: `secure/inc/menu/mm_dashboard.php`, `secure/inc/menu/mm_all.php`, `secure/inc/menu/mm_system.php`
- Project menu noveho qanto.cz: `secure/inc/menu/mm_project.php`

Project routing noveho qanto.cz je oddeleny v `secure/functions/pages_include_rep.php`.

Project vrstva qanto.cz je otevrena pouze pro novy web qanto.cz a nesmi se automaticky portovat do QRS/QANTOPLUS. Shared router/menu nesmi byt smichany s project casti bez jasneho oznaceni.

## Shared Moduly

- Přihlášení administrace a session namespace.
- Uživatelé a skupiny.
- Systémové proměnné.
- Systémové menu a práva skupin na menu.
- Novinky.
- Statické texty a výrazy.
- Fotogalerie.
- Kontakty a pobočky.
- Napište nám.
- Cron log.
- ChangeLog včetně volitelné vazby na novinku/manuál.
- DB migrace.
- E-mail log.
- TinyMCE a DataTables inicializace.
- Shared UI pro vypis cron uloh.

## Project Menu Qanto.cz

Administrace pouziva pevne sekce: `01` shared menu, `02` project menu, `09` system menu. V kazde sekci zacina `page` od `01`.

Project menu noveho webu qanto.cz je v sekci `02`:

- `01` Volna mista
- `02` Akcni nabidky
- `03` Bannery
- `04` Brigadnici
- `05` Ples
- `06` Volani
- `07` TenisQcup
- `08` Inventury

System menu je v sekci `09`.

Aktualne jsou zalozene pouze routy a placeholder stranky. Datovy model a implementace budou doplneny podle migrace ze stare DB.

## Novinky

Stranky v `secure/inc/pages/news/` se aktualne drzi jako shared admin a prenaseji se z `qanto_cz` do QRS i QANTOPLUS.

Uživatelé newsletteru (`news_users`) jsou shared část novinek. Agenda umožňuje ruční přidání, editaci, měkké smazání, ukončení/obnovení odběru a XLSX import přes šablonu. Import deduplikuje podle e-mailu a existující e-mail aktualizuje.

Odesílání newsletteru je shared agenda přes Klerk SMTP. Stránka `secure/inc/pages/news/news_info_send.php` slouží jako náhled a potvrzení odeslání, samotná logika je v `secure/functions/fun_newsletter.php`. Odesílání musí používat `klerk_*` a `newsletter_*` klíče z INI konfigurace, přidávat hlavičku `X-CampaignID` a posílat e-maily jednotlivě, aby každý příjemce dostal unikátní odhlašovací token.

Na lokálním prostředí musí newsletter respektovat `mail_bypass_enabled`. Pokud je zapnutý, neodesílá se na skutečné odběratele, ale pouze na `newsletter_local_test_email` nebo fallback `mail_bypass_email`.

Obrázky a odkazy v HTML obsahu novinek se v DB drží relativně. Při sestavení newsletteru se `src` a `href` relativní vůči webu převádějí na absolutní URL podle `newsletter_public_base_url`. Vizuál e-mailu se řídí konfiguračními klíči `newsletter_brand_name`, `newsletter_logo_url` a `newsletter_accent_color`; logo pro e-mail preferuj ve formátu PNG/JPG kvůli kompatibilitě e-mail klientů.

## Vicejazycne Editace

Novinky, fotogalerie, staticke texty a staticke vyrazy jsou shared admin agendy s vicejazycnymi poli. Preklad CZ -> EN pouziva DeepL konfiguraci z INI (`deepl_*`).

Vicejazycne zaznamy se edituji v jednom detailu pres zalozky `CZ` / `EN`; ve vypisech nepouzivat samostatne EN editacni akce. Preklad CZ -> EN pouziva obecny AJAX `secure/functions/ajax/admin_translate.php` a JS modul `assets/js/sec/lang_tabs_translate.js`.

Preklad se dela z aktualnich hodnot CZ poli ve formulari, ne z DB, aby bylo mozne prelozit i rozepsanou neulozenou upravu. To plati pro novinky, galerie, staticke texty i staticke vyrazy.

## Staticke Texty A Vyrazy

Staticke texty a staticke vyrazy jsou shared admin agenda.

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
