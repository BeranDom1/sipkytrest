<?php
require __DIR__ . '/../db.php';
require __DIR__ . '/pohar_funkce.php';
session_start();

if (!($_SESSION['is_admin'] ?? false)) {
    http_response_code(403);
    exit('Přístup zakázán');
}

$step = (int)($_GET['step'] ?? 1);
?>
<!DOCTYPE html>
<html lang="cs">
<head>
<meta charset="UTF-8">
<title>Vytvořit turnaj</title>

<style>
html, body {
    margin: 0;
    padding: 0;
}
body {
    font-family: system-ui, Arial, sans-serif;
    background: #f0f0f0;
    padding: 40px;
}
.admin-box {
    max-width: 640px;
    margin: 0 auto;
    background: #fff;
    padding: 24px;
    border-radius: 10px;
    box-shadow: 0 10px 30px rgba(0,0,0,.15);
}
h1 {
    margin-bottom: 16px;
}
label {
    display: block;
    margin-top: 14px;
    font-weight: 600;
}
input, select {
    width: 100%;
    margin-top: 6px;
    padding: 10px;
    border: 1px solid #777;
    border-radius: 4px;
}
button {
    width: 100%;
    margin-top: 24px;
    padding: 12px;
    font-size: 16px;
    font-weight: bold;
    border-radius: 6px;
    border: none;
    background: #1976d2;
    color: #fff;
    cursor: pointer;
}
button:hover {
    background: #125ea9;
}
.success {
    background: #e8f5e9;
    border: 1px solid #81c784;
    padding: 16px;
    border-radius: 6px;
}
</style>
</head>

<body>
<div class="admin-box">

<?php if ($step === 1): ?>

<h1>Vytvořit turnaj</h1>

<form method="post" action="?step=2">

    <label>Název turnaje</label>
    <input name="nazev" required>

    <label>Ročník</label>
    <select name="rocnik_id" required>
        <?php
        $res = $conn->query("SELECT id, nazev FROM rocniky ORDER BY id DESC");
        while ($r = $res->fetch_assoc()) {
            echo '<option value="'.$r['id'].'">'.htmlspecialchars($r['nazev']).'</option>';
        }
        ?>
    </select>

    <button type="submit">Pokračovat</button>
</form>

<?php elseif ($step === 2):

$rocnik_id = (int)$_POST['rocnik_id'];

$stmt = $conn->prepare("
    SELECT u.libovolne_id, u.jmeno
    FROM hraci_v_sezone hvs
    JOIN hraci_unikatni_jmena u
        ON u.libovolne_id = hvs.hrac_id
    WHERE hvs.rocnik_id = ?
    ORDER BY u.jmeno
");
$stmt->bind_param("i", $rocnik_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<h1>Vyber hráče turnaje</h1>

<form method="post" action="?step=3">

    <input type="hidden" name="nazev" value="<?= htmlspecialchars($_POST['nazev']) ?>">
    <input type="hidden" name="rocnik_id" value="<?= $rocnik_id ?>">

    <?php while ($h = $result->fetch_assoc()): ?>
        <label>
            <input type="checkbox" name="hraci[]" value="<?= $h['libovolne_id'] ?>" checked>
            <?= htmlspecialchars($h['jmeno']) ?>
        </label>
    <?php endwhile; ?>

    <button type="submit">Vytvořit turnaj</button>
</form>

<?php elseif ($step === 3):

$conn->begin_transaction();

try {
    // 1️⃣ vytvoř turnaj
    $stmt = $conn->prepare("
        INSERT INTO turnaje (nazev, rocnik_id)
        VALUES (?, ?)
    ");
    $stmt->bind_param(
        "si",
        $_POST['nazev'],
        $_POST['rocnik_id']
    );
    $stmt->execute();

    $turnaj_id = $conn->insert_id;

    // 2️⃣ přiřaď hráče k turnaji
    $stmt = $conn->prepare("
        INSERT INTO turnaj_hraci (turnaj_id, hrac_id)
        VALUES (?, ?)
    ");
    foreach ($_POST['hraci'] as $hrac_id) {
        $stmt->bind_param("ii", $turnaj_id, $hrac_id);
        $stmt->execute();
    }

    // 3️⃣ vytvoř strukturu pavouka (BEZ hráčů)
    generujTurnaj($conn, $turnaj_id);

    $conn->commit();
    ?>

    <div class="success">
        <h2>✅ Turnaj vytvořen</h2>
        <p>
            <a href="/liga-app/pohar/pohar_turnaj.php?id=<?= $turnaj_id ?>">
                Zobrazit turnaj
            </a>
        </p>
    </div>

<?php
} catch (Throwable $e) {
    $conn->rollback();
    echo '<p>Chyba: '.htmlspecialchars($e->getMessage()).'</p>';
}
endif;
?>

</div>
</body>
</html>
