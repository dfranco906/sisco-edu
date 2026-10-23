<!-- Submodal Accesible: Editar Materia -->
<div id="modal-editar-materia" class="app-modal fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm hidden" role="dialog" aria-modal="true" aria-labelledby="modal-editar-mat-titulo">
    <div class="relative w-full max-w-2xl max-h-[90vh] bg-white rounded-3xl shadow-2xl border-2 border-slate-200 overflow-hidden flex flex-col animate-in fade-in zoom-in-95 duration-200">
        
        <!-- Cabecera del Submodal -->
        <div class="flex items-center justify-between p-6 sm:p-7 border-b border-slate-200 bg-slate-50/80">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 rounded-2xl bg-amber-100 text-amber-700 flex items-center justify-center text-2xl font-bold shadow-xs shrink-0">
                    ✏️
                </div>
                <div>
                    <h2 id="modal-editar-mat-titulo" class="text-2xl font-extrabold text-slate-900 tracking-tight">
                        Modificar Materia
                    </h2>
                    <p class="text-base text-slate-600 mt-0.5">
                        Actualizá el nombre o descripción curricular de la asignatura
                    </p>
                </div>
            </div>
            <button type="button" onclick="cerrarModalEditarMateria()" class="w-12 h-12 rounded-xl text-slate-500 hover:text-slate-800 hover:bg-slate-200/60 flex items-center justify-center text-2xl font-bold transition focus:outline-none focus:ring-2 focus:ring-blue-500" aria-label="Cerrar modal">
                ✕
            </button>
        </div>

        <!-- Formulario -->
        <form id="form-editar-materia" onsubmit="event.preventDefault(); actualizarMateriaExistente();" class="flex flex-col flex-1 overflow-hidden">
            <input type="hidden" id="editar-mat-id" name="id_materia" value="">

            <div class="p-6 sm:p-8 space-y-6 overflow-y-auto flex-1">
                
                <!-- Nombre de la Materia -->
                <div>
                    <label for="editar-mat-nombre" class="block text-base font-bold text-slate-800 mb-1.5">
                        Nombre de la Materia <span class="text-rose-600">*</span>
                    </label>
                    <input type="text" id="editar-mat-nombre" name="nombre" required
                           class="w-full min-h-[48px] px-4 py-3 text-base text-slate-900 font-semibold bg-white border border-slate-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 shadow-xs">
                </div>

                <!-- Descripción Pedagógica -->
                <div>
                    <label for="editar-mat-desc" class="block text-base font-bold text-slate-800 mb-1.5">
                        Descripción o Área Formativa (Opcional)
                    </label>
                    <textarea id="editar-mat-desc" name="descripcion" rows="3"
                              class="w-full px-4 py-3 text-base text-slate-900 font-medium bg-white border border-slate-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 shadow-xs"></textarea>
                </div>

                <!-- Nota Pedagógica Explicativa -->
                <div class="p-5 rounded-2xl bg-amber-50/80 border-2 border-amber-200 flex items-start gap-3.5">
                    <span class="text-2xl mt-0.5">ℹ️</span>
                    <div class="text-sm sm:text-base text-slate-700 leading-relaxed">
                        <strong class="text-amber-900 block font-bold mb-0.5">Carga Horaria Semanal:</strong>
                        Los cambios en el nombre o descripción se propagarán a los planes de estudio y clases en vivo. La carga horaria de cada curso se administra en <strong>Asignaciones Docentes</strong>.
                    </div>
                </div>

            </div>

            <!-- Pie del Submodal -->
            <div class="p-5 sm:p-6 border-t border-slate-200 bg-slate-50 flex items-center justify-end gap-3">
                <button type="button" onclick="cerrarModalEditarMateria()" class="btn btn-muted min-h-[48px] px-6 text-base font-bold rounded-xl">
                    Cancelar
                </button>
                <button type="submit" id="btn-submit-editar-materia" class="btn btn-primary min-h-[48px] px-8 text-base font-bold rounded-xl shadow-xs">
                    Actualizar Cambios
                </button>
            </div>
        </form>

    </div>
</div>

<script>
function abrirModalEditarMateria(materia) {
    const modal = document.getElementById('modal-editar-materia');
    if (!modal || !materia) return;

    document.getElementById('editar-mat-id').value = materia.id_materia || '';
    document.getElementById('editar-mat-nombre').value = materia.nombre || '';
    document.getElementById('editar-mat-desc').value = materia.descripcion || '';

    modal.classList.remove('hidden');
    document.body.classList.add('overflow-hidden');
    setTimeout(() => document.getElementById('editar-mat-nombre')?.focus(), 100);
}

function cerrarModalEditarMateria() {
    const modal = document.getElementById('modal-editar-materia');
    if (modal) {
        modal.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    }
}

async function actualizarMateriaExistente() {
    const form = document.getElementById('form-editar-materia');
    const btnSubmit = document.getElementById('btn-submit-editar-materia');
    if (!form) return;

    const nombre = document.getElementById('editar-mat-nombre')?.value.trim();
    if (!nombre) {
        if (window.Toast) Toast.warning("El nombre de la materia es obligatorio");
        else alert("El nombre de la materia es obligatorio");
        return;
    }

    const formData = new FormData(form);
    if (btnSubmit) {
        btnSubmit.disabled = true;
        btnSubmit.textContent = "Guardando...";
    }

    try {
        const url = (window.BASE_URL ? window.BASE_URL.replace(/\/+$/, '') : '') + '/src/api/Materia/actualizar_materia.php';
        const res = await fetch(url, {
            method: 'POST',
            body: formData
        });
        const json = await res.json();

        if (json.status === 'success') {
            if (window.Toast) Toast.success(json.message);
            cerrarModalEditarMateria();
            if (typeof window.cargarMaterias === 'function') {
                window.cargarMaterias();
            } else {
                setTimeout(() => location.reload(), 600);
            }
        } else {
            if (window.Toast) Toast.error(json.message || "Error al actualizar materia");
            else alert(json.message);
        }
    } catch (e) {
        console.error("Error actualizando materia:", e);
        if (window.Toast) Toast.error("Error de comunicación con el servidor");
    } finally {
        if (btnSubmit) {
            btnSubmit.disabled = false;
            btnSubmit.textContent = "Actualizar Cambios";
        }
    }
}
</script>
