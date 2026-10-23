<!-- Submodal Accesible: Registrar Nueva Asignación Docente -->
<div id="modal-crear-asignacion" class="app-modal fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm hidden" role="dialog" aria-modal="true" aria-labelledby="modal-crear-asig-titulo">
    <div class="relative w-full max-w-2xl max-h-[90vh] bg-white rounded-3xl shadow-2xl border-2 border-slate-200 overflow-hidden flex flex-col animate-in fade-in zoom-in-95 duration-200">
        
        <!-- Cabecera del Submodal -->
        <div class="flex items-center justify-between p-6 sm:p-7 border-b border-slate-200 bg-slate-50/80">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 rounded-2xl bg-blue-100 text-blue-700 flex items-center justify-center text-2xl font-bold shadow-xs shrink-0">
                    ✍️
                </div>
                <div>
                    <h2 id="modal-crear-asig-titulo" class="text-2xl font-extrabold text-slate-900 tracking-tight">
                        Registrar Asignación Docente
                    </h2>
                    <p class="text-base text-slate-600 mt-0.5">
                        Vinculá un docente a una asignatura, curso y carga horaria semanal
                    </p>
                </div>
            </div>
            <button type="button" onclick="cerrarModalCrearAsignacion()" class="w-12 h-12 rounded-xl text-slate-500 hover:text-slate-800 hover:bg-slate-200/60 flex items-center justify-center text-2xl font-bold transition focus:outline-none focus:ring-2 focus:ring-blue-500" aria-label="Cerrar modal">
                ✕
            </button>
        </div>

        <!-- Formulario -->
        <form id="form-crear-asignacion" onsubmit="event.preventDefault(); guardarNuevaAsignacion();" class="flex flex-col flex-1 overflow-hidden">
            <div class="p-6 sm:p-8 space-y-6 overflow-y-auto flex-1">
                
                <!-- Grado / Curso -->
                <div>
                    <label for="crear-asig-grado" class="block text-base font-bold text-slate-800 mb-1.5">
                        Curso / Grado Destino <span class="text-rose-600">*</span>
                    </label>
                    <select id="crear-asig-grado" name="id_grado" required
                            class="w-full min-h-[48px] px-4 py-2.5 text-base text-slate-900 font-semibold bg-white border border-slate-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 shadow-xs">
                        <option value="">Seleccione el curso o grado...</option>
                    </select>
                </div>

                <!-- Materia / Asignatura -->
                <div>
                    <label for="crear-asig-materia" class="block text-base font-bold text-slate-800 mb-1.5">
                        Materia / Asignatura <span class="text-rose-600">*</span>
                    </label>
                    <select id="crear-asig-materia" name="id_materia" required
                            class="w-full min-h-[48px] px-4 py-2.5 text-base text-slate-900 font-semibold bg-white border border-slate-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 shadow-xs">
                        <option value="">Seleccione la materia curricular...</option>
                    </select>
                </div>

                <!-- Profesor Responsable -->
                <div>
                    <label for="crear-asig-profesor" class="block text-base font-bold text-slate-800 mb-1.5">
                        Docente Responsable <span class="text-rose-600">*</span>
                    </label>
                    <select id="crear-asig-profesor" name="id_profesor" required
                            class="w-full min-h-[48px] px-4 py-2.5 text-base text-slate-900 font-semibold bg-white border border-slate-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 shadow-xs">
                        <option value="">Seleccione el docente...</option>
                    </select>
                </div>

                <!-- Fila: Carga Horaria y Año Lectivo -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label for="crear-asig-carga" class="block text-base font-bold text-slate-800 mb-1.5">
                            Carga Semanal (Horas Cátedra) <span class="text-rose-600">*</span>
                        </label>
                        <input type="number" id="crear-asig-carga" name="carga_horaria" min="1" max="40" value="4" required
                               placeholder="Ej: 4"
                               class="w-full min-h-[48px] px-4 py-2.5 text-base text-slate-900 font-semibold bg-white border border-slate-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 shadow-xs">
                        <p class="text-sm text-slate-600 mt-1">Horas cátedra semanales en este curso.</p>
                    </div>

                    <div>
                        <label for="crear-asig-anio" class="block text-base font-bold text-slate-800 mb-1.5">
                            Año Lectivo <span class="text-rose-600">*</span>
                        </label>
                        <input type="number" id="crear-asig-anio" name="año_lectivo" min="2020" max="2035" value="<?= date('Y') ?>" required
                               class="w-full min-h-[48px] px-4 py-2.5 text-base text-slate-900 font-semibold bg-white border border-slate-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 shadow-xs">
                        <p class="text-sm text-slate-600 mt-1">Período lectivo de la cátedra.</p>
                    </div>
                </div>

                <!-- Nota Informativa Automática de Matrículas -->
                <div class="p-5 rounded-2xl bg-blue-50/80 border-2 border-blue-200 flex items-start gap-3.5">
                    <span class="text-2xl mt-0.5">ℹ️</span>
                    <div class="text-sm sm:text-base text-slate-700 leading-relaxed">
                        <strong class="text-blue-900 block font-bold mb-0.5">Matriculación Automática:</strong>
                        Al registrar esta asignación, todos los estudiantes activos en el curso se vincularán automáticamente a la lista de clase de esta materia para el año lectivo indicado.
                    </div>
                </div>

            </div>

            <!-- Pie del Submodal -->
            <div class="p-5 sm:p-6 border-t border-slate-200 bg-slate-50 flex items-center justify-end gap-3">
                <button type="button" onclick="cerrarModalCrearAsignacion()" class="btn btn-muted min-h-[48px] px-6 text-base font-bold rounded-xl">
                    Cancelar
                </button>
                <button type="submit" id="btn-submit-crear-asignacion" class="btn btn-primary min-h-[48px] px-8 text-base font-bold rounded-xl shadow-xs">
                    Guardar Asignación
                </button>
            </div>
        </form>

    </div>
</div>

<script>
let catalogosAsignacionCargados = false;

async function cargarCatalogosAsignacion() {
    if (catalogosAsignacionCargados) return;

    const base = (window.BASE_URL ? window.BASE_URL.replace(/\/+$/, '') : '');

    try {
        const [resGrados, resMaterias, resProfes] = await Promise.all([
            fetch(base + '/src/api/Grado/leer_grados.php').then(r => r.json()),
            fetch(base + '/src/api/Materia/leer_materias.php').then(r => r.json()),
            fetch(base + '/src/api/Profesor/leer_profesores.php').then(r => r.json())
        ]);

        const selectGrado = document.getElementById('crear-asig-grado');
        const selectMateria = document.getElementById('crear-asig-materia');
        const selectProfesor = document.getElementById('crear-asig-profesor');

        const editGrado = document.getElementById('editar-asig-grado');
        const editMateria = document.getElementById('editar-asig-materia');
        const editProfesor = document.getElementById('editar-asig-profesor');

        const filterGrado = document.getElementById('filtro-curso-asignacion');

        // Cargar Grados
        if (resGrados && Array.isArray(resGrados.data)) {
            const optsG = resGrados.data.map(g => {
                const desc = g.descripcion || `${g.nombre} ${g.seccion || ''} (${g.turno || ''})`.trim();
                return `<option value="${g.id_grado}">${desc}</option>`;
            }).join('');

            if (selectGrado) selectGrado.innerHTML = '<option value="">Seleccione el curso o grado...</option>' + optsG;
            if (editGrado) editGrado.innerHTML = '<option value="">Seleccione el curso o grado...</option>' + optsG;
            if (filterGrado) {
                filterGrado.innerHTML = '<option value="">Todos los cursos</option>' + resGrados.data.map(g => {
                    const desc = g.nombre + (g.seccion ? ' ' + g.seccion : '');
                    return `<option value="${desc.toLowerCase()}">${desc}</option>`;
                }).join('');
            }
        }

        // Cargar Materias
        if (resMaterias && Array.isArray(resMaterias.data)) {
            const optsM = resMaterias.data.map(m => `<option value="${m.id_materia}">${m.nombre}</option>`).join('');
            if (selectMateria) selectMateria.innerHTML = '<option value="">Seleccione la materia curricular...</option>' + optsM;
            if (editMateria) editMateria.innerHTML = '<option value="">Seleccione la materia curricular...</option>' + optsM;
        }

        // Cargar Profesores
        if (resProfes && Array.isArray(resProfes.data)) {
            const optsP = resProfes.data.map(p => {
                const nom = p.nombre_completo || `${p.nombre} ${p.apellido}`;
                const ci = p.cedula_identidad ? ` (C.I. ${p.cedula_identidad})` : '';
                return `<option value="${p.id_profesor}">${nom}${ci}</option>`;
            }).join('');
            if (selectProfesor) selectProfesor.innerHTML = '<option value="">Seleccione el docente...</option>' + optsP;
            if (editProfesor) editProfesor.innerHTML = '<option value="">Seleccione el docente...</option>' + optsP;
        }

        catalogosAsignacionCargados = true;
    } catch (e) {
        console.error("Error cargando catálogos de asignación:", e);
    }
}

async function abrirModalCrearAsignacion() {
    await cargarCatalogosAsignacion();

    const modal = document.getElementById('modal-crear-asignacion');
    const form = document.getElementById('form-crear-asignacion');
    if (form) {
        form.reset();
        const inputAnio = document.getElementById('crear-asig-anio');
        if (inputAnio) inputAnio.value = new Date().getFullYear();
        const inputCarga = document.getElementById('crear-asig-carga');
        if (inputCarga) inputCarga.value = 4;
    }

    if (modal) {
        modal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
        setTimeout(() => document.getElementById('crear-asig-grado')?.focus(), 100);
    }
}

function cerrarModalCrearAsignacion() {
    const modal = document.getElementById('modal-crear-asignacion');
    if (modal) {
        modal.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    }
}

async function guardarNuevaAsignacion() {
    const form = document.getElementById('form-crear-asignacion');
    const btnSubmit = document.getElementById('btn-submit-crear-asignacion');
    if (!form) return;

    const idGrado = document.getElementById('crear-asig-grado')?.value;
    const idMateria = document.getElementById('crear-asig-materia')?.value;
    const idProfesor = document.getElementById('crear-asig-profesor')?.value;
    const carga = document.getElementById('crear-asig-carga')?.value;
    const anio = document.getElementById('crear-asig-anio')?.value;

    if (!idGrado || !idMateria || !idProfesor || !carga || !anio) {
        Swal.fire({
            icon: 'warning',
            title: 'Campos requeridos',
            text: 'Por favor complete el curso, la materia, el docente y la carga horaria.',
            confirmButtonColor: '#2563eb'
        });
        return;
    }

    if (btnSubmit) {
        btnSubmit.disabled = true;
        btnSubmit.innerHTML = `
            <svg class="animate-spin -ml-1 mr-2 h-5 w-5 text-white inline-block" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Guardando...
        `;
    }

    try {
        const formData = new FormData();
        formData.append('id_grado', idGrado);
        formData.append('id_materia', idMateria);
        formData.append('id_profesor', idProfesor);
        formData.append('carga_horaria', carga);
        formData.append('año_lectivo', anio);

        const url = (window.BASE_URL ? window.BASE_URL.replace(/\/+$/, '') : '') + '/src/api/Asignaciones/crear_asignacion.php';
        const res = await fetch(url, {
            method: 'POST',
            body: formData
        });

        const json = await res.json();

        if (json.status === 'success') {
            cerrarModalCrearAsignacion();
            Swal.fire({
                icon: 'success',
                title: '¡Asignación Creada!',
                text: json.message || 'La asignación docente fue guardada con éxito.',
                timer: 2200,
                showConfirmButton: false
            });
            cargarAsignaciones();
        } else {
            Swal.fire({
                icon: 'error',
                title: 'No se pudo guardar',
                text: json.message || 'Ocurrió un error al registrar la asignación docente.',
                confirmButtonColor: '#2563eb'
            });
        }
    } catch (e) {
        console.error("Error al registrar asignación:", e);
        Swal.fire({
            icon: 'error',
            title: 'Error de red',
            text: 'No se pudo conectar con el servidor para registrar la asignación.',
            confirmButtonColor: '#2563eb'
        });
    } finally {
        if (btnSubmit) {
            btnSubmit.disabled = false;
            btnSubmit.innerHTML = 'Guardar Asignación';
        }
    }
}
</script>
