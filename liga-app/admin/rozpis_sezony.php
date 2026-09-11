<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../security/csrf.php';
require_once __DIR__ . '/season_helpers.php';

$seasonId = (int)($_REQUEST['rocnik_id'] ?? 0);
$season = admin_season($conn, $seasonId);
if (!$season) { http_response_code(404); exit('Sezóna nebyla nalezena.'); }
$editable = admin_season_is_editable($season);
$message = '';
$error = '';

function loadSeasonLeagueData(mysqli $conn, int $seasonId): array
{
    $result = [];
    foreach (admin_season_leagues($conn, $seasonId) as $league) {
        $leagueId = (int)$league['id'];
        $stmt = $conn->prepare(
            'SELECT p.libovolne_id AS id, p.jmeno
             FROM hraci_v_sezone hs JOIN hraci_unikatni_jmena p ON p.libovolne_id = hs.hrac_id
             WHERE hs.rocnik_id = ? AND hs.liga_id = ? ORDER BY p.jmeno'
        );
        $stmt->bind_param('ii', $seasonId, $leagueId);
        $stmt->execute();
        $players = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        $matchStmt = $conn->prepare(
            'SELECT id, hrac1_id, hrac2_id, skore1, skore2, datum, average_home, average_away, kolo
             FROM zapasy WHERE rocnik_id = ? AND liga_id = ? ORDER BY kolo, id'
        );
        $matchStmt->bind_param('ii', $seasonId, $leagueId);
        $matchStmt->execute();
        $matches = $matchStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $matchStmt->close();

        $count = count($players);
        $expected = intdiv($count * ($count - 1), 2);
        $hasResult = false;
        $uniquePairs = [];
        $hasSelfMatch = false;
        $hasMissingRound = false;
        foreach ($matches as $match) { if (admin_match_has_result($match)) { $hasResult = true; break; } }
        foreach ($matches as $match) {
            $a = (int)$match['hrac1_id'];
            $b = (int)$match['hrac2_id'];
            if ($a === $b) $hasSelfMatch = true;
            $uniquePairs[min($a, $b) . ':' . max($a, $b)] = true;
            if ($match['kolo'] === null) $hasMissingRound = true;
        }
        $scheduleValid = !$matches || (
            count($matches) === $expected
            && count($uniquePairs) === $expected
            && !$hasSelfMatch
            && !$hasMissingRound
        );
        $result[] = ['league' => $league, 'players' => $players, 'matches' => $matches, 'expected' => $expected, 'has_result' => $hasResult, 'schedule_valid' => $scheduleValid];
    }
    return $result;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_POST['csrf'] ?? '')) { http_response_code(403); exit('CSRF ověření selhalo.'); }
    if (!$editable) { http_response_code(403); exit('Rozpis archivní nebo aktivní sezóny nelze měnit.'); }
    $action = $_POST['action'] ?? '';
    try {
        $leagueData = loadSeasonLeagueData($conn, $seasonId);
        if ($action === 'generate') {
            foreach ($leagueData as $data) {
                if (count($data['players']) < 2) throw new RuntimeException($data['league']['nazev'] . ' nemá alespoň dva hráče.');
                if ($data['matches']) throw new RuntimeException('Rozpis už existuje. Nejprve jej bezpečně smažte.');
            }
            $conn->begin_transaction();
            $insert = $conn->prepare('INSERT INTO zapasy (rocnik_id, liga_id, hrac1_id, hrac2_id, kolo) VALUES (?, ?, ?, ?, ?)');
            foreach ($leagueData as $data) {
                $leagueId = (int)$data['league']['id'];
                $rounds = admin_round_robin(array_column($data['players'], 'id'));
                foreach ($rounds as $round => $pairs) {
                    foreach ($pairs as $pair) {
                        if ($pair['bye']) continue;
                        $a = (int)$pair['a']; $b = (int)$pair['b']; $roundNumber = (int)$round;
                        $insert->bind_param('iiiii', $seasonId, $leagueId, $a, $b, $roundNumber);
                        $insert->execute();
                    }
                }
            }
            $insert->close();
            $conn->commit();
            $message = 'Rozpis všech lig byl vygenerován.';
        }
        if ($action === 'delete') {
            foreach ($leagueData as $data) {
                if ($data['has_result']) throw new RuntimeException('Rozpis nelze smazat, protože už existuje zapsaný výsledek nebo statistika.');
            }
            $stmt = $conn->prepare('DELETE FROM zapasy WHERE rocnik_id = ?');
            $stmt->bind_param('i', $seasonId);
            $stmt->execute();
            $stmt->close();
            $message = 'Neodehraný rozpis byl smazán. Nyní lze upravit účastníky a vygenerovat jej znovu.';
        }
    } catch (Throwable $e) {
        try { $conn->rollback(); } catch (Throwable $ignored) {}
        $error = $e->getMessage();
    }
}

$leagueData = loadSeasonLeagueData($conn, $seasonId);
$allValid = !empty($leagueData);
$hasAnySchedule = false;
$hasAnyResult = false;
foreach ($leagueData as $data) {
    if (count($data['players']) < 2) $allValid = false;
    if ($data['matches']) $hasAnySchedule = true;
    if (!$data['schedule_valid']) $allValid = false;
    if ($data['has_result']) $hasAnyResult = true;
}
$csrf = csrf_token();
?>
  <link rel="stylesheet" href="/liga-app/assets/admin-theme.css?v=<?= (int)@filemtime(dirname(__DIR__).'/assets/admin-theme.css') ?>">
  <script src="/liga-app/assets/admin-theme.js?v=1"></script>
<!doctype html><html lang="cs"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Kontrola rozpisu</title><link rel="stylesheet" href="/liga-app/assets/admin.css?v=<?= (int)@filemtime(dirname(__DIR__).'/assets/admin.css') ?>"></head>
<body class="admin-body"><main class="admin-shell"><div class="admin-top"><a href="/liga-app/admin/sezona.php?rocnik_id=<?= $seasonId ?>">← <?= htmlspecialchars($season['nazev']) ?></a><a href="/liga-app/admin/index.php">Dashboard</a></div>
<h1 class="admin-title">Kontrola a rozpis</h1><p class="admin-subtitle"><?= htmlspecialchars($season['nazev']) ?> · každý s každým právě jednou</p>
<?php if ($message): ?><div class="admin-alert admin-alert--success"><?= htmlspecialchars($message) ?></div><?php endif; ?><?php if ($error): ?><div class="admin-alert admin-alert--danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<div class="admin-league-grid"><?php foreach ($leagueData as $data): $count=count($data['players']); $actual=count($data['matches']); $ok=$count>=2 && $data['schedule_valid']; ?><section class="admin-card"><h2><?= htmlspecialchars($data['league']['nazev']) ?></h2><p><strong><?= $count ?> hráčů</strong> · očekáváno <?= $data['expected'] ?> zápasů · uloženo <?= $actual ?></p><div class="admin-check <?= $ok?'admin-check--ok':'admin-check--bad' ?>"><?= $ok?'✓ Konzistentní':'⚠ Vyžaduje opravu' ?></div><ol class="admin-player-list"><?php foreach ($data['players'] as $player): ?><li><?= htmlspecialchars($player['jmeno']) ?></li><?php endforeach; ?></ol></section><?php endforeach; ?></div>
<?php if ($editable): ?><section class="admin-card" style="margin-top:14px"><h2>Akce rozpisu</h2><div class="admin-actions"><?php if (!$hasAnySchedule): ?><form method="post"><input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>"><input type="hidden" name="rocnik_id" value="<?= $seasonId ?>"><input type="hidden" name="action" value="generate"><button class="admin-btn" type="submit" <?= !$allValid?'disabled':'' ?>>Vygenerovat rozpis</button></form><?php else: ?><a class="admin-btn admin-btn--secondary" href="/liga-app/rozpisy/1rozpis.php">Zkontrolovat veřejný rozpis</a><form method="post" onsubmit="return confirm('Opravdu smazat celý neodehraný rozpis této sezóny?')"><input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>"><input type="hidden" name="rocnik_id" value="<?= $seasonId ?>"><input type="hidden" name="action" value="delete"><button class="admin-btn admin-btn--danger" type="submit" <?= $hasAnyResult?'disabled':'' ?>>Smazat rozpis a upravit znovu</button></form><?php endif; ?></div><?php if ($hasAnyResult): ?><p class="admin-alert admin-alert--danger">Přegenerování je trvale zablokované, protože už existuje výsledek nebo zápasová statistika.</p><?php endif; ?></section><?php endif; ?>
</main></body></html>
