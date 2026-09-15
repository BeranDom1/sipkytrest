<?php
require __DIR__ . "/liga-app/db.php";

$hraci = [];
$nacteniSelhalo = false;
$result = $conn->query("
    SELECT klubove_cislo, jmeno, prezdivka, bydliste, vek
    FROM seznam_hracu_web
    WHERE jmeno NOT LIKE '[SKRYTY:%'
    ORDER BY CAST(klubove_cislo AS UNSIGNED), klubove_cislo
");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $hraci[] = $row;
    }
} else {
    $nacteniSelhalo = true;
    error_log('Šipky Třešť: nepodařilo se načíst veřejný seznam hráčů. '.$conn->error);
}
?>


<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <title>Hráči – Šipky Třešť</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Seznam hráčů klubu Šipky Třešť">
    <link rel="canonical" href="https://sipkytrest.cz/hraci.php">
    <meta property="og:type" content="website">
    <meta property="og:locale" content="cs_CZ">
    <meta property="og:title" content="Hráči – Šipky Třešť">
    <meta property="og:description" content="Seznam hráčů klubu Šipky Třešť">
    <meta property="og:url" content="https://sipkytrest.cz/hraci.php">
    <meta property="og:image" content="https://sipkytrest.cz/img/logo.png">
    <meta name="theme-color" content="#0b1220">
    <link rel="icon" href="img/logo.png" type="image/png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css?v=<?= (int)@filemtime(__DIR__.'/style.css') ?>">
</head>

<body>

<header class="site-header">
    <nav class="main-nav" aria-label="Hlavní navigace">
        <ul>
            <li><a href="/#vanocni-turnaj-2026">Vánoční turnaj 2026</a></li>
            <li><a href="hraci.php" aria-current="page">Hráči</a></li>
            <li><a href="https://sipkytrest.cz/liga-app" target="_blank" rel="noopener noreferrer">Ligová aplikace</a></li>
            <li><a href="/liga-app/rezervace.php">Rezervace terčů</a></li>
            <li><a href="https://www.stedar.org/alms/league/league.view" target="_blank" rel="noopener noreferrer">Liga Vysočina</a></li>
            <li>
                <a href="https://www.facebook.com/groups/1075319810414488"
                   class="main-nav__facebook" target="_blank" rel="noopener noreferrer"
                   aria-label="Facebook skupina Šipky Třešť" title="Facebook – Šipky Třešť">
                    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                        <path d="M13.7 21v-8h2.7l.4-3.1h-3.1v-2c0-.9.3-1.5 1.6-1.5H17V3.6c-.8-.1-1.6-.2-2.4-.2-2.4 0-4.1 1.5-4.1 4.2v2.3H7.8V13h2.7v8h3.2Z"/>
                    </svg>
                </a>
            </li>
        </ul>
    </nav>
    <div class="hero">
        <div class="hero-logo">
            <a href="https://www.sipkytrest.cz/" aria-label="Šipky Třešť – přejít na úvodní stránku" title="Úvodní stránka">
                <img src="img/logo.png" alt="Šipky Třešť" class="logo-image" width="284" height="264">
            </a>
        </div>
    </div>
</header>

<main class="main-content">

<section class="section feature">
    <h1>Hráči klubu</h1>

    <div class="players-table-wrap">
    <table class="players-table">
        <caption class="sr-only">Seznam hráčů klubu, jejich klubových čísel, přezdívek, bydliště a věku</caption>
        <thead>
            <tr>
                <th>Klubové číslo</th>
                <th>Jméno</th>
                <th>Přezdívka</th>
                <th>Bydliště</th>
                <th>Věk</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($hraci as $hrac): ?>
                <tr>
                    <td><?= htmlspecialchars((string) $hrac['klubove_cislo'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string) $hrac['jmeno'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string) ($hrac['prezdivka'] ?: "—"), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string) ($hrac['bydliste'] ?: "—"), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></td>
                    <td><?= $hrac['vek'] !== null ? (int)$hrac['vek'] : "—" ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$hraci): ?>
                <tr>
                    <td colspan="5" class="players-empty">
                        <?= $nacteniSelhalo
                            ? 'Seznam hráčů se teď nepodařilo načíst. Zkuste to prosím později.'
                            : 'V seznamu zatím nejsou žádní hráči.' ?>
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
    </div>
</section>

</main>

<footer class="site-footer">
    <p>© <?= date('Y') ?> Šipky Třešť</p>
</footer>

</body>
</html>
