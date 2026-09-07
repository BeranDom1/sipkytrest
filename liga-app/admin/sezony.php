<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../security/csrf.php';
require_once __DIR__ . '/season_helpers.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_POST['csrf'] ?? '')) {
        http_response_code(403);
        exit('CSRF ověření selhalo.');
    }

    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'create') {
            $name = trim((string)($_POST['nazev'] ?? ''));
            $sourceId = (int)($_POST['source_rocnik_id'] ?? 0);
            if ($name === '' || mb_strlen($name) > 100) {
                throw new RuntimeException('Zadejte platný název sezóny.');
            }

            $check = $conn->prepare('SELECT id FROM rocniky WHERE LOWER(nazev) = LOWER(?) LIMIT 1');
            $check->bind_param('s', $name);
            $check->execute();
            if ($check->get_result()->fetch_assoc()) {
                throw new RuntimeException('Sezóna s tímto názvem už existuje.');
            }
            $check->close();

            if ($sourceId > 0 && !admin_season($conn, $sourceId)) {
                throw new RuntimeException('Zdrojová sezóna neexistuje.');
            }

            $conn->begin_transaction();
            $insert = $conn->prepare("INSERT INTO rocniky (nazev, locked, stav) VALUES (?, 0, 'priprava')");
            $insert->bind_param('s', $name);
            $insert->execute();
            $newId = (int)$conn->insert_id;
            $insert->close();

            if ($sourceId > 0) {
                $copyNames = $conn->prepare(
                    'INSERT INTO ligy_nazvy (rocnik_id, liga_id, nazev)
                     SELECT ?, liga_id, nazev FROM ligy_nazvy WHERE rocnik_id = ?'
                );
                $copyNames->bind_param('ii', $newId, $sourceId);
                $copyNames->execute();
                $copiedNames = $copyNames->affected_rows;
                $copyNames->close();

                if ($copiedNames === 0) {
                    $fallback = $conn->prepare(
                        'INSERT INTO ligy_nazvy (rocnik_id, liga_id, nazev)
                         SELECT ?, id, nazev FROM ligy ORDER BY poradi'
                    );
                    $fallback->bind_param('i', $newId);
                    $fallback->execute();
                    $fallback->close();
                }

                $copyPlayers = $conn->prepare(
                    'INSERT INTO hraci_v_sezone (hrac_id, rocnik_id, liga_id, poznamka)
                     SELECT hrac_id, ?, liga_id, poznamka FROM hraci_v_sezone WHERE rocnik_id = ?'
                );
                $copyPlayers->bind_param('ii', $newId, $sourceId);
                $copyPlayers->execute();
                $copyPlayers->close();

                $copyLogos = $conn->prepare(
                    'INSERT INTO ligy_loga (rocnik_id, liga_id, logo, alt)
                     SELECT ?, liga_id, logo, alt FROM ligy_loga WHERE rocnik_id = ?'
                );
                $copyLogos->bind_param('ii', $newId, $sourceId);
                $copyLogos->execute();
                $copyLogos->close();
            } else {
                $fallback = $conn->prepare(
                    'INSERT INTO ligy_nazvy (rocnik_id, liga_id, nazev)
                     SELECT ?, id, nazev FROM ligy ORDER BY poradi'
                );
                $fallback->bind_param('i', $newId);
                $fallback->execute();
                $fallback->close();
            }

            $conn->commit();
            header('Location: /liga-app/admin/sezona.php?rocnik_id=' . $newId . '&created=1');
            exit;
        }

        if ($action === 'activate') {
            $seasonId = (int)($_POST['rocnik_id'] ?? 0);
            $season = admin_season($conn, $seasonId);
            if (!$season || !admin_season_is_editable($season)) {
                throw new RuntimeException('Aktivovat lze pouze sezónu ve stavu Příprava.');
            }

            $leagues = admin_season_leagues($conn, $seasonId);
            if (!$leagues) {
                throw new RuntimeException('Sezóna nemá žádné ligy.');
            }
            foreach ($leagues as $league) {
                $leagueId = (int)$league['id'];
                $countStmt = $conn->prepare('SELECT COUNT(*) FROM hraci_v_sezone WHERE rocnik_id = ? AND liga_id = ?');
                $countStmt->bind_param('ii', $seasonId, $leagueId);
                $countStmt->execute();
                $players = (int)$countStmt->get_result()->fetch_row()[0];
                $countStmt->close();
                $expected = intdiv($players * ($players - 1), 2);

                $matchStmt = $conn->prepare(
                    "SELECT COUNT(*) AS total,
                            COUNT(DISTINCT CONCAT(LEAST(hrac1_id, hrac2_id), ':', GREATEST(hrac1_id, hrac2_id))) AS unique_pairs,
                            SUM(hrac1_id = hrac2_id) AS self_matches,
                            SUM(kolo IS NULL) AS missing_rounds
                     FROM zapasy WHERE rocnik_id = ? AND liga_id = ?"
                );
                $matchStmt->bind_param('ii', $seasonId, $leagueId);
                $matchStmt->execute();
                $matchMetrics = $matchStmt->get_result()->fetch_assoc();
                $matchStmt->close();
                if ($players < 2
                    || (int)$matchMetrics['total'] !== $expected
                    || (int)$matchMetrics['unique_pairs'] !== $expected
                    || (int)$matchMetrics['self_matches'] !== 0
                    || (int)$matchMetrics['missing_rounds'] !== 0) {
                    throw new RuntimeException('Nejprve zkontrolujte a vygenerujte kompletní rozpis všech lig.');
                }
            }

            $conn->begin_transaction();
            $conn->query("UPDATE rocniky SET stav = 'archivni', locked = 1 WHERE stav = 'aktivni'");
            $activate = $conn->prepare("UPDATE rocniky SET stav = 'aktivni', locked = 0 WHERE id = ?");
            $activate->bind_param('i', $seasonId);
            $activate->execute();
            $activate->close();
            $conn->commit();
            $_SESSION['rocnik_id'] = $seasonId;
            $message = 'Sezóna byla aktivována a je nyní výchozí pro veřejnou část.';
        }
    } catch (Throwable $e) {
        try { $conn->rollback(); } catch (Throwable $ignored) {}
        $error = $e->getMessage();
    }
}

$seasons = $conn->query(
    "SELECT r.id, r.nazev, r.locked, r.stav,
            COUNT(DISTINCT hs.hrac_id) AS players,
            COUNT(DISTINCT hs.liga_id) AS leagues,
            COUNT(DISTINCT z.id) AS matches
     FROM rocniky r
     LEFT JOIN hraci_v_sezone hs ON hs.rocnik_id = r.id
     LEFT JOIN zapasy z ON z.rocnik_id = r.id
     GROUP BY r.id, r.nazev, r.locked, r.stav
     ORDER BY r.id DESC"
)->fetch_all(MYSQLI_ASSOC);
$csrf = csrf_token();
?>
<!doctype html><html lang="cs"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <link rel="stylesheet" href="/liga-app/assets/admin-theme.css?v=1">
  <script src="/liga-app/assets/admin-theme.js?v=1"></script>
<title>Správa sezón</title><link rel="stylesheet" href="/liga-app/assets/admin.css?v=1"></head>
<body class="admin-body"><main class="admin-shell">
  <div class="admin-top"><a href="/liga-app/admin/index.php">← Administrace</a><a href="/liga-app/index.php">Veřejná část</a></div>
  <h1 class="admin-title">Správa sezón</h1>
  <p class="admin-subtitle">Založení, příprava a aktivace sezóny bez ručních zásahů do databáze.</p>
  <?php if ($message): ?><div class="admin-alert admin-alert--success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
  <?php if ($error): ?><div class="admin-alert admin-alert--danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

  <div class="admin-grid admin-grid--two">
    <section class="admin-card">
      <h2>Nová prázdná sezóna</h2>
      <form method="post">
        <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>"><input type="hidden" name="action" value="create"><input type="hidden" name="source_rocnik_id" value="0">
        <div class="admin-field"><label for="empty-name">Název</label><input id="empty-name" name="nazev" placeholder="Podzim 2026" required maxlength="100"></div>
        <button class="admin-btn" type="submit">Vytvořit prázdnou sezónu</button>
      </form>
    </section>
    <section class="admin-card">
      <h2>Vytvořit z předchozí sezóny</h2>
      <p>Přenese ligy, názvy, loga a účastníky. Zápasy ani statistiky se nekopírují.</p>
      <form method="post">
        <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>"><input type="hidden" name="action" value="create">
        <div class="admin-field"><label for="copy-name">Název nové sezóny</label><input id="copy-name" name="nazev" placeholder="Podzim 2026" required maxlength="100"></div>
        <div class="admin-field"><label for="source">Zdrojová sezóna</label><select id="source" name="source_rocnik_id" required><?php foreach ($seasons as $season): ?><option value="<?= (int)$season['id'] ?>"><?= htmlspecialchars($season['nazev']) ?></option><?php endforeach; ?></select></div>
        <button class="admin-btn" type="submit">Vytvořit z předchozí sezóny</button>
      </form>
    </section>
  </div>

  <section class="admin-card" style="margin-top:14px"><h2>Existující sezóny</h2><div class="admin-list">
    <?php foreach ($seasons as $season): ?>
      <article class="admin-season"><div><h3><?= htmlspecialchars($season['nazev']) ?> <span class="admin-badge admin-badge--<?= htmlspecialchars($season['stav']) ?>"><?= htmlspecialchars(admin_status_label($season['stav'])) ?></span></h3><div class="admin-season__meta"><?= (int)$season['leagues'] ?> lig · <?= (int)$season['players'] ?> hráčů · <?= (int)$season['matches'] ?> zápasů</div></div>
      <div class="admin-actions"><a class="admin-btn admin-btn--secondary" href="/liga-app/admin/sezona.php?rocnik_id=<?= (int)$season['id'] ?>">Spravovat</a><?php if ($season['stav'] === 'priprava'): ?><form method="post"><input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>"><input type="hidden" name="action" value="activate"><input type="hidden" name="rocnik_id" value="<?= (int)$season['id'] ?>"><button class="admin-btn" type="submit">Aktivovat sezónu</button></form><?php endif; ?></div></article>
    <?php endforeach; ?>
  </div></section>
</main></body></html>
