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
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-Token': window.__CSRF_TOKEN__ || ''
    },
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

  const score1Input = zapasEl.querySelector('[data-slot="skore1"]');
  const score2Input = zapasEl.querySelector('[data-slot="skore2"]');
  const s1Raw = score1Input.value;
  const s2Raw = score2Input.value;

  if (s1Raw === '' || s2Raw === '') {
    alert('Vyplň obě skóre');
    return;
  }

  const s1 = Number(s1Raw);
  const s2 = Number(s2Raw);
  const winningLegs = Number(score1Input.max);
  if (
    !Number.isInteger(s1) || !Number.isInteger(s2)
    || s1 < 0 || s2 < 0 || s1 === s2
    || Math.max(s1, s2) !== winningLegs
    || Math.min(s1, s2) >= winningLegs
  ) {
    alert(`Neplatný výsledek. Toto kolo se hraje na ${winningLegs} vítězných legů.`);
    return;
  }

  const player1 = zapasEl.querySelector('.hrac-left .jmeno')?.textContent.trim() || 'Hráč 1';
  const player2 = zapasEl.querySelector('.hrac-right .jmeno')?.textContent.trim() || 'Hráč 2';
  const winner = s1 > s2 ? player1 : player2;
  const confirmed = confirm(
    `Opravdu chcete uložit tento výsledek?\n\n${player1} vs. ${player2}\n${s1} : ${s2}\n\nVítěz: ${winner}`
  );
  if (!confirmed) return;

  btn.disabled = true;
  btn.textContent = '...';

  try {
    const res = await fetch('/liga-app/pohar/ajax_uloz_skore.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-Token': window.__CSRF_TOKEN__ || ''
      },
      body: JSON.stringify({
        zapas_id: zapasId,
        skore1: s1,
        skore2: s2
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

  if (!confirm('Opravdu chcete zrušit uložený výsledek? Dvojice hráčů zůstane zachována.')) return;

  const zapasId = btn.dataset.zapasId;

  try {
    const res = await fetch('/liga-app/pohar/ajax_reset_zapas.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-Token': window.__CSRF_TOKEN__ || ''
      },
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
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': window.__CSRF_TOKEN__ || ''
            },
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

