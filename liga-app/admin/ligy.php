<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../security/csrf.php';
require_once __DIR__ . '/season_helpers.php';

function league_h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$seasons = $conn->query('SELECT id, nazev, stav, locked FROM rocniky ORDER BY id DESC')->fetch_all(MYSQLI_ASSOC);
$seasonId = (int)($_REQUEST['rocnik_id'] ?? ($_SESSION['rocnik_id'] ?? ($seasons[0]['id'] ?? 0)));
$season = admin_season($conn, $seasonId);
if (!$season) {
    http_response_code(404);
    exit('Sezóna nebyla nalezena.');
}
$editable = admin_season_is_editable($season);
$message = isset($_GET['created']) ? 'Sezóna byla vytvořena. Nyní nastavte její ligy.' : '';
$error = '';

$sponsorDir = __DIR__ . '/../sponzor/';
$sponsorFiles = array_values(array_filter(
    scandir($sponsorDir) ?: [],
    static fn($file) => preg_match('~\.(png|jpg|jpeg|webp)$~i', $file)
));

function league_validate_logo(string $logo, array $allowed): string
{
    $logo = basename(trim($logo));
    return in_array($logo, $allowed, true) ? $logo : '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_POST['csrf'] ?? '')) {
        http_response_code(403);
        exit('CSRF ověření selhalo.');
    }
    if (!$editable) {
        http_response_code(403);
        exit('Ligy lze měnit pouze u sezóny ve stavu Příprava.');
    }

    $action = (string)($_POST['action'] ?? '');
    try {
        if ($action === 'add') {
            $name = trim((string)($_POST['nazev'] ?? ''));
            $logo = league_validate_logo((string)($_POST['logo'] ?? ''), $sponsorFiles);
            $alt = trim((string)($_POST['alt'] ?? ''));
            if ($name === '' || mb_strlen($name) > 255) {
                throw new RuntimeException('Zadejte platný název ligy.');
            }

            $conn->begin_transaction();
            $max = $conn->query('SELECT COALESCE(MAX(cislo), 0) AS cislo, COALESCE(MAX(poradi), 0) AS poradi FROM ligy')->fetch_assoc();
            $number = (int)$max['cislo'] + 1;
            $order = (int)$max['poradi'] + 1;
            $insertLeague = $conn->prepare('INSERT INTO ligy (nazev, cislo, poradi) VALUES (?, ?, ?)');
            $insertLeague->bind_param('sii', $name, $number, $order);
            $insertLeague->execute();
            $leagueId = (int)$conn->insert_id;
            $insertLeague->close();

            $insertName = $conn->prepare('INSERT INTO ligy_nazvy (rocnik_id, liga_id, nazev) VALUES (?, ?, ?)');
            $insertName->bind_param('iis', $seasonId, $leagueId, $name);
            $insertName->execute();
            $insertName->close();
            if ($logo !== '') {
                $insertLogo = $conn->prepare('INSERT INTO ligy_loga (rocnik_id, liga_id, logo, alt) VALUES (?, ?, ?, ?)');
                $insertLogo->bind_param('iiss', $seasonId, $leagueId, $logo, $alt);
                $insertLogo->execute();
                $insertLogo->close();
            }
            $conn->commit();
            $message = 'Nová liga byla přidána pouze do této sezóny.';
        }

        if ($action === 'restore') {
            $leagueId = (int)($_POST['liga_id'] ?? 0);
            $stmt = $conn->prepare(
                'SELECT l.nazev FROM ligy l
                 WHERE l.id=? AND NOT EXISTS (
                   SELECT 1 FROM ligy_nazvy ln WHERE ln.rocnik_id=? AND ln.liga_id=l.id
                 )'
            );
            $stmt->bind_param('ii', $leagueId, $seasonId);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if (!$row) throw new RuntimeException('Vybranou ligu nelze do sezóny přidat.');
            $name = trim((string)($_POST['nazev'] ?? '')) ?: (string)$row['nazev'];
            $insert = $conn->prepare('INSERT INTO ligy_nazvy (rocnik_id, liga_id, nazev) VALUES (?, ?, ?)');
            $insert->bind_param('iis', $seasonId, $leagueId, $name);
            $insert->execute();
            $insert->close();
            $message = 'Existující liga byla přidána do sezóny.';
        }

        if ($action === 'save') {
            $leagueId = (int)($_POST['liga_id'] ?? 0);
            $name = trim((string)($_POST['nazev'] ?? ''));
            $logo = league_validate_logo((string)($_POST['logo'] ?? ''), $sponsorFiles);
            $alt = trim((string)($_POST['alt'] ?? ''));
            if ($name === '' || mb_strlen($name) > 255) throw new RuntimeException('Zadejte platný název ligy.');
            $member = $conn->prepare('SELECT 1 FROM ligy_nazvy WHERE rocnik_id=? AND liga_id=?');
            $member->bind_param('ii', $seasonId, $leagueId);
            $member->execute();
            $exists = (bool)$member->get_result()->fetch_row();
            $member->close();
            if (!$exists) throw new RuntimeException('Liga do této sezóny nepatří.');

            $conn->begin_transaction();
            $updateName = $conn->prepare('UPDATE ligy_nazvy SET nazev=? WHERE rocnik_id=? AND liga_id=?');
            $updateName->bind_param('sii', $name, $seasonId, $leagueId);
            $updateName->execute();
            $updateName->close();
            if ($logo === '') {
                $deleteLogo = $conn->prepare('DELETE FROM ligy_loga WHERE rocnik_id=? AND liga_id=?');
                $deleteLogo->bind_param('ii', $seasonId, $leagueId);
                $deleteLogo->execute();
                $deleteLogo->close();
            } else {
                $saveLogo = $conn->prepare(
                    'INSERT INTO ligy_loga (rocnik_id, liga_id, logo, alt) VALUES (?, ?, ?, ?)
                     ON DUPLICATE KEY UPDATE logo=VALUES(logo), alt=VALUES(alt)'
                );
                $saveLogo->bind_param('iiss', $seasonId, $leagueId, $logo, $alt);
                $saveLogo->execute();
                $saveLogo->close();
            }
            $conn->commit();
            $message = 'Název a logo ligy byly uloženy.';
        }

        if ($action === 'remove') {
            $leagueId = (int)($_POST['liga_id'] ?? 0);
            $matchStmt = $conn->prepare('SELECT COUNT(*) FROM zapasy WHERE rocnik_id=? AND liga_id=?');
            $matchStmt->bind_param('ii', $seasonId, $leagueId);
            $matchStmt->execute();
            $matchCount = (int)$matchStmt->get_result()->fetch_row()[0];
            $matchStmt->close();
            if ($matchCount > 0) {
                throw new RuntimeException('Ligu nelze odebrat, dokud pro ni existuje rozpis. Nejprve smažte neodehraný rozpis.');
            }

            $conn->begin_transaction();
            $removePlayers = $conn->prepare('DELETE FROM hraci_v_sezone WHERE rocnik_id=? AND liga_id=?');
            $removePlayers->bind_param('ii', $seasonId, $leagueId);
            $removePlayers->execute();
            $removedPlayers = $removePlayers->affected_rows;
            $removePlayers->close();
            $removeLogo = $conn->prepare('DELETE FROM ligy_loga WHERE rocnik_id=? AND liga_id=?');
            $removeLogo->bind_param('ii', $seasonId, $leagueId);
            $removeLogo->execute();
            $removeLogo->close();
            $removeName = $conn->prepare('DELETE FROM ligy_nazvy WHERE rocnik_id=? AND liga_id=?');
            $removeName->bind_param('ii', $seasonId, $leagueId);
            $removeName->execute();
            if ($removeName->affected_rows !== 1) throw new RuntimeException('Liga do této sezóny nepatří.');
            $removeName->close();
            $conn->commit();
            $message = 'Liga byla odebrána pouze z této sezóny. Odebraní hráči zůstali v databázi';
            if ($removedPlayers > 0) $message .= ' (ze sezóny odebráno '.$removedPlayers.' hráčů)';
            $message .= '.';
        }
    } catch (Throwable $e) {
        try { $conn->rollback(); } catch (Throwable $ignored) {}
        $error = $e->getMessage();
    }
}

$leagueStmt = $conn->prepare(
    'SELECT l.id, l.cislo, l.poradi, ln.nazev, ll.logo, ll.alt,
            COUNT(DISTINCT hs.hrac_id) AS players
     FROM ligy_nazvy ln
     JOIN ligy l ON l.id=ln.liga_id
     LEFT JOIN ligy_loga ll ON ll.rocnik_id=ln.rocnik_id AND ll.liga_id=ln.liga_id
     LEFT JOIN hraci_v_sezone hs ON hs.rocnik_id=ln.rocnik_id AND hs.liga_id=ln.liga_id
     WHERE ln.rocnik_id=?
     GROUP BY l.id, l.cislo, l.poradi, ln.nazev, ll.logo, ll.alt
     ORDER BY l.poradi, l.cislo'
);
$leagueStmt->bind_param('i', $seasonId);
$leagueStmt->execute();
$leagues = $leagueStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$leagueStmt->close();

$availableStmt = $conn->prepare(
    'SELECT l.id, l.cislo, l.nazev FROM ligy l
     WHERE NOT EXISTS (SELECT 1 FROM ligy_nazvy ln WHERE ln.rocnik_id=? AND ln.liga_id=l.id)
     ORDER BY l.poradi, l.cislo'
);
$availableStmt->bind_param('i', $seasonId);
$availableStmt->execute();
$available = $availableStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$availableStmt->close();
$csrf = csrf_token();
?>
<!doctype html><html lang="cs"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Správa lig</title><link rel="stylesheet" href="/liga-app/assets/admin.css?v=<?= (int)@filemtime(dirname(__DIR__).'/assets/admin.css') ?>"><link rel="stylesheet" href="/liga-app/assets/admin-theme.css?v=<?= (int)@filemtime(dirname(__DIR__).'/assets/admin-theme.css') ?>"><script src="/liga-app/assets/admin-theme.js?v=1"></script>
<style>.league-editor{display:grid;gap:14px}.league-logo-preview{width:100px;height:58px;object-fit:contain;background:#fff;border:1px solid var(--admin-border);border-radius:9px;padding:5px}.league-meta{color:var(--admin-muted)}@media(min-width:760px){.league-editor{grid-template-columns:100px 1fr}.league-editor__form{display:grid;grid-template-columns:2fr 1.2fr 1.4fr auto;gap:10px;align-items:end}.league-editor__actions{grid-column:1/-1}}</style>
</head><body class="admin-body"><main class="admin-shell">
<div class="admin-top"><a href="/liga-app/admin/sezona.php?rocnik_id=<?= $seasonId ?>">← <?= league_h($season['nazev']) ?></a><a href="/liga-app/admin/index.php">Dashboard</a></div>
<h1 class="admin-title">Správa lig</h1><p class="admin-subtitle"><?= league_h($season['nazev']) ?> · <?= count($leagues) ?> lig · <span class="admin-badge admin-badge--<?= league_h($season['stav']) ?>"><?= league_h(admin_status_label($season['stav'])) ?></span></p>
<?php if ($message): ?><div class="admin-alert admin-alert--success"><?= league_h($message) ?></div><?php endif; ?><?php if ($error): ?><div class="admin-alert admin-alert--danger"><?= league_h($error) ?></div><?php endif; ?>
<?php if (!$editable): ?><div class="admin-alert">Tato sezóna je historická nebo aktivní. Její ligy, názvy, loga i zařazení hráčů jsou pouze ke čtení.</div><?php endif; ?>

<?php if ($editable): ?><div class="admin-grid admin-grid--two" style="margin-bottom:14px"><section class="admin-card"><h2>Přidat novou ligu</h2><form method="post"><input type="hidden" name="csrf" value="<?= league_h($csrf) ?>"><input type="hidden" name="action" value="add"><input type="hidden" name="rocnik_id" value="<?= $seasonId ?>"><div class="admin-field"><label for="new-name">Název ligy</label><input id="new-name" name="nazev" required maxlength="255" placeholder="Např. 4. liga sk. B"></div><div class="admin-field"><label for="new-logo">Logo</label><select id="new-logo" name="logo"><option value="">— bez loga —</option><?php foreach ($sponsorFiles as $file): ?><option value="<?= league_h($file) ?>"><?= league_h($file) ?></option><?php endforeach; ?></select></div><div class="admin-field"><label for="new-alt">Popis loga</label><input id="new-alt" name="alt" maxlength="255"></div><button class="admin-btn" type="submit">Přidat novou ligu</button></form></section>
<section class="admin-card"><h2>Přidat dříve použitou ligu</h2><?php if ($available): ?><form method="post"><input type="hidden" name="csrf" value="<?= league_h($csrf) ?>"><input type="hidden" name="action" value="restore"><input type="hidden" name="rocnik_id" value="<?= $seasonId ?>"><div class="admin-field"><label for="existing">Liga</label><select id="existing" name="liga_id"><?php foreach ($available as $item): ?><option value="<?= (int)$item['id'] ?>"><?= league_h($item['nazev']) ?></option><?php endforeach; ?></select></div><div class="admin-field"><label for="existing-name">Nový sezoní název (volitelně)</label><input id="existing-name" name="nazev" maxlength="255"></div><button class="admin-btn admin-btn--secondary" type="submit">Přidat do sezóny</button></form><?php else: ?><p>Všechny existující ligy už v sezoně jsou.</p><?php endif; ?></section></div><?php endif; ?>

<section class="admin-card"><h2>Ligy v této sezoně</h2><div class="admin-list"><?php if (!$leagues): ?><p>Zatím nebyla přidána žádná liga.</p><?php endif; ?><?php foreach ($leagues as $league): ?><article class="admin-season league-editor"><div><?php if ($league['logo']): ?><img class="league-logo-preview" src="/liga-app/sponzor/<?= league_h($league['logo']) ?>" alt="<?= league_h($league['alt']) ?>"><?php else: ?><div class="league-logo-preview"></div><?php endif; ?><div class="league-meta"><?= (int)$league['players'] ?> hráčů</div></div><form method="post" class="league-editor__form"><input type="hidden" name="csrf" value="<?= league_h($csrf) ?>"><input type="hidden" name="action" value="save"><input type="hidden" name="rocnik_id" value="<?= $seasonId ?>"><input type="hidden" name="liga_id" value="<?= (int)$league['id'] ?>"><div class="admin-field"><label>Název</label><input name="nazev" maxlength="255" required value="<?= league_h($league['nazev']) ?>" <?= $editable?'':'disabled' ?>></div><div class="admin-field"><label>Logo</label><select name="logo" <?= $editable?'':'disabled' ?>><option value="">— bez loga —</option><?php foreach ($sponsorFiles as $file): ?><option value="<?= league_h($file) ?>" <?= $file===$league['logo']?'selected':'' ?>><?= league_h($file) ?></option><?php endforeach; ?></select></div><div class="admin-field"><label>Popis loga</label><input name="alt" maxlength="255" value="<?= league_h($league['alt']) ?>" <?= $editable?'':'disabled' ?>></div><?php if ($editable): ?><button class="admin-btn" type="submit">Uložit</button><div class="league-editor__actions"><button class="admin-btn admin-btn--danger" type="submit" name="action" value="remove" onclick="return confirm('Odebrat ligu pouze z této sezóny? Hráči budou ze sezóny odebráni, ale v databázi zůstanou.')">Odebrat ze sezóny</button></div><?php endif; ?></form></article><?php endforeach; ?></div></section>
<?php if ($editable): ?><div class="admin-actions" style="margin-top:14px"><a class="admin-btn" href="/liga-app/admin/sezona.php?rocnik_id=<?= $seasonId ?>">Zařadit hráče do lig</a><a class="admin-btn admin-btn--secondary" href="/liga-app/admin/rozpis_sezony.php?rocnik_id=<?= $seasonId ?>">Kontrola rozpisu</a></div><?php endif; ?>
</main></body></html>
