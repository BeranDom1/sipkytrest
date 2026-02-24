<?php
require __DIR__ . "/liga-app/db.php";

$sql = "
    SELECT klubove_cislo, jmeno, prezdivka
    FROM seznam_hracu_web
    ORDER BY klubove_cislo
";

$result = $conn->query($sql);

$hraci = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $hraci[] = $row;
    }
}
?>


<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <title>Hráči – Šipky Třešť</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Seznam hráčů klubu Šipky Třešť">
    <link rel="stylesheet" href="style.css">
</head>

<body>

<header class="site-header">
    <nav class="main-nav" aria-label="Hlavní navigace">
        <ul>
            <li><a href="/">Domů</a></li>
            <li><a href="hraci.php" aria-current="page">Hráči</a></li>
            <li><a href="https://sipkytrest.cz/liga-app" target="_blank" rel="noopener noreferrer">Ligová aplikace</a></li>
            <li><a href="https://www.stedar.org/alms/league/league.view" target="_blank" rel="noopener noreferrer">Liga Vysočina</a></li>
            <li><a href="https://www.facebook.com/groups/1075319810414488" target="_blank" rel="noopener noreferrer">Facebook</a></li>
        </ul>
    </nav>
    <div class="hero">
        <div class="hero-logo">
            <a href="/">
                <img src="img/logo.png" alt="Šipky Třešť" class="logo-image">
            </a>
        </div>
    </div>
</header>

<main class="main-content">

<section class="section feature">
    <h2>Hráči klubu</h2>

    <table class="players-table">
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
                    <td><?= htmlspecialchars($hrac['klubove_cislo']) ?></td>
                    <td><?= htmlspecialchars($hrac['jmeno']) ?></td>
                    <td><?= htmlspecialchars($hrac['prezdivka'] ?: "—") ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</section>

</main>

<footer class="site-footer">
    <p>© 2026 Šipky Třešť</p>
</footer>

</body>
</html>
