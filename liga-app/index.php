<?php
$hideRocnikDropdown = false;
include './header.php'; // Obsahuje <html>, <body>, $BASE_URL, $conn

/**
 * ============================================================================
 * POMOCNÉ FUNKCE
 * ============================================================================
 */

// Autodetekce aktivního ročníku
function detect_active_season(mysqli $conn): array {
    if (!empty($_SESSION['rocnik_id'])) {
        $id = (int)$_SESSION['rocnik_id'];
        $st = $conn->prepare("SELECT id, nazev FROM rocniky WHERE id=? LIMIT 1");
        $st->bind_param('i', $id);
        $st->execute();
        if ($r = $st->get_result()->fetch_assoc()) {
            $st->close();
            return ['id' => (int)$r['id'], 'nazev' => $r['nazev']];
        }
        $st->close();
    }

    $res = $conn->query("SELECT id, nazev FROM rocniky WHERE locked=0 ORDER BY id DESC LIMIT 1");
    if ($r = $res->fetch_assoc()) return ['id' => (int)$r['id'], 'nazev' => $r['nazev']];

    $res = $conn->query("SELECT id, nazev FROM rocniky ORDER BY id DESC LIMIT 1");
    if ($r = $res->fetch_assoc()) return ['id' => (int)$r['id'], 'nazev' => $r['nazev']];

    return ['id' => 0, 'nazev' => 'Neznámý ročník'];
}

// Výpočet celkového počtu zápasů v lize (každý s každým dvoukolově/jednokolově dle logiky)
function league_total_matches(mysqli $conn, int $rocnik_id, int $liga_id): int {
    $st = $conn->prepare("SELECT COUNT(*) AS cnt FROM hraci_v_sezone WHERE rocnik_id = ? AND liga_id = ?");
    $st->bind_param('ii', $rocnik_id, $liga_id);
    $st->execute();
    $cnt = (int)$st->get_result()->fetch_assoc()['cnt'];
    $st->close();
    return ($cnt > 1) ? ($cnt * ($cnt - 1)) / 2 : 0;
}

/**
 * ============================================================================
 * DATA PRO STRÁNKU
 * ============================================================================
 */

$season         = detect_active_season($conn);
$rocnik_id      = (int)$season['id'];
$nazev_rocniku  = (string)$season['nazev'];
$base           = $BASE_URL ?? '/liga-app';
$WOMEN_LIGA_ID = 6; // Holoubek a Svoboda – liga ženy
// Celkem odehraných zápasů (ligy 1-5 = skore 7, liga 6 = skore 5)
$st = $conn->prepare("SELECT COUNT(*) AS c FROM zapasy WHERE rocnik_id = ? AND (
  (liga_id BETWEEN 1 AND 5 AND (skore1 = 7 OR skore2 = 7)) OR 
  (liga_id = 6 AND (skore1 = 5 OR skore2 = 5))
)");
$st->bind_param('i', $rocnik_id);
$st->execute();
$total_played = (int)$st->get_result()->fetch_assoc()['c'];
$st->close();

// TOP Statistiky - Inicializace
$showTops = false;
$topAvg = ['jmeno' => '—', 'val' => null];
$topHF  = ['jmeno' => '—', 'val' => null];
$topHB  = ['jmeno' => '—', 'val' => null];

if ($rocnik_id !== 1) {
    $st = $conn->prepare("SELECT 1 FROM zapasy WHERE rocnik_id = ? AND (
      (liga_id BETWEEN 1 AND 5 AND (skore1 = 7 OR skore2 = 7)) OR 
      (liga_id = 6 AND (skore1 = 5 OR skore2 = 5))
    ) AND (average_home IS NOT NULL OR average_away IS NOT NULL) LIMIT 1");
    $st->bind_param('i', $rocnik_id);
    $st->execute();
    $showTops = (bool)$st->get_result()->fetch_row();
    $st->close();
}

if ($showTops) {
    // 1. TOP Průměr (bráno všechny ligy, správné skóre dle typu)
    $st = $conn->prepare("SELECT u.jmeno, ROUND(AVG(a.val),2) AS avg_val FROM (
        SELECT hrac1_id AS hid, average_home AS val FROM zapasy WHERE rocnik_id=? AND average_home IS NOT NULL AND (
          (liga_id BETWEEN 1 AND 5 AND (skore1 = 7 OR skore2 = 7)) OR 
          (liga_id = 6 AND (skore1 = 5 OR skore2 = 5))
        )
        UNION ALL
        SELECT hrac2_id AS hid, average_away AS val FROM zapasy WHERE rocnik_id=? AND average_away IS NOT NULL AND (
          (liga_id BETWEEN 1 AND 5 AND (skore1 = 7 OR skore2 = 7)) OR 
          (liga_id = 6 AND (skore1 = 5 OR skore2 = 5))
        )
    ) a JOIN hraci_unikatni_jmena u ON u.libovolne_id = a.hid GROUP BY u.libovolne_id, u.jmeno ORDER BY avg_val DESC LIMIT 1");
    $st->bind_param('ii', $rocnik_id, $rocnik_id);
    $st->execute();
    if ($r = $st->get_result()->fetch_assoc()) $topAvg = ['jmeno' => $r['jmeno'], 'val' => $r['avg_val']];
    $st->close();

    // 2. TOP Zavření (High Finish) - všechny ligy
    $st = $conn->prepare("SELECT u.jmeno, MAX(a.val) AS hf FROM (
        SELECT hrac1_id AS hid, high_finish_home AS val FROM zapasy WHERE rocnik_id=? AND (
          (liga_id BETWEEN 1 AND 5 AND (skore1 = 7 OR skore2 = 7)) OR 
          (liga_id = 6 AND (skore1 = 5 OR skore2 = 5))
        )
        UNION ALL
        SELECT hrac2_id AS hid, high_finish_away AS val FROM zapasy WHERE rocnik_id=? AND (
          (liga_id BETWEEN 1 AND 5 AND (skore1 = 7 OR skore2 = 7)) OR 
          (liga_id = 6 AND (skore1 = 5 OR skore2 = 5))
        )
    ) a JOIN hraci_unikatni_jmena u ON u.libovolne_id = a.hid GROUP BY u.libovolne_id, u.jmeno ORDER BY hf DESC LIMIT 1");
    $st->bind_param('ii', $rocnik_id, $rocnik_id);
    $st->execute();
    if ($r = $st->get_result()->fetch_assoc()) $topHF = ['jmeno' => $r['jmeno'], 'val' => (int)$r['hf']];
    $st->close();

    // 3. TOP Hodobody - všechny ligy
    $st = $conn->prepare("SELECT u.jmeno, SUM(a.body) AS body FROM (
        SELECT hrac1_id AS hid, COALESCE(count_100p_home,0)*1 + COALESCE(count_120p_home,0)*2 + COALESCE(count_140p_home,0)*3 + COALESCE(count_160p_home,0)*4 + COALESCE(count_180_home,0)*5 AS body FROM zapasy WHERE rocnik_id=? AND (
          (liga_id BETWEEN 1 AND 5 AND (skore1 = 7 OR skore2 = 7)) OR 
          (liga_id = 6 AND (skore1 = 5 OR skore2 = 5))
        )
        UNION ALL
        SELECT hrac2_id AS hid, COALESCE(count_100p_away,0)*1 + COALESCE(count_120p_away,0)*2 + COALESCE(count_140p_away,0)*3 + COALESCE(count_160p_away,0)*4 + COALESCE(count_180_away,0)*5 AS body FROM zapasy WHERE rocnik_id=? AND (
          (liga_id BETWEEN 1 AND 5 AND (skore1 = 7 OR skore2 = 7)) OR 
          (liga_id = 6 AND (skore1 = 5 OR skore2 = 5))
        )
    ) a JOIN hraci_unikatni_jmena u ON u.libovolne_id = a.hid GROUP BY u.libovolne_id, u.jmeno ORDER BY body DESC LIMIT 1");
    $st->bind_param('ii', $rocnik_id, $rocnik_id);
    $st->execute();
    if ($r = $st->get_result()->fetch_assoc()) $topHB = ['jmeno' => $r['jmeno'], 'val' => (int)$r['body']];
    $st->close();
}

// Načtení lig a progresu
$leagueRows = [];
$st = $conn->prepare("SELECT 1 FROM ligy_nazvy WHERE rocnik_id=? LIMIT 1");
$st->bind_param('i', $rocnik_id);
$st->execute();
$hasLigaNazvy = (bool)$st->get_result()->fetch_row();
$st->close();

if ($hasLigaNazvy) {
    $st = $conn->prepare("SELECT ln.liga_id AS id, ln.nazev, SUM(
      CASE 
        WHEN ln.liga_id IN (1,2,3,4,5) AND (z.skore1=7 OR z.skore2=7) THEN 1
        WHEN ln.liga_id=6 AND (z.skore1=5 OR z.skore2=5) THEN 1
        ELSE 0 
      END
    ) AS played FROM ligy_nazvy ln LEFT JOIN zapasy z ON z.liga_id=ln.liga_id AND z.rocnik_id=ln.rocnik_id WHERE ln.rocnik_id=? GROUP BY ln.liga_id, ln.nazev ORDER BY ln.liga_id");
} else {
    $st = $conn->prepare("SELECT l.id, l.nazev, SUM(
      CASE 
        WHEN l.id IN (1,2,3,4,5) AND (z.skore1=7 OR z.skore2=7) THEN 1
        WHEN l.id=6 AND (z.skore1=5 OR z.skore2=5) THEN 1
        ELSE 0
      END
    ) AS played FROM ligy l LEFT JOIN zapasy z ON z.liga_id=l.id AND z.rocnik_id=? GROUP BY l.id, l.nazev ORDER BY l.cislo");
}
$st->bind_param('i', $rocnik_id);
$st->execute();
$res = $st->get_result();
while ($row = $res->fetch_assoc()) {
    $row['total'] = league_total_matches($conn, $rocnik_id, (int)$row['id']);
    $row['pct']   = ($row['total'] > 0) ? round(100 * $row['played'] / $row['total']) : 0;
    $leagueRows[] = $row;
}
$st->close();
?>

<h2 class="mb-3">🎯 Vítejte na stránkách Šipky Třešť – Ligový rozcestník</h2>

<section class="panel panel-stats">
    <div class="panel-hd">
        <h2>Aktuálně probíhá sezona <?= htmlspecialchars($nazev_rocniku) ?>.</h2>
        <p>Počet odehraných zápasů v sezoně: <strong><?= $total_played ?></strong></p>
    </div>

    <div class="league-progress">
        <?php foreach ($leagueRows as $r): ?>
            <a class="league-row" href="<?= $base ?>/ligy/<?= (int)$r['id'] ?>.liga.php">
                <div class="league-meta">
                    <span class="league-title"><?= htmlspecialchars($r['nazev']) ?></span>
                    <span class="badge"><?= (int)$r['played'] ?> / <?= (int)$r['total'] ?></span>
                </div>
                <div class="progress">
                    <span class="fill" style="width:<?= $r['pct'] ?>%"></span>
                </div>
            </a>
        <?php endforeach; ?>
    </div>

    <?php if ($showTops): ?>
        <div class="tops">
            <div><strong>Hráč s největším průměrem:</strong> <?= htmlspecialchars($topAvg['jmeno']) ?><?= $topAvg['val'] !== null ? ' — ' . $topAvg['val'] : '' ?></div>
            <div><strong>Hráč s největším maximálním zavřením:</strong> <?= htmlspecialchars($topHF['jmeno']) ?><?= $topHF['val'] !== null ? ' — ' . $topHF['val'] : '' ?></div>
            <div><strong>Hráč s nejvíce hodobody:</strong> <?= htmlspecialchars($topHB['jmeno']) ?><?= $topHB['val'] !== null ? ' — ' . $topHB['val'] : '' ?></div>
        </div>
    <?php endif; ?>
</section>

<section class="panel panel-info">
    <h3>Jak na to?</h3>
    <ul class="bullets">
        <li><strong>📊 Ligové tabulky:</strong> Sledujte aktuální pořadí v ligách.</li>
        <li><strong>📅 Rozpisy zápasů:</strong> Každá liga má svůj rozpis.</li>
        <li><strong>📈 Statistiky hráčů:</strong> Průměry, zavření, hodobody.</li>
        <li><strong>🕓 Rezervace terčů:</strong> Rezervace pro trénink i zápasy.</li>
        <li><strong>📂 Ročníky:</strong> Přepínání sezón v horním menu.</li>
        <li><strong>📈 Kompletní statistiky:</strong> Zde můžete porovnat všechny hráče napříč ligami. Tabulku lze filtrovat.</li>
    </ul>

    <ul class="bullets">
        <li><a href="<?= $base ?>/docs/pravidla.pdf?v=20260114" target="_blank" rel="noopener">Odkaz na pravidla ligy pro nejnovější sezonu</a></li>
        <li>Vše je navrženo tak, aby byl přehled jednoduchý i na mobilních zařízeních.</li>
        <li>Stránka na Androidu by měla nabídnout stažení funkční aplikace.</li>
        <li>Zápasy se doplňují průběžně.</li>
        <li>Chyby nebo nápady pište na Messenger „Šipky Třešť“.</li>
    </ul>
</section>

<?php 
$sponsors = [
    ['name'=>'FPNet.cz', 'url'=>'https://www.fpnet.cz/', 'img'=>'fpnet.png'],
    ['name'=>'Podzimek a synové s.r.o.', 'url'=>'https://www.podzimek.cz/', 'img'=>'podzimek.jpg'],
    ['name'=>'AUTO – MOTO – KUBA', 'url'=>'http://www.amkuba.cz/', 'img'=>'automoto.png'],
    ['name'=>'Stavebniny SYPSTAV', 'url'=>'https://www.sypstav.cz/', 'img'=>'sypstav.png'],
    ['name'=>'Restaurace U Kapra', 'url'=>'https://www.u-kapra.cz/', 'img'=>'u-kapra.png'],
    ['name'=>'Město Třešť', 'url'=>'https://www.trest.cz/', 'img'=>'trest.png'],
    ['name'=>'Podlahy Svoboda', 'url'=>'https://www.facebook.com/PodlahySvoboda/', 'img'=>'svoboda.jpg'],
    ['name'=>'Střechy Holoubek', 'url'=>'https://sipkytrest.cz/liga-app/sponzor/holoubek.jpg', 'img'=>'holoubek.jpg']
]; 
?>
<section class="panel panel-sponsors">
    <h3>Velký dík patří našim sponzorům: TEST</h3>
    <div class="sponsor-grid">
        <?php foreach ($sponsors as $sp): ?>
            <a class="sponsor-card" href="<?= htmlspecialchars($sp['url']) ?>" target="_blank">
                <div class="logo-wrap">
                    <img src="<?= ($BASE_URL ?? '/liga-app') . '/sponzor/' . $sp['img'] ?>" alt="<?= htmlspecialchars($sp['name']) ?>">
                </div>
                <div class="sponsor-name"><?= htmlspecialchars($sp['name']) ?></div>
            </a>
        <?php endforeach; ?>
    </div>
</section>
<?php include 'footer.php'; ?>
