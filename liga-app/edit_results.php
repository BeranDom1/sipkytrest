<?php
// www/liga-app/edit_results.php  (upravené pro zadávání kompletních statistik obou hráčů)

include __DIR__ . '/header.php';
session_start();
require_once __DIR__ . '/db.php';

// Oprávnění
if (!in_array($_SESSION['role'] ?? '', ['admin','stat_editor'], true)) {
    http_response_code(403);
    die('Přístup zamítnut');
}

$rocnikId = 1; // Podzim 2024

// Načtení zápasů + existujících statistik
$sql = <<<'SQL'
SELECT
  z.id,
  z.datum,
  h1.jmeno AS hrac1,
  h2.jmeno AS hrac2,
  z.skore1,
  z.skore2,
  z.average_home       AS average_home,
  z.average_away       AS average_away,
  z.high_finish_home   AS high_finish_home,
  z.high_finish_away   AS high_finish_away,
  z.count_100p_home    AS count_100p_home,
  z.count_100p_away    AS count_100p_away,
  z.count_120p_home    AS count_120p_home,
  z.count_120p_away    AS count_120p_away,
  z.count_140p_home    AS count_140p_home,
  z.count_140p_away    AS count_140p_away,
  z.count_160p_home    AS count_160p_home,
  z.count_160p_away    AS count_160p_away,
  z.count_180_home     AS count_180_home,
  z.count_180_away     AS count_180_away
FROM zapasy z
JOIN hraci h1 ON z.hrac1_id = h1.id
JOIN hraci h2 ON z.hrac2_id = h2.id
WHERE z.rocnik_id = ?
ORDER BY z.datum, z.id
SQL;

$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $rocnikId);
$stmt->execute();
$matches = $stmt->get_result();
$stmt->close();
?>

<div class="container mt-4">
  <h2>Zadání statistik zápasů – Podzim 2024</h2>
  <form method="post" action="save_results.php">
    <table class="table table-striped">
      <thead>
        <tr>
          <th>#</th>
          <th>Datum</th>
          <th>Hráč 1</th>
          <th>Hráč 2</th>
          <th>Skóre 1</th>
          <th>Skóre 2</th>
          <th>Průměr 1</th>
          <th>Průměr 2</th>
          <th>H. zavř. 1</th>
          <th>H. zavř. 2</th>
          <th>100+ 1</th>
          <th>100+ 2</th>
          <th>120+ 1</th>
          <th>120+ 2</th>
          <th>140+ 1</th>
          <th>140+ 2</th>
          <th>160+ 1</th>
          <th>160+ 2</th>
          <th>180 1</th>
          <th>180 2</th>
        </tr>
      </thead>
      <tbody>
      <?php while($row = $matches->fetch_assoc()): ?>
        <?php $id = $row['id']; ?>
        <tr>
          <td><?= $id ?></td>
          <td><?= htmlspecialchars($row['datum']) ?></td>
          <td><?= htmlspecialchars($row['hrac1']) ?></td>
          <td><?= htmlspecialchars($row['hrac2']) ?></td>

          <td><input type="number" name="skore1[<?= $id ?>]" value="<?= $row['skore1'] ?>" min="0" max="7" class="form-control"></td>
          <td><input type="number" name="skore2[<?= $id ?>]" value="<?= $row['skore2'] ?>" min="0" max="7" class="form-control"></td>

          <td><input type="number" step="0.01" name="average_home[<?= $id ?>]" value="<?= $row['average_home'] ?>" class="form-control"></td>
          <td><input type="number" step="0.01" name="average_away[<?= $id ?>]" value="<?= $row['average_away'] ?>" class="form-control"></td>

          <td><input type="number" name="high_finish_home[<?= $id ?>]" value="<?= $row['high_finish_home'] ?>" class="form-control"></td>
          <td><input type="number" name="high_finish_away[<?= $id ?>]" value="<?= $row['high_finish_away'] ?>" class="form-control"></td>

          <td><input type="number" name="count_100p_home[<?= $id ?>]" value="<?= $row['count_100p_home'] ?>" class="form-control"></td>
          <td><input type="number" name="count_100p_away[<?= $id ?>]" value="<?= $row['count_100p_away'] ?>" class="form-control"></td>

          <td><input type="number" name="count_120p_home[<?= $id ?>]" value="<?= $row['count_120p_home'] ?>" class="form-control"></td>
          <td><input type="number" name="count_120p_away[<?= $id ?>]" value="<?= $row['count_120p_away'] ?>" class="form-control"></td>

          <td><input type="number" name="count_140p_home[<?= $id ?>]" value="<?= $row['count_140p_home'] ?>" class="form-control"></td>
          <td><input type="number" name="count_140p_away[<?= $id ?>]" value="<?= $row['count_140p_away'] ?>" class="form-control"></td>

          <td><input type="number" name="count_160p_home[<?= $id ?>]" value="<?= $row['count_160p_home'] ?>" class="form-control"></td>
          <td><input type="number" name="count_160p_away[<?= $id ?>]" value="<?= $row['count_160p_away'] ?>" class="form-control"></td>

          <td><input type="number" name="count_180_home[<?= $id ?>]" value="<?= $row['count_180_home'] ?>" class="form-control"></td>
          <td><input type="number" name="count_180_away[<?= $id ?>]" value="<?= $row['count_180_away'] ?>" class="form-control"></td>
        </tr>
      <?php endwhile; ?>
      </tbody>
    </table>
    <button type="submit" class="btn btn-success mt-3">Uložit statistiky</button>
  </form>
</div>

<?php include __DIR__ . '/footer.php'; ?>