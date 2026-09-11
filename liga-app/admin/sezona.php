<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../security/csrf.php';
require_once __DIR__ . '/season_helpers.php';

$seasonId = (int)($_REQUEST['rocnik_id'] ?? 0);
$season = admin_season($conn, $seasonId);
if (!$season) {
    http_response_code(404);
    exit('Sezóna nebyla nalezena.');
}
$editable = admin_season_is_editable($season);
$message = isset($_GET['created']) ? 'Sezóna byla vytvořena. Nyní upravte účastníky a ligy.' : '';
$error = '';

$scheduleStmt = $conn->prepare('SELECT COUNT(*) FROM zapasy WHERE rocnik_id = ?');
$scheduleStmt->bind_param('i', $seasonId);
$scheduleStmt->execute();
$hasSchedule = (int)$scheduleStmt->get_result()->fetch_row()[0] > 0;
$scheduleStmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_POST['csrf'] ?? '')) {
        http_response_code(403);
        exit('CSRF ověření selhalo.');
    }
    if (!$editable) {
        http_response_code(403);
        exit('Tuto sezónu už nelze upravovat.');
    }
    if ($hasSchedule) {
        $error = 'Před změnou účastníků nejprve smažte dosud neodehraný rozpis.';
    } else {
        $action = $_POST['action'] ?? '';
        try {
            if ($action === 'save_assignments') {
                $assignments = is_array($_POST['liga'] ?? null) ? $_POST['liga'] : [];
                $allowedLeagues = array_column(admin_season_leagues($conn, $seasonId), 'id');
                $allowedMap = array_fill_keys(array_map('intval', $allowedLeagues), true);

                $conn->begin_transaction();
                $delete = $conn->prepare('DELETE FROM hraci_v_sezone WHERE rocnik_id = ?');
                $delete->bind_param('i', $seasonId);
                $delete->execute();
                $delete->close();

                $insert = $conn->prepare('INSERT INTO hraci_v_sezone (hrac_id, rocnik_id, liga_id) VALUES (?, ?, ?)');
                foreach ($assignments as $playerId => $leagueId) {
                    $playerId = (int)$playerId;
                    $leagueId = (int)$leagueId;
                    if ($playerId <= 0 || $leagueId <= 0 || !isset($allowedMap[$leagueId])) {
                        continue;
                    }
                    $insert->bind_param('iii', $playerId, $seasonId, $leagueId);
                    $insert->execute();
                }
                $insert->close();
                $conn->commit();
                $message = 'Rozřazení hráčů bylo uloženo.';
            }

            if ($action === 'add_player') {
                $name = trim((string)($_POST['jmeno'] ?? ''));
                $leagueId = (int)($_POST['liga_id'] ?? 0);
                if ($name === '' || $leagueId <= 0) {
                    throw new RuntimeException('Zadejte jméno i cílovou ligu.');
                }
                $leagueIds = array_map('intval', array_column(admin_season_leagues($conn, $seasonId), 'id'));
                if (!in_array($leagueId, $leagueIds, true)) {
                    throw new RuntimeException('Vybraná liga nepatří do sezóny.');
                }

                $find = $conn->prepare('SELECT libovolne_id FROM hraci_unikatni_jmena WHERE LOWER(jmeno) = LOWER(?) LIMIT 1');
                $find->bind_param('s', $name);
                $find->execute();
                $found = $find->get_result()->fetch_assoc();
                $find->close();

                $conn->begin_transaction();
                if ($found) {
                    $playerId = (int)$found['libovolne_id'];
                } else {
                    $insertPlayer = $conn->prepare('INSERT INTO hraci_unikatni_jmena (jmeno) VALUES (?)');
                    $insertPlayer->bind_param('s', $name);
                    $insertPlayer->execute();
                    $playerId = (int)$conn->insert_id;
                    $insertPlayer->close();
                }
                $assign = $conn->prepare(
                    'INSERT INTO hraci_v_sezone (hrac_id, rocnik_id, liga_id) VALUES (?, ?, ?)
                     ON DUPLICATE KEY UPDATE liga_id = VALUES(liga_id)'
                );
                $assign->bind_param('iii', $playerId, $seasonId, $leagueId);
                $assign->execute();
                $assign->close();
                $conn->commit();
                $message = $found ? 'Existující hráč byl přiřazen do sezóny.' : 'Nový hráč byl vytvořen a přiřazen do sezóny.';
            }
        } catch (Throwable $e) {
            try { $conn->rollback(); } catch (Throwable $ignored) {}
            $error = $e->getMessage();
        }
    }
}

$leagues = admin_season_leagues($conn, $seasonId);
$playersStmt = $conn->prepare(
    'SELECT p.libovolne_id, p.jmeno, hs.liga_id
     FROM hraci_unikatni_jmena p
     LEFT JOIN hraci_v_sezone hs ON hs.hrac_id = p.libovolne_id AND hs.rocnik_id = ?
     ORDER BY hs.liga_id IS NULL, hs.liga_id, p.jmeno'
);
$playersStmt->bind_param('i', $seasonId);
$playersStmt->execute();
$players = $playersStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$playersStmt->close();
$csrf = csrf_token();
?>
<!doctype html><html lang="cs"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <link rel="stylesheet" href="/liga-app/assets/admin-theme.css?v=<?= (int)@filemtime(dirname(__DIR__).'/assets/admin-theme.css') ?>">
  <script src="/liga-app/assets/admin-theme.js?v=1"></script>
<title><?= htmlspecialchars($season['nazev']) ?> – správa</title><link rel="stylesheet" href="/liga-app/assets/admin.css?v=<?= (int)@filemtime(dirname(__DIR__).'/assets/admin.css') ?>"></head>
<body class="admin-body"><main class="admin-shell">
  <div class="admin-top"><a href="/liga-app/admin/sezony.php">← Sezóny</a><a href="/liga-app/admin/index.php">Dashboard</a></div>
  <h1 class="admin-title"><?= htmlspecialchars($season['nazev']) ?></h1>
  <p class="admin-subtitle"><span class="admin-badge admin-badge--<?= htmlspecialchars($season['stav']) ?>"><?= htmlspecialchars(admin_status_label($season['stav'])) ?></span> · <?= count($leagues) ?> lig</p>
  <?php if ($message): ?><div class="admin-alert admin-alert--success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
  <?php if ($error): ?><div class="admin-alert admin-alert--danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
  <?php if (!$editable): ?><div class="admin-alert">Sezóna je pouze ke čtení. Historické rozřazení a výsledky zůstávají zachované.</div><?php endif; ?>
  <?php if ($hasSchedule && $editable): ?><div class="admin-alert">Rozpis už existuje. Před přesunem nebo odebráním hráčů jej smažte na kontrolní stránce; půjde to pouze bez zapsaných výsledků.</div><?php endif; ?>

  <div class="admin-actions" style="margin-bottom:14px"><a class="admin-btn" href="/liga-app/admin/rozpis_sezony.php?rocnik_id=<?= $seasonId ?>">Kontrola a rozpis</a><a class="admin-btn admin-btn--secondary" href="/liga-app/admin/ligy.php?rocnik_id=<?= $seasonId ?>">Správa lig</a><a class="admin-btn admin-btn--secondary" href="/liga-app/admin/pohar.php?rocnik_id=<?= $seasonId ?>">Prezidentský pohár</a></div>

  <?php if ($editable && !$hasSchedule): ?><section class="admin-card" style="margin-bottom:14px"><h2>Přidat hráče</h2><form method="post"><input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>"><input type="hidden" name="action" value="add_player"><input type="hidden" name="rocnik_id" value="<?= $seasonId ?>"><div class="admin-grid admin-grid--two"><div class="admin-field"><label for="player-name">Jméno hráče</label><input id="player-name" name="jmeno" required></div><div class="admin-field"><label for="player-league">Liga</label><select id="player-league" name="liga_id" required><?php foreach ($leagues as $league): ?><option value="<?= (int)$league['id'] ?>"><?= htmlspecialchars($league['nazev']) ?></option><?php endforeach; ?></select></div></div><button class="admin-btn" type="submit">Vytvořit a přiřadit</button></form></section><?php endif; ?>

  <section class="admin-card"><h2>Účastníci a ruční přesuny</h2><p>Volba „Neúčastní se“ odebere hráče pouze z této sezóny. Jeho historie v ostatních sezónách zůstane.</p>
    <form method="post"><input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>"><input type="hidden" name="action" value="save_assignments"><input type="hidden" name="rocnik_id" value="<?= $seasonId ?>">
      <?php foreach ($players as $player): ?><div class="admin-player-row"><strong><?= htmlspecialchars($player['jmeno']) ?></strong><select name="liga[<?= (int)$player['libovolne_id'] ?>]" <?= (!$editable || $hasSchedule) ? 'disabled' : '' ?>><option value="0">Neúčastní se</option><?php foreach ($leagues as $league): ?><option value="<?= (int)$league['id'] ?>" <?= (int)$player['liga_id'] === (int)$league['id'] ? 'selected' : '' ?>><?= htmlspecialchars($league['nazev']) ?></option><?php endforeach; ?></select></div><?php endforeach; ?>
      <?php if ($editable && !$hasSchedule): ?><button class="admin-btn" type="submit" style="margin-top:14px">Uložit rozřazení</button><?php endif; ?>
    </form>
  </section>
</main></body></html>
