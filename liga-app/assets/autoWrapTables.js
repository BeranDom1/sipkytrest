
// Auto-wrap tables into .table-wrap to confine horizontal scrolling
(function() {
  function wrapTables(root) {
    const tables = (root || document).querySelectorAll('table');
    tables.forEach(t => {
      if (t.closest('.table-wrap')) return;
      const wrap = document.createElement('div');
      wrap.className = 'table-wrap';
      t.parentNode.insertBefore(wrap, t);
      wrap.appendChild(t);
    });
  }

  function ensureMain() {
    if (!document.querySelector('main') && !document.querySelector('#content')) {
      const main = document.createElement('main');
      while (document.body.firstChild) {
        if (document.body.firstChild.tagName && document.body.firstChild.tagName.toLowerCase() === 'footer') break;
        main.appendChild(document.body.firstChild);
      }
      document.body.insertBefore(main, document.body.firstChild);
    }
  }

  function init() {
    ensureMain();
    wrapTables(document.querySelector('main') || document.querySelector('#content') || document);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
  
  ths.forEach((th)=>{ th.setAttribute('data-sortable',''); th.setAttribute('aria-sort','none'); th.title='Klikni pro řazení'; });

});
