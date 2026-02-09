<?php
require_once __DIR__.'/../db.php';
require_once __DIR__.'/_auth.php';
require_once __DIR__.'/../security/csrf.php';

$sezony = $conn->query(
  "SELECT id, nazev, locked FROM rocniky ORDER BY id DESC"
)->fetch_all(MYSQLI_ASSOC);

// výchozí ročník = nejnovější neuzamčený
$default_id = null;
foreach ($sezony as $s) {
  if ((int)$s['locked'] === 0) {
    $default_id = (int)$s['id'];
    break;
  }
}
if (!$default_id && $sezony) {
  $default_id = (int)$sezony[0]['id'];
}
?>
<!doctype html>
<html lang="cs">
<head>
<meta charset="utf-8">
<title>Administrace</title>
<link rel="stylesheet" href="/liga-app/style.css">
<style>
.card{
  border:1px solid #ddd;
  padding:1.2rem;
  border-radius:12px;
  max-width:820px;
  margin:2rem auto;
  background:#fff
}
.btn{
  padding:.5rem .9rem;
  border-radius:10px;
  border:1px solid #333;
  background:#111;
  color:#fff;
  cursor:pointer;
  text-decoration:none;
  display:inline-block
}
.btn.secondary{
  background:#444;
}
.btn.green{
  background:#0a7b12;
  border-color:#0a7b12;
}
select{padding:.35rem .5rem}
hr{margin:1.8rem 0}
</style>
</head>
<body>

<div class="card">

<h1>Administrace</h1>
<p>Centrální správa lig, hráčů a turnajů. Práce je vždy vázaná na konkrétní ročník.</p>

<!-- ======================= ROZŘAZENÍ ======================= -->
<h2>Rozřazení hráčů do lig</h2>
<p>Otevře rozřazení hráčů pro vybraný ročník (0.–5. liga).</p>

<form action="/liga-app/admin/rozrazeni.php" method="get"
      style="display:flex;gap:.5rem;align-items:center;flex-wrap:wrap">
  <label for="rocnik">Sezóna:</label>
  <select id="rocnik" name="rocnik_id" required>
    <?php foreach($sezony as $s): ?>
      <option value="<?= (int)$s['id'] ?>"
        <?= $s['id']==$default_id?'selected':''; ?>>
        <?= htmlspecialchars($s['nazev']) ?>
        <?= (int)$s['locked'] ? ' (uzamčeno)' : '' ?>
      </option>
    <?php endforeach; ?>
  </select>
  <button class="btn">Otevřít rozřazení</button>
</form>

<!-- ======================= LIGY ======================= -->
<hr>

<h2>Správa lig</h2>
<p>Názvy a loga lig jsou vázané na konkrétní ročník.</p>

<p style="display:flex;gap:.6rem;flex-wrap:wrap">
  <a class="btn secondary" href="/liga-app/admin/ligy_nazvy.php">
    🏷️ Názvy lig (podle ročníku)
  </a>

  <a class="btn secondary" href="/liga-app/admin/ligy_loga.php">
    🖼️ Loga lig (podle ročníku)
  </a>
</p>

<!-- ======================= HRÁČI ======================= -->
<hr>

<h2>Hráči</h2>
<p>Správa databáze hráčů (přidání nových hráčů).</p>

<p>
  <a class="btn" href="/liga-app/admin/hraci.php">
    Správa hráčů
  </a>
</p>

<!-- ======================= TURNAJE ======================= -->
<hr>

<h2>Turnaje</h2>
<p>Správa pohárových turnajů pro vybraný ročník.</p>

<p style="display:flex;gap:.6rem;flex-wrap:wrap">
  <a class="btn green" href="/liga-app/pohar/turnaj-vytvorit.php">
    🏆 Vytvořit turnaj
  </a>
</p>

<?php
// seznam turnajů pro výchozí ročník
$stmt = $conn->prepare("
  SELECT id, nazev, created_at
  FROM turnaje
  WHERE rocnik_id = ?
  ORDER BY created_at DESC
");
$stmt->bind_param("i", $default_id);
$stmt->execute();
$turnaje = $stmt->get_result();
?>

<?php if ($turnaje->num_rows): ?>
  <div style="margin-top:1rem">
    <table style="width:100%;border-collapse:collapse">
      <thead>
        <tr style="background:#f3f3f3">
          <th style="text-align:left;padding:.4rem">Název</th>
          <th style="padding:.4rem">Akce</th>
        </tr>
      </thead>
      <tbody>
      <?php while ($t = $turnaje->fetch_assoc()): ?>
        <tr>
          <td style="padding:.4rem">
            <?= htmlspecialchars($t['nazev']) ?>
          </td>
          <td style="padding:.4rem;text-align:center">
            <a class="btn secondary"
               href="/liga-app/pohar/pohar_turnaj.php?id=<?= (int)$t['id'] ?>">
              Náhled
            </a>
            <a class="btn"
               href="/liga-app/pohar/pohar_1kolo_admin.php?id=<?= (int)$t['id'] ?>">
              Správa 1. kola
            </a>
          </td>
        </tr>
      <?php endwhile; ?>
      </tbody>
    </table>
  </div>
<?php else: ?>
  <p style="color:#666;margin-top:.6rem">
    Pro tento ročník zatím není vytvořen žádný turnaj.
  </p>
<?php endif; ?>
<hr>
<li>
  <a href="/liga-app/admin/create_user.php">
    ➕ Vytvořit uživatele
  </a>
</li>





<script>
function ppFillDefaults(){
  var sel = document.getElementById('pp_rocnik');
  var txt = sel.options[sel.selectedIndex].text;
  document.getElementById('pp_nazev').value =
    'Prezidentský pohár ' + txt;
}
</script>

</body>
</html>
