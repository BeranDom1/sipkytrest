<?php
require __DIR__ . '/header.php';
require __DIR__ . '/common.php';
require_once __DIR__ . '/minitabulka_serazeni.php'; // <— přidáno

$liga_id   = _safe_liga_id();
$rocnik_id = _active_rocnik_id($conn);

/* 1) Hráči skutečně patřící do této ligy a ročníku */
$players = []; // [player_id => jmeno]
$sql = "SELECT u.libovolne_id AS player_id, u.jmeno
        FROM hraci_v_sezone hs
        JOIN hraci_unikatni_jmena u ON u.libovolne_id = hs.hrac_id
        WHERE hs.rocnik_id = ? AND hs.liga_id = ?
        ORDER BY u.jmeno";
$st = $conn->prepare($sql);
$st->bind_param('ii', $rocnik_id, $liga_id);
$st->execute();
$res = $st->get_result();
while ($r = $res->fetch_assoc()) {
    $players[(int)$r['player_id']] = $r['jmeno'];
}
$st->close();

if (!$players) {
    $nadpis = _liga_name($conn, $liga_id) . ' – ' . _rocnik_name($conn, $rocnik_id);
    echo '<main id="content" class="nk-content nk-content--flat">';
    echo '<h2>' . htmlspecialchars($nadpis) . '</h2>';
    echo '<p>V této lize zatím nejsou pro daný ročník přiřazeni žádní hráči.</p>';
    echo '</main>';
    require __DIR__ . '/footer.php';
    exit;
}

/* 2) Inicializace statistik */
$stats = []; // [pid => [jmeno,Z,V,P,RZD,body]]
foreach ($players as $pid => $jmeno) {
    $stats[$pid] = [
        'player_id' => $pid,
        'jmeno'     => $jmeno,
        'Z'         => 0,
        'V'         => 0,
        'P'         => 0,
        'RZD'       => 0,
        'body'      => 0,   // používáme klíč 'body' – sedí s minitabulkou
    ];
}

/* 3) Napočítání ze zápasů této ligy a ročníku */
$sql = "SELECT hrac1_id, hrac2_id, skore1, skore2
        FROM zapasy
        WHERE rocnik_id = ? AND liga_id = ?";
$st = $conn->prepare($sql);
$st->bind_param('ii', $rocnik_id, $liga_id);
$st->execute();
$res = $st->get_result();

while ($r = $res->fetch_assoc()) {
    $h1 = (int)$r['hrac1_id'];
    $h2 = (int)$r['hrac2_id'];
    $s1 = is_null($r['skore1']) ? -1 : (int)$r['skore1'];
    $s2 = is_null($r['skore2']) ? -1 : (int)$r['skore2'];

    // zohledňujeme jen zapsané výsledky a jen hráče z tabulky
    if ($s1 >= 0 && $s2 >= 0 && isset($stats[$h1]) && isset($stats[$h2])) {
        $stats[$h1]['Z']++; $stats[$h2]['Z']++;
        $stats[$h1]['RZD'] += ($s1 - $s2);
        $stats[$h2]['RZD'] += ($s2 - $s1);

        if ($s1 > $s2) { $stats[$h1]['V']++; $stats[$h2]['P']++; $stats[$h1]['body'] += 2; }
        elseif ($s2 > $s1) { $stats[$h2]['V']++; $stats[$h1]['P']++; $stats[$h2]['body'] += 2; }
        // remízy neřešíš; pokud by byly, tady by se přidalo 1/1 bod
    }
}
$st->close();

/* 4) Převod na indexované pole a seřazení včetně minitabulky */
$rows = array_values($stats); // zrušíme klíče pid, minitabulka čeká indexované pole

// hrubé seřazení (body, RZD, jméno) – stabilní základ
usort($rows, function($a, $b){
    if ($a['body'] !== $b['body']) return $b['body'] <=> $a['body'];
    if ($a['RZD']  !== $b['RZD'])  return $b['RZD']  <=> $a['RZD'];
    return strcasecmp($a['jmeno'], $b['jmeno']);
});

// finální pořadí přes minitabulku pouze ve skupinách se shodnými body
$rows = serad_hrace_s_rovnymi_body($rows, $conn, $rocnik_id);

/* 5) Render */
$nadpis = _liga_name($conn, $liga_id) . ' – ' . _rocnik_name($conn, $rocnik_id);
?>
<main id="content" class="nk-content nk-content--flat">
  <h2><?= htmlspecialchars($nadpis) ?></h2>
  <div class="table-wrap">
    <table class="table table--league">
      <thead><tr>
        <th>Poř.</th><th>Hráč</th><th>Z</th><th>V</th><th>P</th><th>RZD</th><th>B</th>
      </tr></thead>
      <tbody>
     <?php
$totalPlayers = count($rows);
$i = 1;

foreach ($rows as $row):

  $cls = '';

  /* === 1. liga – speciální pravidla ======================= */
  if ($liga_id === 1) {

    // postup: 1.–3. místo
    if ($i <= 3) {
      $cls = ' style="background:#e6ffed"';
    }

    // sestup: poslední 4 místa
    elseif ($i > $totalPlayers - 4) {
      $cls = ' style="background:#ffe6e6"';
    }

  /* === ostatní ligy – původní chování ===================== */
  } else {

    // postup: 1.–2. místo
    if ($i <= 2) {
      $cls = ' style="background:#e6ffed"';
    }

    // sestup: poslední 2 místa
    elseif ($i > $totalPlayers - 2) {
      $cls = ' style="background:#ffe6e6"';
    }
  }
?>
        <tr<?= $cls ?>>
          <td data-label="Poř."><?= $i++ ?>.</td>
          <td data-label="Hráč"><?= htmlspecialchars($row['jmeno']) ?></td>
          <td data-label="Z"><?= (int)$row['Z'] ?></td>
          <td data-label="V"><?= (int)$row['V'] ?></td>
          <td data-label="P"><?= (int)$row['P'] ?></td>
          <td data-label="RZD"><?= (int)$row['RZD'] ?></td>
          <td data-label="B"><?= (int)$row['body'] ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</main>
<?php require __DIR__ . '/footer.php'; ?>
