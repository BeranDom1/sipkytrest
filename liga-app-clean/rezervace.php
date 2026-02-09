<?php
/* ========= 0) Turnstile klíče ========= */
$TURNSTILE_SITEKEY = '0x4AAAAAABuJesw0pVRoE3BM';
$TURNSTILE_SECRET  = '0x4AAAAAABuJejPGtXVWi9OEry5kSEbkiyw';

/* ========= 1) JSON handler (create/delete) ========= */
if (
  $_SERVER['REQUEST_METHOD'] === 'POST' &&
  isset($_SERVER['CONTENT_TYPE']) &&
  strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false
) {
  header('Content-Type: application/json; charset=utf-8');
  require_once __DIR__.'/db.php';
  if (session_status() !== PHP_SESSION_ACTIVE) session_start();

  // Rate-limit: max 12 požadavků / 5 min (na session)
  $now = time();
  $_SESSION['rez_rl'] = array_filter($_SESSION['rez_rl'] ?? [], fn($t)=> ($now-$t)<300);
  $_SESSION['rez_rl'][] = $now;
  if (count($_SESSION['rez_rl']) > 12) {
    http_response_code(429);
    echo json_encode(['error'=>'Příliš mnoho požadavků.']); exit;
  }

  $data = json_decode(file_get_contents('php://input'), true) ?: [];

  // CSRF
  if (!hash_equals($_SESSION['csrf'] ?? '', (string)($data['csrf'] ?? ''))) {
    http_response_code(403);
    echo json_encode(['error'=>'CSRF ověření selhalo. Načtěte stránku znovu.']); exit;
  }

  // CAPTCHA – požadujeme pro create/delete
  $captcha = (string)($data['captcha'] ?? '');
  $action  = (string)($data['action'] ?? '');
  if (in_array($action, ['create','delete'], true)) {
    if ($captcha === '') { http_response_code(401); echo json_encode(['error'=>'CAPTCHA_REQUIRED']); exit; }
    $ch = curl_init('https://challenges.cloudflare.com/turnstile/v0/siteverify');
    curl_setopt_array($ch, [
      CURLOPT_POST => true,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_POSTFIELDS => http_build_query([
        'secret'   => $TURNSTILE_SECRET,
        'response' => $captcha,
        // 'remoteip' => $_SERVER['REMOTE_ADDR'] ?? null, // raději nechat vypnuto
      ]),
      CURLOPT_TIMEOUT => 8,
    ]);
    $out = curl_exec($ch); curl_close($ch);
    $ok  = $out && ($res = json_decode($out, true)) && !empty($res['success']);
    if (!$ok) { http_response_code(401); echo json_encode(['error'=>'CAPTCHA_FAILED']); exit; }
  }

  $terc  = (int)($data['terc'] ?? 0);
  $datum = (string)($data['datum'] ?? '');
  $cas   = (string)($data['cas'] ?? '');
  $jmeno = trim((string)($data['jmeno'] ?? ''));

  if ($terc < 1 || $terc > 4 || !$datum || !$cas) {
    http_response_code(400); echo json_encode(['error'=>'Neplatná data']); exit;
  }

  if ($action === 'delete') {
    $stmt = $conn->prepare("DELETE FROM rezervace WHERE terc_id=? AND datum=? AND cas=?");
    $stmt->bind_param('iss', $terc, $datum, $cas);
    $stmt->execute();
    echo json_encode($stmt->affected_rows > 0 ? ['success'=>true] : ['error'=>'Rezervace nenalezena']);
    exit;
  }

  // ===== CREATE =====
  if ($jmeno === '' || mb_strlen($jmeno) > 60) {
    http_response_code(400); echo json_encode(['error'=>'Vyplňte jméno (max. 60 znaků).']); exit;
  }

  // zákaz minulého dne (server-side)
  $tz    = new DateTimeZone('Europe/Prague');
  $day   = DateTime::createFromFormat('Y-m-d', $datum, $tz);
  if (!$day) { http_response_code(400); echo json_encode(['error'=>'Neplatné datum']); exit; }
  $today = new DateTime('now', $tz);
  $day->setTime(0,0,0); $today->setTime(0,0,0);
  if ($day < $today) {
    http_response_code(400);
    echo json_encode(['error'=>'Nelze vytvořit rezervaci na den, který již proběhl.']); exit;
  }
  // (volitelné) zakázat i minulý čas:
  // $slot = DateTime::createFromFormat('Y-m-d H:i:s', "$datum $cas", $tz);
  // if ($slot < new DateTime('now', $tz)) { http_response_code(400); echo json_encode(['error'=>'Nelze rezervovat zpětně.']); exit; }

  // kolize
  $chk = $conn->prepare("SELECT 1 FROM rezervace WHERE terc_id=? AND datum=? AND cas=?");
  $chk->bind_param('iss', $terc, $datum, $cas);
  $chk->execute(); $chk->store_result();
  if ($chk->num_rows > 0) { http_response_code(409); echo json_encode(['error'=>'Termín již obsazen']); exit; }

  $ins = $conn->prepare("INSERT INTO rezervace (terc_id, jmeno, datum, cas) VALUES (?,?,?,?)");
  $ins->bind_param('isss', $terc, $jmeno, $datum, $cas);
  $ins->execute();
  echo json_encode(['success'=>true]); exit;
}

/* ========= 2) HTML část ========= */
require __DIR__.'/header.php';
?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.css">
<script defer src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
<!-- Turnstile API -->
<script defer src="https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit"></script>

<main id="content" class="nk-content nk-content--flat">
  <h2>Rezervace terčů</h2>
  <ul>
    <li>Nad každým "kalendářem" je číslo Terče.</li>
    <li>Sloupce označují dny, řádky jsou hodiny (17=17:00).</li>
        <li>Rezervace je vždy minimálně na hodinu - takže když kliknu do políčka 14 rezervuji terč od 14:00 do 15:00 </li>
        <li>Rezervaci provedete kliknutím do prázdného políčka, pak se objeví okno, kde napíšete svoje příjmení (stačí jen jednoho z hráčů) pak<strong> JE POTŘEBA CHVÍLI POČKAT</strong></li>
        <li>Pokud budete chtít rezervaci zrušit, stačí kliknout na rezervaci a potvrdit zrušení </li>
        <li>Buďte k sobě ohleduplní, <strong>nerušte</strong> nikomu rezervace pokud jsou již zadané</li>
</ul>

  <style>
    .rez-grid{ display:grid; grid-template-columns:1fr; gap:18px; max-width:1150px; margin-inline:auto; }
    .rez-card{ border:1px solid var(--line); border-radius:12px; background:#fff; box-shadow:var(--shadow); }
    .rez-card h3{ margin:0; padding:10px 12px; border-bottom:1px solid var(--line); font-size:16px; }
    .rez-card .calendar{ padding:8px; overflow-x:auto; }
    .calendar .fc .fc-scrollgrid{ min-width:1100px; }

    /* Eventy – ať jsou klikací a klik projde až na eventClick */
    .fc .fc-timegrid-event{ cursor:pointer; }
    .fc .fc-timegrid-event .fc-event-main *{ pointer-events:none; }

    .fc .fc-timegrid-event .fc-event-main{
      padding:2px 6px; display:flex; justify-content:space-between; gap:.5rem; font-size:13px;
    }
    .fc-evt-time{ opacity:.8 } .fc-evt-name{ font-weight:600 }

    /* schovaný kontejner pro invisible CAPTCHA */
    #ts-container{ height:0; overflow:hidden; }
    
  </style>

  <!-- neviditelný Turnstile widget -->
  <div id="ts-container"></div>

  <div class="rez-grid">
    <?php for ($i=1; $i<=4; $i++): ?>
      <section class="rez-card">
        <h3>Terč <?= $i ?></h3>
        <div id="calendar<?= $i ?>" class="calendar"></div>
      </section>
    <?php endfor; ?>
  </div>
</main>

<script>
(function(){
  const CSRF = window.__CSRF_TOKEN__ || '';
  const SITEKEY = <?= json_encode($TURNSTILE_SITEKEY) ?>;
  let widgetId = null;

  // Render invisible Turnstile co nejdřív
  function renderTurnstile(){
    if (!window.turnstile || widgetId !== null) return;
    widgetId = turnstile.render('#ts-container', { sitekey: SITEKEY, size: 'invisible' });
  }
  document.addEventListener('DOMContentLoaded', renderTurnstile);

  // vždy resetni a vyžádej nový token
  function getCaptchaToken(action){
    return new Promise((resolve, reject) => {
      const wait = () => {
        if (!window.turnstile || widgetId === null) return setTimeout(wait, 60);
        try { turnstile.reset(widgetId); } catch(e) {}
        turnstile.execute(widgetId, {
          action,
          callback: token => resolve(token),
          'error-callback': () => reject('CAPTCHA_ERROR'),
          'timeout-callback': () => reject('CAPTCHA_TIMEOUT'),
        });
      };
      wait();
    });
  }

  function postJSON(payload){
    return fetch('rezervace.php', {
      method:'POST', credentials:'same-origin',
      headers:{ 'Content-Type':'application/json', 'Accept':'application/json' },
      body: JSON.stringify(Object.assign({ csrf: CSRF }, payload))
    }).then(r=>r.json());
  }

  document.addEventListener('DOMContentLoaded', function(){
    for (let i=1;i<=4;i++){
      const terc = i, el = document.getElementById('calendar'+i);

      const cal = new FullCalendar.Calendar(el, {
        initialView:'timeGridWeek',
        locale:'cs',
        firstDay:1,
        hiddenDays:[0],
        slotMinTime:'14:00:00',
        slotMaxTime:'22:00:00',
        slotDuration:'01:00:00',
        allDaySlot:false,
        height:'auto',
        headerToolbar:{ left:'prev,next today', center:'title', right:'' },
         buttonText: {
    today: 'Dnes'  },

        events:{ url:'events.php', method:'GET', extraParams:()=>({ terc, _:Date.now() }) },

        // vlastní obsah jako DOM uzly (spolehlivé klikání)
        eventContent: arg => {
          const time = document.createElement('span');
          time.className = 'fc-evt-time';
          time.textContent = arg.timeText || '';

          const name = document.createElement('span');
          name.className = 'fc-evt-name';
          name.textContent = (arg.event.title || '').trim().split(/\s+/).pop();

          const wrap = document.createElement('span');
          wrap.style.display = 'flex';
          wrap.style.justifyContent = 'space-between';
          wrap.style.gap = '.5rem';
          wrap.appendChild(name);
          wrap.appendChild(time);

          return { domNodes: [wrap] };
        },

        // klik na prázdný slot – vytvoření
        dateClick: info => {
          // blokace minulého dne (frontend)
          const slotDate = info.date;
          const startOfSlotDay = new Date(slotDate.getFullYear(), slotDate.getMonth(), slotDate.getDate());
          const startOfToday   = new Date(); startOfToday.setHours(0,0,0,0);
          if (startOfSlotDay < startOfToday) { alert('⛔ Nelze vytvořit rezervaci na den, který už proběhl.'); return; }

          const [date, time] = info.dateStr.split('T');
          const hm  = time.slice(0,5);
          const end = `${String((+hm.slice(0,2)+1)).padStart(2,'0')}:${hm.slice(3)}`;

          const name = prompt(`Rezervace terče ${terc}\nDatum: ${date}\nČas: ${hm} – ${end}\n\nZadejte své jméno:`);
          if (!name) return;

          getCaptchaToken('create')
            .then(token => postJSON({ action:'create', captcha:token, terc, datum:date, cas:hm+':00', jmeno:name }))
            .then(d => {
              if (d.success){ alert('✅ Rezervace uložena.'); cal.refetchEvents(); }
              else if (d.error==='CAPTCHA_REQUIRED' || d.error==='CAPTCHA_FAILED'){ alert('Ověření selhalo, zkuste to znovu.'); }
              else { alert('⚠️ ' + (d.error || 'Chyba při rezervaci')); }
            })
            .catch(()=> alert('Chyba ověření. Zkuste znovu.'));
        },

        // klik na event – mazání
        eventClick: info => {
          if (!confirm('Opravdu zrušit rezervaci?')) return;
          const s = info.event.start;
          const y  = s.getFullYear();
          const m  = String(s.getMonth()+1).padStart(2,'0');
          const d  = String(s.getDate()).padStart(2,'0');
          const hh = String(s.getHours()).padStart(2,'0');
          const mi = String(s.getMinutes()).padStart(2,'0');

          getCaptchaToken('delete')
            .then(token => postJSON({ action:'delete', captcha:token, terc, datum:`${y}-${m}-${d}`, cas:`${hh}:${mi}:00` }))
            .then(d => {
              if (d.success){ alert('🗑️ Rezervace smazána.'); cal.refetchEvents(); }
              else if (d.error==='CAPTCHA_REQUIRED' || d.error==='CAPTCHA_FAILED'){ alert('Ověření selhalo, zkuste to znovu.'); }
              else { alert('⚠️ ' + (d.error || 'Chyba při mazání')); }
            })
            .catch(()=> alert('Chyba ověření. Zkuste znovu.'));
        },
      });

      cal.render();
    }
  });
})();
</script>

<?php require __DIR__.'/footer.php'; ?>
