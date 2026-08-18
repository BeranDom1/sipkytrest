# Šipky Třešť – technický kontext projektu

> Stav analýzy: 18. 8. 2026. Dokument popisuje současný stav repozitáře a lokální databáze. Není návrhem nové architektury. Při změně architektury, databázového schématu nebo hlavních datových toků musí být aktualizován.

## 1. Účel a rozsah

Repozitář obsahuje dvě související části:

1. veřejný web klubu v kořeni projektu,
2. ligovou aplikaci v `liga-app/` pro soutěže, statistiky, rezervace, administraci a Prezidentský pohár.

Historická paralelní kopie `liga-app-clean/` byla 18. 8. 2026 z projektu odstraněna jako nepoužívaná. Zdrojem pravdy je pouze `liga-app/`.

Projekt je napsán převážně v PHP, HTML, CSS a čistém JavaScriptu. Nepoužívá Composer, Node.js ani aplikační framework. Data ukládá do MariaDB/MySQL.

## 2. Provozní prostředí a nasazení

### Produkce

- Zdrojový repozitář: GitHub `BeranDom1/sipkytrest`.
- Větev `main` se nasazuje přes `.github/workflows/deploy.yml` pomocí FTP na WEDOS.
- Workflow kopíruje prakticky celý repozitář; vylučuje zejména `.git`, `.github`, `node_modules` a `.env`.
- Produkční web očekává PHP a databázi MariaDB/MySQL.

### Lokální prostředí

- XAMPP: `C:\xampp`
- PHP: 8.2.x
- MariaDB: 10.4.x
- lokální URL: `http://127.0.0.1:8080/`
- lokální databáze: `d377108_liga`
- lokální přihlašovací údaje jsou v ignorovaném souboru `config/db.local.php`
- produkční přihlašovací údaje jsou v ignorovaném souboru `config/db.production.php`, který se spravuje ručně na hostingu

Soubor `liga-app/db.php` nejprve zkouší načíst lokální konfiguraci a následně produkční konfiguraci. Ani jeden soukromý konfigurační soubor se nesmí commitovat nebo nasazovat z GitHubu. Bez platné konfigurace vrátí aplikace obecnou chybu bez vypsání přihlašovacích údajů či stack trace. Bezpečná šablona je v `config/db.production.example.php`.

## 3. Struktura repozitáře

### Kořen projektu

| Cesta | Význam |
|---|---|
| `index.html` | Hlavní veřejná stránka klubu. |
| `style.css` | Hlavní styl veřejného webu. |
| `script.js` | JavaScript veřejného webu. |
| `hraci.php` | Dynamický veřejný seznam hráčů z tabulky `seznam_hracu_web`. |
| `hraci.html` | Starší statická varianta seznamu hráčů; pravděpodobně historická. |
| `img/` | Obrázky, plakáty, loga a veřejné vizuální podklady. |
| `liga-app/` | Aktivní ligová aplikace. |
| `d377108_liga.sql` | Export databáze použitý pro lokální prostředí. Obsahuje i produkční `DEFINER` pohledu. |
| `.github/workflows/deploy.yml` | Automatické FTP nasazení z větve `main`. |
| `DATABASE_CLEANUP_ANALYSIS.md` | Dřívější analýza čištění databáze. |

### Aktivní `liga-app/`

| Oblast | Hlavní soubory/adresáře |
|---|---|
| Společný layout a navigace | `header.php`, `sidebar.php`, `footer.php` |
| Připojení a společné funkce | `db.php`, `common.php` |
| Přihlášení | `login.php`, `login_action.php`, `logout.php`, `_auth.php` |
| Sezóna | `set_season.php` |
| Ligové tabulky | `liga.php`, `ligy/*.liga.php`, `minitabulka_serazeni.php` |
| Rozpisy a výsledky | `rozpis.php`, `rozpisy/*rozpis.php`, `zapas.php`, `zapas_create.php`, `save_stats.php` |
| Statistiky | `stat.php`, `statistiky/*.stat.php`, `kompletni-statistiky.php` |
| Administrace | `admin/` |
| Rezervace terčů | `rezervace.php` |
| Starší Prezidentský pohár | `prezidentsky-pohar.php`, `admin/pp_*`, `save_pp.php` |
| Nový Prezidentský pohár | `pohar/` |
| Vzhled a klientská logika | `assets/`, `pohar/pohar.css`, `pohar/pohar.js` |
| PWA | `manifest.webmanifest`, `sw.js`, ikony |

## 4. Vysoká architektura

```text
Veřejný web
  index.html + style.css + script.js
  hraci.php ───────────────> seznam_hracu_web

Ligová aplikace
  header/sidebar/common
       │
       ├── sezóny a ligy ──> rocniky, ligy, ligy_nazvy, ligy_loga
       ├── hráči ──────────> hraci_unikatni_jmena, hraci_v_sezone
       ├── výsledky ───────> zapasy
       ├── rezervace ──────> rezervace
       ├── starý pohár ────> prezidentsky_turnaj, prezidentsky_zapas
       └── nový pohár ─────> turnaje, turnaj_hraci, turnaj_zapasy
```

PHP stránky přistupují k databázi přímo přes PDO. Neexistuje oddělená servisní nebo API vrstva. Většina výpočtů tabulek a statistik probíhá dynamicky při vykreslení stránky.

## 5. Databázový model

Lokální import obsahuje 19 základních tabulek. Databáze kombinuje novější kanonický model hráčů se starším modelem, což je důležité zejména u Prezidentského poháru.

### Sezóny a ligy

#### `rocniky`

- `id` – primární klíč
- `nazev` – název sezóny
- `locked` – zamčení sezóny proti některým administrativním změnám

Data obsahují sezóny Podzim 2024, Jaro 2025, Podzim 2025 a Jaro 2026. První tři jsou zamčené, Jaro 2026 je odemčené.

#### `ligy`

- `id`, `nazev`, `cislo`, `poradi`
- `cislo` je unikátní

Ligy 1–5 mají očekávané číslování. Ženská/„0. liga“ má v současných datech `id = 6` a `cislo = 6`, přestože se v uživatelském rozhraní označuje jako 0. liga. Část kódu proto používá speciální mapování nebo přímo ID 6.

#### `ligy_nazvy`

- složený primární klíč `rocnik_id, liga_id`
- umožňuje název ligy specifický pro sezónu
- databáze nemá deklarované cizí klíče

#### `ligy_loga`

- logo a alternativní text pro kombinaci sezóny a ligy
- unikátní kombinace `rocnik_id, liga_id`
- databáze nemá deklarované cizí klíče

### Hráči

#### `hraci_unikatni_jmena`

- `libovolne_id` – primární klíč
- `jmeno` – unikátní jméno

Toto je kanonická identita hráče pro aktivní ligy a nový turnajový modul.

#### `hraci_v_sezone`

- `hrac_id` → `hraci_unikatni_jmena.libovolne_id`
- `rocnik_id` → `rocniky.id`
- `liga_id` → `ligy.id`
- unikátní kombinace hráče a sezóny

Tabulka určuje, ve které lize hráč v daném ročníku působí. Jeden hráč může být v jednom ročníku přiřazen jen jednou.

#### `hraci`

- starší tabulka hráčů
- `liga_id` má FK na `ligy.id`
- obsahuje historické/kešované sloupce `z`, `v`, `p`, `rzd`, `body`

Aktivní ligové tabulky tyto kešované statistiky nepoužívají. Starý Prezidentský pohár však na tuto tabulku stále odkazuje, takže existují dvě identity hráče.

#### `seznam_hracu_web`

- samostatný veřejný seznam používaný kořenovým `hraci.php`
- není propojen cizím klíčem s ligovými hráči
- nemá primární klíč

### Ligové zápasy

#### `zapasy`

- dvojice `hrac1_id`, `hrac2_id` implicitně odkazuje na `hraci_unikatni_jmena`
- `rocnik_id`, `liga_id` určují soutěž
- výsledky: `skore1`, `skore2`
- datum zápasu
- průměry, high finish a počty hodů 100/120/140/160/180 pro oba hráče
- unikátní kombinace `rocnik_id, liga_id, hrac1_id, hrac2_id`

Většina vztahů v této tabulce není v databázi chráněna cizími klíči; integritu drží aplikační kód.

### Uživatelé a administrace

#### `uzivatele`

- přihlášení pomocí `username` a hashovaného hesla
- role: `user`, `admin`, `stat_editor`
- `must_change_pw` pro vynucenou změnu hesla

#### `admins`

- starší tabulka administrátorů
- `login_action.php` ji používá jako záložní autentizaci, pokud účet není nalezen v `uzivatele`

### Rezervace

#### `rezervace`

- aktivní model: `datum`, `hodina`, `terc`, `jmeno`
- unikátní kombinace data, hodiny a terče

#### `rezervace_old`

- starý model používající `terc_id`, `datum`, `cas`
- aktivní `rezervace.php` jej nepoužívá

### Starší Prezidentský pohár

#### `prezidentsky_turnaj`

- nejvýše jeden turnaj na ročník (`rocnik_id` je unikátní)
- počet vítězných legů, pravidlo IN/OUT a stav `draft/running/finished`

#### `prezidentsky_zapas`

- fáze `P`, `R`, `O`, `OF`, `QF`, `SF`, `F`
- hráči odkazují FK na starou tabulku `hraci`
- umožňuje i textový snapshot jména/placeholderu
- `next_match_id` je vazba na navazující zápas, `next_pos` určuje cílovou pozici
- unikátní kód a unikátní kombinace fáze/slotu v rámci turnaje

### Nový turnajový modul

#### `turnaje`

- název, ročník a stav `priprava/probiha/ukonceno`
- vazba na ročník není deklarovaná FK

#### `turnaj_hraci`

- účastníci turnaje
- `turnaj_id` má FK na `turnaje`
- `hrac_id` implicitně odkazuje na kanonický seznam `hraci_unikatni_jmena`
- unikátní kombinace turnaje a hráče

#### `turnaj_zapasy`

- kolo, pořadí, dva hráči, skóre a vítěz
- navazující zápas přes `next_match_id` a `next_slot`
- pouze `turnaj_id` je chráněn FK
- sloupce `navazuje_na_1` a `navazuje_na_2` se v nalezeném aktivním toku nepoužívají

#### `n_ligy`

- alternativní/rozpracovaný model lig pro ročník
- aktivní aplikace jej nepoužívá; jde o pozůstatek dřívějšího rozpracovaného modelu

## 6. Sezóna a navigace

Aktivní ročník je uložen v `$_SESSION['rocnik_id']`. Pokud chybí, `header.php` zvolí nejnovější ročník. `set_season.php` přijímá POST požadavek s CSRF tokenem, ověří existenci ročníku a přesměruje zpět na bezpečnou interní adresu.

`common.php` obsahuje centrální pomocné funkce pro:

- aktivní ročník,
- převod čísla ligy na ID,
- bezpečné určení ligy,
- názvy lig a ročníků,
- načítání hráčů pro ročník a ligu.

`sidebar.php` vytváří ligovou navigaci z databáze. Název a logo mohou být odlišné pro každý ročník.

Rizikové místo je mapování 0. ligy: obecné funkce pracují hlavně s čísly 0–5, databáze ji však reprezentuje jako ligu s ID/číslem 6. Wrappery pro 0. ligu proto používají ID 6 přímo.

## 7. Tok ligových dat

### Rozřazení hráčů

1. Hráč existuje v `hraci_unikatni_jmena`.
2. Administrace jej přiřadí do ročníku a ligy.
3. `admin/save_rozrazeni.php` v transakci vloží nebo aktualizuje `hraci_v_sezone`.
4. Odemknutý ročník lze měnit; zamčený ročník má změny blokované.

### Rozpis

`rozpis.php` generuje jednokolový systém každý s každým dynamicky z aktuálních hráčů ligy pomocí kruhového algoritmu. Úplný rozpis se do databáze neukládá. Ukládají se až jednotlivé zápasy v `zapasy`.

1. Stránka vytvoří teoretické dvojice.
2. Dvojice porovná s existujícími řádky `zapasy`.
3. Pokud zápas neexistuje, editor jej založí přes `zapas_create.php`.
4. ID hráčů se před vložením kanonicky seřadí, aby jedna dvojice nemohla vzniknout dvakrát v opačném pořadí.
5. Detail a editace probíhá v `zapas.php`.
6. `save_stats.php` v transakci aktualizuje skóre, datum a zápasové statistiky.

### Ligová tabulka

`liga.php` tabulku vždy dopočítává z výsledků v `zapasy`:

- výhra = 2 body,
- porážka = 0 bodů,
- rozdíl skóre se počítá ze součtu legů,
- remízy nejsou v logice řešeny jako běžný stav.

Při shodě bodů se používá `minitabulka_serazeni.php`:

- pro dva hráče vzájemný zápas,
- pro tři a více hráčů minitabulka podle mini-výher a mini-rozdílu skóre,
- následně celkový rozdíl skóre.

Není definovaný jednoznačný poslední abecední tie-break, takže úplná shoda může mít nestabilní pořadí.

### Statistiky

`stat.php` agreguje hodnoty uložené u jednotlivých zápasů:

- průměr = průměr z vyplněných zápasových průměrů,
- high finish = maximum,
- hodobody jsou vážený součet: 100 × 1, 120 × 2, 140 × 3, 160 × 4, 180 × 5.

`kompletni-statistiky.php` agreguje napříč ligami. Vazba na zápasy filtruje hráče a ročník, ale ne vždy také `liga_id`; při současném pravidle jedné ligy na ročník to obvykle výsledek nezkreslí, vazba je však slabší, než napovídá zobrazené členění.

Dashboard `liga-app/index.php` počítá průběh soutěží a lídry dynamicky. U lig 1–5 považuje za úplný výsledek cílové skóre 7, u ligy s ID 6 cílové skóre 5. Pro první ročník jsou některé statistiky záměrně přeskočeny.

## 8. Prezidentský pohár

V projektu existují dva oddělené systémy. Nemají se zaměňovat ani slučovat bez samostatného návrhu migrace.

### Starší systém – ročníky 1 až 3

Používá:

- `prezidentsky-pohar.php`,
- `admin/pp_index.php`, `admin/pp_seed.php` a související soubory,
- `save_pp.php`,
- tabulky `prezidentsky_turnaj` a `prezidentsky_zapas`,
- staré identity hráčů z `hraci`.

Administrátor založí turnaj a ručně obsadí pozice. Každý zápas ví, do kterého dalšího zápasu a na kterou pozici má postoupit vítěz. Uložení výsledku propaguje vítěze dopředu; reset je blokován, pokud už je navazující zápas odehraný.

Známé odchylky:

- ukládání výsledků prakticky používá pevnou hodnotu 5 vítězných legů, přestože tabulka obsahuje `legs_to_win`,
- stav turnaje se v nalezeném toku konzistentně neaktualizuje,
- modul používá starou tabulku hráčů,
- `admin/pp_seed.php` závisí na pohledu `v_hraci_rocnik`.

SQL export vytváří `v_hraci_rocnik` s produkčním `DEFINER='a377108_liga'@'%'`. Lokální import proto pohled nevytvořil. Navíc definice pohledu spojuje `hraci_v_sezone.hrac_id` se starou tabulkou `hraci`, přestože moderní význam tohoto ID vede do `hraci_unikatni_jmena`. To představuje riziko nesprávného propojení identit.

Veřejná stránka odkazuje na `/liga-app/assets/pp.css?v=3`, ale tento soubor v repozitáři chybí.

### Nový systém – ročník 4 a novější

Používá:

- `pohar/pohar_turnaj.php`,
- `pohar/pohar_funkce.php`,
- `pohar/pohar_1kolo_admin.php`,
- AJAX endpointy v `pohar/`,
- `pohar/pohar.js` a `pohar/pohar.css`,
- tabulky `turnaje`, `turnaj_hraci`, `turnaj_zapasy`,
- kanonické hráče `hraci_unikatni_jmena`.

Generátor vytváří pevný sportovní pavouk pro 64 pozic a propojí zápasy přes `next_match_id` a `next_slot`. Volné pozice lze označit jako BYE a hráč automaticky postoupí. Výsledek se ukládá transakčně a vítěz se propaguje do dalšího kola. Reset je blokován, pokud už je navazující zápas dokončen.

Pravidla skóre v současném kódu:

- kola 1–4: první na 3,
- kola 5–6: první na 4.

Aktuální data obsahují turnaj Jaro 2026 s 62 účastníky, dvěma BYE a kompletním 64místným pavoukem. Výsledky jsou vyplněné až do konce, ale stav turnaje zůstal `priprava`, protože se stav automaticky nesynchronizuje.

Známý problém: `pohar/turnaj-vytvorit.php` volá neexistující funkci `generujTurnaj()`, zatímco dostupná funkce se jmenuje `generujSportovniPavouk()`. Vytváření turnaje tímto průvodcem je pravděpodobně nefunkční a transakce se vrátí zpět.

Klientská logika zabraňuje dvojímu výběru hráče v rozhraní, ale samotný AJAX endpoint přiřazení nekontroluje globální duplicitu stejným způsobem na serveru.

## 9. Autentizace, role a oprávnění

`login_action.php` nejprve hledá uživatele v `uzivatele`. Pokud jej nenajde, zkouší starší `admins`. Do session ukládá zejména ID uživatele, jméno, roli a příznak administrátora.

Používané role:

- `admin` – administrace,
- `stat_editor` – editace výsledků/statistik,
- `user` – běžný účet.

`_auth.php` vyžaduje administrátora. Některé stránky jej načtou a následně deklarují podporu `stat_editor`, ale editor se přes první kontrolu nemusí dostat. Viditelnost odkazu na administraci v `header.php` je navíc částečně odvozena od konkrétního uživatelského jména `beran`, nikoli pouze od role.

CSRF ochranu poskytuje `security/csrf.php`; `header.php` kontroluje POST formuláře, které přes něj procházejí, a `assets/csrf-autoinject.js` vkládá token do formulářů. Ochrana není plošná pro všechny endpointy.

## 10. Frontend, CSS a JavaScript

### Veřejný web

- `style.css` je samostatný globální stylesheet veřejné části.
- `script.js` obsluhuje menu, popup a další drobné chování.
- Skript očekává prvky `event-popup`, `popup-close` a `year`, zatímco aktuální HTML používá popup s ID `popup`, zavírací prvek jako třídu `.popup-close` a nemá prvek `year`. Popupová logika je proto pravděpodobně napojená na starší markup.
- `index.html` obsahuje podezřelé/nevyvážené členění uzavíracích elementů kolem hero sekce; prohlížeč jej opravuje tolerantním HTML parserem.

### Ligová aplikace

Aktivní `header.php` načítá především:

- `assets/theme.final.css`,
- `assets/theme.js`,
- `assets/autoWrapTables.js`,
- `assets/csrf-autoinject.js`.

`theme.js` obsluhuje mobilní navigaci/sidebar. `autoWrapTables.js` doplňuje obaly tabulek pro responzivní zobrazení. Pohár má vlastní CSS a JS.

V `assets/` zůstávají další varianty (`theme.css`, `theme.fixed.css`, `patch.css` a dílčí CSS soubory), které aktivní hlavička přímo nenačítá. Pravděpodobně jde o historické zdroje nebo dřívější patche. V aplikaci je také mnoho inline stylů, zejména v zápasech, rezervacích a administraci. Globální třídy jako `.btn`, `.table` a `.nk-*` používá více nesouvisejících obrazovek, proto může změna CSS snadno způsobit vedlejší regresi.

PWA část používá jednoduchý service worker se síťovým načtením a offline odpovědí. Hlavička odkazuje na Apple ikonu `sipky-180.png`, která v sadě ikon není; dostupné jsou jiné velikosti.

### Externí závislosti

- veřejný web používá Google Fonts,
- aktivní jádro nemá balíčkované PHP ani JS knihovny,
- aktivní rezervační stránka nepoužívá externí kalendářovou knihovnu ani CAPTCHA.

## 11. Rezervace

Aktivní `liga-app/rezervace.php` používá novou tabulku `rezervace`, časové sloty 15–21, uzavření v pondělí a úterý a zvláštní nedělní omezení na terč 7 v Čenkově.

Rezervaci i její odstranění lze v současném toku provést bez přihlášení, CSRF ochrany a CAPTCHA. Uživatel, který zná slot, může odeslat požadavek na smazání. Jde o významné bezpečnostní riziko.

`events.php` a `calendar.php` pracují se starými poli `terc_id` a `cas`; aktivní tabulka používá `terc` a `hodina`. Tyto stránky jsou pravděpodobně zastaralé/nefunkční.

## 12. Známé bezpečnostní a provozní problémy

Priorita níže je orientační; tento dokument problémy pouze eviduje.

### Kritické

1. Staré produkční databázové přihlašovací údaje byly v minulosti zapsané v historii trackovaného `liga-app/db.php`. Heslo bylo následně změněno a aktuální údaje se ukládají pouze do ignorovaného `config/db.production.php`. Historické heslo je přesto nutné trvale považovat za kompromitované.
2. Veřejné rezervace umožňují vytváření a mazání bez autentizace, CSRF a ochrany proti automatizaci.
3. Některé pomocné skripty pro vytvoření administrátora (`create_admin.php`) mají nedostatečnou nebo zakomentovanou ochranu a nemají být veřejně dostupné.

### Vysoké

1. AJAX endpointy nového poháru kontrolují session roli i CSRF token posílaný v hlavičce `X-CSRF-Token`. Stejnou CSRF ochranu používá také formulář ručního obsazení prvního kola.
2. `admin/ligy_loga.php` není jednoznačně chráněn administrátorskou kontrolou a `admin/ulozit_logo.php` nemá dostatečnou kontrolu role ani CSRF.
3. Dvojí identita hráčů (`hraci` versus `hraci_unikatni_jmena`) může propojit nesprávná data ve starém poháru.
4. Nasazovací workflow kopíruje téměř celý repozitář, tedy potenciálně i SQL exporty a pomocné skripty, pokud jsou v `main`.

### Střední a technický dluh

1. CSRF ochrana nemusí být úplná ve starších a pomocných endpointech mimo nový pohár; každý nový zápisový tok je nutné samostatně ověřit.
2. Chybí rate limiting přihlášení a veřejných zápisů.
3. Některé databázové vztahy nejsou chráněny FK.
4. Stav turnaje není synchronizován s výsledky.
5. Mapování 0. ligy je nekonzistentní.
6. Chybí asset `assets/pp.css` a Apple ikona `sipky-180.png`.
7. `set_password.php` vyžaduje nenalezený soubor `d.php`.
8. `edit_results.php` je pevně svázán s ročníkem 1 a jeho `save_results.php` očekává sloupce, které v současném `zapasy` neexistují (`match_id`, `score_home`, `entered_by` aj.). Tento tok je pravděpodobně zastaralý.

## 13. Duplicitní a pravděpodobně nepoužívaný kód

### Odstraněná paralelní kopie

Nepoužívaná složka `liga-app-clean/` byla 18. 8. 2026 odstraněna. Obsahovala neúplnou a syntakticky poškozenou paralelní variantu aplikace. Aktivní `liga-app/` zůstává jedinou udržovanou implementací a prošla kontrolou syntaxe PHP bez chyby.

### Další pravděpodobně historické části

- `hraci.html` vedle aktivního `hraci.php`,
- `events.php` a `calendar.php` proti starému schématu rezervací,
- `rezervace_old`,
- `edit_results.php` a `save_results.php`,
- `n_ligy` a generátor očekávající neexistující `n_zapasy`,
- starší CSS varianty v `liga-app/assets/`, které aktivní hlavička nenačítá,
- historické statistické sloupce v `hraci`.

Před odstraněním čehokoli je nutné ověřit produkční odkazy, ručně používané administrativní URL a obsah nasazený mimo Git.

## 14. Ověřený stav dat k datu analýzy

- 4 ročníky,
- 6 lig,
- 69 kanonických unikátních jmen hráčů,
- 195 přiřazení hráče do sezóny,
- 815 ligových zápasů,
- 188 aktivních rezervací a 173 záznamů ve staré rezervační tabulce,
- 1 nový turnaj, 62 účastníků a 63 zápasů,
- 49 zápasů starého Prezidentského poháru.

Lokálně vracely veřejný web, seznam hráčů, dashboard ligové aplikace, ligová tabulka, rozpis a statistiky HTTP 200. Aktivní PHP soubory prošly syntax checkem.

## 15. Pravidla pro budoucí změny

Při každé další úpravě projektu:

1. Nejdříve určit dotčené stránky, tabulky, role, společné CSS/JS a případné vazby mezi starým a novým modulem.
2. Považovat `liga-app/` za jediný zdroj pravdy pro ligovou aplikaci.
3. Provést nejmenší možnou změnu v rozsahu zadání.
4. Neměnit databázové schéma bez kontroly všech SQL dotazů a datových migrací.
5. U zápisových endpointů ověřit autentizaci, autorizaci, CSRF, validaci vstupu a transakční chování.
6. U změn hráčů rozlišovat `hraci`, `hraci_unikatni_jmena` a `seznam_hracu_web`.
7. U poháru nejprve určit, zda jde o starý, nebo nový systém.
8. U CSS ověřit desktop i mobil a dopad globálních tříd na ostatní obrazovky.
9. Před odevzdáním spustit PHP lint a přiměřené lokální testy dotčených stránek.
10. Zachovat existující nesouvisející lokální změny.
11. Pokud změna upraví architekturu, databázové schéma, role, závislosti nebo hlavní datový tok, aktualizovat tento `PROJECT_CONTEXT.md` ve stejném commitu.

## 16. Doporučený postup při orientaci

Pro ligový problém začít v wrapperu příslušné ligy a pokračovat do společného `liga.php`, `rozpis.php` nebo `stat.php`. Pro zápis výsledku sledovat tok `zapas_create.php` → `zapas.php` → `save_stats.php`. Pro hráče začít v `hraci_v_sezone` a `hraci_unikatni_jmena`. Pro pohár nejdříve podle ročníku určit starý či nový modul. Pro problémy se vzhledem začít u `header.php` a `assets/theme.final.css`, nikoli automaticky u všech CSS souborů.
