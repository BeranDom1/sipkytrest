<?php
require __DIR__.'/../db.php';
session_start();

if (!in_array($_SESSION['role'] ?? '', ['admin','stat_editor'], true)) {
    http_response_code(403);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$zapas_id = (int)($data['zapas_id'] ?? 0);

if ($zapas_id <= 0) {
    http_response_code(400);
    exit;
}

$conn->begin_transaction();

try {
    // načti zápas
    $stmt = $conn->prepare("
        SELECT vitez_id, next_match_id, next_slot
        FROM turnaj_zapasy
        WHERE id = ?
        FOR UPDATE
    ");
    $stmt->bind_param("i", $zapas_id);
    $stmt->execute();
    $z = $stmt->get_result()->fetch_assoc();

    if (!$z) {
        throw new Exception('Zápas nenalezen');
    }

    // pokud už existuje navazující zápas s výsledkem → zákaz
    if ($z['next_match_id']) {
        $stmt = $conn->prepare("
            SELECT vitez_id
            FROM turnaj_zapasy
            WHERE id = ?
        ");
        $stmt->bind_param("i", $z['next_match_id']);
        $stmt->execute();
        $nav = $stmt->get_result()->fetch_assoc();

        if ($nav && $nav['vitez_id'] !== null) {
            throw new Exception('Nelze zrušit – navazující zápas už je odehrán');
        }
    }

    // odeber postup vítěze z dalšího kola
    if ($z['next_match_id'] && $z['next_slot']) {
        $slotCol = $z['next_slot'] === 'hrac1' ? 'hrac1_id' : 'hrac2_id';

        $stmt = $conn->prepare("
            UPDATE turnaj_zapasy
            SET {$slotCol} = NULL
            WHERE id = ?
        ");
        $stmt->bind_param("i", $z['next_match_id']);
        $stmt->execute();
    }

    // reset zápasu
    $stmt = $conn->prepare("
        UPDATE turnaj_zapasy
        SET
            hrac1_id = NULL,
            hrac2_id = NULL,
            skore1 = NULL,
            skore2 = NULL,
            vitez_id = NULL
        WHERE id = ?
    ");
    $stmt->bind_param("i", $zapas_id);
    $stmt->execute();

    $conn->commit();
    echo json_encode(['ok' => true]);

} catch (Throwable $e) {
    $conn->rollback();
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
