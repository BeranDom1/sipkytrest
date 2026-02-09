<?php
// /liga-app/admin/create_user.php
// require __DIR__.'/../db.php';
// require __DIR__.'/_auth.php'; // pustí jen role=admin

// $msg = $err = null;

// if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $jmeno    = trim($_POST['jmeno'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $heslo    = $_POST['heslo'] ?? '';
    $role     = $_POST['role'] ?? 'stat_editor'; // default editor

    if ($jmeno === '' || $username === '' || $heslo === '') {
        $err = 'Vyplň jméno, uživatelské jméno i heslo.';
    } elseif (!in_array($role, ['user','stat_editor','admin'], true)) {
        $err = 'Neplatná role.';
    } else {
        $hash = password_hash($heslo, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO uzivatele (jmeno, username, password, role, must_change_pw) VALUES (?, ?, ?, ?, 0)");
        $stmt->bind_param('ssss', $jmeno, $username, $hash, $role);
        if ($stmt->execute()) {
            $msg = 'Uživatel vytvořen.';
        } else {
            $err = 'Chyba: ' . $stmt->error;
        }
        $stmt->close();
    }
}
?>
<!doctype html><meta charset="utf-8">
<h2>Vytvořit uživatele</h2>
<?php if ($msg): ?><p style="color:green"><?= htmlspecialchars($msg) ?></p><?php endif; ?>
<?php if ($err): ?><p style="color:red"><?= htmlspecialchars($err) ?></p><?php endif; ?>

<form method="post">
  <div><label>Jméno: <input name="jmeno" required></label></div>
  <div><label>Uživatelské jméno: <input name="username" required></label></div>
  <div><label>Heslo: <input type="password" name="heslo" required></label></div>
  <div>
    <label>Role:
      <select name="role">
        <option value="stat_editor" selected>Editor (může zadávat výsledky)</option>
        <option value="user">User (bez práv)</option>
        <option value="admin">Admin (všechno)</option>
      </select>
    </label>
  </div>
  <button type="submit">Vytvořit</button>
</form>
