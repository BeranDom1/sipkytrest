<?php
// /liga-app/admin/create_admin.php
// $BASE_URL = '/liga-app';

// require_once __DIR__.'/../db.php';
// require_once __DIR__.'/../security/csrf.php';

// if (session_status() !== PHP_SESSION_ACTIVE) {
    $isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => $BASE_URL,
        'secure'   => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();


// $msg = '';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_POST['csrf'] ?? '')) {
        $err = 'CSRF ověření selhalo.';
    } else {
        $u  = trim($_POST['username'] ?? '');
        $p  = $_POST['password']  ?? '';
        $p2 = $_POST['password2'] ?? '';

        if ($u === '' || $p === '' || $p2 === '') {
            $err = 'Vyplň uživatelské jméno i heslo.';
        } elseif ($p !== $p2) {
            $err = 'Hesla se neshodují.';
        } else {
            // vlož/aktualizuj admina (tabulka už MUSÍ existovat – viz SQL v phpMyAdminu)
            $hash = password_hash($p, PASSWORD_DEFAULT);

            try {
                $stmt = $conn->prepare("
                    INSERT INTO admins (username, password_hash) VALUES (?, ?)
                    ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash)
                ");
                $stmt->bind_param('ss', $u, $hash);
                $stmt->execute();

                $msg = "Admin „".htmlspecialchars($u)."“ uložen. ".
                       "<a href=\"{$BASE_URL}/login.php?next=" .
                       urlencode($BASE_URL.'/admin/index.php') . "\">Přejít na přihlášení</a>";

            } catch (Throwable $e) {
                $err = "Chyba při ukládání: ".$e->getMessage()."<br>Tip: je vytvořená tabulka <code>admins</code>?";
            }
        }
    }
}

$csrf = csrf_token();
?>
<!doctype html>
<html lang="cs">
<head><meta charset="utf-8"><title>Vytvořit admina</title>
<meta name="robots" content="noindex,nofollow">
<link rel="stylesheet" href="<?= $BASE_URL ?>/style.css">
<style>
.box{max-width:420px;margin:3rem auto;background:#fff;border:1px solid #ddd;border-radius:12px;padding:1rem}
label{display:block;margin-top:.6rem}
input{width:100%;padding:.5rem}
.btn{margin-top:1rem;padding:.6rem 1rem;background:#111;color:#fff;border:0;border-radius:10px;cursor:pointer}
.msg{margin-top:1rem;color:#0a0}
.err{margin-top:1rem;color:#c00}
</style>
</head>
<body>
<div class="box">
  <h2>Vytvořit/aktualizovat admina</h2>
  <?php if($msg): ?><div class="msg"><?= $msg ?></div><?php endif; ?>
  <?php if($err): ?><div class="err"><?= $err ?></div><?php endif; ?>

  <form method="post">
    <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
    <label>Uživatelské jméno
      <input type="text" name="username" required>
    </label>
    <label>Heslo
      <input type="password" name="password" required>
    </label>
    <label>Heslo znovu
      <input type="password" name="password2" required>
    </label>
    <button class="btn" type="submit">Uložit</button>
  </form>

  <p style="font-size:.9rem;color:#666;margin-top:.75rem">
    Po úspěchu tento soubor <b>ihned smaž</b>.
  </p>
</div>
</body>
</html>
