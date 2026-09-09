<?php
// sidebar.php
$currentPage = basename($_SERVER['SCRIPT_NAME']);

if (!isset($conn)) {
    require_once __DIR__ . '/db.php';
}

$base = $BASE_URL ?? '/liga-app';

// aktivní ročník
$rocnik_id = (int)($_SESSION['rocnik_id'] ?? 0);

// === Prezidentský pohár – dynamický odkaz podle ročníku ======================

$prezidentskyPoharUrl = null;

// staré ročníky → statická stránka
if ($rocnik_id > 0 && $rocnik_id <= 3) {

    $prezidentskyPoharUrl = $base . '/prezidentsky-pohar.php';

// nové ročníky → dynamický turnaj
} elseif ($rocnik_id >= 4) {

    $stmt = $conn->prepare("
        SELECT id
        FROM turnaje
        WHERE rocnik_id = ?
        ORDER BY id DESC
        LIMIT 1
    ");
    $stmt->bind_param('i', $rocnik_id);
    $stmt->execute();
    $stmt->bind_result($turnaj_id);
    $stmt->fetch();
    $stmt->close();

    if ($turnaj_id) {
        $prezidentskyPoharUrl =
            $base . '/pohar/pohar_turnaj.php?id=' . (int)$turnaj_id;
    } else {
        // fallback – kdyby turnaj ještě nebyl založen
        $prezidentskyPoharUrl = '#';
    }
}

// pro zvýraznění active stavu
$ppPages = [
    'prezidentsky-pohar.php',
    'pohar_turnaj.php'
];


/**
 * Načtení lig:
 * - pořadí z tabulky lig (cislo)
 * - název z ligy_nazvy dle ročníku
 * - logo + alt z ligy_loga dle ročníku
 */
$sql = "
SELECT
  l.id        AS liga_id,
  l.cislo     AS cislo,
  COALESCE(ln.nazev, l.nazev) AS nazev,
  ll.logo     AS logo,
  ll.alt      AS alt
FROM ligy l
LEFT JOIN ligy_nazvy ln
  ON ln.liga_id = l.id
 AND ln.rocnik_id = ?
LEFT JOIN ligy_loga ll
  ON ll.liga_id = l.id
 AND ll.rocnik_id = ?
WHERE ln.rocnik_id IS NOT NULL
   OR EXISTS (
       SELECT 1 FROM hraci_v_sezone hs
       WHERE hs.rocnik_id = ? AND hs.liga_id = l.id
   )
ORDER BY l.poradi, l.cislo
";

$st = $conn->prepare($sql);
$st->bind_param('iii', $rocnik_id, $rocnik_id, $rocnik_id);
$st->execute();
$ligy = $st->get_result()->fetch_all(MYSQLI_ASSOC);
$st->close();
?>

<nav id="sidebar" class="bg-dark text-white min-vh-100 p-3">
  <ul class="nav flex-column">

<?php foreach ($ligy as $liga):
    $leagueId = (int)$liga['liga_id'];
    $isCurrentLeague = (int)($_GET['liga_id'] ?? 0) === $leagueId;

    $logoUrl = $liga['logo']
        ? $base . '/sponzor/' . $liga['logo']
        : null;
    $logoNeedsBlend = in_array(strtolower((string)$liga['logo']), [
        'fpnet.png', 'podzimek.jpg', 'podzimek.png', 'sypstav.png'
    ], true);
?>

    <!-- Nadpis ligy -->
    <li class="nav-item mb-1">
      <div class="sidebar-league d-flex align-items-center ps-1">
        <?php if ($logoUrl): ?>
          <span class="league-logo-frame">
            <img class="league-logo<?= $logoNeedsBlend ? ' league-logo--blend' : '' ?>"
                 src="<?= htmlspecialchars($logoUrl, ENT_QUOTES, 'UTF-8') ?>"
                 alt="<?= htmlspecialchars($liga['alt'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
          </span>
        <?php endif; ?>
        <strong class="league-name">
          <?= htmlspecialchars($liga['nazev'], ENT_QUOTES, 'UTF-8') ?>
        </strong>
      </div>
    </li>

    <li class="nav-item">
      <a class="nav-link d-flex align-items-center ps-4 <?= $currentPage === 'liga.php' && $isCurrentLeague ? 'active' : '' ?>"
         href="<?= $base ?>/liga.php?liga_id=<?= $leagueId ?>">
        Tabulka
      </a>
    </li>

    <li class="nav-item">
      <a class="nav-link d-flex align-items-center ps-4 <?= $currentPage === 'rozpis.php' && $isCurrentLeague ? 'active' : '' ?>"
         href="<?= $base ?>/rozpis.php?liga_id=<?= $leagueId ?>">
        Rozpis
      </a>
    </li>

    <li class="nav-item mb-3">
      <a class="nav-link d-flex align-items-center ps-4 <?= $currentPage === 'stat.php' && $isCurrentLeague ? 'active' : '' ?>"
         href="<?= $base ?>/stat.php?liga_id=<?= $leagueId ?>">
        Statistiky
      </a>
    </li>

<?php endforeach; ?>

<?php if (empty($MOBILE_MENU)): ?>
    <li class="nk-divider"></li>

    <li class="nav-item mt-3">
      <a class="nav-link ps-4 <?= $currentPage === 'kompletni-statistiky.php' ? 'active' : '' ?>"
         href="<?= $base ?>/kompletni-statistiky.php">
        Kompletní statistiky
      </a>
    </li>

    <li class="nav-item">
  <a class="nav-link ps-4 <?= in_array($currentPage, $ppPages, true) ? 'active' : '' ?>"
     href="<?= htmlspecialchars($prezidentskyPoharUrl, ENT_QUOTES, 'UTF-8') ?>">
    Prezidentský pohár
  </a>
</li>

    <li class="nav-item">
      <a class="nav-link ps-4"
         href="<?= $base ?>/docs/pravidla.pdf"
         target="_blank" rel="noopener">
        Pravidla (PDF)
      </a>
    </li>

    <li class="nav-item">
      <a class="nav-link ps-4" href="https://sipkytrest.cz">
        Zpět na sipkytrest.cz
      </a>
    </li>
<?php endif; ?>

  </ul>
</nav>
