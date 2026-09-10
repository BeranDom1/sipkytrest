document.addEventListener("DOMContentLoaded", () => {
    const year = document.getElementById("year");

    if (year) {
        year.textContent = new Date().getFullYear();
    }

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
