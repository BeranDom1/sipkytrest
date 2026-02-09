document.addEventListener("DOMContentLoaded", () => {
    const popup = document.getElementById("event-popup");
    const closeBtn = document.getElementById("popup-close");
    const yearSpan = document.getElementById("year");

    // Rok v patičce
    if (yearSpan) {
        yearSpan.textContent = new Date().getFullYear();
    }

    // Logika pro popup – zobrazit jen jednou za den
    const todayKey = new Date().toISOString().slice(0, 10); // např. 2025-11-20
    const dismissedFor = localStorage.getItem("eventPopupDismissedFor");

    if (dismissedFor === todayKey) {
        // už dnes zavřeno -> popup neschováváme
        if (popup) popup.style.display = "none";
    } else {
        if (popup) popup.style.display = "flex";
    }

    function closePopup() {
        if (popup) {
            popup.style.display = "none";
            localStorage.setItem("eventPopupDismissedFor", todayKey);
        }
    }

    if (closeBtn) {
        closeBtn.addEventListener("click", closePopup);
    }

    // zavření kliknutím mimo obsah
    if (popup) {
        popup.addEventListener("click", (e) => {
            if (e.target === popup) {
                closePopup();
            }
        });
    }
});

