-- Nedestruktivní rozšíření správy sezón a uložených kol rozpisu.
ALTER TABLE rocniky
    ADD COLUMN IF NOT EXISTS stav ENUM('priprava', 'aktivni', 'archivni')
    NOT NULL DEFAULT 'priprava' AFTER locked;

-- Staré exporty měly po přidání sloupce všechny sezóny ve stavu „příprava“.
-- Již vyplněné stavy z novějšího exportu ale zachovej.
UPDATE rocniky
SET stav = 'archivni'
WHERE locked = 1 AND stav = 'priprava';

UPDATE rocniky
SET stav = 'aktivni'
WHERE locked = 0
  AND stav = 'priprava'
  AND id = (
      SELECT newest_id
      FROM (SELECT MAX(id) AS newest_id FROM rocniky WHERE locked = 0) AS newest_season
  )
  AND NOT EXISTS (
      SELECT 1
      FROM (SELECT stav FROM rocniky) AS existing_seasons
      WHERE existing_seasons.stav = 'aktivni'
  );

ALTER TABLE zapasy
    ADD COLUMN IF NOT EXISTS kolo SMALLINT UNSIGNED NULL AFTER liga_id;

ALTER TABLE zapasy
    ADD INDEX IF NOT EXISTS idx_rocnik_liga_kolo (rocnik_id, liga_id, kolo);
