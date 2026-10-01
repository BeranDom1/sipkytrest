<?php
session_start();
require_once __DIR__ . '/../security/csrf.php';
header('Content-Type: application/json; charset=utf-8');

if (!in_array($_SESSION['role'] ?? '', ['admin','stat_editor'], true)) {
    http_response_code(403);
    exit;
}

csrf_validate_ajax_or_die();

require __DIR__.'/../db.php';

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
        SELECT z.vitez_id, z.next_match_id, z.next_slot, t.stav
        FROM turnaj_zapasy z
        JOIN turnaje t ON t.id=z.turnaj_id
        WHERE z.id = ?
        FOR UPDATE
    ");
    $stmt->bind_param("i", $zapas_id);
    $stmt->execute();
    $z = $stmt->get_result()->fetch_assoc();

    if (!$z) {
        throw new Exception('Zápas nenalezen');
    }

    if (($z['stav'] ?? '') !== 'probiha') {
        throw new Exception('Výsledek lze zrušit pouze u spuštěného turnaje.');
    }

    // pokud už existuje navazující zápas s výsledkem → zákaz
    if ($z['next_match_id']) {
        $stmt = $conn->prepare("
            SELECT skore1, skore2, vitez_id
            FROM turnaj_zapasy
            WHERE id = ?
            FOR UPDATE
        ");
        $stmt->bind_param("i", $z['next_match_id']);
        $stmt->execute();
        $nav = $stmt->get_result()->fetch_assoc();

        if ($nav && ($nav['skore1'] !== null || $nav['skore2'] !== null || $nav['vitez_id'] !== null)) {
            throw new Exception('Nelze zrušit – navazující zápas už je odehrán');
        }
    }

    // odeber postup vítěze z dalšího kola
    if ($z['next_match_id'] && $z['next_slot']) {
        $slotCol = $z['next_slot'] === 'hrac1' ? 'hrac1_id' : 'hrac2_id';

        $stmt = $conn->prepare("
            UPDATE turnaj_zapasy
            SET {$slotCol} = NULL
            WHERE id = ? AND {$slotCol} = ?
        ");
        $stmt->bind_param("ii", $z['next_match_id'], $z['vitez_id']);
        $stmt->execute();
    }

    // Zruší se pouze výsledek; obsazení zápasu musí zůstat zachované.
    $stmt = $conn->prepare("
        UPDATE turnaj_zapasy
        SET
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
