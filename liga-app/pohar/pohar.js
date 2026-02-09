// ============================
// TOGGLE KOL
// ============================
function toggleKolo(btn) {
  const section = btn.closest('.kolo');
  section.classList.toggle('open');
}

// ============================
// PŘIŘAZENÍ HRÁČE
// ============================
document.addEventListener('change', e => {
  if (!e.target.classList.contains('hrac-select')) return;

  const zapasId = e.target.dataset.zapasId;
  const slot = e.target.dataset.slot;
  const hracId = e.target.value || null;

  fetch('/liga-app/pohar/ajax_prirazeni_hrace.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({
      zapas_id: zapasId,
      slot: slot,
      hrac_id: hracId
    })
  }).then(updateHracSelects);
});

// ============================
// ZÁKAZ DUPLICITY HRÁČE (UX)
// ============================
function updateHracSelects() {
  const selectedIds = new Set();

  document.querySelectorAll('.hrac-select').forEach(sel => {
    if (sel.value) selectedIds.add(sel.value);
  });

  document.querySelectorAll('.hrac-select').forEach(sel => {
    const currentValue = sel.value;

    sel.querySelectorAll('option').forEach(opt => {
      if (!opt.value) return;

      if (opt.value === currentValue) {
        opt.disabled = false;
      } else {
        opt.disabled = selectedIds.has(opt.value);
      }
    });
  });
}

document.addEventListener('DOMContentLoaded', updateHracSelects);

// ============================
// ULOŽENÍ SKÓRE
// ============================
document.addEventListener('click', async e => {
  const btn = e.target.closest('.btn-save-score');
  if (!btn) return;

  const zapasId = btn.dataset.zapasId;
  const zapasEl = btn.closest('.zapas');

  const s1 = zapasEl.querySelector('[data-slot="skore1"]').value;
  const s2 = zapasEl.querySelector('[data-slot="skore2"]').value;

  if (s1 === '' || s2 === '') {
    alert('Vyplň obě skóre');
    return;
  }

  btn.disabled = true;
  btn.textContent = '...';

  try {
    const res = await fetch('/liga-app/pohar/ajax_uloz_skore.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        zapas_id: zapasId,
        skore1: parseInt(s1, 10),
        skore2: parseInt(s2, 10)
      })
    });

    const data = await res.json();

    if (!data.ok) {
      throw new Error(data.error || 'Chyba při ukládání');
    }

    location.reload(); // jednoduché & bezpečné

  } catch (err) {
    alert(err.message);
    btn.disabled = false;
    btn.textContent = 'Uložit';
  }
});
document.addEventListener('click', async e => {
  const btn = e.target.closest('.btn-reset-zapas');
  if (!btn) return;

  if (!confirm('Opravdu chceš zrušit celý zápas?')) return;

  const zapasId = btn.dataset.zapasId;

  try {
    const res = await fetch('/liga-app/pohar/ajax_reset_zapas.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ zapas_id: zapasId })
    });

    const data = await res.json();

    if (!data.ok) {
      alert(data.error || 'Nelze zrušit zápas');
      return;
    }

    location.reload();

  } catch (err) {
    alert('Chyba spojení');
  }
});

document.addEventListener('click', async (e) => {
    const btn = e.target.closest('.btn-cancel-bye');
    if (!btn) return;

    const zapasId = btn.dataset.zapasId;

    if (!confirm('Opravdu chcete zrušit volný los?')) return;

    try {
        const res = await fetch('/liga-app/pohar/ajax_zrus_bye.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ zapas_id: zapasId })
        });

        const data = await res.json();

        if (!data.ok) {
            alert(data.error || 'Chyba při rušení BYE');
            return;
        }

        // nejjednodušší a bezpečné
        location.reload();

    } catch (err) {
        alert('Chyba spojení');
    }
});

