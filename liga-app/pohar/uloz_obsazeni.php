<?php
session_start();
require_once __DIR__ . '/../security/csrf.php';

$role = $_SESSION['role'] ?? null;
if (!in_array($role, ['admin', 'stat_editor'])) {
    http_response_code(403);
    exit('Přístup zakázán');
}

csrf_validate_or_die();

require __DIR__ . '/../db.php';

$zapas_id  = (int)$_POST['zapas_id'];
$turnaj_id = (int)$_POST['turnaj_id'];
$h1 = (int)($_POST['hrac1_id'] ?? 0);
$h2 = (int)($_POST['hrac2_id'] ?? 0);

if ($h1 && $h1 === $h2) {
    exit('Hráč nemůže hrát sám proti sobě');
}

$stmt = $conn->prepare(
    "SELECT z.id FROM turnaj_zapasy z
     JOIN turnaje t ON t.id=z.turnaj_id
     WHERE z.id=? AND z.turnaj_id=? AND z.kolo=1 AND t.stav='priprava' LIMIT 1"
);
$stmt->bind_param('ii', $zapas_id, $turnaj_id);
$stmt->execute();
$editableMatch = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$editableMatch) {
    http_response_code(409);
    exit('Tento zápas už nelze upravovat.');
}

$resultStmt = $conn->prepare(
    'SELECT COUNT(*) FROM turnaj_zapasy
      WHERE turnaj_id=? AND (skore1 IS NOT NULL OR skore2 IS NOT NULL OR vitez_id IS NOT NULL)'
);
$resultStmt->bind_param('i', $turnaj_id);
$resultStmt->execute();
$hasResults = (int)$resultStmt->get_result()->fetch_row()[0] > 0;
$resultStmt->close();
if ($hasResults) {
    http_response_code(409);
    exit('Los už byl dokončen nebo turnaj obsahuje výsledky.');
}

foreach ([$h1, $h2] as $playerId) {
    if ($playerId <= 0) continue;
    $allowedStmt = $conn->prepare('SELECT 1 FROM turnaj_hraci WHERE turnaj_id=? AND hrac_id=? LIMIT 1');
    $allowedStmt->bind_param('ii', $turnaj_id, $playerId);
    $allowedStmt->execute();
    $allowed = (bool)$allowedStmt->get_result()->fetch_row();
    $allowedStmt->close();
    if (!$allowed) exit('Vybraný hráč není v seznamu účastníků.');

    $duplicateStmt = $conn->prepare(
        'SELECT COUNT(*) FROM turnaj_zapasy
          WHERE turnaj_id=? AND kolo=1 AND id<>? AND (hrac1_id=? OR hrac2_id=?)'
    );
    $duplicateStmt->bind_param('iiii', $turnaj_id, $zapas_id, $playerId, $playerId);
    $duplicateStmt->execute();
    $duplicate = (int)$duplicateStmt->get_result()->fetch_row()[0] > 0;
    $duplicateStmt->close();
    if ($duplicate) exit('Hráč už je v jiné dvojici prvního kola.');
}

/* uložení */
$h1Value = $h1 > 0 ? $h1 : null;
$h2Value = $h2 > 0 ? $h2 : null;
$stmt = $conn->prepare("
    UPDATE turnaj_zapasy
    SET hrac1_id = ?, hrac2_id = ?
    WHERE id = ? AND turnaj_id=? AND kolo=1 AND vitez_id IS NULL
");
$stmt->bind_param('iiii', $h1Value, $h2Value, $zapas_id, $turnaj_id);
$stmt->execute();
$stmt->close();

header('Location: pohar_1kolo_admin.php?id='.$turnaj_id.'&saved=1');
exit;
