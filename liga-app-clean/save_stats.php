<?php
// www/liga-app/save_stats.php

session_start();
require_once __DIR__ . '/db.php';

// 1) Povolit jen POST a roli admin/stat_editor
if (
  $_SERVER['REQUEST_METHOD'] !== 'POST' ||
  !in_array($_SESSION['role'] ?? '', ['admin','stat_editor'], true)
) {
  header('Location: /liga-app/index.php');
  exit;
}

// 1b) CSRF (token je v session a v <input name="csrf">)
$csrf = (string)($_POST['csrf'] ?? '');
if (!hash_equals($_SESSION['csrf'] ?? '', $csrf)) {
  http_response_code(403);
  die('CSRF check failed');
}

// 2) Načíst ID zápasu a hodnoty
$matchId = (int)($_POST['match_id'] ?? 0);

// --- DATUM: prázdné = NULL, podporuj i DD.MM.RRRR ---
$datum_raw = trim((string)($_POST['datum'] ?? ''));
$datum = null; // uložíme NULL, pokud nic nepřišlo

if ($datum_raw !== '') {
  if (preg_match('/^(\d{1,2})\.(\d{1,2})\.(\d{4})$/', $datum_raw, $m)) {
    // DD.MM.RRRR -> RRRR-MM-DD
    $datum = sprintf('%04d-%02d-%02d', (int)$m[3], (int)$m[2], (int)$m[1]);
  } elseif (preg_match('/^\d{4}-\d{2}-\d{2}$/', $datum_raw)) {
    // už je RRRR-MM-DD
    $datum = $datum_raw;
  } else {
    http_response_code(400);
    die('Neplatné datum (očekávám RRRR-MM-DD nebo DD.MM.RRRR)');
  }
}

$s1      = (int)($_POST['skore1'] ?? 0);
$s2      = (int)($_POST['skore2'] ?? 0);
$avg1    = (float)($_POST['average_home']   ?? 0);
$avg2    = (float)($_POST['average_away']   ?? 0);
$hf1     = (int)($_POST['high_finish_home'] ?? 0);
$hf2     = (int)($_POST['high_finish_away'] ?? 0);
$c100h   = (int)($_POST['count_100p_home']  ?? 0);
$c100a   = (int)($_POST['count_100p_away']  ?? 0);
$c120h   = (int)($_POST['count_120p_home']  ?? 0);
$c120a   = (int)($_POST['count_120p_away']  ?? 0);
$c140h   = (int)($_POST['count_140p_home']  ?? 0);
$c140a   = (int)($_POST['count_140p_away']  ?? 0);
$c160h   = (int)($_POST['count_160p_home']  ?? 0);
$c160a   = (int)($_POST['count_160p_away']  ?? 0);
$c180h   = (int)($_POST['count_180_home']   ?? 0);
$c180a   = (int)($_POST['count_180_away']   ?? 0);

// 3) Transakce
$conn->begin_transaction();

// 4) UPDATE vč. datum
$sql = "
  UPDATE zapasy SET
    datum = ?,
    skore1 = ?, skore2 = ?,
    average_home      = ?, average_away      = ?,
    high_finish_home  = ?, high_finish_away  = ?,
    count_100p_home   = ?, count_100p_away   = ?,
    count_120p_home   = ?, count_120p_away   = ?,
    count_140p_home   = ?, count_140p_away   = ?,
    count_160p_home   = ?, count_160p_away   = ?,
    count_180_home    = ?, count_180_away    = ?
  WHERE id = ?
";
$stmt = $conn->prepare($sql);
if (!$stmt) {
  $conn->rollback();
  die('Chyba přípravy dotazu: ' . $conn->error);
}

// Typy: s + ii + dd + (12×i) + i  => celkem 18 parametrů
$types = 'siidd' . str_repeat('i', 13);

$stmt->bind_param(
  $types,
  $datum,
  $s1, $s2,
  $avg1, $avg2,
  $hf1, $hf2,
  $c100h, $c100a,
  $c120h, $c120a,
  $c140h, $c140a,
  $c160h, $c160a,
  $c180h, $c180a,
  $matchId
);

// 5) Provedeme UPDATE
if (!$stmt->execute()) {
  $conn->rollback();
  die('Chyba při ukládání: ' . $stmt->error);
}
$stmt->close();
$conn->commit();

// 6) Zpět na detail
header("Location: /liga-app/zapas.php?id={$matchId}&saved=1");
exit;
