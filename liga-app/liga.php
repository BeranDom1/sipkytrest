<?php
require __DIR__ . '/header.php';
require __DIR__ . '/common.php';
require_once __DIR__ . '/minitabulka_serazeni.php'; // <— přidáno

$liga_id   = _safe_liga_id();
$rocnik_id = _active_rocnik_id($conn);

/** Načte živou ligovou tabulku ve stejném pořadí, jaké se zobrazuje návštěvníkům. */
function load_league_standings(mysqli $conn, int $seasonId, int $leagueId): array {
    $stats = [];
    $stmt = $conn->prepare(
        'SELECT u.libovolne_id AS player_id, u.jmeno
           FROM hraci_v_sezone hs
           JOIN hraci_unikatni_jmena u ON u.libovolne_id=hs.hrac_id
          WHERE hs.rocnik_id=? AND hs.liga_id=?
          ORDER BY hs.hrac_id'
    );
    $stmt->bind_param('ii', $seasonId, $leagueId);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($player = $result->fetch_assoc()) {
        $playerId = (int)$player['player_id'];
        $stats[$playerId] = [
            'player_id' => $playerId,
            'jmeno' => $player['jmeno'],
            'Z' => 0,
            'V' => 0,
            'P' => 0,
            'RZD' => 0,
            'body' => 0,
        ];
    }
    $stmt->close();

    if (!$stats) return [];

    $validScoreSql = _reportable_match_score_sql($conn, $seasonId, 'z');
    $sql = "SELECT z.hrac1_id, z.hrac2_id, z.skore1, z.skore2
              FROM zapasy z
             WHERE z.rocnik_id=? AND z.liga_id=? AND $validScoreSql";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ii', $seasonId, $leagueId);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($match = $result->fetch_assoc()) {
        $home = (int)$match['hrac1_id'];
        $away = (int)$match['hrac2_id'];
        if (!isset($stats[$home], $stats[$away])) continue;

        $homeScore = (int)$match['skore1'];
        $awayScore = (int)$match['skore2'];
        $stats[$home]['Z']++;
        $stats[$away]['Z']++;
        $stats[$home]['RZD'] += $homeScore - $awayScore;
        $stats[$away]['RZD'] += $awayScore - $homeScore;
        if ($homeScore > $awayScore) {
            $stats[$home]['V']++;
            $stats[$away]['P']++;
            $stats[$home]['body'] += 2;
        } elseif ($awayScore > $homeScore) {
            $stats[$away]['V']++;
            $stats[$home]['P']++;
            $stats[$away]['body'] += 2;
        }
    }
    $stmt->close();

    $rows = array_values($stats);
    usort($rows, function(array $a, array $b): int {
        if ($a['body'] !== $b['body']) return $b['body'] <=> $a['body'];
        if ($a['RZD'] !== $b['RZD']) return $b['RZD'] <=> $a['RZD'];
        return 0;
    });
    return serad_hrace_s_rovnymi_body($rows, $conn, $seasonId, $leagueId);
}

$rows = load_league_standings($conn, $rocnik_id, $liga_id);

if (!$rows) {
    $nadpis = _liga_name($conn, $liga_id) . ' – ' . _rocnik_name($conn, $rocnik_id);
    echo '<main id="content" class="nk-content nk-content--flat">';
    echo '<h2>' . htmlspecialchars($nadpis) . '</h2>';
    echo '<p>V této lize zatím nejsou pro daný ročník přiřazeni žádní hráči.</p>';
    echo '</main>';
    require __DIR__ . '/footer.php';
    exit;
}

/* Ženské skupiny v aktivní sezóně a jejich průběžně postupující hráčky. */
$womenGroups = [];
$stmt = $conn->prepare(
    'SELECT l.id, COALESCE(ln.nazev, l.nazev) AS nazev
       FROM ligy l
       JOIN hraci_v_sezone hs ON hs.liga_id=l.id AND hs.rocnik_id=?
       LEFT JOIN ligy_nazvy ln ON ln.liga_id=l.id AND ln.rocnik_id=?
      GROUP BY l.id, COALESCE(ln.nazev, l.nazev), l.poradi
      ORDER BY l.poradi, l.id'
);
$stmt->bind_param('ii', $rocnik_id, $rocnik_id);
$stmt->execute();
$result = $stmt->get_result();
while ($league = $result->fetch_assoc()) {
    if (!preg_match('~žen~iu', (string)$league['nazev'])) continue;
    preg_match('~sk\.?\s*([AB])~iu', (string)$league['nazev'], $groupMatch);
    $womenGroups[] = [
        'id' => (int)$league['id'],
        'name' => (string)$league['nazev'],
        'group' => isset($groupMatch[1]) ? strtoupper($groupMatch[1]) : '',
    ];
}
$stmt->close();

$playoffRows = [];
$womenLeagueIds = array_column($womenGroups, 'id');
if (count($womenGroups) === 2 && in_array($liga_id, $womenLeagueIds, true)) {
    foreach ($womenGroups as $group) {
        $groupRows = load_league_standings($conn, $rocnik_id, $group['id']);
        foreach (array_slice($groupRows, 0, 2) as $index => $row) {
            $row['group'] = $group['group'];
            $row['group_rank'] = $index + 1;
            $playoffRows[] = $row;
        }
    }
    usort($playoffRows, function(array $a, array $b): int {
        if ($a['group_rank'] !== $b['group_rank']) return $a['group_rank'] <=> $b['group_rank'];
        return strcmp($a['group'], $b['group']);
    });
}

/* 5) Render */
$nadpis = _liga_name($conn, $liga_id) . ' – ' . _rocnik_name($conn, $rocnik_id);
?>
<main id="content" class="nk-content nk-content--flat">
  <h2><?= htmlspecialchars($nadpis) ?></h2>
  <div class="table-wrap league-table-wrap">
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

  <?php if ($playoffRows): ?>
    <section class="women-playoff-preview" aria-labelledby="women-playoff-title">
      <h3 id="women-playoff-title">Aktuální postupující do play-off</h3>
      <p>Do play-off průběžně postupují první dvě hráčky ze skupiny A a první dvě hráčky ze skupiny B. Tabulka se mění automaticky podle zapsaných výsledků.</p>
      <div class="table-wrap league-table-wrap">
        <table class="table table--league table--playoff">
          <thead>
            <tr>
              <th>Nasazení</th><th>Hráčka</th><th>Sk.</th><th>Poř. ve sk.</th><th>Z</th><th>V</th><th>P</th><th>RZD</th><th>B</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($playoffRows as $index => $row): ?>
              <tr>
                <td data-label="Nasazení"><?= $index + 1 ?>.</td>
                <td data-label="Hráčka"><?= htmlspecialchars($row['jmeno']) ?></td>
                <td data-label="Sk."><?= htmlspecialchars($row['group']) ?></td>
                <td data-label="Poř. ve sk."><?= (int)$row['group_rank'] ?>.</td>
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
    </section>
  <?php endif; ?>
</main>
<?php require __DIR__ . '/footer.php'; ?>
