# Migrace Dat xqanto_cz_old -> xqanto_cz_main

Tato slozka je pro migracni soubory prevodu dat ze stare databaze `xqanto_cz_old` do nove databaze `xqanto_cz_main`.

## Databaze

- Zdroj: `xqanto_cz_old`
- Cil: `xqanto_cz_main`

`xqanto_cz_old` je lokalni kopie stale produkcni databaze. Migracni soubory ji nesmi menit.

`xqanto_cz_main` je nova cilova databaze pro novy web `qanto.cz`; zatim nema produkcni instanci, proto se jeji schema muze pri navrhu lokalne menit primo.

## Predmigracni Postup

Pred samotnou migraci do noveho `xqanto_cz_main`:

1. Udelat aktualni backup produkcni databaze stareho webu.
2. Obnovit tento backup lokalne jako `xqanto_cz_old`.
3. Stahnout produkcni soubory pres FTP primo do tohoto projektu `qanto_cz`, nebrat je jako autoritativni zdroj z vedlejsiho projektu.
4. Vedlejsi projekt `old-qanto_cz` muze slouzit jako zaloha/fallback pro dohledani, ale neni primarni zdroj ostre migrace.

Cilove lokalni adresare pro soubory:

- galerie: `media/galerie/*`
- knihovna obrazku z obsahu: `media/library/*`
- stare obrazky novinek: `media/library/x_news/*`
- stare obrazky novinek small: `media/library/x_news/small/*`
- download soubory: `media/download/*`

## Konvence

- Nejdrive popsat mapovani zdrojovych a cilovych tabulek.
- Migrace dat psat jako opakovatelne kroky, ktere lze pustit nad aktualni kopii `xqanto_cz_old`.
- Pokud se meni struktura cilove tabulky, popsat zmenu v pracovnim planu a pozdeji ji propsat do finalniho schema/bootstrap postupu.
- Neprebirat historicka data bez rozhodnuti, ze jsou potreba pro novy web.

## Navrhovane Cislovani

- `001_<oblast>_mapovani.md` - analyza a mapovani dat.
- `001_<oblast>_migrate.sql` - SQL migrace dat, pokud je vhodna.
- `001_<oblast>_migrate.php` - skript migrace dat, pokud je potreba transformace mimo cisty SQL.

## Stav

- `001_fotogalerie_migrate.php` - typy galerií, galerie a fotografie.
- `002_staticke_texty_migrate.php` - statické texty.
- `003_news_typ_migrate.php` - typy novinek.
- `004_news_migrate.php` - novinky, SEO fallback pole, štítky a media odkazy.
- `005_news_links_report.php` - kontrolní report odkazů v novinkách.
- `006_news_users_migrate.php` - uživatelé newsletteru z `news_users`.
