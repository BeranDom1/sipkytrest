<?php
require __DIR__ . '/../header.php';
require __DIR__ . '/../db.php';
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../security/csrf.php';

$rocnik_id = (int)($_SESSION['rocnik_id'] ?? 0);
$seasonStmt = $conn->prepare('SELECT stav FROM rocniky WHERE id = ?');
$seasonStmt->bind_param('i', $rocnik_id);
$seasonStmt->execute();
$season = $seasonStmt->get_result()->fetch_assoc();
$seasonStmt->close();
$editable = $season && $season['stav'] === 'priprava';
$csrf = csrf_token();

// ligy + NAZVY PRO ROČNÍK
$sql = "
SELECT
  l.id AS liga_id,
  l.cislo,
  COALESCE(ln.nazev, l.nazev) AS nazev,
  ll.logo,
  ll.alt
FROM ligy l
LEFT JOIN ligy_nazvy ln
  ON ln.liga_id = l.id AND ln.rocnik_id = ?
LEFT JOIN ligy_loga ll
  ON ll.liga_id = l.id AND ll.rocnik_id = ?
ORDER BY l.cislo
";

$st = $conn->prepare($sql);
$st->bind_param('ii', $rocnik_id, $rocnik_id);
$st->execute();
$ligy = $st->get_result()->fetch_all(MYSQLI_ASSOC);
$st->close();

// obrázky ze složky /sponzor
$sponsorDir = __DIR__ . '/../sponzor/';
$sponsorFiles = array_values(array_filter(
    scandir($sponsorDir),
    fn($f) => preg_match('~\.(png|jpg|jpeg|webp)$~i', $f)
));
?>

<h1>Správa log lig – ročník <?= $rocnik_id ?></h1>
<?php if (!$editable): ?><div class="alert alert-warning">Loga lze měnit pouze u sezóny ve stavu Příprava.</div><?php endif; ?>

<table class="table table-dark table-striped align-middle">
<thead>
<tr>
  <th>Liga</th>
  <th>Aktuální logo</th>
  <th>Vybrat logo</th>
  <th>Alt text</th>
  <th>Akce</th>
</tr>
</thead>
<tbody>

<?php foreach ($ligy as $liga): ?>
<tr>
<form method="post" action="ulozit_logo.php">
  <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
  <td>
    <?= htmlspecialchars($liga['cislo'] . '. ' . $liga['nazev']) ?>
    <input type="hidden" name="liga_id" value="<?= $liga['liga_id'] ?>">
    <input type="hidden" name="rocnik_id" value="<?= $rocnik_id ?>">
  </td>

  <td>
    <?php if ($liga['logo']): ?>
      <img src="/liga-app/sponzor/<?= htmlspecialchars($liga['logo']) ?>"
           style="height:40px">
    <?php else: ?>
      <em>—</em>
    <?php endif; ?>
  </td>

  <td>
    <select name="logo" class="form-select">
      <option value="">— žádné —</option>
      <?php foreach ($sponsorFiles as $file): ?>
        <option value="<?= $file ?>"
          <?= $file === $liga['logo'] ? 'selected' : '' ?>>
          <?= $file ?>
        </option>
      <?php endforeach; ?>
    </select>
  </td>

  <td>
    <input type="text" name="alt"
           class="form-control"
           value="<?= htmlspecialchars($liga['alt'] ?? '') ?>">
  </td>

  <td>
    <button class="btn btn-sm btn-warning" <?= $editable ? '' : 'disabled' ?>>Uložit</button>
  </td>
</form>
</tr>
<?php endforeach; ?>

</tbody>
</table>
