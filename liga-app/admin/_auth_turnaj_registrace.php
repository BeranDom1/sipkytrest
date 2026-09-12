<?php
// Správu online registrací smí administrátor a pověřený stat editor Jakub Šebesta.
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$registrationRole = (string)($_SESSION['role'] ?? 'viewer');
$registrationUserId = (int)($_SESSION['user_id'] ?? 0);
$registrationUsername = (string)($_SESSION['username'] ?? '');
$canManageTournamentRegistrations = $registrationRole === 'admin'
    || ($registrationRole === 'stat_editor'
        && hash_equals('sebesta', $registrationUsername));

if (!$canManageTournamentRegistrations) {
    if ($registrationUserId <= 0) {
        $next = $_SERVER['REQUEST_URI'] ?? '/liga-app/admin/registrace-turnaje.php';
        header('Location: /liga-app/login.php?next='.urlencode($next));
        exit;
    }

    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    exit('403 – Ke správě registrací nemáte oprávnění.');
}
