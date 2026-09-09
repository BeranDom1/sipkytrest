<?php
require __DIR__.'/db.php';
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

$tz = new DateTimeZone('Europe/Prague');

/* ===== funkce: najde další platný den (přeskočí Po+Út) ===== */
function najdiPlatnyDen(DateTime $day, int $smer): DateTime {
    do {
        $day->modify(($smer > 0 ? '+1 day' : '-1 day'));
        $dow = (int)$day->format('N'); // 1=Po ... 7=Ne
    } while (in_array($dow, [1, 2]));
    return $day;
}

/* ===== vybraný den ===== */
$datum = $_GET['datum'] ?? date('Y-m-d');
$day = DateTime::createFromFormat('Y-m-d', $datum, $tz);
if (!$day) $day = new DateTime('today', $tz);

/* ===== zákaz minulosti ===== */
$today = new DateTime('today', $tz);
if ($day < $today) {
    $day = clone $today;
}

/* ===== přeskočení Po + Út ===== */
if (in_array((int)$day->format('N'), [1,2])) {
    $day = najdiPlatnyDen(clone $day, +1);
}

$datum = $day->format('Y-m-d');

/* ===== české dny ===== */
$dny = [
    'Monday'    => 'Pondělí',
    'Tuesday'   => 'Úterý',
    'Wednesday' => 'Středa',
    'Thursday'  => 'Čtvrtek',
    'Friday'    => 'Pátek',
    'Saturday'  => 'Sobota',
    'Sunday'    => 'Neděle',
];
$denCesky = $dny[$day->format('l')];

/* ===== navigace ===== */
$nextDay = najdiPlatnyDen(clone $day, +1);
$prevDay = najdiPlatnyDen(clone $day, -1);

/* ===== neděle ===== */
$jeNedele = ((int)$day->format('N') === 7);

/* ===== POST ===== */
$msg = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $terc   = (int)($_POST['terc'] ?? 0);
    $hodina = (int)($_POST['hodina'] ?? 0);
    $jmeno  = trim($_POST['jmeno'] ?? '');

    $dow = (int)$day->format('N');

    if (in_array($dow, [1,2])) {
        $msg = 'V pondělí a úterý je zavřeno.';
    }
    elseif ($dow === 7 && $terc >= 1 && $terc <= 6) {
        $msg = 'V neděli je otevřen pouze Čenkov.';
    }
    elseif ($terc < 1 || $terc > 7 || $hodina < 15 || $hodina > 21) {
        $msg = 'Neplatná data.';
    }
    else {
        if ($action === 'create') {
            if ($jmeno === '' || mb_strlen($jmeno) > 60) {
                $msg = 'Zadejte jméno.';
            } else {
                $stmt = $conn->prepare(
                    "INSERT INTO rezervace (datum, hodina, terc, jmeno)
                     VALUES (?,?,?,?)"
                );
                $stmt->bind_param('siis', $datum, $hodina, $terc, $jmeno);
                $msg = $stmt->execute()
                    ? 'Rezervace uložena.'
                    : 'Termín je již obsazen.';
            }
        }

        if ($action === 'delete') {
            $stmt = $conn->prepare(
                "DELETE FROM rezervace WHERE datum=? AND hodina=? AND terc=?"
            );
            $stmt->bind_param('sii', $datum, $hodina, $terc);
            $stmt->execute();
            $msg = 'Rezervace zrušena.';
        }
    }
}

/* ===== načtení rezervací ===== */
$rez = [];
$q = $conn->prepare(
    "SELECT hodina, terc, jmeno FROM rezervace WHERE datum=?"
);
$q->bind_param('s', $datum);
$q->execute();
$res = $q->get_result();
while ($r = $res->fetch_assoc()) {
    $rez[$r['hodina']][$r['terc']] = $r['jmeno'];
}

require __DIR__.'/header.php';
?>

<main class="nk-content nk-content--flat rez-content">
<div class="container">

<h2>Rezervace terčů</h2>

<?php if ($msg): ?>
<div class="msg-box"><?= htmlspecialchars($msg) ?></div>
<?php endif; ?>

<div class="rez-opening">
  <div class="opening-box">
    <h4>🕓 Otevírací doba Sport Baru</h4>
    <ul>
      <li><strong>Středa – Sobota</strong></li>
      <li>17:00 – 22:00</li>
    </ul>
  </div>

  <div class="opening-box opening-cenkov">
    <h4>🎯 Otevírací doba Čenkov</h4>
    <ul>
      <li>Spíše na domluvě na Messengeru Šipky Třešť</li>
      <li>Většinou otevřeno <strong>pátek a neděle</strong></li>
    </ul>
  </div>
</div>

<div class="rez-day-nav">
    <?php if ($datum !== $today->format('Y-m-d')): ?>
        <a href="?datum=<?= $prevDay->format('Y-m-d') ?>">◀</a>
    <?php else: ?>
        <span class="nav-disabled">◀</span>
    <?php endif; ?>

    <strong><?= $denCesky ?> <?= $day->format('d. m. Y') ?></strong>

    <a href="?datum=<?= $nextDay->format('Y-m-d') ?>">▶</a>
</div>
<div class="rez-table-wrapper">
<table class="rez-table">
<tr>
    <th>Čas</th>
    <?php for ($t=1;$t<=7;$t++): ?>
        <th><?= $t === 7 ? 'Čenkov' : 'Terč '.$t ?></th>
    <?php endfor; ?>
</tr>

<?php for ($h=15;$h<=21;$h++): ?>
<tr>
    <td class="rez-time"><?= $h ?>:00</td>

    <?php for ($t=1;$t<=7;$t++): ?>
        <?php $zakazanyTerc = ($jeNedele && $t <= 6); ?>

        <td class="rez-cell">
            <div class="rez-terc terc-<?= $t ?>"
                 data-label="<?= $t === 7 ? 'Čenkov' : 'Terč '.$t ?>">

            <?php if ($zakazanyTerc): ?>
                <div class="slot-disabled">Zavřeno</div>

            <?php elseif (!empty($rez[$h][$t])): ?>
                <div class="slot-busy">
                    <form method="post" onsubmit="return confirm('Zrušit rezervaci?')">
                        <?= htmlspecialchars($rez[$h][$t]) ?>
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="terc" value="<?= $t ?>">
                        <input type="hidden" name="hodina" value="<?= $h ?>">
                        <button>✖</button>
                    </form>
                </div>

            <?php else: ?>
                <div class="slot-free">
                    <form method="post">
                        <input type="text" name="jmeno" placeholder="jméno" required>
                        <input type="hidden" name="action" value="create">
                        <input type="hidden" name="terc" value="<?= $t ?>">
                        <input type="hidden" name="hodina" value="<?= $h ?>">
                        <button>+</button>
                    </form>
                </div>
            <?php endif; ?>

            </div>
        </td>
    <?php endfor; ?>
</tr>
<?php endfor; ?>
</table>
            </div>
</div>
</main>

<style>
/* === základ === */
.rez-content,.rez-content > .container{min-width:0}
.rez-table-wrapper{width:100%;max-width:100%;min-width:0;overflow-x:auto;-webkit-overflow-scrolling:touch}
.rez-day-nav{display:flex;gap:12px;margin:15px 0}
.rez-day-nav a{padding:4px 10px;background:#e5e9f2;border-radius:6px}
.nav-disabled{opacity:.3;padding:4px 10px}
.rez-table{width:100%;border-collapse:collapse}
.rez-table th,.rez-table td{border:1px solid #d9dee8;padding:6px}
.rez-time{background:#f5f6fa;font-weight:600}

.slot-free{background:#f8fff8}
.slot-busy{background:#eaf1ff;font-weight:600}
.slot-disabled{background:#f0f0f0;color:#999;font-style:italic}

.slot-free form{display:flex;gap:5px;justify-content:center}
.slot-free input{width:70px}
.slot-free button{background:#2ecc71;color:#fff;border:none;border-radius:4px}

.slot-busy button{background:#e74c3c;color:#fff;border:none;border-radius:4px}
/* === otevírací doba === */
.rez-opening{
  display:grid;
  grid-template-columns:1fr 1fr;
  gap:16px;
  margin:15px 0 20px;
}

.opening-box{
  background:#f5f6fa;
  border:1px solid #d9dee8;
  border-radius:8px;
  padding:12px 14px;
}

.opening-box h4{
  margin:0 0 6px;
  font-size:15px;
  font-weight:700;
}

.opening-box ul{
  margin:0;
  padding-left:18px;
  font-size:14px;
}

.opening-box li{
  margin:2px 0;
}

.opening-box .hint{
  font-size:13px;
  color:#555;
}

.opening-cenkov{
  background:#fffaf0;
  border-color:#f1d18a;
}

/* mobil */
@media (max-width:768px){
  .rez-opening{
    grid-template-columns:1fr;
  }
}

/* =========================
   VARIANTA B – MOBIL
   horizontální scroll
   ========================= */
@media (max-width:768px){

  .rez-table{
    min-width:900px; /* aby bylo co scrollovat */
  }

  /* sticky sloupec Čas */
  .rez-table th:first-child,
  .rez-table td:first-child{
    position:sticky;
    left:0;
    background:#f5f6fa;
    z-index:2;
    min-width:70px;
  }

  /* hlavička */
  .rez-table th{
    white-space:nowrap;
  }

  .rez-table td{
    white-space:nowrap;
  }

  /* lehké oddělení sticky sloupce */
  .rez-table td:first-child{
    box-shadow:2px 0 4px rgba(0,0,0,.08);
  }
}

/* Kontrast rezervací v tmavém režimu, včetně mobilního sloupce Čas. */
html[data-theme=dark] .opening-box,
html[data-theme=dark] .rez-table th,
html[data-theme=dark] .rez-table .rez-time,
html[data-theme=dark] .rez-table th:first-child,
html[data-theme=dark] .rez-table td:first-child{
  background:#1b292f;
  color:var(--nk-text);
  border-color:var(--nk-border);
}
html[data-theme=dark] .opening-cenkov{
  background:#332c1d;
  border-color:#806a38;
}
html[data-theme=dark] .opening-box .hint{color:var(--nk-muted)}
html[data-theme=dark] .rez-table td{border-color:var(--nk-border)}
html[data-theme=dark] .rez-day-nav a{background:#203b41;color:#8ed8df}
html[data-theme=dark] .slot-free{background:#17392f;color:var(--nk-text)}
html[data-theme=dark] .slot-busy{background:#203b41;color:var(--nk-text)}
html[data-theme=dark] .slot-disabled{background:#1b292f;color:var(--nk-muted)}
html[data-theme=dark] .slot-free input::placeholder{color:var(--nk-muted);opacity:1}
html[data-theme=dark] .slot-free button{background:#267342;color:#fff}
html[data-theme=dark] .slot-busy button{background:#b33126;color:#fff}
</style>

<?php require __DIR__.'/footer.php'; ?>
