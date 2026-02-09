<?php
// admin_pp_seed.php — Admin seed pro Prezidentský pohár (1. kolo)
// Požadavky: tabulky prezidentsky_turnaj, prezidentsky_zapas + view v_hraci_rocnik

require __DIR__.'/header.php';
require __DIR__.'/security/csrf.php';

if (!in_array($_SESSION['role'] ?? '', ['admin','stat_editor'], true)) {
  http_response_code(403); echo "<div class='notice'>Nemáš oprávnění.</div>"; require __DIR__.'/footer.php'; exit;
}

$active_rocnik_id = $_SESSION['rocnik_id'] ?? null;

// 1) načti ročníky (pro výběr)
$rocniky = $conn->query("SELECT id, nazev FROM rocniky ORDER BY id DESC")->fetch_all(MYSQLI_ASSOC);

// helpers
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES,'UTF-8'); }

// čtení parametrů z GET/POST (ročník, liga, stage, počet slotů)
$rocnik_id = isset($_REQUEST['rocnik_id']) ? (int)$_REQUEST['rocnik_id'] : (int)($active_rocnik_id ?: ($rocniky[0]['id'] ?? 1));
$liga_id   = isset($_REQUEST['liga_id']) && $_REQUEST['liga_id'] !== '' ? (int)$_REQUEST['liga_id'] : null;
$stage     = $_REQUEST['stage'] ?? 'O';      // R/O/QF/SF/F
$slots     = max(1, (int)($_REQUEST['slots'] ?? 16)); // počet zápasů v 1. kole (např. 16)

// 2) hráči pro ročník (+ volitelně filtr liga)
$sql = "SELECT hrac_id, jmeno, liga_id
        FROM v_hraci_rocnik
        WHERE rocnik_id = ?
        ".($liga_id ? " AND liga_id = ?" : "")."
        ORDER BY liga_id, jmeno";
$stmt = $conn->prepare($sql);
if ($liga_id) $stmt->bind_param('ii', $rocnik_id, $liga_id);
else          $stmt->bind_param('i',  $rocnik_id);
$stmt->execute();
$players = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// 3) ligy pro ročník (do filtru)
$ligy = $conn->prepare("SELECT DISTINCT liga_id FROM v_hraci_rocnik WHERE rocnik_id=? ORDER BY liga_id");
$ligy->bind_param('i',$rocnik_id);
$ligy->execute();
$ligy = $ligy->get_result()->fetch_all(MYSQLI_ASSOC);

// 4) existující turnaj pro ročník?
$turnaj = $conn->prepare("SELECT * FROM prezidentsky_turnaj WHERE rocnik_id=? ORDER BY id DESC LIMIT 1");
$turnaj->bind_param('i',$rocnik_id);
$turnaj->execute();
$turnaj = $turnaj->get_result()->fetch_assoc();

// vytvoření turnaje (POST create_turnaj)
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['create_turnaj']) && hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) {
  $nazev = trim($_POST['nazev'] ?? ('Prezidentský pohár '.$rocnik_id));
  $legs  = max(1,(int)($_POST['legs_to_win'] ?? 5));
  $io    = trim($_POST['in_out'] ?? '201 IN/OUT');

  $ins = $conn->prepare("INSERT INTO prezidentsky_turnaj(rocnik_id, nazev, legs_to_win, in_out, status)
                         VALUES (?,?,?,?, 'draft')");
  $ins->bind_param('isis', $rocnik_id, $nazev, $legs, $io);
  $ins->execute();
  header('Location: '.$_SERVER['PHP_SELF'].'?rocnik_id='.$rocnik_id); exit;
}

// uložení prvního kola (POST save_matches)
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['save_matches']) && hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) {
  if (!$turnaj) {
    echo "<div class='notice'>Nejdřív založ turnaj pro vybraný ročník.</div>";
  } else {
    $turnaj_id = (int)$turnaj['id'];
    $stage     = $_POST['stage'];
    $slots     = max(1, (int)$_POST['slots']);

    // projdi páry a vkládej
    $ins = $conn->prepare("INSERT INTO prezidentsky_zapas (turnaj_id, stage, slot, hrac1_id, hrac2_id, hrac1_jmeno, hrac2_jmeno)
                           VALUES (?,?,?,?,?,?,?)");
    for ($i=1; $i<=$slots; $i++){
      // varianta A: oba jako ID (selecty)
      $h1 = $_POST["h1_$i"] !== '' ? (int)$_POST["h1_$i"] : null;
      $h2 = $_POST["h2_$i"] !== '' ? (int)$_POST["h2_$i"] : null;
      // varianta B: fallback text
      $t1 = trim($_POST["t1_$i"] ?? '') ?: null;
      $t2 = trim($_POST["t2_$i"] ?? '') ?: null;

      if (!$h1 && !$t1 && !$h2 && !$t2) continue; // prázdný řádek přeskoč

      $ins->bind_param('issiiss', $turnaj_id, $stage, $i, $h1, $h2, $t1, $t2);
      $ins->execute();
    }
    echo "<div class='notice success'>Zápasy uloženy.</div>";
  }
}

// znovu načti turnaj po případném vytvoření
if (!$turnaj) {
  $turnaj = ['id'=>null,'nazev'=>null,'legs_to_win'=>5,'in_out'=>'201 IN/OUT','status'=>'draft'];
}
?>
<style>
.pp-admin {max-width: 1100px; margin: 1rem auto; background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:1rem;}
.pp-row {display:grid; grid-template-columns: 1fr 1fr; gap:1rem; margin-bottom:1rem;}
.pp-box {border:1px solid #e5e7eb; border-radius:10px; padding:1rem;}
.pp-form table{width:100%; border-collapse:collapse;}
.pp-form th,.pp-form td{border-bottom:1px solid #eef2f7; padding:.35rem .5rem; vertical-align:middle;}
select, input[type=number], input[type=text]{padding:.4rem .55rem; border:1px solid #dfe3ea; border-radius:8px; width:100%}
.btn{padding:.5rem .8rem; border:1px solid #dfe3ea; border-radius:10px; background:#0d47a1; color:#fff;}
.btn.secondary{background:#fff; color:#0d47a1;}
.notice{margin:.5rem 0; padding:.5rem .75rem; background:#fff8db; border:1px solid #f1e2a3; border-radius:8px}
.notice.success{background:#e8fbef; border-color:#b8ebc8}
</style>

<div class="pp-admin">
  <h2>Prezidentský pohár – seed prvního kola</h2>

  <form method="get" class="pp-box">
    <div class="pp-row">
      <label>Ročník
        <select name="rocnik_id" onchange="this.form.submit()">
          <?php foreach($rocniky as $r): ?>
            <option value="<?= (int)$r['id'] ?>" <?= $rocnik_id==$r['id']?'selected':'' ?>><?= h($r['nazev']) ?> (ID <?= (int)$r['id'] ?>)</option>
          <?php endforeach; ?>
        </select>
      </label>

      <label>Liga (volitelné filtrování hráčů)
        <select name="liga_id" onchange="this.form.submit()">
          <option value="">— všechny ligy —</option>
          <?php foreach($ligy as $l): ?>
            <option value="<?= (int)$l['liga_id'] ?>" <?= $liga_id==$l['liga_id']?'selected':'' ?>>Liga <?= (int)$l['liga_id'] ?></option>
          <?php endforeach; ?>
        </select>
      </label>
    </div>

    <div class="pp-row">
      <label>Fáze (stage)
        <select name="stage">
          <option value="R"  <?= $stage==='R'?'selected':'' ?>>Předkola</option>
          <option value="O"  <?= $stage==='O'?'selected':'' ?>>1/16 (O)</option>
          <option value="QF" <?= $stage==='QF'?'selected':'' ?>>Čtvrtfinále (QF)</option>
          <option value="SF" <?= $stage==='SF'?'selected':'' ?>>Semifinále (SF)</option>
          <option value="F"  <?= $stage==='F'?'selected':'' ?>>Finále (F)</option>
        </select>
      </label>

      <label>Počet zápasů v tomto kole
        <input type="number" name="slots" value="<?= (int)$slots ?>" min="1" max="32" />
      </label>
    </div>

    <button class="btn secondary">Aktualizovat formulář</button>
  </form>

  <div class="pp-row">
    <div class="pp-box">
      <h3>Turnaj pro ročník</h3>
      <?php if ($turnaj['id']): ?>
        <p><b><?= h($turnaj['nazev']) ?></b><br>
           IN/OUT: <?= h($turnaj['in_out']) ?> • Do <?= (int)$turnaj['legs_to_win'] ?> legů • Status: <?= h($turnaj['status']) ?><br>
           <small>(ID turnaje: <?= (int)$turnaj['id'] ?>)</small></p>
      <?php else: ?>
        <form method="post">
          <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
          <input type="hidden" name="rocnik_id" value="<?= (int)$rocnik_id ?>">
          <label>Název <input name="nazev" value="Prezidentský pohár <?= (int)$rocnik_id ?>"></label>
          <div class="pp-row">
            <label>IN/OUT <input name="in_out" value="201 IN/OUT"></label>
            <label>Počet vítězných legů <input type="number" name="legs_to_win" value="5" min="1" max="9"></label>
          </div>
          <button class="btn" name="create_turnaj" value="1">Založit turnaj</button>
        </form>
      <?php endif; ?>
    </div>

    <div class="pp-box">
      <h3>Hráči v ročníku <?= (int)$rocnik_id ?> <?= $liga_id?('• Liga '.(int)$liga_id):'' ?></h3>
      <div style="max-height:260px; overflow:auto; border:1px solid #eef2f7; border-radius:8px; padding:.5rem;">
        <?php foreach($players as $p): ?>
          <div>[L<?= (int)$p['liga_id'] ?>] <?= h($p['jmeno']) ?> (ID <?= (int)$p['hrac_id'] ?>)</div>
        <?php endforeach; ?>
        <?php if (!$players): ?><em>Žádní hráči pro zvolený filtr.</em><?php endif; ?>
      </div>
    </div>
  </div>

  <div class="pp-box pp-form">
    <h3>Vytvořit první kolo – nastav dvojice</h3>
    <?php if (!$turnaj['id']): ?>
      <div class="notice">Nejdřív založ turnaj pro vybraný ročník.</div>
    <?php else: ?>
    <form method="post">
      <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
      <input type="hidden" name="stage" value="<?= h($stage) ?>">
      <input type="hidden" name="slots" value="<?= (int)$slots ?>">

      <table>
        <thead>
          <tr>
            <th style="width:55%">Hráč 1 (nebo text)</th>
            <th style="width:55%">Hráč 2 (nebo text)</th>
          </tr>
        </thead>
        <tbody>
        <?php
          // options pro selecty – seskupíme podle ligy
          $optsByLiga = [];
          foreach($players as $p){ $optsByLiga[$p['liga_id']][] = $p; }
          for ($i=1; $i<=$slots; $i++):
        ?>
          <tr>
            <td>
              <div style="display:flex; gap:.5rem;">
                <select name="h1_<?= $i ?>" style="flex:1">
                  <option value="">— vyber hráče —</option>
                  <?php foreach($optsByLiga as $lid=>$arr): ?>
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
              <div style="display:flex; gap:.5rem;">
                <select name="h2_<?= $i ?>" style="flex:1">
                  <option value="">— vyber hráče —</option>
                  <?php foreach($optsByLiga as $lid=>$arr): ?>
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

      <div style="margin-top:.75rem; display:flex; gap:.5rem;">
        <button class="btn" name="save_matches" value="1">Uložit zápasy</button>
        <a class="btn secondary" href="<?= h($BASE_URL) ?>/prezidentsky-pohar.php">Zpět na pavouka</a>
      </div>
    </form>
    <?php endif; ?>
  </div>
</div>

<?php require __DIR__.'/footer.php'; ?>
