# Qanto.cz Predprodukcni Checklist

Ukoly, ktere musi byt doresene pred produkcni migraci a nasazenim noveho `qanto.cz`.

## Infrastruktura A Hosting

- Pred finalni produkcni migraci domluvit s Webglobe moznost a postup prechodu produkcni DB z MySQL 5.7 na novejsi MySQL nebo MariaDB.
- Overit konkretni dostupnou verzi, hostname/server, dopad na phpMyAdmin/WebSSH a zda jde o migraci existujici DB nebo zalozeni nove DB s importem.
- Nepocitat s tim, ze prepnuti verze DB je dostupne jako bezny uzivatelsky prepinac v administraci hostingu.
- Dokud produkce zustava na MySQL 5.7, lokalni vyvoj drzet kompatibilni s MySQL 5.7.

## Novinky

- Pripravit sadu fallback obrazku pro novinky z marketingovych materialu Qanto.
- Minimalni rozsah: alespon 20 obrazku.
- Temata: cirkus, DLC a dalsi pouzitelne marketingove motivy.
- Ucel: pokud novinka nema vlastni obrazek, frontend/admin pouzije nahodny fallback obrazek z teto sady.
- Pred nasazenim rozhodnout cilovou cestu ulozeni, pravdepodobne `/media/news/defaults/` nebo obdobna projektova media slozka.
- Obrazky pripravit ve webovych rozmerech vhodnych pro karty novinek a sdileni, vcetne varianty pro Open Graph.
- Implementovat vyber nahodneho fallback obrazku az po priprave sady a finalnim rozhodnuti cesty.
- Overit, ze produkcni release obsahuje `assets/css/sec_rep_secure.css`; jsou v nem projektove tridy stitku novinek `text-bg-qanto-markety`, `text-bg-qanto-velkoobchod`, `text-bg-qanto` a `text-bg-qantoplus`.
- Pripravit frontend redirect ze stare cesty novinek `/cz/index/news/{url}` na novou kanonickou cestu detailu novinky. Migrace zachovava hodnoty `news.url_cz` a `news.url_en`.
- Projit report migrace novinek `migrations/old-to-main/reports/004_news_migrate_*.md` a rozhodnout, ktere obrazky/dokumenty odkazovane v HTML obsahu se zkopiruji do nove struktury `media/*`.
- Puvodni `_images/_library/*` je lokalne stazene v `media/library/*`; puvodni `download/*` je lokalne stazene v `media/download/*`.
- Puvodni `_images/_news/*` zkopirovat do `media/library/x_news/*`; puvodni `_images/_news/small/*` zkopirovat do `media/library/x_news/small/*`.
- Pri prepisu obsahu mapovat odkazy na `_images/_library/...` do `/media/library/...`, `_images/_news/...` do `/media/library/x_news/...` a odkazy na `download/...` do `/media/download/...`.
- Po zkopirovani souboru do `media/*` pripravit prepis nebo redirect starych internich odkazu v obsahu novinek/statickych textu, aby nevedly na stare adresare projektu.
- Pro AI preklad CZ -> EN nastavit v produkcnim INI `deepl_auth_key`; volitelne `deepl_api_url` a `deepl_target_lang`.
- Pred produkci overit, ze PHP ma dostupne rozsireni cURL pro volani DeepL API.
- Obecne AI generovani nebo marketingove upravy textu resit pozdeji jako samostatnou funkci; aktualni implementace je pouze preklad CZ -> EN.
- Pred produkci overit Klerk SMTP hodnoty v produkcnim INI (`klerk_*`) vcetne `klerk_campaign_id`.
- Doplnit verejnou odhlasovaci stranku podle `newsletter_unsubscribe_url`; odkaz z e-mailu nese `uid` a podepsany token, uzivatel tedy nemusi rucne zadavat e-mail.
- Pri produkcnim testu newsletteru nejdriv odeslat testovaci skrytou novinku na omezeny dataset nebo docasnou testovaci DB kopii, ne rovnou na plny seznam odberatelu.

## Poznamky

- Fallback obrazky jsou obsah/projektova data, ne shared admin kod.
- Produkcni migrace novinek musi pocitat s tim, ze historicke novinky nemusi mit vlastni `news_ico`.
- Projektove barvy stitku jsou soucast projektu `qanto_cz`, ne shared admin baseline pro QRS/QANTOPLUS.
- Ikony novinek ze stare DB (`news_ico`) se nemigruji; budou nahrazeny novou fallback logikou obrazku.
