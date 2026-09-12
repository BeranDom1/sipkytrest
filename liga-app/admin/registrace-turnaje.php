<?php
declare(strict_types=1);

require_once __DIR__.'/../db.php';
require_once __DIR__.'/_auth_turnaj_registrace.php';
require_once __DIR__.'/../security/csrf.php';
require_once __DIR__.'/../registrace-schema.php';

try {
    zajistiSchemaRegistraci($conn);
} catch (Throwable $e) {
    error_log('Schéma registrací: '.$e->getMessage());
    http_response_code(503);
    exit('Databázi registrací se nepodařilo připravit.');
}

function reg_admin_h(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function reg_admin_event(mysqli $conn, int $id): ?array
{
    $stmt = $conn->prepare('SELECT * FROM registracni_turnaje WHERE id=? LIMIT 1');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}

function reg_admin_csv_value($value): string
{
    $value = (string)$value;
    return preg_match('/^[=+\-@\t\r]/', $value) ? "'".$value : $value;
}

function reg_admin_custom_fields(mysqli $conn, int $eventId): array
{
    $stmt = $conn->prepare('SELECT * FROM turnaj_form_pole WHERE turnaj_id=? ORDER BY poradi,id');
    $stmt->bind_param('i', $eventId);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $rows;
}

function reg_admin_custom_values(mysqli $conn, int $eventId): array
{
    $stmt = $conn->prepare(
        'SELECT h.registrace_id,h.pole_id,h.hodnota FROM turnaj_registrace_hodnoty h
         JOIN turnaj_registrace r ON r.id=h.registrace_id WHERE r.turnaj_id=?'
    );
    $stmt->bind_param('i', $eventId);
    $stmt->execute();
    $map = [];
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) $map[(int)$row['registrace_id']][(int)$row['pole_id']] = (string)$row['hodnota'];
    $stmt->close();
    return $map;
}

function reg_admin_options(string $options): array
{
    $rows = preg_split('/\R/u', $options) ?: [];
    return array_values(array_unique(array_filter(array_map('trim', $rows), static fn($row) => $row !== '')));
}

function reg_admin_answers_html(array $fields, array $answerMap, int $registrationId): string
{
    $items = '';
    foreach ($fields as $field) {
        $answer = $answerMap[$registrationId][(int)$field['id']] ?? '';
        if ($answer === '') continue;
        if ($field['typ'] === 'ano_ne') $answer = $answer === '1' ? 'Ano' : 'Ne';
        $items .= '<dt>'.reg_admin_h($field['popisek']).'</dt><dd>'.nl2br(reg_admin_h($answer)).'</dd>';
    }
    return $items === '' ? '' : '<dl class="custom-answers">'.$items.'</dl>';
}

$eventsResult = $conn->query(
    "SELECT t.*,
       (SELECT COUNT(*) FROM turnaj_registrace r WHERE r.turnaj_id=t.id AND r.stav IN ('nova','potvrzena')) AS obsazeno,
       (SELECT COUNT(*) FROM turnaj_registrace r WHERE r.turnaj_id=t.id AND r.stav='nahradnik') AS nahradnici
     FROM registracni_turnaje t ORDER BY t.datum DESC, t.id DESC"
);
if (!$eventsResult) {
    http_response_code(503);
    exit('Databáze registrací není připravená. Nejdříve spusťte příslušnou migraci.');
}
$events = $eventsResult->fetch_all(MYSQLI_ASSOC);
$eventId = (int)($_REQUEST['turnaj_id'] ?? ($events[0]['id'] ?? 0));
$event = $eventId > 0 ? reg_admin_event($conn, $eventId) : null;
$customFields = $event ? reg_admin_custom_fields($conn, $eventId) : [];
$message = trim((string)($_GET['message'] ?? ''));
$error = '';

if (($_GET['export'] ?? '') === 'csv' && $event) {
    $stmt = $conn->prepare('SELECT * FROM turnaj_registrace WHERE turnaj_id=? ORDER BY created_at,id');
    $stmt->bind_param('i', $eventId);
    $stmt->execute();
    $rows = $stmt->get_result();
    $filename = preg_replace('/[^a-z0-9-]+/i', '-', (string)$event['slug']).'-registrace.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="'.$filename.'"');
    echo "\xEF\xBB\xBF";
    $out = fopen('php://output', 'wb');
    $headers = ['Jméno', 'Příjmení', 'Město', 'Telefon', 'Rok narození', 'Zájem o ubytování', 'Poznámka'];
    foreach ($customFields as $field) $headers[] = (string)$field['popisek'];
    $headers[] = 'Stav';
    $headers[] = 'Přihlášeno';
    fputcsv($out, $headers, ';');
    $answerMap = reg_admin_custom_values($conn, $eventId);
    while ($row = $rows->fetch_assoc()) {
        $exportRow = [$row['jmeno'], $row['prijmeni'], $row['mesto'], $row['telefon'], $row['rok_narozeni'],
            !empty($row['zajem_ubytovani']) ? 'Ano' : 'Ne', $row['poznamka']];
        foreach ($customFields as $field) {
            $answer = $answerMap[(int)$row['id']][(int)$field['id']] ?? '';
            $exportRow[] = $field['typ'] === 'ano_ne' && $answer !== '' ? ($answer === '1' ? 'Ano' : 'Ne') : $answer;
        }
        $exportRow[] = $row['stav'];
        $exportRow[] = $row['created_at'];
        fputcsv($out, array_map('reg_admin_csv_value', $exportRow), ';');
    }
    fclose($out);
    $stmt->close();
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_POST['csrf'] ?? '')) {
        http_response_code(403);
        exit('CSRF ověření selhalo.');
    }
    $action = (string)($_POST['action'] ?? '');
    try {
        if ($action === 'create_event' || $action === 'save_event') {
            $name = trim((string)($_POST['nazev'] ?? ''));
            $slug = trim((string)($_POST['slug'] ?? ''));
            $subtitle = trim((string)($_POST['podtitul'] ?? ''));
            $date = trim((string)($_POST['datum'] ?? ''));
            $timeInfo = trim((string)($_POST['cas_info'] ?? ''));
            $place = trim((string)($_POST['misto'] ?? ''));
            $address = trim((string)($_POST['adresa'] ?? ''));
            $capacity = (int)($_POST['kapacita'] ?? 0);
            $feeValue = trim((string)($_POST['startovne'] ?? ''));
            $fee = $feeValue === '' ? null : (int)$feeValue;
            $info = trim((string)($_POST['informace'] ?? ''));
            $email = trim((string)($_POST['kontakt_email'] ?? ''));
            $phone = trim((string)($_POST['kontakt_telefon'] ?? ''));
            $showBirthYear = !empty($_POST['zobrazit_rok_narozeni']) ? 1 : 0;
            $showAccommodation = !empty($_POST['zobrazit_ubytovani']) ? 1 : 0;
            $showNote = !empty($_POST['zobrazit_poznamku']) ? 1 : 0;
            $status = (string)($_POST['stav'] ?? 'priprava');
            $deadline = trim((string)($_POST['registrace_do'] ?? ''));
            $deadline = $deadline === '' ? null : str_replace('T', ' ', $deadline).':00';
            if ($name === '' || !preg_match('/^[a-z0-9-]{1,120}$/', $slug) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                throw new RuntimeException('Vyplňte název, datum a platnou adresu odkazu.');
            }
            if ($place === '' || $address === '' || $capacity < 1 || $capacity > 5000) {
                throw new RuntimeException('Vyplňte místo, adresu a platnou kapacitu.');
            }
            if (!in_array($status, ['priprava', 'otevrena', 'uzavrena', 'archivni'], true)) $status = 'priprava';
            if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) throw new RuntimeException('Kontaktní e-mail není platný.');

            if ($action === 'create_event') {
                $stmt = $conn->prepare(
                    'INSERT INTO registracni_turnaje
                     (slug,nazev,podtitul,datum,cas_info,misto,adresa,kapacita,startovne,informace,kontakt_email,kontakt_telefon,
                      zobrazit_rok_narozeni,zobrazit_ubytovani,zobrazit_poznamku,stav,registrace_do)
                     VALUES (?, ?, NULLIF(?, \'\'), ?, NULLIF(?, \'\'), ?, ?, ?, ?, NULLIF(?, \'\'), NULLIF(?, \'\'), NULLIF(?, \'\'), ?, ?, ?, ?, ?)'
                );
                $stmt->bind_param('sssssssiisssiiiss', $slug, $name, $subtitle, $date, $timeInfo, $place, $address, $capacity, $fee, $info, $email, $phone, $showBirthYear, $showAccommodation, $showNote, $status, $deadline);
                if (!$stmt->execute()) throw new RuntimeException($stmt->errno === 1062 ? 'Tato adresa odkazu už existuje.' : 'Turnaj se nepodařilo vytvořit.');
                $eventId = (int)$conn->insert_id;
                $stmt->close();
                header('Location: /liga-app/admin/registrace-turnaje.php?turnaj_id='.$eventId.'&message='.rawurlencode('Turnaj byl vytvořen.'));
                exit;
            }

            if (!$event) throw new RuntimeException('Turnaj nebyl nalezen.');
            $stmt = $conn->prepare(
                'UPDATE registracni_turnaje SET slug=?,nazev=?,podtitul=NULLIF(?, \'\'),datum=?,cas_info=NULLIF(?, \'\'),
                 misto=?,adresa=?,kapacita=?,startovne=?,informace=NULLIF(?, \'\'),kontakt_email=NULLIF(?, \'\'),
                 kontakt_telefon=NULLIF(?, \'\'),zobrazit_rok_narozeni=?,zobrazit_ubytovani=?,zobrazit_poznamku=?,
                 stav=?,registrace_do=? WHERE id=?'
            );
            $stmt->bind_param('sssssssiisssiiissi', $slug, $name, $subtitle, $date, $timeInfo, $place, $address, $capacity, $fee, $info, $email, $phone, $showBirthYear, $showAccommodation, $showNote, $status, $deadline, $eventId);
            if (!$stmt->execute()) throw new RuntimeException($stmt->errno === 1062 ? 'Tato adresa odkazu už existuje.' : 'Nastavení se nepodařilo uložit.');
            $stmt->close();
            header('Location: /liga-app/admin/registrace-turnaje.php?turnaj_id='.$eventId.'&message='.rawurlencode('Nastavení turnaje bylo uloženo.'));
            exit;
        }

        if (!$event) throw new RuntimeException('Turnaj nebyl nalezen.');
        if ($action === 'add_field' || $action === 'save_field') {
            $fieldId = (int)($_POST['pole_id'] ?? 0);
            $label = trim((string)($_POST['popisek'] ?? ''));
            $type = (string)($_POST['typ'] ?? 'text');
            $options = trim((string)($_POST['moznosti'] ?? ''));
            $required = !empty($_POST['povinne']) ? 1 : 0;
            $visible = !empty($_POST['zobrazit']) ? 1 : 0;
            $order = max(0, min(1000, (int)($_POST['poradi'] ?? 0)));
            if ($label === '' || strlen($label) > 255) throw new RuntimeException('Vyplňte platný název pole.');
            if (!in_array($type, ['text', 'textarea', 'cislo', 'ano_ne', 'vyber'], true)) throw new RuntimeException('Neplatný typ pole.');
            if ($type === 'vyber' && count(reg_admin_options($options)) < 2) throw new RuntimeException('Pro výběr zadejte alespoň dvě možnosti, každou na samostatný řádek.');
            if ($type !== 'vyber') $options = '';
            if ($action === 'add_field') {
                $stmt = $conn->prepare(
                    'INSERT INTO turnaj_form_pole (turnaj_id,popisek,typ,moznosti,povinne,zobrazit,poradi)
                     VALUES (?,?,?,NULLIF(?, \'\'),?,?,?)'
                );
                $stmt->bind_param('isssiii', $eventId, $label, $type, $options, $required, $visible, $order);
            } else {
                if ($fieldId <= 0) throw new RuntimeException('Pole nebylo nalezeno.');
                $stmt = $conn->prepare(
                    'UPDATE turnaj_form_pole SET popisek=?,typ=?,moznosti=NULLIF(?, \'\'),povinne=?,zobrazit=?,poradi=?
                     WHERE id=? AND turnaj_id=?'
                );
                $stmt->bind_param('sssiiiii', $label, $type, $options, $required, $visible, $order, $fieldId, $eventId);
            }
            if (!$stmt->execute()) throw new RuntimeException('Pole se nepodařilo uložit.');
            $stmt->close();
            header('Location: /liga-app/admin/registrace-turnaje.php?turnaj_id='.$eventId.'&message='.rawurlencode('Pole formuláře bylo uloženo.'));
            exit;
        }
        if ($action === 'delete_field') {
            $fieldId = (int)($_POST['pole_id'] ?? 0);
            $stmt = $conn->prepare('DELETE FROM turnaj_form_pole WHERE id=? AND turnaj_id=?');
            $stmt->bind_param('ii', $fieldId, $eventId);
            $stmt->execute();
            $stmt->close();
            header('Location: /liga-app/admin/registrace-turnaje.php?turnaj_id='.$eventId.'&message='.rawurlencode('Pole a jeho uložené odpovědi byly odstraněny.'));
            exit;
        }
        if ($action === 'registration_status') {
            $registrationId = (int)($_POST['registrace_id'] ?? 0);
            $status = (string)($_POST['registrace_stav'] ?? '');
            if (!in_array($status, ['nova', 'potvrzena', 'nahradnik', 'odmitnuta', 'zrusena'], true)) throw new RuntimeException('Neplatný stav přihlášky.');
            if (in_array($status, ['nova', 'potvrzena'], true)) {
                $capacityStmt = $conn->prepare(
                    "SELECT t.kapacita,
                       (SELECT COUNT(*) FROM turnaj_registrace r WHERE r.turnaj_id=t.id AND r.id<>? AND r.stav IN ('nova','potvrzena')) obsazeno
                     FROM registracni_turnaje t WHERE t.id=?"
                );
                $capacityStmt->bind_param('ii', $registrationId, $eventId);
                $capacityStmt->execute();
                $capacity = $capacityStmt->get_result()->fetch_assoc();
                $capacityStmt->close();
                if (!$capacity || (int)$capacity['obsazeno'] >= (int)$capacity['kapacita']) throw new RuntimeException('Kapacita turnaje je naplněná. Hráče ponechte mezi náhradníky nebo nejdříve uvolněte místo.');
            }
            $stmt = $conn->prepare('UPDATE turnaj_registrace SET stav=? WHERE id=? AND turnaj_id=?');
            $stmt->bind_param('sii', $status, $registrationId, $eventId);
            $stmt->execute();
            $stmt->close();
            header('Location: /liga-app/admin/registrace-turnaje.php?turnaj_id='.$eventId.'&message='.rawurlencode('Stav přihlášky byl změněn.'));
            exit;
        }
        if ($action === 'delete_registration') {
            $registrationId = (int)($_POST['registrace_id'] ?? 0);
            $stmt = $conn->prepare('DELETE FROM turnaj_registrace WHERE id=? AND turnaj_id=?');
            $stmt->bind_param('ii', $registrationId, $eventId);
            $stmt->execute();
            $stmt->close();
            header('Location: /liga-app/admin/registrace-turnaje.php?turnaj_id='.$eventId.'&message='.rawurlencode('Přihláška byla trvale odstraněna.'));
            exit;
        }
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
    $event = $eventId > 0 ? reg_admin_event($conn, $eventId) : null;
}

$registrations = [];
$customAnswerMap = [];
$counts = ['celkem' => 0, 'nova' => 0, 'potvrzena' => 0, 'nahradnik' => 0];
if ($event) {
    $stmt = $conn->prepare(
        "SELECT * FROM turnaj_registrace WHERE turnaj_id=?
         ORDER BY FIELD(stav,'nova','potvrzena','nahradnik','odmitnuta','zrusena'), created_at, id"
    );
    $stmt->bind_param('i', $eventId);
    $stmt->execute();
    $registrations = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    foreach ($registrations as $registration) {
        $counts['celkem']++;
        if (isset($counts[$registration['stav']])) $counts[$registration['stav']]++;
    }
    $customAnswerMap = reg_admin_custom_values($conn, $eventId);
}
$publicUrl = $event ? 'https://sipkytrest.cz/turnaj/'.$event['slug'] : '';
$qrFile = $event ? dirname(__DIR__).'/img/qr-'.$event['slug'].'.svg' : '';
?>
<!doctype html><html lang="cs"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Registrace na turnaje</title>
<link rel="stylesheet" href="/liga-app/assets/admin.css?v=<?= (int)@filemtime(dirname(__DIR__).'/assets/admin.css') ?>">
<link rel="stylesheet" href="/liga-app/assets/admin-theme.css?v=<?= (int)@filemtime(dirname(__DIR__).'/assets/admin-theme.css') ?>">
<script src="/liga-app/assets/admin-theme.js?v=1" defer></script>
<style>.reg-layout{display:grid;gap:14px}.reg-event-list{display:flex;flex-wrap:wrap;gap:8px}.reg-link{overflow-wrap:anywhere}.reg-form-grid{display:grid;gap:12px}.reg-form-grid textarea{min-height:90px;resize:vertical}.admin-field textarea{width:100%;border:1px solid var(--admin-border);border-radius:11px;padding:10px 12px;background:#fff;color:var(--admin-text);font:inherit}.reg-row-actions{display:flex;gap:6px;align-items:center}.reg-row-actions select{min-height:40px;border:1px solid var(--admin-border);border-radius:9px;padding:6px;background:#fff}.reg-qr{width:180px;height:180px;background:#fff;padding:8px;border:1px solid var(--admin-border);border-radius:12px}.reg-create{margin-top:14px}.reg-create summary{cursor:pointer;font-weight:800}.reg-muted{color:var(--admin-muted);font-size:.9rem}.field-editor{border:1px solid var(--admin-border);border-radius:12px;padding:12px;margin:10px 0}.field-editor--hidden{opacity:.7}.field-editor__actions{display:flex;gap:8px;flex-wrap:wrap}.custom-answers{margin:8px 0 0;font-size:.82rem}.custom-answers dt{font-weight:800}.custom-answers dd{margin:0 0 4px}.admin-check input{width:auto;min-height:0}@media(min-width:800px){.reg-layout{grid-template-columns:minmax(280px,.8fr) minmax(0,2fr)}.reg-form-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.reg-span-2{grid-column:1/-1}}</style>
</head><body class="admin-body"><main class="admin-shell">
<div class="admin-top"><a href="<?= $registrationRole === 'admin' ? '/liga-app/admin/index.php' : '/liga-app/index.php' ?>">← <?= $registrationRole === 'admin' ? 'Administrace' : 'Veřejná část' ?></a><a href="/liga-app/logout.php">Odhlásit</a></div>
<h1 class="admin-title">Registrace na turnaje</h1><p class="admin-subtitle">Veřejné přihlášky, kapacita a náhradníci.</p>
<?php if ($message): ?><div class="admin-alert admin-alert--success"><?= reg_admin_h($message) ?></div><?php endif; ?>
<?php if ($error): ?><div class="admin-alert admin-alert--danger"><?= reg_admin_h($error) ?></div><?php endif; ?>

<section class="admin-card"><h2>Turnaje</h2><div class="reg-event-list"><?php foreach ($events as $item): ?><a class="admin-btn <?= (int)$item['id'] === $eventId ? '' : 'admin-btn--secondary' ?>" href="?turnaj_id=<?= (int)$item['id'] ?>"><?= reg_admin_h($item['nazev']) ?> (<?= (int)$item['obsazeno'] ?>/<?= (int)$item['kapacita'] ?>)</a><?php endforeach; ?></div>
<details class="reg-create"><summary>+ Vytvořit další turnaj</summary>
<form method="post" class="reg-form-grid" style="margin-top:14px"><input type="hidden" name="csrf" value="<?= reg_admin_h(csrf_token()) ?>"><input type="hidden" name="action" value="create_event">
<?php $newEvent = ['nazev'=>'','slug'=>'','podtitul'=>'','datum'=>'','cas_info'=>'','misto'=>'','adresa'=>'','kapacita'=>'192','startovne'=>'','informace'=>'','kontakt_email'=>'prihlasky@sipkytrest.cz','kontakt_telefon'=>'603 723 705','stav'=>'priprava','registrace_do'=>'']; ?>
<?php include __DIR__.'/_registrace-turnaje-form.php'; ?><button class="admin-btn reg-span-2" type="submit">Vytvořit turnaj</button></form></details></section>

<?php if ($event): ?>
<div class="reg-layout" style="margin-top:14px"><section class="admin-card"><h2>Nastavení turnaje</h2>
<form method="post" class="reg-form-grid"><input type="hidden" name="csrf" value="<?= reg_admin_h(csrf_token()) ?>"><input type="hidden" name="action" value="save_event"><input type="hidden" name="turnaj_id" value="<?= $eventId ?>">
<?php $newEvent = $event; include __DIR__.'/_registrace-turnaje-form.php'; ?><button class="admin-btn reg-span-2" type="submit">Uložit nastavení</button></form>
<hr style="border:0;border-top:1px solid var(--admin-border);margin:20px 0"><h3>Vlastní pole formuláře</h3><p class="reg-muted">Skryté pole se přestane zobrazovat, ale dřívější odpovědi zůstanou uložené. Odstranění smaže i jeho odpovědi.</p>
<?php foreach ($customFields as $field): ?><div class="field-editor <?= empty($field['zobrazit'])?'field-editor--hidden':'' ?>"><form method="post" class="reg-form-grid"><input type="hidden" name="csrf" value="<?= reg_admin_h(csrf_token()) ?>"><input type="hidden" name="action" value="save_field"><input type="hidden" name="turnaj_id" value="<?= $eventId ?>"><input type="hidden" name="pole_id" value="<?= (int)$field['id'] ?>">
<div class="admin-field"><label>Název pole</label><input name="popisek" value="<?= reg_admin_h($field['popisek']) ?>" maxlength="255" required></div><div class="admin-field"><label>Typ</label><select name="typ"><?php foreach (['text'=>'Krátký text','textarea'=>'Delší text','cislo'=>'Číslo','ano_ne'=>'Ano / Ne','vyber'=>'Výběr z možností'] as $value=>$label): ?><option value="<?= $value ?>" <?= $field['typ']===$value?'selected':'' ?>><?= $label ?></option><?php endforeach; ?></select></div><div class="admin-field reg-span-2"><label>Možnosti výběru <span class="reg-muted">(každá na nový řádek)</span></label><textarea name="moznosti"><?= reg_admin_h($field['moznosti']) ?></textarea></div><div class="admin-field"><label>Pořadí</label><input type="number" name="poradi" value="<?= (int)$field['poradi'] ?>" min="0" max="1000"></div><div class="admin-field"><label class="admin-check"><input type="checkbox" name="povinne" value="1" <?= !empty($field['povinne'])?'checked':'' ?>> Povinné</label><label class="admin-check"><input type="checkbox" name="zobrazit" value="1" <?= !empty($field['zobrazit'])?'checked':'' ?>> Zobrazit ve formuláři</label></div><div class="field-editor__actions reg-span-2"><button class="admin-btn admin-btn--secondary" type="submit">Uložit pole</button></div></form><form method="post" onsubmit="return confirm('Odstranit pole i všechny dosud uložené odpovědi?')"><input type="hidden" name="csrf" value="<?= reg_admin_h(csrf_token()) ?>"><input type="hidden" name="action" value="delete_field"><input type="hidden" name="turnaj_id" value="<?= $eventId ?>"><input type="hidden" name="pole_id" value="<?= (int)$field['id'] ?>"><button class="admin-btn admin-btn--danger" type="submit">Odstranit pole</button></form></div><?php endforeach; ?>
<details class="reg-create"><summary>+ Přidat vlastní pole</summary><form method="post" class="reg-form-grid" style="margin-top:12px"><input type="hidden" name="csrf" value="<?= reg_admin_h(csrf_token()) ?>"><input type="hidden" name="action" value="add_field"><input type="hidden" name="turnaj_id" value="<?= $eventId ?>"><div class="admin-field"><label>Název pole</label><input name="popisek" maxlength="255" required></div><div class="admin-field"><label>Typ</label><select name="typ"><option value="text">Krátký text</option><option value="textarea">Delší text</option><option value="cislo">Číslo</option><option value="ano_ne">Ano / Ne</option><option value="vyber">Výběr z možností</option></select></div><div class="admin-field reg-span-2"><label>Možnosti výběru <span class="reg-muted">(každá na nový řádek)</span></label><textarea name="moznosti"></textarea></div><div class="admin-field"><label>Pořadí</label><input type="number" name="poradi" value="10" min="0" max="1000"></div><div class="admin-field"><label class="admin-check"><input type="checkbox" name="povinne" value="1"> Povinné</label><label class="admin-check"><input type="checkbox" name="zobrazit" value="1" checked> Zobrazit ve formuláři</label></div><button class="admin-btn reg-span-2" type="submit">Přidat pole</button></form></details>
<hr style="border:0;border-top:1px solid var(--admin-border);margin:20px 0"><h3>Odkaz a QR kód</h3><p class="reg-link"><a href="<?= reg_admin_h($publicUrl) ?>" target="_blank" rel="noopener"><?= reg_admin_h($publicUrl) ?></a></p>
<?php if (is_file($qrFile)): ?><a href="/liga-app/img/<?= reg_admin_h(basename($qrFile)) ?>" download><img class="reg-qr" src="/liga-app/img/<?= reg_admin_h(basename($qrFile)) ?>" alt="QR kód registračního formuláře"></a><p class="reg-muted">Kliknutím QR kód stáhnete pro plakát.</p><?php else: ?><p class="reg-muted">Pro tento nový turnaj zatím není vytvořen obrázek QR kódu.</p><?php endif; ?>
</section>
<section><div class="admin-grid admin-grid--stats">
<div class="admin-card admin-stat"><strong><?= $counts['celkem'] ?></strong><span>celkem</span></div><div class="admin-card admin-stat"><strong><?= $counts['nova'] ?></strong><span>nových</span></div><div class="admin-card admin-stat"><strong><?= $counts['potvrzena'] ?></strong><span>potvrzených</span></div><div class="admin-card admin-stat"><strong><?= $counts['nahradnik'] ?></strong><span>náhradníků</span></div></div>
<section class="admin-card" style="margin-top:14px"><div class="admin-top"><h2 style="margin:0">Přihlášení</h2><a class="admin-btn admin-btn--secondary" href="?turnaj_id=<?= $eventId ?>&export=csv">Export CSV</a></div>
<?php if (!$registrations): ?><p class="reg-muted">Zatím nejsou uložené žádné přihlášky.</p><?php else: ?><div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Hráč</th><th>Kontakt</th><th>Rok</th><th>Ubytování</th><th>Přihlášeno</th><th>Stav</th><th></th></tr></thead><tbody>
<?php foreach ($registrations as $registration): ?><tr><td><strong><?= reg_admin_h($registration['jmeno'].' '.$registration['prijmeni']) ?></strong><br><span class="reg-muted"><?= reg_admin_h($registration['mesto']) ?><?= $registration['poznamka'] ? '<br>'.reg_admin_h($registration['poznamka']) : '' ?></span><?= reg_admin_answers_html($customFields, $customAnswerMap, (int)$registration['id']) ?></td><td><a href="tel:<?= reg_admin_h($registration['telefon_normalized']) ?>"><?= reg_admin_h($registration['telefon']) ?></a></td><td><?= $registration['rok_narozeni'] ? (int)$registration['rok_narozeni'] : '—' ?></td><td><strong><?= !empty($registration['zajem_ubytovani']) ? 'Ano' : 'Ne' ?></strong></td><td><?= reg_admin_h(date('j. n. Y H:i', strtotime($registration['created_at']))) ?></td><td><form method="post" class="reg-row-actions"><input type="hidden" name="csrf" value="<?= reg_admin_h(csrf_token()) ?>"><input type="hidden" name="action" value="registration_status"><input type="hidden" name="turnaj_id" value="<?= $eventId ?>"><input type="hidden" name="registrace_id" value="<?= (int)$registration['id'] ?>"><select name="registrace_stav" aria-label="Stav přihlášky"><?php foreach (['nova'=>'Nová','potvrzena'=>'Potvrzená','nahradnik'=>'Náhradník','odmitnuta'=>'Odmítnutá','zrusena'=>'Zrušená'] as $value=>$label): ?><option value="<?= $value ?>" <?= $registration['stav']===$value?'selected':'' ?>><?= $label ?></option><?php endforeach; ?></select><button class="admin-btn admin-btn--secondary" type="submit">Uložit</button></form></td><td><form method="post" onsubmit="return confirm('Opravdu tuto přihlášku trvale odstranit?')"><input type="hidden" name="csrf" value="<?= reg_admin_h(csrf_token()) ?>"><input type="hidden" name="action" value="delete_registration"><input type="hidden" name="turnaj_id" value="<?= $eventId ?>"><input type="hidden" name="registrace_id" value="<?= (int)$registration['id'] ?>"><button class="admin-btn admin-btn--danger" type="submit">Smazat</button></form></td></tr><?php endforeach; ?>
</tbody></table></div><?php endif; ?></section></section></div>
<?php endif; ?>
</main></body></html>
