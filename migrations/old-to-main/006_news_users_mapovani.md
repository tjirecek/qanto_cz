# 006 Uživatelé Newsletteru - Mapování Old -> Main

## Zdroj

- DB: `xqanto_cz_old`
- Tabulka: `news_users`

## Cíl

- DB: `xqanto_cz_main`
- Tabulka: `news_users`

## Rozhodnutí

- Historická data se migrují včetně nevalidních a ukončených odběrů.
- ID ze staré tabulky se zachovává kvůli dohledatelnosti.
- `user_i` a `user_u` se při migraci nastaví na `migration`, protože ve staré tabulce nejsou auditní sloupce.
- Duplicitní e-maily se při migraci nemažou ani neslučují, aby se neztratila historie.
- Ruční vložení a XLSX import v administraci deduplikuje podle e-mailu a existující e-mail aktualizuje.
- Unicode/IDN e-maily jsou validovány přes `idn_to_ascii`, pokud je dostupné PHP `intl` rozšíření.

## Mapování Sloupců

| Zdroj | Cíl | Transformace |
| --- | --- | --- |
| `news_users.ID` | `news_users.id` | Zachovat ID. |
| `news_users.name` | `news_users.name` | Trim. |
| `news_users.email` | `news_users.email` | Trim + lowercase. |
| `news_users.datum_od` | `news_users.datum_od` | Beze změny, prázdné jako `0000-00-00`. |
| `news_users.datum_do` | `news_users.datum_do` | Beze změny, prázdné jako `0000-00-00`. |
| `news_users.registered` | `news_users.registered` | `1/0`. |
| `news_users.valid` | `news_users.valid` | `1/0`. |
| - | `news_users.user_i`, `news_users.user_u` | `migration`. |

## Běh

```bash
php migrations/old-to-main/006_news_users_migrate.php --dry-run
php migrations/old-to-main/006_news_users_migrate.php --reset
```
