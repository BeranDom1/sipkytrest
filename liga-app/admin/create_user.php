<?php
$BASE_URL = '/liga-app';

require_once __DIR__.'/../db.php';
require_once __DIR__.'/_auth.php';
require_once __DIR__.'/../security/csrf.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

/* ===== pouze admin ===== */
if (($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(403);
    exit('Přístup odepřen');
}

$msg = '';
$err = '';

/* ================= AKCE ================= */
$action = $_POST['action'] ?? '';

/* ===== vytvoření uživatele ===== */
if ($action === 'create') {
    if (!csrf_check($_POST['csrf'] ?? '')) {
        $err = 'CSRF chyba.';
    } else {
        $jmeno    = trim($_POST['jmeno']);
        $username = trim($_POST['username']);
        $role     = $_POST['role'];
        $p        = $_POST['password'];
        $p2       = $_POST['password2'];

        if ($jmeno === '' || $username === '' || $p === '' || $p2 === '') {
            $err = 'Vyplň všechna pole.';
        } elseif ($p !== $p2) {
            $err = 'Hesla se neshodují.';
        } else {
            $chk = $conn->prepare("SELECT id FROM uzivatele WHERE username=?");
            $chk->bind_param('s', $username);
            $chk->execute();
            $chk->store_result();

            if ($chk->num_rows) {
                $err = 'Uživatel už existuje.';
            } else {
                $hash = password_hash($p, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("
                  INSERT INTO uzivatele (jmeno, username, password, role, must_change_pw)
                  VALUES (?, ?, ?, ?, 0)
                ");
                $stmt->bind_param('ssss', $jmeno, $username, $hash, $role);
                $stmt->execute();
                $msg = 'Uživatel vytvořen.';
            }
        }
    }
}

/* ===== reset hesla ===== */
if ($action === 'reset_pw') {
    if (csrf_check($_POST['csrf'] ?? '')) {
        $uid = (int)$_POST['user_id'];
        $new = $_POST['new_password'];

        if ($new !== '') {
            $hash = password_hash($new, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE uzivatele SET password=? WHERE id=?");
            $stmt->bind_param('si', $hash, $uid);
            $stmt->execute();
            $msg = 'Heslo bylo změněno.';
        }
    }
}

/* ===== změna role ===== */
if ($action === 'change_role') {
    if (csrf_check($_POST['csrf'] ?? '')) {
        $uid  = (int)$_POST['user_id'];
        $role = $_POST['role'];

        $stmt = $conn->prepare("UPDATE uzivatele SET role=? WHERE id=?");
        $stmt->bind_param('si', $role, $uid);
        $stmt->execute();
        $msg = 'Role změněna.';
    }
}

/* ===== smazání ===== */
if ($action === 'delete') {
    if (csrf_check($_POST['csrf'] ?? '')) {
        $uid = (int)$_POST['user_id'];

        if ($uid === (int)$_SESSION['user_id']) {
            $err = 'Nemůžeš smazat sám sebe.';
        } else {
            $stmt = $conn->prepare("DELETE FROM uzivatele WHERE id=?");
            $stmt->bind_param('i', $uid);
            $stmt->execute();
            $msg = 'Uživatel smazán.';
        }
    }
}

/* ===== seznam uživatelů ===== */
$users = $conn->query("
  SELECT id, jmeno, username, role, created_at
  FROM uzivatele
  ORDER BY role DESC, jmeno
")->fetch_all(MYSQLI_ASSOC);

$csrf = csrf_token();

?>
<!doctype html>
<html lang="cs">
<head>
<meta charset="utf-8">
<title>Správa uživatelů</title>
<link rel="stylesheet" href="<?= $BASE_URL ?>/style.css?v=20260911">
<style>
.box{max-width:920px;margin:2rem auto;background:#fff;border:1px solid #ddd;border-radius:12px;padding:1.2rem}
table{width:100%;border-collapse:collapse}
th,td{padding:.45rem;border-bottom:1px solid #eee}
th{background:#f4f4f4}
.small{font-size:.85rem}
.btn{padding:.3rem .6rem;border-radius:8px;border:1px solid #333;background:#111;color:#fff}
.btn.red{background:#b11212}
.btn.gray{background:#444}
form.inline{display:inline}
</style>
  <link rel="stylesheet" href="/liga-app/assets/admin-theme.css?v=20260911">
  <script src="/liga-app/assets/admin-theme.js?v=1"></script>
</head>
<body>

<div class="box">
<h2>Správa uživatelů</h2>

<?php if ($msg): ?><p style="color:green"><?= $msg ?></p><?php endif; ?>
<?php if ($err): ?><p style="color:red"><?= $err ?></p><?php endif; ?>

<h3>➕ Nový uživatel</h3>
<form method="post">
<input type="hidden" name="csrf" value="<?= $csrf ?>">
<input type="hidden" name="action" value="create">

<input name="jmeno" placeholder="Celé jméno" required>
<input name="username" placeholder="Uživatelské jméno" required>

<select name="role">
  <option value="stat_editor">Stat editor</option>
  <option value="admin">Admin</option>
</select>

<input type="password" name="password" placeholder="Heslo" required>
<input type="password" name="password2" placeholder="Heslo znovu" required>

<button class="btn">Vytvořit</button>
</form>

<hr>

<h3>👥 Existující uživatelé</h3>

<table>
<tr>
  <th>Jméno</th>
  <th>Login</th>
  <th>Role</th>
  <th>Akce</th>
</tr>

<?php foreach ($users as $u): ?>
<tr>
<td><?= htmlspecialchars($u['jmeno']) ?></td>
<td><?= htmlspecialchars($u['username']) ?></td>
<td>
<form method="post" class="inline">
<input type="hidden" name="csrf" value="<?= $csrf ?>">
<input type="hidden" name="action" value="change_role">
<input type="hidden" name="user_id" value="<?= $u['id'] ?>">
<select name="role" onchange="this.form.submit()">
  <option value="stat_editor" <?= $u['role']=='stat_editor'?'selected':'' ?>>stat_editor</option>
  <option value="admin" <?= $u['role']=='admin'?'selected':'' ?>>admin</option>
</select>
</form>
</td>
<td class="small">

<form method="post" class="inline">
<input type="hidden" name="csrf" value="<?= $csrf ?>">
<input type="hidden" name="action" value="reset_pw">
<input type="hidden" name="user_id" value="<?= $u['id'] ?>">
<input type="password" name="new_password" placeholder="Nové heslo" required>
<button class="btn gray">Reset</button>
</form>

<form method="post" class="inline" onsubmit="return confirm('Opravdu smazat?')">
<input type="hidden" name="csrf" value="<?= $csrf ?>">
<input type="hidden" name="action" value="delete">
<input type="hidden" name="user_id" value="<?= $u['id'] ?>">
<button class="btn red">Smazat</button>
</form>

</td>
</tr>
<?php endforeach; ?>
</table>

<p style="margin-top:1rem">
<a href="<?= $BASE_URL ?>/admin/index.php">← zpět do administrace</a>
</p>

</div>
</body>
</html>

