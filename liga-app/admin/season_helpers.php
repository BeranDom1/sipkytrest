<?php

function admin_season(mysqli $conn, int $rocnikId): ?array
{
    $stmt = $conn->prepare('SELECT id, nazev, locked, stav FROM rocniky WHERE id = ?');
    $stmt->bind_param('i', $rocnikId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}

function admin_season_is_editable(array $season): bool
{
    return ($season['stav'] ?? '') === 'priprava' && (int)$season['locked'] === 0;
}

/** Delete only a draft and its season-owned data, atomically. */
function admin_delete_draft_season(mysqli $conn, int $seasonId): void
{
    $run = static function (string $sql) use ($conn, $seasonId): mysqli_stmt {
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new RuntimeException('Smazání sezóny se nezdařilo. Žádné změny nebyly uloženy.');
        }
        $stmt->bind_param('i', $seasonId);
        if (!$stmt->execute()) {
            $stmt->close();
            throw new RuntimeException('Smazání sezóny se nezdařilo. Žádné změny nebyly uloženy.');
        }
        return $stmt;
    };

    if (!$conn->begin_transaction()) {
        throw new RuntimeException('Smazání sezóny se nepodařilo zahájit.');
    }
    try {
        $stmt = $run('SELECT id, stav, locked FROM rocniky WHERE id = ? FOR UPDATE');
        $season = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$season || !admin_season_is_editable($season)) {
            throw new RuntimeException('Smazat lze pouze odemčenou sezónu ve stavu Příprava.');
        }

        // Remove child records first; the legacy cup has a self-referencing FK.
        foreach ([
            'UPDATE prezidentsky_zapas z JOIN prezidentsky_turnaj t ON t.id = z.turnaj_id SET z.next_match_id = NULL WHERE t.rocnik_id = ?',
            'DELETE z FROM prezidentsky_zapas z JOIN prezidentsky_turnaj t ON t.id = z.turnaj_id WHERE t.rocnik_id = ?',
            'DELETE FROM prezidentsky_turnaj WHERE rocnik_id = ?',
            'DELETE z FROM turnaj_zapasy z JOIN turnaje t ON t.id = z.turnaj_id WHERE t.rocnik_id = ?',
            'DELETE h FROM turnaj_hraci h JOIN turnaje t ON t.id = h.turnaj_id WHERE t.rocnik_id = ?',
            'DELETE FROM turnaje WHERE rocnik_id = ?',
            'DELETE FROM zapasy WHERE rocnik_id = ?',
            'DELETE FROM hraci_v_sezone WHERE rocnik_id = ?',
            'DELETE FROM ligy_loga WHERE rocnik_id = ?',
            'DELETE FROM ligy_nazvy WHERE rocnik_id = ?',
            'DELETE FROM n_ligy WHERE rocnik_id = ?',
            // Legacy player identities may be referenced by other seasons' cups.
            'UPDATE hraci SET rocnik_id = NULL WHERE rocnik_id = ?',
            "DELETE FROM rocniky WHERE id = ? AND stav = 'priprava' AND locked = 0",
        ] as $sql) {
            $stmt = $run($sql);
            $stmt->close();
        }
        if (!$conn->commit()) {
            throw new RuntimeException('Smazání sezóny se nepodařilo uložit.');
        }
    } catch (Throwable $e) {
        $conn->rollback();
        throw $e;
    }
}

function admin_season_leagues(mysqli $conn, int $rocnikId): array
{
    $stmt = $conn->prepare(
        "SELECT l.id, l.cislo, l.poradi, COALESCE(ln.nazev, l.nazev) AS nazev
         FROM ligy l
         LEFT JOIN ligy_nazvy ln ON ln.liga_id = l.id AND ln.rocnik_id = ?
         WHERE ln.rocnik_id IS NOT NULL
            OR EXISTS (
                SELECT 1 FROM hraci_v_sezone hs
                WHERE hs.rocnik_id = ? AND hs.liga_id = l.id
            )
         ORDER BY l.poradi, l.cislo"
    );
    $stmt->bind_param('ii', $rocnikId, $rocnikId);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $rows;
}

function admin_round_robin(array $playerIds): array
{
    $ids = array_values(array_unique(array_map('intval', $playerIds)));
    if (count($ids) < 2) {
        return [];
    }

    if (count($ids) % 2 === 1) {
        $ids[] = 0;
    }

    $count = count($ids);
    $rounds = [];
    for ($round = 1; $round < $count; $round++) {
        $pairs = [];
        for ($i = 0; $i < $count / 2; $i++) {
            $a = (int)$ids[$i];
            $b = (int)$ids[$count - 1 - $i];
            if ($a === 0 || $b === 0) {
                $freePlayer = $a ?: $b;
                if ($freePlayer > 0) {
                    $pairs[] = ['a' => $freePlayer, 'b' => 0, 'bye' => true];
                }
                continue;
            }
            $pairs[] = [
                'a' => min($a, $b),
                'b' => max($a, $b),
                'bye' => false,
            ];
        }
        $rounds[$round] = $pairs;

        $fixed = array_shift($ids);
        $last = array_pop($ids);
        array_unshift($ids, $fixed);
        array_splice($ids, 1, 0, [$last]);
    }

    return $rounds;
}

function admin_match_has_result(array $match): bool
{
    return $match['skore1'] !== null
        || $match['skore2'] !== null
        || $match['datum'] !== null
        || $match['average_home'] !== null
        || $match['average_away'] !== null;
}

function admin_status_label(string $status): string
{
    return match ($status) {
        'aktivni' => 'Aktivní',
        'archivni' => 'Dokončená / archivní',
        default => 'Příprava',
    };
}
