<!-- Modal Importación Masiva de Profesores -->
<div id="modal-importar-profesores" class="app-modal hidden" role="dialog" aria-modal="true" aria-labelledby="mip-titulo">
    <div class="app-modal-dialog max-w-xl bg-white dark:bg-slate-900 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-800 p-5 sm:p-6 space-y-5 overflow-y-auto max-h-[90vh]">
        
        <!-- Header -->
        <div class="flex items-start justify-between gap-4 pb-3 border-b border-slate-100 dark:border-slate-800">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-purple-50 dark:bg-purple-950/50 text-purple-600 dark:text-purple-400 flex items-center justify-center text-xl shrink-0">
                    👨‍🏫
                </div>
                <div>
                    <h3 id="mip-titulo" class="text-lg font-bold text-slate-900 dark:text-white leading-tight">
                        Importación Masiva de Profesores
                    </h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                        Cargá nóminas docentes desde archivos CSV compatibles con Microsoft Excel.
                    </p>
                </div>
            </div>
            <button type="button" onclick="cerrarModalImportarProfesores()" class="app-modal-close text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-xl font-bold" aria-label="Cerrar modal">
                ×
            </button>
        </div>

        <!-- Descarga de Plantilla e Instrucciones -->
        <div class="p-3.5 rounded-xl bg-purple-50/70 dark:bg-purple-950/30 border border-purple-200/60 dark:border-purple-900/40 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs">
            <div class="space-y-1">
                <p class="font-bold text-purple-900 dark:text-purple-300">¿No tenés el formato correcto?</p>
                <p class="text-slate-600 dark:text-slate-400">Descargá la plantilla modelo lista para abrir y editar en Excel.</p>
            </div>
            <a href="<?= base_url('src/api/Profesor/plantilla_profesores.php') ?>" class="btn btn-secondary text-xs py-1.5 px-3 inline-flex items-center gap-1.5 shrink-0 self-start sm:self-auto font-semibold">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                <span>Descargar Plantilla (.csv)</span>
            </a>
        </div>

        <!-- Formulario -->
        <form id="form-importar-profesores" class="space-y-4" enctype="multipart/form-data">
            
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <!-- Selector opcional de Grado por defecto -->
                <div>
                    <label for="import-prof-grado-defecto" class="block mb-1.5 font-semibold text-xs uppercase tracking-wider text-slate-700 dark:text-slate-300">
                        Curso por Defecto (Opcional)
                    </label>
                    <select id="import-prof-grado-defecto" name="id_grado_defecto" class="app-input w-full text-xs">
                        <option value="">Detectar desde el archivo</option>
                    </select>
                </div>

                <!-- Selector opcional de Materia por defecto -->
                <div>
                    <label for="import-prof-materia-defecto" class="block mb-1.5 font-semibold text-xs uppercase tracking-wider text-slate-700 dark:text-slate-300">
                        Materia por Defecto (Opcional)
                    </label>
                    <select id="import-prof-materia-defecto" name="id_materia_defecto" class="app-input w-full text-xs">
                        <option value="">Detectar desde el archivo</option>
                    </select>
                </div>
            </div>

            <!-- Zona de Subida de Archivo (Dropzone) -->
            <div>
                <label class="block mb-1.5 font-semibold text-xs uppercase tracking-wider text-slate-700 dark:text-slate-300">
                    Archivo de Profesores (.csv / Excel) *
                </label>
                <div class="relative border-2 border-dashed border-slate-300 dark:border-slate-700 rounded-2xl p-6 text-center hover:border-purple-500 transition-colors bg-slate-50/50 dark:bg-slate-950/40">
                    <input type="file" id="import-prof-file" name="archivo_csv" accept=".csv,text/csv,text/plain" required
                           class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                    <div class="space-y-2 pointer-events-none">
                        <div class="w-10 h-10 mx-auto rounded-xl bg-purple-100 dark:bg-purple-900/40 text-purple-600 dark:text-purple-300 flex items-center justify-center text-lg">
                            📄
                        </div>
                        <p id="import-prof-file-label" class="text-xs font-semibold text-slate-700 dark:text-slate-200">
                            Arrastrá tu archivo CSV acá o <span class="text-purple-600 dark:text-purple-400 underline">buscalo en tu computadora</span>
                        </p>
                        <p class="text-[11px] text-slate-400">Archivos CSV delimitados por coma o punto y coma (máx. 10MB)</p>
                    </div>
                </div>
            </div>

            <!-- Panel de Resultados (oculto por defecto) -->
            <div id="import-prof-resultados" class="hidden p-4 rounded-xl bg-slate-50 dark:bg-slate-800/40 border border-slate-200 dark:border-slate-700 space-y-3 text-xs">
                <div class="flex items-center justify-between font-bold">
                    <span class="text-slate-800 dark:text-slate-200">Resumen del Procesamiento:</span>
                    <span id="import-prof-total-filas" class="text-slate-500 text-[11px]">0 filas</span>
                </div>
                <div class="grid grid-cols-4 gap-2 text-center">
                    <div class="p-2 rounded-lg bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 text-emerald-700 dark:text-emerald-300">
                        <div class="text-lg font-black" id="import-prof-res-creados">0</div>
                        <div class="text-[9px] font-semibold uppercase">Nuevos</div>
                    </div>
                    <div class="p-2 rounded-lg bg-blue-50 dark:bg-blue-950/40 border border-blue-200 dark:border-blue-800 text-blue-700 dark:text-blue-300">
                        <div class="text-lg font-black" id="import-prof-res-actualizados">0</div>
                        <div class="text-[9px] font-semibold uppercase">Actualizados</div>
                    </div>
                    <div class="p-2 rounded-lg bg-purple-50 dark:bg-purple-950/40 border border-purple-200 dark:border-purple-800 text-purple-700 dark:text-purple-300">
                        <div class="text-lg font-black" id="import-prof-res-asignaciones">0</div>
                        <div class="text-[9px] font-semibold uppercase">Asignaciones</div>
                    </div>
                    <div class="p-2 rounded-lg bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 text-rose-700 dark:text-rose-300">
                        <div class="text-lg font-black" id="import-prof-res-errores">0</div>
                        <div class="text-[9px] font-semibold uppercase">Observaciones</div>
                    </div>
                </div>
                <div id="import-prof-lista-errores" class="hidden max-h-32 overflow-y-auto space-y-1 p-2 rounded-lg bg-rose-50/50 dark:bg-rose-950/20 text-rose-700 dark:text-rose-300 text-[11px] font-mono">
                    <!-- Dinámico -->
                </div>
            </div>

            <!-- Footer / Acciones -->
            <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100 dark:border-slate-800">
                <button type="button" onclick="cerrarModalImportarProfesores()" class="btn btn-muted text-xs py-2 px-4">
                    Cancelar
                </button>
                <button type="submit" id="btn-submit-import-prof" class="btn btn-primary text-xs py-2 px-5 font-bold inline-flex items-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                    <span>Iniciar Importación</span>
                </button>
            </div>
        </form>

    </div>
</div>

<script>
function abrirModalImportarProfesores(idGradoSeleccionado = null) {
    const modal = document.getElementById('modal-importar-profesores');
    if (!modal) return;

    // Resetear formulario
    const form = document.getElementById('form-importar-profesores');
    if (form) form.reset();

    const lbl = document.getElementById('import-prof-file-label');
    if (lbl) {
        lbl.innerHTML = 'Arrastrá tu archivo CSV acá o <span class="text-purple-600 dark:text-purple-400 underline">buscalo en tu computadora</span>';
    }

    const resPanel = document.getElementById('import-prof-resultados');
    if (resPanel) resPanel.classList.add('hidden');

    const listaErr = document.getElementById('import-prof-lista-errores');
    if (listaErr) {
        listaErr.innerHTML = '';
        listaErr.classList.add('hidden');
    }

    // Preseleccionar grado si viene provisto y es numérico
    const selGrado = document.getElementById('import-prof-grado-defecto');
    if (selGrado && idGradoSeleccionado && idGradoSeleccionado !== 'TODOS') {
        selGrado.value = String(idGradoSeleccionado);
    }

    modal.classList.remove('hidden');
}

function cerrarModalImportarProfesores() {
    const modal = document.getElementById('modal-importar-profesores');
    if (modal) modal.classList.add('hidden');
}

// Configurar listeners de archivo y envío
document.addEventListener('DOMContentLoaded', () => {
    const fileInput = document.getElementById('import-prof-file');
    const fileLabel = document.getElementById('import-prof-file-label');
    const form = document.getElementById('form-importar-profesores');
    const btnSubmit = document.getElementById('btn-submit-import-prof');

    if (fileInput && fileLabel) {
        fileInput.addEventListener('change', () => {
            if (fileInput.files && fileInput.files[0]) {
                const f = fileInput.files[0];
                fileLabel.innerHTML = `Archivo seleccionado: <strong class="text-purple-600 dark:text-purple-400 font-bold">${f.name}</strong> (${(f.size / 1024).toFixed(1)} KB)`;
            }
        });
    }

    if (form) {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();

            if (!fileInput || !fileInput.files || !fileInput.files[0]) {
                alert("Por favor seleccioná un archivo CSV para importar.");
                return;
            }

            const formData = new FormData(form);
            const originalBtnHtml = btnSubmit ? btnSubmit.innerHTML : '';
            if (btnSubmit) {
                btnSubmit.disabled = true;
                btnSubmit.innerHTML = `
                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white inline-block" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                    <span>Procesando archivo...</span>
                `;
            }

            try {
                const base = (window.BASE_URL || '').replace(/\/+$/, '');
                const res = await fetch(`${base}/src/api/Profesor/importar_profesores.php`, {
                    method: 'POST',
                    body: formData
                });

                const json = await res.json();

                if (!res.ok && json.status !== 'success') {
                    throw new Error(json.message || `HTTP ${res.status}`);
                }

                const data = json.data || {};
                const resPanel = document.getElementById('import-prof-resultados');
                const lblTotal = document.getElementById('import-prof-total-filas');
                const lblCreados = document.getElementById('import-prof-res-creados');
                const lblActualizados = document.getElementById('import-prof-res-actualizados');
                const lblAsignaciones = document.getElementById('import-prof-res-asignaciones');
                const lblErrores = document.getElementById('import-prof-res-errores');
                const listaErr = document.getElementById('import-prof-lista-errores');

                if (resPanel) resPanel.classList.remove('hidden');
                if (lblTotal) lblTotal.textContent = `${data.total_filas || 0} filas procesadas`;
                if (lblCreados) lblCreados.textContent = String(data.creados || 0);
                if (lblActualizados) lblActualizados.textContent = String(data.actualizados || 0);
                if (lblAsignaciones) lblAsignaciones.textContent = String(data.asignaciones || 0);
                if (lblErrores) lblErrores.textContent = String((data.errores || []).length);

                if (listaErr && data.errores && data.errores.length > 0) {
                    listaErr.innerHTML = data.errores.map(err => `<div>• ${err}</div>`).join('');
                    listaErr.classList.remove('hidden');
                } else if (listaErr) {
                    listaErr.classList.add('hidden');
                }

                // Recargar nómina si existe la función
                if (typeof cargarProfesores === 'function') {
                    const selG = document.getElementById('filtro-grado');
                    const val = selG ? selG.value : 'TODOS';
                    if (val) {
                        cargarProfesores(val);
                    }
                }

            } catch (err) {
                console.error("Error en importación de profesores:", err);
                alert("Error al importar profesores: " + err.message);
            } finally {
                if (btnSubmit) {
                    btnSubmit.disabled = false;
                    btnSubmit.innerHTML = originalBtnHtml;
                }
            }
        });
    }
});
</script>
