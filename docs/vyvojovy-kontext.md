# Vývojový Kontext

`qanto_cz` je hlavní shared admin projekt.

Před změnou vždy určete, zda jde o:

- shared administraci: patří sem,
- projektovou administraci: patří do QRS/QANTOPLUS,
- veřejný frontend: nepatří sem.

Pravidla:

- Nepřidávat projektové `rep_*` moduly.
- Nepřidávat `mm_project.php` ani `pages_include_rep.php`.
- DB změny řešit migrací v `secure/sql/`.
- PHP změny ověřit přes `php -l`.
- Dokumentaci aktualizovat ve stejné změně.
