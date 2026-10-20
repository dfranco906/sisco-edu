(() => {
    "use strict";

    document.addEventListener("DOMContentLoaded", () => {
        const sidebar = document.getElementById("app-sidebar");
        const toggle = document.getElementById("app-menu-toggle");
        const close = document.getElementById("app-menu-close");
        const overlay = document.getElementById("app-sidebar-overlay");
        const collapseBtn = document.getElementById("app-sidebar-collapse-btn");

        const THEME_STORAGE_KEY = "sisco_theme";
        const SIDEBAR_STORAGE_KEY = "sisco_sidebar_collapsed";

        // 1. Control unificado de Tema (Claro / Oscuro)
        const getCurrentTheme = () => document.documentElement.getAttribute("data-theme") || "light";

        const applyTheme = (theme, notify = false) => {
            document.documentElement.setAttribute("data-theme", theme);
            document.documentElement.classList.toggle("dark", theme === "dark");
            try {
                localStorage.setItem(THEME_STORAGE_KEY, theme);
            } catch (e) {}

            // Actualizar títulos de botones
            document.querySelectorAll("#app-theme-toggle, #sidebar-theme-toggle, [data-action='toggle-theme']").forEach(btn => {
                btn.setAttribute("title", theme === "dark" ? "Cambiar a Modo Claro" : "Cambiar a Modo Oscuro");
                btn.setAttribute("aria-label", theme === "dark" ? "Cambiar a Modo Claro" : "Cambiar a Modo Oscuro");
            });

            if (notify && window.Toast) {
                window.Toast.info(
                    theme === "dark" ? "Modo Oscuro activado" : "Modo Claro activado",
                    "Apariencia",
                    2200
                );
            }
        };

        const toggleTheme = () => {
            const nextTheme = getCurrentTheme() === "dark" ? "light" : "dark";
            applyTheme(nextTheme, true);
        };

        document.querySelectorAll("#app-theme-toggle, #sidebar-theme-toggle, [data-action='toggle-theme']").forEach(btn => {
            btn.addEventListener("click", (e) => {
                e.preventDefault();
                toggleTheme();
            });
        });

        // 2. Estado inicial colapsado / expandido en desktop
        if (sidebar) {
            const isCollapsedInitial = localStorage.getItem(SIDEBAR_STORAGE_KEY) === "true";
            if (isCollapsedInitial && window.innerWidth >= 768) {
                sidebar.classList.add("is-collapsed");
                collapseBtn?.setAttribute("aria-expanded", "false");
                collapseBtn?.setAttribute("title", "Expandir menú");
            } else {
                collapseBtn?.setAttribute("aria-expanded", "true");
                collapseBtn?.setAttribute("title", "Colapsar menú");
            }

            // 3. Control de colapso / expansión
            collapseBtn?.addEventListener("click", () => {
                const willCollapse = !sidebar.classList.contains("is-collapsed");
                sidebar.classList.toggle("is-collapsed", willCollapse);
                document.documentElement.classList.toggle("sidebar-init-collapsed", willCollapse);
                try {
                    localStorage.setItem(SIDEBAR_STORAGE_KEY, String(willCollapse));
                } catch (e) {}
                collapseBtn.setAttribute("aria-expanded", String(!willCollapse));
                collapseBtn.setAttribute("title", willCollapse ? "Expandir menú" : "Colapsar menú");
            });

            // 4. Control móvil (off-canvas)
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
        }
    });
})();
