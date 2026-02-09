<?php
// CSRF helper – inicializace tokenu do session
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (empty($_SESSION['csrf'])) {
    // Oprava pro starší verze PHP (před 7.0)
    if (function_exists('random_bytes')) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    } elseif (function_exists('openssl_random_pseudo_bytes')) {
        $_SESSION['csrf'] = bin2hex(openssl_random_pseudo_bytes(32));
    } else {
        // Poslední záchrana pro velmi staré konfigurace
        $_SESSION['csrf'] = bin2hex(md5(uniqid(mt_rand(), true)));
    }
}

/** Vypíše <input type="hidden" …> s tokenem */
function csrf_input() {
    // Oprava pro PHP 5.4 (náhrada operátoru ??)
    $token = htmlspecialchars(isset($_SESSION['csrf']) ? $_SESSION['csrf'] : '', ENT_QUOTES, 'UTF-8');
    echo '<input type="hidden" name="csrf" value="' . $token . '">';
}

/** Validace a případné ukončení */
function csrf_validate_or_die() {
    // Kontrola existence tokenu v POSTu i v SESSION
    $tokenFromPost = isset($_POST['csrf']) ? $_POST['csrf'] : null;
    $tokenFromSession = isset($_SESSION['csrf']) ? $_SESSION['csrf'] : null;

    $ok = ($tokenFromPost !== null && $tokenFromSession !== null) && hash_equals($tokenFromSession, $tokenFromPost);
    
    if (!$ok) {
        http_response_code(403);
        exit('CSRF token mismatch - overeni selhalo. Zkuste obnovit stranku.');
    }
}

/** Funkce pro PHP < 5.6 (pokud by chyběla vestavěná hash_equals) */
if (!function_exists('hash_equals')) {
    function hash_equals($str1, $str2) {
        if (strlen($str1) !== strlen($str2)) return false;
        $res = $str1 ^ $str2;
        $ret = 0;
        for ($i = strlen($res) - 1; $i >= 0; $i--) {
            $ret |= ord($res[$i]);
        }
        return $ret === 0;
    }
}

/* --- WRAPPERY pro kompatibilitu s tvým zbytkem kódu --- */

/** Vrať CSRF token jako string */
if (!function_exists('csrf_token')) {
    function csrf_token() {
        return isset($_SESSION['csrf']) ? $_SESSION['csrf'] : '';
    }
}

/** Ověř předaný token a vrať bool (true = platný) */
if (!function_exists('csrf_check')) {
    function csrf_check($token) {
        $tokenFromSession = isset($_SESSION['csrf']) ? $_SESSION['csrf'] : null;
        return ($tokenFromSession !== null && is_string($token) && hash_equals($tokenFromSession, $token));
    }
}