# Frontend

Tento projekt nemá veřejný frontend. Kořenový `index.php` přesměruje do `/secure/`.

Veřejné frontend moduly patří do konkrétních projektů, ne do shared admin baseline.

`functions/settings.php` je projektový frontend routing. Rozdíl `/cz` proti `/cz/main` je povolený projektový rozdíl a neporovnává se jako shared administrace.
