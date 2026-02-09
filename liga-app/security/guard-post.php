<?php
// Guard POST requests: validate CSRF if method=POST
require_once __DIR__.'/csrf.php';
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    csrf_validate_or_die();
}
