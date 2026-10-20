(() => {
    "use strict";

    document.addEventListener("DOMContentLoaded", () => {
        const sidebar = document.getElementById("app-sidebar");
        const toggle = document.getElementById("app-menu-toggle");
        const close = document.getElementById("app-menu-close");
        const overlay = document.getElementById("app-sidebar-overlay");
        const collapseBtn = document.getElementById("app-sidebar-collapse-btn");

        if (!sidebar) return;

        const STORAGE_KEY = "sisco_sidebar_collapsed";

        // 1. Estado inicial colapsado / expandido en desktop
        const isCollapsedInitial = localStorage.getItem(STORAGE_KEY) === "true";
        if (isCollapsedInitial && window.innerWidth >= 768) {
            sidebar.classList.add("is-collapsed");
            collapseBtn?.setAttribute("aria-expanded", "false");
            collapseBtn?.setAttribute("title", "Expandir menú");
        } else {
            collapseBtn?.setAttribute("aria-expanded", "true");
            collapseBtn?.setAttribute("title", "Colapsar menú");
        }

        // 2. Control de colapso / expansión
        collapseBtn?.addEventListener("click", () => {
            const willCollapse = !sidebar.classList.contains("is-collapsed");
            sidebar.classList.toggle("is-collapsed", willCollapse);
            document.documentElement.classList.toggle("sidebar-init-collapsed", willCollapse);
            localStorage.setItem(STORAGE_KEY, String(willCollapse));
            collapseBtn.setAttribute("aria-expanded", String(!willCollapse));
            collapseBtn.setAttribute("title", willCollapse ? "Expandir menú" : "Colapsar menú");
        });

        // 3. Control móvil (off-canvas)
        const setOpen = (open) => {
            document.body.classList.toggle("sidebar-open", open);
            toggle?.setAttribute("aria-expanded", String(open));
            sidebar.setAttribute("aria-hidden", String(!open && window.innerWidth < 768));
        };

        toggle?.addEventListener("click", () => setOpen(!document.body.classList.contains("sidebar-open")));
        close?.addEventListener("click", () => setOpen(false));
        overlay?.addEventListener("click", () => setOpen(false));

        sidebar.querySelectorAll("a").forEach((link) => {
            link.addEventListener("click", () => {
                if (window.innerWidth < 768) setOpen(false);
            });
        });

        document.addEventListener("keydown", (event) => {
            if (event.key === "Escape") setOpen(false);
        });

        window.addEventListener("resize", () => {
            if (window.innerWidth >= 768) {
                setOpen(false);
            }
        });

        setOpen(false);
    });
})();
