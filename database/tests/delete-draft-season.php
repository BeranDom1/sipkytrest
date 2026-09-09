<?php
// Integration checks using connection-local temporary tables only.
require_once __DIR__ . '/../../liga-app/admin/season_helpers.php';
mysqli_report(MYSQLI_REPORT_OFF); // Match the application connection settings.
function verify(bool $ok, string $message): void {
    if (!$ok) throw new RuntimeException($message);
}
$schemas = [
    'rocniky' => 'id INT PRIMARY KEY, stav VARCHAR(20), locked INT',
    'prezidentsky_turnaj' => 'id INT PRIMARY KEY, rocnik_id INT',
    'prezidentsky_zapas' => 'id INT PRIMARY KEY, turnaj_id INT, next_match_id INT NULL',
    'turnaje' => 'id INT PRIMARY KEY, rocnik_id INT',
    'turnaj_zapasy' => 'id INT PRIMARY KEY, turnaj_id INT',
    'turnaj_hraci' => 'id INT PRIMARY KEY, turnaj_id INT',
];
foreach (['zapasy', 'hraci_v_sezone', 'ligy_loga', 'ligy_nazvy', 'n_ligy', 'hraci'] as $table) {
    $schemas[$table] = 'id INT PRIMARY KEY, rocnik_id INT NULL';
}
$schemas['hraci_unikatni_jmena'] = 'id INT PRIMARY KEY';
$schemas['rezervace'] = 'id INT PRIMARY KEY';
foreach ($schemas as $table => $schema) {
    $conn->query("CREATE TEMPORARY TABLE `$table` ($schema) ENGINE=InnoDB");
}
try {
    $conn->query("INSERT INTO rocniky VALUES (1,'priprava',0),(2,'aktivni',0),(3,'archivni',1),(4,'priprava',1),(5,'priprava',0)");
    foreach ($schemas as $table => $schema) {
        if ($table === 'rocniky') continue;
        if (in_array($table, ['hraci_unikatni_jmena', 'rezervace'], true)) {
            $conn->query("INSERT INTO `$table` VALUES (1),(2)");
        } elseif ($table === 'prezidentsky_zapas') {
            $conn->query("INSERT INTO `$table` VALUES (1,1,1),(2,2,2)");
        } else {
            $conn->query("INSERT INTO `$table` VALUES (1,1),(2,2)");
        }
    }
    $snapshot = static function () use ($conn, $schemas): array {
        $data = [];
        foreach ($schemas as $table => $schema) {
            $data[$table] = $conn->query("SELECT * FROM `$table` ORDER BY id")->fetch_all(MYSQLI_ASSOC);
        }
        return $data;
    };
    $before = $snapshot();
    foreach ([2,3,4,999] as $id) {
        $rejected = false;
        try { admin_delete_draft_season($conn, $id); } catch (RuntimeException $e) { $rejected = true; }
        verify($rejected, "Must reject season $id");
        verify($snapshot() === $before, 'Rejected deletion changed data');
    }
    // Force an error after cup rows have already been removed: verify rollback.
    $conn->query('ALTER TABLE n_ligy CHANGE rocnik_id broken_column INT');
    $failed = false;
    try { admin_delete_draft_season($conn, 1); } catch (Throwable $e) { $failed = true; }
    $conn->query('ALTER TABLE n_ligy CHANGE broken_column rocnik_id INT NULL');
    verify($failed, 'Failure injection did not fail');
    verify($snapshot() === $before, 'Failed deletion was not fully rolled back');
    admin_delete_draft_season($conn, 1);
    $after = $snapshot();
    foreach ($schemas as $table => $schema) {
        if (in_array($table, ['hraci_unikatni_jmena', 'rezervace'], true)) {
            verify($after[$table] === $before[$table], "Shared data changed: $table");
        } elseif ($table === 'hraci') {
            verify(count($after[$table]) === 2 && $after[$table][0]['rocnik_id'] === null, 'Legacy player identity lost');
            verify($after[$table][1] === $before[$table][1], 'Other season player changed');
        } else {
            verify($after[$table] === array_values(array_filter($before[$table], static fn($row) => (int)$row['id'] !== 1)), "Wrong deletion scope: $table");
        }
    }
    admin_delete_draft_season($conn, 5);
    verify(!$conn->query('SELECT id FROM rocniky WHERE id=5')->num_rows, 'Empty draft not deleted');
    echo "PASS: draft cleanup, empty draft, active/archive/locked/missing rejection, rollback, shared data preservation\n";
} finally {
    foreach (array_keys($schemas) as $table) $conn->query("DROP TEMPORARY TABLE `$table`");
}
