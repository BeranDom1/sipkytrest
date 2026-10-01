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
function generujSportovniPavouk(mysqli $conn, int $turnaj_id, int $velikost = 64, bool $vlastniTransakce = true): void
{
    if ($velikost < 2 || $velikost > 64 || ($velikost & ($velikost - 1)) !== 0) {
        throw new InvalidArgumentException('Velikost pavouka musí být 2, 4, 8, 16, 32 nebo 64.');
    }

    if ($vlastniTransakce) $conn->begin_transaction();

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

        if ($vlastniTransakce) $conn->commit();

    } catch (Throwable $e) {
        if ($vlastniTransakce) $conn->rollback();
        throw $e;
    }
}

function vitezneLegyProKolo(?string $legyJson, int $kolo): int
{
    $legy = json_decode((string)$legyJson, true);
    if (is_array($legy) && isset($legy[(string)$kolo])) {
        return max(1, min(15, (int)$legy[(string)$kolo]));
    }
    return $kolo >= 5 ? 4 : 3;
}

function validujSkorePodleKola(int $kolo, int $s1, int $s2, int $vitezneLegy): void
{
    if ($s1 === $s2) {
        throw new Exception('Remíza není povolena.');
    }

    $max = max($s1, $s2);
    $min = min($s1, $s2);

    if ($max !== $vitezneLegy || $min < 0 || $min >= $vitezneLegy) {
        throw new Exception(
            'Neplatné skóre – toto kolo se hraje na '.$vitezneLegy.
            ' vítězné legy ('.$vitezneLegy.':0 až '.$vitezneLegy.':'.($vitezneLegy - 1).').'
        );
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
            SELECT z.id, z.kolo, z.hrac1_id, z.hrac2_id, z.vitez_id,
                   z.next_match_id, z.next_slot, t.legy_json, t.stav
            FROM turnaj_zapasy z
            JOIN turnaje t ON t.id = z.turnaj_id
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
            throw new Exception('Výsledky lze zadávat pouze u spuštěného turnaje.');
        }

        $h1 = $z['hrac1_id'] === null ? null : (int)$z['hrac1_id'];
        $h2 = $z['hrac2_id'] === null ? null : (int)$z['hrac2_id'];

        if (($h1 > 0 && $h2 === 0) || ($h2 > 0 && $h1 === 0)) {
            throw new Exception('Hráč s volným losem už postupuje automaticky.');
        }

        // validace skóre
        $vitezneLegy = vitezneLegyProKolo($z['legy_json'] ?? null, (int)$z['kolo']);
        validujSkorePodleKola((int)$z['kolo'], $s1, $s2, $vitezneLegy);

        // určení vítěze
        if ($h1 > 0 && $h2 > 0) {
            $vitez_id = ($s1 > $s2) ? $h1 : $h2;
        } else {
            throw new Exception('Zápas nemá oba hráče');
        }

        $oldWinner = $z['vitez_id'] === null ? null : (int)$z['vitez_id'];

        // Navazující zápas zamkneme dřív, než v něm vítěze vyměníme.
        if ($z['next_match_id'] && $z['next_slot']) {
            $slotCol = $z['next_slot'] === 'hrac1' ? 'hrac1_id' : 'hrac2_id';
            $stmt = $conn->prepare("
                SELECT {$slotCol} AS postupujici, skore1, skore2, vitez_id
                FROM turnaj_zapasy
                WHERE id = ?
                FOR UPDATE
            ");
            $stmt->bind_param("i", $z['next_match_id']);
            $stmt->execute();
            $nextMatch = $stmt->get_result()->fetch_assoc();

            if (!$nextMatch) {
                throw new Exception('Navazující zápas nebyl nalezen.');
            }

            $nextHasResult = $nextMatch['skore1'] !== null
                || $nextMatch['skore2'] !== null
                || $nextMatch['vitez_id'] !== null;
            if ($nextHasResult && $oldWinner !== $vitez_id) {
                throw new Exception('Vítěze už nelze změnit – jeho další zápas je odehraný.');
            }

            $currentAdvancedPlayer = $nextMatch['postupujici'] === null
                ? null
                : (int)$nextMatch['postupujici'];
            if (
                $currentAdvancedPlayer !== null
                && $currentAdvancedPlayer !== $oldWinner
                && $currentAdvancedPlayer !== $vitez_id
            ) {
                throw new Exception('Navazující pozice už obsahuje jiného hráče.');
            }
        }

        // ulož nový výsledek
        $stmt = $conn->prepare("
            UPDATE turnaj_zapasy
            SET skore1 = ?, skore2 = ?, vitez_id = ?
            WHERE id = ?
        ");
        $stmt->bind_param("iiii", $s1, $s2, $vitez_id, $zapas_id);
        $stmt->execute();

        // Propaguj uloženého vítěze do přesně určené pozice dalšího kola.
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

