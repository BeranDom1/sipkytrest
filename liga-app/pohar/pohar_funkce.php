<?php
// liga-app/pohar/pohar_funkce.php

/**
 * =========================================================
 * JMÉNO HRÁČE (CACHE)
 * =========================================================
 */
function getJmenoHraca(mysqli $conn, int $hrac_id): string
{
    static $cache = [];

    if (!isset($cache[$hrac_id])) {
        $stmt = $conn->prepare("
            SELECT jmeno
            FROM hraci_unikatni_jmena
            WHERE libovolne_id = ?
        ");
        $stmt->bind_param("i", $hrac_id);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_row();

        $cache[$hrac_id] = $res[0] ?? 'Neznámý';
    }

    return $cache[$hrac_id];
}

/**
 * =========================================================
 * SPORTOVNÍ KO PAVOUK (1 vs 64 → finále)
 * =========================================================
 * - vytvoří KOSTRU pavouka
 * - žádní hráči
 * - zrcadlové párování
 */
function generujSportovniPavouk(mysqli $conn, int $turnaj_id, int $velikost = 64): void
{
    $conn->begin_transaction();

    try {
        $zapasy = [];
        $pocetKol = (int)log($velikost, 2);

        // === vytvoření všech kol ===
        for ($kolo = 1; $kolo <= $pocetKol; $kolo++) {
            $zapasuVKole = $velikost / (2 ** $kolo);

            for ($i = 1; $i <= $zapasuVKole; $i++) {
                $stmt = $conn->prepare("
                    INSERT INTO turnaj_zapasy (turnaj_id, kolo, poradi)
                    VALUES (?, ?, ?)
                ");
                $stmt->bind_param("iii", $turnaj_id, $kolo, $i);
                $stmt->execute();

                $zapasy[$kolo][$i] = $conn->insert_id;
            }
        }

        // === zrcadlové vazby mezi koly ===
        for ($kolo = 1; $kolo < $pocetKol; $kolo++) {
            $pocetZapasu = count($zapasy[$kolo]);

            for ($i = 1; $i <= $pocetZapasu / 2; $i++) {
                $j = $pocetZapasu + 1 - $i;

                // i → hrac1
                $stmt = $conn->prepare("
                    UPDATE turnaj_zapasy
                    SET next_match_id = ?, next_slot = 'hrac1'
                    WHERE id = ?
                ");
                $stmt->bind_param("ii",
                    $zapasy[$kolo + 1][$i],
                    $zapasy[$kolo][$i]
                );
                $stmt->execute();

                // j → hrac2
                $stmt = $conn->prepare("
                    UPDATE turnaj_zapasy
                    SET next_match_id = ?, next_slot = 'hrac2'
                    WHERE id = ?
                ");
                $stmt->bind_param("ii",
                    $zapasy[$kolo + 1][$i],
                    $zapasy[$kolo][$j]
                );
                $stmt->execute();
            }
        }

        $conn->commit();

    } catch (Throwable $e) {
        $conn->rollback();
        throw $e;
    }
}
function validujSkorePodleKola(int $kolo, int $s1, int $s2): void
{
    if ($s1 === $s2) {
        throw new Exception('Remíza není povolena.');
    }

    $max = max($s1, $s2);
    $min = min($s1, $s2);

    // 1.–4. kolo → na 3 vítězné legy
    if ($kolo <= 4) {
        if ($max !== 3 || $min < 0 || $min > 2) {
            throw new Exception('Neplatné skóre – hraje se na 3 vítězné legy (3:0 až 3:2).');
        }
    }

    // semifinále + finále → na 4 vítězné legy
    if ($kolo >= 5) {
        if ($max !== 4 || $min < 0 || $min > 3) {
            throw new Exception('Neplatné skóre – hraje se na 4 vítězné legy (4:0 až 4:3).');
        }
    }
}


/**
 * =========================================================
 * ULOŽENÍ SKÓRE + AUTOMATICKÝ POSTUP
 * =========================================================
 * - řeší BYE
 * - zamyká zápas
 * - propaguje vítěze
 */
function ulozSkoreAZpropagujViteze(mysqli $conn, int $zapas_id, int $s1, int $s2): void
{
    $conn->begin_transaction();

    try {
        // zamkni zápas
        $stmt = $conn->prepare("
            SELECT id, kolo, hrac1_id, hrac2_id, vitez_id, next_match_id, next_slot
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
// === AUTOMATICKÝ BYE ===
if (
    ($z['hrac1_id'] > 0 && $z['hrac2_id'] === 0) ||
    ($z['hrac2_id'] > 0 && $z['hrac1_id'] === 0)
) {
    $vitez_id = $z['hrac1_id'] > 0 ? $z['hrac1_id'] : $z['hrac2_id'];

    // uložit jako BYE
    $stmt = $conn->prepare("
        UPDATE turnaj_zapasy
        SET vitez_id = ?, skore1 = NULL, skore2 = NULL
        WHERE id = ?
    ");
    $stmt->bind_param("ii", $vitez_id, $zapas_id);
    $stmt->execute();

    // propis do dalšího kola
    if ($z['next_match_id'] && $z['next_slot']) {
        $slotCol = $z['next_slot'] === 'hrac1' ? 'hrac1_id' : 'hrac2_id';

        $stmt = $conn->prepare("
            UPDATE turnaj_zapasy
            SET {$slotCol} = ?
            WHERE id = ?
        ");
        $stmt->bind_param("ii", $vitez_id, $z['next_match_id']);
        $stmt->execute();
    }

    $conn->commit();
    return;
}

        // validace skóre
        validujSkorePodleKola((int)$z['kolo'], $s1, $s2);

        // určení vítěze
        if ($z['hrac1_id'] && $z['hrac2_id']) {
            $vitez_id = ($s1 > $s2) ? $z['hrac1_id'] : $z['hrac2_id'];
        } else {
            throw new Exception('Zápas nemá oba hráče');
        }

        // 🔁 pokud už byl starý vítěz → ODSTRANIT ho z dalšího kola
        if ($z['vitez_id'] && $z['next_match_id'] && $z['next_slot']) {
            $slotCol = $z['next_slot'] === 'hrac1' ? 'hrac1_id' : 'hrac2_id';

            $stmt = $conn->prepare("
                UPDATE turnaj_zapasy
                SET {$slotCol} = NULL
                WHERE id = ?
            ");
            $stmt->bind_param("i", $z['next_match_id']);
            $stmt->execute();
        }

        // ulož nový výsledek
        $stmt = $conn->prepare("
            UPDATE turnaj_zapasy
            SET skore1 = ?, skore2 = ?, vitez_id = ?
            WHERE id = ?
        ");
        $stmt->bind_param("iiii", $s1, $s2, $vitez_id, $zapas_id);
        $stmt->execute();

        // propaguj nového vítěze
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

        $conn->commit();

    } catch (Throwable $e) {
        $conn->rollback();
        throw $e;
    }
}

