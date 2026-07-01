# 004 Novinky - Mapovani Old -> Main

## Zdroj

- DB: `xqanto_cz_old`
- Tabulka: `news`
- Soubory z produkcniho FTP se pred ostrou migraci stahuji primo do `qanto_cz/media/*`.

## Cil

- DB: `xqanto_cz_main`
- Tabulky: `news`, `news_tag`, `news_tag_rel`

## Rozhodnuti

- URL se zachovavaji presne ze stare DB (`url_cz`, `url_en`).
- Stara frontend cesta byla `/cz/index/news/{url}`; novy frontend bude muset pripravit redirect na novou kanonickou cestu.
- HTML obsah `perex_*` a `text_*` se obsahove neprepisuje. Migrace normalizuje legacy escapovani HTML atributu (`\"`, `\&quot;`) a rozbite `style="0height:..."`, aby obrazky/odkazy fungovaly v editoru.
- Jasne mapovatelne media odkazy se prepisuji rovnou bez domeny na root-relative cesty:
  - `_images/_library/*` -> `/media/library/*`
  - `_images/_news/*` -> `/media/library/x_news/*`
  - `download/*` -> `/media/download/*`
- Odkazy na stranky a nejasne soubory se pouze eviduji v reportu a doresi se pozdeji.
- Puvodni adresar `_images/_library/*` byl stazen do `media/library/*`.
- Puvodni adresar `_images/_news/*` bude zkopirovan do `media/library/x_news/*`; puvodni `_images/_news/small/*` bude zkopirovan do `media/library/x_news/small/*`.
- Puvodni adresar `download/*` byl stazen do `media/download/*`.
- Pro ostrou migraci jsou autoritativni soubory stazene z produkcniho FTP primo do tohoto projektu; `old-qanto_cz` muze byt jen zaloha/fallback pro dohledani.
- Report `005_news_links_report.php` po migraci kontroluje existenci cilovych `/media/*` odkazu a ponechava ostatni interni odkazy k rucnimu rozhodnuti.
- `news_ico` se nemigruje. Cilove `news.news_ico` zustane prazdne; novy web pouzije fallback/generovane obrazky novinek.
- `news_typ` se zachovava kvuli historicke kategorizaci.
- SEO sloupce zustanou prazdne; frontend pouzije fallback z nazvu/perexu.

## Mapovani Sloupcu

| Zdroj | Cil | Transformace |
| --- | --- | --- |
| `news.ID` | `news.id` | Zachovat ID kvuli dohledatelnosti. |
| `news.url_cz` | `news.url_cz` | Beze zmeny. |
| `news.url_en` | `news.url_en` | Beze zmeny. |
| `news.datum` | `news.datum` | Beze zmeny. |
| `news.news_typ` | `news.news_typ` | Beze zmeny; typy migruje krok `003_news_typ`. |
| `news.nazev_cz` | `news.nazev_cz` | Beze zmeny. |
| `news.nazev_en` | `news.nazev_en` | Beze zmeny. |
| `news.perex_cz` | `news.perex_cz` | Ponechat obsah, normalizovat HTML atributy, prepsat media odkazy na `/media/*`. |
| `news.perex_en` | `news.perex_en` | Ponechat obsah, normalizovat HTML atributy, prepsat media odkazy na `/media/*`. |
| `news.text_cz` | `news.text_cz` | Ponechat obsah, normalizovat HTML atributy, prepsat media odkazy na `/media/*`. |
| `news.text_en` | `news.text_en` | Ponechat obsah, normalizovat HTML atributy, prepsat media odkazy na `/media/*`. |
| - | `news.seo_title_cz/en` | Prazdne, frontend fallback z nazvu. |
| - | `news.seo_description_cz/en` | `NULL`, frontend fallback z perexu. |
| `news.galerie_id` | `news.galerie_id` | Zachovat jen pokud galerie existuje v `xqanto_cz_main.galerie`, jinak `0`. |
| `news.news_ico` | `news.news_ico` | Nemigrovat, vzdy prazdne. |
| `news.info_send` | `news.info_send` | Beze zmeny. |
| `news.visible` | `news.visible` | Beze zmeny. |
| `news.valid` | `news.valid` | Beze zmeny. |
| - | `news.user_i`, `news.user_u` | `migration`. |

## Mapovani Stitku

| `news.news_typ` | Stitky |
| ---: | --- |
| `1` Qanto | `qanto` |
| `2` Qanto maloobchod | `qanto`, `maloobchod` |
| `3` Qanto velkoobchod | `qanto`, `velkoobchod` |

Skript vytvori nebo aktualizuje stitky:

| Slug | Nazev CZ | CSS trida |
| --- | --- | --- |
| `qanto` | Qanto | `text-bg-qanto` |
| `maloobchod` | Maloobchod | `text-bg-qanto-markety` |
| `velkoobchod` | Velkoobchod | `text-bg-qanto-velkoobchod` |

## Reset Cilove Databaze

- `004_news_migrate.php --reset` maze pouze `news` a vazby `news_tag_rel`.
- Tabulku `news_tag` nemaze; potrebne stitky vytvori/updatne podle slugů.
- Ikony novinek a fyzicke obrazky se nemigruji.

## Beh

```bash
php migrations/old-to-main/004_news_migrate.php --dry-run
php migrations/old-to-main/004_news_migrate.php --reset
```
