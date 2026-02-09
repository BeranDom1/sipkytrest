<?php
require __DIR__ . '/../db.php';
require __DIR__ . '/pohar_funkce.php';
session_start();

$turnaj_id = (int)($_GET['id'] ?? 0);

$role = $_SESSION['role'] ?? null;
if (!in_array($role, ['admin', 'stat_editor'])) {
    http_response_code(403);
    exit('Přístup zakázán');
}

if ($turnaj_id <= 0) {
    exit('Neplatné ID turnaje');
}

/* === hráči v turnaji === */
$stmt = $conn->prepare("
    SELECT u.libovolne_id, u.jmeno
    FROM turnaj_hraci th
    JOIN hraci_unikatni_jmena u ON u.libovolne_id = th.hrac_id
    WHERE th.turnaj_id = ?
    ORDER BY u.jmeno
");
$stmt->bind_param("i", $turnaj_id);
$stmt->execute();
$res = $stmt->get_result();

$hraci = [];
while ($r = $res->fetch_assoc()) {
    $hraci[$r['libovolne_id']] = $r['jmeno'];
}

/* === zápasy 1. kola === */
$stmt = $conn->prepare("
    SELECT *
    FROM turnaj_zapasy
    WHERE turnaj_id = ? AND kolo = 1
    ORDER BY poradi
");
$stmt->bind_param("i", $turnaj_id);
$stmt->execute();
$zapasy = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="cs">
<head>
<meta charset="UTF-8">
<title>Správa 1. kola</title>
<style>
body {
    font-family: system-ui, Arial, sans-serif;
    background: #f4f4f4;
    padding: 20px;
}
.box {
    max-width: 900px;
    margin: auto;
    background: #fff;
    padding: 20px;
    border-radius: 8px;
}
.zapas {
    border: 1px solid #ddd;
    border-radius: 6px;
    padding: 10px;
    margin-bottom: 10px;
}
.row {
    display: grid;
    grid-template-columns: 1fr auto 1fr auto;
    gap: 8px;
    align-items: center;
}
select, button {
    padding: 6px;
}
.locked {
    background: #eee;
    color: #888;
}
</style>
</head>

<body>
<div class="box">
<h1>Správa 1. kola</h1>

<?php while ($z = $zapasy->fetch_assoc()): ?>
<form method="post" action="uloz_obsazeni.php" class="zapas">
<input type="hidden" name="zapas_id" value="<?= $z['id'] ?>">
<input type="hidden" name="turnaj_id" value="<?= $turnaj_id ?>">

<div class="row">
<select name="hrac1_id" <?= $z['vitez_id'] ? 'disabled' : '' ?>>
    <option value="">— hráč —</option>
    <?php foreach ($hraci as $id => $jmeno): ?>
        <option value="<?= $id ?>" <?= $z['hrac1_id']==$id?'selected':'' ?>>
            <?= htmlspecialchars($jmeno) ?>
        </option>
    <?php endforeach; ?>
</select>

<strong>vs</strong>

<select name="hrac2_id" <?= $z['vitez_id'] ? 'disabled' : '' ?>>
    <option value="">— soupeř —</option>
    <?php foreach ($hraci as $id => $jmeno): ?>
        <option value="<?= $id ?>" <?= $z['hrac2_id']==$id?'selected':'' ?>>
            <?= htmlspecialchars($jmeno) ?>
        </option>
    <?php endforeach; ?>
</select>

<?php if (!$z['vitez_id']): ?>
<button type="submit">Uložit</button>
<?php else: ?>
<span class="locked">Odehráno</span>
<?php endif; ?>
</div>
</form>
<?php endwhile; ?>

<p>
<a href="pohar_turnaj.php?id=<?= $turnaj_id ?>">← Zpět na turnaj</a>
</p>

</div>
</body>
</html>
