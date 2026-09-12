<?php
declare(strict_types=1);

/**
 * Připraví registrační tabulky i na instalacích, kde ještě nebyla
 * samostatně spuštěna migrace. Po první kontrole v rámci requestu už neběží znovu.
 */
function zajistiSchemaRegistraci(mysqli $conn): void
{
    static $ready = false;
    if ($ready) return;

    $result = $conn->query("SHOW TABLES LIKE 'registracni_turnaje'");
    $registrations = $conn->query("SHOW TABLES LIKE 'turnaj_registrace'");
    $customFields = $conn->query("SHOW TABLES LIKE 'turnaj_form_pole'");
    $customValues = $conn->query("SHOW TABLES LIKE 'turnaj_registrace_hodnoty'");
    if (!$result || $result->num_rows === 0 || !$registrations || $registrations->num_rows === 0
        || !$customFields || $customFields->num_rows === 0 || !$customValues || $customValues->num_rows === 0) {
        $statements = [
            "CREATE TABLE IF NOT EXISTS registracni_turnaje (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT, slug VARCHAR(120) NOT NULL,
                nazev VARCHAR(255) NOT NULL, podtitul VARCHAR(255) NULL, datum DATE NOT NULL,
                cas_info VARCHAR(120) NULL, misto VARCHAR(255) NOT NULL, adresa VARCHAR(255) NOT NULL,
                kapacita SMALLINT UNSIGNED NOT NULL, startovne SMALLINT UNSIGNED NULL,
                informace TEXT NULL, kontakt_email VARCHAR(255) NULL, kontakt_telefon VARCHAR(40) NULL,
                zobrazit_rok_narozeni TINYINT(1) NOT NULL DEFAULT 1,
                zobrazit_ubytovani TINYINT(1) NOT NULL DEFAULT 1,
                zobrazit_poznamku TINYINT(1) NOT NULL DEFAULT 1,
                stav ENUM('priprava','otevrena','uzavrena','archivni') NOT NULL DEFAULT 'priprava',
                registrace_do DATETIME NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id), UNIQUE KEY uq_registracni_turnaje_slug (slug),
                KEY idx_registracni_turnaje_stav_datum (stav, datum)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_czech_ci",
            "CREATE TABLE IF NOT EXISTS turnaj_registrace (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT, turnaj_id INT UNSIGNED NOT NULL,
                jmeno VARCHAR(100) NOT NULL, prijmeni VARCHAR(100) NOT NULL, mesto VARCHAR(150) NOT NULL,
                telefon VARCHAR(40) NOT NULL, telefon_normalized VARCHAR(20) NOT NULL,
                rok_narozeni SMALLINT UNSIGNED NULL, zajem_ubytovani TINYINT(1) NOT NULL DEFAULT 0,
                poznamka VARCHAR(1000) NULL,
                stav ENUM('nova','potvrzena','nahradnik','odmitnuta','zrusena') NOT NULL DEFAULT 'nova',
                dedupe_key CHAR(64) NOT NULL, souhlas_at DATETIME NOT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id), UNIQUE KEY uq_turnaj_registrace_dedupe (turnaj_id,dedupe_key),
                KEY idx_turnaj_registrace_poradi (turnaj_id,stav,created_at),
                CONSTRAINT fk_turnaj_registrace_turnaj FOREIGN KEY (turnaj_id)
                    REFERENCES registracni_turnaje (id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_czech_ci",
            "CREATE TABLE IF NOT EXISTS turnaj_form_pole (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT, turnaj_id INT UNSIGNED NOT NULL,
                popisek VARCHAR(255) NOT NULL,
                typ ENUM('text','textarea','cislo','ano_ne','vyber') NOT NULL DEFAULT 'text',
                moznosti TEXT NULL, povinne TINYINT(1) NOT NULL DEFAULT 0,
                zobrazit TINYINT(1) NOT NULL DEFAULT 1, poradi SMALLINT UNSIGNED NOT NULL DEFAULT 0,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (id),
                KEY idx_turnaj_form_pole_poradi (turnaj_id,poradi,id),
                CONSTRAINT fk_turnaj_form_pole_turnaj FOREIGN KEY (turnaj_id)
                    REFERENCES registracni_turnaje (id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_czech_ci",
            "CREATE TABLE IF NOT EXISTS turnaj_registrace_hodnoty (
                registrace_id INT UNSIGNED NOT NULL, pole_id INT UNSIGNED NOT NULL, hodnota TEXT NOT NULL,
                PRIMARY KEY (registrace_id,pole_id),
                CONSTRAINT fk_turnaj_registrace_hodnoty_registrace FOREIGN KEY (registrace_id)
                    REFERENCES turnaj_registrace (id) ON DELETE CASCADE,
                CONSTRAINT fk_turnaj_registrace_hodnoty_pole FOREIGN KEY (pole_id)
                    REFERENCES turnaj_form_pole (id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_czech_ci",
        ];
        foreach ($statements as $sql) {
            if (!$conn->query($sql)) throw new RuntimeException('Registrační databázi se nepodařilo připravit.');
        }
    }

    $accommodationColumn = $conn->query("SHOW COLUMNS FROM turnaj_registrace LIKE 'zajem_ubytovani'");
    if (!$accommodationColumn || $accommodationColumn->num_rows === 0) {
        if (!$conn->query('ALTER TABLE turnaj_registrace ADD COLUMN zajem_ubytovani TINYINT(1) NOT NULL DEFAULT 0 AFTER rok_narozeni')) {
            throw new RuntimeException('Pole pro ubytování se nepodařilo připravit.');
        }
    }

    $eventColumns = [
        'zobrazit_rok_narozeni' => 'TINYINT(1) NOT NULL DEFAULT 1 AFTER kontakt_telefon',
        'zobrazit_ubytovani' => 'TINYINT(1) NOT NULL DEFAULT 1 AFTER zobrazit_rok_narozeni',
        'zobrazit_poznamku' => 'TINYINT(1) NOT NULL DEFAULT 1 AFTER zobrazit_ubytovani',
    ];
    foreach ($eventColumns as $column => $definition) {
        $columnResult = $conn->query("SHOW COLUMNS FROM registracni_turnaje LIKE '{$column}'");
        if ((!$columnResult || $columnResult->num_rows === 0)
            && !$conn->query("ALTER TABLE registracni_turnaje ADD COLUMN {$column} {$definition}")) {
            throw new RuntimeException('Nastavení formuláře se nepodařilo připravit.');
        }
    }

    $seed = $conn->prepare("INSERT IGNORE INTO registracni_turnaje
        (slug,nazev,podtitul,datum,cas_info,misto,adresa,kapacita,startovne,informace,kontakt_email,kontakt_telefon,stav)
        SELECT 'vanocni-turnaj-2026','Vánoční šipkový turnaj','Steel • Pohár starosty města Třeště',
        '2026-12-12','Celodenní turnaj','Areál SOŠ, SOU Třešť','K Valše 1251, Třešť',192,300,
        'Skupiny po 6 • ceny pro TOP 32 + Lucky Loser • 32 terčů • možnost ubytování • losovací tombola pro platící hráče',
        'prihlasky@sipkytrest.cz','603 723 705','otevrena'");
    if (!$seed || !$seed->execute()) throw new RuntimeException('Vánoční turnaj se nepodařilo připravit.');
    $seed->close();
    $ready = true;
}
