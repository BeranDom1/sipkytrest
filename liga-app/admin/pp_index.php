<?php
// /liga-app/admin/pp_index.php
require_once __DIR__.'/../db.php';
require_once __DIR__.'/_auth.php';
require_once __DIR__.'/../security/csrf.php';

if (!in_array($_SESSION['role'] ?? '', ['admin','stat_editor'], true)) {
  http_response_code(403); die('Nemáš oprávnění.');
}

$rocnik_id = isset($_REQUEST['rocnik_id']) ? (int)$_REQUEST['rocnik_id'] : (int)($_SESSION['rocnik_id'] ?? 0);

// načti ročníky pro dropdown
$sezony = $conn->query("SELECT id, nazev FROM rocniky ORDER BY id DESC")->fetch_all(MYSQLI_ASSOC);

// existující turnaj?
$turnaj = null;
if ($rocnik_id) {
  $st = $conn->prepare("SELECT * FROM prezidentsky_turnaj WHERE rocnik_id=? ORDER BY id DESC LIMIT 1");
  $st->bind_param('i',$rocnik_id);
  $st->execute(); $turnaj = $st->get_result()->fetch_assoc();
}

// založení turnaje
if (isset($_POST['create'])) {
  $rocnik_id = (int)$_POST['rocnik_id'];

  // už existuje?
  $chk = $conn->prepare("SELECT id FROM prezidentsky_turnaj WHERE rocnik_id=? LIMIT 1");
  $chk->bind_param('i',$rocnik_id);
  $chk->execute();
  $exists = $chk->get_result()->fetch_column();

  if (!$exists) {
    $nazev = trim($_POST['nazev'] ?? 'Prezidentský pohár');
    $legs  = max(1,(int)($_POST['legs_to_win'] ?? 5));
    $io    = trim($_POST['in_out'] ?? '201 IN/OUT');

    $ins = $conn->prepare("INSERT INTO prezidentsky_turnaj(rocnik_id, nazev, legs_to_win, in_out, status)
                           VALUES (?,?,?,?, 'draft')");
    $ins->bind_param('isis', $rocnik_id, $nazev, $legs, $io);
    $ins->execute();
  }
  header('Location: pp_index.php?rocnik_id='.$rocnik_id);
  exit;
}


function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES,'UTF-8'); }
?>
<!doctype html>
<html lang="cs">
<head>
<meta charset="utf-8">
<title>Administrace – Prezidentský pohár</title>
<link rel="stylesheet" href="/liga-app/style.css">
<style>
.card{border:1px solid #ddd;padding:1rem;border-radius:12px;max-width:900px;margin:2rem auto;background:#fff}
.btn{padding:.5rem .9rem;border-radius:10px;border:1px solid #333;background:#111;color:#fff;cursor:pointer}
.btn.secondary{background:#fff;color:#111}
select,input[type=text],input[type=number]{padding:.35rem .5rem;border:1px solid #dfe3ea;border-radius:8px}
.form-row{display:grid;grid-template-columns: 1fr 1fr;gap:.75rem}
</style>
</head>
<body>
<div class="card">
  <h1>Prezidentský pohár – správa</h1>

  <form method="get" style="display:flex;gap:.5rem;align-items:center;flex-wrap:wrap;margin-bottom:1rem">
    <label>Sezóna:
      <select name="rocnik_id" onchange="this.form.submit()">
        <?php foreach($sezony as $s): ?>
          <option value="<?= (int)$s['id'] ?>" <?= $rocnik_id==$s['id']?'selected':'' ?>>
            <?= h($s['nazev']) ?> (ID <?= (int)$s['id'] ?>)
          </option>
        <?php endforeach; ?>
      </select>
    </label>
    <noscript><button class="btn secondary">Změnit</button></noscript>
  </form>

  <?php if (!$rocnik_id): ?>
    <p>Vyber sezónu.</p>
  <?php else: ?>
    <?php if ($turnaj): ?>
      <p><b>Turnaj existuje:</b> <?= h($turnaj['nazev']) ?> • <?= h($turnaj['in_out']) ?> • do <?= (int)$turnaj['legs_to_win'] ?> legů • status <b><?= h($turnaj['status']) ?></b></p>
      <p>
        <a class="btn" href="/liga-app/admin/pp_seed.php?rocnik_id=<?= (int)$rocnik_id ?>">Otevřít administraci (1. kolo / párování)</a>
        <a class="btn secondary" href="/liga-app/prezidentsky-pohar.php">Zobrazit pavouka</a>
      </p>
    <?php else: ?>
      <h3>Založit turnaj pro vybraný ročník</h3>
      <form method="post" class="form">
        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
        <input type="hidden" name="rocnik_id" value="<?= (int)$rocnik_id ?>">
        <div class="form-row">
          <label>Název
            <input type="text" name="nazev" value="Prezidentský pohár <?= (int)$rocnik_id ?>">
          </label>
          <label>IN/OUT
            <input type="text" name="in_out" value="201 IN/OUT">
          </label>
        </div>
        <div class="form-row" style="margin-top:.5rem">
          <label>Počet vítězných legů
            <input type="number" name="legs_to_win" value="5" min="1" max="9">
          </label>
        </div>
        <p style="margin-top:.75rem"><button class="btn" name="create" value="1">Vytvořit turnaj</button></p>
      </form>
    <?php endif; ?>
  <?php endif; ?>
</div>
</body>
</html>
