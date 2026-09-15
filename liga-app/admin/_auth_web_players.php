<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$webPlayersRole = (string)($_SESSION['role'] ?? 'viewer');
$webPlayersUserId = (int)($_SESSION['user_id'] ?? 0);
$canManageWebPlayers = in_array($webPlayersRole, ['admin', 'stat_editor'], true);

if (!$canManageWebPlayers) {
    if ($webPlayersUserId <= 0) {
        $next = $_SERVER['REQUEST_URI'] ?? '/liga-app/admin/hraci-web.php';
        header('Location: /liga-app/login.php?next='.urlencode($next));
        exit;
    }

    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    exit('403 – Ke správě hráčů na webu nemáte oprávnění.');
}

