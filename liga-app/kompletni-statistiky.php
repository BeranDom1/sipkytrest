<?php
require __DIR__.'/header.php';
require __DIR__.'/common.php';

$rocnik_id = _active_rocnik_id($conn);
$nadpis = 'Kompletní statistiky – '._rocnik_name($conn, $rocnik_id);

// Pokud je ročník Podzim 2024 (id=1), statistiky nezobrazujeme
if ((int)$rocnik_id === 1) {
  ?>
  <main id="content" class="nk-content nk-content--flat">
    <h2><?= htmlspecialchars($nadpis, ENT_QUOTES, 'UTF-8') ?></h2>
    <p>Z této sezóny nemáme žádné statistiky.</p>
  </main>
  <?php
  require __DIR__.'/footer.php';
  exit;
}


// Všichni hráči napříč ligami v daném ročníku + metriky
$sql = "
SELECT
  u.libovolne_id                   AS id,
  u.jmeno                          AS jmeno,

  hs.liga_id                       AS liga_id,
  l.nazev                          AS liga_nazev,
  l.cislo                          AS liga_cislo,

  -- Počítej jen odehrané: ne-NULL a ne 0:0
  SUM(CASE
        WHEN z.skore1 IS NOT NULL AND z.skore2 IS NOT NULL AND NOT (z.skore1 = 0 AND z.skore2 = 0)
        THEN 1 ELSE 0
      END) AS zapasy,

  ROUND(AVG(CASE
      WHEN z.skore1 IS NOT NULL AND z.skore2 IS NOT NULL AND NOT (z.skore1 = 0 AND z.skore2 = 0) AND z.hrac1_id = u.libovolne_id THEN z.average_home
      WHEN z.skore1 IS NOT NULL AND z.skore2 IS NOT NULL AND NOT (z.skore1 = 0 AND z.skore2 = 0) AND z.hrac2_id = u.libovolne_id THEN z.average_away
      ELSE NULL
  END), 2)                        AS prumer,

  MAX(CASE
      WHEN z.hrac1_id = u.libovolne_id THEN z.high_finish_home
      WHEN z.hrac2_id = u.libovolne_id THEN z.high_finish_away
      ELSE NULL
  END)                             AS nejvyssi_zavreni,

  (
  -- 100+ = 1 bod
  SUM(
    CASE
      WHEN z.hrac1_id = u.libovolne_id THEN COALESCE(z.count_100p_home,0)
      WHEN z.hrac2_id = u.libovolne_id THEN COALESCE(z.count_100p_away,0)
      ELSE 0
    END
  ) * 1

  -- 120+ = 2 body
  +
  SUM(
    CASE
      WHEN z.hrac1_id = u.libovolne_id THEN COALESCE(z.count_120p_home,0)
      WHEN z.hrac2_id = u.libovolne_id THEN COALESCE(z.count_120p_away,0)
      ELSE 0
    END
  ) * 2

  -- 140+ = 3 body
  +
  SUM(
    CASE
      WHEN z.hrac1_id = u.libovolne_id THEN COALESCE(z.count_140p_home,0)
      WHEN z.hrac2_id = u.libovolne_id THEN COALESCE(z.count_140p_away,0)
      ELSE 0
    END
  ) * 3

  -- 160+ = 4 body
  +
  SUM(
    CASE
      WHEN z.hrac1_id = u.libovolne_id THEN COALESCE(z.count_160p_home,0)
      WHEN z.hrac2_id = u.libovolne_id THEN COALESCE(z.count_160p_away,0)
      ELSE 0
    END
  ) * 4

  -- 180 = 5 bodů
  +
  SUM(
    CASE
      WHEN z.hrac1_id = u.libovolne_id THEN COALESCE(z.count_180_home,0)
      WHEN z.hrac2_id = u.libovolne_id THEN COALESCE(z.count_180_away,0)
      ELSE 0
    END
  ) * 5
) AS hodobody


FROM hraci_unikatni_jmena u
JOIN hraci_v_sezone hs
  ON hs.hrac_id = u.libovolne_id
 AND hs.rocnik_id = ?
JOIN ligy l
  ON l.id = hs.liga_id
LEFT JOIN zapasy z
  ON z.rocnik_id = ?
 AND (z.hrac1_id = u.libovolne_id OR z.hrac2_id = u.libovolne_id)

GROUP BY u.libovolne_id, u.jmeno, hs.liga_id, l.nazev, l.cislo
ORDER BY prumer DESC, u.jmeno ASC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param('ii', $rocnik_id, $rocnik_id);
$stmt->execute();
$res = $stmt->get_result();
$rows = [];
while ($r = $res->fetch_assoc()) $rows[] = $r;
$stmt->close();

// Pomocný převod čísla ligy (z l.cislo) na text "X. liga"
function liga_label_from_cislo(int $cislo){ return $cislo.'. '; }
?>
<main id="content" class="nk-content nk-content--flat">
  <h2><?= htmlspecialchars($nadpis) ?></h2>

  <div class="table-wrap">
    <table class="table table--stats table--sortable" data-default-sort="prumer" data-default-order="desc">
      <thead>
        <tr>
          <th data-key="_poradi" data-type="num">#</th>
          <th data-key="jmeno"   data-type="text">Jméno</th>
          <th data-key="liga"    data-type="text">Liga</th>
          <th data-key="zapasy"  data-type="num">Zápasy</th>
          <th data-key="prumer"  data-type="num">Průměr</th>
          <th data-key="hi"      data-type="num">Nejvyšší zavření</th>
          <th data-key="body"    data-type="num">Hodobody</th>
        </tr>
      </thead>
      <tbody>
        <?php $i=1; foreach ($rows as $r): ?>
          <tr>
            <td><?= $i++ ?>.</td>
            <td><?= htmlspecialchars($r['jmeno']) ?></td>
            <td><?= htmlspecialchars(liga_label_from_cislo((int)$r['liga_cislo'])) ?></td>
            <td style="text-align:center"><?= (int)($r['zapasy'] ?? 0) ?></td>
            <td style="text-align:center"><?= is_null($r['prumer']) ? '—' : number_format((float)$r['prumer'], 2, ',', ' ') ?></td>
            <td style="text-align:center"><?= is_null($r['nejvyssi_zavreni']) ? '—' : (int)$r['nejvyssi_zavreni'] ?></td>
            <td style="text-align:center"><?= is_null($r['hodobody']) ? '—' : (int)$r['hodobody'] ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <!-- Lehký JS sorter (bez knihoven) -->
  <script>
  (function(){
    function parseNum(txt){
      if (!txt) return null;
      const s = txt.replace(/\s+/g,'').replace(',', '.');
      const n = parseFloat(s);
      return isNaN(n) ? null : n;
    }
    function setup(table){
      const thead = table.tHead, tbody = table.tBodies[0];
      if (!thead || !tbody) return;
      const ths = Array.from(thead.rows[0].cells);

      ths.forEach((th, idx) => {
        th.setAttribute('data-sortable','');
        th.setAttribute('aria-sort','none');
        th.addEventListener('click', () => sortBy(idx, th));
      });

      function sortBy(idx, th){
        const type = th.dataset.type || 'text';
        const current = th.getAttribute('aria-sort');
        const dir = current === 'ascending' ? 'descending' : 'ascending';
        ths.forEach(x => x.setAttribute('aria-sort','none'));
        th.setAttribute('aria-sort', dir);

        const rows = Array.from(tbody.rows);
        rows.sort((a,b)=>{
          const A = a.cells[idx].textContent.trim();
          const B = b.cells[idx].textContent.trim();
          if (type === 'num'){
            const na = parseNum(A), nb = parseNum(B);
            if (na===null && nb===null) return 0;
            if (na===null) return 1;
            if (nb===null) return -1;
            return dir==='ascending' ? (na-nb) : (nb-na);
          } else {
            return dir==='ascending'
              ? A.localeCompare(B,'cs',{numeric:true})
              : B.localeCompare(A,'cs',{numeric:true});
          }
        });
        rows.forEach((tr,i)=>{
          tbody.appendChild(tr);
          tr.cells[0].textContent = (i+1)+'.';
        });
      }

      const defKey = (table.dataset.defaultSort || '').toLowerCase();
      const defOrder = (table.dataset.defaultOrder || 'desc').toLowerCase();
      if (defKey){
        const th = ths.find(x => (x.dataset.key||'').toLowerCase() === defKey);
        if (th){
          th.setAttribute('aria-sort', defOrder === 'asc' ? 'descending' : 'ascending');
          th.click();
        }
      }
    }

    document.addEventListener('DOMContentLoaded', function(){
      document.querySelectorAll('.table--sortable').forEach(setup);
    });
  })();
  </script>
</main>
<?php require __DIR__.'/footer.php'; ?>
