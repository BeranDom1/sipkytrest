<?php
require_once __DIR__.'/../db.php';
require_once __DIR__.'/_auth.php';

$sezony = $conn->query("SELECT id, nazev, locked FROM rocniky ORDER BY id DESC")->fetch_all(MYSQLI_ASSOC);

// vyber předem „nejnovější odemčenou“; fallback = první v seznamu
$default_id = null;
foreach ($sezony as $s) {
  if ((int)$s['locked'] === 0) { $default_id = (int)$s['id']; break; }
}
if (!$default_id && $sezony) $default_id = (int)$sezony[0]['id'];
?>
<!doctype html>
<html lang="cs">
<head>
<meta charset="utf-8">
<title>Administrace</title>
<link rel="stylesheet" href="/liga-app/style.css">
<style>
.card{border:1px solid #ddd;padding:1rem;border-radius:12px;max-width:760px;margin:2rem auto;background:#fff}
button,.btn{padding:.5rem .9rem;border-radius:10px;border:1px solid #333;background:#111;color:#fff;cursor:pointer}
select{padding:.35rem .5rem}
.lock{color:#888}
</style>
</head>
<body>
<div class="card">
  <h1>Administrace</h1>
  <p>Vyber sezónu a otevři rozřazení hráčů (0.–5. liga). Staré sezóny jsou uzamčené.</p>

  <!-- 1) klasický form (funguje bez JS) -->
  <form id="openForm" action="/liga-app/admin/rozrazeni.php" method="get" style="display:flex;gap:.5rem;align-items:center;flex-wrap:wrap">
    <label for="rocnik">Sezóna:</label>
    <select id="rocnik" name="rocnik_id" required>
      <?php foreach($sezony as $s): ?>
        <option value="<?= (int)$s['id'] ?>" <?= $s['id']==$default_id?'selected':''; ?>>
          <?= htmlspecialchars($s['nazev']) ?><?= (int)$s['locked'] ? ' (uzamčeno)' : '' ?>
        </option>
      <?php endforeach; ?>
    </select>
    <button type="submit" id="openBtn">Otevřít rozřazení</button>
  </form>

  <!-- 2) JS fallback: i kdyby submit něco blokovalo, přesměrujeme ručně -->
  <script>
  (function(){
    var btn = document.getElementById('openBtn');
    var sel = document.getElementById('rocnik');
    btn.addEventListener('click', function(e){
      try {
        var id = sel && sel.value ? sel.value : '<?= (int)$default_id ?>';
        if (id) {
          // Když by z jakéhokoli důvodu submit neprošel, ručně přesměruj
          window.location.href = '/liga-app/admin/rozrazeni.php?rocnik_id=' + encodeURIComponent(id);
        }
      } catch(_){}
      // submit necháme proběhnout také (dvojí pojistka)
    });
  })();
  </script>

  <hr style="margin:1rem 0">
  <p class="lock">Tip: Chceš-li ověřit, že stránka existuje, zkus rovnou otevřít:
    <a href="/liga-app/admin/rozrazeni.php?rocnik_id=<?= (int)$default_id ?>">/admin/rozrazeni.php?rocnik_id=<?= (int)$default_id ?></a>
  </p>
</div>
</body>
</html>
