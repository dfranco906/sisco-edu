<?php
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';
?>

<main class="app-main flex-1 p-4 sm:p-6 lg:p-10 overflow-y-auto">
    <!-- Barra Superior -->
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 mb-6">
        <div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 dark:bg-emerald-950/40 text-emerald-800 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/40 animate-pulse">
                    ● EN VIVO
                </span>
                <h2 class="text-2xl font-bold text-slate-900 dark:text-white">Panel de Clase y Asistencia Biométrica</h2>
            </div>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                Monitoreo en tiempo real de huellas dactilares, lección pedagógica del día y lista de presentes/ausentes.
            </p>
        </div>

        <!-- Controles de Aula y Fecha -->
        <div class="flex flex-wrap items-center gap-3">
            <div class="flex items-center gap-2 bg-white dark:bg-slate-900 px-3 py-1.5 rounded-lg border border-slate-200 dark:border-slate-800 shadow-sm text-xs">
                <span class="text-slate-400 font-semibold uppercase">Aula:</span>
                <select id="select-aula" class="bg-transparent font-bold text-slate-800 dark:text-slate-200 focus:outline-none">
                    <option value="">Cargando aulas...</option>
                </select>
            </div>

            <div class="flex items-center gap-2 bg-white dark:bg-slate-900 px-3 py-1.5 rounded-lg border border-slate-200 dark:border-slate-800 shadow-sm text-xs font-mono text-slate-700 dark:text-slate-300">
                <span>📅</span>
                <span id="label-servidor-tiempo">-</span>
            </div>

            <button id="btn-toggle-auto" type="button" class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-semibold shadow-sm transition-all flex items-center gap-1.5">
                <span id="dot-auto" class="w-2 h-2 rounded-full bg-white animate-ping"></span>
                <span id="text-auto">Auto-refresco: ON</span>
            </button>
        </div>
    </div>

    <!-- Si no hay clase activa -->
    <div id="no-clase-banner" class="bg-amber-50 border border-amber-200 rounded-xl p-6 mb-6 hidden">
        <div class="flex items-start gap-3">
            <span class="text-2xl">⚠️</span>
            <div>
                <h3 class="font-bold text-amber-900 text-sm">No se detectó una clase en curso para esta aula</h3>
                <p class="text-xs text-amber-800 mt-1 leading-relaxed">
                    Verifique que el día de hoy coincida con los horarios programados del aula o seleccione otra aula en el selector superior.
                </p>
            </div>
        </div>
    </div>

    <!-- Contenedor Principal de la Clase Activa -->
    <div id="clase-activa-container" class="space-y-6">

        <!-- 1. Encabezado de la Clase y Docente -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Datos de la Materia y Horario -->
            <div class="lg:col-span-2 bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-slate-200 dark:border-slate-800 p-6">
                <div class="flex flex-wrap items-center justify-between gap-3 pb-4 border-b border-slate-100 dark:border-slate-800">
                    <div>
                        <span class="text-[11px] uppercase tracking-wider text-indigo-600 dark:text-indigo-400 font-bold">Materia Activa</span>
                        <h3 id="clase-materia" class="text-xl font-bold text-slate-900 dark:text-white mt-0.5">-</h3>
                    </div>
                    <div class="text-right">
                        <span id="clase-grado-badge" class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-indigo-50 dark:bg-indigo-500/20 text-indigo-700 dark:text-indigo-300 border border-indigo-100 dark:border-indigo-500/30">
                            -
                        </span>
                        <p id="clase-horario" class="text-xs font-mono text-slate-500 dark:text-slate-400 mt-1 font-semibold">-</p>
                    </div>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 pt-4 text-xs">
                    <div>
                        <span class="text-slate-400 font-medium">Aula Física</span>
                        <p id="clase-aula" class="font-bold text-slate-800 dark:text-slate-200 mt-0.5">-</p>
                    </div>
                    <div>
                        <span class="text-slate-400 font-medium">Horas Cátedra</span>
                        <p id="clase-hc" class="font-bold text-slate-800 dark:text-slate-200 mt-0.5">-</p>
                    </div>
                    <div>
                        <span class="text-slate-400 font-medium">Duración Total</span>
                        <p id="clase-duracion" class="font-bold text-slate-800 dark:text-slate-200 mt-0.5">-</p>
                    </div>
                    <div>
                        <span class="text-slate-400 font-medium">Fecha de la Clase</span>
                        <p id="clase-fecha" class="font-bold text-slate-800 dark:text-slate-200 mt-0.5 font-mono">-</p>
                    </div>
                </div>
            </div>

            <!-- Tarjeta del Docente y su Asistencia -->
            <div class="bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-slate-200 dark:border-slate-800 p-6 flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-[11px] uppercase tracking-wider text-slate-400 font-bold">Profesor a Cargo</span>
                        <span id="badge-asist-profesor" class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                            Verificando...
                        </span>
                    </div>

                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-xl bg-indigo-100 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-300 flex items-center justify-center font-bold text-lg">
                            👨‍🏫
                        </div>
                        <div>
                            <h4 id="profesor-nombre" class="font-bold text-slate-900 dark:text-white text-base leading-tight">-</h4>
                            <p id="profesor-ci" class="text-xs text-slate-500 dark:text-slate-400 font-mono mt-0.5">CI: -</p>
                        </div>
                    </div>
                </div>

                <div class="mt-4 pt-4 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-xs text-slate-600 dark:text-slate-400">
                    <span id="profesor-hora-marcacion">Hora de Marcación: --:--:--</span>
                    <button id="btn-simular-prof" type="button" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300 font-semibold underline">
                        Simular Huella
                    </button>
                </div>
            </div>
        </div>

        <!-- 2. Contenido Pedagógico / Lección del Día -->
        <div class="bg-gradient-to-r from-indigo-900 to-slate-900 rounded-xl shadow-md p-6 text-white">
            <div class="flex flex-wrap items-center justify-between gap-3 mb-4 pb-4 border-b border-white/10">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-indigo-500/40 text-indigo-200 border border-indigo-400/30 uppercase tracking-wider">
                            Plan Anual MEC Paraguay
                        </span>
                        <span id="leccion-unidad" class="text-xs text-indigo-200 font-medium">-</span>
                    </div>
                    <h3 id="leccion-tema" class="text-lg font-bold text-white mt-1 leading-snug">
                        Cargando lección del día...
                    </h3>
                </div>
                <div class="text-right">
                    <span class="text-xs text-indigo-300 block">Horas Cátedra</span>
                    <span id="leccion-hc-badge" class="text-sm font-bold text-white font-mono bg-white/10 px-3 py-1 rounded-md inline-block mt-0.5">
                        - HC (40m c/u)
                    </span>
                </div>
            </div>

            <!-- Capacidad y Contenido -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-4">
                <div class="md:col-span-2">
                    <span class="text-[11px] font-bold text-indigo-300 uppercase tracking-wide">Capacidad a Desarrollar</span>
                    <p id="leccion-capacidad" class="text-xs text-slate-200 mt-1 leading-relaxed">-</p>
                </div>
                <div>
                    <span class="text-[11px] font-bold text-indigo-300 uppercase tracking-wide">Evaluación Pedagógica</span>
                    <div id="leccion-evaluacion" class="text-xs text-slate-200 mt-1 space-y-1">-</div>
                </div>
            </div>

            <!-- Indicadores de Logro con Checkbox en Tiempo Real -->
            <div class="mt-4 pt-4 border-t border-white/10">
                <span class="text-[11px] font-bold text-indigo-300 uppercase tracking-wider block mb-2">
                    Indicadores de Logro de la Clase (Marcar cumplimiento durante la clase)
                </span>
                <div id="leccion-indicadores-list" class="grid grid-cols-1 md:grid-cols-2 gap-2 text-xs">
                    <!-- Dinámico -->
                </div>
            </div>
        </div>

        <!-- 3. Métricas y Asistencia de Estudiantes -->
        <div>
            <!-- Tarjetas Métricas -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
                <div class="bg-white dark:bg-slate-900 p-4 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm">
                    <span class="text-xs font-semibold text-slate-400 uppercase">Matriculados</span>
                    <p id="metric-total" class="text-2xl font-bold text-slate-900 dark:text-white mt-1">0</p>
                </div>
                <div class="bg-white dark:bg-slate-900 p-4 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm">
                    <span class="text-xs font-semibold text-emerald-600 dark:text-emerald-400 uppercase">Presentes (Huella)</span>
                    <p id="metric-presentes" class="text-2xl font-bold text-emerald-600 dark:text-emerald-400 mt-1">0</p>
                </div>
                <div class="bg-white dark:bg-slate-900 p-4 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm">
                    <span class="text-xs font-semibold text-rose-500 dark:text-rose-400 uppercase">Ausentes</span>
                    <p id="metric-ausentes" class="text-2xl font-bold text-rose-500 dark:text-rose-400 mt-1">0</p>
                </div>
                <div class="bg-white dark:bg-slate-900 p-4 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm">
                    <span class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 uppercase">% Asistencia</span>
                    <p id="metric-porcentaje" class="text-2xl font-bold text-indigo-600 dark:text-indigo-400 mt-1">0%</p>
                </div>
            </div>

            <!-- Columnas: Presentes vs Ausentes -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                <!-- Columna Presentes -->
                <div class="bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-slate-200 dark:border-slate-800 p-5 flex flex-col">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800 mb-3">
                        <div class="flex items-center gap-2">
                            <span class="w-3 h-3 rounded-full bg-emerald-500"></span>
                            <h4 class="font-bold text-slate-900 dark:text-white text-sm">Estudiantes Presentes</h4>
                        </div>
                        <span id="badge-count-presentes" class="px-2 py-0.5 rounded-full text-xs font-bold bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-400 border border-emerald-100 dark:border-emerald-800/40">0</span>
                    </div>

                    <div id="lista-presentes" class="space-y-2 flex-1 overflow-y-auto max-h-96 pr-1">
                        <!-- Items dinámicos -->
                    </div>
                </div>

                <!-- Columna Ausentes -->
                <div class="bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-slate-200 dark:border-slate-800 p-5 flex flex-col">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800 mb-3">
                        <div class="flex items-center gap-2">
                            <span class="w-3 h-3 rounded-full bg-rose-500"></span>
                            <h4 class="font-bold text-slate-900 dark:text-white text-sm">Estudiantes Ausentes</h4>
                        </div>
                        <span id="badge-count-ausentes" class="px-2 py-0.5 rounded-full text-xs font-bold bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-400 border border-rose-100 dark:border-rose-800/40">0</span>
                    </div>

                    <div id="lista-ausentes" class="space-y-2 flex-1 overflow-y-auto max-h-96 pr-1">
                        <!-- Items dinámicos -->
                    </div>
                </div>

            </div>
        </div>

        <!-- 4. Consola de Pruebas: Simulación Rápida de Huella Dactilar -->
        <div class="bg-slate-100 dark:bg-slate-900/90 rounded-xl p-5 text-slate-800 dark:text-white shadow-md border border-slate-200 dark:border-slate-800">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 mb-4">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="text-sm font-bold text-amber-500 dark:text-amber-400">⚡ Consola de Pruebas Rápidas:</span>
                        <h4 class="text-sm font-semibold text-slate-900 dark:text-slate-200">Simulador de Marcación Biométrica</h4>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                        Permite disparar la marcación de huella de cualquier estudiante o docente sin tocar hardware físico para verificar la detección de clase y lección.
                    </p>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <select id="simular-select-estudiante" class="px-3 py-2 bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded-lg text-xs text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 flex-1 min-w-[220px]">
                    <option value="">Seleccione estudiante del curso...</option>
                </select>

                <button id="btn-disparar-huella" type="button" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg text-xs font-bold transition-all shadow-sm flex items-center gap-1.5">
                    <span>👆</span> Marcar Huella Estudiante
                </button>

                <button id="btn-marcar-todos" type="button" class="px-3 py-2 bg-slate-200 hover:bg-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 rounded-lg text-xs font-medium transition-all">
                    Marcar Todos Presentes
                </button>
            </div>
        </div>

    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const API_PANEL = `${window.BASE_URL}src/api/Clases/panel_en_vivo.php`;
    const API_INDICADOR = `${window.BASE_URL}src/api/Clases/actualizar_indicador.php`;
    const API_MARCAR = `${window.BASE_URL}src/api/Clases/marcar_asistencia.php`;
    const API_SIMULAR = `${window.BASE_URL}src/api/Clases/simular_marcacion.php`;

    const selectAula = document.getElementById('select-aula');
    const labelTiempo = document.getElementById('label-servidor-tiempo');
    const btnToggleAuto = document.getElementById('btn-toggle-auto');
    const dotAuto = document.getElementById('dot-auto');
    const textAuto = document.getElementById('text-auto');

    const noClaseBanner = document.getElementById('no-clase-banner');
    const claseContainer = document.getElementById('clase-activa-container');

    const simularSelectEst = document.getElementById('simular-select-estudiante');
    const btnDispararHuella = document.getElementById('btn-disparar-huella');
    const btnSimularProf = document.getElementById('btn-simular-prof');
    const btnMarcarTodos = document.getElementById('btn-marcar-todos');

    let currentClaseData = null;
    let autoRefrescoActivo = true;
    let autoRefrescoTimer = null;

    // 1. Cargar Aulas e inicializar
    function init() {
        fetch(API_PANEL)
            .then(r => r.json())
            .then(res => {
                if (res.status === 'success' && res.data) {
                    if (res.data.aulas && res.data.aulas.length > 0) {
                        selectAula.innerHTML = res.data.aulas.map(a => `
                            <option value="${a.id_aula}">${a.nombre} (${a.codigo || 'Aula ' + a.id_aula})</option>
                        `).join('');
                    }
                    if (res.data.clase && res.data.clase.id_aula) {
                        selectAula.value = res.data.clase.id_aula;
                    }
                    renderizarPanel(res.data);
                }
            })
            .catch(e => console.error(e));
    }

    // 2. Refrescar datos del panel
    function refrescarPanel(silencioso = false) {
        const idAula = selectAula.value;
        const url = `${API_PANEL}?id_aula=${idAula}`;

        fetch(url)
            .then(r => r.json())
            .then(res => {
                if (res.status === 'success') {
                    renderizarPanel(res.data);
                }
            })
            .catch(e => console.error("Error al refrescar panel:", e));
    }

    selectAula.addEventListener('change', () => refrescarPanel(false));

    // 3. Renderizar vista completa
    function renderizarPanel(data) {
        if (data.servidor) {
            labelTiempo.textContent = `${data.servidor.dia_semana} ${data.servidor.fecha_actual} — ${data.servidor.hora_actual}`;
        }

        const clase = data.clase;
        currentClaseData = clase;

        if (!clase) {
            noClaseBanner.classList.remove('hidden');
            claseContainer.classList.add('hidden');
            return;
        }

        noClaseBanner.classList.add('hidden');
        claseContainer.classList.remove('hidden');

        // Datos de Clase
        document.getElementById('clase-materia').textContent = clase.materia_nombre || 'Materia';
        document.getElementById('clase-grado-badge').textContent = clase.grado_nombre || 'Curso';
        document.getElementById('clase-horario').textContent = `${clase.horario_inicio || ''} - ${clase.horario_fin || ''}`;
        document.getElementById('clase-aula').textContent = clase.aula_nombre || 'Aula';

        const hc = parseFloat(clase.horas_catedra || 2);
        const durMin = hc * 40;
        document.getElementById('clase-hc').textContent = `${hc} Horas Cátedra`;
        document.getElementById('clase-duracion').textContent = `${durMin} min (40m/HC)`;
        document.getElementById('clase-fecha').textContent = clase.fecha;

        // Docente
        document.getElementById('profesor-nombre').textContent = `${clase.profesor_nombre} ${clase.profesor_apellido}`;
        document.getElementById('profesor-ci').textContent = `CI: ${clase.profesor_ci || '-'}`;

        const profAsist = clase.profesor_asistencia || {};
        const badgeProf = document.getElementById('badge-asist-profesor');
        if (profAsist.presente) {
            badgeProf.className = 'inline-flex items-center px-2.5 py-0.5 rounded text-[11px] font-bold bg-emerald-100 text-emerald-800';
            badgeProf.textContent = '🟢 PRESENTE (HUELLA)';
            document.getElementById('profesor-hora-marcacion').textContent = `Hora de Marcación: ${profAsist.hora || ''}`;
        } else {
            badgeProf.className = 'inline-flex items-center px-2.5 py-0.5 rounded text-[11px] font-bold bg-rose-100 text-rose-800';
            badgeProf.textContent = '🔴 AUSENTE';
            document.getElementById('profesor-hora-marcacion').textContent = 'Hora de Marcación: Pendiente';
        }

        // Lección Pedagógica
        document.getElementById('leccion-unidad').textContent = clase.unidad_nombre || 'Unidad Temática';
        document.getElementById('leccion-tema').textContent = clase.tema_titulo || 'Contenido calendarizado del día';
        document.getElementById('leccion-hc-badge').textContent = `${hc} HC (${durMin} min)`;
        document.getElementById('leccion-capacidad').textContent = clase.capacidad_descripcion || 'Capacidad pedagógica institucional.';

        // Evaluación
        const evalList = [
            ...(clase.procedimientos || []),
            ...(clase.instrumentos || [])
        ];
        document.getElementById('leccion-evaluacion').innerHTML = evalList.length > 0
            ? evalList.map(item => `<div>• ${item}</div>`).join('')
            : '<div>• Registro Secuencia Aprendizaje (RSA)</div><div>• Observación sistemática</div>';

        // Indicadores con checkboxes interactivos
        const indContainer = document.getElementById('leccion-indicadores-list');
        if (clase.indicadores && clase.indicadores.length > 0) {
            indContainer.innerHTML = clase.indicadores.map(ind => `
                <label class="flex items-start gap-2.5 p-2 rounded bg-white/5 hover:bg-white/10 transition-colors cursor-pointer border border-white/5">
                    <input type="checkbox" data-indicador="${ind.id_indicador}" ${parseInt(ind.cumplido) === 1 ? 'checked' : ''} class="mt-0.5 rounded border-white/30 text-indigo-500 focus:ring-0 cursor-pointer indicador-chk">
                    <span class="${parseInt(ind.cumplido) === 1 ? 'line-through text-indigo-300' : 'text-slate-100'}">${ind.descripcion}</span>
                </label>
            `).join('');

            // Event listener en indicadores
            indContainer.querySelectorAll('.indicador-chk').forEach(chk => {
                chk.addEventListener('change', (e) => {
                    const idInd = e.target.getAttribute('data-indicador');
                    const cumplido = e.target.checked ? 1 : 0;
                    fetch(API_INDICADOR, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            id_clase: clase.id_clase,
                            id_indicador: idInd,
                            cumplido: cumplido
                        })
                    }).then(() => refrescarPanel(true));
                });
            });
        } else {
            indContainer.innerHTML = '<span class="text-xs text-slate-400 italic">No hay indicadores registrados para este tema.</span>';
        }

        // Métricas
        const metricas = clase.metricas || {};
        document.getElementById('metric-total').textContent = metricas.total_matriculados || 0;
        document.getElementById('metric-presentes').textContent = metricas.presentes || 0;
        document.getElementById('metric-ausentes').textContent = metricas.ausentes || 0;
        document.getElementById('metric-porcentaje').textContent = `${metricas.porcentaje_asistencia || 0}%`;

        document.getElementById('badge-count-presentes').textContent = metricas.presentes || 0;
        document.getElementById('badge-count-ausentes').textContent = metricas.ausentes || 0;

        // Estudiantes Presentes
        const listaPresentes = document.getElementById('lista-presentes');
        if (clase.estudiantes_presentes && clase.estudiantes_presentes.length > 0) {
            listaPresentes.innerHTML = clase.estudiantes_presentes.map(e => `
                <div class="flex items-center justify-between p-2.5 rounded-lg border border-emerald-100 bg-emerald-50/50 hover:bg-emerald-50 transition-colors text-xs">
                    <div class="flex items-center gap-2.5">
                        <span class="text-base">👤</span>
                        <div>
                            <span class="font-bold text-slate-800 block">${e.nombre_completo}</span>
                            <span class="text-[11px] text-slate-500 font-mono">CI: ${e.cedula_identidad}</span>
                        </div>
                    </div>
                    <div class="text-right">
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800">
                            ${e.estado_asistencia === 'TARDANZA' ? '🟡 TARDANZA' : '🟢 PRESENTE'}
                        </span>
                        <span class="text-[10px] text-slate-500 font-mono block mt-0.5">Huella: ${e.hora_marcacion || '--:--'}</span>
                    </div>
                </div>
            `).join('');
        } else {
            listaPresentes.innerHTML = `
                <div class="p-6 text-center text-slate-400 text-xs italic">
                    Aún no se han registrado huellas dactilares de estudiantes para esta clase.
                </div>
            `;
        }

        // Estudiantes Ausentes
        const listaAusentes = document.getElementById('lista-ausentes');
        if (clase.estudiantes_ausentes && clase.estudiantes_ausentes.length > 0) {
            listaAusentes.innerHTML = clase.estudiantes_ausentes.map(e => `
                <div class="flex items-center justify-between p-2.5 rounded-lg border border-slate-200 bg-slate-50 hover:bg-slate-100/60 transition-colors text-xs">
                    <div class="flex items-center gap-2.5">
                        <span class="text-base text-slate-400">👤</span>
                        <div>
                            <span class="font-medium text-slate-700 block">${e.nombre_completo}</span>
                            <span class="text-[11px] text-slate-400 font-mono">CI: ${e.cedula_identidad}</span>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-700">
                            🔴 AUSENTE
                        </span>
                        <button type="button" data-id="${e.id_estudiante}" class="btn-marcar-manual px-2 py-1 bg-white border border-slate-300 hover:bg-indigo-50 hover:text-indigo-600 rounded text-[10px] font-bold text-slate-600 shadow-2xs">
                            Marcar
                        </button>
                    </div>
                </div>
            `).join('');

            // Listeners para botones de marcar manual
            listaAusentes.querySelectorAll('.btn-marcar-manual').forEach(btn => {
                btn.addEventListener('click', (ev) => {
                    const idEst = ev.target.getAttribute('data-id');
                    fetch(API_MARCAR, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            id_estudiante: parseInt(idEst),
                            fecha: clase.fecha,
                            estado: 'PRESENTE'
                        })
                    }).then(() => refrescarPanel(true));
                });
            });
        } else {
            listaAusentes.innerHTML = `
                <div class="p-6 text-center text-emerald-600 text-xs font-semibold">
                    🎉 ¡100% de asistencia! No hay estudiantes ausentes.
                </div>
            `;
        }

        // Llenar selector del simulador con los estudiantes ausentes o todos
        const todos = [
            ...(clase.estudiantes_ausentes || []),
            ...(clase.estudiantes_presentes || [])
        ];
        simularSelectEst.innerHTML = '<option value="">Seleccione estudiante del curso...</option>' +
            todos.map(e => `<option value="${e.id_estudiante}">${e.nombre_completo} (CI: ${e.cedula_identidad})</option>`).join('');
    }

    // 4. Simulador de Huella
    btnDispararHuella.addEventListener('click', () => {
        const idEst = simularSelectEst.value;
        if (!idEst) {
            window.Toast ? Toast.warning("Por favor seleccione un estudiante en el menú desplegable") : alert("Por favor seleccione un estudiante");
            return;
        }
        if (!currentClaseData) return;

        fetch(API_SIMULAR, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                tipo_persona: 'estudiante',
                id_persona: parseInt(idEst),
                id_aula: parseInt(currentClaseData.id_aula),
                timestamp: new Date().toISOString().replace('T', ' ').substring(0, 19)
            })
        })
        .then(r => r.json())
        .then(res => {
            if (res.status === 'success') {
                if (window.Toast) Toast.success("Marcación biométrica registrada exitosamente");
                refrescarPanel(true);
            } else {
                if (window.Toast) Toast.error(res.message, "Error en simulación");
                else alert(`Error: ${res.message}`);
            }
        })
        .catch(e => {
            if (window.Toast) Toast.error(e.message);
            else alert(e.message);
        });
    });

    btnSimularProf.addEventListener('click', () => {
        if (!currentClaseData) return;

        fetch(API_SIMULAR, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                tipo_persona: 'profesor',
                id_persona: parseInt(currentClaseData.id_profesor),
                id_aula: parseInt(currentClaseData.id_aula),
                timestamp: new Date().toISOString().replace('T', ' ').substring(0, 19)
            })
        })
        .then(r => r.json())
        .then(res => {
            if (res.status === 'success') {
                if (window.Toast) Toast.success("Profesor registrado en el aula");
                refrescarPanel(true);
            } else {
                if (window.Toast) Toast.error(res.message, "Error");
                else alert(`Error: ${res.message}`);
            }
        })
        .catch(e => {
            if (window.Toast) Toast.error(e.message);
            else alert(e.message);
        });
    });

    btnMarcarTodos.addEventListener('click', () => {
        if (!currentClaseData || !currentClaseData.estudiantes_ausentes) return;
        const promises = currentClaseData.estudiantes_ausentes.map(e => {
            return fetch(API_MARCAR, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    id_estudiante: e.id_estudiante,
                    fecha: currentClaseData.fecha,
                    estado: 'PRESENTE'
                })
            });
        });
        Promise.all(promises).then(() => refrescarPanel(true));
    });

    // 5. Control de Auto-Refresco
    function toggleAuto() {
        autoRefrescoActivo = !autoRefrescoActivo;
        if (autoRefrescoActivo) {
            btnToggleAuto.className = "px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-semibold shadow-sm transition-all flex items-center gap-1.5";
            dotAuto.classList.remove('hidden');
            textAuto.textContent = "Auto-refresco: ON";
            iniciarTimer();
        } else {
            btnToggleAuto.className = "px-3 py-1.5 bg-slate-600 hover:bg-slate-700 text-white rounded-lg text-xs font-semibold shadow-sm transition-all flex items-center gap-1.5";
            dotAuto.classList.add('hidden');
            textAuto.textContent = "Auto-refresco: OFF";
            detenerTimer();
        }
    }

    function iniciarTimer() {
        detenerTimer();
        autoRefrescoTimer = setInterval(() => {
            if (autoRefrescoActivo) refrescarPanel(true);
        }, 5000);
    }

    function detenerTimer() {
        if (autoRefrescoTimer) clearInterval(autoRefrescoTimer);
    }

    btnToggleAuto.addEventListener('click', toggleAuto);

    init();
    iniciarTimer();
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
