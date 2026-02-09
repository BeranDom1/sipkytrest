<?php 
require __DIR__.'/header.php';
require __DIR__.'/common.php';
require_once __DIR__.'/security/csrf.php';
$csrfRozpis = csrf_token();

$liga_id   = _safe_liga_id();
$rocnik_id = _active_rocnik_id($conn);

// --- kdo může upravovat ---
$canEdit = in_array($_SESSION['role'] ?? '', ['admin','stat_editor'], true);

/** ------------------------------------------------------------------
 *  1) Hráči v dané lize/ročníku (id => jméno), řazeno podle jména
 * -----------------------------------------------------------------*/
$players = [];
$sql = "SELECT hs.hrac_id, u.jmeno
          FROM hraci_v_sezone hs
          JOIN hraci_unikatni_jmena u ON u.libovolne_id = hs.hrac_id
         WHERE hs.rocnik_id = ? AND hs.liga_id = ?
         ORDER BY u.jmeno";
$st = $conn->prepare($sql);
$st->bind_param('ii', $rocnik_id, $liga_id);
$st->execute();
$res = $st->get_result();
while ($r = $res->fetch_assoc()) {
    $players[(int)$r['hrac_id']] = (string)$r['jmeno'];
}
$st->close();

/** ------------------------------------------------------------------
 *  2) Round-robin (circle method) – vrací pole kol s dvojicemi id
 * -----------------------------------------------------------------*/
function rr_schedule(array $ids): array {
    $n = count($ids);
    if ($n < 2) return [];
    $work = array_values($ids);

    if ($n % 2 === 1) {           // lichý počet => přidej BYE
        $work[] = 0;              // 0 = volno
        $n++;
    }

    $rounds = [];
    $half   = (int)($n / 2);
    for ($r = 0; $r < $n - 1; $r++) {
        $pairs = [];
        for ($i = 0; $i < $half; $i++) {
            $a = $work[$i];
            $b = $work[$n - 1 - $i];
            if ($a !== 0 && $b !== 0) {
                if ($a > $b) { $t=$a; $a=$b; $b=$t; }
                $pairs[] = [$a, $b];
            }
        }
        $rounds[] = $pairs;

        // rotace: první zůstává, ostatní rotují
        $last = array_pop($work);
        array_splice($work, 1, 0, [$last]);
    }
    return $rounds;
}

/** ------------------------------------------------------------------
 *  3) Zápasy z DB pro danou ligu/ročník → mapa "min-max" => řádek
 * -----------------------------------------------------------------*/
$matchMap = [];
$st = $conn->prepare(
    "SELECT id, hrac1_id, hrac2_id, skore1, skore2
       FROM zapasy
      WHERE rocnik_id = ? AND liga_id = ?"
);
$st->bind_param('ii', $rocnik_id, $liga_id);
$st->execute();
$res = $st->get_result();
while ($m = $res->fetch_assoc()) {
    $a = (int)$m['hrac1_id']; $b = (int)$m['hrac2_id'];
    $k = ($a < $b) ? "$a-$b" : "$b-$a";
    $matchMap[$k] = $m;
}
$st->close();

/** ------------------------------------------------------------------
 *  4) Rozvrh kol a render
 * -----------------------------------------------------------------*/
$pids   = array_keys($players);         // už je seřazeno podle jména
$rounds = rr_schedule($pids);

$nadpis = 'Rozpis – '._liga_name($conn, $liga_id).' – '._rocnik_name($conn, $rocnik_id);
?>
<main id="content" class="nk-content nk-content--flat">
  <h2><?= htmlspecialchars($nadpis) ?></h2>

  <?php if (!$players): ?>
    <p>V téhle lize zatím nejsou zapsaní žádní hráči.</p>
  <?php elseif (!$rounds): ?>
    <p>Pro počet hráčů nelze vygenerovat rozpis.</p>
  <?php else: ?>
    <?php foreach ($rounds as $i => $pairs): ?>
      <h3>Kolo <?= $i+1 ?></h3>
      <div class="table-wrap">
        <table class="table table--rozpis">
          <thead>
            <tr>
              <th>Hráč 1</th>
              <th>Výsledek</th>
              <th>Hráč 2</th>
              <th>Detail</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($pairs as [$a,$b]):
                $k = "$a-$b";
                $m = $matchMap[$k] ?? null;

                // necháme původní hodnoty (mohou být NULL)
                $s1 = $m['skore1'] ?? null;
                $s2 = $m['skore2'] ?? null;

                // má-li se skóre počítat: musí být ne-NULL a ne 0:0
                $hasScore = ($s1 !== null && $s2 !== null && ((int)$s1 !== 0 || (int)$s2 !== 0));
            ?>
              <tr>
                <td data-label="Hráč 1"><?= htmlspecialchars($players[$a] ?? ('#'.$a)) ?></td>
                <td data-label="Výsledek" style="text-align:center">
                  <?= $hasScore ? (((int)$s1).' : '.((int)$s2)) : '—' ?>
                </td>
                <td data-label="Hráč 2" style="text-align:right">
                  <?= htmlspecialchars($players[$b] ?? ('#'.$b)) ?>
                </td>
                <td data-label="Detail" style="text-align:center">
                  <?php if ($m): ?>
                    <?php if (!$hasScore && $canEdit): ?>
                      <a href="<?= htmlspecialchars($BASE_URL) ?>/zapas.php?id=<?= (int)$m['id'] ?>&edit=1">Zadat výsledek</a>
                    <?php else: ?>
                      <a href="<?= htmlspecialchars($BASE_URL) ?>/zapas.php?id=<?= (int)$m['id'] ?>">Detail</a>
                    <?php endif; ?>
                  <?php elseif ($canEdit): ?>
                    <form action="<?= htmlspecialchars($BASE_URL) ?>/zapas_create.php" method="post" style="display:inline">
                      <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrfRozpis) ?>">
                      <input type="hidden" name="rocnik_id" value="<?= (int)$rocnik_id ?>">
                      <input type="hidden" name="liga_id"   value="<?= (int)$liga_id ?>">
                      <input type="hidden" name="a"         value="<?= (int)$a ?>">
                      <input type="hidden" name="b"         value="<?= (int)$b ?>">
                      <button type="submit" class="btn btn-sm">Zadat výsledek</button>
                    </form>
                  <?php else: ?>
                    <span style="opacity:.5">—</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</main>
<?php require __DIR__.'/footer.php'; ?>
