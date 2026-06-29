(() => {
    "use strict";

    document.addEventListener("DOMContentLoaded", () => {
        const sidebar = document.getElementById("app-sidebar");
        const toggle = document.getElementById("app-menu-toggle");
        const close = document.getElementById("app-menu-close");
        const overlay = document.getElementById("app-sidebar-overlay");

        if (!sidebar || !toggle) return;

        const setOpen = (open) => {
            document.body.classList.toggle("sidebar-open", open);
            toggle.setAttribute("aria-expanded", String(open));
            sidebar.setAttribute("aria-hidden", String(!open && window.innerWidth < 768));
        };

        toggle.addEventListener("click", () => setOpen(!document.body.classList.contains("sidebar-open")));
        close?.addEventListener("click", () => setOpen(false));
        overlay?.addEventListener("click", () => setOpen(false));

        sidebar.querySelectorAll("a").forEach((link) => {
            link.addEventListener("click", () => setOpen(false));
        });

        document.addEventListener("keydown", (event) => {
            if (event.key === "Escape") setOpen(false);
        });

        window.addEventListener("resize", () => {
            if (window.innerWidth >= 768) setOpen(false);
        });

        setOpen(false);
    });
})();
