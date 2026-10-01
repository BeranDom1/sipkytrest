<?php
session_start();
require_once __DIR__ . '/../security/csrf.php';

$role = $_SESSION['role'] ?? null;
if (!in_array($role, ['admin', 'stat_editor'], true)) {
    http_response_code(403);
    exit('Přístup zakázán');
}

csrf_validate_or_die();
require __DIR__ . '/../db.php';

$turnajId = (int)($_POST['turnaj_id'] ?? 0);
$submitted = is_array($_POST['obsazeni'] ?? null) ? $_POST['obsazeni'] : [];

function drawRedirect(int $turnajId, string $message = ''): never
{
    $location = '/liga-app/pohar/pohar_1kolo_admin.php?id='.$turnajId;
    if ($message !== '') $location .= '&error='.rawurlencode($message);
    header('Location: '.$location);
    exit;
}

if ($turnajId <= 0) drawRedirect($turnajId, 'Neplatné ID turnaje.');

$conn->begin_transaction();
try {
    $turnajStmt = $conn->prepare('SELECT stav FROM turnaje WHERE id=? FOR UPDATE');
    $turnajStmt->bind_param('i', $turnajId);
    $turnajStmt->execute();
    $turnaj = $turnajStmt->get_result()->fetch_assoc();
    $turnajStmt->close();
    if (!$turnaj || ($turnaj['stav'] ?? '') !== 'priprava') {
        throw new RuntimeException('Ruční los už nelze upravovat.');
    }

    $resultStmt = $conn->prepare(
        'SELECT COUNT(*) FROM turnaj_zapasy
          WHERE turnaj_id=? AND (skore1 IS NOT NULL OR skore2 IS NOT NULL OR vitez_id IS NOT NULL)'
    );
    $resultStmt->bind_param('i', $turnajId);
    $resultStmt->execute();
    $hasResults = (int)$resultStmt->get_result()->fetch_row()[0] > 0;
    $resultStmt->close();
    if ($hasResults) throw new RuntimeException('Los už byl dokončen nebo turnaj obsahuje výsledky.');

    $allowedStmt = $conn->prepare('SELECT hrac_id FROM turnaj_hraci WHERE turnaj_id=?');
    $allowedStmt->bind_param('i', $turnajId);
    $allowedStmt->execute();
    $allowedPlayers = array_fill_keys(
        array_map('intval', array_column($allowedStmt->get_result()->fetch_all(MYSQLI_ASSOC), 'hrac_id')),
        true
    );
    $allowedStmt->close();

    $matchesStmt = $conn->prepare(
        'SELECT id, poradi FROM turnaj_zapasy
          WHERE turnaj_id=? AND kolo=1 ORDER BY poradi FOR UPDATE'
    );
    $matchesStmt->bind_param('i', $turnajId);
    $matchesStmt->execute();
    $matches = $matchesStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $matchesStmt->close();
    if (!$matches) throw new RuntimeException('První kolo neobsahuje žádné zápasy.');

    $expectedIds = array_fill_keys(array_map('intval', array_column($matches, 'id')), true);
    $submittedIds = array_fill_keys(array_map('intval', array_keys($submitted)), true);
    if (array_diff_key($expectedIds, $submittedIds) || array_diff_key($submittedIds, $expectedIds)) {
        throw new RuntimeException('Formulář neobsahuje kompletní seznam zápasů. Obnovte stránku a zkuste to znovu.');
    }

    $usedPlayers = [];
    $normalized = [];
    foreach ($matches as $match) {
        $matchId = (int)$match['id'];
        $order = (int)$match['poradi'];
        $row = is_array($submitted[$matchId] ?? null) ? $submitted[$matchId] : [];
        $h1 = max(0, (int)($row['hrac1_id'] ?? 0));
        $h2 = max(0, (int)($row['hrac2_id'] ?? 0));

        if ($h1 > 0 && $h1 === $h2) {
            throw new RuntimeException('V zápasu '.$order.' nemůže hráč hrát sám proti sobě.');
        }

        foreach ([$h1, $h2] as $playerId) {
            if ($playerId <= 0) continue;
            if (!isset($allowedPlayers[$playerId])) {
                throw new RuntimeException('V zápasu '.$order.' je hráč, který není v seznamu účastníků.');
            }
            if (isset($usedPlayers[$playerId])) {
                throw new RuntimeException('Jeden hráč je v losu uveden vícekrát.');
            }
            $usedPlayers[$playerId] = true;
        }

        $hasExactlyOnePlayer = ($h1 > 0) xor ($h2 > 0);
        $normalized[$matchId] = [
            'h1' => $h1 > 0 ? $h1 : ($hasExactlyOnePlayer ? 0 : null),
            'h2' => $h2 > 0 ? $h2 : ($hasExactlyOnePlayer ? 0 : null),
        ];
    }

    $update = $conn->prepare(
        'UPDATE turnaj_zapasy SET hrac1_id=?, hrac2_id=?
          WHERE id=? AND turnaj_id=? AND kolo=1 AND vitez_id IS NULL'
    );
    foreach ($normalized as $matchId => $players) {
        $h1 = $players['h1'];
        $h2 = $players['h2'];
        $update->bind_param('iiii', $h1, $h2, $matchId, $turnajId);
        $update->execute();
    }
    $update->close();
    $conn->commit();

    header('Location: /liga-app/pohar/pohar_1kolo_admin.php?id='.$turnajId.'&saved=1');
    exit;
} catch (Throwable $e) {
    $conn->rollback();
    drawRedirect($turnajId, $e->getMessage());
}
