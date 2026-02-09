<?php
// /liga-app/zapas_create.php
// Vytvoří zápas (pokud neexistuje) a přesměruje na jeho editaci.

if (session_status() !== PHP_SESSION_ACTIVE) session_start();

// BASE_URL podle umístění skriptu (např. "/liga-app")
$BASE_URL = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/'), '/');
if ($BASE_URL === '') $BASE_URL = '/';

require __DIR__.'/db.php';
require __DIR__.'/security/csrf.php';

// ---- práva: editor nebo admin ----
$role    = $_SESSION['role'] ?? 'viewer';
$canEdit = in_array($role, ['admin','stat_editor'], true);
if (!$canEdit) {
    http_response_code(403);
    exit('403 – Nemáte oprávnění zadávat výsledky.');
}

// ---- jen POST ----
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('405 – Method Not Allowed');
}

// ---- CSRF ----
$csrfToken = (string)($_POST['csrf'] ?? '');
$csrfOk = false;
if (function_exists('csrf_verify'))       $csrfOk = csrf_verify($csrfToken);
elseif (function_exists('csrf_validate')) $csrfOk = csrf_validate($csrfToken);
elseif (function_exists('csrf_check'))    $csrfOk = csrf_check($csrfToken);
else {
    $sessionToken = $_SESSION['csrf'] ?? ($_SESSION['csrf_token'] ?? '');
    $csrfOk = is_string($sessionToken) && hash_equals($sessionToken, $csrfToken);
}
if (!$csrfOk) {
    http_response_code(400);
    exit('CSRF token neplatný.');
}

// ---- vstupy ----
$rocnik_id = (int)($_POST['rocnik_id'] ?? 0);
$liga_id   = (int)($_POST['liga_id']   ?? 0);
$a         = (int)($_POST['a'] ?? 0);
$b         = (int)($_POST['b'] ?? 0);

if ($rocnik_id<=0 || $liga_id<=0 || $a<=0 || $b<=0 || $a === $b) {
    http_response_code(400);
    exit('Chybné vstupy.');
}

// kanonické pořadí hráčů (menší id první – stejné jako v rozpisu)
if ($a > $b) { $t=$a; $a=$b; $b=$t; }

// ---- zkus najít existující zápas ----
$st = $conn->prepare(
    "SELECT id FROM zapasy
     WHERE rocnik_id=? AND liga_id=? AND hrac1_id=? AND hrac2_id=? LIMIT 1"
);
$st->bind_param('iiii', $rocnik_id, $liga_id, $a, $b);
$st->execute();
$ex = $st->get_result()->fetch_assoc();
$st->close();

if ($ex) {
    $matchId = (int)$ex['id'];
} else {
    // založit nový řádek (skóre NULL; lze prefiltrovat na 0:0 – viz poznámka níže)
    $st = $conn->prepare(
        "INSERT INTO zapasy (rocnik_id, liga_id, hrac1_id, hrac2_id)
         VALUES (?,?,?,?)"
    );
    $st->bind_param('iiii', $rocnik_id, $liga_id, $a, $b);
    if (!$st->execute()) {
        http_response_code(500);
        exit('Nelze založit zápas: '.$st->error);
    }
    // DŮLEŽITÉ: ID ber z připojení, ne ze statementu
    $matchId = (int)$conn->insert_id;
    $st->close();
}

// ---- přesměruj rovnou do editace ----
header('Location: '.$BASE_URL.'/zapas.php?id='.$matchId.'&edit=1&liga='.$liga_id.'&rocnik='.$rocnik_id);
exit;
