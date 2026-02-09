<?php
require __DIR__.'/db.php';
$rocnik_id = (int)($_SESSION['rocnik_id'] ?? 1);
$ids = array_map('intval', $_GET['ids'] ?? []);
if (!$ids) { echo "Bez hráčů"; exit; }

$ph = implode(',', array_fill(0, count($ids), '?'));
$sql = "SELECT hrac1_id, hrac2_id, skore1, skore2
        FROM zapasy
        WHERE rocnik_id = ?
          AND hrac1_id IN ($ph)
          AND hrac2_id IN ($ph)";
$st = $conn->prepare($sql);
$types = 'i' . str_repeat('i', count($ids)*2);
$st->bind_param($types, $rocnik_id, ...$ids, ...$ids);
$st->execute();
$res = $st->get_result();
$rows = $res->fetch_all(MYSQLI_ASSOC);
$st->close();

header('Content-Type: application/json; charset=utf-8');
echo json_encode($rows);
