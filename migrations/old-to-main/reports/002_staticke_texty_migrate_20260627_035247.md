# 002 Staticke Texty Migrace Report

- Datum: 2026-06-27 03:52:47
- Zdroj DB: `xqanto_cz_old`
- Zdroj tabulka: `texty`
- Cil DB: `xqanto_cz_main`
- Cil tabulka: `stat_texty`
- Rezim: zapis
- Reset cilove tabulky: ano

## Pocty

| Oblast | Pocet |
| --- | ---: |
| Old `texty` | 65 |
| Cil pred `stat_texty` | 65 |
| Cil po `stat_texty` | 65 |
| Vkladanych radku | 65 |
| Validni | 55 |
| Nevalidni | 10 |

## Duplicitni `cislo` Ve Zdroji

| Cislo | Reseni |
| ---: | --- |
| 101 | code `text_101_{ID}` |
| 112 | code `text_112_{ID}` |
| 1053 | code `text_1053_{ID}` |
| 10531 | code `text_10531_{ID}` |

## Duplicitni Vysledne Kody

- Zadny konflikt.
