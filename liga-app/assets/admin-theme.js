(() => {
  const storageKey = 'sipky-theme';
  const media = window.matchMedia('(prefers-color-scheme: dark)');
  const saved = localStorage.getItem(storageKey);
  const initial = saved || (media.matches ? 'dark' : 'light');
  const applyTheme = (theme, persist = false) => {
    document.documentElement.dataset.theme = theme;
    if (persist) localStorage.setItem(storageKey, theme);
    const button = document.querySelector('[data-admin-theme-toggle]');
    if (button) {
      const dark = theme === 'dark';
      button.textContent = dark ? '☀' : '☾';
      button.title = dark ? 'Přepnout na světlý režim' : 'Přepnout na tmavý režim';
      button.setAttribute('aria-label', button.title);
    }
  };
  applyTheme(initial);
  const mount = () => {
    if (document.querySelector('[data-theme-toggle], [data-admin-theme-toggle]')) return;
    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'admin-theme-toggle';
    button.dataset.adminThemeToggle = '1';
    button.addEventListener('click', () => applyTheme(document.documentElement.dataset.theme === 'dark' ? 'light' : 'dark', true));
    document.body.appendChild(button);
    applyTheme(document.documentElement.dataset.theme || 'light');
  };
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', mount); else mount();
  media.addEventListener?.('change', event => {
    if (!localStorage.getItem(storageKey)) applyTheme(event.matches ? 'dark' : 'light');
  });
})();
