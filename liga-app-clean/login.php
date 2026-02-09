<?php
require __DIR__.'/header.php';
require_once __DIR__.'/security/csrf.php';

// -------- Bezpečné určení návratové adresy --------
$base = $BASE_URL ?? '/liga-app';                               // z header.php
$nextRaw = $_GET['next'] ?? ($_SERVER['HTTP_REFERER'] ?? $base.'/');
$path    = parse_url($nextRaw, PHP_URL_PATH) ?? $base.'/';

// dovol jen cesty v rámci aplikace a nevracej se na login
if (strpos($path, $base) !== 0) {
    $path = $base.'/';
}
$loginPath = rtrim($base,'/').'/login.php';
if ($path === $loginPath) {
    $path = $base.'/';
}
$next = $path;

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
