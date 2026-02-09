<?php
require __DIR__ . '/../db.php';

$rocnik_id = (int)$_POST['rocnik_id'];
$liga_id   = (int)$_POST['liga_id'];
$logo      = $_POST['logo'] ?: null;
$alt       = trim($_POST['alt'] ?? '');

// existuje záznam?
$st = $conn->prepare("
  SELECT id FROM ligy_loga
  WHERE rocnik_id = ? AND liga_id = ?
");
$st->bind_param('ii', $rocnik_id, $liga_id);
$st->execute();
$res = $st->get_result();
$exist = $res->fetch_assoc();
$st->close();

if ($exist) {
    $st = $conn->prepare("
      UPDATE ligy_loga
      SET logo = ?, alt = ?
      WHERE id = ?
    ");
    $st->bind_param('ssi', $logo, $alt, $exist['id']);
} else {
    $st = $conn->prepare("
      INSERT INTO ligy_loga (rocnik_id, liga_id, logo, alt)
      VALUES (?, ?, ?, ?)
    ");
    $st->bind_param('iiss', $rocnik_id, $liga_id, $logo, $alt);
}

$st->execute();
$st->close();

header('Location: ligy_loga.php');
exit;
