# Qanto Shared Admin Dokumentace

Tento projekt je hlavní zdroj sdílené administrace pro Qanto projekty.

## Dokumenty

- `ai-agent-routing.md` - hlavní směrování změn: shared baseline, budoucí project vrstva qanto.cz, porovnávání s QRS/QANTOPLUS.
- `projekt-prehled.md` - role projektu a hranice shared adminu.
- `administrace.md` - struktura sdílené administrace.
- `databaze.md` - shared DB tabulky a migrace.
- `prostredi-a-nasazeni.md` - lokální doména, konfigurace a nasazení.
- `architektura.md` - technické rozdělení souborů.
- `shared-admin-porovnani.md` - skript a pravidla porovnávání shared adminu proti QRS a QANTOPLUS.
- `qanto-cz-admin-plan.md` - pracovní plán nové administrace a migrací pro web qanto.cz.
- `qrs-qantoplus-project-tasks.md` - pracovní backlog projektových úkolů pro QRS a QANTOPLUS mimo shared baseline.
- `vyvojovy-kontext.md` - rychlý kontext pro AI agenty.
- `repozitar-a-verzovani.md` - práce s git repozitářem.

## Hlavní Pravidla

- `qanto_cz` je autoritativní zdroj shared/system administrace.
- Aktuální stav projektu je čistý shared admin baseline.
- Shared změny vznikají nejdřív zde a potom se portují do QRS/QANTOPLUS.
- Projektové části QRS/QANTOPLUS (`rep_*`, `sec_rep_*`, project routy/menu/importy/cron) se sem nepřenášejí.
- Budoucí project vrstva nového webu qanto.cz se řeší jen podle `qanto-cz-admin-plan.md` a musí zůstat oddělená od shared baseline.
