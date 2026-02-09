<?php
// /liga-app/admin/hraci.php
require_once __DIR__.'/../db.php';
require_once __DIR__.'/_auth.php';
require_once __DIR__.'/../security/csrf.php';

$msg = $err = '';

// === uložení nového hráče =====================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_POST['csrf'] ?? '')) {
        $err = 'CSRF ověření selhalo.';
    } else {
        $jmeno = trim($_POST['jmeno'] ?? '');

        if ($jmeno === '') {
            $err = 'Zadej jméno hráče.';
        } else {
            // kontrola duplicity (case-insensitive)
            $stmt = $conn->prepare("
                SELECT 1
                FROM hraci_unikatni_jmena
                WHERE LOWER(jmeno) = LOWER(?)
                LIMIT 1
            ");
            $stmt->bind_param('s', $jmeno);
            $stmt->execute();
            $exists = $stmt->get_result()->fetch_column();
            $stmt->close();

            if ($exists) {
                $err = 'Tento hráč už v databázi existuje.';
            } else {
                $ins = $conn->prepare("
                    INSERT INTO hraci_unikatni_jmena (jmeno)
                    VALUES (?)
                ");
                $ins->bind_param('s', $jmeno);

                if ($ins->execute()) {
                    $msg = 'Hráč byl úspěšně přidán.';
                } else {
                    $err = 'Chyba při ukládání: '.$ins->error;
                }
                $ins->close();
            }
        }
    }
}

$csrf = csrf_token();

// seznam všech hráčů
$players = $conn->query("
    SELECT libovolne_id, jmeno
    FROM hraci_unikatni_jmena
    ORDER BY jmeno
")->fetch_all(MYSQLI_ASSOC);
?>
<!doctype html>
<html lang="cs">
<head>
<meta charset="utf-8">
<title>Správa hráčů</title>
<link rel="stylesheet" href="/liga-app/style.css">
<style>
.card{border:1px solid #ddd;padding:1rem;border-radius:12px;max-width:720px;margin:2rem auto;background:#fff}
label{display:block;margin:.6rem 0 .2rem}
input{width:100%;padding:.4rem}
.btn{margin-top:.6rem;padding:.5rem .9rem;border-radius:10px;border:1px solid #333;background:#111;color:#fff;cursor:pointer}
.msg{color:#0a0;margin:.6rem 0}
.err{color:#c00;margin:.6rem 0}
table{width:100%;border-collapse:collapse;margin-top:1rem}
th,td{padding:.35rem;border-bottom:1px solid #eee;text-align:left}
</style>
</head>
<body>

<div class="card">
  <h1>Hráči – databáze</h1>

  <?php if ($msg): ?><div class="msg"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
  <?php if ($err): ?><div class="err"><?= htmlspecialchars($err) ?></div><?php endif; ?>

  <form method="post">
    <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
    <label>Jméno hráče</label>
    <input name="jmeno" required>
    <button class="btn" type="submit">Přidat hráče</button>
  </form>

  <h2>Seznam hráčů</h2>
  <table>
    <thead>
      <tr><th>Jméno</th></tr>
    </thead>
    <tbody>
    <?php foreach ($players as $p): ?>
      <tr>
        <td><?= htmlspecialchars($p['jmeno']) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>

  <p style="margin-top:1rem">
    <a class="btn" href="/liga-app/admin/index.php">Zpět do administrace</a>
  </p>
</div>

</body>
</html>
