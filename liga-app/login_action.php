<?php
$BASE_URL = '/liga-app';

require_once __DIR__.'/db.php';
require_once __DIR__.'/security/csrf.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    $isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => $BASE_URL,
        'secure'   => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function safe_next(string $url, string $default) : string {
    // dovol jen interní cesty pod /liga-app
    $u = parse_url($url);
    if (!$u || !empty($u['scheme']) || !empty($u['host'])) return $default;
    if (empty($u['path']) || strpos($u['path'], '/liga-app') !== 0) return $default;
    return $url;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_check($_POST['csrf'] ?? '')) {
    header('Location: '.$BASE_URL.'/login.php?error=Neplatný+požadavek&next='.urlencode($BASE_URL.'/admin/index.php'));
    exit;
}

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';
$next     = safe_next($_POST['next'] ?? '', $BASE_URL.'/admin/index.php');

if ($username === '' || $password === '') {
    header('Location: '.$BASE_URL.'/login.php?error=Zadejte+jméno+a+heslo&next='.urlencode($next));
    exit;
}

$stmt = $conn->prepare("SELECT id, username, password, role FROM uzivatele WHERE username = ? LIMIT 1");
$stmt->bind_param('s', $username);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($user && password_verify($password, $user['password'])) {
    $_SESSION['user_id']  = (int)$user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['role']     = $user['role'];                 // 'user' | 'stat_editor' | 'admin'
    $_SESSION['is_admin'] = ($user['role'] === 'admin');   // kvůli starším částem kódu

    header('Location: '.$next);
    exit;
}

// 2) Fallback: tabulka admins (pokud chceš ponechat staré „admin“ účty)
$stmt = $conn->prepare("SELECT id, username, password_hash FROM admins WHERE username = ? LIMIT 1");
$stmt->bind_param('s', $username);
$stmt->execute();
$adm = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($adm && password_verify($password, $adm['password_hash'])) {
    $_SESSION['user_id']  = (int)$adm['id'];
    $_SESSION['username'] = $adm['username'];
    $_SESSION['role']     = 'admin';
    $_SESSION['is_admin'] = true;

   $next = $_POST['next'] ?? $BASE_URL.'/';
$path = parse_url($next, PHP_URL_PATH) ?? '/';
if (strpos($path, $BASE_URL) !== 0) {
    $next = $BASE_URL.'/'; // jistič proti open redirectu
}

// ... po nastavení $_SESSION['user_id'], $_SESSION['username'], $_SESSION['role'], atd.
header('Location: ' . $next);
exit;
}

// Neúspěch
header('Location: '.$BASE_URL.'/login.php?error=Nesprávné+jméno+nebo+heslo&next='.urlencode($next));
exit;
