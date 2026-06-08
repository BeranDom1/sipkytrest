<?php
// soubor: minitabulka_serazeni.php
// Řazení hráčů se shodnými body pomocí výsledků „vzájemných“ zápasů v rámci daného ročníku (+ volitelně i ligy)
// Fallback při absenci vzájemných zápasů: RZD (desc); nikdy neřadit podle abecedy.

function porovnej_vzajemny_zapas(array $a, array $b, mysqli $conn, int $rocnik_id, ?int $liga_id = null): int {
    $sql = "SELECT hrac1_id, hrac2_id, skore1, skore2
            FROM zapasy
            WHERE rocnik_id = ?
              AND ((hrac1_id = ? AND hrac2_id = ?) OR (hrac1_id = ? AND hrac2_id = ?))
              AND skore1 IS NOT NULL AND skore2 IS NOT NULL";

    if ($liga_id !== null) {
        $sql .= " AND liga_id = ?";
    }

    $sql .= " LIMIT 1";

    $st = $conn->prepare($sql);
    if ($liga_id !== null) {
        $st->bind_param('iiiiii', $rocnik_id, $a['player_id'], $b['player_id'], $b['player_id'], $a['player_id'], $liga_id);
    } else {
        $st->bind_param('iiiii', $rocnik_id, $a['player_id'], $b['player_id'], $b['player_id'], $a['player_id']);
    }

    $st->execute();
    $m = $st->get_result()->fetch_assoc();
    $st->close();

    if (!$m || (int)$m['skore1'] === (int)$m['skore2']) {
        return 0;
    }

    $aVyhral = ((int)$m['hrac1_id'] === (int)$a['player_id'] && (int)$m['skore1'] > (int)$m['skore2'])
        || ((int)$m['hrac2_id'] === (int)$a['player_id'] && (int)$m['skore2'] > (int)$m['skore1']);

    return $aVyhral ? -1 : 1;
}

function do_rad_nerozhodnute_dvojice(array $skupina, mysqli $conn, int $rocnik_id, ?int $liga_id = null): array {
    $vysledek = [];
    $pocet = count($skupina);

    for ($i = 0; $i < $pocet; $i++) {
        $aktualni = [$skupina[$i]];

        while (
            $i + 1 < $pocet
            && ($skupina[$i]['mini_vyhra'] ?? null) === ($skupina[$i + 1]['mini_vyhra'] ?? null)
            && ($skupina[$i]['mini_rzd'] ?? null) === ($skupina[$i + 1]['mini_rzd'] ?? null)
        ) {
            $aktualni[] = $skupina[++$i];
        }

        if (count($aktualni) === 2) {
            $cmp = porovnej_vzajemny_zapas($aktualni[0], $aktualni[1], $conn, $rocnik_id, $liga_id);
            if ($cmp > 0) {
                $aktualni = [$aktualni[1], $aktualni[0]];
            }
        }

        $vysledek = array_merge($vysledek, $aktualni);
    }

    return $vysledek;
}

function serad_hrace_s_rovnymi_body(array $rows, mysqli $conn, int $rocnik_id, int $liga_id = null): array {
    // rozdělení na skupiny dle bodů
    $skupiny = [];
    foreach ($rows as $r) {
        $skupiny[$r['body']][] = $r;
    }

    $final = [];

    foreach ($skupiny as $skupina) {

        // --- 1v1: rozhodni vzájemným zápasem, jinak RZD
        if (count($skupina) <= 2) {
            usort($skupina, function($a, $b) use ($conn, $rocnik_id, $liga_id) {
                $vzajemny = porovnej_vzajemny_zapas($a, $b, $conn, $rocnik_id, $liga_id);
                if ($vzajemny !== 0) return $vzajemny;

                // fallback: lepší celkový RZD v sezóně
                if (($a['RZD'] ?? 0) !== ($b['RZD'] ?? 0)) return ($b['RZD'] ?? 0) <=> ($a['RZD'] ?? 0);
                return 0;
            });

        } else {
            // --- minitabulka ve skupině (jen odehrané vzájemné zápasy)
            $ids = array_column($skupina, 'player_id');
            $vysledky = [];
            foreach ($ids as $id1) {
                $vysledky[$id1] = ['vitezstvi' => 0, 'rzd' => 0];
            }

            $in = implode(',', array_map('intval', $ids));
            $sql = "SELECT hrac1_id, hrac2_id, skore1, skore2
                    FROM zapasy
                    WHERE rocnik_id = $rocnik_id
                      AND hrac1_id IN ($in) AND hrac2_id IN ($in)
                      AND skore1 IS NOT NULL AND skore2 IS NOT NULL";
            if ($liga_id !== null) $sql .= " AND liga_id = ".(int)$liga_id;

            $res = $conn->query($sql);
            $pocetMinitabulkovychZapasu = 0;

            while ($z = $res->fetch_assoc()) {
                $pocetMinitabulkovychZapasu++;

                if ($z['skore1'] > $z['skore2']) {
                    $vysledky[$z['hrac1_id']]['vitezstvi']++;
                } elseif ($z['skore2'] > $z['skore1']) {
                    $vysledky[$z['hrac2_id']]['vitezstvi']++;
                }
                $vysledky[$z['hrac1_id']]['rzd'] += $z['skore1'] - $z['skore2'];
                $vysledky[$z['hrac2_id']]['rzd'] += $z['skore2'] - $z['skore1'];
            }
            if ($res) { $res->close(); }

            foreach ($skupina as &$hrac) {
                $hrac['mini_vyhra'] = $vysledky[$hrac['player_id']]['vitezstvi'] ?? 0;
                $hrac['mini_rzd']   = $vysledky[$hrac['player_id']]['rzd'] ?? 0;
            }
            unset($hrac);

            if ($pocetMinitabulkovychZapasu === 0) {
                // --- žádné vzájemné zápasy v téhle skupině → fallback na RZD (desc)
                usort($skupina, function($a, $b) {
                    if (($a['RZD'] ?? 0) !== ($b['RZD'] ?? 0)) return ($b['RZD'] ?? 0) <=> ($a['RZD'] ?? 0);
                    return 0;
                });
            } else {
                // --- standardní minitabulka: počet výher -> mini RZD -> vzájemný zápas v nerozhodnuté dvojici
                usort($skupina, function($a, $b) {
                    if ($a['mini_vyhra'] !== $b['mini_vyhra']) return $b['mini_vyhra'] <=> $a['mini_vyhra'];
                    if ($a['mini_rzd']   !== $b['mini_rzd'])   return $b['mini_rzd']   <=> $a['mini_rzd'];
                    return 0;
                });
                $skupina = do_rad_nerozhodnute_dvojice($skupina, $conn, $rocnik_id, $liga_id);
            }
        }

        $final = array_merge($final, $skupina);
    }

    return $final;
}
