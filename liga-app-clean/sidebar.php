<?php
// sidebar.php
$currentPage = basename($_SERVER['SCRIPT_NAME']);
$LIG_COUNT = 5;

// mít k dispozici $conn (většinou ho natahuješ v headeru)
if (!isset($conn)) { require_once __DIR__.'/db.php'; }

// 1) Načti názvy lig (klíč = číslo ligy 1..5)
$leagueNames = [];
$sql = "SELECT COALESCE(cislo, id) AS cislo, nazev FROM ligy ORDER BY cislo";
if ($res = $conn->query($sql)) {
  while ($r = $res->fetch_assoc()) $leagueNames[(int)$r['cislo']] = $r['nazev'];
}

// 2) Mapování číslo ligy -> mini logo (ve složce /liga-app/sponzor)
$logoMap = [
  1 => ['file' => 'fpnet.png',     'alt' => 'FPNet.cz'],
  2 => ['file' => 'podzimek.png',  'alt' => 'Podzimek a synové s.r.o.'],
  3 => ['file' => 'automoto.png',  'alt' => 'AUTO – MOTO – KUBA'],
  4 => ['file' => 'sypstav.png',   'alt' => 'Stavebniny SYPSTAV'],
  5 => ['file' => 'u-kapra.png',   'alt' => 'Restaurace U Kapra'],
];

$base = $BASE_URL ?? '/liga-app';
?>

<nav id="sidebar" class="bg-dark text-white min-vh-100 p-3">
  <ul class="nav flex-column">
    <?php for ($i = 1; $i <= $LIG_COUNT; $i++):
      $tableFile  = "{$i}.liga.php";
      $rozpisFile = "{$i}rozpis.php";
      $statFile   = "{$i}.stat.php";
      $ligaName   = $leagueNames[$i] ?? ($i . '. liga'); // fallback
      $logo       = $logoMap[$i] ?? null;
      $logoUrl    = $logo ? $base . '/sponzor/' . $logo['file'] : null;
    ?>
      <!-- Nadpis ligy s mini logem -->
      <li class="nav-item mb-1">
        <div class="sidebar-league d-flex align-items-center ps-1">
          <?php if ($logoUrl): ?>
            <img class="league-logo" src="<?= htmlspecialchars($logoUrl, ENT_QUOTES, 'UTF-8') ?>"
                 alt="<?= htmlspecialchars($logo['alt'], ENT_QUOTES, 'UTF-8') ?>">
          <?php endif; ?>
          <strong class="league-name"><?= htmlspecialchars($ligaName, ENT_QUOTES, 'UTF-8') ?></strong>
        </div>
      </li>

      <li class="nav-item">
        <a class="nav-link d-flex align-items-center ps-4 <?= $currentPage===$tableFile?'active':'' ?>"
           href="/liga-app/ligy/<?= $tableFile ?>">Tabulka</a>
      </li>
      <li class="nav-item">
        <a class="nav-link d-flex align-items-center ps-4 <?= $currentPage===$rozpisFile?'active':'' ?>"
           href="/liga-app/rozpisy/<?= $rozpisFile ?>">Rozpis</a>
      </li>
      <li class="nav-item mb-3">
        <a class="nav-link d-flex align-items-center ps-4 <?= $currentPage===$statFile?'active':'' ?>"
           href="/liga-app/statistiky/<?= $statFile ?>">Statistiky</a>
      </li>
   <?php endfor; ?>

<!-- dělící čára jako LI kvůli jednotnému stylování -->
<li class="nk-divider"></li>

<?php if (!empty($MOBILE_MENU)): ?>
  <!-- extra čára jen pro MOBIL (nad „Kompletní statistiky“) -->
  <li class="nk-divider"></li>
<?php endif; ?>

  

    <!-- zobrazit jen na >= md (na mobilu už je v hamburgeru) -->
    <li class="nav-item mt-4 d-none d-md-block">
      <a class="nav-link d-flex align-items-center ps-4 <?= $currentPage=='kompletni-statistiky.php'?'active':'' ?>"
         href="/liga-app/kompletni-statistiky.php">
        <i class="bi bi-table me-2"></i> Kompletní statistiky
      </a>
    </li>
    <li class="nav-item d-none d-md-block">
      <a class="nav-link d-flex align-items-center ps-4" href="/">
        <i class="bi bi-arrow-left-circle me-2"></i> Zpět na sipkytrest.cz
      </a>
    </li>
  </ul>
</nav>
