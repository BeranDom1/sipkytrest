<?php
require_once __DIR__.'/../db.php';
require_once __DIR__.'/_auth.php';
require_once __DIR__.'/../security/csrf.php';

// ročníky
$rocniky = $conn->query("
  SELECT id, nazev
  FROM rocniky
  ORDER BY id DESC
")->fetch_all(MYSQLI_ASSOC);

// vybraný ročník
$rocnik_id = isset($_GET['rocnik_id']) ? (int)$_GET['rocnik_id'] : ($rocniky[0]['id'] ?? 0);

// ligy
$ligy = $conn->query("
  SELECT id, cislo, nazev
  FROM ligy
  ORDER BY poradi
")->fetch_all(MYSQLI_ASSOC);

// existující názvy pro ročník
$nazvy = [];
$st = $conn->prepare("
  SELECT liga_id, nazev
  FROM ligy_nazvy
  WHERE rocnik_id = ?
");
$st->bind_param('i', $rocnik_id);
$st->execute();
$res = $st->get_result();
while ($r = $res->fetch_assoc()) {
  $nazvy[(int)$r['liga_id']] = $r['nazev'];
}
$st->close();

$msg = '';
$err = '';

// uložení
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!csrf_check($_POST['csrf'] ?? '')) {
    $err = 'CSRF chyba.';
  } else {
    $del = $conn->prepare("DELETE FROM ligy_nazvy WHERE rocnik_id = ?");
$del->bind_param('i', $rocnik_id);
$del->execute();
$del->close();

    $ins = $conn->prepare("
      INSERT INTO ligy_nazvy (rocnik_id, liga_id, nazev)
      VALUES (?, ?, ?)
    ");

    foreach ($_POST['nazev'] ?? [] as $liga_id => $nazev) {
      $nazev = trim($nazev);
      if ($nazev !== '') {
        $lid = (int)$liga_id;
        $ins->bind_param('iis', $rocnik_id, $lid, $nazev);
        $ins->execute();
      }
    }
    $ins->close();
    $msg = 'Názvy lig byly uloženy.';
  }
}

$csrf = csrf_token();
?>
<!doctype html>
<html lang="cs">
<head>
<meta charset="utf-8">
<title>Názvy lig podle ročníku</title>
<link rel="stylesheet" href="/liga-app/style.css">
<style>
.card{border:1px solid #ddd;padding:1rem;border-radius:12px;max-width:760px;margin:2rem auto;background:#fff}
label{display:block;margin:.6rem 0 .2rem}
input{width:100%;padding:.4rem}
.btn{padding:.5rem .9rem;border-radius:10px;border:1px solid #333;background:#111;color:#fff;cursor:pointer}
.msg{color:#0a0}
.err{color:#c00}
</style>
</head>
<body>

<div class="card">
  <h1>Názvy lig pro ročník</h1>

  <form method="get" style="margin-bottom:1rem">
    <label>Ročník</label>
    <select name="rocnik_id" onchange="this.form.submit()">
      <?php foreach ($rocniky as $r): ?>
        <option value="<?= (int)$r['id'] ?>" <?= $r['id']==$rocnik_id?'selected':'' ?>>
          <?= htmlspecialchars($r['nazev']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </form>

  <?php if ($msg): ?><p class="msg"><?= htmlspecialchars($msg) ?></p><?php endif; ?>
  <?php if ($err): ?><p class="err"><?= htmlspecialchars($err) ?></p><?php endif; ?>

  <form method="post">
    <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">

    <?php foreach ($ligy as $l): 
      $lid = (int)$l['id'];
      $val = $nazvy[$lid] ?? $l['nazev'];
    ?>
      <label><?= htmlspecialchars($l['cislo']) ?>. liga</label>
      <input name="nazev[<?= $lid ?>]" value="<?= htmlspecialchars($val) ?>">
    <?php endforeach; ?>

    <p style="margin-top:1rem">
      <button class="btn" type="submit">Uložit názvy</button>
      <a class="btn" href="/liga-app/admin/index.php">Zpět</a>
    </p>
  </form>
</div>

</body>
</html>
