<?php
require __DIR__ . '/../db.php';
session_start();

$role = $_SESSION['role'] ?? null;
if (!in_array($role, ['admin', 'stat_editor'])) {
    http_response_code(403);
    exit('Přístup zakázán');
}

$zapas_id  = (int)$_POST['zapas_id'];
$turnaj_id = (int)$_POST['turnaj_id'];
$h1 = (int)($_POST['hrac1_id'] ?? 0);
$h2 = (int)($_POST['hrac2_id'] ?? 0);

if ($h1 && $h1 === $h2) {
    exit('Hráč nemůže hrát sám proti sobě');
}

/* kontrola duplicity */
$stmt = $conn->prepare("
    SELECT COUNT(*) FROM turnaj_zapasy
    WHERE turnaj_id = ?
      AND id != ?
      AND (hrac1_id = ? OR hrac2_id = ?)
");
$stmt->bind_param("iiii", $turnaj_id, $zapas_id, $h1, $h1);
$stmt->execute();
if ($stmt->get_result()->fetch_row()[0] > 0) {
    exit('Hráč už je v jiném zápase');
}

$stmt->bind_param("iiii", $turnaj_id, $zapas_id, $h2, $h2);
$stmt->execute();
if ($stmt->get_result()->fetch_row()[0] > 0) {
    exit('Hráč už je v jiném zápase');
}

/* uložení */
$stmt = $conn->prepare("
    UPDATE turnaj_zapasy
    SET hrac1_id = ?, hrac2_id = ?
    WHERE id = ? AND vitez_id IS NULL
");
$stmt->bind_param("iii", $h1, $h2, $zapas_id);
$stmt->execute();

header("Location: pohar_1kolo_admin.php?id=".$turnaj_id);
exit;
