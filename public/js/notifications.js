/**
 * SISCO-EDU - Sistema Unificado de Notificaciones y Diálogos
 * Reemplaza los alerts/confirms primitivos por Toasts y Modales accesibles y adaptables a tema claro/oscuro.
 */
(() => {
    "use strict";

    // 1. Contenedor de Toasts
    let toastContainer = null;

    function getOrCreateToastContainer() {
        if (!toastContainer || !document.body.contains(toastContainer)) {
            toastContainer = document.createElement("div");
            toastContainer.id = "toast-container";
            toastContainer.className = "toast-container";
            toastContainer.setAttribute("aria-live", "polite");
            toastContainer.setAttribute("aria-atomic", "false");
            document.body.appendChild(toastContainer);
        }
        return toastContainer;
    }

    // Iconos SVG semánticos
    const ICONOS = {
        success: `<svg class="toast-icon text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>`,
        error: `<svg class="toast-icon text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>`,
        warning: `<svg class="toast-icon text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
        </svg>`,
        info: `<svg class="toast-icon text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>`
    };

    function escaparTexto(str) {
        return String(str ?? "")
            .replaceAll("&", "&amp;")
            .replaceAll("<", "&lt;")
            .replaceAll(">", "&gt;")
            .replaceAll('"', "&quot;")
            .replaceAll("'", "&#039;");
    }

    /**
     * Muestra una notificación Toast flotante
     */
    function showToast(tipo, mensaje, titulo = null, duracion = 4000) {
        const container = getOrCreateToastContainer();

        // Evitar saturación visual en pantalla: mantener como máximo 2 toasts activos simultáneamente
        const activos = container.querySelectorAll(".toast-item:not(.toast-closing)");
        if (activos.length >= 2) {
            const masViejo = activos[0];
            masViejo.classList.add("toast-closing");
            setTimeout(() => {
                if (masViejo.parentNode) {
                    masViejo.parentNode.removeChild(masViejo);
                }
            }, 180);
        }

        const toast = document.createElement("div");
        toast.className = `toast-item toast-${tipo}`;
        toast.setAttribute("role", tipo === "error" ? "alert" : "status");

        const icono = ICONOS[tipo] || ICONOS.info;
        const tituloHtml = titulo ? `<strong class="toast-title">${escaparTexto(titulo)}</strong>` : "";

        toast.innerHTML = `
            <div class="toast-content flex items-start gap-3">
                <div class="toast-icon-wrap flex-shrink-0 mt-0.5">${icono}</div>
                <div class="toast-body flex-1 min-w-0">
                    ${tituloHtml}
                    <div class="toast-message text-sm">${escaparTexto(mensaje)}</div>
                </div>
                <button type="button" class="toast-close text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors" aria-label="Cerrar notificación">×</button>
            </div>
            ${duracion > 0 ? `<div class="toast-progress"><div class="toast-progress-bar"></div></div>` : ""}
        `;

        let timer = null;
        let remaining = duracion;
        let start = Date.now();

        const dismiss = () => {
            if (toast.classList.contains("toast-closing")) return;
            toast.classList.add("toast-closing");
            setTimeout(() => {
                if (toast.parentNode) {
                    toast.parentNode.removeChild(toast);
                }
            }, 220);
        };

        const startTimer = () => {
            if (duracion <= 0) return;
            start = Date.now();
            const bar = toast.querySelector(".toast-progress-bar");
            if (bar) {
                bar.style.transition = `width ${remaining}ms linear`;
                bar.style.width = "0%";
            }
            timer = setTimeout(dismiss, remaining);
        };

        const pauseTimer = () => {
            if (duracion <= 0) return;
            clearTimeout(timer);
            const elapsed = Date.now() - start;
            remaining = Math.max(100, remaining - elapsed);
            const bar = toast.querySelector(".toast-progress-bar");
            if (bar) {
                bar.style.transition = "none";
            }
        };

        toast.querySelector(".toast-close").addEventListener("click", dismiss);
        toast.addEventListener("mouseenter", pauseTimer);
        toast.addEventListener("mouseleave", startTimer);

        container.appendChild(toast);
        requestAnimationFrame(() => {
            toast.classList.add("toast-visible");
            startTimer();
        });

        return { dismiss };
    }

    const Toast = {
        success: (mensaje, titulo = "Operación exitosa", duracion = 4000) => showToast("success", mensaje, titulo, duracion),
        error: (mensaje, titulo = "Error", duracion = 5000) => showToast("error", mensaje, titulo, duracion),
        warning: (mensaje, titulo = "Atención", duracion = 4500) => showToast("warning", mensaje, titulo, duracion),
        info: (mensaje, titulo = "Información", duracion = 4000) => showToast("info", mensaje, titulo, duracion),
    };

    /**
     * Diálogo de confirmación moderno basado en Promises
     */
    function showConfirmModal(opciones = {}) {
        return new Promise((resolve) => {
            const {
                title = "¿Estás seguro?",
                message = "Esta acción no se puede deshacer.",
                confirmText = "Confirmar",
                cancelText = "Cancelar",
                type = "danger" // danger | warning | primary
            } = typeof opciones === "string" ? { message: opciones } : opciones;

            const modal = document.createElement("div");
            modal.className = "app-modal app-confirm-modal flex items-center justify-center";
            modal.setAttribute("role", "dialog");
            modal.setAttribute("aria-modal", "true");

            const iconSvg = type === "danger"
                ? `<div class="w-12 h-12 rounded-full bg-rose-100 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400 flex items-center justify-center flex-shrink-0">${ICONOS.error}</div>`
                : type === "warning"
                    ? `<div class="w-12 h-12 rounded-full bg-amber-100 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 flex items-center justify-center flex-shrink-0">${ICONOS.warning}</div>`
                    : `<div class="w-12 h-12 rounded-full bg-blue-100 dark:bg-blue-950/60 text-blue-600 dark:text-blue-400 flex items-center justify-center flex-shrink-0">${ICONOS.info}</div>`;

            const btnConfirmClass = type === "danger" ? "btn btn-danger" : type === "warning" ? "btn btn-warning" : "btn btn-primary";

            modal.innerHTML = `
                <div class="app-confirm-backdrop"></div>
                <div class="app-modal-dialog app-confirm-dialog max-w-md w-full p-6 relative z-10 animate-scale-up">
                    <div class="flex items-start gap-4">
                        ${iconSvg}
                        <div class="flex-1 min-w-0">
                            <h3 class="text-lg font-bold text-slate-900 dark:text-slate-100 leading-snug">${escaparTexto(title)}</h3>
                            <p class="text-sm text-slate-600 dark:text-slate-400 mt-2 leading-relaxed">${escaparTexto(message)}</p>
                        </div>
                    </div>
                    <div class="flex items-center justify-end gap-3 mt-6 pt-4 border-t border-slate-100 dark:border-slate-800">
                        <button type="button" class="btn btn-muted js-cancel">${escaparTexto(cancelText)}</button>
                        <button type="button" class="${btnConfirmClass} js-confirm">${escaparTexto(confirmText)}</button>
                    </div>
                </div>
            `;

            const cleanup = (resultado) => {
                modal.classList.add("modal-closing");
                document.removeEventListener("keydown", onKeyDown);
                setTimeout(() => {
                    if (modal.parentNode) modal.parentNode.removeChild(modal);
                }, 180);
                resolve(resultado);
            };

            const onKeyDown = (e) => {
                if (e.key === "Escape") cleanup(false);
            };

            modal.querySelector(".js-cancel").addEventListener("click", () => cleanup(false));
            modal.querySelector(".js-confirm").addEventListener("click", () => cleanup(true));
            modal.querySelector(".app-confirm-backdrop").addEventListener("click", () => cleanup(false));
            document.addEventListener("keydown", onKeyDown);

            document.body.appendChild(modal);
            setTimeout(() => {
                modal.querySelector(".js-confirm")?.focus();
            }, 50);
        });
    }

    /**
     * Diálogo de alerta moderno (reemplaza alert())
     */
    function showAlertModal(mensaje, titulo = "Aviso") {
        return showConfirmModal({
            title: titulo,
            message: mensaje,
            confirmText: "Entendido",
            cancelText: "",
            type: "primary"
        });
    }

    // Exportar al objeto global
    window.Toast = Toast;
    window.SISCO = window.SISCO || {};
    window.SISCO.Toast = Toast;
    window.SISCO.confirm = showConfirmModal;
    window.SISCO.alert = showAlertModal;
    window.mostrarNotificacion = (tipo, mensaje, titulo) => {
        if (Toast[tipo]) return Toast[tipo](mensaje, titulo);
        return Toast.info(mensaje, titulo);
    };

    // Polyfill no invasivo de window.alert para transformar llamadas heredadas a Toasts
    const nativeAlert = window.alert;
    window.alert = function (msg) {
        try {
            Toast.info(String(msg), "Aviso del Sistema");
        } catch (e) {
            nativeAlert(msg);
        }
    };
})();
