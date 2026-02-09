
-- Rezervace: zabrání duplicitnímu zapsání stejného slotu (terč + datum + čas)
ALTER TABLE rezervace
  ADD UNIQUE KEY uniq_slot (terc_id, datum, cas);

-- Zrychlení filtrů nad zápasy
CREATE INDEX idx_zapasy_rocnik_liga ON zapasy (rocnik_id, liga_id);
CREATE INDEX idx_zapasy_hraci ON zapasy (hrac1_id, hrac2_id);
