<?php
require __DIR__ . '/../db.php';
require __DIR__ . '/pohar_funkce.php';
session_start();

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'stat_editor'], true)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Zakázáno']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

$zapas_id = (int)($data['zapas_id'] ?? 0);
$skore1   = (int)($data['skore1'] ?? -1);
$skore2   = (int)($data['skore2'] ?? -1);

if ($zapas_id <= 0 || $skore1 < 0 || $skore2 < 0) {
    echo json_encode(['ok' => false, 'error' => 'Neplatná data']);
    exit;
}

try {
    ulozSkoreAZpropagujViteze($conn, $zapas_id, $skore1, $skore2);
    echo json_encode(['ok' => true]);
} catch (Throwable $e) {
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
