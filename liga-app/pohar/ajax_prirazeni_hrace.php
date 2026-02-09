<?php
require __DIR__ . '/../db.php';
session_start();

if (!in_array($_SESSION['role'] ?? '', ['admin','stat_editor'], true)) {
    http_response_code(403);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

$zapas_id = (int)($data['zapas_id'] ?? 0);
$slotKey  = ($data['slot'] ?? '') === 'hrac2_id' ? 'hrac2_id' : 'hrac1_id';

/**
 * hrac_id:
 *  NULL = nevyplněno
 *  0    = Volný los
 * >0    = hráč
 */
$hrac_id = null;

if (array_key_exists('hrac_id', $data)) {
    if ($data['hrac_id'] === 'BYE1' || $data['hrac_id'] === 'BYE2') {
        $hrac_id = 0; // oba = Volný los
    } elseif ($data['hrac_id'] !== '' && $data['hrac_id'] !== null) {
        $hrac_id = (int)$data['hrac_id'];
    }
}

if ($zapas_id <= 0) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Neplatné ID zápasu']);
    exit;
}

$conn->begin_transaction();

try {
    // zamknout zápas
    $stmt = $conn->prepare("
        SELECT hrac1_id, hrac2_id, vitez_id, next_match_id, next_slot
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

    if ($z['vitez_id'] !== null) {
        throw new Exception('Zápas je již odehrán');
    }

    // zapiš hráče / bye / null
    $stmt = $conn->prepare("
        UPDATE turnaj_zapasy
        SET {$slotKey} = ?
        WHERE id = ?
    ");
    $stmt->bind_param("ii", $hrac_id, $zapas_id);
    $stmt->execute();

    // nová kombinace
    $h1 = ($slotKey === 'hrac1_id') ? $hrac_id : $z['hrac1_id'];
    $h2 = ($slotKey === 'hrac2_id') ? $hrac_id : $z['hrac2_id'];

    /**
     * AUTO POSTUP POUZE PŘI BYE
     */
    $jeBye =
        ($h1 > 0 && $h2 === 0) ||
        ($h2 > 0 && $h1 === 0);

    if ($jeBye) {
        $vitez_id = ($h1 > 0) ? $h1 : $h2;

        // ulož vítěze (bez skóre)
        $stmt = $conn->prepare("
            UPDATE turnaj_zapasy
            SET vitez_id = ?, skore1 = NULL, skore2 = NULL
            WHERE id = ?
        ");
        $stmt->bind_param("ii", $vitez_id, $zapas_id);
        $stmt->execute();

        // propagace
        if ($z['next_match_id'] && $z['next_slot']) {
            $slotCol = $z['next_slot'] === 'hrac1'
                ? 'hrac1_id'
                : 'hrac2_id';

            $stmt = $conn->prepare("
                UPDATE turnaj_zapasy
                SET {$slotCol} = ?
                WHERE id = ?
            ");
            $stmt->bind_param("ii", $vitez_id, $z['next_match_id']);
            $stmt->execute();
        }
    }

    $conn->commit();
    echo json_encode(['ok' => true]);

} catch (Throwable $e) {
    $conn->rollback();
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
