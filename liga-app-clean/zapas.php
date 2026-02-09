<?php
// /liga-app/zapas.php
require __DIR__ . '/header.php';   // načte session + $conn + $BASE_URL

// ===== 1) Bezpečně načti zápas jen podle ID =====
$matchId = (int)($_GET['id'] ?? 0);
if ($matchId <= 0) {
    echo '<div class="alert alert-danger">Neplatné ID zápasu.</div>';
    require __DIR__.'/footer.php'; exit;
}

$sql = "
SELECT
  z.id, z.datum, z.liga_id, z.rocnik_id,
  z.hrac1_id, z.hrac2_id,
  z.skore1, z.skore2,
  z.average_home, z.average_away,
  z.high_finish_home, z.high_finish_away,
  z.count_100p_home, z.count_100p_away,
  z.count_120p_home, z.count_120p_away,
  z.count_140p_home, z.count_140p_away,
  z.count_160p_home, z.count_160p_away,
  z.count_180_home,  z.count_180_away,
  u1.jmeno AS hrac1,
  u2.jmeno AS hrac2,
  l.cislo AS liga_cislo               -- ⬅ číslo ligy pro zobrazení (0 u Nulté)
FROM zapasy z
LEFT JOIN hraci_unikatni_jmena u1 ON u1.libovolne_id = z.hrac1_id
LEFT JOIN hraci_unikatni_jmena u2 ON u2.libovolne_id = z.hrac2_id
LEFT JOIN ligy l ON l.id = z.liga_id  -- ⬅ přidán JOIN na ligy
WHERE z.id = ?
LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $matchId);
$stmt->execute();
$z = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$z) {
    echo '<div class="alert alert-danger">Neplatné ID zápasu.</div>';
    require __DIR__.'/footer.php'; exit;
}

// ===== 2) Liga/Ročník bereme z DB záznamu =====
$liga_id     = (int)$z['liga_id'];
$rocnik_id   = (int)$z['rocnik_id'];
$liga_cislo  = isset($z['liga_cislo']) ? (int)$z['liga_cislo'] : $liga_id; // fallback

// ===== 3) Práva a edit mód =====
$isEditor = in_array($_SESSION['role'] ?? '', ['admin','stat_editor'], true);
$editMode = $isEditor && (($_GET['edit'] ?? '0') === '1');

// ===== 4) Bezpečné výstupy / helpery =====
$h1    = htmlspecialchars((string)$z['hrac1']);
$h2    = htmlspecialchars((string)$z['hrac2']);
$datum = htmlspecialchars((string)($z['datum'] ?? ''));
$rocnik= $rocnik_id;
function nf($v){ return number_format((float)$v, 2, ',', ''); }
function iv($v){ return (int)$v; }
?>
<main id="content" class="nk-content nk-content--flat">
<style>
  .match-meta{color:#6b7280;margin:.25rem 0 1rem}
  .match-actions{margin:.5rem 0 1rem;display:flex;gap:.5rem}
  .btn{display:inline-block;padding:.5rem .75rem;border:1px solid #e5e7eb;border-radius:10px;background:#fff;text-decoration:none;color:#0d47a1}
  .btn:hover{background:#eef4ff}

  /* Respektuj <colgroup> a rozděl zbytek 50/50 pro score sloupce */
  .match-table{
    width:100%;
    max-width:980px;
    margin-top:.25rem;
    table-layout:fixed !important;
  }
  .match-table col.label{width:40% !important}
  .match-table col.sep{width:1.5rem !important}
  .match-table col.score{width:calc((60% - 1.5rem)/2) !important}

  /* Fallback pro jistotu i přímo na buňkách */
  .match-table tr > td:nth-child(1){width:40% !important}
  .match-table tr > td:nth-child(3){width:1.5rem !important}
  .match-table tr > td:nth-child(2),
  .match-table tr > td:nth-child(4){width:calc((60% - 1.5rem)/2) !important}

  .match-table td{padding:.75rem 1rem;border-bottom:1px solid #e9edf3}
  .match-table tr:last-child td{border-bottom:0}
  .match-table td:nth-child(2),
  .match-table td:nth-child(4){text-align:center;font-weight:600;font-variant-numeric:tabular-nums}
  .match-table td:nth-child(3){text-align:center;color:#9ca3af;font-weight:700}

  .match-table input[type="number"],
  .match-table input[type="date"]{
    width:100%;
    min-width:0;
    max-width:100%;
    box-sizing:border-box;
    padding:.45rem .55rem;border:1px solid #d1d5db;border-radius:8px;
  }

  /* Mobilní úpravy */
  @media (max-width:640px){
    .match-table col.label{width:36% !important}
    .match-table col.sep{width:1.25rem !important}
    .match-table col.score{width:calc((64% - 1.25rem)/2) !important}

    .match-table tr > td:nth-child(1){width:36% !important}
    .match-table tr > td:nth-child(3){width:1.25rem !important}
    .match-table tr > td:nth-child(2),
    .match-table tr > td:nth-child(4){width:calc((64% - 1.25rem)/2) !important}
  }
</style>

  <h1><?= $h1 ?> vs. <?= $h2 ?></h1>
  <div class="match-meta">
    Datum: <?= $datum ?> | Liga: <?= $liga_cislo ?> | Ročník: <?= $rocnik ?>
  </div>

  <?php if ($isEditor): ?>
    <div class="match-actions">
      <?php if ($editMode): ?>
        <a class="btn" href="<?= htmlspecialchars($BASE_URL) ?>/zapas.php?id=<?= $matchId ?>">Hotovo</a>
      <?php else: ?>
        <a class="btn" href="<?= htmlspecialchars($BASE_URL) ?>/zapas.php?id=<?= $matchId ?>&edit=1">Upravit</a>
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <div class="table-wrap">
    <table class="table match-table">
      <colgroup>
        <col class="label"><col class="score"><col class="sep"><col class="score">
      </colgroup>
      <tbody>
        <?php if ($editMode): ?>
          <form method="post" action="<?= htmlspecialchars($BASE_URL) ?>/save_stats.php">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf'] ?? '') ?>">
            <input type="hidden" name="match_id" value="<?= $matchId ?>">

            <tr>
              <td>Datum</td>
              <td colspan="3" style="text-align:left">
                <input type="date" name="datum" value="<?= htmlspecialchars($z['datum'] ?? '') ?>">
              </td>
            </tr>

            <tr>
              <td>Skóre</td>
              <td><input type="number" name="skore1" min="0" max="7" value="<?= iv($z['skore1']) ?>"></td>
              <td>:</td>
              <td><input type="number" name="skore2" min="0" max="7" value="<?= iv($z['skore2']) ?>"></td>
            </tr>

            <tr>
              <td>Průměr</td>
              <td><input type="number" step="0.01" min="0" max="180" name="average_home" value="<?= (float)$z['average_home'] ?>"></td>
              <td></td>
              <td><input type="number" step="0.01" min="0" max="180" name="average_away" value="<?= (float)$z['average_away'] ?>"></td>
            </tr>

            <tr>
              <td>Nejvyšší zavření</td>
              <td><input type="number" min="0" max="170" name="high_finish_home" value="<?= iv($z['high_finish_home']) ?>"></td>
              <td></td>
              <td><input type="number" min="0" max="170" name="high_finish_away" value="<?= iv($z['high_finish_away']) ?>"></td>
            </tr>

            <tr>
              <td>100+</td>
              <td><input type="number" min="0" max="99" name="count_100p_home" value="<?= iv($z['count_100p_home']) ?>"></td>
              <td></td>
              <td><input type="number" min="0" max="99" name="count_100p_away" value="<?= iv($z['count_100p_away']) ?>"></td>
            </tr>
            <tr>
              <td>140+</td>
              <td><input type="number" min="0" max="99" name="count_140p_home" value="<?= iv($z['count_140p_home']) ?>"></td>
              <td></td>
              <td><input type="number" min="0" max="99" name="count_140p_away" value="<?= iv($z['count_140p_away']) ?>"></td>
            </tr>
            <tr>
              <td>160+</td>
              <td><input type="number" min="0" max="99" name="count_160p_home" value="<?= iv($z['count_160p_home']) ?>"></td>
              <td></td>
              <td><input type="number" min="0" max="99" name="count_160p_away" value="<?= iv($z['count_160p_away']) ?>"></td>
            </tr>
            <tr>
              <td>180+</td>
              <td><input type="number" min="0" max="99" name="count_180_home" value="<?= iv($z['count_180_home']) ?>"></td>
              <td></td>
              <td><input type="number" min="0" max="99" name="count_180_away" value="<?= iv($z['count_180_away']) ?>"></td>
            </tr>

            <tr>
              <td></td>
              <td colspan="3" style="text-align:right">
                <a class="btn" href="<?= htmlspecialchars($BASE_URL) ?>/zapas.php?id=<?= $matchId ?>">Zrušit</a>
                <button type="submit" class="btn">Uložit</button>
              </td>
            </tr>
          </form>
        <?php else: ?>
          <tr>
            <td>Skóre</td>
            <td><?= iv($z['skore1']) ?></td>
            <td>:</td>
            <td><?= iv($z['skore2']) ?></td>
          </tr>
          <tr>
            <td>Průměr</td>
            <td><?= nf($z['average_home']) ?></td>
            <td></td>
            <td><?= nf($z['average_away']) ?></td>
          </tr>
          <tr>
            <td>Nejvyšší zavření</td>
            <td><?= iv($z['high_finish_home']) ?></td>
            <td></td>
            <td><?= iv($z['high_finish_away']) ?></td>
          </tr>
          <tr>
            <td>100+</td>
            <td><?= iv($z['count_100p_home']) ?></td>
            <td></td>
            <td><?= iv($z['count_100p_away']) ?></td>
          </tr>
          <tr>
            <td>140+</td>
            <td><?= iv($z['count_140p_home']) ?></td>
            <td></td>
            <td><?= iv($z['count_140p_away']) ?></td>
          </tr>
          <tr>
            <td>160+</td>
            <td><?= iv($z['count_160p_home']) ?></td>
            <td></td>
            <td><?= iv($z['count_160p_away']) ?></td>
          </tr>
          <tr>
            <td>180+</td>
            <td><?= iv($z['count_180_home']) ?></td>
            <td></td>
            <td><?= iv($z['count_180_away']) ?></td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</main>
<?php require __DIR__ . '/footer.php'; ?>
