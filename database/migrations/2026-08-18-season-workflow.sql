-- Nedestruktivní rozšíření správy sezón a uložených kol rozpisu.
ALTER TABLE rocniky
    ADD COLUMN IF NOT EXISTS stav ENUM('priprava', 'aktivni', 'archivni')
    NOT NULL DEFAULT 'priprava' AFTER locked;

UPDATE rocniky
SET stav = CASE WHEN locked = 1 THEN 'archivni' ELSE 'aktivni' END;

ALTER TABLE zapasy
    ADD COLUMN IF NOT EXISTS kolo SMALLINT UNSIGNED NULL AFTER liga_id;

ALTER TABLE zapasy
    ADD INDEX IF NOT EXISTS idx_rocnik_liga_kolo (rocnik_id, liga_id, kolo);
