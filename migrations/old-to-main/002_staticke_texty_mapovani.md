# 002 Staticke Texty - Mapovani Old -> Main

## Zdroj

- DB: `xqanto_cz_old`
- Tabulka: `texty`

## Cil

- DB: `xqanto_cz_main`
- Tabulka: `stat_texty`

## Stav Zdrojovych Dat

- `xqanto_cz_old.texty`: 65 zaznamu.
- Validni zaznamy: 55.
- Nevalidni zaznamy: 10.
- Zdrojova DB nema nalezenou odpovidajici tabulku pro `stat_vyrazy`.

## Mapovani Sloupcu

| Zdroj | Cil | Transformace |
| --- | --- | --- |
| `texty.ID` | `stat_texty.id` | Zachovat ID kvuli dohledatelnosti. |
| `texty.cislo` | `stat_texty.code` | `text_{cislo}`; pri duplicitnim `cislo` pouzit `text_{cislo}_{ID}`. |
| `texty.nazev_cz` | `stat_texty.nazev_cz` | Beze zmeny. |
| `texty.nazev_en` | `stat_texty.nazev_en` | Beze zmeny. |
| `texty.text_cz` | `stat_texty.text_cz` | Beze zmeny, HTML/entity obsah ponechat. |
| `texty.text_en` | `stat_texty.text_en` | Beze zmeny, HTML/entity obsah ponechat. |
| `texty.galerie_id` | `stat_texty.galerie_id` | Zachovat jen pokud galerie existuje v `xqanto_cz_main.galerie`, jinak `0`. |
| - | `stat_texty.col` | Default `12`; stara tabulka tento sloupec nema. |
| `texty.valid` | `stat_texty.valid` | Beze zmeny. |
| - | `stat_texty.user_i`, `stat_texty.user_u` | `migration`. |

## Reset Cilove Databaze

- Skript `002_staticke_texty_migrate.php --reset` smaze pouze data v `stat_texty`.
- `stat_vyrazy` skript necisti, protoze ve stare DB nebyl nalezen odpovidajici zdroj.
- QRS a QANTOPLUS lokalni ani produkcni databaze se touto migraci necisti; uz mohou obsahovat projektove existujici texty.

## Beh

```bash
php migrations/old-to-main/002_staticke_texty_migrate.php --dry-run
php migrations/old-to-main/002_staticke_texty_migrate.php --reset
```
