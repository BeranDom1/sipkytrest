<?php
// liga-app/admin/_auth.php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// zjištění role a toho, zda je uživatel přihlášen
$role    = $_SESSION['role']    ?? 'viewer';
$userId  = $_SESSION['user_id'] ?? null;

// dopočítej BASE URL ve tvaru "/liga-app" (admin je vždy o úroveň níž)
$script = $_SERVER['SCRIPT_NAME'] ?? ($_SERVER['PHP_SELF'] ?? '/');
$base   = rtrim(dirname(dirname($script)), '/');
if ($base === '') { $base = '/'; }

// když není admin → řeš
if ($role !== 'admin') {
    // nepřihlášený → pošli na login s návratem zpět
    if (empty($userId)) {
        $next = $_SERVER['REQUEST_URI'] ?? ($base . '/');
        header('Location: ' . $base . '/login.php?next=' . urlencode($next));
        exit;
    }

    // přihlášený, ale bez práv → 403
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    echo '403 – Přístup jen pro správce.';
    exit;
}

// jinak: admin → pokračuj
