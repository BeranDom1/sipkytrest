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

function admin_active_season_edit_is_enabled(array $season): bool
{
    $seasonId = (int)($season['id'] ?? 0);
    $enabled = $_SESSION['admin_active_season_edits'] ?? [];

    return $seasonId > 0
        && ($season['stav'] ?? '') === 'aktivni'
        && (int)($season['locked'] ?? 0) === 0
        && is_array($enabled)
        && !empty($enabled[$seasonId]);
}

function admin_season_can_manage_competition(array $season): bool
{
    return admin_season_is_editable($season) || admin_active_season_edit_is_enabled($season);
}

function admin_enable_active_season_edit(int $seasonId): void
{
    if (!isset($_SESSION['admin_active_season_edits']) || !is_array($_SESSION['admin_active_season_edits'])) {
        $_SESSION['admin_active_season_edits'] = [];
    }
    $_SESSION['admin_active_season_edits'][$seasonId] = true;
}

function admin_disable_active_season_edit(int $seasonId): void
{
    if (isset($_SESSION['admin_active_season_edits']) && is_array($_SESSION['admin_active_season_edits'])) {
        unset($_SESSION['admin_active_season_edits'][$seasonId]);
    }
}

function admin_season_has_match_data(mysqli $conn, int $seasonId): bool
{
    $stmt = $conn->prepare(
        'SELECT 1 FROM zapasy
         WHERE rocnik_id = ? AND (
             skore1 IS NOT NULL OR skore2 IS NOT NULL OR datum IS NOT NULL
             OR average_home IS NOT NULL OR average_away IS NOT NULL
             OR COALESCE(high_finish_home, 0) > 0 OR COALESCE(high_finish_away, 0) > 0
             OR COALESCE(count_100p_home, 0) > 0 OR COALESCE(count_100p_away, 0) > 0
             OR COALESCE(count_120p_home, 0) > 0 OR COALESCE(count_120p_away, 0) > 0
             OR COALESCE(count_140p_home, 0) > 0 OR COALESCE(count_140p_away, 0) > 0
             OR COALESCE(count_160p_home, 0) > 0 OR COALESCE(count_160p_away, 0) > 0
             OR COALESCE(count_180_home, 0) > 0 OR COALESCE(count_180_away, 0) > 0
         ) LIMIT 1'
    );
    $stmt->bind_param('i', $seasonId);
    $stmt->execute();
    $hasData = (bool)$stmt->get_result()->fetch_row();
    $stmt->close();
    return $hasData;
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
    foreach (['skore1', 'skore2', 'datum', 'average_home', 'average_away'] as $field) {
        if (array_key_exists($field, $match) && $match[$field] !== null) {
            return true;
        }
    }
    foreach ([
        'high_finish_home', 'high_finish_away',
        'count_100p_home', 'count_100p_away',
        'count_120p_home', 'count_120p_away',
        'count_140p_home', 'count_140p_away',
        'count_160p_home', 'count_160p_away',
        'count_180_home', 'count_180_away',
    ] as $field) {
        if (array_key_exists($field, $match) && (int)$match[$field] > 0) {
            return true;
        }
    }
    return false;
}

function admin_status_label(string $status): string
{
    return match ($status) {
        'aktivni' => 'Aktivní',
        'archivni' => 'Dokončená / archivní',
        default => 'Příprava',
    };
}
