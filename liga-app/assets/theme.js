// Jediné mobilní menu – open/close
(function () {
  const body = document.body;
  const dim  = document.getElementById('nk-dim');
  const menu = document.getElementById('nk-mobilemenu');

  // otevřít/zavřít
  function toggleMenu(force){
    if (typeof force === 'boolean'){
      body.classList.toggle('nk-open-menu', force);
    } else {
      body.classList.toggle('nk-open-menu');
    }
  }

  // click na burger / X
  document.addEventListener('click', (e)=>{
    const t = e.target.closest('[data-toggle="mobilemenu"]');
    if (!t) return;
    e.preventDefault();
    toggleMenu();
  });

  // klik do pozadí zavře
  if (dim){
    dim.addEventListener('click', ()=> toggleMenu(false));
  }

  // ESC zavře
  document.addEventListener('keydown', (e)=>{
    if (e.key === 'Escape') toggleMenu(false);
  });

  // když přejdeme na desktop, zavřít
  const mq = window.matchMedia('(min-width: 961px)');
  mq.addEventListener?.('change', () => {
    if (mq.matches) toggleMenu(false);
  });
})();
