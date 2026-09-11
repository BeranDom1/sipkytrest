<?php
require __DIR__.'/header.php';
require_once __DIR__.'/security/csrf.php';

// -------- Bezpečné určení návratové adresy --------
$base = $BASE_URL ?? '/liga-app';                               // z header.php
$nextRaw = (string)($_GET['next'] ?? ($_SERVER['HTTP_REFERER'] ?? $base.'/'));
$parsed  = parse_url($nextRaw);
$path    = is_array($parsed) ? ($parsed['path'] ?? '') : '';

// Dovol jen interní cestu v rámci aplikace a zachovej její query parametry
// (např. id zápasu). Externí URL ani podobný prefix typu /liga-app-foo neprojdou.
$isInternal = is_array($parsed)
    && empty($parsed['scheme'])
    && empty($parsed['host'])
    && ($path === $base || strpos($path, rtrim($base, '/').'/') === 0);
$loginPath = rtrim($base,'/').'/login.php';
if (!$isInternal || $path === $loginPath) {
    $next = $base.'/';
} else {
    $next = $path;
    if (!empty($parsed['query'])) {
        $next .= '?'.$parsed['query'];
    }
}

$csrf = csrf_token();
?>
<div class="container mt-5" style="max-width:400px">
  <h2>Přihlášení</h2>
  <form action="<?= htmlspecialchars($base) ?>/login_action.php" method="post">
    <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
    <input type="hidden" name="next" value="<?= htmlspecialchars($next) ?>">

    <div class="mb-3">
      <label for="username" class="form-label">Uživatelské jméno</label>
      <input type="text" class="form-control" id="username" name="username" required>
    </div>
    <div class="mb-3">
      <label for="password" class="form-label">Heslo</label>
      <input type="password" class="form-control" id="password" name="password" required>
    </div>

    <?php if(!empty($_GET['error'])): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($_GET['error']) ?></div>
    <?php endif; ?>

    <button type="submit" class="btn btn-primary">Přihlásit</button>
  </form>
</div>
<?php require __DIR__.'/footer.php'; ?>
