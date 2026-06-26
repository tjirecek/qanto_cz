# Qanto.cz Admin Plan

Pracovni dokument pro novou administraci projektu `qanto_cz`.

Projekt `qanto_cz` se bude rozsirovat z cisteho shared admin baseline na administraci noveho webu `qanto.cz`, ktery nahrazuje puvodni projekt `old-qanto_cz`.

## Cile

- vytvorit administraci noveho webu `qanto.cz`,
- zachovat shared admin jako zaklad a jasne oddelit nove projektove moduly,
- migrovat potrebna data ze stare databaze `xqanto_cz_old` do nove databaze `xqanto_cz_main`,
- pripravovat zmeny pres SQL migrace v `secure/sql/`,
- neprebirat historicky balast bez overeni, ze je potreba pro novy web.

## Databaze

- Nova databaze: `xqanto_cz_main`
- Stara databaze: `xqanto_cz_old`
- Produkcni databaze se neupravuje bez vyslovneho potvrzeni.

## Pracovni Pravidla

- Shared admin zustava autoritativni zaklad pro QRS/QANTOPLUS.
- Projektove moduly noveho `qanto.cz` musi byt oznacene jako projektove a nemaji se automaticky portovat do QRS/QANTOPLUS.
- DB zmeny pro novy web se pripravuji jako idempotentni migrace v `secure/sql/`.
- Pri migraci dat nejdrive popsat zdrojove tabulky, cilove tabulky a transformace.
- Pred vetsimi zasahy kontrolovat `git status` a neprepisovat nesouvisejici lokalni zmeny.

## Rozsah Administrace

Sem budeme doplnovat moduly, ktere nova administrace potrebuje.

| Oblast | Stav | Poznamky |
| --- | --- | --- |
| Shared admin baseline | hotovo | Zakladni administrace, uzivatele, menu, settings, migrace. |
| Verejny web qanto.cz | navrhnout | Nahrada puvodniho `old-qanto_cz`. |
| Migrace dat | navrhnout | Zdroj `xqanto_cz_old`, cil `xqanto_cz_main`. |

## Pozadavky K Diktovani

### Admin Moduly

- TODO

### Frontend / Obsah Webu

- TODO

### Datove Entity

- TODO

### Migrace Ze Stare DB

| Zdroj `xqanto_cz_old` | Cil `xqanto_cz_main` | Stav | Poznamka |
| --- | --- | --- | --- |
| TODO | TODO | navrhnout |  |

### SQL Migrace

- TODO

### Otevrene Otazky

- TODO

## Rozhodnuti

- `functions/settings.php` je projektovy frontend routing a neni shared admin soubor.
- QRS muze pouzivat `/cz/main`; verejne weby mohou kanonicky pouzivat `/cz`.
