<?php
require_once __DIR__.'/../db.php';
require_once __DIR__.'/_auth.php';
require_once __DIR__.'/../security/csrf.php';

$rocnik_id = (int)($_GET['rocnik_id'] ?? 0);
if ($rocnik_id<=0) { header('Location: /liga-app/admin/index.php'); exit; }

$lockRow = $conn->query("SELECT nazev, locked FROM rocniky WHERE id={$rocnik_id}")->fetch_assoc();
if (!$lockRow) { header('Location: /liga-app/admin/index.php'); exit; }
$sezona_nazev = $lockRow['nazev'];
$locked = (int)$lockRow['locked'] === 1;

// ligy + rozřazení
$ligy = $conn->query("SELECT id,cislo,nazev FROM ligy ORDER BY poradi")->fetch_all(MYSQLI_ASSOC);
$stmt = $conn->prepare("
  SELECT h.id AS hrac_id, h.jmeno, hs.liga_id
  FROM hraci h
  LEFT JOIN hraci_v_sezone hs ON hs.hrac_id=h.id AND hs.rocnik_id=?
  ORDER BY h.jmeno
");
$stmt->bind_param('i', $rocnik_id);
$stmt->execute();
$res = $stmt->get_result();

$rozpis=[]; $nezarazeni=[];
while($r=$res->fetch_assoc()){ if ($r['liga_id']) $rozpis[(int)$r['liga_id']][]=$r; else $nezarazeni[]=$r; }
$csrf = csrf_token();
?>
<!doctype html><meta charset="utf-8">
<title>Rozřazení – <?= htmlspecialchars($sezona_nazev) ?></title>
<link rel="stylesheet" href="/liga-app/style.css">
<style>
.grid{display:grid;grid-template-columns:repeat(3,minmax(260px,1fr));gap:12px}
@media (max-width:900px){.grid{grid-template-columns:1fr}}
.col{background:#fff;border:1px solid #ddd;border-radius:12px;padding:10px}
.col h3{margin:.2rem 0 .6rem}
.box{min-height:220px;border:1px dashed #bbb;border-radius:10px;padding:.5rem}
.item{background:#f8f9fa;border:1px solid #e5e7eb;border-radius:8px;padding:.35rem .55rem;margin:.3rem 0;<?= $locked ? 'cursor:default;' : 'cursor:grab;' ?>}
.btn{padding:.5rem .9rem;border-radius:10px;border:1px solid #333;background:#111;color:#fff;cursor:pointer}
.alert{padding:.6rem .8rem;background:#ffe8cc;border:1px solid #f0c089;border-radius:10px;margin:.8rem 0}
</style>

<h1>Rozřazení hráčů – <?= htmlspecialchars($sezona_nazev) ?></h1>
<?php if ($locked): ?>
  <div class="alert">Tato sezóna je <b>uzamčená</b>. Úpravy nejsou povoleny.</div>
<?php endif; ?>

<form id="frm" method="post" action="/liga-app/admin/save_rozrazeni.php">
  <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
  <input type="hidden" name="rocnik_id" value="<?= (int)$rocnik_id ?>">

  <div class="grid">
    <?php foreach($ligy as $l): ?>
      <div class="col">
        <h3><?= (int)$l['cislo'] ?>. <?= htmlspecialchars($l['nazev']) ?></h3>
        <div class="box" data-liga-id="<?= (int)$l['id'] ?>">
          <?php foreach(($rozpis[$l['id']]??[]) as $hr): ?>
            <div class="item" <?= $locked?'':'draggable="true"' ?> data-hrac-id="<?= (int)$hr['hrac_id'] ?>">
              <?= htmlspecialchars($hr['jmeno']) ?>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endforeach; ?>

    <div class="col">
      <h3>Nezařazení</h3>
      <div class="box" data-liga-id="">
        <?php foreach($nezarazeni as $hr): ?>
          <div class="item" <?= $locked?'':'draggable="true"' ?> data-hrac-id="<?= (int)$hr['hrac_id'] ?>">
            <?= htmlspecialchars($hr['jmeno']) ?>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <div style="margin-top:12px;display:flex;gap:8px;flex-wrap:wrap">
    <?php if (!$locked): ?>
      <button class="btn" type="submit">Uložit změny</button>
    <?php endif; ?>
    <a class="btn" href="/liga-app/admin/index.php">Zpět</a>
  </div>
</form>

<?php if (!$locked): ?>
<script>
let dragged=null;
document.querySelectorAll('.item[draggable="true"]').forEach(it=>it.addEventListener('dragstart', e=>dragged=it));
document.querySelectorAll('.box').forEach(b=>{
  b.addEventListener('dragover', e=>e.preventDefault());
  b.addEventListener('drop', e=>{ e.preventDefault(); if(dragged) b.appendChild(dragged); });
});
const frm=document.getElementById('frm');
frm.addEventListener('submit', e=>{
  document.querySelectorAll('input[name^="liga_"],input[name="neza[]"]').forEach(x=>x.remove());
  document.querySelectorAll('.box').forEach(b=>{
    const ligaId=b.getAttribute('data-liga-id');
    b.querySelectorAll('.item').forEach(it=>{
      const i=document.createElement('input'); i.type='hidden';
      i.name = ligaId ? `liga_${ligaId}[]` : 'neza[]';
      i.value = it.getAttribute('data-hrac-id');
      frm.appendChild(i);
    });
  });
});
</script>
<?php endif; ?>
