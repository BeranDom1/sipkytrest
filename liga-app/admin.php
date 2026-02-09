<?php
// admin/_auth.php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

// Sem si dosaď, jak si u sebe značkuješ admina.
// Např. po tvém loginu: $_SESSION['is_admin'] = true;
if (empty($_SESSION['is_admin'])) {
    header('Location: /liga-app/login.php');
    exit;
}
