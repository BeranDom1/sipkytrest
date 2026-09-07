<?php
// /liga-app/zapas.php
require __DIR__ . '/header.php';   // načte session + $conn + $BASE_URL
require_once __DIR__ . '/common.php';

// ===== 1) Bezpečně načti zápas jen podle ID =====
$matchId = (int)($_GET['id'] ?? 0);
if ($matchId <= 0) {
    echo '<div class="alert alert-danger">Neplatné ID zápasu.</div>';
    require __DIR__.'/footer.php'; exit;
}

$sql = "
SELECT
  z.id, z.datum, z.liga_id, z.rocnik_id,
  z.hrac1_id, z.hrac2_id,
  z.skore1, z.skore2,
  z.average_home, z.average_away,
  z.high_finish_home, z.high_finish_away,
  z.count_100p_home, z.count_100p_away,
  z.count_120p_home, z.count_120p_away,
  z.count_140p_home, z.count_140p_away,
  z.count_160p_home, z.count_160p_away,
  z.count_180_home,  z.count_180_away,
  u1.jmeno AS hrac1,
  u2.jmeno AS hrac2,
  l.cislo AS liga_cislo               -- ⬅ číslo ligy pro zobrazení (0 u Nulté)
FROM zapasy z
LEFT JOIN hraci_unikatni_jmena u1 ON u1.libovolne_id = z.hrac1_id
LEFT JOIN hraci_unikatni_jmena u2 ON u2.libovolne_id = z.hrac2_id
LEFT JOIN ligy l ON l.id = z.liga_id  -- ⬅ přidán JOIN na ligy
WHERE z.id = ?
LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $matchId);
$stmt->execute();
$z = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$z) {
    echo '<div class="alert alert-danger">Neplatné ID zápasu.</div>';
    require __DIR__.'/footer.php'; exit;
}

// ===== 2) Liga/Ročník bereme z DB záznamu =====
$liga_id     = (int)$z['liga_id'];
$rocnik_id   = (int)$z['rocnik_id'];
$liga_cislo  = isset($z['liga_cislo']) ? (int)$z['liga_cislo'] : $liga_id; // fallback

// ===== 3) Práva a edit mód =====
$isEditor = _season_can_edit_matches($conn, $rocnik_id, $_SESSION['role'] ?? '');
$editMode = $isEditor && (($_GET['edit'] ?? '0') === '1');

// ===== 4) Bezpečné výstupy / helpery =====
$h1    = htmlspecialchars((string)$z['hrac1']);
$h2    = htmlspecialchars((string)$z['hrac2']);
$datum = htmlspecialchars((string)($z['datum'] ?? ''));
$rocnik= $rocnik_id;
function nf($v){ return number_format((float)$v, 2, ',', ''); }
function iv($v){ return (int)$v; }
?>
<section class="match-detail">
<style>
  .match-meta{color:#6b7280;margin:.25rem 0 1rem}
  .match-actions{margin:.5rem 0 1rem;display:flex;gap:.5rem}
  .btn{display:inline-block;padding:.5rem .75rem;border:1px solid #e5e7eb;border-radius:10px;background:#fff;text-decoration:none;color:#0d47a1}
  .btn:hover{background:#eef4ff}

  /* Respektuj <colgroup> a rozděl zbytek 50/50 pro score sloupce */
  .match-table{
    width:100%;
    max-width:980px;
    margin-top:.25rem;
    table-layout:fixed !important;
  }
  .match-table col.label{width:40% !important}
  .match-table col.sep{width:1.5rem !important}
  .match-table col.score{width:calc((60% - 1.5rem)/2) !important}

  /* Fallback pro jistotu i přímo na buňkách */
  .match-table tr > td:nth-child(1){width:40% !important}
  .match-table tr > td:nth-child(3){width:1.5rem !important}
  .match-table tr > td:nth-child(2),
  .match-table tr > td:nth-child(4){width:calc((60% - 1.5rem)/2) !important}

  .match-table td{padding:.75rem 1rem;border-bottom:1px solid #e9edf3}
  .match-table tr:last-child td{border-bottom:0}
  .match-table td:nth-child(2),
  .match-table td:nth-child(4){text-align:center;font-weight:600;font-variant-numeric:tabular-nums}
  .match-table td:nth-child(3){text-align:center;color:#9ca3af;font-weight:700}

  .match-table input[type="number"],
  .match-table input[type="date"]{
    width:100%;
    min-width:0;
    max-width:100%;
    box-sizing:border-box;
    padding:.45rem .55rem;border:1px solid #d1d5db;border-radius:8px;
  }

  /* Mobilní úpravy */

  /* ↓↓↓ nahraď touto verzí – užší sloupce + menší mezery na mobilu */

/* MOBIL ≤640 px — ztenčit VŠECHNY sloupce */
/* MOBIL ≤640 px — úplně zruš sep sloupec a zúži vše */
/* MOBIL ≤640 px – score sloupce co nejužší */
/* MOBIL ≤640 px – vyvážené šířky, bez dvojtečky jako sloupce */
/* MOBIL ≤640 px – tabulka už ne 100 %, ale auto; čísla na ch; bez ":" sloupce */
/* MOBIL ≤640 px – tabulka už ne 100 %, ale auto; čísla na ch; bez ":" sloupce */
/* MOBIL ≤640 px – vyvážené šířky, bez dvojtečky jako sloupce */
/* MOBIL ≤640 px – co nejužší sloupce, žádná ":" kolona, malé mezery */
/* MOBIL ≤640 px — kompletní zúžení tabulky, bez scrollu */
/* MOBIL ≤640 px — žádné fixní procenta, šířka podle obsahu, bez ":" sloupce */
/* ===== MOBIL: přesné šířky sloupců + skrytý ":" sloupec ===== */
/* ===== MOBIL: fixní šířky + celá tabulka menší a vycentrovaná ===== */
/* MOBIL ≤640 px — čísla co nejužší, žádné volné místo */
@media (max-width:640px){
  .match-table{
    width:100%;
    table-layout:auto;        /* nepoužívej fixed, ať se sloupce smršťují podle obsahu */
    border-collapse:collapse;
    border-spacing:0;
    font-size:.80rem;
  }

  /* menší mezery v buňkách */
  .match-table td{ padding:.18rem .16rem; }

  /* úplně odstranit ":" sloupec, ať nezabírá místo */
  .match-table colgroup{ display:contents; }
  .match-table col.sep{ display:none !important; width:0 !important; }
  .match-table tr > td:nth-child(3){ display:none !important; width:0 !important; padding:0 !important; border:0 !important; }

  /* 1. sloupec – text: dovol rozbalit na zbytek šířky */
  .match-table tr > td:nth-child(1){
    width:auto !important;
    min-width:0 !important;
    white-space:normal; 
    word-break:break-word;
  }

  /* 2. a 4. sloupec – čísla: co NEJÚŽEJŠÍ
     width:0 + nowrap = shrink-to-fit přesně na obsah */
  .match-table tr > td:nth-child(2),
  .match-table tr > td:nth-child(4){
    width:0 !important;             /* klíčové – žádná procenta */
    min-width:0 !important;          /* zahoď dřívější min-width:4–5ch */
    white-space:nowrap;              /* čísla v jednom řádku */
    text-align:center;
    font-variant-numeric:tabular-nums;
    font-weight:600;
    padding-left:.16rem;
    padding-right:.16rem;
  }

  /* tenká optická předělka mezi hodnotami */
  .match-table tr > td:nth-child(4){ border-left:1px solid #e5e7eb; }

  /* menší nadpis/meta, ať nic netlačí do šířky */
  h1{ font-size:1.06rem; line-height:1.2; margin-bottom:.3rem; }
  .match-meta{ font-size:.84rem; margin-bottom:.4rem; }
}

/* extra malé telefony */
@media (max-width:380px){
  .match-table{ font-size:.74rem; }
  .match-table td{ padding:.16rem .12rem; }
}
@media (max-width:640px){
  /* první sloupec co nejužší, ostatní beze změny */
  .match-table{
    width:100%;
    table-layout:auto;
    border-collapse:collapse;
    border-spacing:0;
  }

  /* první sloupec (popisky) — pevná šířka 20 % */
  .match-table tr > td:nth-child(1){
    width:20% !important;         /* zúží textový sloupec */
    white-space:normal;
    word-break:break-word;
    font-size:.78rem;             /* můžeš snížit písmo jen pro popisky */
    
    
  }

  /* číselné sloupce nechej tak, jak jsou */
  .match-table tr > td:nth-child(2),
  .match-table tr > td:nth-child(4){
    width:auto !important;
    white-space:nowrap;
    text-align:center;
    font-variant-numeric:tabular-nums;
    font-weight:600;
    min-width:0 !important;
    padding-left:.16rem;
    padding-right:.16rem;
  }

  /* ":" sloupec zůstává skrytý */
  .match-table tr > td:nth-child(3){
    display:none !important;
  }

  /* vizuální oddělení pravého sloupce */
  .match-table tr > td:nth-child(4){
    border-left:1px solid #e5e7eb;
  }
}



</style>

  <h1 class="match-heading"><span><?= $h1 ?></span><small>vs.</small><span><?= $h2 ?></span></h1>
  <div class="match-meta">
    Datum: <?= $datum ?>
  | <?= htmlspecialchars(_liga_name($conn, $liga_id, $rocnik_id)) ?>
  | Ročník: <?= $rocnik ?>
</div>

  <?php if ($isEditor): ?>
    <div class="match-actions">
      <?php if ($editMode): ?>
        <a class="btn" href="<?= htmlspecialchars($BASE_URL) ?>/zapas.php?id=<?= $matchId ?>">Hotovo</a>
      <?php else: ?>
        <a class="btn" href="<?= htmlspecialchars($BASE_URL) ?>/zapas.php?id=<?= $matchId ?>&edit=1">Upravit</a>
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <?php if ($editMode): ?>
  <form method="post" action="<?= htmlspecialchars($BASE_URL) ?>/save_stats.php">
    <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf'] ?? '') ?>">
    <input type="hidden" name="match_id" value="<?= $matchId ?>">
  <?php endif; ?>
  <div class="table-wrap match-table-wrap">
    <table class="table match-table<?= $editMode ? ' is-edit' : '' ?>">
      <colgroup>
        <col class="label"><col class="score"><col class="sep"><col class="score">
      </colgroup>
      <thead><tr><th>Statistika</th><th><?= $h1 ?></th><th></th><th><?= $h2 ?></th></tr></thead>
      <tbody>
        <?php if ($editMode): ?>
          <tr class="row-date">
  <td>Datum</td>
  <!-- přes VŠECHNY pravé sloupce: 2. + 3. (sep) + 4. -->
  <td colspan="3" class="date-cell">
    <input class="date-input" type="date" name="datum" value="<?= htmlspecialchars($z['datum'] ?? '') ?>">
  </td>
</tr>

            <tr>
              <td>Skóre</td>
              <td><input type="number" name="skore1" min="0" max="7" value="<?= iv($z['skore1']) ?>"></td>
              <td>:</td>
              <td><input type="number" name="skore2" min="0" max="7" value="<?= iv($z['skore2']) ?>"></td>
            </tr>

            <tr>
              <td>Průměr</td>
              <td><input type="number" step="0.01" min="0" max="180" name="average_home" value="<?= (float)$z['average_home'] ?>"></td>
              <td></td>
              <td><input type="number" step="0.01" min="0" max="180" name="average_away" value="<?= (float)$z['average_away'] ?>"></td>
            </tr>

            <tr>
              <td>Nejvyšší zavření</td>
              <td><input type="number" min="0" max="170" name="high_finish_home" value="<?= iv($z['high_finish_home']) ?>"></td>
              <td></td>
              <td><input type="number" min="0" max="170" name="high_finish_away" value="<?= iv($z['high_finish_away']) ?>"></td>
            </tr>

            <tr>
              <td>100+</td>
              <td><input type="number" min="0" max="99" name="count_100p_home" value="<?= iv($z['count_100p_home']) ?>"></td>
              <td></td>
              <td><input type="number" min="0" max="99" name="count_100p_away" value="<?= iv($z['count_100p_away']) ?>"></td>
            </tr>
              <tr>
              <td>120+</td>
              <td><input type="number" min="0" max="99" name="count_120p_home" value="<?= iv($z['count_120p_home']) ?>"></td>
              <td></td>
              <td><input type="number" min="0" max="99" name="count_120p_away" value="<?= iv($z['count_120p_away']) ?>"></td>
            </tr>
            <tr>
              <td>140+</td>
              <td><input type="number" min="0" max="99" name="count_140p_home" value="<?= iv($z['count_140p_home']) ?>"></td>
              <td></td>
              <td><input type="number" min="0" max="99" name="count_140p_away" value="<?= iv($z['count_140p_away']) ?>"></td>
            </tr>
            <tr>
              <td>160+</td>
              <td><input type="number" min="0" max="99" name="count_160p_home" value="<?= iv($z['count_160p_home']) ?>"></td>
              <td></td>
              <td><input type="number" min="0" max="99" name="count_160p_away" value="<?= iv($z['count_160p_away']) ?>"></td>
            </tr>
            <tr>
              <td>180+</td>
              <td><input type="number" min="0" max="99" name="count_180_home" value="<?= iv($z['count_180_home']) ?>"></td>
              <td></td>
              <td><input type="number" min="0" max="99" name="count_180_away" value="<?= iv($z['count_180_away']) ?>"></td>
            </tr>

            <tr>
              <td></td>
              <td colspan="2" style="text-align:right">
                <a class="btn" href="<?= htmlspecialchars($BASE_URL) ?>/zapas.php?id=<?= $matchId ?>">Zrušit</a>
                <button type="submit" class="btn">Uložit</button>
              </td>
            </tr>
        <?php else: ?>
          <tr>
            <td>Skóre</td>
            <td><?= iv($z['skore1']) ?></td>
            <td>:</td>
            <td><?= iv($z['skore2']) ?></td>
          </tr>
          <tr>
            <td>Průměr</td>
            <td><?= nf($z['average_home']) ?></td>
            <td></td>
            <td><?= nf($z['average_away']) ?></td>
          </tr>
          <tr>
            <td>Nejvyšší zavření</td>
            <td><?= iv($z['high_finish_home']) ?></td>
            <td></td>
            <td><?= iv($z['high_finish_away']) ?></td>
          </tr>
          <tr>
            <td>100+</td>
            <td><?= iv($z['count_100p_home']) ?></td>
            <td></td>
            <td><?= iv($z['count_100p_away']) ?></td>
          </tr>
           <tr>
            <td>120+</td>
            <td><?= iv($z['count_120p_home']) ?></td>
            <td></td>
            <td><?= iv($z['count_120p_away']) ?></td>
          </tr>
          <tr>
            <td>140+</td>
            <td><?= iv($z['count_140p_home']) ?></td>
            <td></td>
            <td><?= iv($z['count_140p_away']) ?></td>
          </tr>
          <tr>
            <td>160+</td>
            <td><?= iv($z['count_160p_home']) ?></td>
            <td></td>
            <td><?= iv($z['count_160p_away']) ?></td>
          </tr>
          <tr>
            <td>180+</td>
            <td><?= iv($z['count_180_home']) ?></td>
            <td></td>
            <td><?= iv($z['count_180_away']) ?></td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  <?php if ($editMode): ?></form><?php endif; ?>
</section>
<?php require __DIR__ . '/footer.php'; ?>
<style>
/* ===== MOBILE ≤640px – zrušit colgroup, 1. sloupec na znaky, čísla co nejmenší ===== */
/* MOBILE ≤640px – tabulka podle obsahu, 1. sloupec úzký, čísla co nejmenší */
/* ===== zapas.php – MOBIL ≤640px: přebi theme, zruš 100% a min-width ===== */
@media (max-width:640px){
  /* 0) wrapper nesmí nutit scroll a tabulka nesmí být 100 % ani mít min-width */
  .table-wrap{ overflow-x: visible !important; }
  .table-wrap > table.match-table{
    width: auto !important;
    min-width: 0 !important;                 /* přebije min-width:520px z theme */
    table-layout: auto !important;           /* přebije table-layout:fixed */
    display: inline-table;
  }

  /* 1) zruš globální 40% na 2. sloupci pro TUTO tabulku */
  .table.match-table td:nth-child(2),
  .table.match-table th:nth-child(2){
    width: auto !important;
  }

  /* 2) 3. (":" ) sloupec ponecháme kvůli colspan, ale zkolabovaný na 0 */
  .table.match-table td:nth-child(3){
    width:0 !important; padding:0 !important; border:0 !important; font-size:0 !important;
  }

  /* 3) 1. sloupec – úzký, ale čitelný (na znaky) */
  .table.match-table td:nth-child(1){
    width: 14ch !important;                  /* klidně 10–14ch podle potřeby */
    max-width: 40%;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    font-size: .82rem !important; color:#111 !important;
  }

  /* 4) číselné sloupce – přesně na obsah (žádné rezervy) */
  .table.match-table td:nth-child(2),
  .table.match-table td:nth-child(4){
    width: 0 !important; min-width:0 !important; white-space: nowrap;
    text-align:center; font-variant-numeric: tabular-nums; font-weight:600;
    padding:.14rem .12rem;
  }

  /* 5) inputy v edit režimu – malé, aby nevycpávaly buňku */
  .table.match-table.is-edit input[type="number"],
  .table.match-table.is-edit input[type="date"]{
    width:5.5ch; max-width:6ch; padding:.26rem .28rem; text-align:center; display:inline-block;
  }

  /* optická dělicí linka mezi čísly */
  .table.match-table td:nth-child(4){ border-left:1px solid #e5e7eb; }

  /* drobná typografie pro kompaktnost */
  .table.match-table td{ padding:.18rem .16rem; }
}
/* řádek Datum – vždy plná šířka přes oba sloupce */
.match-table .date-cell{ text-align:left; }
.match-table .date-cell .date-input{ width:100%; max-width:100%; }

/* přebij mobilní zmenšování inputů v edit režimu */
@media (max-width:640px){
  .match-table.is-edit .date-cell .date-input{
    width:100% !important;
    max-width:100% !important;
    padding:.32rem .4rem;   /* ať se příjemně trefuje prstem */
    text-align:left;
  }
}

</style>

<style>
/* Final responsive match-detail layout. */

/* Desktop: keep both player value columns exactly the same width. */
@media (min-width:641px){
  .match-table thead th:first-child{width:40%!important}
  .match-table thead th:nth-child(2),
  .match-table thead th:nth-child(4){width:calc((60% - 1.5rem)/2)!important}
  .match-table.is-edit td:nth-child(2) input[type=number],
  .match-table.is-edit td:nth-child(4) input[type=number]{width:100%!important;max-width:none!important}
}

.match-detail{width:100%;min-width:0}
.match-heading{
  display:grid;grid-template-columns:minmax(0,1fr) auto minmax(0,1fr);
  align-items:center;gap:12px;margin:0 0 10px;text-align:center;
  font-size:clamp(1.35rem,4vw,2rem);line-height:1.2
}
.match-heading span{min-width:0;overflow-wrap:anywhere}
.match-heading small{color:var(--nk-muted);font-size:.55em;font-weight:600}
.match-table-wrap{width:100%;max-width:none}
.match-table thead{display:table-header-group}
.match-table thead th{text-transform:none;letter-spacing:0;text-align:center}
.match-table thead th:first-child{text-align:left}
.match-table thead th:nth-child(3){width:0;padding:0}

@media (max-width:640px){
  .match-detail{padding-top:2px}
  .match-heading{grid-template-columns:1fr;gap:2px;text-align:left;font-size:1.4rem}
  .match-heading small{display:none}
  .match-meta{font-size:.9rem;line-height:1.45;margin:8px 0 12px}
  .match-actions{margin:8px 0 12px}
  .match-table-wrap{overflow:visible!important;border-radius:14px}
  .table-wrap>table.match-table,
  .match-table{display:table!important;width:100%!important;min-width:0!important;table-layout:fixed!important}
  .match-table col.label{width:38%!important}
  .match-table col.score{width:31%!important}
  .match-table col.sep{width:0!important}
  .match-table thead th{padding:9px 6px!important;font-size:.72rem;line-height:1.15;overflow-wrap:anywhere}
  .match-table thead th:nth-child(3),
  .match-table tr>td:nth-child(3){display:none!important}
  .table.match-table tr>td:nth-child(1){width:38%!important;max-width:none!important;white-space:normal!important;overflow:visible!important;text-overflow:clip!important;font-size:.84rem!important;padding:9px 7px!important}
  .table.match-table tr>td:nth-child(2),
  .table.match-table tr>td:nth-child(4){width:31%!important;min-width:0!important;padding:8px 5px!important;border-left:1px solid var(--nk-border)}
  .table.match-table.is-edit input[type="number"]{width:100%!important;max-width:72px!important;min-height:42px;padding:7px 4px!important}
  .match-table.is-edit .date-cell{width:62%!important;padding:7px!important}
  .match-table.is-edit .date-cell .date-input{width:100%!important;max-width:none!important;min-height:42px}
  .match-table .btn{min-height:40px;padding:7px 10px}
}
</style>
