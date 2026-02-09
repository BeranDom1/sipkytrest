
// Inject hidden CSRF token into all POST forms if missing
(function() {
  function inject() {
    var token = window.__CSRF_TOKEN__ || '';
    if (!token) return;
    document.querySelectorAll('form').forEach(function(f){
      if ((f.getAttribute('method') || '').toLowerCase() !== 'post') return;
      if (f.querySelector('input[name="csrf"]')) return;
      var input = document.createElement('input');
      input.type = 'hidden';
      input.name = 'csrf';
      input.value = token;
      f.appendChild(input);
    });
  }
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', inject);
  } else {
    inject();
  }
})();
