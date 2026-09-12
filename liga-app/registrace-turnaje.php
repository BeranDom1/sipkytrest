<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name('SIPKYREGSESSID');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

require_once __DIR__.'/db.php';
require_once __DIR__.'/security/csrf.php';
require_once __DIR__.'/registrace-schema.php';

try {
    zajistiSchemaRegistraci($conn);
} catch (Throwable $e) {
    error_log('Schéma registrací: '.$e->getMessage());
    header('X-Registration-Error-Code: '.(string)$e->getCode());
    http_response_code(503);
    exit('Registrace se právě připravuje. Zkuste to prosím později.');
}

function registration_h(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function registration_phone(string $phone): string
{
    $digits = preg_replace('/\D+/', '', $phone) ?? '';
    if (str_starts_with($digits, '00420')) $digits = substr($digits, 5);
    if (strlen($digits) === 12 && str_starts_with($digits, '420')) $digits = substr($digits, 3);
    return $digits;
}

function registration_field_options(?string $options): array
{
    $rows = preg_split('/\R/u', (string)$options) ?: [];
    return array_values(array_unique(array_filter(array_map('trim', $rows), static fn($row) => $row !== '')));
}

$slug = trim((string)($_GET['turnaj'] ?? $_POST['turnaj'] ?? ''));
if (!preg_match('/^[a-z0-9-]{1,120}$/', $slug)) {
    http_response_code(404);
    exit('Turnaj nebyl nalezen.');
}

$eventStmt = $conn->prepare('SELECT * FROM registracni_turnaje WHERE slug=? LIMIT 1');
if (!$eventStmt) {
    http_response_code(503);
    exit('Registrace se právě připravuje. Zkuste to prosím později.');
}
$eventStmt->bind_param('s', $slug);
$eventStmt->execute();
$event = $eventStmt->get_result()->fetch_assoc();
$eventStmt->close();
if (!$event) {
    http_response_code(404);
    exit('Turnaj nebyl nalezen.');
}

$customFieldsStmt = $conn->prepare('SELECT * FROM turnaj_form_pole WHERE turnaj_id=? AND zobrazit=1 ORDER BY poradi, id');
$eventId = (int)$event['id'];
$customFieldsStmt->bind_param('i', $eventId);
$customFieldsStmt->execute();
$customFields = $customFieldsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$customFieldsStmt->close();

$activeCountStmt = $conn->prepare("SELECT COUNT(*) FROM turnaj_registrace WHERE turnaj_id=? AND stav IN ('nova','potvrzena')");
$activeCountStmt->bind_param('i', $eventId);
$activeCountStmt->execute();
$activeCount = (int)$activeCountStmt->get_result()->fetch_row()[0];
$activeCountStmt->close();
$remaining = max(0, (int)$event['kapacita'] - $activeCount);

$deadlinePassed = !empty($event['registrace_do']) && strtotime((string)$event['registrace_do']) < time();
$isOpen = $event['stav'] === 'otevrena' && !$deadlinePassed;
$errors = [];
$values = [
    'jmeno' => '', 'prijmeni' => '', 'mesto' => '', 'telefon' => '',
    'rok_narozeni' => '', 'ubytovani' => '', 'poznamka' => '',
];
$customValues = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach (array_keys($values) as $field) $values[$field] = trim((string)($_POST[$field] ?? ''));
    $postedCustom = is_array($_POST['vlastni'] ?? null) ? $_POST['vlastni'] : [];

    if (!csrf_check($_POST['csrf'] ?? '')) $errors[] = 'Platnost formuláře vypršela. Obnovte stránku a zkuste to znovu.';
    if (trim((string)($_POST['website'] ?? '')) !== '') $errors[] = 'Registraci se nepodařilo odeslat.';
    if (!$isOpen) $errors[] = 'Registrace na tento turnaj jsou uzavřené.';
    if (strlen($values['jmeno']) < 2 || strlen($values['jmeno']) > 100) $errors[] = 'Vyplňte prosím jméno.';
    if (strlen($values['prijmeni']) < 2 || strlen($values['prijmeni']) > 100) $errors[] = 'Vyplňte prosím příjmení.';
    if (strlen($values['mesto']) < 2 || strlen($values['mesto']) > 150) $errors[] = 'Vyplňte prosím město.';
    if (empty($event['zobrazit_poznamku'])) $values['poznamka'] = '';
    if (strlen($values['poznamka']) > 1000) $errors[] = 'Poznámka je příliš dlouhá.';

    $phoneNormalized = registration_phone($values['telefon']);
    if (strlen($phoneNormalized) < 9 || strlen($phoneNormalized) > 15) $errors[] = 'Vyplňte platné telefonní číslo.';

    $birthYear = null;
    if (!empty($event['zobrazit_rok_narozeni']) && $values['rok_narozeni'] !== '') {
        $birthYear = filter_var($values['rok_narozeni'], FILTER_VALIDATE_INT);
        if ($birthYear === false || $birthYear < 1900 || $birthYear > (int)date('Y')) {
            $errors[] = 'Rok narození není platný.';
        }
    }
    if (empty($_POST['souhlas'])) $errors[] = 'Pro odeslání je potřeba potvrdit souhlas se zpracováním údajů.';
    if (!empty($event['zobrazit_ubytovani']) && !in_array($values['ubytovani'], ['0', '1'], true)) {
        $errors[] = 'Vyberte prosím, zda máte zájem o ubytování.';
    }

    foreach ($customFields as $field) {
        $fieldId = (int)$field['id'];
        $raw = $postedCustom[$fieldId] ?? '';
        $raw = is_scalar($raw) ? trim((string)$raw) : '';
        $customValues[$fieldId] = $raw;
        $label = (string)$field['popisek'];
        if (!empty($field['povinne']) && $raw === '') $errors[] = 'Vyplňte pole „'.$label.'“.';
        if ($raw === '') continue;
        if (strlen($raw) > ($field['typ'] === 'textarea' ? 2000 : 500)) $errors[] = 'Odpověď v poli „'.$label.'“ je příliš dlouhá.';
        if ($field['typ'] === 'cislo' && !is_numeric(str_replace(',', '.', $raw))) $errors[] = 'Pole „'.$label.'“ musí obsahovat číslo.';
        if ($field['typ'] === 'ano_ne' && !in_array($raw, ['0', '1'], true)) $errors[] = 'Vyberte odpověď v poli „'.$label.'“.';
        if ($field['typ'] === 'vyber' && !in_array($raw, registration_field_options($field['moznosti']), true)) $errors[] = 'Vyberte platnou možnost v poli „'.$label.'“.';
    }

    if (!$errors) {
        $accommodationInterest = !empty($event['zobrazit_ubytovani']) ? (int)$values['ubytovani'] : 0;
        $dedupeKey = hash('sha256', strtolower($values['jmeno'].'|'.$values['prijmeni'].'|'.$phoneNormalized));
        try {
            $conn->begin_transaction();
            $lock = $conn->prepare('SELECT kapacita, stav, registrace_do FROM registracni_turnaje WHERE id=? FOR UPDATE');
            $lock->bind_param('i', $eventId);
            $lock->execute();
            $lockedEvent = $lock->get_result()->fetch_assoc();
            $lock->close();
            $closedNow = !$lockedEvent || $lockedEvent['stav'] !== 'otevrena'
                || (!empty($lockedEvent['registrace_do']) && strtotime((string)$lockedEvent['registrace_do']) < time());
            if ($closedNow) throw new RuntimeException('Registrace na tento turnaj byly mezitím uzavřeny.');

            $count = $conn->prepare("SELECT COUNT(*) FROM turnaj_registrace WHERE turnaj_id=? AND stav IN ('nova','potvrzena')");
            $count->bind_param('i', $eventId);
            $count->execute();
            $occupied = (int)$count->get_result()->fetch_row()[0];
            $count->close();
            $registrationStatus = $occupied >= (int)$lockedEvent['kapacita'] ? 'nahradnik' : 'nova';

            $insert = $conn->prepare(
                'INSERT INTO turnaj_registrace
                 (turnaj_id, jmeno, prijmeni, mesto, telefon, telefon_normalized, rok_narozeni, zajem_ubytovani, poznamka, stav, dedupe_key, souhlas_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, NULLIF(?, \'\'), ?, ?, NOW())'
            );
            $insert->bind_param(
                'isssssiisss',
                $eventId, $values['jmeno'], $values['prijmeni'], $values['mesto'], $values['telefon'],
                $phoneNormalized, $birthYear, $accommodationInterest, $values['poznamka'], $registrationStatus, $dedupeKey
            );
            if (!$insert->execute()) {
                $code = $insert->errno;
                $insert->close();
                if ($code === 1062) throw new DomainException('Tento hráč už je na turnaj přihlášený.');
                throw new RuntimeException('Registraci se nepodařilo uložit.');
            }
            $registrationId = (int)$conn->insert_id;
            $insert->close();

            if ($customValues) {
                $valueInsert = $conn->prepare('INSERT INTO turnaj_registrace_hodnoty (registrace_id,pole_id,hodnota) VALUES (?,?,?)');
                foreach ($customValues as $fieldId => $customValue) {
                    if ($customValue === '') continue;
                    $valueInsert->bind_param('iis', $registrationId, $fieldId, $customValue);
                    if (!$valueInsert->execute()) throw new RuntimeException('Vlastní odpovědi se nepodařilo uložit.');
                }
                $valueInsert->close();
            }
            $conn->commit();

            $_SESSION['registration_success'] = [
                'slug' => $slug,
                'name' => $values['jmeno'].' '.$values['prijmeni'],
                'status' => $registrationStatus,
            ];
            header('Location: /turnaj/'.rawurlencode($slug).'?hotovo=1', true, 303);
            exit;
        } catch (Throwable $e) {
            $conn->rollback();
            error_log('Registrace turnaje: '.$e->getMessage());
            $errors[] = $e instanceof DomainException ? $e->getMessage() : 'Registraci se nepodařilo uložit. Zkuste to prosím znovu.';
        }
    }
}

$success = null;
if (($_GET['hotovo'] ?? '') === '1' && isset($_SESSION['registration_success'])) {
    $candidate = $_SESSION['registration_success'];
    unset($_SESSION['registration_success']);
    if (($candidate['slug'] ?? '') === $slug) $success = $candidate;
}

$date = date_create((string)$event['datum']);
$formattedDate = $date ? $date->format('j. n. Y') : (string)$event['datum'];
?>
<!doctype html>
<html lang="cs">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#062f27">
  <title>Registrace – <?= registration_h($event['nazev']) ?></title>
  <link rel="stylesheet" href="/liga-app/assets/registrace-turnaje.css?v=<?= (int)@filemtime(__DIR__.'/assets/registrace-turnaje.css') ?>">
</head>
<body>
<header class="registration-header">
  <a href="https://sipkytrest.cz" aria-label="Šipky Třešť – hlavní stránka">
    <img src="/liga-app/img/logo.png" alt="Šipky Třešť">
    <strong>Šipky Třešť</strong>
  </a>
</header>
<main class="registration-shell">
  <section class="event-card">
    <span class="event-kicker">Online přihlášení</span>
    <h1><?= registration_h($event['nazev']) ?></h1>
    <?php if ($event['podtitul']): ?><p class="event-subtitle"><?= registration_h($event['podtitul']) ?></p><?php endif; ?>
    <div class="event-facts">
      <div><span>Datum</span><strong><?= registration_h($formattedDate) ?></strong><small><?= registration_h($event['cas_info']) ?></small></div>
      <div><span>Místo</span><strong><?= registration_h($event['misto']) ?></strong><small><?= registration_h($event['adresa']) ?></small></div>
      <div><span>Startovné</span><strong><?= $event['startovne'] !== null ? (int)$event['startovne'].' Kč' : '—' ?></strong></div>
      <div><span>Kapacita</span><strong><?= (int)$event['kapacita'] ?> hráčů</strong><small><?= $remaining > 0 ? 'Zbývá '.$remaining.' míst' : 'Kapacita naplněna – přijímáme náhradníky' ?></small></div>
    </div>
    <?php if ($event['informace']): ?><p class="event-note"><?= registration_h($event['informace']) ?></p><?php endif; ?>
  </section>

  <?php if ($success): ?>
    <section class="form-card success-card" aria-live="polite">
      <div class="success-icon">✓</div>
      <h2>Registrace byla odeslána</h2>
      <p><strong><?= registration_h($success['name']) ?></strong>, vaše přihláška je uložená.</p>
      <?php if ($success['status'] === 'nahradnik'): ?>
        <p>Kapacita je nyní naplněná, proto jste zařazen(a) mezi náhradníky. Organizátor vás bude kontaktovat, pokud se uvolní místo.</p>
      <?php else: ?>
        <p>Organizátor přihlášku zkontroluje a v případě potřeby vás kontaktuje telefonicky.</p>
      <?php endif; ?>
      <a class="secondary-button" href="https://sipkytrest.cz">Zpět na sipkytrest.cz</a>
    </section>
  <?php elseif (!$isOpen): ?>
    <section class="form-card closed-card"><h2>Registrace jsou uzavřené</h2><p>V případě dotazu kontaktujte pořadatele.</p></section>
  <?php else: ?>
    <section class="form-card">
      <h2>Přihláška hráče</h2>
      <p class="required-note">Položky označené * jsou povinné.</p>
      <?php if ($errors): ?><div class="form-errors" role="alert"><strong>Přihlášku zatím nelze odeslat:</strong><ul><?php foreach ($errors as $error): ?><li><?= registration_h($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
      <form method="post" action="/turnaj/<?= rawurlencode($slug) ?>" novalidate>
        <input type="hidden" name="csrf" value="<?= registration_h(csrf_token()) ?>">
        <input type="hidden" name="turnaj" value="<?= registration_h($slug) ?>">
        <div class="honeypot" aria-hidden="true"><label>Web <input name="website" tabindex="-1" autocomplete="off"></label></div>
        <div class="form-grid">
          <label>Jméno *<input name="jmeno" value="<?= registration_h($values['jmeno']) ?>" maxlength="100" autocomplete="given-name" required></label>
          <label>Příjmení *<input name="prijmeni" value="<?= registration_h($values['prijmeni']) ?>" maxlength="100" autocomplete="family-name" required></label>
          <label>Město *<input name="mesto" value="<?= registration_h($values['mesto']) ?>" maxlength="150" autocomplete="address-level2" required></label>
          <label>Telefon *<input type="tel" name="telefon" value="<?= registration_h($values['telefon']) ?>" maxlength="40" inputmode="tel" autocomplete="tel" placeholder="např. 603 123 456" required></label>
          <?php if (!empty($event['zobrazit_rok_narozeni'])): ?><label>Rok narození <span>(nepovinné)</span><input type="number" name="rok_narozeni" value="<?= registration_h($values['rok_narozeni']) ?>" min="1900" max="<?= (int)date('Y') ?>" inputmode="numeric" autocomplete="bday-year"></label><?php endif; ?>
          <?php if (!empty($event['zobrazit_ubytovani'])): ?><fieldset class="accommodation full-width"><legend>Máte zájem o ubytování v místě turnaje? *</legend><div><label><input type="radio" name="ubytovani" value="1" <?= $values['ubytovani'] === '1' ? 'checked' : '' ?> required> Ano</label><label><input type="radio" name="ubytovani" value="0" <?= $values['ubytovani'] === '0' ? 'checked' : '' ?> required> Ne</label></div><small>Jde o nezávazný zájem. Podrobnosti s vámi domluví pořadatel.</small></fieldset><?php endif; ?>
          <?php foreach ($customFields as $field): $fieldId=(int)$field['id']; $fieldValue=$customValues[$fieldId] ?? ''; $required=!empty($field['povinne']); ?>
            <?php if ($field['typ'] === 'textarea'): ?><label class="full-width"><?= registration_h($field['popisek']) ?><?= $required?' *':'' ?><textarea name="vlastni[<?= $fieldId ?>]" maxlength="2000" rows="3" <?= $required?'required':'' ?>><?= registration_h($fieldValue) ?></textarea></label>
            <?php elseif ($field['typ'] === 'ano_ne'): ?><fieldset class="accommodation full-width"><legend><?= registration_h($field['popisek']) ?><?= $required?' *':'' ?></legend><div><label><input type="radio" name="vlastni[<?= $fieldId ?>]" value="1" <?= $fieldValue==='1'?'checked':'' ?> <?= $required?'required':'' ?>> Ano</label><label><input type="radio" name="vlastni[<?= $fieldId ?>]" value="0" <?= $fieldValue==='0'?'checked':'' ?> <?= $required?'required':'' ?>> Ne</label></div></fieldset>
            <?php elseif ($field['typ'] === 'vyber'): ?><label><?= registration_h($field['popisek']) ?><?= $required?' *':'' ?><select name="vlastni[<?= $fieldId ?>]" <?= $required?'required':'' ?>><option value="">Vyberte</option><?php foreach (registration_field_options($field['moznosti']) as $option): ?><option value="<?= registration_h($option) ?>" <?= $fieldValue===$option?'selected':'' ?>><?= registration_h($option) ?></option><?php endforeach; ?></select></label>
            <?php else: ?><label><?= registration_h($field['popisek']) ?><?= $required?' *':'' ?><input type="<?= $field['typ']==='cislo'?'number':'text' ?>" name="vlastni[<?= $fieldId ?>]" value="<?= registration_h($fieldValue) ?>" maxlength="500" <?= $required?'required':'' ?>></label><?php endif; ?>
          <?php endforeach; ?>
          <?php if (!empty($event['zobrazit_poznamku'])): ?><label class="full-width">Poznámka <span>(nepovinné)</span><textarea name="poznamka" maxlength="1000" rows="3"><?= registration_h($values['poznamka']) ?></textarea></label><?php endif; ?>
        </div>
        <label class="consent"><input type="checkbox" name="souhlas" value="1" <?= !empty($_POST['souhlas']) ? 'checked' : '' ?> required><span>Souhlasím se zpracováním uvedených údajů pořadatelem Šipky Třešť za účelem organizace tohoto turnaje. *</span></label>
        <button class="submit-button" type="submit">Odeslat přihlášku</button>
        <p class="privacy-note">Údaje slouží pouze k organizaci turnaje a kontaktování hráče. Kontakt: <a href="mailto:<?= registration_h($event['kontakt_email']) ?>"><?= registration_h($event['kontakt_email']) ?></a><?php if ($event['kontakt_telefon']): ?>, tel. <a href="tel:<?= registration_h(registration_phone((string)$event['kontakt_telefon'])) ?>"><?= registration_h($event['kontakt_telefon']) ?></a><?php endif; ?>.</p>
      </form>
    </section>
  <?php endif; ?>
</main>
<footer>© <?= date('Y') ?> Šipky Třešť</footer>
</body>
</html>
