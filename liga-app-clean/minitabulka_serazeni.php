<?php
// soubor: minitabulka_serazeni.php
// Řazení hráčů se shodnými body pomocí výsledků „vzájemných“ zápasů v rámci daného ročníku (+ volitelně i ligy)

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
                $sql = "SELECT hrac1_id, hrac2_id, skore1, skore2
                        FROM zapasy
                        WHERE rocnik_id = ?
                          AND ((hrac1_id = ? AND hrac2_id = ?) OR (hrac1_id = ? AND hrac2_id = ?))";
                if ($liga_id !== null) $sql .= " AND liga_id = ?";
                $sql .= " LIMIT 1";

                if ($liga_id !== null) {
                    $st = $conn->prepare($sql);
                    $st->bind_param('iiiiii', $rocnik_id, $a['player_id'], $b['player_id'], $b['player_id'], $a['player_id'], $liga_id);
                } else {
                    $st = $conn->prepare($sql);
                    $st->bind_param('iiiii', $rocnik_id, $a['player_id'], $b['player_id'], $b['player_id'], $a['player_id']);
                }
                $st->execute();
                $m = $st->get_result()->fetch_assoc();

                if ($m) {
                    if     ($m['hrac1_id'] == $a['player_id'] && $m['skore1'] > $m['skore2']) return -1;
                    elseif ($m['hrac2_id'] == $a['player_id'] && $m['skore2'] > $m['skore1']) return -1;
                    elseif ($m['skore1'] != $m['skore2']) return 1;
                }
                // fallback: lepší RZD
                return ($b['RZD'] ?? 0) <=> ($a['RZD'] ?? 0);
            });

        } else {
            // --- minitabulka ve skupině
            $ids = array_column($skupina, 'player_id');
            $vysledky = [];
            foreach ($ids as $id1) {
                $vysledky[$id1] = ['vitezstvi' => 0, 'rzd' => 0];
            }

            $in = implode(',', array_map('intval', $ids));
            $sql = "SELECT hrac1_id, hrac2_id, skore1, skore2
                    FROM zapasy
                    WHERE rocnik_id = $rocnik_id
                      AND hrac1_id IN ($in) AND hrac2_id IN ($in)";
            if ($liga_id !== null) $sql .= " AND liga_id = ".(int)$liga_id;

            $res = $conn->query($sql);
            while ($z = $res->fetch_assoc()) {
                if ($z['skore1'] > $z['skore2']) {
                    $vysledky[$z['hrac1_id']]['vitezstvi']++;
                } elseif ($z['skore2'] > $z['skore1']) {
                    $vysledky[$z['hrac2_id']]['vitezstvi']++;
                }
                $vysledky[$z['hrac1_id']]['rzd'] += $z['skore1'] - $z['skore2'];
                $vysledky[$z['hrac2_id']]['rzd'] += $z['skore2'] - $z['skore1'];
            }

            foreach ($skupina as &$hrac) {
                $hrac['mini_vyhra'] = $vysledky[$hrac['player_id']]['vitezstvi'] ?? 0;
                $hrac['mini_rzd']   = $vysledky[$hrac['player_id']]['rzd'] ?? 0;
            }
            unset($hrac);

            usort($skupina, function($a, $b) {
                if ($a['mini_vyhra'] !== $b['mini_vyhra']) return $b['mini_vyhra'] <=> $a['mini_vyhra'];
                if ($a['mini_rzd']   !== $b['mini_rzd'])   return $b['mini_rzd']   <=> $a['mini_rzd'];
                return strcasecmp($a['jmeno'], $b['jmeno']);
            });
        }

        $final = array_merge($final, $skupina);
    }

    return $final;
}
