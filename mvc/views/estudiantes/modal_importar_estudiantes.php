<!-- Modal Importación Masiva de Estudiantes (Rediseñado con Tipografía Grande y Accesibilidad) -->
<div id="modal-importar-estudiantes" class="app-modal hidden" role="dialog" aria-modal="true" aria-labelledby="mie-titulo">
    <div class="app-modal-dialog max-w-2xl bg-white dark:bg-slate-900 rounded-3xl shadow-2xl border border-slate-200 dark:border-slate-800 p-6 sm:p-8 space-y-6 overflow-y-auto max-h-[92vh]">
        
        <!-- Header -->
        <div class="flex items-start justify-between gap-4 pb-4 border-b border-slate-200 dark:border-slate-800">
            <div class="flex items-center gap-3.5">
                <div class="w-12 h-12 rounded-2xl bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300 flex items-center justify-center text-2xl font-bold shrink-0 border border-blue-200 dark:border-blue-800">
                    📥
                </div>
                <div>
                    <h3 id="mie-titulo" class="text-xl sm:text-2xl font-extrabold text-slate-900 dark:text-white leading-tight">
                        Importación Masiva de Estudiantes
                    </h3>
                    <p class="text-base text-slate-600 dark:text-slate-400 mt-0.5">
                        Cargue nóminas estudiantiles desde planillas CSV compatibles con Microsoft Excel.
                    </p>
                </div>
            </div>
            <button type="button" onclick="cerrarModalImportarEstudiantes()" class="app-modal-close text-slate-400 hover:text-slate-700 dark:hover:text-white text-2xl font-bold p-2" aria-label="Cerrar modal">
                ×
            </button>
        </div>

        <!-- Descarga de Plantilla e Instrucciones Accesibles -->
        <div class="p-4 rounded-2xl bg-blue-50/80 dark:bg-blue-950/40 border border-blue-200 dark:border-blue-900/50 flex flex-col sm:flex-row sm:items-center justify-between gap-4 text-base">
            <div class="space-y-1">
                <p class="font-extrabold text-blue-900 dark:text-blue-200">¿Desea revisar el formato esperado?</p>
                <p class="text-slate-700 dark:text-slate-300 text-sm sm:text-base leading-relaxed">
                    Descargue la plantilla modelo preconfigurada con columnas para nombre, apellido, cédula y curso.
                </p>
            </div>
            <a href="<?= base_url('src/api/Estudiante/plantilla_estudiantes.php') ?>" class="btn btn-secondary text-base py-2.5 px-4 inline-flex items-center gap-2 shrink-0 self-start sm:self-auto font-bold min-h-[44px]">
                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                <span>Descargar Plantilla (.csv)</span>
            </a>
        </div>

        <!-- Formulario -->
        <form id="form-importar-estudiantes" class="space-y-5" enctype="multipart/form-data">
            
            <!-- Selector opcional de Grado por defecto -->
            <div>
                <label for="import-est-grado-defecto" class="block mb-1.5 font-bold text-base text-slate-800 dark:text-slate-200">
                    Asignar Grado / Curso por Defecto
                </label>
                <select id="import-est-grado-defecto" name="id_grado_defecto" class="app-input w-full text-base py-2.5 px-3.5 rounded-xl min-h-[46px]">
                    <option value="">Detectar desde la columna 'grado' del archivo</option>
                </select>
                <p class="text-sm text-slate-600 dark:text-slate-400 mt-1 font-medium">
                    Si el archivo incluye la columna de curso, este selector servirá de respaldo para filas vacías.
                </p>
            </div>

            <!-- Zona de Subida de Archivo (Dropzone) -->
            <div>
                <label class="block mb-1.5 font-bold text-base text-slate-800 dark:text-slate-200">
                    Archivo de Estudiantes (.csv / Excel) *
                </label>
                <div class="relative border-2 border-dashed border-slate-300 dark:border-slate-700 rounded-3xl p-8 text-center hover:border-blue-500 transition-colors bg-slate-50/70 dark:bg-slate-950/40">
                    <input type="file" id="import-est-file" name="archivo_csv" accept=".csv,text/csv,text/plain" required
                           class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                    <div class="space-y-2 pointer-events-none">
                        <div class="w-12 h-12 mx-auto rounded-2xl bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300 flex items-center justify-center text-2xl font-bold">
                            📄
                        </div>
                        <p id="import-est-file-label" class="text-base font-bold text-slate-800 dark:text-slate-200">
                            Arrastre su archivo CSV aquí o <span class="text-blue-600 dark:text-blue-400 underline">búsquelo en su equipo</span>
                        </p>
                        <p class="text-sm text-slate-500 font-medium">Archivos CSV delimitados por coma o punto y coma (máx. 10MB)</p>
                    </div>
                </div>
            </div>

            <!-- Panel de Resultados (oculto por defecto) -->
            <div id="import-est-resultados" class="hidden p-5 rounded-2xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 space-y-4 text-base">
                <div class="flex items-center justify-between font-extrabold text-base">
                    <span class="text-slate-900 dark:text-white">Resumen del Procesamiento:</span>
                    <span id="import-est-total-filas" class="text-blue-700 dark:text-blue-300">0 filas procesadas</span>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-center">
                    <div class="p-3 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300">
                        <div class="text-2xl font-black" id="import-est-res-creados">0</div>
                        <div class="text-sm font-bold uppercase mt-0.5">Nuevos Alumnos</div>
                    </div>
                    <div class="p-3 rounded-xl bg-blue-50 dark:bg-blue-950/40 border border-blue-200 dark:border-blue-800 text-blue-800 dark:text-blue-300">
                        <div class="text-2xl font-black" id="import-est-res-actualizados">0</div>
                        <div class="text-sm font-bold uppercase mt-0.5">Actualizados</div>
                    </div>
                    <div class="p-3 rounded-xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 text-rose-800 dark:text-rose-300">
                        <div class="text-2xl font-black" id="import-est-res-errores">0</div>
                        <div class="text-sm font-bold uppercase mt-0.5">Observaciones</div>
                    </div>
                </div>
                <div id="import-est-lista-errores" class="hidden max-h-40 overflow-y-auto space-y-1.5 p-3 rounded-xl bg-rose-50/70 dark:bg-rose-950/30 text-rose-800 dark:text-rose-300 text-sm font-mono border border-rose-200 dark:border-rose-900/40">
                    <!-- Dinámico -->
                </div>
            </div>

            <!-- Footer / Acciones -->
            <div class="flex flex-col sm:flex-row items-center justify-end gap-3 pt-4 border-t border-slate-200 dark:border-slate-800">
                <button type="button" onclick="cerrarModalImportarEstudiantes()" class="btn btn-muted text-base font-bold py-2.5 px-5 min-h-[46px] w-full sm:w-auto">
                    Cancelar
                </button>
                <button type="submit" id="btn-submit-importar-est" class="btn btn-primary text-base font-extrabold py-2.5 px-6 min-h-[46px] w-full sm:w-auto inline-flex items-center justify-center gap-2 shadow-sm">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                    <span>Procesar e Importar</span>
                </button>
            </div>
        </form>

    </div>
</div>

<script>
function abrirModalImportarEstudiantes(idGradoActual = null) {
    const modal = document.getElementById('modal-importar-estudiantes');
    const form = document.getElementById('form-importar-estudiantes');
    const resBox = document.getElementById('import-est-resultados');
    const label = document.getElementById('import-est-file-label');
    const selGrado = document.getElementById('import-est-grado-defecto');

    if (!modal) return;

    if (form) form.reset();
    if (resBox) resBox.classList.add('hidden');
    if (label) label.textContent = 'Arrastre su archivo CSV aquí o búsquelo en su equipo';

    // Poblar selector de grado por defecto si hay grados en memoria
    if (selGrado && window.todosLosGradosCatalogo) {
        selGrado.innerHTML = '<option value="">Detectar desde la columna "grado" del archivo</option>' +
            window.todosLosGradosCatalogo.map(g => `<option value="${g.id_grado}">${g.nombre} (${g.turno || 'Sin turno'})</option>`).join('');
        if (idGradoActual && idGradoActual !== 'TODOS') {
            selGrado.value = String(idGradoActual);
        }
    }

    modal.classList.remove('hidden');
}

function cerrarModalImportarEstudiantes() {
    const modal = document.getElementById('modal-importar-estudiantes');
    if (modal) modal.classList.add('hidden');
}

document.addEventListener('DOMContentLoaded', () => {
    const fileInput = document.getElementById('import-est-file');
    const fileLabel = document.getElementById('import-est-file-label');
    const form = document.getElementById('form-importar-estudiantes');

    if (fileInput && fileLabel) {
        fileInput.addEventListener('change', () => {
            if (fileInput.files && fileInput.files[0]) {
                fileLabel.textContent = `Archivo seleccionado: ${fileInput.files[0].name} (${(fileInput.files[0].size / 1024).toFixed(1)} KB)`;
            }
        });
    }

    if (form) {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();

            if (!fileInput || !fileInput.files[0]) {
                alert("Por favor seleccione un archivo CSV primero.");
                return;
            }

            const btn = document.getElementById('btn-submit-importar-est');
            const originalBtnHtml = btn ? btn.innerHTML : '';
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = `
                    <svg class="animate-spin -ml-1 mr-2 h-5 w-5 text-white inline-block" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                    <span>Procesando archivo...</span>
                `;
            }

            const formData = new FormData(form);

            try {
                const base = (window.BASE_URL ? window.BASE_URL.replace(/\/+$/, '') : '');
                const res = await fetch(`${base}/src/api/Estudiante/importar_estudiantes.php`, { method: 'POST', body: formData });
                const json = await res.json();

                const resBox = document.getElementById('import-est-resultados');
                const txtTotal = document.getElementById('import-est-total-filas');
                const txtCreados = document.getElementById('import-est-res-creados');
                const txtActualizados = document.getElementById('import-est-res-actualizados');
                const txtErrores = document.getElementById('import-est-res-errores');
                const boxErrLista = document.getElementById('import-est-lista-errores');

                if (json.status === 'success' && json.data) {
                    const d = json.data;
                    if (resBox) resBox.classList.remove('hidden');
                    if (txtTotal) txtTotal.textContent = `${d.total_filas} filas procesadas`;
                    if (txtCreados) txtCreados.textContent = d.creados;
                    if (txtActualizados) txtActualizados.textContent = d.actualizados;
                    if (txtErrores) txtErrores.textContent = (d.errores || []).length;

                    if (boxErrLista) {
                        if (d.errores && d.errores.length > 0) {
                            boxErrLista.classList.remove('hidden');
                            boxErrLista.innerHTML = d.errores.map(err => `<div>• ${err}</div>`).join('');
                        } else {
                            boxErrLista.classList.add('hidden');
                        }
                    }

                    if (typeof window.recargarListaEstudiantesActual === 'function') {
                        window.recargarListaEstudiantesActual();
                    }
                } else {
                    alert(json.message || "Error al procesar la importación.");
                }
            } catch (err) {
                console.error("Error importando estudiantes:", err);
                alert("Error de comunicación al importar estudiantes.");
            } finally {
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = originalBtnHtml;
                }
            }
        });
    }
});
</script>
