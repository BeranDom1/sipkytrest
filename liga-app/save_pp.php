<?php
// www/liga-app/save_pp.php
session_start();
require_once __DIR__.'/db.php';
require_once __DIR__.'/security/csrf.php';

function back(){ header('Location: /liga-app/prezidentsky-pohar.php'); exit; }

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !in_array($_SESSION['role'] ?? '', ['admin','stat_editor'], true)) back();
if (!hash_equals($_SESSION['csrf'] ?? '', (string)($_POST['csrf'] ?? ''))) { http_response_code(403); die('CSRF check failed'); }

$action  = $_POST['action'] ?? 'save';
$matchId = (int)($_POST['match_id'] ?? 0);
if (!$matchId) back();

$match = $conn->query("SELECT * FROM prezidentsky_zapas WHERE id={$matchId}")->fetch_assoc();
if (!$match) back();

// Pomůcky
$nextId  = (int)($match['next_match_id'] ?? 0);
$nextPos = (int)($match['next_pos'] ?? 0);
$code    = trim((string)($match['code'] ?? '')); // např. R1, P2…

if ($action === 'reset') {
  // 1) Zabezpečení: jestli už má „další kolo“ skóre, reset nedovolíme
  if ($nextId) {
    $n = $conn->query("SELECT skore1, skore2, vitez FROM prezidentsky_zapas WHERE id={$nextId}")->fetch_assoc();
    if ($n && ($n['skore1'] !== null || $n['skore2'] !== null || $n['vitez'] !== null)) {
      $_SESSION['pp_err'] = 'Nejde zrušit: v dalším kole už je zadaný výsledek. Nejdřív zruš ten další zápas.';
      back();
    }
  }

  // 2) Vynulovat tento zápas
  $stmt = $conn->prepare("UPDATE prezidentsky_zapas SET skore1=NULL, skore2=NULL, vitez=NULL WHERE id=?");
  $stmt->bind_param('i', $matchId);
  $stmt->execute();

  // 3) Vrátit „vítěz R1/P1…“ do dalšího kola (pokud existuje)
  if ($nextId && $nextPos) {
    $label = $code ? ('vítěz '.$code) : 'vítěz předchozího zápasu';
    if ($nextPos === 1) {
      $stmt2 = $conn->prepare("UPDATE prezidentsky_zapas SET hrac1_jmeno=?, hrac1_id=NULL WHERE id=?");
    } else {
      $stmt2 = $conn->prepare("UPDATE prezidentsky_zapas SET hrac2_jmeno=?, hrac2_id=NULL WHERE id=?");
    }
    $stmt2->bind_param('si', $label, $nextId);
    $stmt2->execute();
  }

  $_SESSION['pp_ok'] = 'Výsledek zrušen.';
  back();
}

// ====== Akce ULOŽIT (validace: max 5, součet 5–9 a přesně jeden hráč má 5) ======
$s1 = ($_POST['s1'] === '' ? null : (int)$_POST['s1']);
$s2 = ($_POST['s2'] === '' ? null : (int)$_POST['s2']);

if ($s1 === null && $s2 === null) {
  // povolíme prázdné – jen se vymažou hodnoty a vitez
  $stmt = $conn->prepare("UPDATE prezidentsky_zapas SET skore1=NULL, skore2=NULL, vitez=NULL WHERE id=?");
  $stmt->bind_param('i', $matchId);
  $stmt->execute();
  $_SESSION['pp_ok'] = 'Skóre vymazáno.';
  back();
}

$WIN = 5;
$minTotal = $WIN;
$maxTotal = $WIN*2 - 1;

$err = null;
if ($s1 === null || $s2 === null) {
  $err = 'Zadej obě skóre nebo nech prázdné.';
} elseif ($s1 > $WIN || $s2 > $WIN) {
  $err = "Maximální počet legů na hráče je {$WIN}.";
} elseif (($s1 + $s2) < $minTotal) {
  $err = "Součet legů musí být alespoň {$minTotal} (např. 5:0).";
} elseif (($s1 + $s2) > $maxTotal) {
  $err = "Součet legů nesmí být více než {$maxTotal} (max. 5:4).";
} elseif (!(($s1 === $WIN) xor ($s2 === $WIN))) {
  $err = "Přesně jeden hráč musí mít {$WIN} legů.";
}

if ($err) { $_SESSION['pp_err'] = $err; back(); }

$vitez = ($s1 > $s2) ? 1 : 2;

// Pokud změna vítěze zasahuje už „odehrané“ další kolo, blokni to
if ($nextId) {
  $n = $conn->query("SELECT skore1, skore2, vitez, hrac1_id, hrac2_id, hrac1_jmeno, hrac2_jmeno FROM prezidentsky_zapas WHERE id={$nextId}")->fetch_assoc();
  if ($n && ($n['skore1'] !== null || $n['skore2'] !== null || $n['vitez'] !== null)) {
    // Další kolo už má výsledek – změnu nepovolíme (zabráníme rozporu)
    $_SESSION['pp_err'] = 'Nelze změnit vítěze: v dalším kole už je zadaný výsledek. Nejdřív zruš další zápas.';
    back();
  }
}

// Ulož skóre a vítěze
$stmt = $conn->prepare("UPDATE prezidentsky_zapas SET skore1=?, skore2=?, vitez=? WHERE id=?");
$stmt->bind_param('iiii', $s1, $s2, $vitez, $matchId);
$stmt->execute();

// Vypočti údaje vítěze
$winnerName = null; $winnerId = null;
if ($vitez == 1) {
  $winnerId   = $match['hrac1_id'] ? (int)$match['hrac1_id'] : null;
  if (!empty($match['hrac1_jmeno'])) $winnerName = $match['hrac1_jmeno'];
} else {
  $winnerId   = $match['hrac2_id'] ? (int)$match['hrac2_id'] : null;
  if (!empty($match['hrac2_jmeno'])) $winnerName = $match['hrac2_jmeno'];
}
// Dotažení jména z tabulky hraci, když je jen id
if (!$winnerName && $winnerId) {
  $stmtN = $conn->prepare("SELECT jmeno FROM hraci WHERE id=?");
  $stmtN->bind_param('i', $winnerId); $stmtN->execute();
  $winnerName = ($stmtN->get_result()->fetch_column()) ?: null;
}

// Propagace do dalšího zápasu (pokud existuje)
if ($nextId && $nextPos) {
  if ($nextPos === 1) {
    $stmt2 = $conn->prepare("UPDATE prezidentsky_zapas SET hrac1_jmeno=?, hrac1_id=? WHERE id=?");
  } else {
    $stmt2 = $conn->prepare("UPDATE prezidentsky_zapas SET hrac2_jmeno=?, hrac2_id=? WHERE id=?");
  }
  $stmt2->bind_param('sii', $winnerName, $winnerId, $nextId);
  $stmt2->execute();
}

$_SESSION['pp_ok'] = 'Výsledek uložen.';
back();
