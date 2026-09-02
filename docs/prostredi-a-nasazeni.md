# Prostředí A Nasazení

## Lokál

- Adresář: `/Users/tjirecek/www_dev/qanto_cz`
- Doména: `https://qanto.test`
- Lokální web server: Laravel Herd / Nginx
- Lokální PHP pro web: Herd PHP 8.4
- Lokální MySQL: Docker/Colima kontejner `qanto-mysql84`, image `mysql:8.4`, port `3306`
- Lokální DB: `xqanto_cz_main`
- Lokální zdrojová DB pro migrace: `xqanto_cz_old`
- Konfigurace: `ini/config_local.ini`
- Lokální phpMyAdmin: `https://phpmyadmin.test` (`~/Herd/phpmyadmin`)

Sekundární projekty jsou lokálně linknuté v Herdu:

- `https://qrs-qanto.test` -> `/Users/tjirecek/www_dev/qrs-qanto_cz`
- `https://qantoplus.test` -> `/Users/tjirecek/www_dev/qantoplus_cz`

MAMP Apache/Nginx nepoužívat pro tyto projekty současně s Herdem na portech `80/443`.
Lokální databáze již neběží přes MAMP; MAMP zůstává jen historický zdroj původního exportu.
V lokálních INI konfiguracích používat DB host `127.0.0.1` a port `3306`.

### Lokální DB Služba

Lokální MySQL 8.4 běží přes Colima/Docker. Produkční Webglobe prostředí zatím používá MySQL 5.7,
proto je před produkčním nasazením nutné potvrdit cílovou verzi a ověřit kompatibilitu migrací.

Základní příkazy:

```bash
colima status
colima start
docker start qanto-mysql84
docker ps --filter name=qanto-mysql84
```

Kontejner `qanto-mysql84` má restart policy `unless-stopped`. Pokud po restartu stroje databáze
neodpovídá, spustit `colima start`; kontejner se následně obnoví automaticky, případně ručně přes
`docker start qanto-mysql84`.

## Produkce

Produkční konfigurace je `ini/config.ini`. Produkční DB ani soubory se nemění bez výslovného zadání.

## Nasazení

Tento projekt je zdroj shared administrace. Změny se po ověření portují do cílových projektů QRS/QANTOPLUS.

Pro projektovou administraci nového `qanto.cz` se nasazují i projektové admin assety. Soubor
`assets/css/sec_rep_secure.css` není generovaný ani ignorovaný a musí být součástí produkčního releasu,
protože obsahuje projektové třídy štítků novinek (`text-bg-qanto-*`).
