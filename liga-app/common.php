<?php
// Common helpers for liga/rozpis/stat pages – conservative mysqli-based implementation
require_once __DIR__.'/db.php';

function _active_rocnik_id(mysqli $conn): int {
    if (isset($_SESSION['rocnik_id']) && (int)$_SESSION['rocnik_id'] > 0) {
        return (int)$_SESSION['rocnik_id'];
    }
    $res = $conn->query("SELECT MAX(id) AS mx FROM rocniky");
    $row = $res ? $res->fetch_assoc() : ['mx' => 1];
    return (int)($row['mx'] ?? 1);
}

// Přeloží číslo ligy (0..5) na skutečné ligy.id
function _liga_id_from_cislo(mysqli $conn, int $cislo): ?int {
    if ($cislo < 0) $cislo = 0;
    if ($cislo > 5) $cislo = 5;
    $st = $conn->prepare("SELECT id FROM ligy WHERE cislo = ? ORDER BY id LIMIT 1");
    $st->bind_param('i', $cislo);
    $st->execute();
    $row = $st->get_result()->fetch_assoc();
    $st->close();
    return $row ? (int)$row['id'] : null;
}

/**
 * Vrať "správné" ligy.id takto:
 * - preferuj ?cislo= (0..5) → přelož na id z DB
 * - wrappery typu ligy/1.liga.php posílají $liga_id = 1..5 → ber to jako cislo a přelož
 * - legacy ?liga=:
 *     - pokud 0..5 → ber to jako cislo a přelož
 *     - pokud >5   → ber to jako skutečné ligy.id (ověř existenci)
 * - default: 1. liga (cislo=1) → přelož na id
 */
function _safe_liga_id(): int {
    global $conn;

    // 1) explicitní ?cislo=
    if (isset($_GET['cislo'])) {
        $id = _liga_id_from_cislo($conn, (int)$_GET['cislo']);
        if ($id) return $id;
    }

    // 2) wrappery: $GLOBALS['liga_id'] = 1..5 => je to cislo
    if (isset($GLOBALS['liga_id'])) {
        $v = (int)$GLOBALS['liga_id'];
        if ($v >= 0 && $v <= 5) {
            $id = _liga_id_from_cislo($conn, $v);
            if ($id) return $id;
        }
        // když by někdo poslal přímo velké číslo jako skutečné ID
        if ($v > 5) {
            $st = $conn->prepare("SELECT id FROM ligy WHERE id = ? LIMIT 1");
            $st->bind_param('i', $v);
            $st->execute();
            $ok = (int)($st->get_result()->fetch_assoc()['id'] ?? 0);
            $st->close();
            if ($ok) return $v;
        }
    }

    // 3) legacy ?liga=
    if (isset($_GET['liga'])) {
        $v = (int)$_GET['liga'];
        if ($v >= 0 && $v <= 5) {
            $id = _liga_id_from_cislo($conn, $v);
            if ($id) return $id;
        } else if ($v > 5) {
            $st = $conn->prepare("SELECT id FROM ligy WHERE id = ? LIMIT 1");
            $st->bind_param('i', $v);
            $st->execute();
            $ok = (int)($st->get_result()->fetch_assoc()['id'] ?? 0);
            $st->close();
            if ($ok) return $v;
        }
    }

    // 4) fallback: 1. liga (cislo=1)
    $id = _liga_id_from_cislo($conn, 1);
    if ($id) return $id;

    // 5) absolutní nouzový fallback: první řádek v ligy
    $res = $conn->query("SELECT id FROM ligy ORDER BY id LIMIT 1");
    $row = $res ? $res->fetch_assoc() : null;
    return (int)($row['id'] ?? 1);

}

function _rocnik_name(mysqli $conn, int $rocnik_id): string {
    $st = $conn->prepare("SELECT nazev FROM rocniky WHERE id=?");
    $st->bind_param('i', $rocnik_id);
    $st->execute();
    $res = $st->get_result()->fetch_assoc();
    $st->close();
    return $res['nazev'] ?? ('Ročník '.$rocnik_id);
}

if (!function_exists('_players_for')) {
    function _players_for(mysqli $conn, int $rocnik_id, int $liga_id): array {
        $sql = "SELECT h.id, h.jmeno
                FROM hraci_v_sezone hs
                JOIN hraci h ON h.id = hs.hrac_id
                WHERE hs.rocnik_id = ? AND hs.liga_id = ?
                ORDER BY h.jmeno";
        $st = $conn->prepare($sql);
        $st->bind_param('ii', $rocnik_id, $liga_id);
        $st->execute();
        $res = $st->get_result();
        $out = [];
        while ($r = $res->fetch_assoc()) { $out[] = $r; }
        $st->close();
        return $out;
    }
}
function liga_cislo(int $ligaId): int {
    static $map = null;
    if ($map === null) {
        global $conn;
        $map = [];
        if ($conn) {
            $res = $conn->query("SELECT id, cislo FROM ligy");
            while ($r = $res->fetch_assoc()) {
                $map[(int)$r['id']] = (int)$r['cislo'];
            }
        }
    }
    return $map[$ligaId] ?? $ligaId;
}

function _liga_name(mysqli $conn, int $liga_id, ?int $rocnik_id = null): string
{
    // když není ročník předán, vezmi aktivní (zpětná kompatibilita)
    if ($rocnik_id === null) {
        $rocnik_id = _active_rocnik_id($conn);
    }

    // 1️⃣ nový systém – názvy lig podle ročníku
    $sql = "SELECT nazev
              FROM ligy_nazvy
             WHERE liga_id = ?
               AND rocnik_id = ?
             LIMIT 1";

    $st = $conn->prepare($sql);
    $st->bind_param('ii', $liga_id, $rocnik_id);
    $st->execute();
    $res = $st->get_result();

    if ($row = $res->fetch_assoc()) {
        $st->close();
        return $row['nazev'];
    }
    $st->close();

    // 2️⃣ fallback – staré ročníky (tabulka ligy)
    $sql = "SELECT nazev
              FROM ligy
             WHERE id = ?
             LIMIT 1";

    $st = $conn->prepare($sql);
    $st->bind_param('i', $liga_id);
    $st->execute();

    $nazev = $st->get_result()->fetch_assoc()['nazev'] ?? 'Neznámá liga';
    $st->close();

    return $nazev;
}


