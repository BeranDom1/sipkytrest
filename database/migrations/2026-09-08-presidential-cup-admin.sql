-- Nastavitelný Prezidentský pohár pro každou sezonu.
ALTER TABLE turnaje
    ADD COLUMN IF NOT EXISTS uvod TEXT NULL AFTER stav;

ALTER TABLE turnaje
    ADD COLUMN IF NOT EXISTS herni_mod VARCHAR(500) NULL AFTER uvod;

ALTER TABLE turnaje
    ADD COLUMN IF NOT EXISTS system_hry VARCHAR(500) NULL AFTER herni_mod;

ALTER TABLE turnaje
    ADD COLUMN IF NOT EXISTS los_typ ENUM('nasazeny', 'nahodny') NOT NULL DEFAULT 'nasazeny' AFTER system_hry;

ALTER TABLE turnaje
    ADD COLUMN IF NOT EXISTS los_popis VARCHAR(500) NULL AFTER los_typ;

ALTER TABLE turnaje
    ADD COLUMN IF NOT EXISTS velikost_pavouka SMALLINT UNSIGNED NOT NULL DEFAULT 64 AFTER los_popis;

ALTER TABLE turnaje
    ADD COLUMN IF NOT EXISTS legy_json TEXT NULL AFTER velikost_pavouka;

ALTER TABLE turnaje
    ADD COLUMN IF NOT EXISTS terminy_json TEXT NULL AFTER legy_json;

ALTER TABLE turnaj_hraci
    ADD COLUMN IF NOT EXISTS volny_los TINYINT(1) NOT NULL DEFAULT 0 AFTER nasazeni;

-- Zachování dosavadního vzhledu a pravidel turnaje Jaro 2026.
UPDATE turnaje
SET uvod = COALESCE(NULLIF(uvod, ''), 'Prezidentský pohár se hraje vyřazovacím způsobem (KO). Poražený v turnaji končí, vítěz postupuje do dalšího kola.'),
    herni_mod = COALESCE(NULLIF(herni_mod, ''), 'Cricket (cut-throut) na 3 vítězné legy, semifinále a finále na 4 vítězné legy'),
    system_hry = COALESCE(NULLIF(system_hry, ''), 'KO pavouk (64 → 32 → 16 → 8 → 4 → 2 → vítěz)'),
    los_popis = COALESCE(NULLIF(los_popis, ''), 'Prvních 32 nasazených hráčů + los'),
    velikost_pavouka = 64,
    legy_json = COALESCE(NULLIF(legy_json, ''), '{"1":3,"2":3,"3":3,"4":3,"5":4,"6":4}'),
    terminy_json = COALESCE(NULLIF(terminy_json, ''), '[{"label":"TOP 64","text":"odehrát do 1. 3. 2026"},{"label":"TOP 32","text":"odehrát do 1. 4. 2026"},{"label":"TOP 16","text":"odehrát do 25. 4. 2026"},{"label":"TOP 8","text":"odehrát do 20. 5. 2026"},{"label":"Grande finále","text":"(semifinále 1, semifinále 2, finále) – pátek 29. 5. 18:00 (sobota 30. 5. 18:00)"}]')
WHERE rocnik_id = 4;
