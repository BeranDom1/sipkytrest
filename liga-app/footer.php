    </main><!-- /nk-content -->
  </div><!-- /nk-wrapper -->

  <footer class="nk-footer">
    © <?= date('Y') ?> Šipky Třešť
  </footer>
  <script>
    (() => {
      const storageKey = "sipky-theme";
      const media = window.matchMedia("(prefers-color-scheme: dark)");
      const applyTheme = (theme, persist = false) => {
        document.documentElement.dataset.theme = theme;
        if (persist) localStorage.setItem(storageKey, theme);
        const dark = theme === "dark";
        document.querySelectorAll("[data-theme-toggle]").forEach((button) => {
          button.querySelector("[aria-hidden]").textContent = dark ? "☀" : "☾";
          button.querySelector(".nk-theme-toggle__text").textContent = dark ? "Světlý režim" : "Tmavý režim";
          button.setAttribute("aria-label", dark ? "Přepnout na světlý režim" : "Přepnout na tmavý režim");
        });
        const meta = document.querySelector("meta[name=theme-color]");
        if (meta) meta.content = dark ? "#0f181d" : "#164b57";
      };
      applyTheme(document.documentElement.dataset.theme || "light");
      document.querySelectorAll("[data-theme-toggle]").forEach((button) => {
        button.addEventListener("click", () => applyTheme(document.documentElement.dataset.theme === "dark" ? "light" : "dark", true));
      });
      media.addEventListener?.("change", (event) => {
        if (!localStorage.getItem(storageKey)) applyTheme(event.matches ? "dark" : "light");
      });
    })();
  </script>
</body>
</html>
