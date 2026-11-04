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

            // 5. Control global de modales y tecla Escape
            const syncModalScrollLock = () => {
                const hasOpenModal = Array.from(document.querySelectorAll(
                    '.app-modal:not(.hidden), [role="dialog"]:not(.hidden), [id^="modal-"]:not(.hidden)'
                )).some(el => {
                    if (['H1', 'H2', 'H3', 'H4', 'SPAN', 'P', 'INPUT', 'SELECT', 'LABEL'].includes(el.tagName)) return false;
                    const style = window.getComputedStyle(el);
                    return style.display !== 'none' && style.visibility !== 'hidden' && (el.offsetWidth > 0 || el.offsetHeight > 0);
                });

                document.body.classList.toggle('overflow-hidden', hasOpenModal);
            };

            const modalObserver = new MutationObserver((mutations) => {
                let shouldCheck = false;
                for (const m of mutations) {
                    if (m.type === 'attributes' && (m.attributeName === 'class' || m.attributeName === 'style')) {
                        const t = m.target;
                        if (t && ((t.id && t.id.startsWith('modal-')) || t.classList?.contains('app-modal') || t.getAttribute?.('role') === 'dialog')) {
                            shouldCheck = true;
                            break;
                        }
                    } else if (m.type === 'childList') {
                        for (const n of m.addedNodes) {
                            if (n.nodeType === 1 && ((n.id && n.id.startsWith('modal-')) || n.classList?.contains('app-modal') || n.getAttribute?.('role') === 'dialog')) {
                                shouldCheck = true;
                                break;
                            }
                        }
                        if (shouldCheck) break;
                        for (const n of m.removedNodes) {
                            if (n.nodeType === 1 && ((n.id && n.id.startsWith('modal-')) || n.classList?.contains('app-modal') || n.getAttribute?.('role') === 'dialog')) {
                                shouldCheck = true;
                                break;
                            }
                        }
                        if (shouldCheck) break;
                    }
                }
                if (shouldCheck) {
                    syncModalScrollLock();
                }
            });
            modalObserver.observe(document.body, { attributes: true, attributeFilter: ['class', 'style'], childList: true, subtree: true });

            document.addEventListener("keydown", (event) => {
                if (event.key === "Escape") {
                    const visibleModals = Array.from(document.querySelectorAll(
                        '.app-modal:not(.hidden), [role="dialog"]:not(.hidden), [id^="modal-"]:not(.hidden)'
                    )).filter(el => {
                        if (['H1', 'H2', 'H3', 'H4', 'SPAN', 'P', 'INPUT', 'SELECT', 'LABEL'].includes(el.tagName)) return false;
                        const style = window.getComputedStyle(el);
                        return style.display !== 'none' && style.visibility !== 'hidden' && (el.offsetWidth > 0 || el.offsetHeight > 0);
                    });

                    if (visibleModals.length > 0) {
                        event.preventDefault();
                        const topModal = visibleModals[visibleModals.length - 1];
                        const closeBtn = topModal.querySelector(
                            '[data-action="close"], .app-modal-close, button[onclick*="cerrar"], button[onclick*="Cerrar"], button[id*="cerrar"], button[id*="close"]'
                        );
                        if (closeBtn) {
                            closeBtn.click();
                        } else {
                            topModal.classList.add("hidden");
                        }
                        setTimeout(syncModalScrollLock, 50);
                        return;
                    }

                    setOpen(false);
                }
            });

            // Cierre accesible al hacer click en el backdrop exterior del modal
            document.addEventListener("click", (event) => {
                const target = event.target;
                if (!target || !target.classList) return;
                if (
                    (target.classList.contains("app-modal") || (target.id && target.id.startsWith("modal-") && target.classList.contains("fixed"))) &&
                    !target.classList.contains("hidden")
                ) {
                    const closeBtn = target.querySelector(
                        '[data-action="close"], .app-modal-close, button[onclick*="cerrar"], button[onclick*="Cerrar"], button[id*="cerrar"], button[id*="close"]'
                    );
                    if (closeBtn) {
                        closeBtn.click();
                    } else {
                        target.classList.add("hidden");
                    }
                    setTimeout(syncModalScrollLock, 50);
                }
            });

            // 6. Monitoreo de Hardware IoT con Caché Inteligente (USB Registrador & Gateway LoRa)
            // Se utiliza sessionStorage con TTL de 5 minutos para evitar saturar la red y el hardware en cada navegación.
            const IOT_CACHE_KEY = "sisco_iot_hw_status";
            const IOT_CACHE_TTL_MS = 5 * 60 * 1000; // 5 minutos

            const verificarEstadoHardwareIoT = async (manual = false) => {
                const dotReg = document.getElementById("dot-iot-registrador");
                const dotGate = document.getElementById("dot-iot-gateway");
                const dotMiniReg = document.getElementById("dot-iot-mini-registrador");
                const dotMiniGate = document.getElementById("dot-iot-mini-gateway");
                const chipReg = document.getElementById("chip-iot-registrador");
                const chipGate = document.getElementById("chip-iot-gateway");
                const iconPing = document.getElementById("icon-ping-iot-sidebar");

                if (!dotReg && !dotGate) return;

                const now = Date.now();

                // Si no es refresco manual, verificar si tenemos datos en caché válidos
                if (!manual) {
                    try {
                        const raw = sessionStorage.getItem(IOT_CACHE_KEY);
                        if (raw) {
                            const cached = JSON.parse(raw);
                            if (cached && (now - (cached.timestamp || 0) < IOT_CACHE_TTL_MS)) {
                                if (cached.reg) {
                                    if (dotReg) dotReg.className = cached.reg.dotClass;
                                    if (dotMiniReg) dotMiniReg.className = cached.reg.dotMiniClass;
                                    if (chipReg) chipReg.title = cached.reg.title;
                                }
                                if (cached.gate) {
                                    if (dotGate) dotGate.className = cached.gate.dotClass;
                                    if (dotMiniGate) dotMiniGate.className = cached.gate.dotMiniClass;
                                    if (chipGate) chipGate.title = cached.gate.title;
                                }
                                return; // Estado restaurado desde caché, sin peticiones de red
                            }
                        }
                    } catch (e) {
                        // Fallback a consulta directa si falla lectura de caché
                    }
                }

                if (iconPing) iconPing.classList.add("animate-spin");

                const baseUrl = (window.BASE_URL || "/").replace(/\/+$/, "");
                const nuevoEstado = { timestamp: now, reg: null, gate: null };

                // Test Registrador USB / Wi-Fi
                try {
                    const r = await fetch(`${baseUrl}/src/api/Configuracion/probar_conexion.php?tipo=registrador`, { cache: 'no-cache' });
                    const j = await r.json();
                    const online = Boolean(j.online === true || (j.status === 'success' && j.data?.online === true));
                    const ms = j.latencia_ms || j.data?.latencia_ms;

                    if (online) {
                        const title = `Lector USB / Wi-Fi: ONLINE${ms ? ` (${ms} ms)` : ''}`;
                        const dotClass = "w-2 h-2 rounded-full bg-emerald-500 shrink-0";
                        const dotMiniClass = "w-2.5 h-2.5 rounded-full bg-emerald-500 shrink-0";
                        if (dotReg) dotReg.className = dotClass;
                        if (dotMiniReg) dotMiniReg.className = dotMiniClass;
                        if (chipReg) chipReg.title = title;
                        nuevoEstado.reg = { title, dotClass, dotMiniClass };
                    } else {
                        const title = `Lector USB / Wi-Fi: ${j.message || 'Fuera de línea'}`;
                        const dotClass = "w-2 h-2 rounded-full bg-amber-500 shrink-0";
                        const dotMiniClass = "w-2.5 h-2.5 rounded-full bg-amber-500 shrink-0";
                        if (dotReg) dotReg.className = dotClass;
                        if (dotMiniReg) dotMiniReg.className = dotMiniClass;
                        if (chipReg) chipReg.title = title;
                        nuevoEstado.reg = { title, dotClass, dotMiniClass };
                    }
                } catch (e) {
                    const title = "Lector USB: Error de consulta";
                    const dotClass = "w-2 h-2 rounded-full bg-rose-500 shrink-0";
                    const dotMiniClass = "w-2.5 h-2.5 rounded-full bg-rose-500 shrink-0";
                    if (dotReg) dotReg.className = dotClass;
                    if (dotMiniReg) dotMiniReg.className = dotMiniClass;
                    if (chipReg) chipReg.title = title;
                    nuevoEstado.reg = { title, dotClass, dotMiniClass };
                }

                // Test Gateway LoRa
                try {
                    const r = await fetch(`${baseUrl}/src/api/Configuracion/probar_conexion.php?tipo=gateway`, { cache: 'no-cache' });
                    const j = await r.json();
                    const online = Boolean(j.online === true || (j.status === 'success' && j.data?.online === true));
                    const ms = j.latencia_ms || j.data?.latencia_ms;

                    if (online) {
                        const title = `Gateway LoRa: ONLINE${ms ? ` (${ms} ms)` : ''}`;
                        const dotClass = "w-2 h-2 rounded-full bg-emerald-500 shrink-0";
                        const dotMiniClass = "w-2.5 h-2.5 rounded-full bg-emerald-500 shrink-0";
                        if (dotGate) dotGate.className = dotClass;
                        if (dotMiniGate) dotMiniGate.className = dotMiniClass;
                        if (chipGate) chipGate.title = title;
                        nuevoEstado.gate = { title, dotClass, dotMiniClass };
                    } else {
                        const title = `Gateway LoRa: ${j.message || 'Fuera de línea'}`;
                        const dotClass = "w-2 h-2 rounded-full bg-amber-500 shrink-0";
                        const dotMiniClass = "w-2.5 h-2.5 rounded-full bg-amber-500 shrink-0";
                        if (dotGate) dotGate.className = dotClass;
                        if (dotMiniGate) dotMiniGate.className = dotMiniClass;
                        if (chipGate) chipGate.title = title;
                        nuevoEstado.gate = { title, dotClass, dotMiniClass };
                    }
                } catch (e) {
                    const title = "Gateway LoRa: Error de consulta";
                    const dotClass = "w-2 h-2 rounded-full bg-rose-500 shrink-0";
                    const dotMiniClass = "w-2.5 h-2.5 rounded-full bg-rose-500 shrink-0";
                    if (dotGate) dotGate.className = dotClass;
                    if (dotMiniGate) dotMiniGate.className = dotMiniClass;
                    if (chipGate) chipGate.title = title;
                    nuevoEstado.gate = { title, dotClass, dotMiniClass };
                }

                try {
                    sessionStorage.setItem(IOT_CACHE_KEY, JSON.stringify(nuevoEstado));
                } catch (e) {}

                if (iconPing) iconPing.classList.remove("animate-spin");

                if (manual && window.Toast) {
                    window.Toast.info("Diagnóstico de enlace hardware actualizado.", "Hardware IoT", 2500);
                }
            };

            const btnPingIot = document.getElementById("btn-ping-iot-sidebar");
            btnPingIot?.addEventListener("click", (e) => {
                e.preventDefault();
                verificarEstadoHardwareIoT(true);
            });

            // Verificación inicial diferida tras 1.2 segundos
            setTimeout(verificarEstadoHardwareIoT, 1200);

            window.addEventListener("resize", () => {
                if (window.innerWidth >= 768) {
                    setOpen(false);
                }
            });

            setOpen(false);
        }
    });
})();
