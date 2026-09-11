<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../security/csrf.php';
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

$role = $_SESSION['role'] ?? null;
if (!in_array($role, ['admin', 'stat_editor'], true)) {
    http_response_code(403);
    exit('Přístup zakázán');
}

$turnajId = (int)($_GET['id'] ?? 0);
if ($turnajId <= 0) exit('Neplatné ID turnaje');

$stmt = $conn->prepare('SELECT id, nazev, rocnik_id, stav, velikost_pavouka FROM turnaje WHERE id=? LIMIT 1');
$stmt->bind_param('i', $turnajId);
$stmt->execute();
$turnaj = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$turnaj) exit('Turnaj nebyl nalezen.');

$stmt = $conn->prepare(
    'SELECT COUNT(*) FROM turnaj_zapasy
      WHERE turnaj_id=? AND (skore1 IS NOT NULL OR skore2 IS NOT NULL OR vitez_id IS NOT NULL)'
);
$stmt->bind_param('i', $turnajId);
$stmt->execute();
$hasResults = (int)$stmt->get_result()->fetch_row()[0] > 0;
$stmt->close();
if (($turnaj['stav'] ?? '') !== 'priprava' || $hasResults) {
    http_response_code(409);
    exit('Ruční los už byl dokončen. Dvojice prvního kola nelze měnit.');
}

$stmt = $conn->prepare(
    'SELECT u.libovolne_id, u.jmeno
       FROM turnaj_hraci th
       JOIN hraci_unikatni_jmena u ON u.libovolne_id=th.hrac_id
      WHERE th.turnaj_id=? ORDER BY u.jmeno'
);
$stmt->bind_param('i', $turnajId);
$stmt->execute();
$players = [];
foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $player) {
    $players[(int)$player['libovolne_id']] = (string)$player['jmeno'];
}
$stmt->close();

$stmt = $conn->prepare(
    'SELECT id, poradi, hrac1_id, hrac2_id
       FROM turnaj_zapasy WHERE turnaj_id=? AND kolo=1 ORDER BY poradi'
);
$stmt->bind_param('i', $turnajId);
$stmt->execute();
$matches = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$usedPlayers = [];
foreach ($matches as $match) {
    foreach ([(int)($match['hrac1_id'] ?? 0), (int)($match['hrac2_id'] ?? 0)] as $playerId) {
        if ($playerId > 0) $usedPlayers[$playerId] = true;
    }
}
$csrf = csrf_token();
function h($value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
?>
<!doctype html>
<html lang="cs" data-theme="dark">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Ruční los prvního kola</title>
  <link rel="stylesheet" href="/liga-app/assets/admin.css?v=20260911">
  <link rel="stylesheet" href="/liga-app/assets/admin-theme.css?v=20260911">
  <script src="/liga-app/assets/admin-theme.js?v=1"></script>
  <style>
    .draw-list{display:grid;gap:10px}.draw-match{display:grid;grid-template-columns:70px minmax(180px,1fr) auto minmax(180px,1fr) auto;gap:10px;align-items:center;padding:12px;border:1px solid var(--admin-border);border-radius:12px}.draw-match select{width:100%}.draw-vs{text-align:center;font-weight:800}.draw-summary{display:flex;gap:16px;flex-wrap:wrap}.draw-summary strong{font-size:1.15rem}@media(max-width:720px){.draw-match{grid-template-columns:1fr}.draw-vs{text-align:left}.draw-match .admin-btn{width:100%}}
  </style>
</head>
<body class="admin-body"><main class="admin-shell">
  <div class="admin-top"><a href="/liga-app/admin/pohar.php?rocnik_id=<?= (int)$turnaj['rocnik_id'] ?>">← Správa poháru</a><a href="/liga-app/pohar/pohar_turnaj.php?id=<?= $turnajId ?>">Náhled pavouka</a></div>
  <h1 class="admin-title">Ruční los prvního kola</h1>
  <p class="admin-subtitle"><?= h($turnaj['nazev']) ?> · dvojice vyplňte přesně podle fyzického losování.</p>
  <?php if (($_GET['saved'] ?? '') === '1'): ?><div class="admin-alert admin-alert--success">Dvojice byla uložena.</div><?php endif; ?>
  <section class="admin-card" style="margin-bottom:14px">
    <div class="draw-summary"><span>Účastníků: <strong><?= count($players) ?></strong></span><span>Zařazeno do losu: <strong><?= count($usedPlayers) ?></strong></span><span>Zápasů 1. kola: <strong><?= count($matches) ?></strong></span></div>
    <p class="admin-help">U volného losu vyberte hráče pouze na jedné straně. Stejného hráče nelze uložit do více dvojic.</p>
  </section>

  <?php if (!$players): ?>
    <div class="admin-alert admin-alert--danger">Nejdřív se vraťte do správy poháru a vyberte účastníky.</div>
  <?php endif; ?>

  <div class="draw-list">
    <?php foreach ($matches as $match): ?>
      <form method="post" action="/liga-app/pohar/uloz_obsazeni.php" class="draw-match">
        <strong>Zápas <?= (int)$match['poradi'] ?></strong>
        <select name="hrac1_id" aria-label="První hráč zápasu <?= (int)$match['poradi'] ?>">
          <option value="">— volný los —</option>
          <?php foreach ($players as $playerId => $playerName): ?><option value="<?= $playerId ?>" <?= (int)$match['hrac1_id']===$playerId?'selected':'' ?>><?= h($playerName) ?></option><?php endforeach; ?>
        </select>
        <span class="draw-vs">vs.</span>
        <select name="hrac2_id" aria-label="Druhý hráč zápasu <?= (int)$match['poradi'] ?>">
          <option value="">— volný los —</option>
          <?php foreach ($players as $playerId => $playerName): ?><option value="<?= $playerId ?>" <?= (int)$match['hrac2_id']===$playerId?'selected':'' ?>><?= h($playerName) ?></option><?php endforeach; ?>
        </select>
        <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
        <input type="hidden" name="zapas_id" value="<?= (int)$match['id'] ?>">
        <input type="hidden" name="turnaj_id" value="<?= $turnajId ?>">
        <button class="admin-btn admin-btn--secondary" type="submit">Uložit</button>
      </form>
    <?php endforeach; ?>
  </div>

  <section class="admin-card admin-danger-zone" style="margin-top:14px">
    <h2>Dokončit ruční los</h2>
    <p>Systém zkontroluje, že je každý účastník právě jednou v prvním kole a že žádný zápas nezůstal úplně prázdný. Volné losy automaticky postoupí.</p>
    <form method="post" action="/liga-app/admin/pohar.php" onsubmit="return confirm('Opravdu dokončit ruční los a spustit turnaj? Potom už dvojice nepůjde měnit.')">
      <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
      <input type="hidden" name="action" value="finish_manual_draw">
      <input type="hidden" name="rocnik_id" value="<?= (int)$turnaj['rocnik_id'] ?>">
      <button class="admin-btn" type="submit" <?= !$players?'disabled':'' ?>>Dokončit ruční los a spustit</button>
    </form>
  </section>
</main></body></html>
