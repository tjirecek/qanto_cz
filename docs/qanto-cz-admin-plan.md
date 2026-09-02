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
- Project formulare pouzivaji shared komponentu `.js-admin-single-picker` pro vyber jednoho databazoveho zaznamu; `multiple`, filtry a kratke stavove nebo technicke enumy zustavaji nativni selecty.

## Rozsah Administrace

Hlavni bloky administrace noveho `qanto.cz`.

| # | Blok | Typ | Stav | Poznamky |
| --- | --- | --- | --- | --- |
| 1 | Novinky | shared | prvni verze hotova, lokalni migrace hotova | Sdilena agenda `secure/inc/pages/news/`; data lokalne migrovana z old DB, bez ikon `news_ico`. |
| 2 | Staticke texty | shared | prvni verze hotova | Sdilena agenda `secure/inc/pages/stattexty/`; vypisy sjednocene podle stylu galerii/kontaktu, data lokalne migrovana z old DB. |
| 3 | Volna mista | projektove | prvni verze hotova | Project agenda pracovních pozic, skupin a přijatých dotazníků; pozice i skupiny mají oddělený výpis/přidání/editaci, výpisy defaultně zobrazují validní záznamy, pozice mají vazbu na shared kontaktní osobu z `kontakty_lide`, odeslání nového/zrušeného místa na ÚP a dotazníky mají detail, PDF stažení a vícenásobné přílohy uchazeče. Lokálně migrováno ze starých tabulek `career*`. |
| 4 | Fotogalerie | shared | prvni verze hotova | Typy, galerie a fotky jsou shared agenda. Data/soubory se migruji projektove. |
| 5 | Akcni nabidky | projektove | prvni verze hotova | Project agenda pro typy, akční nabídky a odběratele akčních nabídek; typy mají CSS třídu badge `color` a samostatnou distribuční skupinu `newsletter_group`, která mapuje čtyři obsahové typy na maloobchodní odběr, velkoobchodní odběr, obě skupiny současně nebo může odesílání pro typ vypnout. Odběratelé vedení pro všechny akce se zahrnují do každé maloobchodní i velkoobchodní rozesílky; při společné rozesílce se adresy deduplikují. Nabídky mají příznak `is_primary` pro budoucí výběr primárních akcí na hlavní stránce, prohlížeč je primárně nad obrázkovými stranami uloženými v `/media/akce/{id}-{slug}/pages`, nové stránky se nahrávají přes FilePond async/chunk uploader, PDF jsou přesunutá do `/media/akce/{id}-{slug}/` jako download a v administraci se nahrávají samostatně přes FilePond po 4MB částech mimo hlavní formulář akce; server ověřuje přesnou délku každé části a po přenosu vyžaduje samostatnou idempotentní finalizaci celého PDF před zápisem do DB; leták lze z administrace odeslat odběratelům odpovídající distribuční skupiny e-mailem přes Klerk SMTP, e-mail obsahuje odkaz na detail letáku, PDF download, náhled první stránky a individuální podepsaný odhlašovací odkaz. Veřejná odhlašovací stránka vyžaduje potvrzení a ukončí všechny letákové odběry stejné e-mailové adresy; odkaz v testovacím e-mailu je pouze neaktivní náhled. Samostatné testovací odeslání vždy přijímá jednu explicitní adresu, nečte distribuční seznam a má předmět označený `[TEST]`; testovací i hromadné pokusy se zapisují do `log_emails`; lokální staging `_files/akce_old` byl po převodu odstraněn. |
| 6 | Kontakty | shared | prvni verze hotova | Prodejny, markety, velkoobchody, obchodni zastupci, oteviraci doby a lide ve firme existuji; oblasti se budou doplnovat dale. Pobocky/OZ se uz z `xqanto_cz_old` znovu nemigruji; osoby a skupiny byly jednorazove doplneny ze starych `contacts_lide*`. Zdrojem pro produkci je aktualni lokalni stav `xqanto_cz_main`. |
| 7 | Napiste nam | shared | prvni verze hotova | Shared agenda pro historické zprávy a kategorie kontaktního formuláře s e-mail příjemci; odesílání z frontendu se doplní později. |
| 8 | Bannery | projektove | prvni verze hotova | Project agenda pro řízení dvou homepage reklamních linií: hlavní carousel a sekundární odkazy. Banner má pozici, pořadí, platnost do, obrázek, katalogové pozadí pro bannery bez obrázku, odkaz, CZ/EN popis, CZ/EN text odkazu, tmavý/světlý text, viditelnost a validitu. Sekundární řada na frontendu se automaticky doplňuje o TOP 1 primární aktuální/nadcházející akční nabídku pro každý validní typ akce podle nejvyšší platnosti do; administrace zobrazuje aktuální počet těchto automatických odkazů a popis pravidla výběru. Frontend se napojuje na `rep_bannery`; při prázdném obsahu se daný bannerový blok nevykreslí. |
| 9 | Brigadnici | projektove | prvni verze hotova | Submenu VO/MO, registrace z frontend formulářů, filtrování podle roku, valid/nevalid přepínač, vazba na aktuální pobočky a XLSX export aktuálního filtru. Frontend registrace posílá interní notifikaci na `pobocky.email_brigada` a samostatné potvrzení uchazeči podle systémových proměnných `rep_brigadnici_confirmation_subject` a `rep_brigadnici_confirmation_body`; `pobocky.email_kariera` je připravené pro budoucí použití u kariéry. |
| 10 | Ples | projektove | prvni verze hotova | Registrace hostů z frontend formuláře, filtrování podle roku, valid/nevalid přepínač a XLSX export aktuálního filtru. |
| 11 | Volani | projektove | prvni verze hotova | Project agenda pro přeúčtování telefonních služeb z původního webu. Nově drží historii: `volani_preuctovani` má unikátní klíč `obdobi + mobil`, zákaznický token `unify`, `volani_souhrn` a `volani_detail` vážou historická data na stejné `obdobi + mobil`. Administrace umožňuje import tří Vodafone XLSX souborů (`prehled.xlsx`, `souhrn.xlsx`, `detail.xlsx`), výpis s odkazy na zákaznický souhrn/detail i e-mailový přehled za více čísel, stav/filtr odeslání, ruční i filtrované hromadné odeslání e-mailů a smazání celého období před opakovaným importem; odesílání seskupuje řádky podle `obdobi + email`, tělo bere statický text `volani` a odesílatele ze systémové proměnné `volani_from_email`; přehled bere období ze sloupce `obdobi` ve formátu `MM.RRRR` a importuje jen nenulové `sdph`, souhrn/detail deduplikují řádky přes hash; veřejný e-mailový přehled má výběr období a bez parametru zobrazí poslední dostupné období, zákaznický souhrn/detail má jednoduchý PDF export aktuálně zobrazené tabulky přes Dompdf. |
| 12 | TenisQcup | projektove | prvni verze hotova | Neveřejně propagovaná registrační stránka `/cz/tenisqcup` je dostupná jen přes přímý odkaz a ukládá týmy do administrace; úvodní nadpis a HTML informace k turnaji načítá z editovatelného statického textu `tenisqcup`. Formulář zachovává pole starého webu, používá CSRF, antispam kontrolu a souhlas se zpracováním osobních údajů. Po registraci posílá samostatně logované interní oznámení a potvrzení kontaktnímu hráči, obě ve společném Qanto e-mailovém designu a s odesílatelem `Qanto TenisQcup`; obsah potvrzení se načítá ze statického textu `tenisqcup_confirmation_email`, ve kterém lze použít zástupné hodnoty `{first_name}`, `{last_name}`, `{team_name}`, `{year}` a `{registration_id}`. Zapnutí, ročník a seznam interních příjemců řídí systémové proměnné `tenis_registrace-on`, `tenis_default-year` a `tenis_default-email-main`; více adres lze oddělit čárkou, středníkem nebo novým řádkem. Pokud proměnná zapnutí chybí nebo není validní, registrace zůstane zavřená. Inicializační SQL vytváří chybějící nastavení, ale při opakovaném spuštění nepřepisuje ručně nastavené zapnutí ani ročník. Administrace podporuje filtrování podle roku a XLSX export vybraných ročníků. |
| 13 | Inventury | projektove | navrhnout | Project vrstva noveho `qanto.cz`. |
| 14 | Zavozove obce | projektove | prvni verze | Podstranky `Obce`, `Mapa` a `Okresy`; ruční XLSX import `PSC` + `PRODEJ`, všechny obce se stavem, obsluhované/vyloučené/neobsluhované obce, okresní fallback kontakt oblasti přiřazovaný přes vyhledávací modal obchodních zástupců a admin náhled nad Mapy.com raster podkladem přes Leaflet. |

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
| 09 | Zavozove obce | project | `section=02&page=09` |
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
| Verejny web qanto.cz | zalozen frontend shell | Root `index.php` renderuje verejny web pro `/cz` a `/en`, `/secure` zustava admin. Struktura vychazi technicky z Qanto+ (`inc/*`, `assets/css/default.css`, `assets/js/default.js`), vizual se bude ladit podle Figmy. |
| Migrace dat | navrhnout | Zdroj `xqanto_cz_old`, cil `xqanto_cz_main`. |
| Legacy kontakty osob | lokalne migrovano do shared agendy | Stary modul `contacts_lide*` byl nahrazen novymi tabulkami `kontakty_lide_skupiny` a `kontakty_lide`; lokalne bylo prevedeno 8 skupin, 51 osob a 30 fotek. |
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

- Zalozen verejny frontend shell pro `/cz` a `/en`.
- Routing je pres prvni segment URL a `functions/pages_include.php`.
- Zakladni sablony jsou v `inc/`, frontend styly v `assets/css/default.css`, frontend skripty v `assets/js/default.js`.
- Verejny web pouziva figmovy model: sedive pozadi dokumentu, uprostred bily page canvas max. `1440px`; horní logo blok a menu mají full-bleed bílé pozadí přes celou šířku viewportu, ale vlastní layout používá šířku `100 %` bez `100vw`, aby šířka svislého scrollbarového pruhu nevytvářela horizontální přetečení. Jemne oddeleni obsahu se resi bordery na kartach/detailovych blocich, ne samostatnym bilym shellem uvnitr stranky.
- Zalozena hlavni verejna navigace: Letaky, Markety, Velkoobchod, Prodejny, Kariera, O nas dropdown a Kontakty; v mobilnim zobrazeni se collapse menu renderuje jako svetly kartovy panel s vnorenym dropdownem `O nas`, ne jako zmensene desktopove menu; sekce Nase znacky ma route, ale neni viditelna v hlavnim menu. Rozcestnikove karty pouzivaji ciselne znacky `01`/`02`/`03` ukotvene vlevo nahore; jejich desktopovy rozmer je sjednoceny podle homepage, vyska `104px` a sirka odpovida jedne karte trisloupcoveho rozcestniku.
- Prvni reklamni blok na homepage je plynuly carousel s kartami cca `540x300`, viditelne zhruba 2+ karty; data bere z administrace banneru. Carousel pouziva JS posun pres `translate3d` transform a merenou sirku prvni sady banneru kvuli mobilnimu Safari a sipky zastavuji propagaci kliknuti, aby se nespoustel odkaz karty pod ovladanim. Jeho frontendove CSS tridy a datove atributy pouzivaji neutralni prefix `promo-`, protoze genericke `ad-carousel` a `ad-card` skryvaly aktualizovane kosmeticke filtry blokatoru reklam. Druhy reklamni blok je pevny grid bez carouselu, desktopove pocita s 5 kartami s pomerem blizkym A4 portrait a nizsi vyskou; mobilne se sklada do jednoho sloupce. Pokud pro dany blok neni obsah, blok se nevykresli.
- Homepage blok `Letáky` bere z `rep_akce` pouze validní a viditelné letáky s koncem platnosti dnes nebo v budoucnu; výchozí panel je `Všechny` bez zvolené konkrétní kategorie, kategorie se zobrazují jen tehdy, pokud mají aspoň jeden takový leták, v panelu se ukazuje max 5 položek seřazených podle nejbližšího konce platnosti a položky rozlišují stav `platné` / `nadcházející`.
- Veřejná stránka `Letáky` zobrazuje právě platné, nadcházející a omezený archiv uplynulých letáků. Do archivu se načítají pouze nabídky, jejichž `datum_do` je od dnešního dne nejvýše jeden kalendářní měsíc zpět; pokud takové nabídky nejsou, archivní sekce se nevykreslí. Homepage nadále používá jen platné a nadcházející nabídky. Horní část má právě platné letáky vlevo a registrační blok odběru vpravo. Query parametr `typ` pouze předvybere aktivní typ, ostatní filtry zůstávají viditelné. Karty jsou stránkované bez reloadu stránky a číselné stránkování zobrazuje kompaktní okno max. pěti stránek podobně jako novinky. Breadcrumb používá stejnou ikonu a horní odsazení jako Markety. Karty mají stažení PDF a detail s obrázkovým prohlížečem nad stránkami z `/media/akce`. Detail letáku otevírá modalový čtecí režim přes téměř celý viewport, viewer využívá maximální viditelnou výšku prohlížeče a podporuje klávesové/boční listování, přechod na zadané číslo strany, přepínatelné náhledy stran, automatické listování, fullscreen a výraznou lupu se zoomem a posunem. Na počítači jedno kliknutí do stránky přepíná zvětšení 100/200 %; na dotykovém zařízení totéž dělá dvojité klepnutí. Označování obsahu vieweru je vypnuté a `Ctrl`/`Cmd` + kolečko mění zoom. Kliknutí ani tažení přímo po ploše letáku stránku nepřepíná; ruční listování je vyhrazené bočním a panelovým šipkám, náhledům, číslu stránky a klávesnici. Veřejný web i administrace zobrazují na všech zařízeních přímo plný obrázek právě jedné stránky; PageFlip canvas ani knihovna `page-flip` se nepoužívají. Přechod mezi stránkami je lehký obrazový přechod bez další rasterizace, takže poměr stran a ostrost odpovídají uloženému obrázku při základním zobrazení i zoomu. Mobilní lišta používá kompaktní stejně široká ikonová tlačítka, textové odkazy `Zpět na letáky` a `Stáhnout PDF` se na mobilu zkracují na ikonu/značku a redundantní ovladače první/poslední strany a samostatné `− / 100 % / +` se skrývají. Spodní náhledy stran mají sníženou výšku; lišta i náhledy zůstávají vodorovně posuvné dotykem, ale systémový scrollbar se vizuálně nezobrazuje. Ovládání je plovoucí mimo horní hranu letáku a viewer jde zavřít křížkem nebo klávesou `Esc` přes historii zpět na stránku, odkud byl otevřen, při přímém otevření spadne na přehled letáků. Veřejný odběr zapisuje do `rep_akce_users` jen dvě distribuční skupiny `maloobchod` a `velkoobchod`; jednotlivé obsahové typy letáků se na ně mapují přes `rep_akce_typ.newsletter_group`. Formulář je chráněný jednotnou frontend captcha ochranou.
- Typy letáků mají vlastní CSS badge třídu v `rep_akce_typ.color`; homepage ji používá u tabů kategorií a u badge kategorie v kartě letáku.
- Homepage blok `Poslední novinky` bere poslední validní a jazykově viditelné záznamy z tabulky `news`; priorita obrázku je `news_ico`, první obrázek z těla/perexu novinky a až potom stabilní pseudo-náhodný fallback obrázek z `img/design/news-default/` vygenerovaný ze zdrojů `_graphics/qanto_cz/news_icons_default`.
- Veřejná stránka `Novinky` má stejnou breadcrumb navigaci jako Markety a Letáky; ve výpisu zobrazuje nejnovější novinku samostatně nahoře jako hlavní kartu, vedle ní kompaktní formulář odběru novinek zapisující do `news_users`, další novinky pod ní v gridu, filtr podle validních štítků a stránkování přes query parametr `p`. Odběr je chráněný jednotnou frontend captcha ochranou.
- Detail novinky a detail statického textu používají společný dvousloupcový layout s pravým sloupcem sekundárních odkazů z homepage a breadcrumb navigací. Detail novinky zobrazuje perex jako lead text pod titulkem a horní obrázek z vlastní `news_ico` nebo z defaultní pseudo-náhodné ikony; první obrázek z těla se v detailu jako horní obrázek nepoužívá, aby se neduplikoval s obsahem článku. Pokud má novinka přes `news.galerie_id` přiřazenou validní galerii s fotografiemi, pod obsahem se zobrazí responzivní náhledy a fullscreen lightbox s listováním. Stránky `O nás`, `Historie`, `Média` a `Podporujeme` v menu `O nás` používají statické texty s kódy `o_nas`, `history`, `media` a `podporujeme`; interní soubory textu `media` jsou lokálně normalizované do `/media/library` a `/media/library/media`, obrázky textu `podporujeme` do `/media/staticke-texty/podporujeme`.
- Veřejná stránka `Kariéra` je napojená na project agendu `rep_volna_mista`; používá stejnou breadcrumb ikonu a horní odsazení jako ostatní hlavní výpisové stránky, zobrazuje hero blok s titulkem a textem ze `stat_texty` pod kódem `kariera`, obrázkový panel z `img/design/career/hero-market.webp`, řádkový seznam validních a viditelných pozic s odkazem na detail a Leaflet mapu celé ČR nad tabulkou `pobocky` s Mapy.com raster tile podkladem řízeným přes `frontend_mapy_api_key` v INI konfiguraci: zelené body jsou všechny pobočky s GPS, červené body ukazují pobočky s aktivními volnými místy a jejich počet. Dropdown filtr měst je umístěný nad seznamem pozic ve stejném stylu jako výběr města u poboček, nabízí jen města s volnou pozicí a filtruje současně mapu i seznam pozic; město pozice se bere z `pobocky` podle `rep_volna_mista_typ.stredisko_kod -> pobocky.stredisko`, ne z názvu skupiny. Mapa má také přepínač pouze poboček s volnými místy a v úzkém mobilním zobrazení se seznam pozic a mapa přepínají tlačítkem `Zobrazit mapu`/`Zobrazit seznam`. Detail pozice obsahuje veřejný dotazník zapisovaný do `rep_volna_mista_dotaznik`; formulář je chráněný jednotnou frontend captcha ochranou. Po uložení se notifikace posílá na e-mail kontaktní osoby přiřazené u dané pozice (`kontakty_lide.email`) a zapisuje se do `log_emails`. E-mail má jako přílohu vždy generované PDF dotazníku a volitelně také uchazečem nahrané PDF/DOC/DOCX přílohy, maximálně 5 souborů po 10 MB. Přílohy uchazeče se ukládají do `_files/volna-mista/dotazniky/`, v DB jsou vedené v `rep_volna_mista_dotaznik_prilohy` jako `protected://...`; první příloha se kvůli kompatibilitě drží také ve starších sloupcích `rep_volna_mista_dotaznik.dot_priloha_*`. Stahují se jen přes přihlášenou administraci; adresář je blokovaný přes `.htaccess`, na Nginxu je nutné přidat odpovídající `location` deny pravidlo. Veřejná stránka `/brigada` a legacy aliasy `/brigada-mo`, `/brigada-vo` poskytují obecnou registraci brigádníka do `rep_brigadnici_registrace`; pobočka se vybírá přes vyhledávací modal ze všech validních `pobocky`, `market` se zapisuje jako `typ = mo` a `prodejna`/`velkoobchod` jako `typ = vo`, formulář používá stejnou frontend captcha ochranu.
- Veřejná stránka `Markety` je napojená na validní pobočky `pobocky.typ = 'market'`; zobrazuje breadcrumb, horní rozcestníkový hero blok s `h1` odkazem `markety`, úvodní text ze `stat_texty` pod kódem `markets_intro` vpravo, na úzkém mobilním zobrazení pod rozcestníkem, levý seznam marketů, dropdown filtr města s vyhledáváním obce a Leaflet mapu bodů marketů; v úzkém mobilním zobrazení se seznam a mapa přepínají tlačítkem `Zobrazit mapu`/`Zobrazit seznam` se stejným Mapy.com podkladem jako kariérní mapa; výběr pobočky používá mírnější přiblížení a mapa má reset zpět na celou ČR. Pod mapou se vykresluje obsahový text ze `stat_texty` pod kódem `markets_text` a až potom kontaktní CTA. Město se odvozuje z adresy pobočky stejnou pomocnou logikou jako u kariérní mapy, karty v seznamu používají jednotné logo Qanto, adresu a aktuální otevírací dobu ve formátu otevřeno od-do / zavřeno dnes nebo zítra od-do. Detail marketu je dostupný na `/markety/{slug}` podle stabilního `pobocky.slug`; starší URL s `{id-slug}` zůstávají dočasně funkční jako fallback. Nahoře používá kompaktní variantu stejného rozcestníkového hero bloku s `h1` názvem marketu, adresou místo pomocného textu a číslem `01` vlevo nahoře; skládá hero fotku z `pobocky.image`; pokud úvodní fotka není vyplněná, používá logo Qanto. Detailová mapa s jedním bodem se centruje na vyšší zoom než přehledová mapa. Dále obsahuje otevírací dobu, služby, kontakt, volná místa podle střediska, hned pod nimi aktuálně platné marketové letáky (`rep_akce_typ.code = markety`) s odkazem na `/akce?typ=markety`; tlačítko `Prolistovat` otevírá stejný fullscreen flip viewer přímo nad detailem pobočky, zavírá se křížkem nebo klávesou `Esc` bez odchodu ze stránky. Následuje náhled fotogalerie s vlastním lightbox prohlížečem a sdílený kontaktní formulář z `inc/partials/contact_form.php`.
- Veřejná stránka `Velkoobchod` je routovaná jako `/velkoobchod`; používá stejný horní rozcestníkový styl jako Markety/Prodejny a stejnou světle zelenou barvu velkoobchodního rozcestníku jako homepage, úvodní text ze `stat_texty` pod kódem `velkoobchod_intro` a doplňkový text pod obchodními zástupci pod kódem `velkoobchod_text`. Hlavní mapový blok drží stejný layout jako Markety: vlevo je seznam velkoobchodních skladů s logem Qanto, aktuální otevírací dobou ve stejném formátu jako ostatní pobočky a odkazem na detail skladu, pod tím kompaktní ověření dostupnosti závozu podle obce/PSČ ve stejném vizuálním stylu jako marketový výběr města, vpravo Leaflet/Mapy.com mapa závozového území; v úzkém mobilním zobrazení se seznam skladů a mapa přepínají tlačítkem `Zobrazit mapu`/`Zobrazit seznam`; výběr pobočky používá mírnější přiblížení, popup mapu neodtlačuje a mapa má reset zpět na celou ČR. Geojson hranice se berou z obsluhovaných obcí `rep_zavoz_obce.status = served` a vykreslují se jemnou červenou transparentní výplní, body na mapě jsou jen validní velkoobchodní sklady z `pobocky.typ = velkoobchod`. Výsledek ověření dostupnosti rozlišuje obsluhujeme/neobsluhujeme/vyloučené/ke kontrole a vyhledaná obec se v mapě zvýrazní modře v širším mapovém kontextu, pokud je součástí vykresleného závozového území a kontaktní osobu bere prioritně z obce (`rep_zavoz_obce.obchodni_zastupce_id`), potom z okresu (`rep_cr_okresy.obchodni_zastupce_id`). Pod mapou je výpis validních obchodních zástupců z `obchodni_zastupci`, filtrování podle přiřazeného skladu `pobocka_id`; výběr skladu v levém seznamu u mapy, klik na bod skladu na mapě i výběr skladu ve filtru obchodních zástupců jsou vzájemně provázané. Karty obchodních zástupců mají figmový horizontální layout s fotkou, názvem pobočky, telefonem a e-mailem s ikonami, bez webového odkazu. Popis oblasti propouští očištěné základní HTML z DB, takže ruční tučnost přes `<strong>` zůstává zachovaná. Detail velkoobchodu je dostupný na `/velkoobchod/{slug}` podle stabilního `pobocky.slug` a používá stejnou detailovou skladbu jako detail marketu: rozcestníkový hero, úvodní fotku/logo Qanto, mapu s jedním bodem, otevírací dobu, služby, kontakt, výpis obchodních zástupců přiřazených k danému skladu a pod nimi volná místa podle střediska, aktuálně platné velkoobchodní letáky (`rep_akce_typ.code = velkoobchod`) s fullscreen prolistováním a náhled fotogalerie/lightbox, pokud má sklad přiřazenou galerii.
- Veřejná stránka `Prodejny` používá stejnou technickou šablonu a JS logiku jako Markety, ale filtruje `pobocky.typ = prodejna`; zobrazuje tři Qanto+ prodejny se stejnými kartami poboček s logem Qanto a aktuální otevírací dobou, v úzkém mobilním zobrazení dědí stejný přepínač seznam/mapa jako Markety, horní statický text `qantoplus_intro`, text pod mapou `qantoplus_text`, detail na `/prodejny/{slug}` podle stabilního `pobocky.slug`, Qanto+ logo, volná místa podle střediska, aktuálně platné Qanto+ letáky (`rep_akce_typ.code = qantoplus`) s odkazem na `/akce?typ=qantoplus`, fullscreen modalové prolistování letáku přímo v detailu prodejny, galerii a sdílený kontaktní formulář. Homepage rozcestník Qanto+ vede na interní `/prodejny`.
- Veřejná stránka `Kontakty` je routovaná jako `/kontakty`; stará route `/kontakt` zůstává jako alias a breadcrumb používá stejnou ikonu a horní odsazení jako ostatní hlavní výpisové stránky. Horní firemní a administrativní kontakt se neukládá natvrdo do PHP, ale do krátkých editovatelných `stat_vyrazy` s kódy `contact.company.*` a `contact.admin.*`. Lidé ve společnosti se berou ze shared tabulek `kontakty_lide_skupiny` a `kontakty_lide`; telefon a e-mail v kartách lidí používají stejné SVG ikony jako obchodní zástupci ve Velkoobchodu a popis osoby propouští očištěné základní HTML z DB. Velkoobchodní kontakty a rychlé odkazy se berou z validních poboček `pobocky.typ = 'velkoobchod'`. Kontaktní formulář je sdílený partial `inc/partials/contact_form.php` použitelný i na dalších stránkách, je pod kotvou `#kontaktni-formular`, používá ilustraci `img/design/contact-question.png`, typ dotazu bere z validních a viditelných kategorií `napiste_nam_kategorie`, zprávy ukládá do `napiste_nam_zpravy`, odesílá e-mail na příjemce kategorie a je chráněný jednotnou frontend captcha ochranou.
- Veřejná stránka `/cz/b2b-manuals` (a jazyková varianta `/en/b2b-manuals`) používá jednotnou šablonu statického textu a obsah z `stat_texty` s kódem `b2b-manuals`. MP4 video se nahrává přes FileManager do `/media/library` a do obsahu se vkládá TinyMCE nástrojem `Média`; přehrávač je na frontendu responzivní.
- Na homepage se po reklamních blocích zobrazují nejdřív `Poslední novinky` a až potom blok `Letáky`.
- Veřejný footer je součástí frontend shellu v `index.php`; obsahuje logo Qanto, skupinovou navigaci `Letáky`, `Pobočky`, `Kariéra`, `Kontakty`, sekci `Ostatní` se statickými texty `/obchodni-podminky`, `/elektronicka-vymena-dat` a `/zakaznicke-karty`, čtyři sociální odkazy řízené přes `stat_vyrazy` (`footer.social.*.url`) a právní odkazy na externí Cookies Správně cookie policy a lokální `/cz/osobni-udaje`. Právní a ostatní statické stránky používají sdílenou šablonu `inc/static_text.php` a obsah z `stat_texty` s kódy `osobni_udaje`, `obchodni_podminky`, `elektronicka_vymena_dat` a `zakaznicke_karty`. Přímá veřejná adresa `/cz/darkove-poukazy` používá stejnou šablonu a obsah ze statického textu `darkove-poukazy`, ale není přidaná do hlavního menu.
- Frontend nesmi dostavat nove texty natvrdo do PHP sablon, pomocnych PHP funkci ani JS. Pred kazdym novym textem je nutne rozhodnout cil podle tri urovni: (1) relativne pevne a funkcni UI popisky, validacni zpravy, aria texty a SEO metadata patri pojmenovane do `functions/lang.php`, ctou se pres `ui_text()` a administračně se spravují přes DB přepis `ui_texty`; (2) kratke editovatelne obsahove texty, typicky jedna veta, patri do `stat_vyrazy` a na webu se ctou jako plain text pres `stat_vyraz_text()`; (3) dlouhe a formatovane obsahove bloky, stranky a clanky patri do `stat_texty`. Texty navazane na konkretni zaznam patri primo do dane datove entity. Verejne sablony nepredavaji textove fallbacky jako druhy argument `ui_text()`; jedinym zdrojem vychoziho zneni je jazykovy katalog. Audit verejnych stran v srpnu 2026 podle tohoto pravidla presunul obsahove vety do `stat_vyrazy` a odstranil duplicitni fallbacky ze sablon a frontendovych helperu. `functions/lang.php` zustava fallback katalogem pro nasazeni a synchronizaci, administrace `UI texty webu` vklada jen chybejici klice a neprepisuje rucne upravene DB hodnoty. `stat_vyrazy` jsou plain text bez TinyMCE/HTML; HTML obsah patri do `stat_texty`. Veřejné výpisy HTML obsahu ze `stat_texty` používají jednotný editorový typografický reset ve `default.css`, který vynucuje projektový font i proti vloženým inline fontům, ale zachovává ruční tučnost/kurzívu. HTML z TinyMCE se pri ukladani cisti od `<script>` tagu na strane editoru i serveru u sdilenych obsahovych agend. Zmeny `ui_texty`, `stat_vyrazy` a `stat_texty` se v teto fazi zapisuji primo do lokalni DB a do produkce se prenesou z lokalniho stavu; nepridavat pro ne samostatne SQL migrace, pokud nebude receno jinak.
- Automatický překlad CZ -> EN při uložení je defaultně zapnutý. Ruční výjimka je přes checkbox `automaticky nepřekládat do EN` a datově přes `auto_translate_en = 0`. Překládaná pole jsou explicitně mapovaná, ne odvozovaná naslepo podle všech `*_cz` / `*_en`; shared mapa je v `secure/functions/fun_admin_translate_map.php`, project mapa qanto.cz v `secure/functions/fun_rep_admin_translate_map.php`.
- Design neni finalni 1:1; bude se ladit podle Figmy a pripadne podle pouzitelnych casti `qantoplus_cz`.

### Datove Entity

- TODO

### Migrace Ze Stare DB

Migrujeme data ze stale produkcni stare DB `xqanto_cz_old` do nove lokalni DB `xqanto_cz_main`. Struktury se budou menit, proto se migrace dat nepripravuje jako jednoduche dump/restore, ale jako sada mapovacich a transformacnich souboru v `migrations/old-to-main/`.

| Zdroj `xqanto_cz_old` | Cil `xqanto_cz_main` | Stav | Poznamka |
| --- | --- | --- | --- |
| `galerie_typ` | `galerie_typ` | schema hotove, data migrovat | Typy galerii, zachovat poradi/nazvy/popis/valid. |
| `galerie` | `galerie` | schema hotove, data migrovat | Galerie, datum, typ, popisy. |
| `galerie_photo` | `galerie_photo` | lokalne castecne migrovano | Fotky, poradi, nazvy, soubor; nove metadata `mime_type`, `width`, `height`, `filesize`. Do DB se vkladaji jen fotky s existujicim originalem. |
| `old-qanto_cz/_images/_galerie`, `_files/_galerie` | `media/galerie` | lokalne castecne migrovano | Cesta zustava ve formatu `{id}-galerie/small`; zdrojove `small`/`thumb` slozky se neprebiraji jako autoritativni a nahledy se regeneruji z originalu. Lokalni kopie souboru neni kompletni. |
| `texty` | `stat_texty` | lokalne migrovano | 65 textu; `cislo` se mapuje na `code` ve formatu `text_{cislo}`, duplicity jako `text_{cislo}_{ID}`. |
| - | `stat_vyrazy` | bez migrace | Ve stare DB nebyla nalezena odpovidajici zdrojova tabulka; existujici lokalni zaznamy zustaly zachovane. |
| `news_typ` | `news_typ` | lokalne migrovano | 3 typy novinek; zachovat ID kvuli vazbam z novinek, `color` se doplnil jako prazdny. |
| `news` | `news` + `news_tag_rel` | lokalne migrovano | 401 novinek; URL zachovane, `news_ico` nemigrovano, typy zachovane, stitky prirazene podle typu. |
| `contacts_pobocka` | `pobocky` | historicky lokalne migrovano pro velkoobchody | Importovano 5 validnich velkoobchodu, Krnov vynechan podle zadani. Dale uz se sekce Kontakty z `xqanto_cz_old` nemigruje. |
| `contacts_oz` | `obchodni_zastupci` | historicky lokalne migrovano | Importovano 32 obchodnich zastupcu vcetne fotek; 23 zustalo aktivnich a 9 puvodne `visible = 0` bylo prevedeno na `valid = 0`. Stary Krnov je navazan na existujici `Qanto+ Krnov`, protoze velkoobchodni Krnov nebyl importovan. Dale uz se sekce Kontakty z `xqanto_cz_old` nemigruje. |
| `contacts_lide_category` | `kontakty_lide_skupiny` | lokalne migrovano | 8 skupin osob vcetne poradi, CZ/EN nazvu, `visible` a `valid`. |
| `contacts_lide` | `kontakty_lide` | lokalne migrovano | 51 osob vcetne skupiny, kontaktu, funkce, popisu, `visible`, `valid` a 30 fotek presunutych do `media/kontakty-lide`. |
| `career_typ`, `career`, `career_dotaznik` | `rep_volna_mista_typ`, `rep_volna_mista`, `rep_volna_mista_dotaznik` | lokalne migrovano | Volná místa, skupiny pracovních míst a historické dotazníky uchazečů; pozice mají novou vazbu `kontakt_lide_id` na `kontakty_lide`, staré kontakty osob zůstávají jako `legacy_contact_id`. |
| `contacts_napiste` | `napiste_nam_kategorie` | lokalne migrovano | Kategorie kontaktního formuláře včetně pořadí, názvů, příjemce a kopie. |
| `contacts_napiste_emaily` | `napiste_nam_zpravy` | lokalne migrovano | Historické zprávy z kontaktního formuláře včetně nevalidních záznamů. |
| `brigadnici`, `brigadnici_mo` | `rep_brigadnici_registrace` | lokalne migrovano | Registrace brigádníků VO/MO; ve staré DB nejsou zdrojové tabulky s prefixem `rep_*`, nová cílová tabulka je project `rep_*`. `pobocka_id` drží původní ID, `pobocka_ref_id` váže MO na markety a VO na velkoobchody v aktuální tabulce `pobocky`. |
| `ples_registrace` | `rep_ples_registrace` | lokalne migrovano | Registrace hostů na Ples; ve staré DB není zdrojová tabulka s prefixem `rep_*`, nová cílová tabulka je project `rep_*`. |
| `tenis_registrace` | `rep_tenis_qcup_registrace` | lokalne migrovano | Registrace týmů na TenisQcup; ve staré DB není zdrojová tabulka s prefixem `rep_*`, nová cílová tabulka je project `rep_*`. |
| `akce`, `akce_typ`, `akce_image` | `rep_akce_typ`, `rep_akce`, `rep_akce_strany` | lokalne migrovano | Akční nabídky a typy jsou project agenda. Staré soubory byly lokálně použity jen jako staging a po převodu odstraněny; runtime soubory jsou v `/media/akce/{id}-{slug}/pages` a PDF downloady v `/media/akce/{id}-{slug}/`. Nové nabídky se nahrávají jako obrázkové stránky a PDF je pouze ke stažení. |
| `akce_users` | `rep_akce_users` | lokalne migrovano | Odběratelé akčních nabídek z frontend formuláře; `akce_typ = 0` znamená všechny akce, ostatní hodnoty se mapují přes `rep_akce_typ.legacy_id`. Historické duplicity e-mail + typ zůstávají zachované. |
| `volani_preuctovani`, `volani_souhrn`, `volani_detail` | `volani_preuctovani`, `volani_souhrn`, `volani_detail` | schema hotove, data se importují z XLSX | Původní tabulky se záměrně jmenují stejně kvůli návaznosti zákaznických odkazů a internímu pojmenování agendy; nová struktura už netruncuje historii. |

### SQL Migrace

- `secure/sql/20260627_02_create_gallery_tables.sql` - shared tabulky fotogalerie pro `qanto_cz`, QRS a QANTOPLUS.
- `secure/sql/20260627_03_gallery_image_settings.sql` - shared systemove promenne pro velikosti a kvalitu obrazku fotogalerie.
- `secure/sql/20260627_04_gallery_image_quality_95.sql` - nastaveni kvality JPG/WebP obrazku fotogalerie na 95.
- `migrations/old-to-main/001_fotogalerie_migrate.php` - lokalni migrace fotogalerie z `xqanto_cz_old` do `xqanto_cz_main`.
- `migrations/old-to-main/002_staticke_texty_migrate.php` - lokalni reset a migrace `texty` -> `stat_texty`.
- `migrations/old-to-main/003_news_typ_migrate.php` - lokalni reset a migrace `news_typ` -> `news_typ`.
- `migrations/old-to-main/004_news_migrate.php` - lokalni reset a migrace `news` -> `news`, vytvoreni/vyuziti stitku a vazeb `news_tag_rel`.
- `secure/sql/20260711_01_create_obchodni_zastupci.sql` - shared tabulka obchodnich zastupcu s vazbou na `pobocky` a pripravenym `oblast_id`.
- `secure/sql/20260711_03_create_rep_zavoz_obce.sql` - project tabulky `rep_cr_obce`, `rep_cr_obce_psc`, `rep_zavoz_obce` a importní evidence pro ruční XLSX import `PSC` + `PRODEJ`.
- `secure/sql/20260711_04_rep_zavoz_okresy.sql` - project tabulka `rep_cr_okresy`, vazba `rep_cr_obce.okres_id` a fallback kontakt oblasti přes `rep_cr_okresy.obchodni_zastupce_id`; kontakt přímo u obce má prioritu.
- `secure/sql/20260711_05_create_napiste_nam.sql` - shared tabulky `napiste_nam_kategorie` a `napiste_nam_zpravy`.
- `secure/sql/20260711_06_create_rep_tenis_qcup.sql` - project tabulka registrací `rep_tenis_qcup_registrace`.
- `secure/sql/20260711_07_create_rep_ples.sql` - project tabulka registrací `rep_ples_registrace`.
- `secure/sql/20260711_08_create_rep_brigadnici.sql` - project tabulka registrací brigádníků `rep_brigadnici_registrace`.
- `secure/sql/20260711_09_rep_brigadnici_pobocky.sql` - doplnění aktuální vazby registrací brigádníků na `pobocky`.
- `secure/sql/20260711_10_create_rep_volna_mista.sql` - project tabulky Volných míst, skupin pracovních míst a dotazníků.
- `secure/sql/20260711_11_rep_volna_mista_up_settings.sql` - project systémové proměnné pro odesílání nových a zrušených pracovních míst na ÚP.
- `secure/sql/20260712_01_rep_volna_mista_kontakt_lide.sql` - doplnění vazby pracovních míst na shared kontaktní osoby `kontakty_lide`.
- `secure/sql/20260712_02_create_rep_akce.sql` - project tabulky akčních nabídek, typů a historických obrazových stran.
- `secure/sql/20260712_03_create_rep_akce_users.sql` - project tabulka odběratelů akčních nabídek `rep_akce_users` s vazbou na `rep_akce_typ`.
- `secure/sql/20260713_01_rep_akce_primary.sql` - doplnění příznaku `rep_akce.is_primary` a indexu pro budoucí homepage výběr primárních akčních nabídek.
- `secure/sql/20260713_02_create_rep_bannery.sql` - project tabulka `rep_bannery` pro homepage bannery v hlavním carouselu a sekundárních odkazech.
- `secure/sql/20260713_03_rep_bannery_background_theme.sql` - doplnění katalogového pozadí `rep_bannery.background_theme` pro bannery bez nahraného obrázku.
- `secure/sql/20260713_05_rep_akce_typ_color.sql` - doplnění CSS badge třídy `rep_akce_typ.color` a výchozích projektových tříd pro existující typy akčních nabídek.
- `secure/sql/20260713_06_admin_auto_translate_flags.sql` - doplnění příznaku `auto_translate_en` do shared a qanto.cz project tabulek s explicitní CZ -> EN překladovou mapou.
- `secure/sql/20260713_07_rep_akce_typ_main_categories.sql` - sjednocení typů akčních nabídek na hlavní veřejné kategorie `markety`, `velkoobchod`, `qantoplus`; mimoletákové akce a odběratelé se převádí na maloobchodní typ a mimoletákový typ se znevalidní.
- `secure/sql/20260713_08_rep_akce_page_output_settings.sql` - systémové proměnné `rep_akce_page_output_format` a `rep_akce_page_image_quality` pro výstupní formát a kvalitu obrázkových stránek letáků.
- `secure/sql/20260714_01_rep_volna_mista_dotaznik_attachment.sql` - doplnění metadat přílohy k dotazníkům uchazečů (`dot_priloha_*`) v project tabulce `rep_volna_mista_dotaznik`.
- `secure/sql/20260714_02_rep_volna_mista_dotaznik_prilohy.sql` - project tabulka vícenásobných příloh dotazníků uchazečů `rep_volna_mista_dotaznik_prilohy`; existující první příloha z `dot_priloha_*` se do ní idempotentně doplní.
- `secure/sql/20260716_01_pobocky_brigada_kariera_email.sql` - doplnění pobočkových příjemců `email_brigada` a `email_kariera` pro frontendové registrace brigádníků a budoucí kariérní notifikace.
- `secure/sql/20260716_02_rep_brigadnici_confirmation_settings.sql` - systémové proměnné pro předmět a HTML text potvrzovacího e-mailu uchazeči po registraci brigádníka.
- `secure/sql/20260716_03_pobocky_slug.sql` - doplnění stabilního `pobocky.slug` a unikátního indexu `(typ, slug)` pro čisté URL detailů poboček bez ID; slug je v administraci ručně editovatelný a při změně názvu pobočky se sám nepřepisuje.
- `secure/sql/20260716_04_create_ui_texty.sql` - shared tabulka `ui_texty` pro admin editovatelný DB přepis pevných frontendových textů volaných přes `ui_text()`; runtime má per-request cache a fallback do `functions/lang.php`.
- `secure/sql/20260716_05_create_volani_tables.sql` - project tabulky `volani_preuctovani`, `volani_souhrn` a `volani_detail` pro historické přeúčtování telefonů; přehled je unikátní podle `obdobi + mobil`, zákaznický token `unify` zůstává stabilní.
- `secure/sql/20260816_01_rep_akce_typ_newsletter_group.sql` - doplnění distribuční skupiny `newsletter_group` do typů letáků; podporuje maloobchodní, velkoobchodní i společnou rozesílku a prázdná hodnota hromadné odeslání blokuje.
- `secure/sql/20260816_02_tenis_qcup_frontend_settings.sql` - aktivace systémových proměnných pro zapnutí registrační stránky TenisQcup, aktuální ročník a interního příjemce oznámení.
- `secure/sql/20260816_03_tenis_qcup_static_text_code.sql` - přejmenování migrovaného statického textu `text_201` na stabilní kód `tenisqcup` používaný registrační stránkou.
- `secure/sql/20260816_04_rep_akce_page_target_size.sql` - cílová maximální velikost jedné obrázkové strany letáku; výchozí hodnota je 400 kB a převod pro každou stranu hledá nejvyšší vyhovující kvalitu.
- `secure/sql/20260903_01_qanto_akce_users_zero_date_cleanup.sql` - převod otevřeného konce odběru akčních nabídek na `NULL` a odstranění legacy nulových defaultů z datumových sloupců.
- `secure/sql/20260717_01_volani_email_send_status.sql` - doplnění stavu odeslání e-mailu do `volani_preuctovani` (`email_sent_at`, pokusy, poslední chyba a vazba na `log_emails`).
- `secure/sql/20260717_02_volani_email_settings.sql` - systémová proměnná `volani_from_email` pro odesílatele e-mailů vyúčtování volání, výchozí `volani@qanto.cz`.
- `migrations/old-to-main/007_napiste_nam_migrate.php` - lokální migrace kategorií a historických zpráv z `contacts_napiste` a `contacts_napiste_emaily`.
- `migrations/old-to-main/008_tenis_qcup_migrate.php` - lokální migrace registrací TenisQcup z `tenis_registrace`.
- `migrations/old-to-main/009_ples_migrate.php` - lokální migrace registrací Ples z `ples_registrace`.
- `migrations/old-to-main/010_brigadnici_migrate.php` - lokální migrace registrací brigádníků z `brigadnici` a `brigadnici_mo`.
- `migrations/old-to-main/011_volna_mista_migrate.php` - lokální migrace Volných míst z `career_typ`, `career` a `career_dotaznik`.
- `migrations/old-to-main/012_akce_migrate.php` - lokální migrace akčních nabídek z `akce`, `akce_typ` a `akce_image`; soubory nekopíruje, jen váže stažené `_files/akce_old`.
- `migrations/old-to-main/013_akce_users_migrate.php` - lokální migrace odběratelů akčních nabídek ze staré tabulky `akce_users` do `rep_akce_users`.
- `migrations/old-to-main/014_volani_migrate.php` - lokální migrace aktuálního obsahu starých tabulek `volani_preuctovani`, `volani_souhrn` a `volani_detail` do nové historické struktury; bez `--run` dělá dry-run a při zápisu deduplikuje souhrn/detail přes hash řádku.
- `scripts/import_volani_prehled_xlsx.php` - lokální helper pro import přehledu Vodafone z XLSX do `volani_preuctovani`; období bere ze sloupce `obdobi` ve formátu `MM.RRRR`, volitelně jde přepsat přes `--obdobi=YYYY-MM`, bez `--run` dělá dry-run a importuje jen řádky s nenulovým `sdph`.
- `scripts/import_rep_akce_flip_pages.php` - bezpečný helper pro převod stažených `_flip/*/files/mobile` obrázků do spravovaného `/media/akce/{id}-{slug}/pages`; bez `--run` dělá pouze dry-run, po stažení všech `_flip` se použije `--run --replace`.
- `scripts/download_rep_akce_flip_mobile.php` - úsporný downloader historických flipů z `https://www.qanto.cz/_files/_flip`; stahuje pouze `mobile/javascript/config.js` a velké obrázky `files/mobile/*.jpg`, volitelně rovnou převádí akci do `/media/akce` pomocí `--import-to-media`.
- `scripts/import_rep_akce_pdfs.php` - lokální helper pro přesun historických PDF ze stagingu do `/media/akce/{id}-{slug}/` a nastavení `rep_akce.pdf_file`.
- `scripts/production_rep_akce_jpeg_to_webp_db.php` - produkční jednorázový helper pro přepnutí DB referencí JPG/JPEG stran akčních letáků na již existující sourozenecké WebP soubory; bez `--run` dělá dry-run, volitelně umí dávkování přes `--limit`/`--offer` a mazání původních JPG až přes `--delete-originals`.
- `scripts/import_rep_cr_obce_from_ruian.py` - lokální helper pro naplnění referenčních tabulek obcí a PSČ z oficiálního RÚIAN CSV ZIP souboru.
- `scripts/sync_galerie_from_files.php` - lokální helper pro opakovatelnou synchronizaci fotogalerie ze staženého stagingu `_files/_galerie` do `/media/galerie`; ignoruje zdrojové malé náhledy, doplňuje chybějící DB řádky `galerie_photo` ze staré DB a generuje nové náhledy do `small/`.

### Technicke Zavislosti

- XLSX exporty budou pouzivat Composer knihovnu `phpoffice/phpspreadsheet`.
- PDF exporty dotazníků ve Volných místech používají Composer knihovnu `dompdf/dompdf`.
- Akční nabídky používají vendored JS knihovnu `filepond` v `assets/lib/filepond` (MIT) pro asynchronní upload obrázkových stran a obnovitelný přenos PDF po 4MB částech. PDF endpoint drží dočasný transfer odděleně od cílového souboru, kontroluje přesný počet bajtů každé části a teprve explicitní finalizace přesune validní kompletní PDF do `/media/akce` a aktualizuje DB.
- Převod PDF akčních nabídek na obrázkové strany probíhá v prohlížeči přes vendored Mozilla PDF.js v `assets/lib/pdfjs` (Apache-2.0), takže nevyžaduje Poppler ani jiný PDF renderer na hostingu. PDF.js zpracovává stránky postupně, ukazuje průběh a každou hotovou stranu odesílá stávajícím chráněným upload endpointem.
- Odesílání pozic na ÚP ve Volných místech používá `mailer_send_smtp_logged()`, systémové proměnné `rep_volna_mista_up_*`, příjemce `rep_volna_mista_typ.email_up` a zapisuje do `log_emails`.
- Prvni vzorovy XLSX export je `secure/functions/ajax/galerie_export.php`; exportuje validni galerie a validni fotografie s originalnimi DB nazvy sloupcu.
- Lokalne je knihovna instalovana v root `vendor/`; do Gitu patri `composer.json` a `composer.lock`, ne `vendor/`.
- Historicky mel `composer.json` `config.platform.php` a `config.platform.php-64bit` nastavene na `8.2.0` kvuli MAMP webu. Lokalni web nyni bezi pres Laravel Herd na PHP 8.4; Composer platform omezeni je kandidat na samostatne prehodnoceni podle produkcni PHP verze.
- Po nasazeni nebo klonovani projektu spustit `composer install`.

### Otevrene Otazky

- Pred finalni produkcni migraci potvrdit s Webglobe cilovou verzi databaze pro novy `qanto.cz` a pripadny harmonogram migrace z MySQL 5.7 na novejsi MySQL/MariaDB.
- Pro finalni migraci fotogalerie ziskat kompletni produkcni adresar obrazku; aktualni lokalni `old-qanto_cz/_images/_galerie` obsahuje jen cast souboru.
- Po produkčním nasazení ověřit na reálném PDF o velikosti 100–150 MB průchod všech 4MB částí, navazující finalizaci a automatické opakování části při přerušení spojení.

## Rozhodnuti

- `functions/settings.php` je projektovy frontend routing a neni shared admin soubor.
- QRS muze pouzivat `/cz/main`; verejne weby mohou kanonicky pouzivat `/cz`.
- `xqanto_cz_main` zatim neexistuje v produkci; project schema lze pri vyvoji menit primo v lokalni DB.
- Data ze stare `xqanto_cz_old` se budou prevadet pres samostatne migracni soubory v `migrations/old-to-main/`.
- Akcni nabidky nebudou pokracovat ve stare samostatne `_flip` funkcnosti jako primarnim reseni. Cílová runtime struktura jsou obrázkové stránky v `/media/akce/{id}-{slug}/pages`. V administraci zůstává zachovaný ruční upload hotových JPG/PNG/WebP stran a vedle něj je volitelné vytvoření stran přímo z nahraného PDF v prohlížeči pomocí Mozilla PDF.js; hosting proto nepotřebuje Poppler. Převod zpracovává vždy jednu stránku, ukazuje průběh a odesílá ji stávajícím upload endpointem. Delší hrana výstupu má strop `2400 px`, nikoli povinný rozměr: čistě rastrová PDF stránka s jediným obrázkem se nevykreslí nad jeho přirozené pixelové rozlišení, zatímco vektorová nebo kombinovaná stránka využije až 2400 px. Vstupní PDF se před převodem stahuje s viditelným údajem o přenesených MB; při 30sekundové nečinnosti se požadavek ukončí a s využitím HTTP Range pokračuje od posledního přijatého bajtu, takže velké katalogy nezůstanou neomezeně viset na hlášce `Načítám PDF`. Výstupní formát, maximální kvalita a cílová velikost jedné strany se načítají ze systémových proměnných `rep_akce_page_output_format`, `rep_akce_page_image_quality` a `rep_akce_page_target_kb`. Pro každou stranu se zvolí nejvyšší kvalita, která se vejde do cílové velikosti; delší hrana se případně snižuje nejvýše na 1800 px pouze tehdy, pokud samotné snížení kvality nestačí, a nikdy se kvůli tomu nezvětšuje menší zdroj. Hotový obrázek ve stejném cílovém formátu server znovu ztrátově nepřekóduje. Pokud prohlížeč požadovaný WebP/JPG formát z canvasu přímo nevytvoří, klient použije bezeztrátový pomocný PNG a finální převod do nastaveného formátu provede server právě jednou. Nevzniká tak dvojí ztrátová komprese jemného textu a postup je stejný bez ohledu na poměr stran nebo počet stran PDF. Server z každé nové velké stránky vytváří WebP varianty `medium` (75 %, nejvýše 1800 px / 300 kB), `small` (50 %, nejvýše 1200 px / 160 kB) a `thumbs` (nejvýše 280 px / 25 kB); poměr stran se vždy zachová a žádná varianta se nezvětšuje. Historické stránky lze jednorázově převzít také ze stažených `_flip/*/files/mobile`. Pro nové ostré letáky se má z tiskového PDF vytvářet kvalitní zdroj alespoň cca `1720 x 2400 px`; malé stránky kolem `860 x 1200 px` budou při výrazné lupě přirozeně omezené zdrojem. Pro historii se celý `_flip` balík nestahuje; downloader bere jen `config.js` a velké obrázky `files/mobile/*.jpg`. PDF zůstává také jako samostatný download v `/media/akce/{id}-{slug}/`; po převodu byl staging `_files/akce_old` odstraněn.
- Obrázkový prohlížeč akčních nabídek v administraci i na frontendu pracuje přímo nad jediným `<img>` aktuální stránky: nejdříve používá `rep_akce_strany`, potom nouzově stažené `_flip/files/mobile/*.jpg`, pokud ještě nebyl spuštěn převod do `/media/akce`. Knihovna `page-flip` ani canvas se nenačítají. Prohlížeč podle skutečné šířky stránky na obrazovce, DPR zařízení a aktuálního zoomu vybere nejmenší dostačující variantu `small`/`medium`/velký originál; při přiblížení ji automaticky vymění za ostřejší. Starší letáky bez variant zůstávají funkční přes velký originál. Přepnutí stránky mění zdroj obrázku s krátkým přechodem a při dočasné HTTP chybě jej opakuje. Po načtení aktuální strany se na běžném připojení přednačte jedna předchozí a pět následujících stran ve variantě vhodné pro základní zoom, nejvýše dvě současně; při zapnutém šetření dat nebo pomalém 2G připojení pouze jedna následující. Náhledy používají samostatné WebP soubory a načítají se až při přiblížení k viditelné části seznamu, takže ani dlouhé katalogy nevyvolají stovky velkých souběžných požadavků. Zoom a posun zachovávají skutečné rozlišení i poměr stran zvolené varianty.
- Sekce Kontakty je po jednorazovem doplneni osob z tohoto dalsiho prevodu vyjmuta: pobocky, obchodni zastupci, lide a skupiny se nebudou znovu migrovat z `xqanto_cz_old`; pro produkci se pouzije aktualni lokalni stav `xqanto_cz_main`.
- Agenda Volání je project výjimka z prefixu `rep_*`: tabulky zůstávají `volani_preuctovani`, `volani_souhrn` a `volani_detail`, protože navazují na původní zákaznické odkazy `/volani/index.php?typ=...&unify=...` a jasné doménové pojmenování. Nová implementace už nemaže staré období přes `TRUNCATE`; Vodafone data importuje z XLSX a drží historii podle `obdobi + mobil`.
- Fotogalerie uklada hlavni soubory do `/media/galerie/{id}-galerie/` a nahledy do `/media/galerie/{id}-galerie/small/`.
- Fotogalerie serverove zmensuje hlavni fotku podle systemovych promennych `galerie_orig_width` a `galerie_orig_height`, aktualne `1920x1920`.
- Fotogalerie generuje nahledy podle systemovych promennych `galerie_thumb_width` a `galerie_thumb_height`, aktualne `480x480`.
- Kvalita ukladanych JPG/WebP obrazku je rizena systemovou promennou `galerie_image_quality`, aktualne `95`.
- Pri lokalni migraci fotogalerie se nevkladaji radky `galerie_photo`, pokud fyzicky chybi originalni soubor; chybejici soubory se eviduji v reportu migrace.
- Po dostazeni produkcnich souboru do `_files/_galerie` se galerie doplni pres `SERVER_ADDR=127.0.0.1 php scripts/sync_galerie_from_files.php --run --replace-originals --clean-thumbs --update-metadata`. Skript je idempotentni pro opakovane lokalni spusteni; bez `--run` dela pouze dry-run report.
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
