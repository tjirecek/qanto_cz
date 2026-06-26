# Databáze

Lokální DB pro tento projekt je `xqanto_cz_main`.

## Shared Tabulky

Do tohoto projektu se kopírují pouze sdílené tabulky bez prefixu `rep_`, například:

- `users`, `users_skup`, `users_menu`, `users_skup_menu`, `users_password_resets`
- `settings`
- `stat_texty`, `stat_vyrazy`
- `news`, `news_typ`, `news_users`
- `contacts_lide`, `contacts_lide_category`
- `pobocky`, `pobocky_otevdoba`, `pobocky_otevdoba_vyjimky`
- `log_users`, `log_cron`, `log_emails`
- `changelog`
- `schema_migrations`

Projektové tabulky `rep_*` sem nepatří.

## Migrace

SQL migrace jsou v `secure/sql/` a aplikované migrace se evidují v `schema_migrations`.

Nové migrace musí být idempotentní a nesmí mazat živá data bez explicitního potvrzení.
