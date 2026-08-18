<?php
$projectRoot = dirname(__DIR__);
$configPaths = [
    $projectRoot . '/config/db.local.php',
    $projectRoot . '/config/db.production.php',
];

$dbConfig = null;
foreach ($configPaths as $configPath) {
    if (is_file($configPath)) {
        $loadedConfig = require $configPath;
        if (is_array($loadedConfig)) {
            $dbConfig = $loadedConfig;
            break;
        }
    }
}

$requiredKeys = ['host', 'username', 'password', 'database'];
$configIsValid = is_array($dbConfig);

if ($configIsValid) {
    foreach ($requiredKeys as $requiredKey) {
        if (!array_key_exists($requiredKey, $dbConfig) || !is_string($dbConfig[$requiredKey])) {
            $configIsValid = false;
            break;
        }
    }
}

if (!$configIsValid) {
    http_response_code(500);
    error_log('Šipky Třešť: chybí platná soukromá konfigurace databáze.');
    exit('Databázové připojení není nakonfigurováno.');
}

mysqli_report(MYSQLI_REPORT_OFF);
$conn = @new mysqli(
    $dbConfig['host'],
    $dbConfig['username'],
    $dbConfig['password'],
    $dbConfig['database']
);

if ($conn->connect_errno) {
    http_response_code(500);
    error_log('Šipky Třešť: databázové připojení selhalo (' . $conn->connect_errno . ').');
    exit('Databázové připojení se nezdařilo.');
}

$conn->set_charset('utf8mb4');
