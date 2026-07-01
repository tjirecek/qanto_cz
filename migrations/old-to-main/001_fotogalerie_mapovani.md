# 001 Fotogalerie - Mapovani Old -> Main

## Zdroj

- DB: `xqanto_cz_old`
- Primarni lokalni soubory: `/Users/tjirecek/www_dev/qanto_cz/media/galerie`
- Fallback/zaloha pro dohledani: `/Users/tjirecek/www_dev/old-qanto_cz/_images/_galerie`

## Cil

- DB: `xqanto_cz_main`
- Soubory: `/Users/tjirecek/www_dev/qanto_cz/media/galerie`

## Tabulky

| Zdroj | Cil | Transformace |
| --- | --- | --- |
| `galerie_typ.ID` | `galerie_typ.id` | Zachovat ID kvuli vazbam. |
| `galerie_typ.poradi` | `galerie_typ.poradi` | Beze zmeny. |
| `galerie_typ.nazev_cz` | `galerie_typ.nazev_cz` | Beze zmeny. |
| `galerie_typ.nazev_en` | `galerie_typ.nazev_en` | Beze zmeny. |
| `galerie_typ.popis_cz` | `galerie_typ.popis_cz` | Beze zmeny. |
| `galerie_typ.popis_en` | `galerie_typ.popis_en` | Beze zmeny. |
| `galerie_typ.valid` | `galerie_typ.valid` | Beze zmeny. |
| `galerie.ID` | `galerie.id` | Zachovat ID kvuli adresarum `{id}-galerie`. |
| `galerie.nazev_cz` | `galerie.nazev_cz` | Beze zmeny. |
| `galerie.nazev_en` | `galerie.nazev_en` | Beze zmeny. |
| `galerie.datum` | `galerie.datum` | Beze zmeny. |
| `galerie.galerie_typ` | `galerie.galerie_typ` | Vazba na `galerie_typ.id`. |
| `galerie.popis_cz` | `galerie.popis_cz` | Beze zmeny, TinyMCE HTML ponechat. |
| `galerie.popis_en` | `galerie.popis_en` | Beze zmeny, TinyMCE HTML ponechat. |
| `galerie.valid` | `galerie.valid` | Beze zmeny. |
| `galerie_photo.ID` | `galerie_photo.id` | Zachovat ID, pokud nebude konflikt. |
| `galerie_photo.poradi` | `galerie_photo.poradi` | Beze zmeny. |
| `galerie_photo.galerie_id` | `galerie_photo.galerie_id` | Vazba na `galerie.id`. |
| `galerie_photo.nazev_cz` | `galerie_photo.nazev_cz` | Beze zmeny. |
| `galerie_photo.nazev_en` | `galerie_photo.nazev_en` | Beze zmeny. |
| `galerie_photo.soubor` | `galerie_photo.soubor` | Beze zmeny, soubor musi fyzicky existovat v cilovem adresari. |
| - | `galerie_photo.mime_type` | Doplnit z fyzickeho souboru. |
| - | `galerie_photo.width` | Doplnit po zmenseni hlavni fotky. |
| - | `galerie_photo.height` | Doplnit po zmenseni hlavni fotky. |
| - | `galerie_photo.filesize` | Doplnit po zmenseni hlavni fotky. |

## Soubory

- Zdrojovy adresar galerie: `_images/_galerie/{id}-galerie/`.
- Cilovy adresar galerie: `media/galerie/{id}-galerie/`.
- Produkcni galerie se lokalne stahuji primo do `media/galerie/{id}-galerie/`; migrace je bere jako zdroj i cil.
- Pro ostrou migraci je autoritativni aktualni FTP download do `qanto_cz/media/galerie`, ne vedlejsi projekt `old-qanto_cz`.
- Pokud je zdroj v `media/galerie`, reset migrace nesmi mazat cele `media/galerie`, maze pouze generovane `small` podslozky a DB tabulky.
- Zdrojovy `small` adresar nebrat jako autoritativni; nahledy jsou male a maji se regenerovat.
- Po prekopirovani originalu znovu vytvorit `media/galerie/{id}-galerie/small/` z velkych obrazku.
- Hlavni obrazky zmensit na limity administrace, aktualne default `1920x1920`.
- Nahledy generovat na default `480x480`.

## Otevrene Body

- Rozhodnout, zda migrovat vsech 94 galerii z aktualni produkcni kopie, nebo jen vybrane historicke galerie.
- Pred ostrou migraci znovu obnovit aktualni produkcni DB jako lokalni `xqanto_cz_old`, protoze produkce se stale meni.
- Pred ostrou migraci znovu stahnout produkcni FTP soubory primo do `qanto_cz/media/galerie/*`.
- Overit nestandardni pripony a pripadne poskozene obrazky pred hromadnym zpracovanim.

## Lokalni Beh 2026-06-27

- Skript: `migrations/old-to-main/001_fotogalerie_migrate.php`.
- Report: `migrations/old-to-main/reports/001_fotogalerie_migrate_20260627_001255.md`.
- Migrovano do `xqanto_cz_main`: 6 typu, 86 galerii, 885 fotek.
- Lokalni kopie souboru neni kompletni: 6117 fotek z DB nema v `old-qanto_cz/_images/_galerie` dostupny original.
- Jedna duplicita podle galerie/souboru byla preskocena: galerie 51, soubor `img_0548.jpg`, ponechano photo ID 629.

## Lokalni Beh 2026-06-28

- Aktualni `xqanto_cz_old` obsahuje 6 typu a 94 galerii.
- Soubory se stahuji primo do `media/galerie/*`.
- Skript `001_fotogalerie_migrate.php --reset` byl upraven tak, aby pri zdroji v `media/galerie` nesmazal stazene originaly; regeneruje pouze `small` podslozky.
- Dry-run report: `migrations/old-to-main/reports/001_fotogalerie_migrate_20260628_003931.md`.
