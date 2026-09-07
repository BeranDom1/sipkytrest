<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../security/csrf.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_check($_POST['csrf'] ?? '')) {
    http_response_code(403);
    exit('CSRF ověření selhalo.');
}

$rocnik_id = (int)$_POST['rocnik_id'];
$liga_id   = (int)$_POST['liga_id'];
$logo      = $_POST['logo'] ?: null;
$alt       = trim($_POST['alt'] ?? '');

$seasonStmt = $conn->prepare('SELECT stav FROM rocniky WHERE id = ?');
$seasonStmt->bind_param('i', $rocnik_id);
$seasonStmt->execute();
$season = $seasonStmt->get_result()->fetch_assoc();
$seasonStmt->close();
if (!$season || $season['stav'] !== 'priprava') {
    http_response_code(403);
    exit('Loga lze měnit pouze u sezóny ve stavu Příprava.');
}

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
