<?php
$title = 'Prezidentský pohár – Jaro 2026';
$hideRocnikDropdown = false;

require __DIR__ . '/header.php';

// aktivní ročník
$rocnik_id = (int)($_SESSION['rocnik_id'] ?? 0);

// ochrana – kdyby se někdo dostal na stránku z jiného ročníku
// (volitelné, klidně můžeš smazat)
if ($rocnik_id !== 4) { // ID ročníku Jaro 2026
    echo '<main id="content" class="nk-content nk-content--flat">';
    echo '<h2>Prezidentský pohár</h2>';
    echo '<p>Tato stránka je určena pouze pro ročník <strong>Jaro 2026</strong>.</p>';
    echo '</main>';
    require __DIR__ . '/footer.php';
    exit;
}
?>



  <h2>🏆 Prezidentský pohár – Jaro 2026</h2>

  <section class="panel panel-info" style="text-align:center">
    <p>
      Pro ročník <strong>Jaro 2026</strong> zatím není los hotový.
      Bude se hrát Cricket na tři vítězné legy.
    </p>
  </section>

  <section class="panel panel-info" style="text-align:center">
    <img
      src="<?= htmlspecialchars(($BASE_URL ?? '/liga-app') . '/img/prezidentskypohar.png', ENT_QUOTES, 'UTF-8') ?>"
      alt="Prezidentský pohár Jaro 2026 – náhled"
      style="
        max-width: 100%;
        height: auto;
        border-radius: 12px;
        box-shadow: 0 10px 25px rgba(0,0,0,.15);
      "
    >
  </section>

</main>

<?php require __DIR__ . '/footer.php'; ?>
