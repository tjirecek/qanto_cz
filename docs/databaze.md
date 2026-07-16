# Databáze

Lokální DB pro tento projekt je `xqanto_cz_main`.

Lokální MySQL běží přes Docker/Colima jako kontejner `qanto-mysql57` s image `mysql:5.7.44`
na `127.0.0.1:3306`. Toto záměrně drží lokální vývoj u MySQL 5.7, protože produkční Webglobe
prostředí zatím používá MySQL 5.7.

## Shared Tabulky

Do tohoto projektu se kopírují pouze sdílené tabulky bez prefixu `rep_`, například:

- `users`, `users_skup`, `users_menu`, `users_skup_menu`, `users_password_resets`
- `settings`
- `ui_texty`
- `stat_texty`, `stat_vyrazy`
- `news`, `news_typ`, `news_users`
- `galerie_typ`, `galerie`, `galerie_photo`
- `pobocky`, `pobocky_otevdoba`, `pobocky_otevdoba_vyjimky`
- `obchodni_zastupci`
- `kontakty_lide_skupiny`, `kontakty_lide`
- `napiste_nam_kategorie`, `napiste_nam_zpravy`
- `log_users`, `log_cron`, `log_emails`
- `changelog` včetně volitelné vazby `news_id` na novinku/manuál
- `changelog_cat` pro editovatelný výčet kategorií ChangeLogu a jejich barev
- `schema_migrations`

Projektové tabulky `rep_*` sem nepatří.

## Project Tabulky Qanto.cz

Project tabulky nového webu `qanto.cz` jsou oddělené prefixem `rep_` a nesmí se portovat jako shared baseline do QRS/QANTOPLUS.

- `rep_cr_okresy`, `rep_cr_obce`, `rep_cr_obce_psc` - referenční číselník okresů, obcí a vazeb PSČ.
- `rep_zavoz_obce`, `rep_zavoz_import`, `rep_zavoz_import_radky` - obsluhované/vyloučené obce a ruční import souboru se sloupci `PSC` + `PRODEJ`.
- `rep_volna_mista_typ`, `rep_volna_mista`, `rep_volna_mista_dotaznik` - volná pracovní místa, jejich skupiny/pobočky a přijaté dotazníky uchazečů ze staré kariérní agendy; `rep_volna_mista.kontakt_lide_id` váže pozici na shared kontaktní osobu v `kontakty_lide`.
- `rep_brigadnici_registrace` - registrace brigádníků VO/MO z frontend formulářů; `pobocka_id` je původní ID ze staré DB a `pobocka_ref_id` je aktuální vazba na `pobocky` (MO na markety, VO na velkoobchody, starý VO Krnov na prodejnu `Qanto+ Krnov`).
- `rep_ples_registrace` - registrace hostů na Ples z frontend formuláře.
- `rep_tenis_qcup_registrace` - registrace týmů na TenisQcup z frontend formuláře.
- `volani_preuctovani`, `volani_souhrn`, `volani_detail` - project výjimka bez prefixu `rep_*` pro přeúčtování telefonů z původního webu; názvy jsou zachované kvůli zákaznickým `unify` odkazům a interní návaznosti agendy.

Referenční tabulky `rep_cr_okresy`, `rep_cr_obce` a `rep_cr_obce_psc` se lokálně plní z oficiálních ČÚZK/RÚIAN CSV ZIP souborů pomocí `scripts/import_rep_cr_obce_from_ruian.py`. Adresní ZIP dodává obce, PSČ a souřadnice, hierarchický ZIP dodává vazby na okres/kraj/ORP a názvy se doplňují z exportů ČSÚ iSMS. `rep_cr_obce.okres_id` váže obec na okres. Okres může mít fallback kontakt oblasti přes `obchodni_zastupce_id`; kontakt nastavený přímo u obce v `rep_zavoz_obce.obchodni_zastupce_id` má prioritu. Zdrojové ZIP/CSV soubory a dočasné venv patří do `var/` a necommitují se.

Zjednodušené hranice obcí pro admin mapu se lokálně doplňují do `rep_cr_obce.geojson` pomocí `scripts/import_rep_cr_obce_geojson_from_arcgis.py` z veřejné ArcGIS REST vrstvy RÚIAN `Území obce/Území vojenského újezdu`. V administraci se polygony načítají jen pro aktivní závozové stavy, aby se do stránky neposílaly hranice celé ČR.

## Migrace

SQL migrace jsou v `secure/sql/` a aplikované migrace se evidují v `schema_migrations`.

Nové migrace musí být idempotentní a nesmí mazat živá data bez explicitního potvrzení.

Tabulka `ui_texty` slouží jako editovatelný DB přepis pevných frontendových textů z `ui_text()`. Runtime ji čte cacheovaně jednou za request; autoritativní fallback katalog zůstává v `functions/lang.php`.

## Migrace Dat Old -> Main

Pro novy web `qanto.cz` existuji dve lokalni databaze:

- `xqanto_cz_main` - nova cilova databaze, zatim bez produkcni instance.
- `xqanto_cz_old` - lokalni kopie stare produkcni databaze; slouzi jako zdroj pro analyzu a migraci dat.

Pri navrhu project vrstvy lze `xqanto_cz_main` lokalne menit primo. Do `xqanto_cz_old` se nezapisuje.

Migrace dat ze stare struktury do nove se pripravuji oddelene v `migrations/old-to-main/`, protoze se budou menit struktury tabulek a nelze spolehat na prosty dump/restore.

## Fotogalerie

Fotogalerie je shared agenda, ale obsah je projektovy podle konkretni databaze a souboru.

- DB schema: `galerie_typ`, `galerie`, `galerie_photo`.
- Soubory: `/media/galerie/{id}-galerie/`.
- Nahledy: `/media/galerie/{id}-galerie/small/`.
- Stary zdroj souboru pro migraci: `old-qanto_cz/_images/_galerie`.
- Aktualni lokalni staging stazenych produkcnich souboru: `_files/_galerie`.
- Stare male nahledy se nemaji prenaset jako autoritativni; po migraci originalu se maji znovu vygenerovat.
- Jednorazovy/opakovatelny lokalni sync se dela skriptem `scripts/sync_galerie_from_files.php`. Skript ignoruje zdrojove `small`/`thumb` slozky, bere pouze originaly, doplnuje chybejici radky `galerie_photo` ze stare DB a generuje nove nahledy do `small/`.
