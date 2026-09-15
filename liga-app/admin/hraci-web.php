<?php
declare(strict_types=1);

require_once __DIR__.'/../db.php';
require_once __DIR__.'/_auth.php';
require_once __DIR__.'/../security/csrf.php';

function webPlayerH(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function webPlayerText($value, int $limit): string
{
    $value = trim((string)$value);
    return function_exists('mb_substr') ? mb_substr($value, 0, $limit, 'UTF-8') : substr($value, 0, $limit);
}

function webPlayerLower(string $value): string
{
    return function_exists('mb_strtolower') ? mb_strtolower($value, 'UTF-8') : strtolower($value);
}

function nextClubNumber(array &$usedNumbers): string
{
    $next = $usedNumbers ? max(array_keys($usedNumbers)) + 1 : 1;
    if ($next > 999) {
        throw new RuntimeException('Byla vyčerpána klubová čísla 001–999.');
    }
    $usedNumbers[$next] = true;
    return str_pad((string)$next, 3, '0', STR_PAD_LEFT);
}

function mapWebPlayerProfiles(array $profileRows, array $playerMap): array
{
    $byName = [];
    foreach ($playerMap as $playerId => $player) {
        $byName[webPlayerLower(trim((string)$player['jmeno']))] = $playerId;
    }

    $legacyClubNumbers = [
        '002' => 1, '003' => 18, '006' => 35, '008' => 32,
        '018' => 9, '024' => 40, '029' => 17, '032' => 139,
        '034' => 44, '039' => 138, '043' => 30, '046' => 42,
        '053' => 36, '055' => 146, '058' => 149, '059' => 150,
    ];

    $profiles = [];
    $unlinked = [];
    foreach ($profileRows as $profile) {
        $playerId = null;
        $isVisible = true;
        if (preg_match('/^\[SKRYTY:(\d+)\]\s*/u', (string)$profile['jmeno'], $match)) {
            $playerId = (int)$match[1];
            $isVisible = false;
        } elseif (isset($legacyClubNumbers[(string)$profile['klubove_cislo']])) {
            $playerId = $legacyClubNumbers[(string)$profile['klubove_cislo']];
        } else {
            $normalizedName = webPlayerLower(trim((string)$profile['jmeno']));
            $playerId = $byName[$normalizedName] ?? null;
        }

        if ($playerId && isset($playerMap[$playerId]) && !isset($profiles[$playerId])) {
            $profile['_visible'] = $isVisible;
            $profiles[$playerId] = $profile;
        } else {
            $unlinked[] = $profile;
        }
    }

    return [$profiles, $unlinked];
}

$message = '';
$error = '';

try {
    $players = $conn->query('SELECT libovolne_id, jmeno FROM hraci_unikatni_jmena ORDER BY jmeno')->fetch_all(MYSQLI_ASSOC);
    $playerMap = [];
    foreach ($players as $player) {
        $playerMap[(int)$player['libovolne_id']] = $player;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!csrf_check($_POST['csrf'] ?? '')) {
            throw new RuntimeException('Bezpečnostní token vypršel. Obnovte stránku a zkuste to znovu.');
        }

        $visible = is_array($_POST['zobrazit'] ?? null) ? $_POST['zobrazit'] : [];
        $nicknames = is_array($_POST['prezdivka'] ?? null) ? $_POST['prezdivka'] : [];
        $residences = is_array($_POST['bydliste'] ?? null) ? $_POST['bydliste'] : [];
        $ages = is_array($_POST['vek'] ?? null) ? $_POST['vek'] : [];

        $conn->begin_transaction();
        try {
            $profileResult = $conn->query('SELECT * FROM seznam_hracu_web FOR UPDATE');
            $lockedProfileRows = $profileResult->fetch_all(MYSQLI_ASSOC);
            [$profiles] = mapWebPlayerProfiles($lockedProfileRows, $playerMap);

            $usedNumbers = [];
            foreach ($lockedProfileRows as $number) {
                $numeric = (int)$number['klubove_cislo'];
                if ($numeric > 0 && isset($usedNumbers[$numeric])) {
                    throw new RuntimeException('Klubové číslo '.str_pad((string)$numeric, 3, '0', STR_PAD_LEFT).' je v databázi vícekrát.');
                }
                if ($numeric > 0) $usedNumbers[$numeric] = true;
            }

            $update = $conn->prepare('UPDATE seznam_hracu_web
                SET jmeno=?, prezdivka=NULLIF(?, \'\'), bydliste=NULLIF(?, \'\'), vek=?
                WHERE klubove_cislo=?');
            $insert = $conn->prepare('INSERT INTO seznam_hracu_web
                (klubove_cislo,jmeno,prezdivka,bydliste,vek)
                VALUES (?, ?, NULLIF(?, \'\'), NULLIF(?, \'\'), ?)');
            if (!$update || !$insert) {
                throw new RuntimeException('Ukládání profilů se nepodařilo připravit: '.$conn->error);
            }

            foreach ($playerMap as $playerId => $player) {
                $nickname = webPlayerText($nicknames[$playerId] ?? '', 100);
                $residence = webPlayerText($residences[$playerId] ?? '', 100);
                $ageInput = trim((string)($ages[$playerId] ?? ''));
                if ($ageInput !== '' && (!ctype_digit($ageInput) || (int)$ageInput < 1 || (int)$ageInput > 120)) {
                    throw new RuntimeException('Věk u hráče „'.$player['jmeno'].'“ musí být číslo od 1 do 120.');
                }
                $age = $ageInput === '' ? null : (int)$ageInput;
                $isVisible = isset($visible[$playerId]) ? 1 : 0;
                $name = (string)$player['jmeno'];

                if (isset($profiles[$playerId])) {
                    $storedName = $isVisible ? $name : '[SKRYTY:'.$playerId.'] '.$name;
                    $clubNumber = (string)$profiles[$playerId]['klubove_cislo'];
                    $update->bind_param('sssis', $storedName, $nickname, $residence, $age, $clubNumber);
                    if (!$update->execute()) {
                        throw new RuntimeException('Profil hráče „'.$name.'“ se nepodařilo uložit: '.$update->error);
                    }
                } elseif ($isVisible) {
                    $clubNumber = nextClubNumber($usedNumbers);
                    $insert->bind_param('ssssi', $clubNumber, $name, $nickname, $residence, $age);
                    if (!$insert->execute()) {
                        throw new RuntimeException('Profil hráče „'.$name.'“ se nepodařilo vytvořit: '.$insert->error);
                    }
                }
            }
            $update->close();
            $insert->close();
            $conn->commit();
            $message = 'Výběr hráčů a veřejné profily byly uloženy.';
        } catch (Throwable $exception) {
            $conn->rollback();
            throw $exception;
        }
    }

    $profileRows = $conn->query('SELECT * FROM seznam_hracu_web ORDER BY CAST(klubove_cislo AS UNSIGNED), klubove_cislo')->fetch_all(MYSQLI_ASSOC);
    [$profiles, $unlinkedProfiles] = mapWebPlayerProfiles($profileRows, $playerMap);
} catch (Throwable $exception) {
    $error = $exception->getMessage();
    $players = $players ?? [];
    $profiles = $profiles ?? [];
    $unlinkedProfiles = $unlinkedProfiles ?? [];
}

$csrf = csrf_token();
?>
<!doctype html>
<html lang="cs">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Editace hráčů na webu</title>
    <link rel="stylesheet" href="/liga-app/assets/admin.css?v=<?= (int)@filemtime(dirname(__DIR__).'/assets/admin.css') ?>">
    <link rel="stylesheet" href="/liga-app/assets/admin-theme.css?v=<?= (int)@filemtime(dirname(__DIR__).'/assets/admin-theme.css') ?>">
    <script src="/liga-app/assets/admin-theme.js?v=1"></script>
</head>
<body class="admin-body">
<main class="admin-shell">
    <div class="admin-top"><a href="/liga-app/admin/index.php">← Administrace</a><a href="/liga-app/logout.php">Odhlásit</a></div>
    <h1 class="admin-title">Editace hráčů na webu</h1>
    <p class="admin-subtitle">Zaškrtnutí určuje, kdo se zobrazí na stránce Hráči. Novému profilu se automaticky přidělí další volné klubové číslo.</p>

    <?php if ($message): ?><div class="admin-alert admin-alert--success"><?= webPlayerH($message) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="admin-alert admin-alert--danger"><?= webPlayerH($error) ?></div><?php endif; ?>

    <?php if (!$error || $players): ?>
    <section class="admin-card">
        <div class="web-players-toolbar">
            <div class="admin-field">
                <label for="web-player-filter">Hledat hráče</label>
                <input id="web-player-filter" type="search" placeholder="Jméno, číslo, přezdívka nebo bydliště">
            </div>
            <p><strong><?= count($profiles) ?></strong> hráčů má veřejný profil</p>
        </div>
        <form method="post" id="web-players-form">
            <input type="hidden" name="csrf" value="<?= webPlayerH($csrf) ?>">
            <div class="admin-table-wrap">
                <table class="admin-table web-players-table">
                    <thead><tr><th>Zobrazit</th><th>Číslo</th><th>Hráč</th><th>Přezdívka</th><th>Bydliště</th><th>Věk</th></tr></thead>
                    <tbody>
                    <?php foreach ($players as $player):
                        $playerId = (int)$player['libovolne_id'];
                        $profile = $profiles[$playerId] ?? null;
                        $searchText = implode(' ', [$player['jmeno'], $profile['klubove_cislo'] ?? '', $profile['prezdivka'] ?? '', $profile['bydliste'] ?? '']);
                    ?>
                        <tr data-web-player="<?= webPlayerH(webPlayerLower($searchText)) ?>">
                            <td><label class="web-player-toggle"><input type="checkbox" name="zobrazit[<?= $playerId ?>]" value="1" <?= !empty($profile['_visible']) ? 'checked' : '' ?>><span class="sr-only">Zobrazit hráče <?= webPlayerH($player['jmeno']) ?></span></label></td>
                            <td class="web-player-number"><?= webPlayerH($profile['klubove_cislo'] ?? 'automaticky') ?></td>
                            <td><strong><?= webPlayerH($player['jmeno']) ?></strong></td>
                            <td><input name="prezdivka[<?= $playerId ?>]" maxlength="100" value="<?= webPlayerH($profile['prezdivka'] ?? '') ?>" aria-label="Přezdívka hráče <?= webPlayerH($player['jmeno']) ?>"></td>
                            <td><input name="bydliste[<?= $playerId ?>]" maxlength="100" value="<?= webPlayerH($profile['bydliste'] ?? '') ?>" aria-label="Bydliště hráče <?= webPlayerH($player['jmeno']) ?>"></td>
                            <td><input type="number" name="vek[<?= $playerId ?>]" min="1" max="120" value="<?= webPlayerH(isset($profile['vek']) ? (string)$profile['vek'] : '') ?>" aria-label="Věk hráče <?= webPlayerH($player['jmeno']) ?>"></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="web-players-save"><button class="admin-btn" type="submit">Uložit hráče na webu</button></div>
        </form>
    </section>
    <?php endif; ?>

    <?php if ($unlinkedProfiles): ?>
    <section class="admin-card" style="margin-top:14px">
        <h2>Nepřiřazené starší záznamy</h2>
        <p class="admin-alert admin-alert--danger">Tyto profily zůstávají na veřejném webu, ale jejich jméno nebylo nalezeno v centrální databázi. Nejprve sjednoťte jméno hráče v databázi.</p>
        <ul><?php foreach ($unlinkedProfiles as $profile): ?><li><strong><?= webPlayerH($profile['klubove_cislo']) ?></strong> — <?= webPlayerH($profile['jmeno']) ?></li><?php endforeach; ?></ul>
    </section>
    <?php endif; ?>
</main>
<script>
document.getElementById('web-player-filter')?.addEventListener('input', (event) => {
    const query = event.target.value.trim().toLocaleLowerCase('cs');
    document.querySelectorAll('[data-web-player]').forEach((row) => {
        row.hidden = query !== '' && !row.dataset.webPlayer.includes(query);
    });
});
</script>
</body>
</html>
