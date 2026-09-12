<?php
// Sdílená pole pro vytvoření a úpravu registračního turnaje.
$deadlineValue = !empty($newEvent['registrace_do']) ? date('Y-m-d\TH:i', strtotime((string)$newEvent['registrace_do'])) : '';
?>
<div class="admin-field"><label>Název</label><input name="nazev" value="<?= reg_admin_h((string)$newEvent['nazev']) ?>" maxlength="255" required></div>
<div class="admin-field"><label>Adresa odkazu</label><input name="slug" value="<?= reg_admin_h((string)$newEvent['slug']) ?>" maxlength="120" pattern="[a-z0-9-]+" placeholder="napr-letni-turnaj-2027" required></div>
<div class="admin-field reg-span-2"><label>Podtitul</label><input name="podtitul" value="<?= reg_admin_h((string)$newEvent['podtitul']) ?>" maxlength="255"></div>
<div class="admin-field"><label>Datum</label><input type="date" name="datum" value="<?= reg_admin_h((string)$newEvent['datum']) ?>" required></div>
<div class="admin-field"><label>Časová informace</label><input name="cas_info" value="<?= reg_admin_h((string)$newEvent['cas_info']) ?>" maxlength="120" placeholder="Celodenní turnaj"></div>
<div class="admin-field"><label>Místo</label><input name="misto" value="<?= reg_admin_h((string)$newEvent['misto']) ?>" maxlength="255" required></div>
<div class="admin-field"><label>Adresa</label><input name="adresa" value="<?= reg_admin_h((string)$newEvent['adresa']) ?>" maxlength="255" required></div>
<div class="admin-field"><label>Kapacita</label><input type="number" name="kapacita" value="<?= reg_admin_h((string)$newEvent['kapacita']) ?>" min="1" max="5000" required></div>
<div class="admin-field"><label>Startovné v Kč</label><input type="number" name="startovne" value="<?= reg_admin_h((string)$newEvent['startovne']) ?>" min="0" max="65535"></div>
<div class="admin-field"><label>Kontaktní e-mail</label><input type="email" name="kontakt_email" value="<?= reg_admin_h((string)$newEvent['kontakt_email']) ?>" maxlength="255"></div>
<div class="admin-field"><label>Kontaktní telefon</label><input name="kontakt_telefon" value="<?= reg_admin_h((string)$newEvent['kontakt_telefon']) ?>" maxlength="40"></div>
<div class="admin-field"><label>Stav registrace</label><select name="stav"><?php foreach (['priprava'=>'Příprava','otevrena'=>'Otevřená','uzavrena'=>'Uzavřená','archivni'=>'Archivní'] as $value=>$label): ?><option value="<?= $value ?>" <?= $newEvent['stav']===$value?'selected':'' ?>><?= $label ?></option><?php endforeach; ?></select></div>
<div class="admin-field"><label>Uzávěrka (nepovinné)</label><input type="datetime-local" name="registrace_do" value="<?= reg_admin_h($deadlineValue) ?>"></div>
<div class="admin-field reg-span-2"><label>Informace</label><textarea name="informace" maxlength="4000"><?= reg_admin_h((string)$newEvent['informace']) ?></textarea></div>
<fieldset class="admin-field reg-span-2"><legend><strong>Volitelná pole formuláře</strong></legend>
  <label class="admin-check"><input type="checkbox" name="zobrazit_rok_narozeni" value="1" <?= !isset($newEvent['zobrazit_rok_narozeni']) || !empty($newEvent['zobrazit_rok_narozeni']) ? 'checked' : '' ?>> Rok narození</label>
  <label class="admin-check"><input type="checkbox" name="zobrazit_ubytovani" value="1" <?= !isset($newEvent['zobrazit_ubytovani']) || !empty($newEvent['zobrazit_ubytovani']) ? 'checked' : '' ?>> Zájem o ubytování</label>
  <label class="admin-check"><input type="checkbox" name="zobrazit_poznamku" value="1" <?= !isset($newEvent['zobrazit_poznamku']) || !empty($newEvent['zobrazit_poznamku']) ? 'checked' : '' ?>> Poznámka</label>
</fieldset>
