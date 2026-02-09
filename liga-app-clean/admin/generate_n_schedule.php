<?php
// Vygeneruje rozlosování (round-robin) do tabulky n_zapasy pro aktivní ročník (>=4).
// Spusť jako admin: /liga-app-clean/admin/generate_n_schedule.php

require __DIR__.'/_auth.php';
require __DIR__.'/../db.php';
require __DIR__.'/../common.php';

header('Content-Type: text/plain; charset=utf-8');

$rocnik_id = _active_rocnik_id($conn);
if ($rocnik_id < 4) {
    http_response_code(400);
    echo "Aktivní ročník je $rocnik_id. Tenhle generátor je určený pro nové ročníky (>=4).\n";
    exit;
}

// --- round-robin (circle method) ---
function rr_schedule(array $ids): array {
    $n = count($ids);
    if ($n < 2) return [];
    $work = array_values($ids);
    if ($n % 2 === 1) { $work[] = 0; $n++; }

    $rounds = [];
    $half = (int)($n / 2);
    for ($r = 0; $r < $n - 1; $r++) {
        $pairs = [];
        for ($i = 0; $i < $half; $i++) {
            $a = (int)$work[$i];
            $b = (int)$work[$n - 1 - $i];
            if ($a !== 0 && $b !== 0) {
                if ($a > $b) { $t=$a; $a=$b; $b=$t; }
                $pairs[] = [$a, $b];
            }
        }
        $rounds[] = $pairs;
        $last = array_pop($work);
        array_splice($work, 1, 0, [$last]);
    }
    return $rounds;
}

// --- Načti ligy pro ročník ---
$ligy = [];
$st = $conn->prepare("SELECT id, kod, nazev FROM n_ligy WHERE rocnik_id = ? ORDER BY poradi, id");
$st->bind_param('i', $rocnik_id);
$st->execute();
$res = $st->get_result();
while ($r = $res->fetch_assoc()) {
    $ligy[] = $r;
}
$st->close();

if (!$ligy) {
    echo "V n_ligy nejsou žádné ligy pro ročník $rocnik_id.\n";
    exit;
}

// unikátní index, aby se nerozmnožovaly duplicitní zápasy
// (pokud už ho máš, MySQL jen vypíše warning / nic)
// smaže jen plánované zápasy (nechceš mazat odehrané)
$stmt = $conn->prepare("DELETE FROM n_zapasy WHERE rocnik_id=? AND liga_id=? AND stav='plan'");
$stmt->bind_param("ii", $rocnik_id, $liga_id);
$stmt->execute();


$insertedTotal = 0;

// prepared insert
$ins = $conn->prepare(
    "INSERT IGNORE INTO n_zapasy (rocnik_id, liga_id, hrac1_id, hrac2_id, stav)
     VALUES (?, ?, ?, ?, 'plan')"
);

foreach ($ligy as $liga) {
    $ligaId = (int)$liga['id'];

    // hráči v lize (seřazeno podle jména, aby byl rozpis stabilní)
    $players = [];
    $st = $conn->prepare(
        "SELECT h.id
           FROM n_hraci_v_sezone hvs
           JOIN n_hraci h ON h.id = hvs.hrac_id
          WHERE hvs.rocnik_id = ? AND hvs.liga_id = ?
          ORDER BY h.jmeno"
    );
    $st->bind_param('ii', $rocnik_id, $ligaId);
    $st->execute();
    $res = $st->get_result();
    while ($r = $res->fetch_assoc()) {
        $players[] = (int)$r['id'];
    }
    $st->close();

    $rounds = rr_schedule($players);
    $insertedLiga = 0;
    foreach ($rounds as $pairs) {
        foreach ($pairs as [$a,$b]) {
            $ins->bind_param('iiii', $rocnik_id, $ligaId, $a, $b);
            $ins->execute();
            $insertedLiga += $ins->affected_rows; // 1 pokud vloženo, 0 pokud už existovalo
        }
    }

    $insertedTotal += $insertedLiga;
    echo "Liga {$liga['kod']} ({$liga['nazev']}): vloženo {$insertedLiga} zápasů.\n";
}

$ins->close();

echo "\nHotovo. Celkem vloženo {$insertedTotal} zápasů do n_zapasy pro ročník {$rocnik_id}.\n";
echo "Pozn.: Pokud spustíš znovu, nic se neduplikuje (INSERT IGNORE).\n";
