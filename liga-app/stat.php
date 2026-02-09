<?php
require __DIR__.'/header.php';
require __DIR__.'/common.php';

$liga_id   = _safe_liga_id();
$rocnik_id = _active_rocnik_id($conn);

$nadpis = 'Statistiky – '._liga_name($conn, $liga_id).' – '._rocnik_name($conn, $rocnik_id);

// Pokud je ročník Podzim 2024 (id=1), statistiky nezobrazujeme
if ((int)$rocnik_id === 1) {
  ?>
  <main id="content" class="nk-content nk-content--flat">
    <h2><?= htmlspecialchars($nadpis, ENT_QUOTES, 'UTF-8') ?></h2>
    <p>Z této sezóny nemáme žádné statistiky.</p>
  </main>
  <?php
  require __DIR__.'/footer.php';
  exit;
}


/*
  Vytáhneme všechny hráče z dané ligy/ročníku (hraci_v_sezone)
  + jméno (hraci_unikatni_jmena). Na tyto hráče následně LEFT JOIN zapasy
  pro stejnou ligu/ročník. Díky LEFT JOIN se ukážou i hráči bez odehraného zápasu.
*/
$sql = "
SELECT *
FROM (
  SELECT
    u.libovolne_id AS id,
    u.jmeno,

    COALESCE(SUM(CASE WHEN z.skore1 >= 0 AND z.skore2 >= 0 THEN 1 ELSE 0 END), 0) AS zapasy,

    ROUND(AVG(
      CASE
        WHEN z.hrac1_id = u.libovolne_id THEN z.average_home
        WHEN z.hrac2_id = u.libovolne_id THEN z.average_away
        ELSE NULL
      END
    ), 2) AS prumer,

    MAX(
      CASE
        WHEN z.hrac1_id = u.libovolne_id THEN z.high_finish_home
        WHEN z.hrac2_id = u.libovolne_id THEN z.high_finish_away
        ELSE NULL
      END
    ) AS nejvyssi_zavreni,

   COALESCE(
  (
    -- 100+ = 1 bod
    SUM(
      CASE
        WHEN z.hrac1_id = u.libovolne_id THEN IFNULL(z.count_100p_home,0)
        ELSE IFNULL(z.count_100p_away,0)
      END
    ) * 1

    -- 120+ = 2 body
    + SUM(
      CASE
        WHEN z.hrac1_id = u.libovolne_id THEN IFNULL(z.count_120p_home,0)
        ELSE IFNULL(z.count_120p_away,0)
      END
    ) * 2

    -- 140+ = 3 body
    + SUM(
      CASE
        WHEN z.hrac1_id = u.libovolne_id THEN IFNULL(z.count_140p_home,0)
        ELSE IFNULL(z.count_140p_away,0)
      END
    ) * 3

    -- 160+ = 4 body
    + SUM(
      CASE
        WHEN z.hrac1_id = u.libovolne_id THEN IFNULL(z.count_160p_home,0)
        ELSE IFNULL(z.count_160p_away,0)
      END
    ) * 4

    -- 180 = 5 bodů
    + SUM(
      CASE
        WHEN z.hrac1_id = u.libovolne_id THEN IFNULL(z.count_180_home,0)
        ELSE IFNULL(z.count_180_away,0)
      END
    ) * 5
  ), 0
) AS hodobody


  FROM hraci_v_sezone hs
  JOIN hraci_unikatni_jmena u
    ON u.libovolne_id = hs.hrac_id
  LEFT JOIN zapasy z
    ON z.rocnik_id = hs.rocnik_id
   AND z.liga_id   = hs.liga_id
   AND (z.hrac1_id = u.libovolne_id OR z.hrac2_id = u.libovolne_id)
  WHERE hs.rocnik_id = ?
    AND hs.liga_id   = ?
  GROUP BY u.libovolne_id, u.jmeno
) t
ORDER BY (t.prumer IS NULL), t.prumer DESC, t.jmeno ASC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param('ii', $rocnik_id, $liga_id);

$stmt->execute();
$res = $stmt->get_result();

$rows = [];
while ($r = $res->fetch_assoc()) { $rows[] = $r; }
$stmt->close();
?>
<main id="content" class="nk-content nk-content--flat">
  <h2><?= htmlspecialchars($nadpis) ?></h2>

  <div class="table-wrap">
    <table class="table table--stats">
      <thead>
        <tr>
          <th>Poř.</th>
          <th>Hráč</th>
          <th>Zápasy</th>
          <th>Průměr</th>
          <th>Nejvyšší zavření</th>
          <th>Hodobody</th>
        </tr>
      </thead>
      <tbody>
        <?php $i = 1; foreach ($rows as $row): ?>
          <tr>
            <td data-label="Poř."><?= $i++ ?>.</td>
            <td data-label="Hráč"><?= htmlspecialchars($row['jmeno']) ?></td>
            <td data-label="Zápasy" style="text-align:center">
              <?= (int)$row['zapasy'] ?>
            </td>
            <td data-label="Průměr" style="text-align:center">
              <?= is_null($row['prumer']) ? '—' : number_format((float)$row['prumer'], 2, ',', ' ') ?>
            </td>
            <td data-label="Nejvyšší zavření" style="text-align:center">
              <?= is_null($row['nejvyssi_zavreni']) ? '—' : (int)$row['nejvyssi_zavreni'] ?>
            </td>
            <td data-label="Hodobody" style="text-align:center">
              <?= (int)$row['hodobody'] ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</main>
<?php require __DIR__.'/footer.php'; ?>
