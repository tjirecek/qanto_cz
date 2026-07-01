# Vývojový Kontext

`qanto_cz` je hlavní shared admin projekt.

Primární směrování změn je v `docs/ai-agent-routing.md`.

Před změnou vždy určete, zda jde o:

- shared administraci: patří sem,
- budoucí project administraci nového qanto.cz: řešit jen podle `docs/qanto-cz-admin-plan.md`,
- projektovou administraci QRS/QANTOPLUS: patří do cílových projektů,
- veřejný frontend: nepatří sem.

Pravidla:

- Nepřidávat projektové `rep_*` moduly.
- Nepřidávat `mm_project.php` ani `pages_include_rep.php`.
- Výjimka je jen výslovně zadaná budoucí project vrstva qanto.cz podle `docs/qanto-cz-admin-plan.md`.
- DB změny řešit migrací v `secure/sql/`.
- PHP změny ověřit přes `php -l`.
- Dokumentaci aktualizovat ve stejné změně.
