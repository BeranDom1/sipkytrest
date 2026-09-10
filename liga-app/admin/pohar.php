<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../security/csrf.php';
require_once __DIR__ . '/../pohar/pohar_funkce.php';

/**
 * Starší instalace databáze ještě nemají konfigurační sloupce poháru.
 * Jde pouze o nedestruktivní ADD COLUMN; existující data ani výsledky se nemažou.
 */
function zajistiSchemaPoharu(mysqli $conn): void
{
    $columns = [
        'turnaje' => [
            'uvod' => 'TEXT NULL AFTER stav',
            'herni_mod' => 'VARCHAR(500) NULL AFTER uvod',
            'system_hry' => 'VARCHAR(500) NULL AFTER herni_mod',
            'los_typ' => "ENUM('nasazeny', 'nahodny') NOT NULL DEFAULT 'nasazeny' AFTER system_hry",
            'los_popis' => 'VARCHAR(500) NULL AFTER los_typ',
            'velikost_pavouka' => 'SMALLINT UNSIGNED NOT NULL DEFAULT 64 AFTER los_popis',
            'legy_json' => 'TEXT NULL AFTER velikost_pavouka',
            'terminy_json' => 'TEXT NULL AFTER legy_json',
        ],
        'turnaj_hraci' => [
            'volny_los' => 'TINYINT(1) NOT NULL DEFAULT 0 AFTER nasazeni',
        ],
    ];

    foreach ($columns as $table => $tableColumns) {
        foreach ($tableColumns as $column => $definition) {
            $escapedColumn = $conn->real_escape_string($column);
            $result = $conn->query("SHOW COLUMNS FROM `{$table}` LIKE '{$escapedColumn}'");
            if ($result && $result->num_rows > 0) continue;
            if (!$conn->query("ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$definition}")) {
                throw new RuntimeException('Databázi poháru se nepodařilo aktualizovat: '.$conn->error);
            }
        }
    }

    $defaults = $conn->prepare(
        "UPDATE turnaje SET
         uvod=COALESCE(NULLIF(uvod, ''), 'Prezidentský pohár se hraje vyřazovacím způsobem (KO). Poražený v turnaji končí, vítěz postupuje do dalšího kola.'),
         herni_mod=COALESCE(NULLIF(herni_mod, ''), 'Cricket (cut-throut) na 3 vítězné legy, semifinále a finále na 4 vítězné legy'),
         system_hry=COALESCE(NULLIF(system_hry, ''), 'KO pavouk (64 → 32 → 16 → 8 → 4 → 2 → vítěz)'),
         los_popis=COALESCE(NULLIF(los_popis, ''), 'Prvních 32 nasazených hráčů + los'),
         legy_json=COALESCE(NULLIF(legy_json, ''), '{\"1\":3,\"2\":3,\"3\":3,\"4\":3,\"5\":4,\"6\":4}'),
         terminy_json=COALESCE(NULLIF(terminy_json, ''), '[{\"label\":\"TOP 64\",\"text\":\"odehrát do 1. 3. 2026\"},{\"label\":\"TOP 32\",\"text\":\"odehrát do 1. 4. 2026\"},{\"label\":\"TOP 16\",\"text\":\"odehrát do 25. 4. 2026\"},{\"label\":\"TOP 8\",\"text\":\"odehrát do 20. 5. 2026\"},{\"label\":\"Grande finále\",\"text\":\"(semifinále 1, semifinále 2, finále) – pátek 29. 5. 18:00 (sobota 30. 5. 18:00)\"}]')
         WHERE rocnik_id=4"
    );
    if (!$defaults || !$defaults->execute()) {
        throw new RuntimeException('Výchozí nastavení Jara 2026 se nepodařilo uložit.');
    }
    $defaults->close();
}

try {
    zajistiSchemaPoharu($conn);
} catch (Throwable $e) {
    error_log('Administrace poháru: '.$e->getMessage());
    http_response_code(500);
    exit('Databázi poháru se nepodařilo připravit. Spusťte databázovou migraci nebo zkontrolujte oprávnění databázového uživatele.');
}

function h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function poharTurnajProRocnik(mysqli $conn, int $rocnikId): ?array
{
    $stmt = $conn->prepare('SELECT * FROM turnaje WHERE rocnik_id=? ORDER BY id DESC LIMIT 1');
    $stmt->bind_param('i', $rocnikId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}

$seasons = $conn->query('SELECT id, nazev, stav FROM rocniky ORDER BY id DESC')->fetch_all(MYSQLI_ASSOC);
$seasonId = (int)($_REQUEST['rocnik_id'] ?? ($_SESSION['rocnik_id'] ?? ($seasons[0]['id'] ?? 0)));
$season = null;
foreach ($seasons as $candidate) {
    if ((int)$candidate['id'] === $seasonId) $season = $candidate;
}
if (!$season && $seasons) {
    $season = $seasons[0];
    $seasonId = (int)$season['id'];
}

$message = (string)($_GET['message'] ?? '');
$error = '';
$turnaj = $seasonId > 0 ? poharTurnajProRocnik($conn, $seasonId) : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_POST['csrf'] ?? '')) {
        http_response_code(403);
        exit('CSRF ověření selhalo.');
    }

    $action = (string)($_POST['action'] ?? '');
    try {
        if ($action === 'create') {
            if ($turnaj) throw new RuntimeException('Pro tuto sezonu už turnaj existuje.');
            $name = 'Prezidentský pohár '.($season['nazev'] ?? '');
            $uvod = 'Prezidentský pohár se hraje vyřazovacím způsobem (KO). Poražený v turnaji končí, vítěz postupuje do dalšího kola.';
            $herniMod = 'Cricket (cut-throut) na 3 vítězné legy, semifinále a finále na 4 vítězné legy';
            $system = 'KO pavouk (64 → 32 → 16 → 8 → 4 → 2 → vítěz)';
            $losPopis = 'Nasazení hráči podle zadaného pořadí';
            $legy = json_encode(['1' => 3, '2' => 3, '3' => 3, '4' => 3, '5' => 4, '6' => 4]);

            $conn->begin_transaction();
            $stmt = $conn->prepare(
                "INSERT INTO turnaje
                 (nazev, rocnik_id, stav, uvod, herni_mod, system_hry, los_typ, los_popis, velikost_pavouka, legy_json, terminy_json)
                 VALUES (?, ?, 'priprava', ?, ?, ?, 'nasazeny', ?, 64, ?, '[]')"
            );
            $stmt->bind_param('sisssss', $name, $seasonId, $uvod, $herniMod, $system, $losPopis, $legy);
            $stmt->execute();
            $turnajId = (int)$conn->insert_id;
            $stmt->close();

            $conn->commit();
            header('Location: /liga-app/admin/pohar.php?rocnik_id='.$seasonId.'&message='.rawurlencode('Prázdný turnaj byl vytvořen v režimu Příprava. Účastníky můžete doplnit později.'));
            exit;
        }

        if (!$turnaj) throw new RuntimeException('Nejdříve turnaj vytvořte.');
        $turnajId = (int)$turnaj['id'];

        $countStmt = $conn->prepare('SELECT COUNT(*) FROM turnaj_zapasy WHERE turnaj_id=?');
        $countStmt->bind_param('i', $turnajId);
        $countStmt->execute();
        $hasBracket = (int)$countStmt->get_result()->fetch_row()[0] > 0;
        $countStmt->close();

        $resultStmt = $conn->prepare(
            'SELECT COUNT(*) FROM turnaj_zapasy
              WHERE turnaj_id=? AND (skore1 IS NOT NULL OR skore2 IS NOT NULL OR vitez_id IS NOT NULL)'
        );
        $resultStmt->bind_param('i', $turnajId);
        $resultStmt->execute();
        $hasPlayedMatches = (int)$resultStmt->get_result()->fetch_row()[0] > 0;
        $resultStmt->close();

        if ($action === 'save_config') {
            $name = trim((string)($_POST['nazev'] ?? ''));
            $uvod = trim((string)($_POST['uvod'] ?? ''));
            $herniMod = trim((string)($_POST['herni_mod'] ?? ''));
            $system = trim((string)($_POST['system_hry'] ?? ''));
            $losPopis = trim((string)($_POST['los_popis'] ?? ''));
            $losTyp = ($_POST['los_typ'] ?? '') === 'nahodny' ? 'nahodny' : 'nasazeny';
            $velikost = (int)($_POST['velikost_pavouka'] ?? 64);
            if (!in_array($velikost, [4, 8, 16, 32, 64], true)) $velikost = 64;
            if ($name === '' || $uvod === '' || $herniMod === '' || $system === '' || $losPopis === '') {
                throw new RuntimeException('Vyplňte všechny texty v hlavičce turnaje.');
            }

            $legy = [];
            for ($round = 1; $round <= 6; $round++) {
                $legy[(string)$round] = max(1, min(15, (int)($_POST['legy'][$round] ?? ($round >= 5 ? 4 : 3))));
            }
            $terminy = [];
            for ($row = 0; $row < 6; $row++) {
                $label = trim((string)($_POST['termin_label'][$row] ?? ''));
                $text = trim((string)($_POST['termin_text'][$row] ?? ''));
                if ($label !== '' && $text !== '') $terminy[] = ['label' => $label, 'text' => $text];
            }
            $legyJson = json_encode($legy, JSON_UNESCAPED_UNICODE);
            $terminyJson = json_encode($terminy, JSON_UNESCAPED_UNICODE);

            $sql = 'UPDATE turnaje SET nazev=?, uvod=?, herni_mod=?, system_hry=?, los_typ=?, los_popis=?, legy_json=?, terminy_json=?';
            if (!$hasBracket) $sql .= ', velikost_pavouka=?';
            $sql .= ' WHERE id=?';
            $stmt = $conn->prepare($sql);
            if ($hasBracket) {
                $stmt->bind_param('ssssssssi', $name, $uvod, $herniMod, $system, $losTyp, $losPopis, $legyJson, $terminyJson, $turnajId);
            } else {
                $stmt->bind_param('ssssssssii', $name, $uvod, $herniMod, $system, $losTyp, $losPopis, $legyJson, $terminyJson, $velikost, $turnajId);
            }
            $stmt->execute();
            $stmt->close();
            header('Location: /liga-app/admin/pohar.php?rocnik_id='.$seasonId.'&message='.rawurlencode('Nastavení turnaje bylo uloženo.'));
            exit;
        }

        if ($action === 'save_players') {
            $manualDraft = $hasBracket && !$hasPlayedMatches && ($turnaj['stav'] ?? '') === 'priprava';
            if ($hasBracket && !$manualDraft) throw new RuntimeException('Po spuštění turnaje už nelze měnit seznam hráčů.');
            $selected = array_map('intval', is_array($_POST['hraci'] ?? null) ? $_POST['hraci'] : []);
            $selected = array_values(array_unique(array_filter($selected, static fn($id) => $id > 0)));
            $allowedStmt = $conn->prepare('SELECT libovolne_id AS hrac_id FROM hraci_unikatni_jmena');
            $allowedStmt->execute();
            $allowed = array_fill_keys(array_map('intval', array_column($allowedStmt->get_result()->fetch_all(MYSQLI_ASSOC), 'hrac_id')), true);
            $allowedStmt->close();
            $selected = array_values(array_filter($selected, static fn($id) => isset($allowed[$id])));
            if (count($selected) > (int)$turnaj['velikost_pavouka']) {
                throw new RuntimeException('Počet účastníků nesmí být vyšší než velikost pavouka.');
            }

            $conn->begin_transaction();
            $delete = $conn->prepare('DELETE FROM turnaj_hraci WHERE turnaj_id=?');
            $delete->bind_param('i', $turnajId);
            $delete->execute();
            $delete->close();
            $insert = $conn->prepare('INSERT INTO turnaj_hraci (turnaj_id, hrac_id, nasazeni, volny_los) VALUES (?, ?, NULLIF(?, 0), ?)');
            foreach ($selected as $playerId) {
                $seed = max(0, (int)($_POST['nasazeni'][$playerId] ?? 0));
                $bye = !empty($_POST['volny_los'][$playerId]) ? 1 : 0;
                $insert->bind_param('iiii', $turnajId, $playerId, $seed, $bye);
                $insert->execute();
            }
            $insert->close();

            if ($manualDraft) {
                $clearHome = $conn->prepare(
                    'UPDATE turnaj_zapasy z
                     LEFT JOIN turnaj_hraci th ON th.turnaj_id=z.turnaj_id AND th.hrac_id=z.hrac1_id
                     SET z.hrac1_id=NULL
                     WHERE z.turnaj_id=? AND z.kolo=1 AND z.hrac1_id IS NOT NULL AND z.hrac1_id<>0 AND th.id IS NULL'
                );
                $clearHome->bind_param('i', $turnajId);
                $clearHome->execute();
                $clearHome->close();
                $clearAway = $conn->prepare(
                    'UPDATE turnaj_zapasy z
                     LEFT JOIN turnaj_hraci th ON th.turnaj_id=z.turnaj_id AND th.hrac_id=z.hrac2_id
                     SET z.hrac2_id=NULL
                     WHERE z.turnaj_id=? AND z.kolo=1 AND z.hrac2_id IS NOT NULL AND z.hrac2_id<>0 AND th.id IS NULL'
                );
                $clearAway->bind_param('i', $turnajId);
                $clearAway->execute();
                $clearAway->close();
            }
            $conn->commit();
            header('Location: /liga-app/admin/pohar.php?rocnik_id='.$seasonId.'&message='.rawurlencode('Seznam účastníků byl uložen.'));
            exit;
        }

        if ($action === 'start_manual') {
            if ($hasBracket) throw new RuntimeException('Pavouk už byl vytvořen.');
            $velikost = (int)$turnaj['velikost_pavouka'];
            $conn->begin_transaction();
            generujSportovniPavouk($conn, $turnajId, $velikost, false);
            $status = $conn->prepare("UPDATE turnaje SET stav='priprava' WHERE id=?");
            $status->bind_param('i', $turnajId);
            $status->execute();
            $status->close();
            $conn->commit();
            header('Location: /liga-app/pohar/pohar_1kolo_admin.php?id='.$turnajId);
            exit;
        }

        if ($action === 'finish_manual_draw') {
            $manualDraft = $hasBracket && !$hasPlayedMatches && ($turnaj['stav'] ?? '') === 'priprava';
            if (!$manualDraft) throw new RuntimeException('Ruční los už nelze dokončit nebo turnaj není v režimu přípravy.');

            $playerStmt = $conn->prepare('SELECT hrac_id FROM turnaj_hraci WHERE turnaj_id=?');
            $playerStmt->bind_param('i', $turnajId);
            $playerStmt->execute();
            $selectedIds = array_map('intval', array_column($playerStmt->get_result()->fetch_all(MYSQLI_ASSOC), 'hrac_id'));
            $playerStmt->close();
            $selected = array_fill_keys($selectedIds, true);
            $velikost = (int)$turnaj['velikost_pavouka'];
            if (count($selected) < 2 || count($selected) > $velikost) {
                throw new RuntimeException('Před dokončením losu vyberte 2 až '.$velikost.' účastníků.');
            }
            if (count($selected) <= intdiv($velikost, 2)) {
                throw new RuntimeException('Pro tento počet účastníků zvolte menší velikost pavouka.');
            }

            $matchStmt = $conn->prepare(
                'SELECT id, hrac1_id, hrac2_id, next_match_id, next_slot
                   FROM turnaj_zapasy WHERE turnaj_id=? AND kolo=1 ORDER BY poradi'
            );
            $matchStmt->bind_param('i', $turnajId);
            $matchStmt->execute();
            $firstRound = $matchStmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $matchStmt->close();
            $used = [];
            foreach ($firstRound as $index => $match) {
                $home = (int)($match['hrac1_id'] ?? 0);
                $away = (int)($match['hrac2_id'] ?? 0);
                if ($home <= 0 && $away <= 0) {
                    throw new RuntimeException('Zápas č. '.($index + 1).' nemá žádného hráče. Pro volný los vyplňte pouze jednu stranu.');
                }
                if ($home > 0 && $home === $away) {
                    throw new RuntimeException('Ve zápasu č. '.($index + 1).' je stejný hráč na obou stranách.');
                }
                foreach ([$home, $away] as $playerId) {
                    if ($playerId <= 0) continue;
                    if (!isset($selected[$playerId])) throw new RuntimeException('V losu je hráč, který není v seznamu účastníků.');
                    if (isset($used[$playerId])) throw new RuntimeException('Jeden hráč je v prvním kole uveden vícekrát.');
                    $used[$playerId] = true;
                }
            }
            if (count($used) !== count($selected)) {
                throw new RuntimeException('Do dvojic nejsou zařazeni všichni vybraní účastníci.');
            }

            $conn->begin_transaction();
            $reset = $conn->prepare(
                'UPDATE turnaj_zapasy SET skore1=NULL, skore2=NULL, vitez_id=NULL,
                 hrac1_id=IF(kolo=1, hrac1_id, NULL), hrac2_id=IF(kolo=1, hrac2_id, NULL)
                 WHERE turnaj_id=?'
            );
            $reset->bind_param('i', $turnajId);
            $reset->execute();
            $reset->close();
            $setWinner = $conn->prepare('UPDATE turnaj_zapasy SET vitez_id=? WHERE id=?');
            foreach ($firstRound as $match) {
                $home = (int)($match['hrac1_id'] ?? 0);
                $away = (int)($match['hrac2_id'] ?? 0);
                if (($home > 0) === ($away > 0)) continue;
                $winner = $home > 0 ? $home : $away;
                $matchId = (int)$match['id'];
                $setWinner->bind_param('ii', $winner, $matchId);
                $setWinner->execute();
                if ($match['next_match_id'] && in_array($match['next_slot'], ['hrac1', 'hrac2'], true)) {
                    $slot = $match['next_slot'] === 'hrac1' ? 'hrac1_id' : 'hrac2_id';
                    $nextId = (int)$match['next_match_id'];
                    $advance = $conn->prepare("UPDATE turnaj_zapasy SET {$slot}=? WHERE id=?");
                    $advance->bind_param('ii', $winner, $nextId);
                    $advance->execute();
                    $advance->close();
                }
            }
            $setWinner->close();
            $status = $conn->prepare("UPDATE turnaje SET stav='probiha' WHERE id=?");
            $status->bind_param('i', $turnajId);
            $status->execute();
            $status->close();
            $conn->commit();
            header('Location: /liga-app/admin/pohar.php?rocnik_id='.$seasonId.'&message='.rawurlencode('Ruční los byl dokončen a turnaj spuštěn.'));
            exit;
        }

        if ($action === 'start') {
            if ($hasBracket) throw new RuntimeException('Turnaj už byl vylosován.');
            $velikost = (int)$turnaj['velikost_pavouka'];
            $playersStmt = $conn->prepare(
                'SELECT th.hrac_id, th.volny_los FROM turnaj_hraci th
                 JOIN hraci_unikatni_jmena u ON u.libovolne_id=th.hrac_id
                 WHERE th.turnaj_id=?
                 ORDER BY th.nasazeni IS NULL, th.nasazeni, u.jmeno'
            );
            $playersStmt->bind_param('i', $turnajId);
            $playersStmt->execute();
            $playerRows = $playersStmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $playersStmt->close();
            $playerCount = count($playerRows);
            if ($playerCount < 2 || $playerCount > $velikost) {
                throw new RuntimeException('Počet hráčů musí být mezi 2 a velikostí pavouka.');
            }
            if ($playerCount <= intdiv($velikost, 2)) {
                throw new RuntimeException('Pro tento počet hráčů zvolte menší velikost pavouka.');
            }
            $requiredByes = $velikost - $playerCount;
            $byePlayers = [];
            $regularPlayers = [];
            $orderById = [];
            foreach ($playerRows as $index => $playerRow) {
                $playerId = (int)$playerRow['hrac_id'];
                $orderById[$playerId] = $index;
                if ((int)$playerRow['volny_los'] === 1) $byePlayers[] = $playerId;
                else $regularPlayers[] = $playerId;
            }
            if (count($byePlayers) !== $requiredByes) {
                throw new RuntimeException(
                    'Pro pavouka pro '.$velikost.' hráčů a '.$playerCount.' účastníků označte přesně '.
                    $requiredByes.' '.($requiredByes === 1 ? 'volný los' : 'volné losy').'.'
                );
            }

            $isRandom = ($turnaj['los_typ'] ?? '') === 'nahodny';
            if ($isRandom) {
                shuffle($byePlayers);
                shuffle($regularPlayers);
            }
            $pairings = [];
            foreach ($byePlayers as $playerId) {
                $pairings[] = ['h1' => $playerId, 'h2' => 0, 'order' => $orderById[$playerId]];
            }
            while ($regularPlayers) {
                $h1 = array_shift($regularPlayers);
                $h2 = array_pop($regularPlayers);
                $pairings[] = [
                    'h1' => $h1,
                    'h2' => $h2,
                    'order' => min($orderById[$h1], $orderById[$h2]),
                ];
            }
            if ($isRandom) shuffle($pairings);
            else usort($pairings, static fn($a, $b) => $a['order'] <=> $b['order']);

            $conn->begin_transaction();
            generujSportovniPavouk($conn, $turnajId, $velikost, false);
            $matchesStmt = $conn->prepare('SELECT id, poradi, next_match_id, next_slot FROM turnaj_zapasy WHERE turnaj_id=? AND kolo=1 ORDER BY poradi');
            $matchesStmt->bind_param('i', $turnajId);
            $matchesStmt->execute();
            $matches = $matchesStmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $matchesStmt->close();
            $update = $conn->prepare('UPDATE turnaj_zapasy SET hrac1_id=?, hrac2_id=? WHERE id=?');
            $setWinner = $conn->prepare('UPDATE turnaj_zapasy SET vitez_id=? WHERE id=?');
            foreach ($matches as $index => $match) {
                $h1 = (int)($pairings[$index]['h1'] ?? 0);
                $h2 = (int)($pairings[$index]['h2'] ?? 0);
                $matchId = (int)$match['id'];
                $update->bind_param('iii', $h1, $h2, $matchId);
                $update->execute();
                if (($h1 > 0) xor ($h2 > 0)) {
                    $winner = $h1 > 0 ? $h1 : $h2;
                    $setWinner->bind_param('ii', $winner, $matchId);
                    $setWinner->execute();
                    if ($match['next_match_id'] && in_array($match['next_slot'], ['hrac1', 'hrac2'], true)) {
                        $column = $match['next_slot'] === 'hrac1' ? 'hrac1_id' : 'hrac2_id';
                        $advance = $conn->prepare("UPDATE turnaj_zapasy SET {$column}=? WHERE id=?");
                        $nextId = (int)$match['next_match_id'];
                        $advance->bind_param('ii', $winner, $nextId);
                        $advance->execute();
                        $advance->close();
                    }
                }
            }
            $update->close();
            $setWinner->close();
            $status = $conn->prepare("UPDATE turnaje SET stav='probiha' WHERE id=?");
            $status->bind_param('i', $turnajId);
            $status->execute();
            $status->close();
            $conn->commit();
            header('Location: /liga-app/admin/pohar.php?rocnik_id='.$seasonId.'&message='.rawurlencode('Turnaj byl vylosován a spuštěn.'));
            exit;
        }

        if ($action === 'finish') {
            $stmt = $conn->prepare("UPDATE turnaje SET stav='ukonceno' WHERE id=?");
            $stmt->bind_param('i', $turnajId);
            $stmt->execute();
            $stmt->close();
            header('Location: /liga-app/admin/pohar.php?rocnik_id='.$seasonId.'&message='.rawurlencode('Turnaj byl označen jako ukončený.'));
            exit;
        }
    } catch (Throwable $e) {
        try { $conn->rollback(); } catch (Throwable $ignored) {}
        $error = $e->getMessage();
    }
    $turnaj = $seasonId > 0 ? poharTurnajProRocnik($conn, $seasonId) : null;
}

$hasBracket = false;
$hasPlayedMatches = false;
$participants = [];
$allPlayers = [];
$participantMap = [];
if ($turnaj) {
    $turnajId = (int)$turnaj['id'];
    $stmt = $conn->prepare('SELECT COUNT(*) FROM turnaj_zapasy WHERE turnaj_id=?');
    $stmt->bind_param('i', $turnajId);
    $stmt->execute();
    $hasBracket = (int)$stmt->get_result()->fetch_row()[0] > 0;
    $stmt->close();

    $stmt = $conn->prepare(
        'SELECT COUNT(*) FROM turnaj_zapasy
          WHERE turnaj_id=? AND (skore1 IS NOT NULL OR skore2 IS NOT NULL OR vitez_id IS NOT NULL)'
    );
    $stmt->bind_param('i', $turnajId);
    $stmt->execute();
    $hasPlayedMatches = (int)$stmt->get_result()->fetch_row()[0] > 0;
    $stmt->close();

    $stmt = $conn->prepare('SELECT hrac_id, nasazeni, volny_los FROM turnaj_hraci WHERE turnaj_id=?');
    $stmt->bind_param('i', $turnajId);
    $stmt->execute();
    foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $row) $participantMap[(int)$row['hrac_id']] = $row;
    $stmt->close();

    $stmt = $conn->prepare(
        'SELECT u.libovolne_id, u.jmeno,
                EXISTS(SELECT 1 FROM hraci_v_sezone hvs WHERE hvs.hrac_id=u.libovolne_id AND hvs.rocnik_id=?) AS v_sezone
         FROM hraci_unikatni_jmena u
         ORDER BY v_sezone DESC, u.jmeno'
    );
    $stmt->bind_param('i', $seasonId);
    $stmt->execute();
    $allPlayers = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}
$manualDraft = $hasBracket && !$hasPlayedMatches && ($turnaj['stav'] ?? '') === 'priprava';
$participantsEditable = !$hasBracket || $manualDraft;
$legy = json_decode((string)($turnaj['legy_json'] ?? ''), true);
if (!is_array($legy)) $legy = ['1'=>3, '2'=>3, '3'=>3, '4'=>3, '5'=>4, '6'=>4];
$terminy = json_decode((string)($turnaj['terminy_json'] ?? ''), true);
if (!is_array($terminy)) $terminy = [];
$roundLabels = [1=>'1. kolo', 2=>'2. kolo', 3=>'Osmifinále', 4=>'Čtvrtfinále', 5=>'Semifinále', 6=>'Finále'];
$csrf = csrf_token();
?>
<!doctype html><html lang="cs"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Správa Prezidentského poháru</title><link rel="stylesheet" href="/liga-app/assets/admin.css?v=2"><link rel="stylesheet" href="/liga-app/assets/admin-theme.css?v=1"><script src="/liga-app/assets/admin-theme.js?v=1"></script>
<style>.pohar-player{display:grid;grid-template-columns:auto 1fr 90px minmax(105px,auto);gap:10px;align-items:center;padding:8px 0;border-bottom:1px solid var(--admin-border)}.pohar-player input[type=number]{width:90px}.pohar-bye{display:flex;align-items:center;gap:6px;font-weight:700}.pohar-terms{display:grid;gap:8px}.pohar-term{display:grid;gap:8px}.admin-field textarea{width:100%;min-height:92px;border:1px solid var(--admin-border);border-radius:11px;padding:10px 12px;font:inherit}.admin-help{color:var(--admin-muted);font-size:.9rem}.admin-danger-zone{border-color:#efb6bd}@media(max-width:600px){.pohar-player{grid-template-columns:auto 1fr 90px}.pohar-bye{grid-column:2/4}}@media(min-width:720px){.pohar-term{grid-template-columns:minmax(160px,1fr) 2fr}}</style>
</head><body class="admin-body"><main class="admin-shell">
<div class="admin-top"><a href="/liga-app/admin/index.php">← Administrace</a><a href="/liga-app/index.php">Veřejná část</a></div>
<h1 class="admin-title">Prezidentský pohár</h1><p class="admin-subtitle">Nastavení turnaje, účastníků, losu a pravidel pro každou sezonu.</p>
<?php if ($message): ?><div class="admin-alert admin-alert--success"><?= h($message) ?></div><?php endif; ?>
<?php if ($error): ?><div class="admin-alert admin-alert--danger"><?= h($error) ?></div><?php endif; ?>

<section class="admin-card" style="margin-bottom:14px"><form method="get"><div class="admin-field" style="margin:0"><label for="season">Sezona</label><select id="season" name="rocnik_id" onchange="this.form.submit()"><?php foreach ($seasons as $item): ?><option value="<?= (int)$item['id'] ?>" <?= (int)$item['id']===$seasonId?'selected':'' ?>><?= h($item['nazev']) ?></option><?php endforeach; ?></select></div></form></section>

<?php if (!$turnaj): ?>
<section class="admin-card"><h2>Vytvořit pohár pro sezonu <?= h($season['nazev'] ?? '') ?></h2><p>Vytvoří se prázdná příprava. Účastníky i dvojice můžete doplnit později podle fyzického losování.</p><form method="post"><input type="hidden" name="csrf" value="<?= h($csrf) ?>"><input type="hidden" name="action" value="create"><input type="hidden" name="rocnik_id" value="<?= $seasonId ?>"><button class="admin-btn" type="submit">Vytvořit prázdný Prezidentský pohár</button></form></section>
<?php else: ?>
<div class="admin-alert">Stav: <strong><?= $manualDraft ? 'Příprava ručního losu' : ($hasBracket ? (($turnaj['stav'] ?? '') === 'ukonceno' ? 'Ukončeno' : 'Spuštěno') : 'Příprava') ?></strong><?php if ($manualDraft): ?> · Účastníky i dvojice prvního kola můžete stále upravovat.<?php elseif ($hasBracket): ?> · Po spuštění už nelze měnit účastníky ani velikost pavouka.<?php endif; ?></div>

<section class="admin-card" style="margin-bottom:14px"><h2>Text a pravidla turnaje</h2><form method="post"><input type="hidden" name="csrf" value="<?= h($csrf) ?>"><input type="hidden" name="action" value="save_config"><input type="hidden" name="rocnik_id" value="<?= $seasonId ?>">
<div class="admin-field"><label for="name">Hlavní nadpis</label><input id="name" name="nazev" maxlength="255" required value="<?= h($turnaj['nazev']) ?>"></div>
<div class="admin-field"><label for="intro">Úvodní popis</label><textarea id="intro" name="uvod" required><?= h($turnaj['uvod']) ?></textarea></div>
<div class="admin-field"><label for="mode">Herní mód</label><input id="mode" name="herni_mod" maxlength="500" required value="<?= h($turnaj['herni_mod']) ?>"></div>
<div class="admin-field"><label for="system">Systém</label><input id="system" name="system_hry" maxlength="500" required value="<?= h($turnaj['system_hry']) ?>"></div>
<div class="admin-grid admin-grid--two"><div class="admin-field"><label for="draw">Způsob losu</label><select id="draw" name="los_typ"><option value="nasazeny" <?= ($turnaj['los_typ']??'')==='nasazeny'?'selected':'' ?>>Podle nasazení</option><option value="nahodny" <?= ($turnaj['los_typ']??'')==='nahodny'?'selected':'' ?>>Zcela náhodně</option></select></div><div class="admin-field"><label for="draw-text">Text u položky Los 1. kola</label><input id="draw-text" name="los_popis" maxlength="500" required value="<?= h($turnaj['los_popis']) ?>"></div></div>
<div class="admin-field"><label for="size">Velikost pavouka</label><select id="size" name="velikost_pavouka" <?= $hasBracket?'disabled':'' ?>><?php foreach ([4,8,16,32,64] as $size): ?><option value="<?= $size ?>" <?= (int)$turnaj['velikost_pavouka']===$size?'selected':'' ?>><?= $size ?> hráčů</option><?php endforeach; ?></select><?php if ($hasBracket): ?><input type="hidden" name="velikost_pavouka" value="<?= (int)$turnaj['velikost_pavouka'] ?>"><?php endif; ?></div>
<h3>Vítězné legy podle kola</h3><div class="admin-grid admin-grid--stats"><?php foreach ($roundLabels as $round=>$label): ?><div class="admin-field"><label for="legs-<?= $round ?>"><?= h($label) ?></label><input id="legs-<?= $round ?>" type="number" name="legy[<?= $round ?>]" min="1" max="15" value="<?= (int)($legy[(string)$round] ?? 3) ?>"></div><?php endforeach; ?></div>
<h3>Termíny zobrazené v hlavičce</h3><div class="pohar-terms"><?php for ($i=0;$i<6;$i++): ?><div class="pohar-term"><div class="admin-field"><input aria-label="Označení termínu" name="termin_label[<?= $i ?>]" placeholder="TOP 64" value="<?= h($terminy[$i]['label'] ?? '') ?>"></div><div class="admin-field"><input aria-label="Text termínu" name="termin_text[<?= $i ?>]" placeholder="odehrát do 1. 3. 2027" value="<?= h($terminy[$i]['text'] ?? '') ?>"></div></div><?php endfor; ?></div>
<button class="admin-btn" type="submit">Uložit text a pravidla</button></form></section>

<section class="admin-card" style="margin-bottom:14px"><h2>Účastníci (<?= count($participantMap) ?>)</h2><p class="admin-help">Nabídka obsahuje celou databázi hráčů; hráči aktuální sezony jsou označeni. Pro fyzický los stačí zaškrtnout účastníky, jejich dvojice se doplní až v prázdném pavouku.</p><?php if (!$manualDraft): ?><p class="admin-alert">Při automatickém losu je pro současné nastavení potřeba označit <strong><?= max(0, (int)$turnaj['velikost_pavouka'] - count($participantMap)) ?></strong> volných losů.</p><?php endif; ?><div class="admin-field"><label for="player-filter">Hledat hráče</label><input id="player-filter" type="search" placeholder="Začněte psát jméno"></div><form method="post" id="players-form"><input type="hidden" name="csrf" value="<?= h($csrf) ?>"><input type="hidden" name="action" value="save_players"><input type="hidden" name="rocnik_id" value="<?= $seasonId ?>"><div id="players-list"><?php foreach ($allPlayers as $player): $pid=(int)$player['libovolne_id']; $participant=$participantMap[$pid]??null; ?><label class="pohar-player" data-player="<?= h(mb_strtolower($player['jmeno'])) ?>"><input type="checkbox" name="hraci[]" value="<?= $pid ?>" <?= $participant!==null?'checked':'' ?> <?= !$participantsEditable?'disabled':'' ?>><span><?= h($player['jmeno']) ?><?= !empty($player['v_sezone']) ? ' · tato sezona' : '' ?></span><input type="number" name="nasazeni[<?= $pid ?>]" min="1" max="64" placeholder="Pořadí" value="<?= h($participant['nasazeni'] ?? '') ?>" <?= !$participantsEditable?'disabled':'' ?>><span class="pohar-bye"><input type="checkbox" name="volny_los[<?= $pid ?>]" value="1" <?= !empty($participant['volny_los'])?'checked':'' ?> <?= !$participantsEditable?'disabled':'' ?>> Volný los</span></label><?php endforeach; ?></div><?php if ($participantsEditable): ?><button class="admin-btn" style="margin-top:14px" type="submit">Uložit seznam účastníků</button><?php endif; ?></form></section>

<?php if (!$hasBracket): ?>
<section class="admin-card admin-danger-zone">
  <h2>Připravit pavouka</h2>
  <p>Pro osobní losování vytvořte prázdný pavouk a dvojice potom vyplňte ručně. Automatický způsob zůstává dostupný jako druhá možnost.</p>
  <div class="admin-actions">
    <form method="post" onsubmit="return confirm('Vytvořit prázdný pavouk pro ruční doplnění dvojic?')"><input type="hidden" name="csrf" value="<?= h($csrf) ?>"><input type="hidden" name="action" value="start_manual"><input type="hidden" name="rocnik_id" value="<?= $seasonId ?>"><button class="admin-btn" type="submit">Vytvořit prázdný pavouk</button></form>
    <form method="post" onsubmit="return confirm('Opravdu provést automatický los a spustit turnaj?')"><input type="hidden" name="csrf" value="<?= h($csrf) ?>"><input type="hidden" name="action" value="start"><input type="hidden" name="rocnik_id" value="<?= $seasonId ?>"><button class="admin-btn admin-btn--secondary" type="submit">Automaticky vylosovat a spustit</button></form>
  </div>
</section>
<?php elseif ($manualDraft): ?>
<section class="admin-card admin-danger-zone">
  <h2>Ruční fyzický los</h2>
  <p>Otevřete první kolo a doplňte dvojice přesně podle fyzického losování. Účastníky lze až do dokončení losu stále měnit.</p>
  <div class="admin-actions">
    <a class="admin-btn" href="/liga-app/pohar/pohar_1kolo_admin.php?id=<?= (int)$turnaj['id'] ?>">Doplnit dvojice 1. kola</a>
    <a class="admin-btn admin-btn--secondary" href="/liga-app/pohar/pohar_turnaj.php?id=<?= (int)$turnaj['id'] ?>">Náhled prázdného pavouka</a>
  </div>
</section>
<?php else: ?>
<section class="admin-card"><div class="admin-actions"><a class="admin-btn" href="/liga-app/pohar/pohar_turnaj.php?id=<?= (int)$turnaj['id'] ?>">Zobrazit turnaj</a><?php if (($turnaj['stav']??'')!=='ukonceno'): ?><form method="post"><input type="hidden" name="csrf" value="<?= h($csrf) ?>"><input type="hidden" name="action" value="finish"><input type="hidden" name="rocnik_id" value="<?= $seasonId ?>"><button class="admin-btn admin-btn--secondary" type="submit">Označit jako ukončený</button></form><?php endif; ?></div></section>
<?php endif; ?>
<?php endif; ?>
</main><script>const filter=document.getElementById('player-filter');filter?.addEventListener('input',()=>{const value=filter.value.toLocaleLowerCase('cs');document.querySelectorAll('[data-player]').forEach(row=>row.hidden=!row.dataset.player.includes(value));});</script></body></html>
