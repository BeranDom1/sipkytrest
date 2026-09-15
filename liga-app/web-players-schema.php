<?php
declare(strict_types=1);

/**
 * Doplní vazbu veřejných profilů na centrální databázi hráčů.
 * Stávající záznamy zůstávají viditelné a zachovají si klubová čísla.
 */
function ensureWebPlayersSchema(mysqli $conn): void
{
    static $ready = false;
    if ($ready) return;

    if (!$conn->query("CREATE TABLE IF NOT EXISTS seznam_hracu_web (
        klubove_cislo VARCHAR(10) NOT NULL,
        jmeno VARCHAR(100) NOT NULL,
        prezdivka VARCHAR(100) NULL,
        bydliste VARCHAR(100) NULL,
        vek TINYINT UNSIGNED NULL,
        hrac_id INT UNSIGNED NULL,
        zobrazit TINYINT(1) NOT NULL DEFAULT 1,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_czech_ci")) {
        throw new RuntimeException('Tabulku veřejných hráčů se nepodařilo připravit: '.$conn->error, $conn->errno);
    }

    $columns = [
        'hrac_id' => 'INT UNSIGNED NULL AFTER vek',
        'zobrazit' => 'TINYINT(1) NOT NULL DEFAULT 1 AFTER hrac_id',
        'updated_at' => 'DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER zobrazit',
    ];
    foreach ($columns as $column => $definition) {
        $result = $conn->query("SHOW COLUMNS FROM seznam_hracu_web LIKE '{$column}'");
        if ((!$result || $result->num_rows === 0)
            && !$conn->query("ALTER TABLE seznam_hracu_web ADD COLUMN {$column} {$definition}")) {
            throw new RuntimeException('Veřejné profily hráčů se nepodařilo rozšířit: '.$conn->error, $conn->errno);
        }
    }

    if (!$conn->query("UPDATE seznam_hracu_web s
        JOIN hraci_unikatni_jmena h ON TRIM(h.jmeno) = TRIM(s.jmeno)
        SET s.hrac_id = h.libovolne_id, s.jmeno = h.jmeno
        WHERE s.hrac_id IS NULL")) {
        throw new RuntimeException('Stávající hráče se nepodařilo propojit: '.$conn->error, $conn->errno);
    }

    // Historický webový seznam používá u několika hráčů celé jméno nebo jiný pravopis.
    $legacyAliases = [
        '002' => 1,
        '003' => 18,
        '006' => 35,
        '008' => 32,
        '018' => 9,
        '024' => 40,
        '029' => 17,
        '032' => 139,
        '034' => 44,
        '039' => 138,
        '043' => 30,
        '046' => 42,
        '053' => 36,
        '055' => 146,
        '058' => 149,
        '059' => 150,
    ];
    $alias = $conn->prepare("UPDATE seznam_hracu_web s
        JOIN hraci_unikatni_jmena h ON h.libovolne_id = ?
        SET s.hrac_id = h.libovolne_id, s.jmeno = h.jmeno
        WHERE s.hrac_id IS NULL AND s.klubove_cislo = ?");
    if (!$alias) {
        throw new RuntimeException('Mapování veřejných hráčů se nepodařilo připravit: '.$conn->error, $conn->errno);
    }
    foreach ($legacyAliases as $clubNumber => $playerId) {
        $alias->bind_param('is', $playerId, $clubNumber);
        if (!$alias->execute()) {
            throw new RuntimeException('Stávajícího hráče se nepodařilo propojit: '.$alias->error, $alias->errno);
        }
    }
    $alias->close();

    $duplicateNumbers = $conn->query("SELECT klubove_cislo FROM seznam_hracu_web
        GROUP BY klubove_cislo HAVING COUNT(*) > 1 LIMIT 1");
    if (!$duplicateNumbers) {
        throw new RuntimeException('Klubová čísla se nepodařilo ověřit: '.$conn->error, $conn->errno);
    }
    if ($duplicateNumbers->num_rows > 0) {
        throw new RuntimeException('V tabulce veřejných hráčů jsou duplicitní klubová čísla.');
    }

    $indexes = $conn->query('SHOW INDEX FROM seznam_hracu_web');
    if (!$indexes) {
        throw new RuntimeException('Indexy veřejných hráčů se nepodařilo ověřit: '.$conn->error, $conn->errno);
    }
    $indexNames = [];
    while ($index = $indexes->fetch_assoc()) {
        $indexNames[(string)$index['Key_name']] = true;
    }
    if (!isset($indexNames['uq_seznam_hracu_web_klubove_cislo'])
        && !$conn->query('ALTER TABLE seznam_hracu_web ADD UNIQUE KEY uq_seznam_hracu_web_klubove_cislo (klubove_cislo)')) {
        throw new RuntimeException('Unikátnost klubových čísel se nepodařilo nastavit: '.$conn->error, $conn->errno);
    }
    if (!isset($indexNames['uq_seznam_hracu_web_hrac_id'])
        && !$conn->query('ALTER TABLE seznam_hracu_web ADD UNIQUE KEY uq_seznam_hracu_web_hrac_id (hrac_id)')) {
        throw new RuntimeException('Hráčské profily se nepodařilo zabezpečit proti duplicitám: '.$conn->error, $conn->errno);
    }

    $ready = true;
}
