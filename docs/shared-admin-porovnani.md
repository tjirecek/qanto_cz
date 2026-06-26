# Porovnani Shared Adminu

Skript `scripts/compare_shared_admin.sh` porovnava shared administraci z `qanto_cz` proti sekundarnim projektum.

## Projekty

- Primarni zdroj: `/Users/tjirecek/mamp/qanto_cz`
- Sekundarni projekt: `/Users/tjirecek/mamp/qrs-qanto_cz`
- Sekundarni projekt: `/Users/tjirecek/mamp/qantoplus_cz`

Primarni projekt je vzdy repozitar, ve kterem je skript spusten. V tomto projektu je to `qanto_cz`.

## Spusteni

Defaultni porovnani proti obema sekundarnim projektum:

```bash
scripts/compare_shared_admin.sh
```

Porovnani proti vybranym sekundarnim projektum:

```bash
scripts/compare_shared_admin.sh /Users/tjirecek/mamp/qrs-qanto_cz
scripts/compare_shared_admin.sh /Users/tjirecek/mamp/qrs-qanto_cz /Users/tjirecek/mamp/qantoplus_cz
```

Ulozeni reportu:

```bash
scripts/compare_shared_admin.sh > docs/shared-admin-compare-$(date +%Y%m%d).md
```

## Rozsah

Skript porovnava pouze shared/admin kandidaty:

- neprojektove PHP soubory v `secure/functions/`
- neprojektove AJAX endpointy v `secure/functions/ajax/`
- `secure/inc/menu/mm_all.php`, `mm_dashboard.php`, `mm_system.php`
- `secure/inc/settings/`
- `secure/inc/pages/news/`
- `secure/inc/pages/stattexty/`
- `secure/inc/pages/kontakty/`
- `assets/css/secure.css`
- `assets/js/sec_*`
- vybrane sdilene helpery ve `functions/`

Projektove soubory nejsou synchronizacni kandidati:

- `secure/functions/fun_rep_*`
- `secure/functions/pages_include_rep*`
- `secure/functions/ajax/rep_*`
- `secure/index.php`
- `secure/inc/pages/rep_*`
- `assets/js/rep_*`
- `assets/js/sec_rep_*`
- `assets/css/rep_*`
- `assets/css/sec_rep_*`

## Interpretace

- `SAME` znamena byte-identical soubor v `qanto_cz` i sekundarnim projektu.
- `DIFF` znamena shared kandidata s rozdilnym obsahem; vyzaduje manualni review.
- `ONLY_PRIMARY` znamena soubor pouze v `qanto_cz`; prenaset do sekundarnich projektu jen pokud je opravdu shared/system.
- `ONLY_<SECONDARY>` znamena soubor pouze v sekundarnim projektu; importovat do `qanto_cz` jen pokud je obecny shared admin a neni projektovy.
- `PROJECT_*`, `FRONTEND_PROJECT` a `PROJECT_OR_LEGACY` nejsou automaticke shared rozdily.

`secure/inc/settings/cron_vypis.php` je shared UI pro vypis cron uloh. Skutecny projektovy seznam cronu je v `secure/functions/fun_rep_cron.php`, ktery musi poskytovat spolecne API `app_cron_http_base_url()` a `app_cron_jobs()`.

`secure/index.php` je projektovy admin shell. Obsahuje branding, logo/favicon, footer, volitelne projektove menu `mm_project.php`, projektove admin CSS `sec_rep_*` a muze mit rozdilny default dashboard. Shared reseni je mozne az po refaktoru na spolecnou sablonu a projektovou konfiguraci.

Pri opravach shared adminu se zmena dela nejdrive v `qanto_cz`, potom se po overeni prenasi do sekundarnich projektu.
