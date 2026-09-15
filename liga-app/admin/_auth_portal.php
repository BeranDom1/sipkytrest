<?php
// Administrační rozcestník je dostupný administrátorům a stat editorům.
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$portalRole = (string)($_SESSION['role'] ?? 'viewer');
$portalUserId = (int)($_SESSION['user_id'] ?? 0);

if (!in_array($portalRole, ['admin', 'stat_editor'], true)) {
    if ($portalUserId <= 0) {
        $next = $_SERVER['REQUEST_URI'] ?? '/liga-app/admin/index.php';
        header('Location: /liga-app/login.php?next='.urlencode($next));
        exit;
    }

    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    exit('403 – K administraci nemáte oprávnění.');
}
