<?php
// www/liga-app/save_stats.php

session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/common.php';

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
$newMatch = $matchId <= 0;
$newA = 0;
$newB = 0;
$newRound = 0;

if ($newMatch) {
  $matchSeason = (int)($_POST['rocnik_id'] ?? 0);
  $matchLeague = (int)($_POST['liga_id'] ?? 0);
  $newA = (int)($_POST['a'] ?? 0);
  $newB = (int)($_POST['b'] ?? 0);
  $newRound = (int)($_POST['kolo'] ?? 0);
  if ($newA > $newB) { $tmp = $newA; $newA = $newB; $newB = $tmp; }
  if ($matchSeason <= 0 || $matchLeague <= 0 || $newA <= 0 || $newB <= 0 || $newA === $newB || $newRound <= 0) {
    http_response_code(400);
    exit('Neplatné údaje nového zápasu.');
  }

  $playerStmt = $conn->prepare(
    'SELECT COUNT(DISTINCT hrac_id) AS players
       FROM hraci_v_sezone
      WHERE rocnik_id=? AND liga_id=? AND hrac_id IN (?,?)'
  );
  $playerStmt->bind_param('iiii', $matchSeason, $matchLeague, $newA, $newB);
  $playerStmt->execute();
  $playerCount = (int)($playerStmt->get_result()->fetch_assoc()['players'] ?? 0);
  $playerStmt->close();
  if ($playerCount !== 2) {
    http_response_code(400);
    exit('Hráči do této ligy a sezóny nepatří.');
  }
} else {
  $seasonStmt = $conn->prepare('SELECT rocnik_id, liga_id FROM zapasy WHERE id = ? LIMIT 1');
  $seasonStmt->bind_param('i', $matchId);
  $seasonStmt->execute();
  $match = $seasonStmt->get_result()->fetch_assoc();
  $matchSeason = (int)($match['rocnik_id'] ?? 0);
  $matchLeague = (int)($match['liga_id'] ?? 0);
  $seasonStmt->close();
}
if ($matchSeason <= 0 || !_season_can_edit_matches($conn, $matchSeason, (string)($_SESSION['role'] ?? ''))) {
  http_response_code(403);
  exit('Tato sezóna je uzavřená nebo nemáte právo ji upravovat.');
}

function validation_error(string $message, int $matchId): void {
  http_response_code(422);
  echo '<!doctype html><html lang="cs"><meta charset="utf-8"><title>Neplatný výsledek</title>';
  echo '<p>'.htmlspecialchars($message).'</p>';
  $returnUrl = $matchId > 0
    ? '/liga-app/zapas.php?id='.$matchId.'&amp;edit=1'
    : '/liga-app/rozpis.php?liga_id='.(int)($_POST['liga_id'] ?? 1);
  echo '<p><a href="'.$returnUrl.'">Vrátit se k rozpisu</a></p>';
  exit;
}

function post_scalar(string $name, int $matchId): string {
  $value = $_POST[$name] ?? '';
  if (!is_scalar($value)) validation_error('Neplatná hodnota formuláře.', $matchId);
  return trim((string)$value);
}

function optional_int(string $name, int $min, int $max, int $matchId): ?int {
  $raw = post_scalar($name, $matchId);
  if ($raw === '') return null;
  if (!preg_match('/^\d+$/', $raw)) validation_error("Pole $name musí být celé nezáporné číslo.", $matchId);
  $value = (int)$raw;
  if ($value < $min || $value > $max) validation_error("Pole $name musí být v rozsahu $min až $max.", $matchId);
  return $value;
}

function optional_float(string $name, float $min, float $max, int $matchId): ?float {
  $raw = str_replace(',', '.', post_scalar($name, $matchId));
  if ($raw === '') return null;
  if (!is_numeric($raw)) validation_error("Pole $name musí být číslo.", $matchId);
  $value = (float)$raw;
  if (!is_finite($value) || $value < $min || $value > $max) validation_error("Pole $name musí být v rozsahu $min až $max.", $matchId);
  return $value;
}

// --- DATUM: prázdné = NULL, podporuj i DD.MM.RRRR ---
$datum_raw = post_scalar('datum', $matchId);
$datum = null; // uložíme NULL, pokud nic nepřišlo

if ($datum_raw !== '') {
  if (preg_match('/^(\d{1,2})\.(\d{1,2})\.(\d{4})$/', $datum_raw, $m)) {
    // DD.MM.RRRR -> RRRR-MM-DD
    $datum = sprintf('%04d-%02d-%02d', (int)$m[3], (int)$m[2], (int)$m[1]);
  } elseif (preg_match('/^\d{4}-\d{2}-\d{2}$/', $datum_raw)) {
    // už je RRRR-MM-DD
    $datum = $datum_raw;
  } else {
    validation_error('Neplatné datum (očekávám RRRR-MM-DD nebo DD.MM.RRRR).', $matchId);
  }
  $parsedDate = DateTimeImmutable::createFromFormat('!Y-m-d', $datum);
  $dateErrors = DateTimeImmutable::getLastErrors();
  if (!$parsedDate || ($dateErrors !== false && ($dateErrors['warning_count'] > 0 || $dateErrors['error_count'] > 0)) || $parsedDate->format('Y-m-d') !== $datum) {
    validation_error('Zadané datum v kalendáři neexistuje.', $matchId);
  }
}

$s1 = optional_int('skore1', 0, 7, $matchId);
$s2 = optional_int('skore2', 0, 7, $matchId);
$winningScore = _league_winning_score($conn, $matchLeague, $matchSeason);
if ($s1 === null || $s2 === null || !_match_score_is_valid($s1, $s2, $winningScore)) {
  validation_error("Platný výsledek této ligy je $winningScore:0 až $winningScore:".($winningScore - 1).' nebo obráceně. Remíza není možná.', $matchId);
}

$avg1  = optional_float('average_home', 0, 180, $matchId);
$avg2  = optional_float('average_away', 0, 180, $matchId);
$hf1   = optional_int('high_finish_home', 0, 170, $matchId) ?? 0;
$hf2   = optional_int('high_finish_away', 0, 170, $matchId) ?? 0;
$c100h = optional_int('count_100p_home', 0, 99, $matchId) ?? 0;
$c100a = optional_int('count_100p_away', 0, 99, $matchId) ?? 0;
$c120h = optional_int('count_120p_home', 0, 99, $matchId) ?? 0;
$c120a = optional_int('count_120p_away', 0, 99, $matchId) ?? 0;
$c140h = optional_int('count_140p_home', 0, 99, $matchId) ?? 0;
$c140a = optional_int('count_140p_away', 0, 99, $matchId) ?? 0;
$c160h = optional_int('count_160p_home', 0, 99, $matchId) ?? 0;
$c160a = optional_int('count_160p_away', 0, 99, $matchId) ?? 0;
$c180h = optional_int('count_180_home', 0, 99, $matchId) ?? 0;
$c180a = optional_int('count_180_away', 0, 99, $matchId) ?? 0;

// 3) Transakce
$conn->begin_transaction();

if ($newMatch) {
  // Teprve po úplné validaci najdi nebo vytvoř záznam zápasu.
  $existingStmt = $conn->prepare(
    'SELECT id FROM zapasy
      WHERE rocnik_id=? AND liga_id=? AND hrac1_id=? AND hrac2_id=?
      LIMIT 1 FOR UPDATE'
  );
  $existingStmt->bind_param('iiii', $matchSeason, $matchLeague, $newA, $newB);
  $existingStmt->execute();
  $existing = $existingStmt->get_result()->fetch_assoc();
  $existingStmt->close();

  if ($existing) {
    $matchId = (int)$existing['id'];
  } else {
    $insertStmt = $conn->prepare(
      'INSERT INTO zapasy (rocnik_id, liga_id, hrac1_id, hrac2_id, kolo) VALUES (?,?,?,?,?)'
    );
    $insertStmt->bind_param('iiiii', $matchSeason, $matchLeague, $newA, $newB, $newRound);
    if (!$insertStmt->execute()) {
      $conn->rollback();
      exit('Zápas se nepodařilo vytvořit.');
    }
    $matchId = (int)$conn->insert_id;
    $insertStmt->close();
  }
}

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
