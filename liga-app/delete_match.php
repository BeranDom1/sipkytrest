<?php
session_start();

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/common.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('405 – Method Not Allowed');
}

if (!in_array($_SESSION['role'] ?? '', ['admin', 'stat_editor'], true)) {
    http_response_code(403);
    exit('Nemáte oprávnění mazat výsledky.');
}

$csrf = (string)($_POST['csrf'] ?? '');
if (!hash_equals((string)($_SESSION['csrf'] ?? ''), $csrf)) {
    http_response_code(403);
    exit('CSRF check failed');
}

$matchId = (int)($_POST['match_id'] ?? 0);
if ($matchId <= 0) {
    http_response_code(400);
    exit('Neplatné ID zápasu.');
}

$stmt = $conn->prepare('SELECT rocnik_id, liga_id FROM zapasy WHERE id=? LIMIT 1');
$stmt->bind_param('i', $matchId);
$stmt->execute();
$match = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$match) {
    http_response_code(404);
    exit('Zápas nebyl nalezen.');
}

$seasonId = (int)$match['rocnik_id'];
$leagueId = (int)$match['liga_id'];
if (!_season_can_edit_matches($conn, $seasonId, (string)($_SESSION['role'] ?? ''))) {
    http_response_code(403);
    exit('Tato sezóna je uzavřená nebo nemáte právo ji upravovat.');
}

$stmt = $conn->prepare('DELETE FROM zapasy WHERE id=? AND rocnik_id=? AND liga_id=?');
$stmt->bind_param('iii', $matchId, $seasonId, $leagueId);
$stmt->execute();
$deleted = $stmt->affected_rows;
$stmt->close();

if ($deleted !== 1) {
    http_response_code(409);
    exit('Výsledek se nepodařilo smazat.');
}

header('Location: /liga-app/rozpis.php?liga_id='.$leagueId.'&deleted=1');
exit;
