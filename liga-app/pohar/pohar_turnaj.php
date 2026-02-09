<?php
$title = 'Prezidentský pohár';
$hideRocnikDropdown = true;

require __DIR__ . '/../header.php';
require __DIR__ . '/pohar_funkce.php';

$turnaj_id = (int)($_GET['id'] ?? 0);

$isEditor = isset($_SESSION['role'])
    && in_array($_SESSION['role'], ['admin', 'stat_editor'], true);

if ($turnaj_id <= 0) {
    die('Neplatné ID turnaje');
}

function zobrazHraceNeboPlaceholder(
    mysqli $conn,
    array $z,
    string $slot,
    array $placeholderMap
): string {
    if ($z[$slot] === 0) {
        return 'Volný los';
    }

    if (!empty($z[$slot])) {
        return htmlspecialchars(getJmenoHraca($conn, (int)$z[$slot]));
    }

    $short = $slot === 'hrac1_id' ? 'hrac1' : 'hrac2';
    return $placeholderMap[$z['id']][$short] ?? '— nevyplněno —';
}
function oznaceniZapasu(int $kolo, int $poradi): string
{
    return match ($kolo) {
        1 => "K1.$poradi",
        2 => "K2.$poradi",
        3 => "OF$poradi",
        4 => "ČF$poradi",
        5 => "SF$poradi",
        6 => "F",
        default => "Z$kolo.$poradi",
    };
}
function sklonujZapas(int $pocet): string
{
    if ($pocet === 1) {
        return 'zápas';
    }
    if ($pocet >= 2 && $pocet <= 4) {
        return 'zápasy';
    }
    return 'zápasů';
}


/* ===== TURNÁJ ===== */
$stmt = $conn->prepare("SELECT * FROM turnaje WHERE id = ?");
$stmt->bind_param("i", $turnaj_id);
$stmt->execute();
$turnaj = $stmt->get_result()->fetch_assoc();

if (!$turnaj) {
    die('Turnaj nenalezen');
}

/* =========================================================
 * ⭐ AUTOMATICKÉ VYTVOŘENÍ PAVOUKA (POUZE JEDNOU)
 * ========================================================= */
$stmt = $conn->prepare("
    SELECT COUNT(*) 
    FROM turnaj_zapasy 
    WHERE turnaj_id = ?
");
$stmt->bind_param("i", $turnaj_id);
$stmt->execute();
$pocetZapasu = (int)$stmt->get_result()->fetch_row()[0];

if ($pocetZapasu === 0) {
    // ⚠️ POZOR: zavolá se jen jednou
    generujSportovniPavouk($conn, $turnaj_id, 64);
}

/* ===== HRÁČI PRO SELECT (1. KOLO) ===== */
$hraciSelect = [];
$stmt = $conn->prepare("
    SELECT u.libovolne_id, u.jmeno
    FROM hraci_v_sezone hvs
    JOIN hraci_unikatni_jmena u ON u.libovolne_id = hvs.hrac_id
    WHERE hvs.rocnik_id = ?
    ORDER BY u.jmeno
");
$stmt->bind_param("i", $turnaj['rocnik_id']);
$stmt->execute();
$res = $stmt->get_result();
while ($r = $res->fetch_assoc()) {
    $hraciSelect[] = $r;
}

$nazvyKol = [
    1 => '1. kolo',
    2 => '2. kolo',
    3 => 'Osmifinále',
    4 => 'Čtvrtfinále',
    5 => 'Semifinále',
    6 => 'Finále',
];

/* ===== ZÁPASY ===== */
$stmt = $conn->prepare("
    SELECT *
    FROM turnaj_zapasy
    WHERE turnaj_id = ?
    ORDER BY kolo, poradi
");
$stmt->bind_param("i", $turnaj_id);
$stmt->execute();
$res = $stmt->get_result();

$zapasyPoKolech = [];
while ($row = $res->fetch_assoc()) {
    $zapasyPoKolech[$row['kolo']][] = $row;
}

// mapa: [zapas_id][hrac1|hrac2] => "Vítěz zápasu X"
$placeholderMap = [];

foreach ($zapasyPoKolech as $koloZapasy) {
    foreach ($koloZapasy as $z) {
        if ($z['next_match_id'] && $z['next_slot']) {
            $ozn = oznaceniZapasu((int)$z['kolo'], (int)$z['poradi']);

$placeholderMap[$z['next_match_id']][$z['next_slot']] =
    'Vítěz ' . $ozn;

        }
    }
}

?>

<link rel="stylesheet" href="/liga-app/pohar/pohar.css">
<script src="/liga-app/pohar/pohar.js" defer></script>

<div class="turnaj-page"> 
<header class="turnaj-header"> 
<h1>🎯 <?= htmlspecialchars($turnaj['nazev']) ?></h1> 
</header>

 <h4>ℹ️ Informace o turnaji</h4> 
 <p> 
 Prezidentský pohár se hraje vyřazovacím způsobem (KO). Poražený v turnaji končí, vítěz postupuje do dalšího kola. 
 </p> 
 <ul> 
 <li>🎯 <strong>Herní mód:</strong> Cricket (cut-throut) na 3 vítězné legy, semifinále a finále na 4 vítězné legy </li> 
 <li>🏆 <strong>Systém:</strong> KO pavouk (64 → 32 → 16 → 8 → 4 → 2 → vítěz)</li> 
 <li>🎲 <strong>Los 1. kola:</strong> Prvních 32 nasazených hráčů + los</li> 
 </ul>
<ul>
<li><strong>TOP 64</strong> – odehrát do 1.3. 2026</li> 
<li><strong>TOP 32</strong> – odehrát do 1.4. 2026</li> 
<li><strong>TOP 16</strong> – odehrát do 25. 4. 2026</li> 
<li><strong>TOP 8</strong> – odehrát do 20. 5. 2026</li>
<li><strong>Grande finále</strong> (semifinále 1, semifinále 2, finále) – pátek 29. 5. 18:00 (sobota 30. 5. 18:00)</li> 
</ul>

<?php foreach ($zapasyPoKolech as $kolo => $zapasy): ?>
<section class="kolo">

    <?php
$nazevKola = $nazvyKol[$kolo] ?? ($kolo . '. kolo');
?>
<button class="kolo-toggle" onclick="toggleKolo(this)">
    <?php $pocet = count($zapasy); ?>
<?= htmlspecialchars($nazevKola) ?>
 (<?= $pocet ?> <?= sklonujZapas($pocet) ?>)

</button>

    <div class="kolo-body">

    <?php foreach ($zapasy as $z): ?>

        <?php
        $jeUlozeno = (
    $z['skore1'] !== null &&
    $z['skore2'] !== null &&
    $z['vitez_id'] !== null
);
        $oznaceniZapasu = oznaceniZapasu((int)$kolo, (int)$z['poradi']);
  $jmeno1 = zobrazHraceNeboPlaceholder($conn, $z, 'hrac1_id', $placeholderMap);
$jmeno2 = zobrazHraceNeboPlaceholder($conn, $z, 'hrac2_id', $placeholderMap);
            $winner1 = false;
$winner2 = false;

if ($z['skore1'] !== null && $z['skore2'] !== null) {
    if ((int)$z['skore1'] > (int)$z['skore2']) {
        $winner1 = true;
    } elseif ((int)$z['skore2'] > (int)$z['skore1']) {
        $winner2 = true;
    }
}
        ?>

        <div class="zapas <?= ($isEditor && $jeUlozeno) ? 'zapas-ulozen' : '' ?>">

    <!-- OZNAČENÍ ZÁPASU -->
<div class="zapas-id">
    <?= htmlspecialchars($oznaceniZapasu) ?>

    <?php if ($isEditor && $jeUlozeno): ?>
        <span class="ulozeno-ikona" title="Výsledek uložen">💾</span>
    <?php endif; ?>

    <?php if (
        $z['vitez_id'] &&
        ($z['hrac1_id'] === 0 || $z['hrac2_id'] === 0)
    ): ?>
        <span class="bye-label">Volný los</span>

        <?php if ($isEditor): ?>
            <button
                type="button"
                class="btn-cancel-bye"
                data-zapas-id="<?= (int)$z['id'] ?>"
            >
                ❌ Zrušit
            </button>
        <?php endif; ?>
    <?php endif; ?>
</div>

    <!-- HRÁČ 1 -->
    <div class="hrac hrac-left">
        <?php if ($isEditor && $kolo === 1): ?>
            <select class="hrac-select"
                    data-zapas-id="<?= (int)$z['id'] ?>"
                    data-slot="hrac1_id">
                <option value="">— nevyplněno —</option>
<option value="BYE1" <?= $z['hrac1_id'] === 0 ? 'selected' : '' ?>>
    🎟 Volný los 1
</option>

    <option value="BYE2" <?= $z['hrac1_id'] === 0 ? 'selected' : '' ?>>
    🎟 Volný los 2
</option>
    </option>
                <?php foreach ($hraciSelect as $h): ?>
                    <option value="<?= (int)$h['libovolne_id'] ?>"
                        <?= $z['hrac1_id'] == $h['libovolne_id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($h['jmeno']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        <?php else: ?>
            <span class="jmeno <?= $winner1 ? 'winner' : '' ?>">
                <?= htmlspecialchars($jmeno1) ?>
            </span>
        <?php endif; ?>
    </div>

    <!-- SKÓRE -->
    <div class="skore">
        <?php if ($isEditor && $z['hrac1_id'] && $z['hrac2_id']): ?>
            <input type="number" min="0"  class="score-input"
                   value="<?= (int)$z['skore1'] ?>"
                   data-zapas-id="<?= (int)$z['id'] ?>"
                   data-slot="skore1">
            <span>:</span>
            <input type="number" min="0" class="score-input"
                   value="<?= (int)$z['skore2'] ?>"
                   data-zapas-id="<?= (int)$z['id'] ?>"
                   data-slot="skore2">
        <?php else: ?>
            <span><?= $z['skore1'] ?? '–' ?> : <?= $z['skore2'] ?? '–' ?></span>
        <?php endif; ?>
    </div>

    <!-- HRÁČ 2 -->
    <div class="hrac hrac-right">
        <?php if ($isEditor && $kolo === 1): ?>
            <select class="hrac-select"
                    data-zapas-id="<?= (int)$z['id'] ?>"
                    data-slot="hrac2_id">
                <option value="">— nevyplněno —</option>
<option value="BYE1" <?= $z['hrac1_id'] === 0 ? 'selected' : '' ?>>
    🎟 Volný los 1
</option>

    <option value="BYE2" <?= $z['hrac2_id'] === 0 ? 'selected' : '' ?>>
    🎟 Volný los 2
</option>
    </option>
                <?php foreach ($hraciSelect as $h): ?>
                    <option value="<?= (int)$h['libovolne_id'] ?>"
                        <?= $z['hrac2_id'] == $h['libovolne_id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($h['jmeno']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        <?php else: ?>
            <span class="jmeno <?= $winner2 ? 'winner' : '' ?>">
                <?= htmlspecialchars($jmeno2) ?>
            </span>
        <?php endif; ?>
    </div>

            <?php if ($isEditor && $z['hrac1_id'] && $z['hrac2_id']): ?>
                <div class="actions">
    <button class="btn-save-score" data-zapas-id="<?= (int)$z['id'] ?>">
        💾 Uložit
    </button>
    <button class="btn-reset-zapas" data-zapas-id="<?= (int)$z['id'] ?>">
        ❌ Zrušit
    </button>
</div>

            <?php endif; ?>

        </div>

    <?php endforeach; ?>

    </div>
</section>
<?php endforeach; ?>

</div>

<?php require __DIR__ . '/../footer.php'; ?>
