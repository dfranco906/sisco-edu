<!-- Submodal Accesible: Editar Asignación Docente -->
<div id="modal-editar-asignacion" class="app-modal fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm hidden" role="dialog" aria-modal="true" aria-labelledby="modal-editar-asig-titulo">
    <div class="relative w-full max-w-2xl max-h-[90vh] bg-white rounded-3xl shadow-2xl border-2 border-slate-200 overflow-hidden flex flex-col animate-in fade-in zoom-in-95 duration-200">
        
        <!-- Cabecera del Submodal -->
        <div class="flex items-center justify-between p-6 sm:p-7 border-b border-slate-200 bg-slate-50/80">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 rounded-2xl bg-amber-100 text-amber-700 flex items-center justify-center text-2xl font-bold shadow-xs shrink-0">
                    ✏️
                </div>
                <div>
                    <h2 id="modal-editar-asig-titulo" class="text-2xl font-extrabold text-slate-900 tracking-tight">
                        Modificar Asignación Docente
                    </h2>
                    <p class="text-base text-slate-600 mt-0.5">
                        Actualizá el curso, materia, docente asignado o carga horaria
                    </p>
                </div>
            </div>
            <button type="button" onclick="cerrarModalEditarAsignacion()" class="w-12 h-12 rounded-xl text-slate-500 hover:text-slate-800 hover:bg-slate-200/60 flex items-center justify-center text-2xl font-bold transition focus:outline-none focus:ring-2 focus:ring-amber-500" aria-label="Cerrar modal">
                ✕
            </button>
        </div>

        <!-- Formulario -->
        <form id="form-editar-asignacion" onsubmit="event.preventDefault(); guardarEdicionAsignacion();" class="flex flex-col flex-1 overflow-hidden">
            <input type="hidden" id="editar-asig-id" name="id_asignacion">

            <div class="p-6 sm:p-8 space-y-6 overflow-y-auto flex-1">
                
                <!-- Grado / Curso -->
                <div>
                    <label for="editar-asig-grado" class="block text-base font-bold text-slate-800 mb-1.5">
                        Curso / Grado Destino <span class="text-rose-600">*</span>
                    </label>
                    <select id="editar-asig-grado" name="id_grado" required
                            class="w-full min-h-[48px] px-4 py-2.5 text-base text-slate-900 font-semibold bg-white border border-slate-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 shadow-xs">
                        <option value="">Seleccione el curso o grado...</option>
                    </select>
                </div>

                <!-- Materia / Asignatura -->
                <div>
                    <label for="editar-asig-materia" class="block text-base font-bold text-slate-800 mb-1.5">
                        Materia / Asignatura <span class="text-rose-600">*</span>
                    </label>
                    <select id="editar-asig-materia" name="id_materia" required
                            class="w-full min-h-[48px] px-4 py-2.5 text-base text-slate-900 font-semibold bg-white border border-slate-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 shadow-xs">
                        <option value="">Seleccione la materia curricular...</option>
                    </select>
                    <p class="text-sm text-slate-500 mt-1">Podés seleccionar cualquiera de las materias disponibles en la malla.</p>
                </div>

                <!-- Profesor Responsable -->
                <div>
                    <label for="editar-asig-profesor" class="block text-base font-bold text-slate-800 mb-1.5">
                        Docente Responsable <span class="text-rose-600">*</span>
                    </label>
                    <select id="editar-asig-profesor" name="id_profesor" required
                            class="w-full min-h-[48px] px-4 py-2.5 text-base text-slate-900 font-semibold bg-white border border-slate-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 shadow-xs">
                        <option value="">Seleccione el docente...</option>
                    </select>
                </div>

                <!-- Fila: Carga Horaria y Año Lectivo -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label for="editar-asig-carga" class="block text-base font-bold text-slate-800 mb-1.5">
                            Carga Semanal (Horas Cátedra) <span class="text-rose-600">*</span>
                        </label>
                        <input type="number" id="editar-asig-carga" name="carga_horaria" min="1" max="40" required
                               class="w-full min-h-[48px] px-4 py-2.5 text-base text-slate-900 font-semibold bg-white border border-slate-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 shadow-xs">
                        <p class="text-sm text-slate-600 mt-1">Horas cátedra semanales dictadas en este curso.</p>
                    </div>

                    <div>
                        <label for="editar-asig-anio" class="block text-base font-bold text-slate-800 mb-1.5">
                            Año Lectivo <span class="text-rose-600">*</span>
                        </label>
                        <input type="number" id="editar-asig-anio" name="año_lectivo" min="2020" max="2035" required
                               class="w-full min-h-[48px] px-4 py-2.5 text-base text-slate-900 font-semibold bg-white border border-slate-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 shadow-xs">
                    </div>
                </div>

            </div>

            <!-- Pie del Submodal -->
            <div class="p-5 sm:p-6 border-t border-slate-200 bg-slate-50 flex items-center justify-end gap-3">
                <button type="button" onclick="cerrarModalEditarAsignacion()" class="btn btn-muted min-h-[48px] px-6 text-base font-bold rounded-xl">
                    Cancelar
                </button>
                <button type="submit" id="btn-submit-editar-asignacion" class="btn btn-primary min-h-[48px] px-8 text-base font-bold rounded-xl bg-amber-600 hover:bg-amber-700 shadow-xs">
                    Actualizar Asignación
                </button>
            </div>
        </form>

    </div>
</div>

<script>
async function abrirModalEditarAsignacion(item) {
    if (!item) return;

    const modal = document.getElementById('modal-editar-asignacion');
    const inputId = document.getElementById('editar-asig-id');
    const inputCarga = document.getElementById('editar-asig-carga');
    const inputAnio = document.getElementById('editar-asig-anio');

    if (inputId) inputId.value = item.id_asignacion || '';
    if (inputCarga) inputCarga.value = item.carga_horaria || 4;
    if (inputAnio) inputAnio.value = item.anho_lectivo || item['año_lectivo'] || new Date().getFullYear();

    if (modal) {
        modal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
    }

    // Cargar catálogos sin condiciones de carrera
    const catalogos = await obtenerCatalogosAsignacion();

    const selectGrado = document.getElementById('editar-asig-grado');
    const selectMateria = document.getElementById('editar-asig-materia');
    const selectProfesor = document.getElementById('editar-asig-profesor');

    // 1. Poblar Grados
    if (selectGrado) {
        let optsG = '<option value="">Seleccione el curso o grado...</option>';
        catalogos.grados.forEach(g => {
            const desc = g.descripcion || `${g.nombre} ${g.seccion || ''} (${g.turno || ''})`.trim();
            const isSel = (Number(item.id_grado) === Number(g.id_grado));
            optsG += `<option value="${g.id_grado}" ${isSel ? 'selected' : ''}>${desc}</option>`;
        });
        selectGrado.innerHTML = optsG;
        selectGrado.value = item.id_grado || '';
    }

    // 2. Poblar Materias (Asegurando que la materia actual esté seleccionada y que todas sean elegibles)
    if (selectMateria) {
        let optsM = '<option value="">Seleccione la materia curricular...</option>';
        let materiaEncontrada = false;
        const idMatObjetivo = Number(item.id_materia);

        catalogos.materias.forEach(m => {
            const idM = Number(m.id_materia);
            const isSel = (idMatObjetivo > 0 && idMatObjetivo === idM);
            if (isSel) materiaEncontrada = true;
            optsM += `<option value="${m.id_materia}" ${isSel ? 'selected' : ''}>${m.nombre}</option>`;
        });

        // Si la materia asignada no vino en la lista activa, preservarla como opción
        if (idMatObjetivo > 0 && !materiaEncontrada) {
            const nombreMat = item.materia_nombre || item.materia || `Materia #${idMatObjetivo}`;
            optsM += `<option value="${idMatObjetivo}" selected>${nombreMat} (Actual)</option>`;
        }

        selectMateria.innerHTML = optsM;
        selectMateria.value = item.id_materia || '';
    }

    // 3. Poblar Profesores
    if (selectProfesor) {
        let optsP = '<option value="">Seleccione el docente...</option>';
        catalogos.profesores.forEach(p => {
            const nom = p.nombre_completo || `${p.nombre} ${p.apellido}`;
            const ci = p.cedula_identidad ? ` (C.I. ${p.cedula_identidad})` : '';
            const isSel = (Number(item.id_profesor) === Number(p.id_profesor));
            optsP += `<option value="${p.id_profesor}" ${isSel ? 'selected' : ''}>${nom}${ci}</option>`;
        });
        selectProfesor.innerHTML = optsP;
        selectProfesor.value = item.id_profesor || '';
    }
}

function cerrarModalEditarAsignacion() {
    const modal = document.getElementById('modal-editar-asignacion');
    if (modal) {
        modal.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    }
}

async function guardarEdicionAsignacion() {
    const form = document.getElementById('form-editar-asignacion');
    const btnSubmit = document.getElementById('btn-submit-editar-asignacion');
    if (!form) return;

    const id = document.getElementById('editar-asig-id')?.value;
    const idGrado = document.getElementById('editar-asig-grado')?.value;
    const idMateria = document.getElementById('editar-asig-materia')?.value;
    const idProfesor = document.getElementById('editar-asig-profesor')?.value;
    const carga = document.getElementById('editar-asig-carga')?.value;
    const anio = document.getElementById('editar-asig-anio')?.value;

    if (!id || !idGrado || !idMateria || !idProfesor || !carga || !anio) {
        if (window.Toast) Toast.warning('Por favor complete todos los datos obligatorios de la cátedra.', 'Campos requeridos');
        else alert('Por favor complete todos los datos obligatorios de la cátedra.');
        return;
    }

    if (btnSubmit) {
        btnSubmit.disabled = true;
        btnSubmit.innerHTML = `
            <svg class="animate-spin -ml-1 mr-2 h-5 w-5 text-white inline-block" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Actualizando...
        `;
    }

    try {
        const formData = new FormData();
        formData.append('id_asignacion', id);
        formData.append('id_grado', idGrado);
        formData.append('id_materia', idMateria);
        formData.append('id_profesor', idProfesor);
        formData.append('carga_horaria', carga);
        formData.append('año_lectivo', anio);

        const url = (window.BASE_URL ? window.BASE_URL.replace(/\/+$/, '') : '') + '/src/api/Asignaciones/actualizar_asignacion.php';
        const res = await fetch(url, {
            method: 'POST',
            body: formData
        });

        const json = await res.json();

        if (json.status === 'success') {
            cerrarModalEditarAsignacion();
            if (window.Toast) Toast.success(json.message || 'Los cambios se guardaron correctamente.', '¡Asignación Actualizada!');
            else alert(json.message || 'Los cambios se guardaron correctamente.');

            if (typeof cargarAsignaciones === 'function') {
                cargarAsignaciones();
            }
        } else {
            if (window.Toast) Toast.error(json.message || 'No se pudieron guardar los cambios en la cátedra.', 'Error al actualizar');
            else alert(json.message || 'No se pudieron guardar los cambios en la cátedra.');
        }
    } catch (e) {
        console.error("Error al actualizar asignación:", e);
        if (window.Toast) Toast.error('Ocurrió un problema de red al intentar actualizar la asignación docente.', 'Error de conexión');
        else alert('Ocurrió un problema de red al intentar actualizar la asignación docente.');
    } finally {
        if (btnSubmit) {
            btnSubmit.disabled = false;
            btnSubmit.innerHTML = 'Actualizar Asignación';
        }
    }
}
</script>
