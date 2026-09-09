document.addEventListener("DOMContentLoaded", () => {
    const popup = document.getElementById("popup");
    const popupImage = popup?.querySelector(".popup-image");
    const closeButton = document.getElementById("popup-close");
    const year = document.getElementById("year");
    let popupTrigger = null;

    if (year) {
        year.textContent = new Date().getFullYear();
    }

    function openPopup(trigger) {
        const imageSource = trigger.dataset.popup;
        const thumbnail = trigger.querySelector("img");

        if (!popup || !popupImage || !imageSource) return;

        popupTrigger = trigger;
        popupImage.src = imageSource;
        popupImage.alt = thumbnail?.alt || "Plakát události";
        popup.hidden = false;
        popup.setAttribute("aria-hidden", "false");
        document.body.classList.add("popup-open");
        closeButton?.focus();
    }

    function closePopup() {
        if (!popup || popup.hidden) return;

        popup.hidden = true;
        popup.setAttribute("aria-hidden", "true");
        document.body.classList.remove("popup-open");
        popupImage?.removeAttribute("src");
        popupTrigger?.focus();
    }

    document.querySelectorAll(".popup-trigger[data-popup]").forEach((trigger) => {
        trigger.addEventListener("click", () => openPopup(trigger));
    });

    closeButton?.addEventListener("click", closePopup);
    popup?.addEventListener("click", (event) => {
        if (event.target === popup) closePopup();
    });

    document.addEventListener("keydown", (event) => {
        if (event.key === "Escape") closePopup();
    });

    document.querySelectorAll(".copyable[data-copy]").forEach((button) => {
        button.addEventListener("click", async () => {
            try {
                await navigator.clipboard.writeText(button.dataset.copy);
                button.classList.add("copied");
                window.setTimeout(() => button.classList.remove("copied"), 2500);
            } catch {
                window.prompt("Zkopírujte číslo účtu:", button.dataset.copy);
            }
        });
    });
});
