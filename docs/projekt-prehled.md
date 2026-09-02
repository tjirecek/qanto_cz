# Přehled Projektu

`qanto_cz` je autoritativní projekt pro sdílenou administraci.

Cíl projektu:

- držet čistý shared admin baseline bez projektové business logiky,
- stabilizovat společné moduly před portováním do QRS a QANTOPLUS,
- sloužit jako referenční struktura pro AI agenty i ruční vývoj.

Projekt není veřejný web. Kořenový `index.php` pouze přesměruje do `/secure/`.

Lokální doména je `https://qanto.test` přes Laravel Herd. Lokální databáze běží přes Docker/Colima jako MySQL 8.4 kontejner `qanto-mysql84` na `127.0.0.1:3306`.

## Budoucí Project Vrstva

Existuje pracovní plán `qanto-cz-admin-plan.md` pro budoucí administraci nového webu `qanto.cz`.

Dokud uživatel výslovně nezadá tuto project vrstvu, projekt zůstává čistý shared admin baseline. Jakékoliv budoucí project moduly musí být jasně oddělené od shared administrace a nesmí se automaticky portovat do QRS/QANTOPLUS.
