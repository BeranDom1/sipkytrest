<?php
// /liga-app/admin/pp_seed.php
require_once __DIR__.'/../db.php';
require_once __DIR__.'/_auth.php';
require_once __DIR__.'/../security/csrf.php';

if (!in_array($_SESSION['role'] ?? '', ['admin','stat_editor'], true)) {
  http_response_code(403); die('Nemáš oprávnění.');
}

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES,'UTF-8'); }

$rocnik_id = isset($_REQUEST['rocnik_id']) ? (int)$_REQUEST['rocnik_id'] : (int)($_SESSION['rocnik_id'] ?? 0);
$stage = $_REQUEST['stage'] ?? 'O';         // R/O/QF/SF/F
$slots = max(1, (int)($_REQUEST['slots'] ?? 16));
$liga_id = isset($_REQUEST['liga_id']) && $_REQUEST['liga_id']!=='' ? (int)$_REQUEST['liga_id'] : null;

// turnaj pro ročník
$turnaj = null;
if ($rocnik_id) {
  $st = $conn->prepare("SELECT * FROM prezidentsky_turnaj WHERE rocnik_id=? ORDER BY id DESC LIMIT 1");
  $st->bind_param('i',$rocnik_id);
  $st->execute(); $turnaj = $st->get_result()->fetch_assoc();
  if (!$turnaj) { die('Nejdřív založ turnaj v pp_index.php'); }
}

// hráči pro ročník (volitelně filtr liga)
$sql = "SELECT hrac_id, jmeno, liga_id
        FROM v_hraci_rocnik
        WHERE rocnik_id = ?".($liga_id ? " AND liga_id=?" : "")."
        ORDER BY liga_id, jmeno";
$stmt = $conn->prepare($sql);
if ($liga_id) $stmt->bind_param('ii', $rocnik_id, $liga_id);
else          $stmt->bind_param('i',  $rocnik_id);
$stmt->execute();
$players = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// ligy do filtru
$ligy = $conn->prepare("SELECT DISTINCT liga_id FROM v_hraci_rocnik WHERE rocnik_id=? ORDER BY liga_id");
$ligy->bind_param('i',$rocnik_id);
$ligy->execute(); $ligy = $ligy->get_result()->fetch_all(MYSQLI_ASSOC);

// uložení
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['save']) && hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
  $turnaj_id = (int)$turnaj['id'];
  $stage     = $_POST['stage'];
  $slots     = max(1,(int)$_POST['slots']);

  $ins = $conn->prepare("INSERT INTO prezidentsky_zapas (turnaj_id, stage, slot, hrac1_id, hrac2_id, hrac1_jmeno, hrac2_jmeno)
                         VALUES (?,?,?,?,?,?,?)");
  for ($i=1; $i<=$slots; $i++){
    $h1 = $_POST["h1_$i"] !== '' ? (int)$_POST["h1_$i"] : null;
    $h2 = $_POST["h2_$i"] !== '' ? (int)$_POST["h2_$i"] : null;
    $t1 = trim($_POST["t1_$i"] ?? '') ?: null;
    $t2 = trim($_POST["t2_$i"] ?? '') ?: null;

    if (!$h1 && !$t1 && !$h2 && !$t2) continue;

    $ins->bind_param('issiiss', $turnaj_id, $stage, $i, $h1, $h2, $t1, $t2);
    $ins->execute();
  }
  header('Location: /liga-app/prezidentsky-pohar.php');
  exit;
}
?>
<!doctype html>
<html lang="cs">
<head>
<meta charset="utf-8">
<title>PP – první kolo</title>
<link rel="stylesheet" href="/liga-app/style.css">
<style>
.card{border:1px solid #ddd;padding:1rem;border-radius:12px;max-width:1100px;margin:2rem auto;background:#fff}
.btn{padding:.5rem .9rem;border-radius:10px;border:1px solid #333;background:#111;color:#fff;cursor:pointer}
.btn.secondary{background:#fff;color:#111}
select,input[type=text],input[type=number]{padding:.35rem .5rem;border:1px solid #dfe3ea;border-radius:8px}
table{width:100%;border-collapse:collapse}
th,td{border-bottom:1px solid #eef2f7;padding:.35rem .5rem}
.flex{display:flex;gap:.5rem}
</style>
</head>
<body>
<div class="card">
  <h1>Prezidentský pohár – vytvořit 1. kolo</h1>

  <form method="get" class="flex" style="flex-wrap:wrap;margin-bottom:1rem">
    <input type="hidden" name="rocnik_id" value="<?= (int)$rocnik_id ?>">
    <label>Fáze
      <select name="stage" onchange="this.form.submit()">
        <option value="P"  <?= $stage==='R'?'selected':'' ?>>1. Předkolo</option>
        <option value="R"  <?= $stage==='R'?'selected':'' ?>>2. Předkolo</option>
        <option value="O"  <?= $stage==='O'?'selected':'' ?>>1/16 (O)</option>
        <option value="QF" <?= $stage==='QF'?'selected':'' ?>>ČF</option>
        <option value="SF" <?= $stage==='SF'?'selected':'' ?>>SF</option>
        <option value="F"  <?= $stage==='F'?'selected':'' ?>>Finále</option>
      </select>
    </label>
    <label>Počet zápasů
      <input type="number" name="slots" value="<?= (int)$slots ?>" min="1" max="32" onchange="this.form.submit()">
    </label>
    <label>Liga (volitelně)
      <select name="liga_id" onchange="this.form.submit()">
        <option value="">— všechny —</option>
        <?php foreach($ligy as $l): ?>
          <option value="<?= (int)$l['liga_id'] ?>" <?= $liga_id==$l['liga_id']?'selected':'' ?>>Liga <?= (int)$l['liga_id'] ?></option>
        <?php endforeach; ?>
      </select>
    </label>
  </form>

  <form method="post">
    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
    <input type="hidden" name="stage" value="<?= h($stage) ?>">
    <input type="hidden" name="slots" value="<?= (int)$slots ?>">

    <table>
      <thead>
        <tr><th>Hráč 1 (nebo text)</th><th>Hráč 2 (nebo text)</th></tr>
      </thead>
      <tbody>
      <?php
        $opts = [];
        foreach ($players as $p) { $opts[$p['liga_id']][] = $p; }
        for ($i=1; $i<=$slots; $i++):
      ?>
        <tr>
          <td>
            <div class="flex">
              <select name="h1_<?= $i ?>" style="flex:1">
                <option value="">— vyber hráče —</option>
                <?php foreach($opts as $lid=>$arr): ?>
                  <optgroup label="Liga <?= (int)$lid ?>">
                    <?php foreach($arr as $p): ?>
                      <option value="<?= (int)$p['hrac_id'] ?>">[L<?= (int)$p['liga_id'] ?>] <?= h($p['jmeno']) ?></option>
                    <?php endforeach; ?>
                  </optgroup>
                <?php endforeach; ?>
              </select>
              <input type="text" name="t1_<?= $i ?>" placeholder="alternativně text (např. vítěz R7)" style="flex:1">
            </div>
          </td>
          <td>
            <div class="flex">
              <select name="h2_<?= $i ?>" style="flex:1">
                <option value="">— vyber hráče —</option>
                <?php foreach($opts as $lid=>$arr): ?>
                  <optgroup label="Liga <?= (int)$lid ?>">
                    <?php foreach($arr as $p): ?>
                      <option value="<?= (int)$p['hrac_id'] ?>">[L<?= (int)$p['liga_id'] ?>] <?= h($p['jmeno']) ?></option>
                    <?php endforeach; ?>
                  </optgroup>
                <?php endforeach; ?>
              </select>
              <input type="text" name="t2_<?= $i ?>" placeholder="alternativně text" style="flex:1">
            </div>
          </td>
        </tr>
      <?php endfor; ?>
      </tbody>
    </table>

    <p style="margin-top:.75rem;display:flex;gap:.5rem;">
      <button class="btn" name="save" value="1">Uložit zápasy</button>
      <a class="btn secondary" href="/liga-app/admin/pp_index.php?rocnik_id=<?= (int)$rocnik_id ?>">Zpět na přehled</a>
      <a class="btn secondary" href="/liga-app/prezidentsky-pohar.php">Zobrazit pavouka</a>
    </p>
  </form>
</div>
</body>
</html>
