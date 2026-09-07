/*
 * Smoke test del frontend administrativo con Chrome DevTools Protocol.
 * Abre modales y comprueba tablas/selects, pero no envia formularios.
 */
'use strict';

const { spawn } = require('node:child_process');
const fs = require('node:fs');
const os = require('node:os');
const path = require('node:path');

const chromePath = process.env.CHROME_PATH || 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe';
const baseUrl = (process.env.SISCO_BASE_URL || 'http://localhost/tiago3roBTI2026/sisco-edu').replace(/\/$/, '');
const profilePath = fs.mkdtempSync(path.join(os.tmpdir(), 'sisco-headless-'));
const port = 9337;
const resultados = [];
const erroresConsola = [];
const erroresHttp = [];

const demora = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

function registrar(pagina, prueba, ok, detalle = '') {
    resultados.push({ pagina, prueba, ok, detalle });
    console.log(`${ok ? 'OK' : 'FALLA'} | ${pagina} | ${prueba}${detalle ? ` | ${detalle}` : ''}`);
}

async function esperarObjetivo() {
    for (let intento = 0; intento < 80; intento += 1) {
        try {
            const respuesta = await fetch(`http://127.0.0.1:${port}/json/list`);
            const objetivos = await respuesta.json();
            const pagina = objetivos.find((objetivo) => objetivo.type === 'page');
            if (pagina) return pagina;
        } catch {
            // Chrome todavia esta iniciando.
        }
        await demora(100);
    }
    throw new Error('Chrome no expuso el puerto de depuracion.');
}

async function ejecutar() {
    if (!fs.existsSync(chromePath)) throw new Error(`No se encontro Chrome en ${chromePath}`);

    const chrome = spawn(chromePath, [
        '--headless=new', '--disable-gpu', '--no-first-run', '--no-default-browser-check',
        `--remote-debugging-port=${port}`, `--user-data-dir=${profilePath}`, 'about:blank'
    ], { stdio: 'ignore', windowsHide: true });

    try {
        const objetivo = await esperarObjetivo();
        const ws = new WebSocket(objetivo.webSocketDebuggerUrl);
        await new Promise((resolve, reject) => {
            ws.addEventListener('open', resolve, { once: true });
            ws.addEventListener('error', reject, { once: true });
        });

        let secuencia = 0;
        const pendientes = new Map();
        const eventos = new Map();

        ws.addEventListener('message', (evento) => {
            const mensaje = JSON.parse(evento.data);
            if (mensaje.id) {
                const pendiente = pendientes.get(mensaje.id);
                if (!pendiente) return;
                pendientes.delete(mensaje.id);
                if (mensaje.error) pendiente.reject(new Error(mensaje.error.message));
                else pendiente.resolve(mensaje.result);
                return;
            }

            if (mensaje.method === 'Runtime.exceptionThrown') {
                erroresConsola.push(mensaje.params.exceptionDetails?.text || 'Excepcion JavaScript');
            }
            if (mensaje.method === 'Runtime.consoleAPICalled' && mensaje.params.type === 'error') {
                erroresConsola.push(mensaje.params.args.map((arg) => arg.value || arg.description || '').join(' '));
            }
            if (mensaje.method === 'Network.responseReceived') {
                const { response } = mensaje.params;
                if (response.status >= 400 && response.url.startsWith(baseUrl)) {
                    erroresHttp.push(`${response.status} ${response.url}`);
                }
            }

            const lista = eventos.get(mensaje.method);
            if (lista?.length) lista.shift()(mensaje.params);
        });

        function comando(method, params = {}) {
            const id = ++secuencia;
            return new Promise((resolve, reject) => {
                pendientes.set(id, { resolve, reject });
                ws.send(JSON.stringify({ id, method, params }));
            });
        }

        function esperarEvento(nombre, timeout = 10000) {
            return new Promise((resolve, reject) => {
                const lista = eventos.get(nombre) || [];
                const resolver = (params) => {
                    clearTimeout(temporizador);
                    resolve(params);
                };
                lista.push(resolver);
                eventos.set(nombre, lista);
                const temporizador = setTimeout(() => {
                    const actual = eventos.get(nombre) || [];
                    eventos.set(nombre, actual.filter((item) => item !== resolver));
                    reject(new Error(`Timeout esperando ${nombre}`));
                }, timeout);
            });
        }

        async function evaluar(expression, awaitPromise = false) {
            const respuesta = await comando('Runtime.evaluate', {
                expression, awaitPromise, returnByValue: true, userGesture: true
            });
            if (respuesta.exceptionDetails) {
                throw new Error(respuesta.exceptionDetails.exception?.description || respuesta.exceptionDetails.text);
            }
            return respuesta.result.value;
        }

        async function navegar(ruta) {
            const cargada = esperarEvento('Page.loadEventFired');
            await comando('Page.navigate', { url: `${baseUrl}/${ruta}` });
            await cargada;
        }

        async function esperarTabla(selector = '#tabla-body') {
            return evaluar(`(async () => {
                for (let i = 0; i < 80; i += 1) {
                    const cuerpo = document.querySelector(${JSON.stringify(selector)});
                    if (cuerpo && !/Cargando/i.test(cuerpo.textContent)) return true;
                    await new Promise((resolve) => setTimeout(resolve, 100));
                }
                return false;
            })()`, true);
        }

        await comando('Page.enable');
        await comando('Runtime.enable');
        await comando('Network.enable');

        await navegar('mvc/views/auth/login.php');
        const loginCargado = esperarEvento('Page.loadEventFired');
        await evaluar(`(() => {
            document.querySelector('[name="usuario"]').value = 'admin1';
            document.querySelector('[name="password"]').value = '123456';
            document.querySelector('form').requestSubmit();
            return true;
        })()`);
        await loginCargado;
        const login = await evaluar(`({ url: location.href, titulo: document.title })`);
        registrar('Login', 'autenticar', /dashboard\.php/.test(login.url), login.url);

        const paginasCrud = [
            ['Profesores', 'mvc/views/profesores/index.php', 1],
            ['Estudiantes', 'mvc/views/estudiantes/index.php', 1],
            ['Materias', 'mvc/views/materias/index.php', 0],
            ['Aulas', 'mvc/views/aulas/index.php', 0],
            ['Grados', 'mvc/views/grados/index.php', 1],
            ['Usuarios', 'mvc/views/usuarios/index.php', 1],
            ['Asignaciones', 'mvc/views/asignaciones/index.php', 3]
        ];

        for (const [nombre, ruta, selectsMinimos, selectsEditarMinimos = selectsMinimos] of paginasCrud) {
            await navegar(ruta);
            const tablaLista = await esperarTabla();
            registrar(nombre, 'listar', tablaLista, tablaLista ? 'tabla respondio' : 'tabla quedo cargando');

            const botonCrearExiste = await evaluar(`(() => {
                const boton = document.querySelector('#btn-crear');
                boton?.click();
                return Boolean(boton);
            })()`);
            await evaluar(`(async () => {
                for (let i = 0; i < 50; i += 1) {
                    const selects = [...document.querySelectorAll('#modal-crear select[data-api]')];
                    const grupos = [...document.querySelectorAll('#modal-crear [data-checkbox-group]')];
                    const selectsListos = selects.every((select) => select.options.length > 1 && !/Cargando/i.test(select.textContent));
                    const gruposListos = grupos.every((grupo) => grupo.querySelectorAll('[data-checkbox-options] input[type=checkbox]').length > 0);
                    if (selectsListos && gruposListos) return true;
                    await new Promise((resolve) => setTimeout(resolve, 100));
                }
                return false;
            })()`, true);
            const crear = await evaluar(`(() => {
                const modal = document.querySelector('#modal-crear');
                return {
                    boton: ${botonCrearExiste},
                    abierto: Boolean(modal && !modal.classList.contains('hidden')),
                    selects: modal?.querySelectorAll('select').length || 0,
                    opciones: [...(modal?.querySelectorAll('select') || [])].map((s) => s.options.length),
                    buscadores: modal?.querySelectorAll('[data-select-search]').length || 0
                };
            })()`);
            registrar(nombre, 'abrir Crear', crear.boton && crear.abierto, `${crear.selects} selects; opciones=${crear.opciones.join(',')}`);
            registrar(nombre, 'selects Crear', crear.selects >= selectsMinimos && crear.opciones.every((n) => n > 1), `${crear.selects}/${selectsMinimos} requeridos`);
            if (nombre === 'Asignaciones') {
                const organizacion = await evaluar(`(async () => {
                    const grado = document.querySelector('#filtro-principal');
                    const selects = [...document.querySelectorAll('#modal-crear select[data-api]')];
                    const selectsOrdenados = selects.every((select) => {
                        const textos = [...select.options].slice(1).map((opcion) => opcion.text);
                        return textos.every((texto, indice) =>
                            indice === 0 || textos[indice - 1].localeCompare(texto, 'es', { numeric: true, sensitivity: 'base' }) <= 0
                        );
                    });
                    const buscadorMateria = document.querySelector('[data-select-search="id_materia"]');
                    const selectMateria = document.querySelector('select[name="id_materia"]');
                    const opcionesAntes = selectMateria?.options.length || 0;
                    if (buscadorMateria && selectMateria && opcionesAntes > 2) {
                        buscadorMateria.value = selectMateria.options[1].text;
                        buscadorMateria.dispatchEvent(new Event('input', { bubbles: true }));
                    }
                    const opcionesDespues = selectMateria?.options.length || 0;
                    const panelSugerencias = buscadorMateria?.nextElementSibling;
                    const sugerenciasInmediatas = Boolean(
                        panelSugerencias
                        && panelSugerencias.classList.contains('app-select-suggestions')
                        && !panelSugerencias.hidden
                        && panelSugerencias.querySelector('[role="option"]')
                    );
                    let seleccionMateriaConTeclado = false;
                    let seleccionMateriaVisible = false;
                    if (sugerenciasInmediatas) {
                        buscadorMateria.dispatchEvent(new KeyboardEvent('keydown', {
                            key: 'ArrowDown', bubbles: true, cancelable: true
                        }));
                        const sugerenciaActiva = panelSugerencias.querySelector('.app-select-suggestion.is-active');
                        buscadorMateria.dispatchEvent(new KeyboardEvent('keydown', {
                            key: 'Enter', bubbles: true, cancelable: true
                        }));
                        seleccionMateriaConTeclado = Boolean(
                            sugerenciaActiva && selectMateria.value && panelSugerencias.hidden
                        );
                        seleccionMateriaVisible = buscadorMateria.value === selectMateria.selectedOptions[0]?.text;
                    }
                    const selectsRedundantesOcultos = [...document.querySelectorAll('#modal-crear select[data-searchable="1"]')]
                        .every((select) => select.classList.contains('app-select-source-hidden'));
                    const selectGradoCrear = document.querySelector('#modal-crear select[name="id_grado"]');
                    const contextoInicialCoincide = Boolean(
                        grado?.value && selectGradoCrear?.value === grado.value
                    );
                    const opcionContexto = [...(grado?.options || [])].filter((opcion) => opcion.value).at(-1);
                    let contextoActualizadoCoincide = false;
                    let contextoPersistidoEnUrl = false;
                    if (opcionContexto) {
                        grado.value = opcionContexto.value;
                        grado.dispatchEvent(new Event('change', { bubbles: true }));
                        document.querySelector('#btn-crear')?.click();
                        await new Promise((resolve) => setTimeout(resolve, 0));
                        contextoActualizadoCoincide = selectGradoCrear?.value === opcionContexto.value;
                        contextoPersistidoEnUrl = new URL(window.location.href).searchParams.get('filtro_id_grado')
                            === opcionContexto.value;
                    }
                    const opcionesGrado = [...(selectGradoCrear?.options || [])].slice(1);
                    const gradosOrdenados = opcionesGrado.every((opcion, indice) =>
                        indice === 0 || opcionesGrado[indice - 1].text.localeCompare(
                            opcion.text,
                            'es',
                            { numeric: true, sensitivity: 'base' }
                        ) <= 0
                    );
                    if (selectGradoCrear && opcionesGrado.length) {
                        selectGradoCrear.value = opcionesGrado[0].value;
                        selectGradoCrear.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                    const unaSolaSeleccion = selectGradoCrear?.selectedOptions.length === 1
                        && Boolean(selectGradoCrear.value);
                    const buscadorGrados = document.querySelector('[data-select-search="id_grado"]');
                    if (buscadorGrados && opcionesGrado.length > 2) {
                        buscadorGrados.value = opcionesGrado[0].text;
                        buscadorGrados.dispatchEvent(new Event('input', { bubbles: true }));
                    }
                    const opcionesGradoFiltradas = selectGradoCrear?.options.length || 0;
                    return {
                        selectorGrado: Boolean(grado && grado.options.length > 1 && grado.value),
                        contextoInicialCoincide,
                        contextoActualizadoCoincide,
                        contextoPersistidoEnUrl,
                        resumen: document.querySelector('#resumen-filtro-principal')?.textContent || '',
                        buscadoresSelect: ${crear.buscadores},
                        selectsOrdenados,
                        busquedaFiltra: opcionesAntes <= 2 || opcionesDespues < opcionesAntes,
                        sugerenciasInmediatas,
                        seleccionMateriaConTeclado,
                        seleccionMateriaVisible,
                        selectsRedundantesOcultos,
                        gradosDisponibles: opcionesGrado.length,
                        gradosConAula: opcionesGrado.length > 0
                            && opcionesGrado.every((opcion) => opcion.text.includes(' - ')),
                        gradosOrdenados,
                        unaSolaSeleccion,
                        busquedaGradoFiltra: opcionesGrado.length <= 2
                            || opcionesGradoFiltradas < opcionesGrado.length + 1
                    };
                })()`, true);
                registrar(
                    nombre,
                    'organización y selección única de grado',
                    organizacion.selectorGrado
                        && organizacion.contextoInicialCoincide
                        && organizacion.contextoActualizadoCoincide
                        && organizacion.contextoPersistidoEnUrl
                        && organizacion.resumen
                        && organizacion.buscadoresSelect === 3
                        && organizacion.selectsOrdenados
                        && organizacion.busquedaFiltra
                        && organizacion.sugerenciasInmediatas
                        && organizacion.seleccionMateriaConTeclado
                        && organizacion.seleccionMateriaVisible
                        && organizacion.selectsRedundantesOcultos
                        && organizacion.gradosDisponibles > 1
                        && organizacion.gradosConAula
                        && organizacion.gradosOrdenados
                        && organizacion.unaSolaSeleccion
                        && organizacion.busquedaGradoFiltra,
                    JSON.stringify(organizacion)
                );
            }
            if (nombre === 'Materias') {
                const camposRetirados = await evaluar(`!(
                    document.querySelector('[name="carga_horaria_semanal"]')
                    || document.querySelector('[name="descripcion"]')
                )`);
                registrar(nombre, 'solo nombre y estado', camposRetirados, camposRetirados ? 'campos retirados' : 'hay campos sobrantes');
            }
            await evaluar(`document.querySelector('.js-cerrar-modal-crear')?.click()`);

            if (nombre === 'Asignaciones') {
                const gradoPersistido = await evaluar(`new URL(window.location.href).searchParams.get('filtro_id_grado')`);
                await navegar(`${ruta}?filtro_id_grado=${encodeURIComponent(gradoPersistido)}`);
                await esperarTabla();
                const filtroRestaurado = await evaluar(`document.querySelector('#filtro-principal')?.value`);
                registrar(
                    nombre,
                    'mantener curso después de recargar',
                    Boolean(gradoPersistido) && String(filtroRestaurado) === String(gradoPersistido),
                    `esperado ${gradoPersistido}, recibido ${filtroRestaurado}`
                );
            }

            const hayEditar = await evaluar(`Boolean(document.querySelector('[data-accion-registro=editar]'))`);
            registrar(nombre, 'boton Editar', hayEditar, hayEditar ? 'disponible' : 'sin registro editable');
            if (hayEditar) {
                await evaluar(`document.querySelector('[data-accion-registro=editar]').click()`);
                await demora(350);
                const editar = await evaluar(`(() => {
                    const modal = document.querySelector('#modal-editar');
                    const selects = [...(modal?.querySelectorAll('select') || [])];
                    return {
                        abierto: Boolean(modal && !modal.classList.contains('hidden')),
                        selects: selects.length,
                        opciones: selects.map((s) => s.options.length),
                        valores: selects.map((s) => s.value)
                    };
                })()`);
                registrar(nombre, 'abrir Editar', editar.abierto, `${editar.selects} selects; valores=${editar.valores.join(',')}`);
                const relacionesPrecargadas = selectsMinimos === 0 || editar.valores.every((valor) => String(valor).trim() !== '');
                registrar(
                    nombre,
                    'selects Editar',
                    editar.selects >= selectsEditarMinimos && editar.opciones.every((n) => n > 1) && relacionesPrecargadas,
                    `opciones=${editar.opciones.join(',')}; valores=${editar.valores.join(',')}`
                );
                await evaluar(`document.querySelector('#cancelar-editar')?.click()`);
            }

            const controles = await evaluar(`({
                filtros: document.querySelectorAll('#filtros-tabla input, #filtros-tabla select').length,
                desactivar: Boolean(document.querySelector('[data-accion-registro=desactivar]')),
                mojibake: /(?:Ã.|Â.|â€|�)/.test(document.body.innerText)
            })`);
            registrar(nombre, 'filtros', controles.filtros > 0, `${controles.filtros} controles`);
            registrar(nombre, 'boton Desactivar', controles.desactivar, controles.desactivar ? 'disponible' : 'ausente');
            registrar(nombre, 'texto UTF-8', !controles.mojibake, controles.mojibake ? 'texto corrupto visible' : 'correcto');
        }

        await navegar('mvc/views/profesores/index.php');
        await esperarTabla();
        const distribucionHuella = await evaluar(`(async () => {
            const registrar = [...document.querySelectorAll('[data-accion-registro="huella"][data-tipo-persona="profesor"]')];
            const enviar = document.querySelector('[data-accion-registro="sincronizar-huella"]');
            enviar?.click();
            for (let i = 0; i < 50 && !document.querySelector('#modal-aulas-huella'); i += 1) {
                await new Promise((resolve) => setTimeout(resolve, 100));
            }
            const modal = document.querySelector('#modal-aulas-huella');
            const resultado = {
                registrar: registrar.length,
                enviar: Boolean(enviar),
                modal: Boolean(modal),
                aulas: modal?.querySelectorAll('input[type="checkbox"]').length || 0
            };
            modal?.querySelector('[data-cancelar-aulas]')?.click();
            return resultado;
        })()`, true);
        registrar('Profesores', 'distribucion de huella por aula', distribucionHuella.registrar > 0 && distribucionHuella.enviar && distribucionHuella.modal && distribucionHuella.aulas > 0, JSON.stringify(distribucionHuella));

        await navegar('mvc/views/horarios/index.php');
        const horariosListos = await esperarTabla('#tabla-horarios-body');
        registrar('Horarios', 'listar', horariosListos, horariosListos ? 'tabla respondio' : 'tabla quedo cargando');
        const coincidenciasCompletas = await evaluar(`(() => {
            document.querySelector('#btn-crear-horario')?.click();
            const select = document.querySelector('#crear-id-asignacion');
            const buscador = document.querySelector('#crear-buscar-asignacion');
            const panel = document.querySelector('#crear-asignacion-sugerencias');
            const normalizar = texto => texto.normalize('NFD').replace(/[\\u0300-\\u036f]/g, '').toLowerCase();
            buscador.value = '9';
            buscador.dispatchEvent(new Event('input', { bubbles: true }));
            const esperadas = [...select.options].filter(o => o.value && normalizar(o.textContent).includes('9'));
            const visibles = [...panel.querySelectorAll('[role=option]')];
            const matematica = visibles.find(o => normalizar(o.textContent).includes('matematica'));
            const esperadaMatematica = esperadas.find(o => normalizar(o.textContent).includes('matematica'));
            matematica?.click();
            const seleccionCorrecta = !esperadaMatematica || select.value === esperadaMatematica.value;
            buscador.value = '';
            buscador.dispatchEvent(new Event('input', { bubbles: true }));
            return { esperadas: esperadas.length, visibles: visibles.length, seleccionCorrecta,
                todas: panel.querySelectorAll('[role=option]').length === [...select.options].filter(o => o.value).length };
        })()`);
        registrar('Horarios', 'todas las asignaciones y Matemática de noveno seleccionable',
            coincidenciasCompletas.esperadas === coincidenciasCompletas.visibles
                && coincidenciasCompletas.seleccionCorrecta && coincidenciasCompletas.todas,
            JSON.stringify(coincidenciasCompletas));
        const conjuntaMixta = await evaluar(`(async () => {
            const respuesta = await fetch(window.HORARIOS_CONFIG.apiAsignaciones);
            const json = await respuesta.json();
            const lista = json.data || json;
            let principal, otra;
            for (const candidata of lista) {
                const compatible = lista.find(x => String(x.id_profesor) === String(candidata.id_profesor)
                    && String(x.anio_lectivo) === String(candidata.anio_lectivo)
                    && String(x.id_grado) !== String(candidata.id_grado)
                    && String(x.id_materia) !== String(candidata.id_materia));
                if (compatible) { principal = candidata; otra = compatible; break; }
            }
            if (!principal) return { fixture: false };
            const select = document.querySelector('#crear-id-asignacion');
            const conjunta = document.querySelector('#crear-permite-superposicion');
            const mixta = document.querySelector('#crear-materias-distintas');
            select.value = principal.id_asignacion;
            select.dispatchEvent(new Event('change', { bubbles: true }));
            conjunta.checked = true; conjunta.dispatchEvent(new Event('change', { bubbles: true }));
            const opciones = () => [...document.querySelectorAll('#crear-opciones-clase-conjunta [data-clase-conjunta-opcion]')];
            const sinOptIn = !opciones().some(o => o.value === String(otra.id_asignacion));
            mixta.checked = true; mixta.dispatchEvent(new Event('change', { bubbles: true }));
            const encontrada = opciones().find(o => o.value === String(otra.id_asignacion));
            encontrada?.click();
            const campo = document.querySelector('#crear-id-asignacion-conjunta');
            const seleccionada = campo.value.split(',').includes(String(otra.id_asignacion));
            const textoAyuda = document.querySelector('#crear-clase-conjunta-ayuda').textContent;
            const alternativas = opciones().filter(o => lista.some(x => String(x.id_asignacion) === o.value
                && String(x.id_grado) === String(otra.id_grado) && o.value !== String(otra.id_asignacion)));
            alternativas[0]?.click();
            const grados = campo.value.split(',').filter(Boolean).map(id => lista.find(x => String(x.id_asignacion) === id)?.id_grado);
            const gradoUnico = new Set(grados).size === grados.length;
            mixta.checked = false; mixta.dispatchEvent(new Event('change', { bubbles: true }));
            const soloMismaMateria = campo.value.split(',').filter(Boolean).every(id => {
                const a = lista.find(x => String(x.id_asignacion) === id);
                return String(a.id_materia) === String(principal.id_materia);
            });
            conjunta.checked = false; conjunta.dispatchEvent(new Event('change', { bubbles: true }));
            return { fixture: true, sinOptIn, visible: Boolean(encontrada), seleccionada,
                materiaEnResumen: textoAyuda.includes(otra.materia), gradoUnico, soloMismaMateria,
                csrf: document.querySelector('#form-crear-horario [name=horarios_csrf]').value.length === 64 };
        })()`, true);
        registrar('Horarios', 'materias distintas explícitas y una asignación por grado',
            Object.values(conjuntaMixta).every(Boolean), JSON.stringify(conjuntaMixta));
        const horarioCrear = await evaluar(`(() => {
            document.querySelector('#btn-crear-horario')?.click();
            const modal = document.querySelector('#modal-crear-horario');
            const asignacion = document.querySelector('#crear-id-asignacion');
            const buscador = document.querySelector('#crear-buscar-asignacion');
            const panel = document.querySelector('#crear-asignacion-sugerencias');
            let sugerenciasInmediatas = false;
            let seleccionAsignacionConTeclado = false;
            if (asignacion?.options.length > 1 && buscador) {
                buscador.value = asignacion.options[1].text.split(' · ')[0];
                buscador.dispatchEvent(new Event('input', { bubbles: true }));
                sugerenciasInmediatas = Boolean(panel && !panel.hidden && panel.querySelector('[role=option]'));
                buscador.dispatchEvent(new KeyboardEvent('keydown', {
                    key: 'ArrowDown', bubbles: true, cancelable: true
                }));
                const sugerenciaActiva = panel?.querySelector('.app-select-suggestion.is-active');
                buscador.dispatchEvent(new KeyboardEvent('keydown', {
                    key: 'Enter', bubbles: true, cancelable: true
                }));
                seleccionAsignacionConTeclado = Boolean(
                    sugerenciaActiva && asignacion.value && panel.hidden
                );
                asignacion.value = asignacion.options[1].value;
                asignacion.dispatchEvent(new Event('change', { bubbles: true }));
            }
            const conjunta = document.querySelector('#crear-permite-superposicion');
            const cursoConjunto = document.querySelector('#crear-id-asignacion-conjunta');
            let autovinculo = false;
            for (const opcion of [...(asignacion?.options || [])].slice(1)) {
                asignacion.value = opcion.value;
                asignacion.dispatchEvent(new Event('change', { bubbles: true }));
                conjunta.checked = true;
                conjunta.dispatchEvent(new Event('change', { bubbles: true }));
                document.querySelector('#crear-seleccionar-clase-conjunta')?.click();
                if (cursoConjunto.value) {
                    autovinculo = true;
                    break;
                }
            }
            const normalizar = (valor) => String(valor || '')
                .normalize('NFD').replace(/[\u0300-\u036f]/g, '').toUpperCase();
            const opcionCultoSegundoBcb = [...(asignacion?.options || [])].find((opcion) => {
                const texto = normalizar(opcion.text);
                return texto.startsWith('2') && texto.includes('BCB') && texto.includes('CULTO');
            });
            let nivelMedioCompleto = false;
            let bloquesNivelMedioSinCambios = false;
            if (opcionCultoSegundoBcb) {
                asignacion.value = opcionCultoSegundoBcb.value;
                asignacion.dispatchEvent(new Event('change', { bubbles: true }));
                conjunta.checked = true;
                conjunta.dispatchEvent(new Event('change', { bubbles: true }));
                nivelMedioCompleto = [...document.querySelectorAll('#crear-opciones-clase-conjunta [data-clase-conjunta-opcion]')]
                    .some((opcion) => {
                    const texto = normalizar(opcion.closest('label')?.textContent);
                    return opcion.value && texto.includes('2') && texto.includes('BTI') && texto.includes('CULTO');
                });
                const bloques = [...document.querySelector('#crear-bloque-horario').options]
                    .map((opcion) => opcion.value);
                bloquesNivelMedioSinCambios = bloques.includes('09:40|10:20')
                    && !bloques.includes('10:10|10:50');
            }
            const opcionCultoTercerCiclo = [...(asignacion?.options || [])].find((opcion) =>
                opcion.text.trim().startsWith('7') && opcion.text.toUpperCase().includes('CULTO')
            );
            let tercerCicloCompleto = false;
            let bloquesTercerCicloCorrectos = false;
            if (opcionCultoTercerCiclo) {
                asignacion.value = opcionCultoTercerCiclo.value;
                asignacion.dispatchEvent(new Event('change', { bubbles: true }));
                conjunta.checked = true;
                conjunta.dispatchEvent(new Event('change', { bubbles: true }));
                document.querySelector('#crear-seleccionar-clase-conjunta')?.click();
                const opcionesConjuntas = document.querySelector('#crear-opciones-clase-conjunta')?.textContent || '';
                tercerCicloCompleto = cursoConjunto.value.split(',').filter(Boolean).length === 2
                    && opcionesConjuntas.includes('8°')
                    && opcionesConjuntas.includes('9°');
                const bloques = [...document.querySelector('#crear-bloque-horario').options]
                    .map((opcion) => opcion.value);
                bloquesTercerCicloCorrectos = bloques.includes('10:10|10:50')
                    && !bloques.includes('09:40|10:20');
            }
            const opcionRoboticaSegundoBcb = [...(asignacion?.options || [])].find((opcion) => {
                const texto = normalizar(opcion.text);
                return texto.startsWith('2') && texto.includes('BCB') && texto.includes('ROBOTICA');
            });
            let roboticaSegundoYTercero = false;
            let buscadorClaseConjunta = false;
            if (opcionRoboticaSegundoBcb) {
                asignacion.value = opcionRoboticaSegundoBcb.value;
                asignacion.dispatchEvent(new Event('change', { bubbles: true }));
                conjunta.checked = true;
                conjunta.dispatchEvent(new Event('change', { bubbles: true }));
                roboticaSegundoYTercero = [...document.querySelectorAll('#crear-opciones-clase-conjunta label')]
                    .some((item) => {
                        const texto = normalizar(item.textContent);
                        return texto.includes('3') && texto.includes('BCB') && texto.includes('ROBOTICA');
                    });
                const buscadorConjunto = document.querySelector('#crear-buscar-clase-conjunta');
                if (buscadorConjunto) {
                    buscadorConjunto.value = '3 BCB';
                    buscadorConjunto.dispatchEvent(new Event('input', { bubbles: true }));
                    const visibles = [...document.querySelectorAll('#crear-opciones-clase-conjunta label')];
                    buscadorClaseConjunta = visibles.length > 0
                        && visibles.every((item) => normalizar(item.textContent).includes('3'));
                }
            }
            const selectorSemanal = document.querySelector('#selector-grado-semanal');
            const opcionSeptimo = [...(selectorSemanal?.options || [])].find((opcion) =>
                opcion.text.trim().startsWith('7')
            );
            let recreoTercerCicloVisible = false;
            if (opcionSeptimo) {
                selectorSemanal.value = opcionSeptimo.value;
                selectorSemanal.dispatchEvent(new Event('change', { bubbles: true }));
                recreoTercerCicloVisible = [...document.querySelectorAll('.schedule-grid-time.is-break')]
                    .some((item) => item.textContent.includes('09:40') && item.textContent.includes('10:10'));
            }
            return {
                abierto: Boolean(modal && !modal.classList.contains('hidden')),
                selects: modal?.querySelectorAll('select').length || 0,
                asignaciones: asignacion?.options.length || 0,
                sugerenciasInmediatas,
                seleccionAsignacionConTeclado,
                seleccionVisibleEnBuscador: Boolean(
                    asignacion.value
                    && buscador.value === asignacion.selectedOptions[0]?.text
                ),
                selectorRedundanteOculto: asignacion.classList.contains('app-select-source-hidden'),
                profesorCompleto: Boolean(document.querySelector('#crear-profesor')?.value),
                materiaCompleta: Boolean(document.querySelector('#crear-materia')?.value),
                gradoCompleto: Boolean(document.querySelector('#crear-grado')?.value),
                aulaSoloLectura: document.querySelector('#crear-id-aula')?.readOnly === true,
                excepcionDisponible: conjunta?.type === 'checkbox',
                horasCompartidas: document.querySelectorAll('.schedule-grid-time').length >= 10,
                horasExtraDisponible: document.querySelector('#crear-horario-extra')?.type === 'checkbox',
                etiquetaTurnoTarde: [...document.querySelectorAll('strong')]
                    .some((item) => item.textContent.includes('turno tarde') || item.textContent.includes('Turno tarde')),
                listaPlegada: document.querySelector('.schedule-admin-details')?.open === false,
                autovinculo,
                nivelMedioCompleto,
                bloquesNivelMedioSinCambios,
                tercerCicloCompleto,
                bloquesTercerCicloCorrectos,
                roboticaSegundoYTercero,
                buscadorClaseConjunta,
                recreoTercerCicloVisible,
                sincronizacionExplicada: document.querySelector('#crear-clase-conjunta-ayuda')?.textContent
                    .match(/automáticamente|Seleccioná/),
                edicionDirecta: !document.querySelector('[data-gestionar-horarios]')
                    && [...document.querySelectorAll('.schedule-entry')].every((tarjeta) =>
                        Boolean(tarjeta.querySelector('[data-editar-horario]'))
                    ),
                tarjetasConTexto: [...document.querySelectorAll('.schedule-entry')].every((tarjeta) =>
                    Boolean(tarjeta.querySelector('.schedule-entry-subject')?.textContent.trim())
                    && Boolean(tarjeta.querySelector('.schedule-entry-meta')?.textContent.trim())
                )
            };
        })()`);
        registrar(
            'Horarios',
            'Grilla compacta, turno tarde y clase conjunta',
            horarioCrear.abierto
                && horarioCrear.selects >= 3
                && horarioCrear.asignaciones > 1
                && horarioCrear.sugerenciasInmediatas
                && horarioCrear.seleccionAsignacionConTeclado
                && horarioCrear.seleccionVisibleEnBuscador
                && horarioCrear.selectorRedundanteOculto
                && horarioCrear.profesorCompleto
                && horarioCrear.materiaCompleta
                && horarioCrear.gradoCompleto
                && horarioCrear.aulaSoloLectura
                && horarioCrear.excepcionDisponible
                && horarioCrear.horasCompartidas
                && horarioCrear.horasExtraDisponible
                && horarioCrear.etiquetaTurnoTarde
                && horarioCrear.listaPlegada
                && horarioCrear.autovinculo
                && horarioCrear.nivelMedioCompleto
                && horarioCrear.bloquesNivelMedioSinCambios
                && horarioCrear.tercerCicloCompleto
                && horarioCrear.bloquesTercerCicloCorrectos
                && horarioCrear.roboticaSegundoYTercero
                && horarioCrear.buscadorClaseConjunta
                && horarioCrear.recreoTercerCicloVisible
                && horarioCrear.sincronizacionExplicada
                && horarioCrear.edicionDirecta
                && horarioCrear.tarjetasConTexto,
            JSON.stringify(horarioCrear)
        );
        await evaluar(`document.querySelector('[data-close-modal="modal-crear-horario"]')?.click()`);
        const horarioEditarExiste = await evaluar(`Boolean(document.querySelector('[data-editar-horario]'))`);
        registrar('Horarios', 'boton Editar', horarioEditarExiste, horarioEditarExiste ? 'disponible' : 'ausente');
        if (horarioEditarExiste) {
            await evaluar(`document.querySelector('[data-editar-horario]').click()`);
            await demora(250);
            const horarioEditar = await evaluar(`({
                abierto: !document.querySelector('#modal-editar-horario').classList.contains('hidden'),
                asignacion: document.querySelector('#editar-id-asignacion').value,
                profesor: document.querySelector('#editar-profesor').value,
                materia: document.querySelector('#editar-materia').value,
                grado: document.querySelector('#editar-grado').value,
                aulaSoloLectura: document.querySelector('#editar-id-aula').readOnly,
                excepcionDisponible: document.querySelector('#editar-permite-superposicion')?.type === 'checkbox'
            })`);
            registrar('Horarios', 'Editar precargado', horarioEditar.abierto && horarioEditar.profesor && horarioEditar.materia && horarioEditar.grado && horarioEditar.asignacion && horarioEditar.aulaSoloLectura && horarioEditar.excepcionDisponible, JSON.stringify(horarioEditar));
            await evaluar(`document.querySelector('[data-close-modal="modal-editar-horario"]')?.click()`);
        }

        await navegar('mvc/views/planificacion/index.php');
        const planificacion = await evaluar(`(async () => {
            for (let i = 0; i < 60; i += 1) {
                const select = document.querySelector('#plan-asignacion');
                const lista = document.querySelector('#planes-lista');
                if (select?.options.length > 1 && lista && !/Cargando/i.test(lista.textContent)) {
                    return { asignaciones: select.options.length, lista: lista.textContent.trim().length, crear: Boolean(document.querySelector('#form-crear-plan')) };
                }
                await new Promise((resolve) => setTimeout(resolve, 100));
            }
            return { asignaciones: 0, lista: 0, crear: false };
        })()`, true);
        registrar('Planificacion', 'lista y creacion', planificacion.asignaciones > 1 && planificacion.lista > 0 && planificacion.crear, JSON.stringify(planificacion));

        await navegar('mvc/views/informes/diario.php');
        const informes = await evaluar(`(async () => {
            for (let i = 0; i < 60; i += 1) {
                const select = document.querySelector('#informe-asignacion');
                const lista = document.querySelector('#lista-clases');
                if (select?.options.length > 1 && lista && !/Cargando/i.test(lista.textContent)) {
                    return { asignaciones: select.options.length, filtro: Boolean(document.querySelector('#informe-fecha')), lista: lista.textContent.trim().length };
                }
                await new Promise((resolve) => setTimeout(resolve, 100));
            }
            return { asignaciones: 0, filtro: false, lista: 0 };
        })()`, true);
        registrar('Informe diario', 'filtros y clases', informes.asignaciones > 1 && informes.filtro && informes.lista > 0, JSON.stringify(informes));

        await navegar('mvc/views/configuracion/informes.php');
        const configInformes = await evaluar(`(async () => {
            for (let i = 0; i < 40; i += 1) {
                const vista = document.querySelector('#membrete-actual');
                if (vista && !/Cargando/i.test(vista.textContent)) return { vista: true, formulario: Boolean(document.querySelector('#form-membrete')) };
                await new Promise((resolve) => setTimeout(resolve, 100));
            }
            return { vista: false, formulario: false };
        })()`, true);
        registrar('Configuracion informes', 'membrete', configInformes.vista && configInformes.formulario, JSON.stringify(configInformes));

        const resetMembrete = await evaluar(`(async () => {
            const formulario = document.querySelector('#form-membrete');
            const mensaje = document.querySelector('#mensaje-config-informes');
            if (!formulario || !mensaje) return { ok: false, mensaje: 'Formulario no disponible' };
            const fetchOriginal = window.fetch;
            window.fetch = async () => ({
                ok: true,
                json: async () => ({ success: true, message: 'Membrete actualizado.', data: null })
            });
            formulario.dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }));
            await new Promise((resolve) => setTimeout(resolve, 50));
            window.fetch = fetchOriginal;
            return {
                ok: mensaje.classList.contains('app-message-success') && !/Cannot read properties/i.test(mensaje.textContent),
                mensaje: mensaje.textContent
            };
        })()`, true);
        registrar('Configuracion informes', 'reset despues de subir', resetMembrete.ok, JSON.stringify(resetMembrete));

        for (const [nombre, ruta, selector] of [
            ['Asistencia estudiantes', 'mvc/views/asistencias_estudiantes/index.php', '#tabla-body'],
            ['Asistencia profesores', 'mvc/views/asistencias_profesores/index.php', '#tabla-body']
        ]) {
            await navegar(ruta);
            const lista = await esperarTabla(selector);
            registrar(nombre, 'visualizacion solamente', lista, 'sin abrir ni enviar formularios');
        }

        await navegar('mvc/views/administracion/index.php');
        const administracion = await evaluar(`({ enlaces: document.querySelectorAll('a[href]').length, texto: document.body.innerText.length })`);
        registrar('Administracion', 'cargar', administracion.enlaces >= 5 && administracion.texto > 100, `${administracion.enlaces} enlaces`);

        await navegar('mvc/views/dashboard.php');
        const dashboard = await evaluar(`(async () => {
            for (let i = 0; i < 50; i += 1) {
                const metricas = [...document.querySelectorAll('#dashboard-metricas article')];
                if (metricas.length >= 8) {
                    return { cantidad: metricas.length, cargadas: metricas.filter((m) => /\\d/.test(m.textContent)).length };
                }
                await new Promise((resolve) => setTimeout(resolve, 100));
            }
            return { cantidad: 0, cargadas: 0 };
        })()`, true);
        registrar('Dashboard', 'metricas', dashboard.cantidad >= 8 && dashboard.cargadas === dashboard.cantidad, JSON.stringify(dashboard));

        const excepcionesReales = erroresConsola.filter((error) => error && !/favicon|tailwind/i.test(error));
        registrar('Global', 'errores JavaScript', excepcionesReales.length === 0, excepcionesReales.join(' || ') || 'ninguno');
        registrar('Global', 'errores HTTP', erroresHttp.length === 0, erroresHttp.join(' || ') || 'ninguno');

        ws.close();
    } finally {
        chrome.kill();
        await demora(200);
        for (let intento = 0; intento < 5; intento += 1) {
            try { fs.rmSync(profilePath, { recursive: true, force: true }); break; }
            catch (error) { if (intento === 4) console.warn(`No se pudo limpiar el perfil temporal: ${error.message}`); else await demora(200); }
        }
    }
}

ejecutar()
    .catch((error) => {
        console.error(`ERROR PRINCIPAL: ${error.stack || error.message}`);
        process.exitCode = 1;
    })
    .finally(() => {
        const fallas = resultados.filter((resultado) => !resultado.ok);
        console.log(`RESUMEN | ${resultados.length - fallas.length} OK | ${fallas.length} FALLAS`);
        if (fallas.length) process.exitCode = 1;
    });
