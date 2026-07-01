# Qanto.cz Admin Plan

Pracovni dokument pro novou administraci projektu `qanto_cz`.

Projekt `qanto_cz` se bude rozsirovat z cisteho shared admin baseline na administraci noveho webu `qanto.cz`, ktery nahrazuje puvodni projekt `old-qanto_cz`.

Tento dokument je jen plan pro budouci project vrstvu. Aktualni autoritativni pravidla pro beznou praci jsou v `docs/ai-agent-routing.md`.

Predprodukcni checklist pro migraci a nasazeni je v `docs/qanto-cz-predprodukce.md`.

## Cile

- vytvorit administraci noveho webu `qanto.cz`,
- zachovat shared admin jako zaklad a jasne oddelit nove projektove moduly,
- migrovat potrebna data ze stare databaze `xqanto_cz_old` do nove databaze `xqanto_cz_main`,
- pripravit opakovatelne migracni soubory pro prevod dat `xqanto_cz_old` -> `xqanto_cz_main`,
- neprebirat historicky balast bez overeni, ze je potreba pro novy web.

## Databaze

- Nova databaze: `xqanto_cz_main`
- Stara databaze: `xqanto_cz_old`
- `xqanto_cz_main` zatim v produkci neexistuje; pri navrhu noveho webu se muze lokalne menit primo.
- `xqanto_cz_old` je stale produkcni databaze; lokalni kopie je pracovni zdroj pro analyzu a migrace dat.
- Do `xqanto_cz_old` nezapisovat a nemenit ji.
- Migracni soubory pro prevod dat ze stare do nove DB jsou v `migrations/old-to-main/`.
- Produkcni databaze se neupravuje bez vyslovneho potvrzeni.
- Pred finalni produkcni migraci domluvit s Webglobe moznost a postup prechodu produkcni DB z MySQL 5.7 na novejsi MySQL nebo MariaDB; v administraci hostingu to neni bezny uzivatelsky prepinac.

## Pracovni Pravidla

- Shared admin zustava autoritativni zaklad pro QRS/QANTOPLUS.
- Projektove moduly noveho `qanto.cz` musi byt oznacene jako projektove a nemaji se automaticky portovat do QRS/QANTOPLUS.
- Dokud neni project vrstva vyslovne zadana, nepridavat `rep_*`, `sec_rep_*`, `pages_include_rep.php` ani `mm_project.php`.
- Shared admin DB zmeny zustavaji v `secure/sql/`.
- Project schema noveho `qanto.cz` se pri navrhu muze menit primo v lokalni `xqanto_cz_main`, protoze nova DB zatim nema produkcni instanci.
- Pri migraci dat nejdrive popsat zdrojove tabulky, cilove tabulky a transformace do souboru v `migrations/old-to-main/`.
- Migrace dat musi pocitat se zmenou struktur mezi `xqanto_cz_old` a `xqanto_cz_main`.
- Pred vetsimi zasahy kontrolovat `git status` a neprepisovat nesouvisejici lokalni zmeny.

## Rozsah Administrace

Hlavni bloky administrace noveho `qanto.cz`.

| # | Blok | Typ | Stav | Poznamky |
| --- | --- | --- | --- | --- |
| 1 | Novinky | shared | prvni verze hotova, lokalni migrace hotova | Sdilena agenda `secure/inc/pages/news/`; data lokalne migrovana z old DB, bez ikon `news_ico`. |
| 2 | Staticke texty | shared | prvni verze hotova | Sdilena agenda `secure/inc/pages/stattexty/`; vypisy sjednocene podle stylu galerii/kontaktu, data lokalne migrovana z old DB. |
| 3 | Volna mista | projektove | navrhnout | Project vrstva noveho `qanto.cz`. |
| 4 | Fotogalerie | shared | prvni verze hotova | Typy, galerie a fotky jsou shared agenda. Data/soubory se migruji projektove. |
| 5 | Akcni nabidky | projektove | navrhnout | Project vrstva noveho `qanto.cz`. |
| 6 | Kontakty | shared | castecne existuje | Prodejny, velkoobchody, markety, lide, obchodni zastupci. |
| 7 | Napiste nam | shared | navrhnout | Obecny kontaktni/inquiry modul. |
| 8 | Bannery | projektove | navrhnout | Project vrstva noveho `qanto.cz`. |
| 9 | Brigadnici | projektove | navrhnout | Project vrstva noveho `qanto.cz`. |
| 10 | Ples | projektove | navrhnout | Project vrstva noveho `qanto.cz`. |
| 11 | Volani | projektove | navrhnout | Project vrstva noveho `qanto.cz`. |
| 12 | TenisQcup | projektove | navrhnout | Project vrstva noveho `qanto.cz`. |
| 13 | Inventury | projektove | navrhnout | Project vrstva noveho `qanto.cz`. |

## Navrh Cislovani Menu

Admin menu je rozdeleno do pevnych sekci: `01` shared menu, `02` project menu, `09` system menu. V kazde sekci zacina `page` od `01`.

| Cislo | Menu | Vrstva | Technicka routa |
| --- | --- | --- | --- |
| 01 | Novinky | shared | `section=01&page=01` |
| 02 | Staticke texty | shared | `section=01&page=02` |
| 03 | Fotogalerie | shared | `section=01&page=03` |
| 04 | Kontakty | shared | `section=01&page=04` |
| 05 | Napiste nam | shared | `section=01&page=05` |
| 01 | Volna mista | project | `section=02&page=01` |
| 02 | Akcni nabidky | project | `section=02&page=02` |
| 03 | Bannery | project | `section=02&page=03` |
| 04 | Brigadnici | project | `section=02&page=04` |
| 05 | Ples | project | `section=02&page=05` |
| 06 | Volani | project | `section=02&page=06` |
| 07 | TenisQcup | project | `section=02&page=07` |
| 08 | Inventury | project | `section=02&page=08` |
| 01 | Uzivatele | system | `section=09&page=01` |
| 02 | System | system | `section=09&page=02` |

Shared menu je v `secure/inc/menu/mm_all.php`, project menu noveho `qanto.cz` je v `secure/inc/menu/mm_project.php`, system menu je v `secure/inc/menu/mm_system.php`. Project routy jsou oddelene v `secure/functions/pages_include_rep.php`.

Pri zmene `section/page` je nutne zkontrolovat i akcni odkazy uvnitr vypisu a detailu (`edit`, `del`, `show`, prava, export/import odkazy). `sec_page` zustava cislo konkretni akce/podstranky uvnitr agendy, ale prefix `section/page` musi vzdy odpovidat aktualnimu menu:

- shared akce patri pod `section=01`.
- project akce patri pod `section=02`.
- system akce patri pod `section=09`.

## Zakladni Oblasti Projektu

| Oblast | Stav | Poznamky |
| --- | --- | --- |
| Shared admin baseline | hotovo | Zakladni administrace, uzivatele, menu, settings, migrace. |
| Project vrstva qanto.cz | zalozeno menu/routing | Projektove moduly patri pouze do `qanto_cz`, ne do QRS/QANTOPLUS. |
| Verejny web qanto.cz | navrhnout | Nahrada puvodniho `old-qanto_cz`. |
| Migrace dat | navrhnout | Zdroj `xqanto_cz_old`, cil `xqanto_cz_main`. |
| Legacy kontakty osob | odstraneno | Stary modul `contacts_lide*` byl smazan z kodu i lokalnich DB. Kontakty/lide se navrhnou znovu v nove shared kontaktni agende. |
| Fotogalerie | prvni verze hotova, lokalni migrace castecna | Shared CRUD pro typy, galerie a fotky. Upload pres `input multiple`, serverove zmenseni hlavni fotky a generovani nahledu do `/media/galerie`. Lokalni migrace prevzala jen fotky, ktere existuji v lokalni kopii `old-qanto_cz`. |
| Staticke texty | prvni verze hotova, lokalni migrace hotova | Vypisy maji badge `valid`, sloupec `Upraveno`, DataTables filtry a horni pocty. `xqanto_cz_main.stat_texty` bylo resetovano a naplneno z `xqanto_cz_old.texty`; QRS/QANTOPLUS DB se nemazaly. |

## Pozadavky K Diktovani

### Admin Moduly

- Novinky.
- Staticke texty.
- Volna mista.
- Fotogalerie.
- Akcni nabidky.
- Kontakty.
- Napiste nam.
- Bannery.
- Brigadnici.
- Ples.
- Volani.
- TenisQcup.
- Inventury.

### Frontend / Obsah Webu

- TODO

### Datove Entity

- TODO

### Migrace Ze Stare DB

Migrujeme data ze stale produkcni stare DB `xqanto_cz_old` do nove lokalni DB `xqanto_cz_main`. Struktury se budou menit, proto se migrace dat nepripravuje jako jednoduche dump/restore, ale jako sada mapovacich a transformacnich souboru v `migrations/old-to-main/`.

| Zdroj `xqanto_cz_old` | Cil `xqanto_cz_main` | Stav | Poznamka |
| --- | --- | --- | --- |
| `galerie_typ` | `galerie_typ` | schema hotove, data migrovat | Typy galerii, zachovat poradi/nazvy/popis/valid. |
| `galerie` | `galerie` | schema hotove, data migrovat | Galerie, datum, typ, popisy. |
| `galerie_photo` | `galerie_photo` | lokalne castecne migrovano | Fotky, poradi, nazvy, soubor; nove metadata `mime_type`, `width`, `height`, `filesize`. Do DB se vkladaji jen fotky s existujicim originalem. |
| `old-qanto_cz/_images/_galerie` | `media/galerie` | lokalne castecne migrovano | Cesta zustava ve formatu `{id}-galerie/small`; nahledy se regeneruji z originalu. Lokalni kopie souboru neni kompletni. |
| `texty` | `stat_texty` | lokalne migrovano | 65 textu; `cislo` se mapuje na `code` ve formatu `text_{cislo}`, duplicity jako `text_{cislo}_{ID}`. |
| - | `stat_vyrazy` | bez migrace | Ve stare DB nebyla nalezena odpovidajici zdrojova tabulka; existujici lokalni zaznamy zustaly zachovane. |
| `news_typ` | `news_typ` | lokalne migrovano | 3 typy novinek; zachovat ID kvuli vazbam z novinek, `color` se doplnil jako prazdny. |
| `news` | `news` + `news_tag_rel` | lokalne migrovano | 401 novinek; URL zachovane, `news_ico` nemigrovano, typy zachovane, stitky prirazene podle typu. |

### SQL Migrace

- `secure/sql/20260627_02_create_gallery_tables.sql` - shared tabulky fotogalerie pro `qanto_cz`, QRS a QANTOPLUS.
- `secure/sql/20260627_03_gallery_image_settings.sql` - shared systemove promenne pro velikosti a kvalitu obrazku fotogalerie.
- `secure/sql/20260627_04_gallery_image_quality_95.sql` - nastaveni kvality JPG/WebP obrazku fotogalerie na 95.
- `migrations/old-to-main/001_fotogalerie_migrate.php` - lokalni migrace fotogalerie z `xqanto_cz_old` do `xqanto_cz_main`.
- `migrations/old-to-main/002_staticke_texty_migrate.php` - lokalni reset a migrace `texty` -> `stat_texty`.
- `migrations/old-to-main/003_news_typ_migrate.php` - lokalni reset a migrace `news_typ` -> `news_typ`.
- `migrations/old-to-main/004_news_migrate.php` - lokalni reset a migrace `news` -> `news`, vytvoreni/vyuziti stitku a vazeb `news_tag_rel`.

### Technicke Zavislosti

- XLSX exporty budou pouzivat Composer knihovnu `phpoffice/phpspreadsheet`.
- Prvni vzorovy XLSX export je `secure/functions/ajax/galerie_export.php`; exportuje validni galerie a validni fotografie s originalnimi DB nazvy sloupcu.
- Lokalne je knihovna instalovana v root `vendor/`; do Gitu patri `composer.json` a `composer.lock`, ne `vendor/`.
- Historicky mel `composer.json` `config.platform.php` a `config.platform.php-64bit` nastavene na `8.2.0` kvuli MAMP webu. Lokalni web nyni bezi pres Laravel Herd na PHP 8.4; Composer platform omezeni je kandidat na samostatne prehodnoceni podle produkcni PHP verze.
- Po nasazeni nebo klonovani projektu spustit `composer install`.

### Otevrene Otazky

- Pred finalni produkcni migraci potvrdit s Webglobe cilovou verzi databaze pro novy `qanto.cz` a pripadny harmonogram migrace z MySQL 5.7 na novejsi MySQL/MariaDB.
- Pro finalni migraci fotogalerie ziskat kompletni produkcni adresar obrazku; aktualni lokalni `old-qanto_cz/_images/_galerie` obsahuje jen cast souboru.
- Rozhodnout, zda bude v dalsi verzi potreba Dropzone/chunk upload; prvni verze pouziva jednodussi hromadny upload bez nove JS zavislosti.

## Rozhodnuti

- `functions/settings.php` je projektovy frontend routing a neni shared admin soubor.
- QRS muze pouzivat `/cz/main`; verejne weby mohou kanonicky pouzivat `/cz`.
- `xqanto_cz_main` zatim neexistuje v produkci; project schema lze pri vyvoji menit primo v lokalni DB.
- Data ze stare `xqanto_cz_old` se budou prevadet pres samostatne migracni soubory v `migrations/old-to-main/`.
- Fotogalerie uklada hlavni soubory do `/media/galerie/{id}-galerie/` a nahledy do `/media/galerie/{id}-galerie/small/`.
- Fotogalerie serverove zmensuje hlavni fotku podle systemovych promennych `galerie_orig_width` a `galerie_orig_height`, aktualne `1920x1920`.
- Fotogalerie generuje nahledy podle systemovych promennych `galerie_thumb_width` a `galerie_thumb_height`, aktualne `480x480`.
- Kvalita ukladanych JPG/WebP obrazku je rizena systemovou promennou `galerie_image_quality`, aktualne `95`.
- Pri lokalni migraci fotogalerie se nevkladaji radky `galerie_photo`, pokud fyzicky chybi originalni soubor; chybejici soubory se eviduji v reportu migrace.
- Novinky maji samostatne typy (`news_typ`) a stitky (`news_tag` + `news_tag_rel`). Typ je hlavni kategorie, stitky jsou vicenasobne viditelne oznaceni pro frontend karty/detail.
- Novinky nepouzivaji `meta keywords` a nemaji oddelene rucni ladeni pro Google/Facebook. SEO titulek a SEO popis jsou spolecny zaklad pro meta description i Open Graph fallback.
- URL novinky se generuje z data a nazvu ve formatu `YYYY-MM-DD-slug`, ale zustava rucne editovatelna.
- Viditelnost novinky v administraci se ovlada pres checkboxy `CZ` a `EN`; uklada se do stavajiciho pole `visible`.
- SQL schema pro stitky novinek a SEO sloupce je v `secure/sql/20260627_05_news_tags_and_seo.sql`.
- Detail novinky pouziva jednotnou editaci se zalozkami `CZ` / `EN`; preklad do EN vychazi z aktualnich hodnot CZ poli ve formulari.
- Uživatelé newsletteru (`news_users`) se migrují ze stare DB krokem `migrations/old-to-main/006_news_users_migrate.php`; agenda podporuje ruční správu a XLSX import přes šablonu.
- Odesílání newsletteru jde přes Klerk SMTP konfiguraci (`klerk_*`, `newsletter_*`). Před odesláním se zobrazuje náhled, e-maily se posílají jednotlivě kvůli unikátnímu odhlašovacímu tokenu.
- Lokální odesílání newsletteru respektuje `mail_bypass_enabled`; při zapnutém bypassu se odešle jen jeden testovací e-mail na `newsletter_local_test_email` nebo `mail_bypass_email`.
- Obsah novinek může v DB obsahovat relativní odkazy na média (`/media/...` nebo `media/...`); newsletter je při renderu převádí na absolutní adresy podle `newsletter_public_base_url`.
- Před produkcí doplnit veřejnou stránku/formulář pro odhlášení newsletteru na URL z `newsletter_unsubscribe_url`.
