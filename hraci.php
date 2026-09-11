<?php
require __DIR__ . "/liga-app/db.php";

$sql = "
    SELECT klubove_cislo, jmeno, prezdivka
    FROM seznam_hracu_web
    ORDER BY klubove_cislo
";

$hraci = [];
$nacteniSelhalo = false;
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $hraci[] = $row;
    }
} else {
    $nacteniSelhalo = true;
    error_log('Šipky Třešť: nepodařilo se načíst veřejný seznam hráčů.');
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
            <li><a href="/">Domů</a></li>
            <li><a href="hraci.php" aria-current="page">Hráči</a></li>
            <li><a href="https://sipkytrest.cz/liga-app" target="_blank" rel="noopener noreferrer">Ligová aplikace</a></li>
            <li><a href="/liga-app/rezervace.php">Rezervace terčů</a></li>
            <li><a href="https://www.stedar.org/alms/league/league.view" target="_blank" rel="noopener noreferrer">Liga Vysočina</a></li>
            <li><a href="https://www.facebook.com/groups/1075319810414488" target="_blank" rel="noopener noreferrer">Facebook</a></li>
        </ul>
    </nav>
    <div class="hero">
        <div class="hero-logo">
            <a href="/">
                <img src="img/logo.png" alt="Šipky Třešť" class="logo-image" width="284" height="264">
            </a>
        </div>
    </div>
</header>

<main class="main-content">

<section class="section feature">
    <h1>Hráči klubu</h1>

    <table class="players-table">
        <caption class="sr-only">Seznam hráčů klubu, jejich klubových čísel a přezdívek</caption>
        <thead>
            <tr>
                <th>Klubové číslo</th>
                <th>Jméno</th>
                <th>Přezdívka</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($hraci as $hrac): ?>
                <tr>
                    <td><?= htmlspecialchars((string) $hrac['klubove_cislo'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string) $hrac['jmeno'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string) ($hrac['prezdivka'] ?: "—"), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$hraci): ?>
                <tr>
                    <td colspan="3" class="players-empty">
                        <?= $nacteniSelhalo
                            ? 'Seznam hráčů se teď nepodařilo načíst. Zkuste to prosím později.'
                            : 'V seznamu zatím nejsou žádní hráči.' ?>
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</section>

</main>

<footer class="site-footer">
    <p>© <?= date('Y') ?> Šipky Třešť</p>
</footer>

</body>
</html>
