# 003 Typy Novinek - Mapovani Old -> Main

## Zdroj

- DB: `xqanto_cz_old`
- Tabulka: `news_typ`

## Cil

- DB: `xqanto_cz_main`
- Tabulka: `news_typ`

## Stav Zdrojovych Dat

- `xqanto_cz_old.news_typ`: 3 zaznamy.
- Validni zaznamy: 3.
- Nevalidni zaznamy: 0.

## Mapovani Sloupcu

| Zdroj | Cil | Transformace |
| --- | --- | --- |
| `news_typ.ID` | `news_typ.id` | Zachovat ID kvuli vazbam z novinek. |
| `news_typ.Poradi` | `news_typ.poradi` | Beze zmeny. |
| `news_typ.Nazev_cz` | `news_typ.nazev_cz` | Beze zmeny. |
| `news_typ.Nazev_en` | `news_typ.nazev_en` | Beze zmeny. |
| `news_typ.Popis_cz` | `news_typ.popis_cz` | Beze zmeny. |
| `news_typ.Popis_en` | `news_typ.popis_en` | Beze zmeny. |
| - | `news_typ.color` | Prazdne, stara DB tento sloupec nema. |
| `news_typ.Valid` | `news_typ.valid` | Beze zmeny. |
| - | `news_typ.user_i`, `news_typ.user_u` | `migration`. |

## Reset Cilove Databaze

- Skript `003_news_typ_migrate.php --reset` smaze pouze data v `xqanto_cz_main.news_typ`.
- QRS a QANTOPLUS databaze se touto migraci necisti.

## Beh

```bash
php migrations/old-to-main/003_news_typ_migrate.php --dry-run
php migrations/old-to-main/003_news_typ_migrate.php --reset
```
