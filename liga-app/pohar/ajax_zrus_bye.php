<?php
require __DIR__ . '/../db.php';
session_start();

if (!in_array($_SESSION['role'] ?? '', ['admin', 'stat_editor'], true)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Zakázáno']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$zapas_id = (int)($data['zapas_id'] ?? 0);

if ($zapas_id <= 0) {
    echo json_encode(['ok' => false, 'error' => 'Neplatné ID']);
    exit;
}

$conn->begin_transaction();

try {
    // načti zápas
    $stmt = $conn->prepare("
        SELECT next_match_id, next_slot
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

    // smaž BYE + vítěze + skóre
    $stmt = $conn->prepare("
        UPDATE turnaj_zapasy
        SET hrac1_id = NULL,
            hrac2_id = NULL,
            vitez_id = NULL,
            skore1 = NULL,
            skore2 = NULL
        WHERE id = ?
    ");
    $stmt->bind_param("i", $zapas_id);
    $stmt->execute();

    // zruš postup do dalšího kola
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

    $conn->commit();
    echo json_encode(['ok' => true]);

} catch (Throwable $e) {
    $conn->rollback();
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
