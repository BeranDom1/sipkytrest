<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../security/csrf.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Method Not Allowed');
}
if (!csrf_check($_POST['csrf'] ?? '')) {
    http_response_code(403);
    exit('CSRF ověření selhalo.');
}

@set_time_limit(0);
@ini_set('display_errors', '0');
while (ob_get_level() > 0) {
    ob_end_clean();
}

function backup_identifier(string $name): string
{
    return '`' . str_replace('`', '``', $name) . '`';
}

function backup_line(string $text = ''): void
{
    echo $text, "\n";
}

function backup_value(mysqli $conn, mixed $value): string
{
    if ($value === null) {
        return 'NULL';
    }
    return "'" . $conn->real_escape_string((string)$value) . "'";
}

function backup_show_create(mysqli $conn, string $objectType, string $name): ?string
{
    $result = $conn->query('SHOW CREATE ' . $objectType . ' ' . backup_identifier($name));
    if (!$result) {
        return null;
    }
    $row = $result->fetch_assoc();
    $result->free();
    foreach ($row as $column => $value) {
        if (str_starts_with((string)$column, 'Create ')) {
            return (string)$value;
        }
    }
    return null;
}

$databaseResult = $conn->query('SELECT DATABASE()');
$database = $databaseResult ? (string)$databaseResult->fetch_row()[0] : '';
if ($database === '') {
    http_response_code(500);
    exit('Databáze není vybrána.');
}

$filename = 'sipky-zaloha-' . date('Ymd-His') . '.sql';
header('Content-Type: application/sql; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

backup_line('-- Záloha databáze Šipky Třešť');
backup_line('-- Vytvořeno: ' . date(DATE_ATOM));
backup_line('-- Databáze: ' . $database);
backup_line();
backup_line('SET NAMES utf8mb4;');
backup_line('SET FOREIGN_KEY_CHECKS=0;');
backup_line("SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';");
backup_line('START TRANSACTION WITH CONSISTENT SNAPSHOT;');
backup_line();

$tables = [];
$views = [];
$objects = $conn->query('SHOW FULL TABLES');
if (!$objects) {
    backup_line('-- CHYBA: Nepodařilo se načíst seznam tabulek.');
    backup_line('ROLLBACK;');
    exit;
}
while ($row = $objects->fetch_row()) {
    if (($row[1] ?? '') === 'VIEW') {
        $views[] = (string)$row[0];
    } else {
        $tables[] = (string)$row[0];
    }
}
$objects->free();

foreach ($tables as $table) {
    $create = backup_show_create($conn, 'TABLE', $table);
    if ($create === null) {
        backup_line('-- CHYBA: Nelze načíst strukturu tabulky ' . $table);
        continue;
    }
    backup_line('DROP TABLE IF EXISTS ' . backup_identifier($table) . ';');
    backup_line($create . ';');

    $rows = $conn->query('SELECT * FROM ' . backup_identifier($table), MYSQLI_USE_RESULT);
    if (!$rows) {
        backup_line('-- CHYBA: Nelze načíst data tabulky ' . $table);
        backup_line();
        continue;
    }
    $fields = $rows->fetch_fields();
    $columnList = implode(', ', array_map(
        static fn(object $field): string => backup_identifier((string)$field->name),
        $fields
    ));
    while ($row = $rows->fetch_row()) {
        $values = array_map(static fn($value): string => backup_value($conn, $value), $row);
        backup_line('INSERT INTO ' . backup_identifier($table) . ' (' . $columnList . ') VALUES (' . implode(', ', $values) . ');');
    }
    $rows->free();
    backup_line();
    flush();
}

foreach ($views as $view) {
    $create = backup_show_create($conn, 'VIEW', $view);
    if ($create === null) {
        backup_line('-- UPOZORNĚNÍ: Nelze načíst definici pohledu ' . $view);
        continue;
    }
    backup_line('DROP VIEW IF EXISTS ' . backup_identifier($view) . ';');
    backup_line($create . ';');
    backup_line();
}

$triggers = $conn->query('SHOW TRIGGERS');
if ($triggers) {
    while ($trigger = $triggers->fetch_assoc()) {
        $name = (string)($trigger['Trigger'] ?? '');
        $create = $name !== '' ? backup_show_create($conn, 'TRIGGER', $name) : null;
        if ($create !== null) {
            backup_line('DELIMITER ;;');
            backup_line('DROP TRIGGER IF EXISTS ' . backup_identifier($name) . ';;');
            backup_line($create . ';;');
            backup_line('DELIMITER ;');
            backup_line();
        }
    }
    $triggers->free();
}

backup_line('COMMIT;');
backup_line('SET FOREIGN_KEY_CHECKS=1;');
exit;
