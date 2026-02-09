<?php
$hideRocnikDropdown = false;
include './header.php'; // otevírá <html><head>… i <body> a načte $BASE_URL, $conn

// === Autodetekce aktivního ročníku ===========================================
function detect_active_season(mysqli $conn): array {
  // 1) Pokud je v session platné rocnik_id, použij jej
  if (!empty($_SESSION['rocnik_id'])) {
    $id = (int)$_SESSION['rocnik_id'];
    if ($stmt = $conn->prepare("SELECT id, nazev FROM rocniky WHERE id=? LIMIT 1")) {
      $stmt->bind_param('i', $id);
      $stmt->execute();
      $res = $stmt->get_result();
      if ($row = $res->fetch_assoc()) { $stmt->close(); return ['id'=>(int)$row['id'],'nazev'=>$row['nazev']]; }
      $stmt->close();
    }
  }
  // 2) Jinak nejnovější odemčený
  if ($res = $conn->query("SELECT id, nazev FROM rocniky WHERE locked=0 ORDER BY id DESC LIMIT 1")) {
    if ($row = $res->fetch_assoc()) return ['id'=>(int)$row['id'],'nazev'=>$row['nazev']];
  }
  // 3) Fallback: úplně nejnovější
  if ($res = $conn->query("SELECT id, nazev FROM rocniky ORDER BY id DESC LIMIT 1")) {
    if ($row = $res->fetch_assoc()) return ['id'=>(int)$row['id'],'nazev'=>$row['nazev']];
  }
  return ['id'=>0,'nazev'=>'Neznámý ročník'];
}

$season         = detect_active_season($conn);
$rocnik_id      = (int)$season['id'];
$nazev_rocniku  = (string)$season['nazev'];

// === Celkem odehraných zápasů (ligy 1-5: skore 7, liga 6 ženy: skore 5) ======
$total_played = 0;
if ($stmt = $conn->prepare("
  SELECT COUNT(*) AS c
  FROM zapasy
  WHERE rocnik_id = ?
    AND ((liga_id BETWEEN 1 AND 5 AND (skore1 = 7 OR skore2 = 7))
         OR (liga_id = 6 AND (skore1 = 5 OR skore2 = 5)))
")) {
  $stmt->bind_param('i', $rocnik_id);
  $stmt->execute();
  $res = $stmt->get_result();
  $row = $res->fetch_assoc();
  $total_played = (int)($row['c'] ?? 0);
  $stmt->close();
}

// === Po ligách: odehráno / celkem ===========================================
$leagueRows = [];
if ($stmt = $conn->prepare("
  SELECT l.id, l.nazev,
         COUNT(z.id) AS total,
         SUM(CASE 
           WHEN l.id IN (1,2,3,4,5) AND (z.skore1 = 7 OR z.skore2 = 7) THEN 1
           WHEN l.id = 6 AND (z.skore1 = 5 OR z.skore2 = 5) THEN 1
           ELSE 0
         END) AS played
  FROM ligy l
  LEFT JOIN zapasy z
    ON z.liga_id = l.id AND z.rocnik_id = ?
  WHERE l.id BETWEEN 1 AND 6
  GROUP BY l.id, l.nazev
  ORDER BY l.cislo
")) {
  $stmt->bind_param('i', $rocnik_id);
  $stmt->execute();
  $res = $stmt->get_result();
  while ($row = $res->fetch_assoc()) $leagueRows[] = $row;
  $stmt->close();
}

// === Zda zobrazit TOP statistiky ============================================
$showTops = false;
if ($rocnik_id === 1) {
  $showTops = false; // Podzim 2024 neschovávat
} else {
  $sqlHasStats = "
    SELECT 1
    FROM zapasy
    WHERE rocnik_id = ?
      AND ((liga_id BETWEEN 1 AND 5) OR liga_id = 6)
      AND (
        average_home IS NOT NULL OR average_away IS NOT NULL OR
        COALESCE(high_finish_home,0) > 0 OR COALESCE(high_finish_away,0) > 0 OR
        (COALESCE(count_100p_home,0)+COALESCE(count_120p_home,0)+COALESCE(count_140p_home,0)+
         COALESCE(count_160p_home,0)+COALESCE(count_180_home,0)+
         COALESCE(count_100p_away,0)+COALESCE(count_120p_away,0)+COALESCE(count_140p_away,0)+
         COALESCE(count_160p_away,0)+COALESCE(count_180_away,0)) > 0
      )
    LIMIT 1
  ";
  if ($stmt = $conn->prepare($sqlHasStats)) {
    $stmt->bind_param('i', $rocnik_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $showTops = (bool)$res->fetch_row();
    $stmt->close();
  }
}

// === TOP statistiky (počítej jen pokud se budou zobrazovat) ==================
$topAvg = ['jmeno' => '—', 'val' => null];
$topHF  = ['jmeno' => '—', 'val' => null];
$topHB  = ['jmeno' => '—', 'val' => null];

if ($showTops) {
  // 1) Největší průměr - všechny ligy
  if ($stmt = $conn->prepare("
    SELECT h.jmeno, ROUND(AVG(a.val), 2) AS avg_val
    FROM (
      SELECT hrac1_id AS hrac_id, average_home AS val
      FROM zapasy
      WHERE rocnik_id=? AND average_home IS NOT NULL
      UNION ALL
      SELECT hrac2_id AS hrac_id, average_away AS val
      FROM zapasy
      WHERE rocnik_id=? AND average_away IS NOT NULL
    ) a
    JOIN hraci h ON h.id = a.hrac_id
    GROUP BY h.id
    ORDER BY avg_val DESC
    LIMIT 1
  ")) {
    $stmt->bind_param('ii', $rocnik_id, $rocnik_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) $topAvg = ['jmeno'=>$row['jmeno'],'val'=>$row['avg_val']];
    $stmt->close();
  }

  // 2) Největší zavření - všechny ligy
  if ($stmt = $conn->prepare("
    SELECT h.jmeno, MAX(a.val) AS hf
    FROM (
      SELECT hrac1_id AS hrac_id, high_finish_home AS val
      FROM zapasy
      WHERE rocnik_id=?
      UNION ALL
      SELECT hrac2_id AS hrac_id, high_finish_away AS val
      FROM zapasy
      WHERE rocnik_id=?
    ) a
    JOIN hraci h ON h.id = a.hrac_id
    GROUP BY h.id
    ORDER BY hf DESC
    LIMIT 1
  ")) {
    $stmt->bind_param('ii', $rocnik_id, $rocnik_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) $topHF = ['jmeno'=>$row['jmeno'],'val'=>(int)$row['hf']];
    $stmt->close();
  }

  // 3) Nejvíce „hodobody“
  $HB_WEIGHTS = ['100'=>1,'120'=>2,'140'=>3,'160'=>4,'180'=>5];
  $expr_home = "(count_100p_home*{$HB_WEIGHTS['100']} + count_120p_home*{$HB_WEIGHTS['120']} + count_140p_home*{$HB_WEIGHTS['140']} + count_160p_home*{$HB_WEIGHTS['160']} + count_180_home*{$HB_WEIGHTS['180']})";
  $expr_away = "(count_100p_away*{$HB_WEIGHTS['100']} + count_120p_away*{$HB_WEIGHTS['120']} + count_140p_away*{$HB_WEIGHTS['140']} + count_160p_away*{$HB_WEIGHTS['160']} + count_180_away*{$HB_WEIGHTS['180']})";

  $sqlHB = "
    SELECT h.jmeno, SUM(a.hb) AS body
    FROM (
      SELECT hrac1_id AS hrac_id, {$expr_home} AS hb
      FROM zapasy
      WHERE rocnik_id=?
      UNION ALL
      SELECT hrac2_id AS hrac_id, {$expr_away} AS hb
      FROM zapasy
      WHERE rocnik_id=?
    ) a
    JOIN hraci h ON h.id = a.hrac_id
    GROUP BY h.id
    ORDER BY body DESC
    LIMIT 1
  ";
  if ($stmt = $conn->prepare($sqlHB)) {
    $stmt->bind_param('ii', $rocnik_id, $rocnik_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) $topHB = ['jmeno'=>$row['jmeno'],'val'=>(int)$row['body']];
    $stmt->close();
  }
}

// === Pomocné věci pro výstup =================================================
$base = $BASE_URL ?? '/liga-app';
foreach ($leagueRows as &$r) {
  $played = (int)$r['played'];
  $total  = (int)$r['total'];
  $r['pct'] = $total > 0 ? round(100*$played/$total) : 0;
}
unset($r);
?>

<!-- ====== PANEL: Sezona / zápasy ====== -->
  <h2 class="mb-3">🎯 Vítejte na stránkách Šipky Třešť – Ligový rozcestník</h2>
<section class="panel panel-stats">
  <div class="panel-hd">
    <h2>Aktuálně probíhá sezona <?php echo htmlspecialchars($nazev_rocniku, ENT_QUOTES, 'UTF-8'); ?>.</h2>
    <p>Počet odehraných zápasů v sezoně <?php echo htmlspecialchars($nazev_rocniku, ENT_QUOTES, 'UTF-8'); ?>:
      <strong><?php echo (int)$total_played; ?></strong>
    </p>
  </div>

  <div class="league-progress">
    <?php foreach ($leagueRows as $r): ?>
      <div class="league-row">
        <div class="league-meta">
          <span class="league-title"><?php echo htmlspecialchars($r['nazev'], ENT_QUOTES, 'UTF-8'); ?></span>
          <span class="badge"><?php echo (int)$r['played']; ?> / <?php echo (int)$r['total']; ?></span>
        </div>
        <div class="progress" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?php echo $r['pct']; ?>">
          <span class="fill" style="width:<?php echo $r['pct']; ?>%"></span>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <?php if ($showTops): ?>
    <div class="tops">
      <div><strong>Hráč s největším průměrem:</strong>
        <?php echo htmlspecialchars($topAvg['jmeno'], ENT_QUOTES, 'UTF-8'); ?><?php echo $topAvg['val']!==null?' — '.$topAvg['val']:''; ?>
      </div>
      <div><strong>Hráč s největším maximálním zavřením:</strong>
        <?php echo htmlspecialchars($topHF['jmeno'], ENT_QUOTES, 'UTF-8'); ?><?php echo $topHF['val']!==null?' — '.$topHF['val']:''; ?>
      </div>
      <div><strong>Hráč s nejvíce hodobody:</strong>
        <?php echo htmlspecialchars($topHB['jmeno'], ENT_QUOTES, 'UTF-8'); ?><?php echo $topHB['val']!==null?' — '.$topHB['val']:''; ?>
      </div>
    </div>
  <?php endif; ?>
</section>

<!-- ====== INFO BLOK ======================================================== -->
<section class="panel panel-info">
  <h3>Jak na to?</h3>
  <ul class="bullets">
    <li><strong>📊 Ligové tabulky:</strong> Sledujte aktuální pořadí v 1. až 5. lize – výhry, prohry, body i rozdíly legů.</li>
    <li><strong>📅 Rozpisy zápasů:</strong> Každá liga má svůj rozpis s přehledem zápasů.</li>
    <li><strong>📈 Statistiky hráčů:</strong> Detailní přehledy o výkonech – průměr na šipku, nejvyšší zavření, počet hodobodů</li>
    <li><strong>🕓 Rezervace terčů:</strong> Pomocí jednoduchého rezervačního systému si můžete zarezervovat terč pro trénink či zápas.</li>
    <li><strong>📂 Ročníky:</strong> V pravém horním rohu si můžete přepnout mezi ročníky – například <em>Jaro 2025</em> nebo <em>Podzim 2024</em>.</li>
    <li><strong>📈 Kompletní statistiky: </strong> Zde můžete porovnat všechny hráče napříč ligami. Tabulku lze filtrovat podle několika statistik</strong></li>
</ul>
 <ul class="bullets">
    <li>Zde je odkaz na pravidla ligy pro sezonu Podzim 2025</li>
    <li>Vše je navrženo tak, aby byl přehled jednoduchý a dostupný i na mobilních zařízeních</li>
    <li>Stránka na android by vám měla automaticky nabídnout stažení funkční aplikace.</li>
    <li>Zápasy se budou doplňovat v průběhu ligy</li>
    <li>Narazíš-li na chybu nebo máš nápad na vylepšení, napiš nám prosím na Messenger „Šipky Třešť“.</li>
</section>

<!-- ====== SPONZOŘI ========================================================= -->
<?php
$sponsors = [
  ['name'=>'FPNet.cz',                 'url'=>'https://www.fpnet.cz/',    'img'=>'fpnet.png'],
  ['name'=>'Podzimek a synové s.r.o.', 'url'=>'https://www.podzimek.cz/', 'img'=>'podzimek.png'],
  ['name'=>'AUTO – MOTO – KUBA',       'url'=>'http://www.amkuba.cz/',    'img'=>'automoto.png'],
  ['name'=>'Stavebniny SYPSTAV',       'url'=>'https://www.sypstav.cz/',  'img'=>'sypstav.png'],
  ['name'=>'Restaurace U Kapra',       'url'=>'https://www.u-kapra.cz/',  'img'=>'u-kapra.png'],
  ['name'=>'Město Třešť',              'url'=>'https://www.trest.cz/',    'img'=>'trest.png'],
];
?>
<section class="panel panel-sponsors">
  <h3>Velký dík patří našim sponzorům:</h3>
  <div class="sponsor-grid">
    <?php foreach ($sponsors as $sp): ?>
      <a class="sponsor-card" href="<?php echo htmlspecialchars($sp['url'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener">
        <div class="logo-wrap">
          <img src="<?php echo htmlspecialchars(($BASE_URL ?? '/liga-app').'/sponzor/'.$sp['img'], ENT_QUOTES, 'UTF-8'); ?>"
               alt="<?php echo htmlspecialchars($sp['name'], ENT_QUOTES, 'UTF-8'); ?>">
        </div>
        <div class="sponsor-name"><?php echo htmlspecialchars($sp['name'], ENT_QUOTES, 'UTF-8'); ?></div>
      </a>
    <?php endforeach; ?>
  </div>
</section>

<?php include 'footer.php'; // zavírá </body></html> ?>
