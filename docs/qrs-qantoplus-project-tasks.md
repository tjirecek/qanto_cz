# QRS/QANTOPLUS Project Tasks

Pracovni backlog pro projektove casti cilovych projektu:

- QRS: `/Users/tjirecek/www_dev/qrs-qanto_cz`
- QANTOPLUS: `/Users/tjirecek/www_dev/qantoplus_cz`

Tento dokument neni seznam shared admin zmen. Projektove zmeny QRS/QANTOPLUS se neprenaseji do `qanto_cz`, pokud nejsou vyslovne vyhodnocene jako obecny shared admin.

## Stav Ukolu

| ID | Projekt | Oblast | Stav | Priorita | Popis |
|---|---|---|---|---|---|
| P-001 | QRS + QANTOPLUS | project admin JS | pripraveno | vysoka | Projit projektove stranky a inline JS vyclenit do samostatnych JS souboru. |
| P-002 | QRS + QANTOPLUS | project admin CSS | prubezne | vysoka | Ne-e-mailove inline styly presunuty; pri dalsich project upravach drzet `assets/css/sec_rep_secure.css`. |
| P-003 | QRS + QANTOPLUS | project CSS audit | pripraveno | stredni | Pravidelne projit ostatni projektove casti a odstranit nove inline `<style>` / `style=""` mimo e-mail sablony. |
| P-004 | QRS | Dochazka / single picker | hotovo | vysoka | Dne 2026-08-29 prenesen shared `admin single picker` z `qanto_cz` a nahrazeny jim tri single pickery Uživatel, Manažer a Vedoucí; multiple picker stredisek zustal projektovy. |

## P-004 - Port Single Pickeru Do QRS Dochazky

### Hranice Portu

Autoritativni implementace zustava v `/Users/tjirecek/www_dev/qanto_cz`. Port do `/Users/tjirecek/www_dev/qrs-qanto_cz` byl proveden 2026-08-29; nasledujici seznam zustava zavaznym zaznamem rozsahu a kontrol portu.

Shared soubory k prenosu nebo rucnimu merge do `/Users/tjirecek/www_dev/qrs-qanto_cz`:

1. novy soubor `assets/js/sec/admin_single_picker.js` zkopirovat byte-identical,
2. do `assets/js/sec/admin.js` prenest selector registraci `.js-admin-single-picker` -> `admin_single_picker.js`; pokud je v QRS shodne nasazeny stary modul `oz_pobocka_modal.js`, odstranit jeho registraci i soubor az po prevodu pole pobočky; neprepisovat QRS project mapovani,
3. do `assets/css/secure.css` prenest cely blok `Shared single-record picker`, ostatni rozdily CSS neprepisovat,
4. rucne prenest aktivacni tridu a data atributy do shodnych shared formularu `secure/inc/pages/news/news_add.php`, `secure/inc/pages/news/news_edit.php`, `secure/inc/pages/galerie/galerie_vypis.php`, `secure/inc/pages/kontakty/kontakty_lide.php`, `secure/inc/pages/kontakty/obchodni_zastupci.php`, `secure/inc/settings/users_add.php`, `secure/inc/settings/users_edit.php` a `secure/inc/settings/changelog.php`; u obchodniho zastupce nahradit hidden input a vlastni modal zdrojovym selectem se stejnym `name="pobocka_id"`; vzdy zachovat backendovou logiku,
5. zavazny standard z `docs/administrace.md` propsat do QRS `docs/shared-admin-baseline.md`; soubor `docs/administrace.md` se do QRS nekopiruje, protoze tam neexistuje.

Project soubory QRS pro nahrazeni dochazkovych pickeru:

- `secure/inc/pages/rep_attendance/rep_attendance_permissions.php`,
- `assets/js/sec_rep_attendance_permissions.js`,
- `assets/css/sec_rep_secure.css`,
- `docs/attendance.md`.

Project nasazeni z qanto.cz (`secure/inc/pages/rep_qanto/*`) se do QRS nekopiruje; slouzi pouze jako lokalni pouziti shared komponenty v autoritativnim projektu.

### Prevod Poli Bez Zmeny Backend Logiky

V `rep_attendance_permissions.php` zachovat stejne nazvy POST poli a scalar hodnoty:

- `user_id`: `<select id="attendanceMemberUser" name="user_id" class="form-select js-admin-single-picker" required>`; options vzniknou z `$users`, nazev je `name`, `data-picker-subtext` je `login`, ikona `bi bi-person`.
- `manager_user_id`: `<select id="attendanceManagerUser" name="manager_user_id" class="form-select js-admin-single-picker" required>`; options vzniknou z `$memberUsers`, nazev je `name`, doplnek `login`, ikona `bi bi-person-gear`.
- `owner_user_id`: `<select id="attendanceOwnerUser" name="owner_user_id" class="form-select js-admin-single-picker" required>`; options vzniknou z `$ownerUsers`, nazev je `name`, doplnek spoji `login` a `centers`, ikona `bi bi-person-check`. Hledani tak pokryje jmeno, login i stredisko.

Kazdy select dostane prazdnou `<option value="">`, `data-picker-title`, `data-picker-description`, `data-picker-search-placeholder` a `data-picker-empty-label`. Backendove volani `rep_attendance_admin_member_save()` a `rep_attendance_admin_access_save()` se nemeni.

Z PHP odstranit puvodni hidden inputy, tri trigger buttony a tri cele modalove bloky:

- `#attendanceUserPickerModal`,
- `#attendanceManagerPickerModal`,
- `#attendanceOwnerPickerModal`.

`#attendanceCenterPickerModal` a generovani `center_codes[]` ponechat, protoze jde o multiple vyber mimo rozsah shared komponenty.

### Uprava Project JS A CSS

V `assets/js/sec_rep_attendance_permissions.js` odstranit vlastni single-picker implementaci pro uzivatele, manazera a vedouciho: jejich modal reference, vyhledavani, options mapy a `initAccessPicker()`. Ponechat roli, strediska, editaci clena a kontrolu `manažer != vedoucí`.

Programove predvybrani uzivatele pri editaci ma nastavit `attendanceMemberUser.value` a vyvolat bublajici `change`. Kontrola rozdilnosti manazera a vedouciho ma zustat pred odeslanim a chybu zobrazit pres explicitni feedback u `owner_user_id`; nativni `required` zajisti shared picker.

V `assets/css/sec_rep_secure.css` odstranit pouze tridy pouzivane zrusenymi single trigger buttony a tremi modaly. Styly sdilene s multiple pickerem stredisek (`rep-attendance-user-list`, `rep-attendance-user-option`, `rep-attendance-center-*`) ponechat. Shared `admin-single-picker-*` styly nesmi byt duplikovane v project CSS.

### Poradi A Overeni Portu

1. Pred praci ulozit `git status --short` v QRS; Dochazka ma rozpracovane lokalni zmeny, proto nepouzivat plosne kopirovani project souboru.
2. Spustit `scripts/compare_shared_admin.sh /Users/tjirecek/www_dev/qrs-qanto_cz` z `qanto_cz` a zkontrolovat shared rozdily.
3. Prenest tri shared asset zmeny a overit loader; `secure/index.php` neportovat.
4. Rucne prepsat tri pole v project PHP a zjednodusit project JS/CSS podle vyse uvedene mapy.
5. Aktualizovat QRS `docs/shared-admin-baseline.md` a `docs/attendance.md`.
6. Spustit `php -l secure/inc/pages/rep_attendance/rep_attendance_permissions.php`, `node --check assets/js/sec/admin_single_picker.js`, `node --check assets/js/sec/admin.js`, `node --check assets/js/sec_rep_attendance_permissions.js` a CSS lint pro `assets/css/secure.css` i `assets/css/sec_rep_secure.css`.
7. V prohlizeci overit vsechny tri single selecty, hledani bez diakritiky, Enter/Escape, editacni predvyber, required chyby, kontrolu rozdilnych osob, jeden modal pod `<body>` a z-index nad backdropem. Znovu overit multiple vyber stredisek.

## P-001 - Vycleneni Projektoveho JS

### Cil

Odstranit inline JavaScript z projektovych casti QRS/QANTOPLUS a presunout ho do samostatnych projektovych JS modulu. Stranky maji obsahovat HTML/PHP vystup a datove atributy, ne vlastni `<script>` bloky.

### Rozsah

Prochazet pouze projektove casti cilovych projektu:

- `secure/inc/pages/rep_*`
- `secure/functions/fun_rep_*`
- `secure/functions/ajax/rep_*`
- `secure/inc/menu/mm_project.php`
- projektove casti `secure/index.php`, pouze pokud jde o nacitani projektoveho JS loaderu
- existujici projektove JS `assets/js/rep_*`
- existujici projektove admin CSS/JS s prefixem `sec_rep_*`

Nemenit shared admin soubory, pokud neni zmena samostatne potvrzena jako shared:

- `secure/inc/pages/news/*`
- `secure/inc/pages/stattexty/*`
- `secure/inc/pages/galerie/*`
- `secure/inc/pages/kontakty/*`
- `secure/inc/pages/napiste_nam/*`
- `secure/inc/settings/*`
- `assets/js/sec/*`
- `assets/js/sec_*`

### Doporucena Struktura

Shared admin pouziva loader `assets/js/sec/admin.js`. Projektove casti maji mit oddeleny loader a moduly s projektovym prefixem, napriklad:

```text
assets/js/sec_rep_project/admin.js
assets/js/sec_rep_project/objednavky.js
assets/js/sec_rep_project/markets.js
assets/js/sec_rep_project/ceniky.js
```

Duvod:

- `assets/js/sec/*` zustava shared admin a porovnava se proti `qanto_cz`.
- `assets/js/sec_rep_project/*` je projektova admin vrstva a nesmi byt importovana do `qanto_cz` jako shared.
- `assets/js/rep_*` muze zustat pro verejny/frontend project JS, ale admin JS patri radeji do `assets/js/sec_rep_project/*`.

### Nacitani

V QRS/QANTOPLUS admin shellu nacitat projektovy loader az po shared loaderu:

```php
<script src="<?= asset_version(BASE_URL . 'assets/js/sec/admin.js'); ?>"></script>
<script src="<?= asset_version(BASE_URL . 'assets/js/sec_rep_project/admin.js'); ?>"></script>
```

Projektovy loader ma pouzivat stejne routovaci atributy z `<body>` jako shared loader:

- `data-admin-section`
- `data-admin-page`
- `data-admin-sec-page`
- `data-admin-js-version`

Pokud bude potreba vlastni cache verze pro project JS, doplnit samostatny atribut napriklad `data-admin-project-js-version`.

### Vyhledani Inline JS

Spoustet vzdy v cilovem projektu.

QRS:

```bash
cd /Users/tjirecek/www_dev/qrs-qanto_cz
rg -n "<script|</script>|\bon(click|change|submit|keyup|keydown|input|load)\s*=|javascript:" secure/inc/pages/rep_* secure/functions/fun_rep_* secure/functions/ajax/rep_* secure/inc/menu/mm_project.php -g '*.php'
```

QANTOPLUS:

```bash
cd /Users/tjirecek/www_dev/qantoplus_cz
rg -n "<script|</script>|\bon(click|change|submit|keyup|keydown|input|load)\s*=|javascript:" secure/inc/pages/rep_* secure/functions/fun_rep_* secure/functions/ajax/rep_* secure/inc/menu/mm_project.php -g '*.php'
```

Pokud nektery glob neexistuje, spustit prikaz po castech podle realne existujicich adresaru.

### Pravidla Refaktoru

1. Pred zmenou spustit `git status --short` v cilovem projektu.
2. Nemichat QRS a QANTOPLUS do jedne neprehledne upravy; idealne delat stejnou kategorii zmen v obou projektech postupne.
3. Inline `<script>` blok presunout do konkretniho modulu v `assets/js/sec_rep_project/*`.
4. Inline handlery typu `onclick="..."` nahradit datovymi atributy a event listenerem v JS modulu.
5. PHP ma generovat data pres `data-*` atributy nebo JSON payload v bezpecnem HTML kontextu.
6. JS modul nesmi zaviset na globalnich promennych vytvorenych inline skriptem ve strance.
7. Pokud je stejna projektova funkcionalita v QRS i QANTOPLUS, sjednotit strukturu souboru, ale neprepisovat business rozdily.
8. Pokud se pri praci ukaze, ze kod je obecny shared admin, zastavit a rozhodnout, zda patri nejdrive do `qanto_cz`.

### Overeni

Po uprave spustit v cilovem projektu:

```bash
php -l path/to/changed-file.php
node --check assets/js/sec_rep_project/admin.js
node --check assets/js/sec_rep_project/<module>.js
rg -n "<script|</script>|\bon(click|change|submit|keyup|keydown|input|load)\s*=|javascript:" secure/inc/pages/rep_* -g '*.php'
```

Uprava je hotova, kdyz:

- projektova stranka funguje stejne jako pred refaktorem,
- v refaktorovanych souborech nezustaly inline `<script>` bloky,
- project JS je mimo shared `assets/js/sec/*`,
- `qanto_cz` zustava beze zmen v projektove casti QRS/QANTOPLUS.

### Poznamky

- Shared admin loader `assets/js/sec/admin.js` nemenit kvuli projektovym modulům, pokud nejde o obecne shared pravidlo.
- Projektovy loader muze byt pozdeji rozsiren o mapovani podle route stejne jako shared loader.
- Pri vetsim poctu modulu nenacitat vsechny projektove JS soubory naraz; loader ma pripojit jen moduly potrebne pro aktualni stranku.

## P-002 - Vycleneni Projektovych Stylu

### Cil

Odstranit inline CSS z projektovych casti QRS/QANTOPLUS. Projektove admin stranky nemaji obsahovat `<style>` bloky ani `style=""` atributy.

### Rozsah

Prochazet pouze projektove casti cilovych projektu:

- `secure/inc/pages/rep_*`
- `secure/functions/fun_rep_*`
- `secure/functions/ajax/rep_*`
- `secure/inc/dashboard/*`, pokud jde o projektovy dashboard
- projektove casti `secure/index.php`, pouze pokud jde o projektove logo/layout tridy
- `assets/css/sec_rep_secure.css`

Nemenit shared CSS bez samostatneho rozhodnuti:

- `assets/css/secure.css`
- shared admin PHP soubory bez `rep_*`

### Pravidla Refaktoru

1. Shared nebo obecny admin styl patri do `assets/css/secure.css` a zmena se dela nejdrive v `qanto_cz`.
2. Projektovy admin styl patri do `assets/css/sec_rep_secure.css` v danem cilovem projektu.
3. Pred vlastnim CSS nejdrive zkus Bootstrap komponenty a utility tridy (`d-flex`, `gap-*`, `w-*`, `text-*`, `border-*`, `overflow-*`, `table-responsive`, atd.).
4. Vlastni tridu pridej jen pokud Bootstrap nestaci nebo by vyrazne zhorsil citelnost HTML.
5. Pred pridanim tridy ověř kolizi nazvu pres `rg \"nazev-tridy\" assets/css secure/inc secure/functions`.
6. Projektove tridy prefixuj podle oblasti, napr. `rep-orders-*`, `rep-dashboard-*`, `rep-market-*`.
7. Inline styly v HTML e-mailovych sablonach jsou vyjimka, protoze e-mail klienti vyzaduji inline CSS.
8. Po uprave CSS spust `stylelint` nebo projektovy CSS lint script, pokud je v projektu dostupny.

### Vyhledani Inline CSS

QRS:

```bash
cd /Users/tjirecek/www_dev/qrs-qanto_cz
rg -n \"<style|</style>|\\sstyle=\\\"|\\sstyle='\" secure/inc/pages/rep_* secure/functions/fun_rep_* secure/functions/ajax/rep_* secure/inc/dashboard -g '*.php'
```

QANTOPLUS:

```bash
cd /Users/tjirecek/www_dev/qantoplus_cz
rg -n \"<style|</style>|\\sstyle=\\\"|\\sstyle='\" secure/inc/pages/rep_* secure/functions/fun_rep_* secure/functions/ajax/rep_* secure/inc/dashboard -g '*.php'
```

## P-003 - Prubezny Audit Inline CSS V Ostatnich Projektech

### Cil

Zachytit nove inline styly v QRS/QANTOPLUS po dalsim vyvoji projektovych modulu. Audit se dela hromadne, ne po kazdem drobnem requestu, ale pred vetsim predanim nebo deployem.

### Postup

1. Spustit scan v obou cilovych projektech.
2. Rozdelit nalezy na e-mail HTML sablony/notifikace a realne admin UI.
3. E-mail HTML ponechat inline, pokud jde o kompatibilitu e-mail klientu.
4. Admin UI nejdrive prepsat pres Bootstrap utility; pokud nestaci, presunout CSS do `assets/css/sec_rep_secure.css`.
5. Po uprave spustit PHP lint pro zmenene soubory a `stylelint` pro CSS.

### Audit Prikazy

```bash
cd /Users/tjirecek/www_dev/qrs-qanto_cz
rg -n "<style|</style>|\\sstyle=\\\"|\\sstyle='" secure -g '*.php'

cd /Users/tjirecek/www_dev/qantoplus_cz
rg -n "<style|</style>|\\sstyle=\\\"|\\sstyle='" secure -g '*.php'
```

Aktualne tolerovane zbyvajici nalezy jsou HTML e-mailove sablony/notifikace. Vse ostatni brat jako kandidat na refaktor do Bootstrap trid nebo `sec_rep_secure.css`.
