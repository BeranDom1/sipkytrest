<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../common.php';
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/season_helpers.php';

$active = $conn->query("SELECT id, nazev FROM rocniky WHERE stav = 'aktivni' ORDER BY id DESC LIMIT 1")->fetch_assoc();
if (!$active) {
    $active = $conn->query('SELECT id, nazev FROM rocniky ORDER BY id DESC LIMIT 1')->fetch_assoc();
}
$activeId = (int)($active['id'] ?? 0);
$stats = ['leagues' => 0, 'players' => 0, 'matches' => 0, 'played' => 0];
if ($activeId > 0) {
    $validScoreSql = _reportable_match_score_sql($conn, $activeId, '');
    $stmt = $conn->prepare(
        'SELECT
          (SELECT COUNT(DISTINCT liga_id) FROM hraci_v_sezone WHERE rocnik_id = ?) leagues,
          (SELECT COUNT(*) FROM hraci_v_sezone WHERE rocnik_id = ?) players,
          (SELECT COUNT(*) FROM zapasy WHERE rocnik_id = ?) matches,
          (SELECT COUNT(*) FROM zapasy WHERE rocnik_id = ? AND '.$validScoreSql.') played'
    );
    $stmt->bind_param('iiii', $activeId, $activeId, $activeId, $activeId);
    $stmt->execute();
    $stats = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}
?>
  <link rel="stylesheet" href="/liga-app/assets/admin-theme.css?v=1">
  <script src="/liga-app/assets/admin-theme.js?v=1"></script>
<!doctype html><html lang="cs"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Administrace</title><link rel="stylesheet" href="/liga-app/assets/admin.css?v=1"></head>
<body class="admin-body"><main class="admin-shell">
  <div class="admin-top"><a href="/liga-app/index.php">← Veřejná část</a><a href="/liga-app/logout.php">Odhlásit</a></div>
  <h1 class="admin-title">Administrace</h1><p class="admin-subtitle">Aktivní sezóna: <strong><?= htmlspecialchars($active['nazev'] ?? 'není nastavena') ?></strong></p>
  <section class="admin-grid admin-grid--stats">
    <div class="admin-card admin-stat"><strong><?= (int)$stats['leagues'] ?></strong><span>lig</span></div>
    <div class="admin-card admin-stat"><strong><?= (int)$stats['players'] ?></strong><span>hráčů</span></div>
    <div class="admin-card admin-stat"><strong><?= (int)$stats['matches'] ?></strong><span>zápasů</span></div>
    <div class="admin-card admin-stat"><strong><?= (int)$stats['played'] ?></strong><span>odehráno</span></div>
  </section>
  <section class="admin-card" style="margin-top:14px"><h2>Sezóny a soutěž</h2><div class="admin-actions"><a class="admin-btn" href="/liga-app/admin/sezony.php">Nová sezóna / správa sezón</a><?php if ($activeId): ?><a class="admin-btn admin-btn--secondary" href="/liga-app/admin/sezona.php?rocnik_id=<?= $activeId ?>">Rozřazení hráčů</a><a class="admin-btn admin-btn--secondary" href="/liga-app/admin/rozpis_sezony.php?rocnik_id=<?= $activeId ?>">Kontrola rozpisu</a><?php endif; ?></div></section>
  <div class="admin-grid admin-grid--two" style="margin-top:14px">
    <section class="admin-card"><h2>Hráči a ligy</h2><div class="admin-actions"><a class="admin-btn admin-btn--secondary" href="/liga-app/admin/hraci.php">Databáze hráčů</a><a class="admin-btn admin-btn--secondary" href="/liga-app/admin/ligy.php<?= $activeId ? '?rocnik_id='.$activeId : '' ?>">Správa lig</a></div></section>
    <section class="admin-card"><h2>Výsledky a turnaje</h2><div class="admin-actions"><a class="admin-btn admin-btn--secondary" href="/liga-app/rozpisy/1rozpis.php">Zapsat výsledek</a><a class="admin-btn admin-btn--secondary" href="/liga-app/admin/pohar.php">Prezidentský pohár</a><a class="admin-btn admin-btn--secondary" href="/liga-app/admin/create_user.php">Uživatelé</a></div></section>
  </div>
</main></body></html>
