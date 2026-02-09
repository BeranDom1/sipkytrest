<?php
// www/liga-app/prezidentsky-pohar.php
require __DIR__.'/header.php';
require __DIR__.'/common.php';

$rocnik_id = _active_rocnik_id($conn);
$turnaj = $conn->query("SELECT * FROM prezidentsky_turnaj WHERE rocnik_id={$rocnik_id} ORDER BY id DESC LIMIT 1")->fetch_assoc();
if (!$turnaj) { echo "<div class='notice'>Turnaj zatím není založen.</div>"; require __DIR__.'/footer.php'; exit; }
$turnaj_id = (int)$turnaj['id'];

$stages = [
  'P'  => '1. Předkolo',
  'R'  => '2. Předkolo',   // „kvalifikace“/2. předkolo
  'O'  => '1/16',
  'OF' => '1/8',
  'QF' => 'ČF',
  'SF' => 'SF',
  'F'  => 'Finále'
];

$byStage = [];
$q = $conn->prepare("
  SELECT z.*,
         COALESCE(z.hrac1_jmeno, h1.jmeno) AS name1,
         COALESCE(z.hrac2_jmeno, h2.jmeno) AS name2
  FROM prezidentsky_zapas z
  LEFT JOIN hraci h1 ON h1.id = z.hrac1_id
  LEFT JOIN hraci h2 ON h2.id = z.hrac2_id
  WHERE z.turnaj_id = ?
  ORDER BY FIELD(z.stage,'P','R','O','OF','QF','SF','F'), z.slot
");
$q->bind_param('i', $turnaj_id);
$q->execute();
$res = $q->get_result();
while ($row = $res->fetch_assoc()) { $byStage[$row['stage']][] = $row; }
?>
<link rel="stylesheet" href="<?= htmlspecialchars($BASE_URL) ?>/assets/pp.css?v=3">
<?php if (!empty($_SESSION['pp_err'])): ?>
  <div class="notice error"><?= htmlspecialchars($_SESSION['pp_err']) ?></div>
  <?php unset($_SESSION['pp_err']); ?>
<?php elseif (!empty($_SESSION['pp_ok'])): ?>
  <div class="notice ok"><?= htmlspecialchars($_SESSION['pp_ok']) ?></div>
  <?php unset($_SESSION['pp_ok']); ?>
<?php endif; ?>


<div class="pp-wrap">
  <div class="pp-head">
    <h1>KO PAVOUK <?= htmlspecialchars($turnaj['in_out']) ?> — <?= (int)$turnaj['legs_to_win'] ?> vítězných legů</h1>
  </div>
 <!-- PRAVIDLA TURNAJE -->
  <div class="pp-rules">
    <h2>Pravidla prezidentského poháru</h2>
    <ul>
      <li><?= htmlspecialchars($turnaj['in_out']) ?> — <?= (int)$turnaj['legs_to_win'] ?> vítězných legů až do finále</li>
      <li>Startovné v rámci ligy</li>
      <li>Nasazení podle průměrů z loňské sezóny</li>
      <li>Prvních 16 hráčů volný los, 30 hráčů hraje 2. předkolo, 4 hráči 1. předkolo</li>
      <li>Začátek od 1.&nbsp;10. pro každé kolo je vypsán termín na odehrání (cca 15&nbsp;dní)</li>
    </ul>
  </div>
  <!-- /PRAVIDLA -->
  <!-- ZOOM WRAPPER -->
  <div class="pp-container" id="pp">
    <?php foreach ($stages as $code=>$label): if (empty($byStage[$code])) continue; ?>
      <section class="pp-col pp-col--<?= $code ?>">
        <h3><?= $label ?></h3>

        <?php foreach ($byStage[$code] as $m): ?>
          <?php
            // Bezpečný fallback jmen
            $n1 = $m['name1'] ?? '';
            if ($n1 === '') $n1 = $m['hrac1_jmeno'] ?? '';
            if ($n1 === '' && !empty($m['hrac1_id'])) {
              static $c1=[]; $hid=(int)$m['hrac1_id'];
              if ($hid && !isset($c1[$hid])) {
                $st=$conn->prepare("SELECT jmeno FROM hraci WHERE id=?");
                $st->bind_param('i',$hid); $st->execute();
                $c1[$hid] = (string)($st->get_result()->fetch_column() ?: '');
              }
              $n1 = $c1[$hid] ?? '';
            }
            $n2 = $m['name2'] ?? '';
            if ($n2 === '') $n2 = $m['hrac2_jmeno'] ?? '';
            if ($n2 === '' && !empty($m['hrac2_id'])) {
              static $c2=[]; $hid2=(int)$m['hrac2_id'];
              if ($hid2 && !isset($c2[$hid2])) {
                $st2=$conn->prepare("SELECT jmeno FROM hraci WHERE id=?");
                $st2->bind_param('i',$hid2); $st2->execute();
                $c2[$hid2] = (string)($st2->get_result()->fetch_column() ?: '');
              }
              $n2 = $c2[$hid2] ?? '';
            }
          ?>

          <article class="pp-match"
            id="m<?= (int)$m['id'] ?>"
            data-id="<?= (int)$m['id'] ?>"
            data-stage="<?= $code ?>"
            data-code="<?= htmlspecialchars($m['code'] ?? '') ?>"
            data-next="<?= (int)$m['next_match_id'] ?>"
            data-nextcode="<?= htmlspecialchars($m['next_code'] ?? '') ?>"
            data-nextpos="<?= (int)$m['next_pos'] ?>">

            <div class="pp-pair">
              <span class="pp-name <?= ((int)$m['vitez']===1?'pp-win':'') ?>"><?= htmlspecialchars($n1 !== '' ? $n1 : '—') ?></span>
              <span class="pp-score"><?= $m['skore1']!==null ? (int)$m['skore1'] : '' ?></span>
            </div>
            <div class="pp-pair">
              <span class="pp-name <?= ((int)$m['vitez']===2?'pp-win':'') ?>"><?= htmlspecialchars($n2 !== '' ? $n2 : '—') ?></span>
              <span class="pp-score"><?= $m['skore2']!==null ? (int)$m['skore2'] : '' ?></span>
            </div>

            <?php if (in_array($_SESSION['role'] ?? '', ['admin','stat_editor'], true)): ?>
            <form class="pp-form" method="post" action="save_pp.php" data-win="<?= (int)$turnaj['legs_to_win'] ?>">
  <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
  <input type="hidden" name="match_id" value="<?= (int)$m['id'] ?>">

  <label><input name="s1" type="number" min="0" max="<?= (int)$turnaj['legs_to_win'] ?>" step="1" value="<?= $m['skore1'] ?>"></label>
  <span>:</span>
  <label><input name="s2" type="number" min="0" max="<?= (int)$turnaj['legs_to_win'] ?>" step="1" value="<?= $m['skore2'] ?>"></label>

  <button class="btn" type="submit">Uložit</button>
  <!-- NOVÉ: zrušení (reset) – neposílej validaci, jen reset -->
  <button class="btn btn-warn"
          type="submit"
          name="action" value="reset"
          formnovalidate
          onclick="return confirm('Zrušit výsledek tohoto zápasu a vrátit „vítěz <?= htmlspecialchars($m['code'] ?? '') ?>“ do dalšího kola?');">
    Zrušit
  </button>

  <small class="pp-msg" aria-live="polite"></small>
</form>

            <?php endif; ?>
          </article>
        <?php endforeach; ?>
      </section>
    <?php endforeach; ?>

    <!-- SVG vrstva (čáry jsou trvale vypnuté) -->
    <svg class="pp-links" id="ppLinks" style="display:none"></svg>
  </div>
</div>

<script>
(function(){
  const wrap = document.getElementById('pp');
  const svg  = document.getElementById('ppLinks');
  const LINKS_ENABLED = false; // čáry vypnuté

  // souřadnice vůči #pp
  function topRel(el, anc){ let y=0,n=el; while(n && n!==anc){ y+=n.offsetTop; n=n.offsetParent; } return y; }
  function leftRel(el, anc){ let x=0,n=el; while(n && n!==anc){ x+=n.offsetLeft; n=n.offsetParent; } return x; }
  function getByCode(code){ return code ? wrap.querySelector('.pp-match[data-code="'+code+'"]') : null; }

  // P1/P2 srovnání k cílovým zápasům
function layoutPrelims(){
  const colP = wrap.querySelector('.pp-col--P');
  if (!colP) return;

  // čti posun z CSS (lze měnit bez zásahu do JS)
  const cssShift = parseFloat(getComputedStyle(colP).getPropertyValue('--p-shift')) || 0;

  let maxH = 0;
  wrap.querySelectorAll('.pp-col').forEach(c => { maxH = Math.max(maxH, c.scrollHeight); });
  colP.style.height = maxH + 'px';

  const head = colP.querySelector('h3');
  const headH = head ? head.offsetHeight : 0;
  const colTop = topRel(colP, wrap);

  colP.querySelectorAll('.pp-match').forEach(p => {
    const to = (p.dataset.next && p.dataset.next !== '0')
      ? document.getElementById('m'+p.dataset.next)
      : getByCode(p.dataset.nextcode);
    if (!to) return;

    const pos = +p.dataset.nextpos || 1;
    const yTarget = topRel(to, wrap) + (pos===1 ? to.offsetHeight*0.30 : to.offsetHeight*0.70);

    // dříve + PRELIM_Y_OFFSET (kladné tlačilo DOLŮ)
    const topPx = yTarget - (colTop + headH) - p.offsetHeight/2 + cssShift;

    p.style.top = Math.max(0, topPx) + 'px';
  });
}

  // Posune 1/8 → ČF → SF → Finále tak, aby byl každý sloupec vystředěný
function layoutStageOffsets(){
  const cols = Array.from(document.querySelectorAll('#pp .pp-col'));
  const wrap = document.getElementById('pp');

  // baseline = 1/16 (O); fallback = první sloupec, který není P
  let start = cols.findIndex(c => c.classList.contains('pp-col--O'));
  if (start < 0) {
    start = cols.findIndex(c => !c.classList.contains('pp-col--P'));
    if (start < 0) return;
  }

  // reset předchozích posunů
  cols.forEach(col => {
    const first = col.querySelector('.pp-match');
    if (first) first.style.marginTop = '0px';
  });

  // helper: vrátí střed (y) balíku zápasů ve sloupci
  function columnCenter(matches){
    const topFirst = topRel(matches[0], wrap);
    const last = matches[matches.length - 1];
    const bottomLast = topRel(last, wrap) + last.offsetHeight;
    return (topFirst + bottomLast) / 2;
  }

  // centrování postupně: OF podle O, QF podle OF, SF podle QF, F podle SF
  for (let i = start + 1; i < cols.length; i++) {
    const prev = cols[i - 1];
    const curr = cols[i];
    if (curr.classList.contains('pp-col--P')) continue;

    const prevM = prev.querySelectorAll('.pp-match');
    const currM = curr.querySelectorAll('.pp-match');
    if (!prevM.length || !currM.length) continue;

    const prevC = columnCenter(prevM);
    const currC = columnCenter(currM);        // aktuální střed bez posunu (marginTop=0)
    const shift = prevC - currC;              // o kolik je potřeba spustit dolů

    // jemné doladění (pokud chceš): const fineTune = 0;
    currM[0].style.marginTop = `${Math.max(0, shift)}px`;
  }
}

  function drawLinks(){
    if (!LINKS_ENABLED) return;
    svg.style.display = '';
    svg.setAttribute('width', wrap.scrollWidth);
    svg.setAttribute('height', wrap.scrollHeight);
    svg.setAttribute('viewBox', `0 0 ${wrap.scrollWidth} ${wrap.scrollHeight}`);
    svg.innerHTML = '';

    wrap.querySelectorAll('.pp-match').forEach(from => {
      const to = (from.dataset.next && from.dataset.next !== '0')
        ? document.getElementById('m'+from.dataset.next)
        : getByCode(from.dataset.nextcode);
      if (!to) return;

      const pos = +from.dataset.nextpos || 1;

      const x1 = leftRel(from, wrap) + from.offsetWidth;
      const y1 = topRel(from,  wrap) + from.offsetHeight/2;

      const x2 = leftRel(to, wrap);
      const y2 = topRel(to,  wrap) + (pos===1 ? to.offsetHeight*0.30 : to.offsetHeight*0.70);

      const mid = x1 + (x2 - x1) * 0.5;

      const p = document.createElementNS('http://www.w3.org/2000/svg','path');
      p.setAttribute('d', `M ${x1} ${y1} C ${mid} ${y1}, ${mid} ${y2}, ${x2} ${y2}`);
      p.setAttribute('class','pp-path');
      p.setAttribute('fill','none');
      svg.appendChild(p);
    });
  }

  function layoutAll(){
    layoutPrelims();
    layoutStageOffsets(); // musí být PŘED kreslením čar
    drawLinks();
  }

  window.addEventListener('load',  layoutAll);
  window.addEventListener('resize', layoutAll);
  wrap.addEventListener('scroll', drawLinks, {passive:true}); // čáry jsou off, ale nevadí
})();

form.addEventListener('submit', (e) => {
  // když se odesílá tlačítkem „Zrušit“, validaci přeskoč
  if (e.submitter && e.submitter.name === 'action' && e.submitter.value === 'reset') {
    return;
  }
  if (!validate()) e.preventDefault();
});
</script>



<?php require __DIR__.'/footer.php'; ?>
