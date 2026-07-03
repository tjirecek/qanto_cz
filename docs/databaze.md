# Databáze

Lokální DB pro tento projekt je `xqanto_cz_main`.

Lokální MySQL běží přes Docker/Colima jako kontejner `qanto-mysql57` s image `mysql:5.7.44`
na `127.0.0.1:3306`. Toto záměrně drží lokální vývoj u MySQL 5.7, protože produkční Webglobe
prostředí zatím používá MySQL 5.7.

## Shared Tabulky

Do tohoto projektu se kopírují pouze sdílené tabulky bez prefixu `rep_`, například:

- `users`, `users_skup`, `users_menu`, `users_skup_menu`, `users_password_resets`
- `settings`
- `stat_texty`, `stat_vyrazy`
- `news`, `news_typ`, `news_users`
- `galerie_typ`, `galerie`, `galerie_photo`
- `pobocky`, `pobocky_otevdoba`, `pobocky_otevdoba_vyjimky`
- `log_users`, `log_cron`, `log_emails`
- `changelog` včetně volitelné vazby `news_id` na novinku/manuál
- `schema_migrations`

Projektové tabulky `rep_*` sem nepatří.

## Migrace

SQL migrace jsou v `secure/sql/` a aplikované migrace se evidují v `schema_migrations`.

Nové migrace musí být idempotentní a nesmí mazat živá data bez explicitního potvrzení.

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
- Stare male nahledy se nemaji prenaset jako autoritativni; po migraci originalu se maji znovu vygenerovat.
