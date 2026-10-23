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
        const FONT_SIZE_STORAGE_KEY = "sisco_font_size";
        const VALID_FONT_SIZES = ["11pt", "12pt", "13pt"];

        // 1. Control unificado de Tema (Claro / Oscuro)
        const getCurrentTheme = () => document.documentElement.getAttribute("data-theme") || "light";

        const applyTheme = (theme, notify = false) => {
            document.documentElement.setAttribute("data-theme", theme);
            document.documentElement.classList.toggle("dark", theme === "dark");
            try {
                localStorage.setItem(THEME_STORAGE_KEY, theme);
            } catch (e) {}

            // Actualizar títulos de botones
            document.querySelectorAll("#sidebar-theme-toggle, [data-action='toggle-theme']").forEach(btn => {
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

        document.querySelectorAll("#sidebar-theme-toggle, [data-action='toggle-theme']").forEach(btn => {
            btn.addEventListener("click", (e) => {
                e.preventDefault();
                toggleTheme();
            });
        });

        // 2. Control de Escala Tipográfica y de Elementos (11pt | 12pt | 13pt)
        const getCurrentFontSize = () => {
            const attr = document.documentElement.getAttribute("data-font-size");
            if (VALID_FONT_SIZES.includes(attr)) return attr;
            try {
                const stored = localStorage.getItem(FONT_SIZE_STORAGE_KEY);
                if (VALID_FONT_SIZES.includes(stored)) return stored;
            } catch (e) {}
            return "12pt";
        };

        const applyFontSize = (size, notify = false) => {
            if (!VALID_FONT_SIZES.includes(size)) {
                size = "12pt";
            }
            document.documentElement.setAttribute("data-font-size", size);
            try {
                localStorage.setItem(FONT_SIZE_STORAGE_KEY, size);
            } catch (e) {}

            // Actualizar estado activo en los botones triples
            document.querySelectorAll(".btn-font-scale").forEach(btn => {
                const btnSize = btn.getAttribute("data-font-size");
                const isActive = (btnSize === size);
                btn.classList.toggle("is-active", isActive);
                btn.setAttribute("aria-pressed", String(isActive));
            });

            // Actualizar etiqueta de tamaño actual en sidebar expandido
            const labelCurrent = document.getElementById("label-current-font-size");
            if (labelCurrent) {
                labelCurrent.textContent = size;
            }

            // Actualizar indicador numérico en modo colapsado mini
            const miniBadge = document.getElementById("mini-font-size-badge");
            if (miniBadge) {
                miniBadge.textContent = size.replace("pt", "");
            }

            const cycleBtn = document.getElementById("btn-font-scale-cycle");
            if (cycleBtn) {
                cycleBtn.setAttribute("title", `Tamaño de vista: ${size} (Click para alternar)`);
            }

            if (notify && window.Toast) {
                const descripcion = size === "11pt"
                    ? "Compacta (11pt) — Mayor densidad de elementos"
                    : (size === "13pt"
                        ? "Grande (13pt) — Lectura descansada y clara"
                        : "Estándar (12pt) — Tamaño regular equilibrado");
                window.Toast.info(descripcion, "Escala Visual", 2200);
            }
        };

        const cycleFontSize = () => {
            const current = getCurrentFontSize();
            const idx = VALID_FONT_SIZES.indexOf(current);
            const nextIdx = (idx + 1) % VALID_FONT_SIZES.length;
            applyFontSize(VALID_FONT_SIZES[nextIdx], true);
        };

        document.querySelectorAll(".btn-font-scale").forEach(btn => {
            btn.addEventListener("click", (e) => {
                e.preventDefault();
                const targetSize = btn.getAttribute("data-font-size");
                if (targetSize) {
                    applyFontSize(targetSize, true);
                }
            });
        });

        const cycleBtn = document.getElementById("btn-font-scale-cycle");
        cycleBtn?.addEventListener("click", (e) => {
            e.preventDefault();
            cycleFontSize();
        });

        // Aplicar estado inicial
        applyFontSize(getCurrentFontSize(), false);

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

            // Al hacer click en el logo compacto 'SE', expandir el sidebar
            const brandMini = document.getElementById("sidebar-brand-mini");
            brandMini?.addEventListener("click", (e) => {
                if (sidebar.classList.contains("is-collapsed")) {
                    e.preventDefault();
                    collapseBtn?.click();
                }
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
