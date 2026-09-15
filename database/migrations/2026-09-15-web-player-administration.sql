ALTER TABLE seznam_hracu_web
    ADD COLUMN IF NOT EXISTS hrac_id INT UNSIGNED NULL AFTER vek,
    ADD COLUMN IF NOT EXISTS zobrazit TINYINT(1) NOT NULL DEFAULT 1 AFTER hrac_id,
    ADD COLUMN IF NOT EXISTS updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER zobrazit;

UPDATE seznam_hracu_web s
JOIN hraci_unikatni_jmena h ON TRIM(h.jmeno) = TRIM(s.jmeno)
SET s.hrac_id = h.libovolne_id, s.jmeno = h.jmeno
WHERE s.hrac_id IS NULL;

UPDATE seznam_hracu_web s
JOIN hraci_unikatni_jmena h ON h.libovolne_id = CASE s.klubove_cislo
    WHEN '002' THEN 1 WHEN '003' THEN 18 WHEN '006' THEN 35 WHEN '008' THEN 32
    WHEN '018' THEN 9 WHEN '024' THEN 40 WHEN '029' THEN 17 WHEN '032' THEN 139
    WHEN '034' THEN 44 WHEN '039' THEN 138 WHEN '043' THEN 30 WHEN '046' THEN 42
    WHEN '053' THEN 36 WHEN '055' THEN 146 WHEN '058' THEN 149 WHEN '059' THEN 150
END
SET s.hrac_id = h.libovolne_id, s.jmeno = h.jmeno
WHERE s.hrac_id IS NULL
  AND s.klubove_cislo IN ('002','003','006','008','018','024','029','032','034','039','043','046','053','055','058','059');

ALTER TABLE seznam_hracu_web
    ADD UNIQUE INDEX IF NOT EXISTS uq_seznam_hracu_web_klubove_cislo (klubove_cislo),
    ADD UNIQUE INDEX IF NOT EXISTS uq_seznam_hracu_web_hrac_id (hrac_id);
