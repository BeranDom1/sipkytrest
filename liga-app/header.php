<?php
// === Header (Black–Yellow Theme) ===
$BASE_URL = '/liga-app';

if (session_status() !== PHP_SESSION_ACTIVE) {
    $isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => $BASE_URL,
        'secure'   => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

require_once __DIR__.'/db.php';
require_once __DIR__.'/security/csrf.php';

// uživatel (pro login / logout)
$username = $_SESSION['username'] ?? null;

// Výchozí kontext = explicitně aktivní sezóna, ne pouze nejvyšší ID.
if (empty($_SESSION['rocnik_id'])) {
    $q = $conn->query("
        SELECT id
        FROM rocniky
        WHERE stav = 'aktivni'
        ORDER BY id DESC
        LIMIT 1
    ");
    if ($q && $r = $q->fetch_assoc()) {
        $_SESSION['rocnik_id'] = (int)$r['id'];
    } else {
        $fallback = $conn->query('SELECT id FROM rocniky ORDER BY id DESC LIMIT 1');
        if ($fallback && $r = $fallback->fetch_assoc()) {
            $_SESSION['rocnik_id'] = (int)$r['id'];
        }
    }
}

// POST guard pro běžné formuláře
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    require_once __DIR__.'/security/guard-post.php';
}

$title = $title ?? 'Šipky Třešť – liga';

// seasons pro přepínač
$seasons = [];
if ($q = $conn->query("SELECT id, nazev, stav FROM rocniky ORDER BY id DESC")) {
    while ($r = $q->fetch_assoc()) $seasons[] = $r;
}
$selSeason = (int)($_SESSION['rocnik_id'] ?? 0);

// === Prezidentský pohár – dynamický odkaz podle ročníku =====================
// === Prezidentský pohár – STEJNÁ logika jako v sidebaru =====================

$prezidentskyPoharUrl = null;

// staré ročníky → statická stránka
if ($selSeason > 0 && $selSeason <= 3) {

    $prezidentskyPoharUrl = $BASE_URL . '/prezidentsky-pohar.php';

// nové ročníky → dynamický turnaj
} elseif ($selSeason >= 4) {

    $stmt = $conn->prepare("
        SELECT id
        FROM turnaje
        WHERE rocnik_id = ?
        ORDER BY id DESC
        LIMIT 1
    ");
    $stmt->bind_param('i', $selSeason);
    $stmt->execute();
    $stmt->bind_result($turnaj_id);
    $stmt->fetch();
    $stmt->close();

    if ($turnaj_id) {
        $prezidentskyPoharUrl =
            $BASE_URL . '/pohar/pohar_turnaj.php?id=' . (int)$turnaj_id;
    } else {
        // fallback – kdyby turnaj ještě nebyl založen
        $prezidentskyPoharUrl = '#';
    }
}
// Auto-hide přepínače na index/rezervace (lze přepsat proměnnou)
$__path = $_SERVER['SCRIPT_NAME'] ?? '';
if (!isset($hideRocnikDropdown)) {
  $hideRocnikDropdown = (bool)preg_match('#/(index|rezervace)\.php$#', $__path);
}
?>
<!doctype html>
<html lang="cs">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($title) ?></title>

  <script>
    (() => {
      const saved = localStorage.getItem("sipky-theme");
      const dark = saved ? saved === "dark" : window.matchMedia("(prefers-color-scheme: dark)").matches;
      document.documentElement.dataset.theme = dark ? "dark" : "light";
    })();
  </script>

  <link rel="stylesheet" href="<?= htmlspecialchars($BASE_URL) ?>/assets/theme.final.css?v=52">
  <link rel="manifest" href="/liga-app/manifest.webmanifest?v=3">
  <link rel="apple-touch-icon" href="/liga-app/icons/sipky-192.png">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<meta name="apple-mobile-web-app-title" content="Šipky Třešť">
<meta name="mobile-web-app-title" content="Šipky Třešť">
<meta name="theme-color" content="#164b57">
<script>
  if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
      navigator.serviceWorker.register('/liga-app/sw.js')
        .catch(err => console.error('SW reg error', err));
    });
  }
</script>


  <script>
    window.__CSRF_TOKEN__ = "<?= htmlspecialchars($_SESSION['csrf'] ?? '', ENT_QUOTES, 'UTF-8') ?>";
    window.__BASE_URL__   = "<?= htmlspecialchars($BASE_URL, ENT_QUOTES, 'UTF-8') ?>";
  </script>

  <script defer src="<?= htmlspecialchars($BASE_URL) ?>/assets/autoWrapTables.js"></script>
  <script defer src="<?= htmlspecialchars($BASE_URL) ?>/assets/csrf-autoinject.js"></script>
  <script defer src="<?= htmlspecialchars($BASE_URL) ?>/assets/theme.js?v=4"></script>
</head>
<body class="<?= str_contains($__path, '/admin/') ? 'nk-admin-page' : 'nk-public-page' ?>">
     <!-- Tlačítko instalace PWA -->
  <div id="pwaInstall" style="display:none; text-align:center; margin:.5rem 0;">
    <button id="installBtn" style="display:none; padding:.6rem 1rem; border:1px solid #e5e7eb; border-radius:.75rem; background:#fff; cursor:pointer;">
      📲 Nainstalovat ligu jako aplikaci
    </button>
    <small id="iosHint" style="display:none; color:#6b7280;">
      Na iOS otevři <b>Sdílet</b> → <b>Přidat na plochu</b>.
    </small>
    <small id="androidHint" style="display:none; color:#6b7280;">
      V menu prohlížeče zvol <b>Nainstalovat aplikaci</b> nebo <b>Přidat na plochu</b>.
    </small>
  </div>

  <script>
    let deferredPrompt = null;
    const installBox = document.getElementById('pwaInstall');
    const installBtn = document.getElementById('installBtn');
    const iosHint = document.getElementById('iosHint');
    const androidHint = document.getElementById('androidHint');

    const displayMode = window.matchMedia('(display-mode: standalone)');
    const isStandalone = () => displayMode.matches || window.navigator.standalone === true;
    const userAgent = navigator.userAgent.toLowerCase();
    const isIOS = /iphone|ipad|ipod/i.test(userAgent)
      || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
    const isAndroid = /android/i.test(userAgent);

    function hideInstallOffer() {
      installBox.style.display = 'none';
      installBtn.style.display = 'none';
      iosHint.style.display = 'none';
      androidHint.style.display = 'none';
    }

    if (isStandalone()) {
      hideInstallOffer();
    } else if (isIOS) {
      installBox.style.display = 'block';
      iosHint.style.display = 'inline';
    } else if (isAndroid) {
      installBox.style.display = 'block';
      installBtn.style.display = 'inline-flex';
    }

    // Chrome/Android událost pošle jen tehdy, když lze aplikaci instalovat.
    window.addEventListener('beforeinstallprompt', (event) => {
      if (!isAndroid || isStandalone()) return;
      event.preventDefault();
      deferredPrompt = event;
      androidHint.style.display = 'none';
      installBox.style.display = 'block';
      installBtn.style.display = 'inline-block';
    });

    installBtn.addEventListener('click', async () => {
      if (isStandalone()) return;
      if (!deferredPrompt) {
        androidHint.style.display = 'inline';
        return;
      }
      installBtn.disabled = true;
      deferredPrompt.prompt();
      try {
        const choice = await deferredPrompt.userChoice;
        if (choice.outcome === 'dismissed') {
          installBtn.style.display = 'inline-flex';
          androidHint.style.display = 'inline';
        } else {
          installBtn.style.display = 'none';
        }
      } finally {
        deferredPrompt = null;
        installBtn.disabled = false;
      }
    });

    window.addEventListener('appinstalled', () => {
      deferredPrompt = null;
      hideInstallOffer();
    });

    displayMode.addEventListener?.('change', () => {
      if (isStandalone()) hideInstallOffer();
    });
  </script>
  <!-- Top bar -->
  <header class="nk-header">
    <div class="nk-container">
      <a class="nk-brand" href="<?= htmlspecialchars($BASE_URL) ?>/index.php">
        <img class="nk-logo" src="<?= htmlspecialchars($BASE_URL) ?>/img/logo.png" alt="Šipky Třešť" loading="lazy">
        <span class="nk-title">Šipky Třešť</span>
      </a>

      <nav class="nk-nav">
        <a href="<?= htmlspecialchars($BASE_URL) ?>/index.php" class="nk-link">Domů</a>
        <a href="<?= htmlspecialchars($BASE_URL) ?>/rezervace.php" class="nk-link">Rezervace terčů</a>
        <button type="button" class="nk-theme-toggle" data-theme-toggle aria-label="Přepnout na tmavý režim" title="Přepnout barevný režim"><span aria-hidden="true">☾</span><span class="nk-theme-toggle__text">Tmavý režim</span></button>

        <?php if (empty($hideRocnikDropdown)): ?>
          <?php $returnTo = $_SERVER['REQUEST_URI'] ?? ($BASE_URL.'/index.php'); ?>
          <form class="nk-season" method="post" action="<?= htmlspecialchars($BASE_URL) ?>/set_season.php">
            <label for="season" class="nk-season__label">Ročník</label>
            <select id="season" name="rocnik_id" onchange="this.form.submit()">
              <?php foreach ($seasons as $s): $sid=(int)$s['id']; ?>
                <option value="<?= $sid ?>" <?= $sid===$selSeason?'selected':'' ?>>
                  <?= htmlspecialchars($s['nazev']) ?>
                </option>
              <?php endforeach; ?>
            </select>
            <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf'] ?? '') ?>">
            <input type="hidden" name="return_to" value="<?= htmlspecialchars($returnTo) ?>">
          </form>
        <?php endif; ?>

             <!-- Přihlášení/odhlášení – DESKTOP (na mobilu se .nk-link skrývají v CSS) -->
        <?php
          $currentRequestUri = $_SERVER['REQUEST_URI'] ?? ($BASE_URL.'/index.php');
          $loginHref = $BASE_URL.'/login.php?next='.rawurlencode($currentRequestUri);
        ?>
        <?php if ($username): ?>
          <span class="nk-link" style="opacity:.85;cursor:default">Přihlášen: <?= htmlspecialchars($username) ?></span>
          <a href="<?= htmlspecialchars($BASE_URL) ?>/logout.php" class="nk-link">Odhlásit</a>
        <?php else: ?>
          <a href="<?= htmlspecialchars($loginHref) ?>" class="nk-link">Přihlásit</a>
        <?php endif; ?>
        <?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
if (($_SESSION['role'] ?? '') === 'admin') {
    echo '<a class="nk-admin-link" href="/liga-app/admin/index.php">Admin</a>';
}
// (máš-li Bootstrap, klidně to obal do <li class="nav-item"><a class="nav-link" ...>…</a></li>)
?>
        <!-- Jediné hamburger tlačítko pro celé mobilní menu -->
        <button class="nk-burger" aria-label="Menu" data-toggle="mobilemenu">
          <span></span><span></span><span></span>
        </button>
      </nav>
    </div>
  </header>

  <!-- Overlay (klik zavře menu) -->
  <div id="nk-dim" class="nk-dim" aria-hidden="true"></div>

  <!-- Jediné mobilní menu -->
  <aside class="nk-mobilemenu" id="nk-mobilemenu" aria-hidden="true">
    <div class="nk-mm-head">
      <strong>Menu</strong>
      <button class="nk-mm-close" aria-label="Zavřít" data-toggle="mobilemenu">✕</button>
    </div>

   <nav class="nk-mm-inner">
  <!-- HORNÍ BOX: přihlášení + ročník + hlavní odkazy -->
  <div class="nk-mm-box">
    <div class="nk-mm-auth">
      <?php if ($username): ?>
        <div class="nk-user">Přihlášen: <?= htmlspecialchars($username) ?></div>
        <a class="nk-mm-item" href="<?= htmlspecialchars($BASE_URL) ?>/logout.php">Odhlásit</a>
      <?php else: ?>
        <a class="nk-mm-item" href="<?= htmlspecialchars($loginHref) ?>">Přihlásit</a>
      <?php endif; ?>
    </div>

    <?php $returnTo = $_SERVER['REQUEST_URI'] ?? ($BASE_URL.'/index.php'); ?>
    <form class="nk-season nk-season--mobile" method="post" action="<?= htmlspecialchars($BASE_URL) ?>/set_season.php">
      <label for="season_m" class="nk-season__label">Ročník</label>
      <select id="season_m" name="rocnik_id" onchange="this.form.submit()">
        <?php foreach ($seasons as $s): $sid=(int)$s['id']; ?>
          <option value="<?= $sid ?>" <?= $sid===$selSeason?'selected':'' ?>>
            <?= htmlspecialchars($s['nazev']) ?>
          </option>
        <?php endforeach; ?>
      </select>
      <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf'] ?? '') ?>">
      <input type="hidden" name="return_to" value="<?= htmlspecialchars($returnTo) ?>">
    </form>

    <div class="nk-mm-stack">
      <a class="nk-mm-item" href="<?= htmlspecialchars($BASE_URL) ?>/index.php">Přehled</a>
      <a class="nk-mm-item" href="<?= htmlspecialchars($BASE_URL) ?>/rezervace.php">Rezervace terčů</a>
      <a class="nk-mm-item" href="<?= htmlspecialchars($BASE_URL) ?>/kompletni-statistiky.php">Kompletní statistiky</a>
        <a class="nk-mm-item" href="<?= htmlspecialchars($prezidentskyPoharUrl) ?>">Prezidentský pohár</a>
  <a class="nk-mm-item" href="<?= htmlspecialchars($BASE_URL) ?>/docs/pravidla.pdf?v=20260909" target="_blank" rel="noopener">Pravidla (PDF)</a>

      <a class="nk-mm-item" href="https://sipkytrest.cz">Zpět na sipkytrest.cz</a>
    </div>
  </div>

  <hr class="nk-topmenu__hr">

  <!-- SEZNAM LIG (kopie sidebaru) -->
  <div class="nk-mm-copy">
    <?php
      $__sidebarPath = __DIR__.'/sidebar.php';
      if (file_exists($__sidebarPath)) {
        $MOBILE_MENU = 1;         // důležité pro chování uvnitř sidebaru
        include $__sidebarPath;
        unset($MOBILE_MENU);
      } else {
        echo '<p style="color:#b91c1c">Sidebar není k dispozici.</p>';
      }
    ?>
  </div>
</nav>

  </aside>

  <div class="nk-wrapper">
    <?php
    $sidebar = __DIR__.'/sidebar.php';
    if (file_exists($sidebar)) {
      echo '<aside class="nk-sidebar" id="nk-sidebar">';
      include $sidebar;
      echo '</aside>';
    }
    ?>
    <main id="content" class="nk-content">
