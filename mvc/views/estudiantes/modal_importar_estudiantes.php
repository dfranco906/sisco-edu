<!-- Modal Importación Masiva de Estudiantes -->
<div id="modal-importar-estudiantes" class="app-modal hidden" role="dialog" aria-modal="true" aria-labelledby="mie-titulo">
    <div class="app-modal-dialog max-w-xl bg-white dark:bg-slate-900 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-800 p-5 sm:p-6 space-y-5 overflow-y-auto max-h-[90vh]">
        
        <!-- Header -->
        <div class="flex items-start justify-between gap-4 pb-3 border-b border-slate-100 dark:border-slate-800">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-blue-950/50 text-blue-600 dark:text-blue-400 flex items-center justify-center text-xl shrink-0">
                    📥
                </div>
                <div>
                    <h3 id="mie-titulo" class="text-lg font-bold text-slate-900 dark:text-white leading-tight">
                        Importación Masiva de Estudiantes
                    </h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                        Cargá nóminas completas desde archivos CSV compatibles con Microsoft Excel.
                    </p>
                </div>
            </div>
            <button type="button" onclick="cerrarModalImportarEstudiantes()" class="app-modal-close text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-xl font-bold" aria-label="Cerrar modal">
                ×
            </button>
        </div>

        <!-- Descarga de Plantilla e Instrucciones -->
        <div class="p-3.5 rounded-xl bg-blue-50/70 dark:bg-blue-950/30 border border-blue-200/60 dark:border-blue-900/40 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs">
            <div class="space-y-1">
                <p class="font-bold text-blue-900 dark:text-blue-300">¿No tenés el formato correcto?</p>
                <p class="text-slate-600 dark:text-slate-400">Descargá la plantilla modelo lista para abrir y editar en Excel.</p>
            </div>
            <a href="<?= base_url('src/api/Estudiante/plantilla_estudiantes.php') ?>" class="btn btn-secondary text-xs py-1.5 px-3 inline-flex items-center gap-1.5 shrink-0 self-start sm:self-auto font-semibold">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                <span>Descargar Plantilla (.csv)</span>
            </a>
        </div>

        <!-- Formulario -->
        <form id="form-importar-estudiantes" class="space-y-4" enctype="multipart/form-data">
            
            <!-- Selector opcional de Grado por defecto -->
            <div>
                <label for="import-est-grado-defecto" class="block mb-1.5 font-semibold text-xs uppercase tracking-wider text-slate-700 dark:text-slate-300">
                    Asignar Grado / Curso por Defecto
                </label>
                <select id="import-est-grado-defecto" name="id_grado_defecto" class="app-input w-full text-xs">
                    <option value="">Detectar desde la columna 'grado' del archivo</option>
                </select>
                <p class="text-[11px] text-slate-500 mt-1">Si el archivo ya incluye el nombre del grado en cada fila, este selector servirá como respaldo para filas sin curso especificado.</p>
            </div>

            <!-- Zona de Subida de Archivo (Dropzone) -->
            <div>
                <label class="block mb-1.5 font-semibold text-xs uppercase tracking-wider text-slate-700 dark:text-slate-300">
                    Archivo de Estudiantes (.csv / Excel) *
                </label>
                <div class="relative border-2 border-dashed border-slate-300 dark:border-slate-700 rounded-2xl p-6 text-center hover:border-blue-500 transition-colors bg-slate-50/50 dark:bg-slate-950/40">
                    <input type="file" id="import-est-file" name="archivo_csv" accept=".csv,text/csv,text/plain" required
                           class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                    <div class="space-y-2 pointer-events-none">
                        <div class="w-10 h-10 mx-auto rounded-xl bg-blue-100 dark:bg-blue-900/40 text-blue-600 dark:text-blue-300 flex items-center justify-center text-lg">
                            📄
                        </div>
                        <p id="import-est-file-label" class="text-xs font-semibold text-slate-700 dark:text-slate-200">
                            Arrastrá tu archivo CSV acá o <span class="text-blue-600 dark:text-blue-400 underline">buscalo en tu computadora</span>
                        </p>
                        <p class="text-[11px] text-slate-400">Archivos CSV delimitados por coma o punto y coma (máx. 10MB)</p>
                    </div>
                </div>
            </div>

            <!-- Panel de Resultados (oculto por defecto) -->
            <div id="import-est-resultados" class="hidden p-4 rounded-xl bg-slate-50 dark:bg-slate-800/40 border border-slate-200 dark:border-slate-700 space-y-3 text-xs">
                <div class="flex items-center justify-between font-bold">
                    <span class="text-slate-800 dark:text-slate-200">Resumen del Procesamiento:</span>
                    <span id="import-est-total-filas" class="text-slate-500 text-[11px]">0 filas</span>
                </div>
                <div class="grid grid-cols-3 gap-2 text-center">
                    <div class="p-2 rounded-lg bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 text-emerald-700 dark:text-emerald-300">
                        <div class="text-lg font-black" id="import-est-res-creados">0</div>
                        <div class="text-[10px] font-semibold uppercase">Nuevos</div>
                    </div>
                    <div class="p-2 rounded-lg bg-blue-50 dark:bg-blue-950/40 border border-blue-200 dark:border-blue-800 text-blue-700 dark:text-blue-300">
                        <div class="text-lg font-black" id="import-est-res-actualizados">0</div>
                        <div class="text-[10px] font-semibold uppercase">Actualizados</div>
                    </div>
                    <div class="p-2 rounded-lg bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 text-rose-700 dark:text-rose-300">
                        <div class="text-lg font-black" id="import-est-res-errores">0</div>
                        <div class="text-[10px] font-semibold uppercase">Observaciones</div>
                    </div>
                </div>
                <div id="import-est-lista-errores" class="hidden max-h-32 overflow-y-auto space-y-1 p-2 rounded-lg bg-rose-50/50 dark:bg-rose-950/20 text-rose-700 dark:text-rose-300 text-[11px] font-mono">
                    <!-- Dinámico -->
                </div>
            </div>

            <!-- Footer / Acciones -->
            <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100 dark:border-slate-800">
                <button type="button" onclick="cerrarModalImportarEstudiantes()" class="btn btn-muted text-xs py-2 px-4">
                    Cancelar
                </button>
                <button type="submit" id="btn-submit-importar-est" class="btn btn-primary text-xs py-2 px-5 font-bold">
                    Procesar e Importar Estudiantes
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
    if (label) label.textContent = 'Arrastrá tu archivo CSV acá o buscalo en tu computadora';

    // Poblar selector de grado por defecto si hay grados en memoria
    if (selGrado && window.todosLosGradosCatalogo) {
        selGrado.innerHTML = '<option value="">Detectar desde la columna "grado" del archivo</option>' +
            window.todosLosGradosCatalogo.map(g => `<option value="${g.id_grado}">${g.nombre} (${g.turno || 'Sin turno'})</option>`).join('');
        if (idGradoActual) {
            selGrado.value = String(idGradoActual);
        }
    }

    modal.classList.remove('hidden');
    document.body.classList.add('modal-open');
}

function cerrarModalImportarEstudiantes() {
    const modal = document.getElementById('modal-importar-estudiantes');
    if (modal) {
        modal.classList.add('hidden');
        document.body.classList.remove('modal-open');
    }
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
                if (window.Toast) Toast.warning("Seleccione un archivo CSV primero");
                return;
            }

            const btn = document.getElementById('btn-submit-importar-est');
            if (btn) {
                btn.disabled = true;
                btn.textContent = 'Procesando archivo...';
            }

            const formData = new FormData(form);

            try {
                const url = (window.BASE_URL ? window.BASE_URL.replace(/\/+$/, '') : '') + '/src/api/Estudiante/importar_estudiantes.php';
                const res = await fetch(url, { method: 'POST', body: formData });
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

                    if (window.Toast) Toast.success(json.message);

                    // Si hay una función para recargar la tabla actual sin refrescar toda la página
                    if (typeof window.recargarListaEstudiantesActual === 'function') {
                        window.recargarListaEstudiantesActual();
                    }
                } else {
                    if (window.Toast) Toast.error(json.message || "Error al procesar la importación");
                    else alert(json.message);
                }
            } catch (err) {
                console.error("Error importando estudiantes:", err);
                if (window.Toast) Toast.error("Error de comunicación al importar estudiantes");
            } finally {
                if (btn) {
                    btn.disabled = false;
                    btn.textContent = 'Procesar e Importar Estudiantes';
                }
            }
        });
    }
});
</script>
