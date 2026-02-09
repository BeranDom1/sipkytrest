<?php
// set_season.php – přepnutí ročníku + návrat zpět

$BASE_URL = '/liga-app'; // pokud máš app jinde, uprav!

// stejné nastavení session jako v headeru
$isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => $BASE_URL,
        'secure'   => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

require __DIR__.'/db.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Method Not Allowed');
}

// CSRF kontrola (token je ve formuláři jako "csrf")
$csrfForm = $_POST['csrf'] ?? '';
$csrfSess = $_SESSION['csrf'] ?? '';
if (!is_string($csrfForm) || $csrfForm === '' || $csrfSess === '' || !hash_equals($csrfSess, $csrfForm)) {
    http_response_code(403);
    exit('CSRF check failed');
}

// načti a ověř ročník
$rocnik_id = (int)($_POST['rocnik_id'] ?? 0);
$ok_id = 0;
if ($rocnik_id > 0 && ($st = $conn->prepare('SELECT id FROM rocniky WHERE id=?'))) {
    $st->bind_param('i', $rocnik_id);
    $st->execute();
    $st->bind_result($found);
    if ($st->fetch()) $ok_id = (int)$found;
    $st->close();
}
// fallback: vezmi nejvyšší dostupný
if ($ok_id <= 0) {
    $res = $conn->query('SELECT MAX(id) AS mx FROM rocniky');
    $row = $res ? $res->fetch_assoc() : null;
    $ok_id = (int)($row['mx'] ?? 1);
}

$_SESSION['rocnik_id'] = $ok_id;

// kam se vrátit
$return_to = $_POST['return_to'] ?? ($BASE_URL.'/index.php');

// bezpečně povol jen cesty v rámci aplikace
$u = parse_url($return_to);
$path = $u['path'] ?? $return_to;
$query = isset($u['query']) ? ('?'.$u['query']) : '';
if (strpos($path, $BASE_URL) !== 0) {
    $path = $BASE_URL.'/index.php';
    $query = '';
}

header('Location: '.$path.$query, true, 303);
exit;
