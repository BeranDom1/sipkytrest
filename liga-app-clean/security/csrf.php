<?php
// CSRF helper – initialize token into session; server-side validation helper.
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

/** Vypíše <input type="hidden" …> s tokenem (původní funkce) */
function csrf_input() {
    $token = htmlspecialchars($_SESSION['csrf'] ?? '', ENT_QUOTES, 'UTF-8');
    echo '<input type="hidden" name="csrf" value="'.$token.'">';
}

/** Validace a případné ukončení (původní funkce) */
function csrf_validate_or_die() {
    $ok = isset($_POST['csrf'], $_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $_POST['csrf']);
    if (!$ok) {
        http_response_code(403);
        exit('CSRF token mismatch');
    }
}

/* --- WRAPPERY (shim) pro kompatibilitu s voláním csrf_token() a csrf_check() --- */

/** Vrať CSRF token jako string (pro skryté pole ve formuláři) */
if (!function_exists('csrf_token')) {
    function csrf_token(): string {
        return $_SESSION['csrf'] ?? '';
    }
}

/** Ověř předaný token a vrať bool (true = platný) */
if (!function_exists('csrf_check')) {
    function csrf_check($token): bool {
        return isset($_SESSION['csrf']) && is_string($token) && hash_equals($_SESSION['csrf'], $token);
    }
}
