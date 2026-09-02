# Administrace

Administrace je v `/secure` a je jedinou funkční částí tohoto projektu.

`secure/index.php` je projektový admin shell. Obsahuje branding, favicon/logo, footer,
volitelné project menu, project assety a default dashboard, proto se neportuje
automaticky jako shared baseline do QRS/QANTOPLUS. Shared změny patří do routeru,
menu, helperů, assetů a agendových include souborů.

## Routing

- Router: `secure/functions/pages_include.php`
- Dashboard menu: `secure/inc/menu/mm_dashboard.php`
- Dashboard obsah: `secure/inc/dashboard/*` je projektový a nesmí se automaticky portovat jako shared baseline.
- Shared menu: `secure/inc/menu/mm_dashboard.php`, `secure/inc/menu/mm_all.php`, `secure/inc/menu/mm_system.php`
- Project menu noveho qanto.cz: `secure/inc/menu/mm_project.php`

Project routing noveho qanto.cz je oddeleny v `secure/functions/pages_include_rep.php`.

Project vrstva qanto.cz je otevrena pouze pro novy web qanto.cz a nesmi se automaticky portovat do QRS/QANTOPLUS. Shared router/menu nesmi byt smichany s project casti bez jasneho oznaceni.

## Shared Moduly

- Přihlášení administrace a session namespace.
- Uživatelé a skupiny.
- Systémové proměnné.
- UI texty webu jako DB přepis pro `ui_text()` s fallbackem do `functions/lang.php`.
- Systémové menu a práva skupin na menu.
- Novinky.
- Statické texty a výrazy.
- Fotogalerie.
- Kontakty a pobočky.
- Obchodní zástupci s vazbou na pobočku a připravenou vazbou na oblast.
- Otevírací doby jako souhrnný přehled všech aktivních poboček s editací v detailu pobočky.
- Kontaktní osoby ve firmě a jejich skupiny včetně fotek.
- Napište nám: historické zprávy a kategorie kontaktního formuláře s příjemci e-mailů.
- Cron log.
- ChangeLog včetně editovatelných kategorií a volitelné vazby na novinku/manuál.
- DB migrace.
- E-mail log.
- TinyMCE a DataTables inicializace.
- Shared UI pro vypis cron uloh.

## Univerzalni Vyber Jednoho Zaznamu

Komponenta `admin single picker` je zavazny shared standard pro vzhledove nahrazeni bezneho HTML `<select>` pri vyberu prave jednoho zaznamu. Multiple selecty se touto komponentou nenahrazuji.

Autoritativni soubory:

- `assets/js/sec/admin_single_picker.js` - inicializace, spolecny Bootstrap modal, hledani a synchronizace hodnoty.
- `assets/js/sec/admin.js` - registrace modulu podle selektoru `.js-admin-single-picker`.
- `assets/css/secure.css` - vzhled triggeru, vysledku a z-index modalu/backdropu.

Puvodni `<select>` musi zustat ve formulari se stejnym `name`, hodnotami, `required` a `disabled` stavem. Komponenta se aktivuje pouze pridanim tridy `js-admin-single-picker`; bez JavaScriptu zustane nativni select viditelny a funkcni. Po inicializaci JavaScript skryje zdrojovy select pristupnym vizualnim skrytim, zapisuje volbu zpet do jeho `value` a pri skutecne zmene vyvola bublajici nativni udalost `change`.

Zakladni pouziti:

```html
<select
    name="user_id"
    class="form-select js-admin-single-picker"
    data-picker-title="Vybrat uživatele"
    data-picker-description="Vyberte právě jednoho uživatele."
    data-picker-search-placeholder="Hledat podle jména nebo loginu…"
    data-picker-empty-label="Bez uživatele"
    required
>
    <option value="">Bez uživatele</option>
    <option value="15" data-picker-subtext="novak" data-picker-icon="bi bi-person">Jan Novák</option>
</select>
```

Podporovane atributy selectu:

- `data-picker-title` - nadpis spolecneho modalu.
- `data-picker-description` - volitelny popis pod nadpisem.
- `data-picker-search-placeholder` - placeholder hledani.
- `data-picker-empty-label` - text triggeru a prazdne option s `value=""`.

Podporovane atributy `<option>`:

- `data-picker-subtext` - doplnkovy text, typicky login, kod nebo pobocka; zahrnuje se do hledani.
- `data-picker-icon` - bezpecny seznam CSS trid ikony, typicky Bootstrap Icons `bi bi-person`.

Hledani ignoruje diakritiku a prohledava soucasne text option i `data-picker-subtext`. Enter vybere prvni viditelnou povolenou polozku, Escape modal zavre. Aktualni volba je oznacena. Disabled select vytvori disabled trigger a disabled option nelze vybrat. `required` zustava nativni vlastnosti selectu; pri chybe se zvyrazni a zaostri viditelny trigger.

Vsechny pickery na strance pouzivaji jediny modal `#adminSinglePickerModal`, ktery JavaScript vytvari jednou a pripojuje primo pod `<body>`. Picker modal a jeho backdrop maji explicitni vyssi z-index; po zavreni se uklidi jeho backdrop a zachova `modal-open`, pokud pod nim zustal otevreny jiny Bootstrap modal.

Pri programove zmene hodnoty pouzij standardni udalost `change`, aby se aktualizoval i trigger a navazujici aplikacni logika:

```js
const select = document.querySelector('#user_id');
select.value = '15';
select.dispatchEvent(new Event('change', { bubbles: true }));
```

Pro dynamicky doplnene options komponenta pouziva `MutationObserver`. Rucni prekresleni lze vyvolat pres `window.QantoAdminSinglePicker.refresh(select)` nebo `refreshAll()`.

Picker se pouziva na single selecty, ktere vybiraji jeden databazovy zaznam nebo spravovanou kategorii. V shared administraci je nasazen na typech novinky, prirazene fotogalerii, typu galerie, skupine kontaktni osoby, pobočce obchodniho zastupce, skupine uzivatele a kategorii ChangeLogu. V project vrstve qanto.cz je nasazen na typu akcni nabidky, typu odberu akcí, skupine pracovniho mista a jeho kontaktni osobe. Backendove ukladani a nazvy poli zustavaji beze zmeny. Puvodni samostatne modaly pro pobočku obchodniho zastupce a kontaktni osobu pracovniho mista jsou nahrazeny stejnym zdrojovym selectem a spolecnym modalem komponenty.

Nativni select zustava standardem pro `multiple`, filtry vypisu a kratke stavove nebo technicke enumy, napriklad Ano/Ne, barvu, mesic, rok, stav odeslani nebo rezim prohlizeni. U techto poli by modal neprinesl lepsi orientaci ani hledani. Pri auditu noveho formulare se picker nepridava plosne podle HTML tagu, ale podle tohoto vyznamoveho pravidla.

## Project Menu Qanto.cz

Administrace pouziva pevne sekce: `01` shared menu, `02` project menu, `09` system menu. V kazde sekci zacina `page` od `01`.

Project menu noveho webu qanto.cz je v sekci `02`:

- `01` Volna mista
- `02` Akcni nabidky
- `03` Bannery
- `04` Brigadnici
- `05` Ples
- `06` Volani
- `07` TenisQcup
- `08` Inventury
- `09` Zavozove obce

Agenda `01 Volná místa` má podstránky `Pozice`, `Skupiny` a `Dotazníky`. Pozice i skupiny mají samostatný výpis, přidání a editaci; formulář se zobrazuje pouze po kliknutí na akci přidání nebo úpravy. Filtr skupin ve výpisech pozic a dotazníků je řešen kompaktním vyhledávacím dropdownem se zaškrtávátky; pozice navíc doplňuje filtr `Zobrazovat` pro hodnoty `Vše`, `Ano`, `Ne`. Výpisy standardně zobrazují validní záznamy a přes horní tlačítko lze přepnout na nevalidní. Agenda spravuje pracovní místa a skupiny, zobrazuje přijaté dotazníky s detailem, PDF stažením a umožňuje znevalidnění/obnovení.

U pozic jsou dvě akce pro ÚP: oznámení nového pracovního místa a oznámení zrušení pracovního místa. Příjemce se bere z pole `E-mail ÚP` u skupiny pracovních míst. Předměty, HTML šablony a odesílací identita jsou systémové proměnné s prefixem `rep_volna_mista_up_`; e-maily se odesílají přes `mailer_send_smtp_logged()` a zapisují do `log_emails` s kontextem `rep_volna_mista`.

Agenda `09 Zavozove obce` ma podstranky `Obce` a `Mapa`. `Obce` obsahuje import XLSX a seznam vsech obci s rucni zmenou stavu; `Mapa` zobrazuje vsechny obce nad OpenStreetMap s filtrem podle stavu a modalovou zmenou stavu.

Agenda `05 Ples` obsahuje registrace hostů z frontend formuláře, default posledního ročníku, filtrování podle ročníků, valid/nevalid přepínač, znevalidnění/obnovení a XLSX export aktuálního filtru.

Agenda `04 Brigádníci` má submenu `VO` a `MO`. Obě podstránky obsahují registrace z frontend formuláře, default posledního ročníku podle `reg_date`, filtrování podle ročníků, valid/nevalid přepínač, znevalidnění/obnovení, název napárované aktuální pobočky a XLSX export aktuálního filtru.

Agenda `07 TenisQcup` obsahuje registrace týmů z frontend formuláře, filtrování podle ročníků a XLSX export aktuálně vybraných ročníků. Počty a nabídka ročníků vždy respektují aktuální přepnutí mezi validními a nevalidními registracemi. Formulář odesílá přes společný logovaný mailer interní oznámení na jednu nebo více adres ze systémové proměnné `tenis_default-email-main` a samostatné potvrzení kontaktnímu hráči. Oba e-maily používají odesílatele `Qanto TenisQcup` a společný Qanto HTML design; obsah potvrzení je editovatelný statický text `tenisqcup_confirmation_email`.

Agenda `06 Volání` obsahuje import tří Vodafone XLSX souborů (`prehled.xlsx`, `souhrn.xlsx`, `detail.xlsx`), historický výpis podle období/e-mailu/telefonu a zákaznické odkazy přes `unify` na veřejný souhrn nebo detail. Import netruncuje tabulky; přehled se aktualizuje podle `obdobi + mobil`, souhrn a detail se deduplikují podle hashe řádku. Přehled bere sloupce `obdobi` ve formátu `MM.RRRR`, `zamestnanec`, `email`, `mobil`, `0dph`, `21dph`, `bdph`, `sdph` a importuje jen řádky s nenulovým `sdph`. Souhrn/detail používají názvy sloupců kopírované z Vodafone, např. `Období`, `Mobil`, `Produktová řada`, `Položka`, `Služba`, `Datum čas`, `Volané číslo`, `CELKEM_BEZ_DPH`, `CELKEM_S_DPH`. Výpis má stav odeslání e-mailu, filtr Odesláno ANO/NE, ruční odeslání a hromadné odeslání neodeslaných v aktuálním filtru; e-maily se seskupují podle `obdobi + email`, takže jeden e-mail obsahuje všechna telefonní čísla daného příjemce za období. Úspěšné odeslání se označí u všech zahrnutých řádků v `volani_preuctovani.email_sent_at`, chyba zůstane v `email_last_error`. Tělo e-mailu používá obsahový statický text `stat_texty.code = volani`, odesílatel se řídí systémovou proměnnou `volani_from_email` s výchozí hodnotou `volani@qanto.cz`; před použitím se hodnota převede na prostý text, ověří jako e-mail a při neplatném obsahu se použije výchozí adresa. Lokální CLI alternativa pro přehled je `scripts/import_volani_prehled_xlsx.php`.

System menu je v sekci `09`.

Aktualne jsou zalozene pouze routy a placeholder stranky. Datovy model a implementace budou doplneny podle migrace ze stare DB.

## Novinky

Stranky v `secure/inc/pages/news/` se aktualne drzi jako shared admin a prenaseji se z `qanto_cz` do QRS i QANTOPLUS.

Uživatelé newsletteru (`news_users`) jsou shared část novinek. Agenda umožňuje ruční přidání, editaci, měkké smazání, ukončení/obnovení odběru a XLSX import přes šablonu. Import deduplikuje podle e-mailu a existující e-mail aktualizuje.

Odesílání newsletteru je shared agenda přes Klerk SMTP. Stránka `secure/inc/pages/news/news_info_send.php` slouží jako náhled a potvrzení odeslání, samotná logika je v `secure/functions/fun_newsletter.php`. Odesílání musí používat `klerk_*` a `newsletter_*` klíče z INI konfigurace, přidávat hlavičku `X-CampaignID` a posílat e-maily jednotlivě, aby každý příjemce dostal unikátní odhlašovací token.

Na lokálním prostředí musí newsletter respektovat `mail_bypass_enabled`. Pokud je zapnutý, neodesílá se na skutečné odběratele, ale pouze na `newsletter_local_test_email` nebo fallback `mail_bypass_email`.

Obrázky a odkazy v HTML obsahu novinek se v DB drží relativně. Při sestavení newsletteru se `src` a `href` relativní vůči webu převádějí na absolutní URL podle `newsletter_public_base_url`. Vizuál e-mailu se řídí konfiguračními klíči `newsletter_brand_name`, `newsletter_logo_url` a `newsletter_accent_color`; logo pro e-mail preferuj ve formátu PNG/JPG kvůli kompatibilitě e-mail klientů.

## ChangeLog

ChangeLog je shared admin agenda v `secure/inc/settings/changelog.php` s helpery ve `functions/fun_changelog.php` a admin wrapperem `secure/functions/fun_changelog.php`.

E-mailem lze odeslat pouze změnu ve stavu `nasazeno`. E-mail obsahuje popis změny a při navázaném `news_id` také perex/tělo novinky. Obrázky a odkazy v HTML obsahu se při sestavení e-mailu převádějí na absolutní URL podle `changelog_public_base_url`, fallback aktuální host administrace, nebo `newsletter_public_base_url`.

Příjemci se vybírají přes multiselect skupin. Shared zdroj je vždy `users_skup` + `users`. Pokud cílový projekt obsahuje `rep_users_skup` + `rep_users`, zobrazí se i projektové skupiny. Do rozesílky vstupují pouze aktivní/validní uživatelé s platným e-mailem; duplicitní e-maily napříč skupinami se deduplikují.

Odesílání používá `mailer_send_smtp_logged()` a zapisuje jednotlivé e-maily do `log_emails` s kontextem `changelog`, šablonou `changelog_release`, `related_table = changelog` a `related_id = ID změny`.

## Vicejazycne Editace

Novinky, fotogalerie, staticke texty a staticke vyrazy jsou shared admin agendy s vicejazycnymi poli. Preklad CZ -> EN pouziva DeepL konfiguraci z INI (`deepl_*`).

Vicejazycne zaznamy se edituji v jednom detailu pres zalozky `CZ` / `EN`; ve vypisech nepouzivat samostatne EN editacni akce. Preklad CZ -> EN pouziva obecny AJAX `secure/functions/ajax/admin_translate.php` a JS modul `assets/js/sec/lang_tabs_translate.js`.

Preklad se dela z aktualnich hodnot CZ poli ve formulari, ne z DB, aby bylo mozne prelozit i rozepsanou neulozenou upravu. To plati pro novinky, galerie, staticke texty i staticke vyrazy.

Automatický překlad při uložení nepoužívá slepé hledání všech párů `*_cz` / `*_en`. Autoritativní mapa překládaných polí je ve `secure/functions/fun_admin_translate_map.php`; project agendy doplňují vlastní mapu přes `rep_admin_translate_field_maps()` v `secure/functions/fun_rep_admin_translate_map.php`. Výchozí chování je automaticky překládat CZ -> EN, ruční výjimka je přes příznak `auto_translate_en = 0` a checkbox `automaticky nepřekládat do EN`. Backendový save hook je v `secure/functions/fun_admin_translate.php` a po normálním uložení aktualizuje jen explicitně mapovaná EN pole.

## Staticke Texty A Vyrazy

Staticke texty a staticke vyrazy jsou shared admin agenda.

## Napište Nám

Napište nám je shared admin agenda v `secure/inc/pages/napiste_nam/`.

Administrace spravuje:

- historický výpis přijatých zpráv,
- kategorie kontaktního formuláře,
- hlavní e-mail příjemce a e-mail kopie pro každou kategorii,
- viditelnost kategorie na webu a validitu záznamu.

Odesílání formuláře na veřejném frontendu se zatím neřeší; frontend později použije validní a viditelné kategorie jako seznam dotazů a podle zvolené kategorie vybere příjemce.

## Systemove Promenne

Typ systemove promenne je volne textove pole. Nepouzivat pevny vycet hodnot, protoze typy se mohou lisit podle projektu; prazdna hodnota se uklada jako `main`.

Vychozi limit vypisu systemovych promennych je 500 zaznamu. Rucni `limit=0` zustava zachovany pro nacteni vsech zaznamu.

Podmenu `UI texty webu` spravuje tabulku `ui_texty`. Web pri volani `ui_text()` nacte validni DB hodnoty jednou za request do pametove cache; pokud klic v DB chybi nebo je nevalidni, pouzije se fallback z `functions/lang.php` a potom fallback predany primo ve volani `ui_text()`. Synchronizace v administraci vklada pouze chybejici klice z `functions/lang.php` a jednoduchych pouziti `ui_text('klic', 'fallback')`; existujici rucne upravene DB hodnoty neprepisuje.

## Cron

`secure/inc/settings/cron_vypis.php` je shared UI. Samotny seznam cron uloh zustava projektovy v `secure/functions/fun_rep_cron.php`.

Projektovy cron helper musi poskytovat:

- `app_cron_http_base_url(): ?string`
- `app_cron_jobs(): array`

Polozky z `app_cron_jobs()` mohou pouzivat `role` hodnoty `hosting`, `manual_child` a `legacy`. Provozni crony nastavene na hostingu maji mit `role = hosting`.

V cistem `qanto_cz` nemusi `secure/functions/fun_rep_cron.php` existovat. Shared UI v takovem pripade zobrazi prazdny vypis bez fatal chyby.

## DB Migrace

Pohled `Systémové proměnné > DB migrace` umí:

- porovnat SQL soubory v `secure/sql/` proti `schema_migrations`,
- spustit čekající migraci po opsání aktuálního `dbname`,
- vytvořit a mazat DB backupy,
- smazat evidenci migrace a odpovídající SQL soubor.

Smazání migrace není rollback databázového schématu.

# MySQL 8.4 a datumy newsletteru

- Aktivní odběr v `news_users.datum_do` používá SQL `NULL`, nikoli legacy hodnotu `0000-00-00`.
- Shared helper kvůli zápisům datumů nemění `sql_mode` aktuální DB relace.
- Historická data a nullable schéma opravuje jednorázová migrace `secure/sql/20260901_01_mysql84_shared_zero_date_cleanup.sql`; runtime pracuje pouze s platnými datumy nebo `NULL`.
