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
            ['Profesores', 'mvc/views/profesores/index.php', 0],
            ['Estudiantes', 'mvc/views/estudiantes/index.php', 1],
            ['Materias', 'mvc/views/materias/index.php', 0],
            ['Aulas', 'mvc/views/aulas/index.php', 0],
            ['Grados', 'mvc/views/grados/index.php', 1],
            ['Usuarios', 'mvc/views/usuarios/index.php', 1],
            ['Asignaciones', 'mvc/views/asignaciones/index.php', 3]
        ];

        for (const [nombre, ruta, selectsMinimos] of paginasCrud) {
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
                    if (selects.every((select) => select.options.length > 1 && !/Cargando/i.test(select.textContent))) return true;
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
                    opciones: [...(modal?.querySelectorAll('select') || [])].map((s) => s.options.length)
                };
            })()`);
            registrar(nombre, 'abrir Crear', crear.boton && crear.abierto, `${crear.selects} selects; opciones=${crear.opciones.join(',')}`);
            registrar(nombre, 'selects Crear', crear.selects >= selectsMinimos && crear.opciones.every((n) => n > 1), `${crear.selects}/${selectsMinimos} requeridos`);
            if (nombre === 'Materias') {
                const camposRetirados = await evaluar(`!(
                    document.querySelector('[name="carga_horaria_semanal"]')
                    || document.querySelector('[name="descripcion"]')
                )`);
                registrar(nombre, 'solo nombre y estado', camposRetirados, camposRetirados ? 'campos retirados' : 'hay campos sobrantes');
            }
            await evaluar(`document.querySelector('.js-cerrar-modal-crear')?.click()`);

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
                    editar.selects >= selectsMinimos && editar.opciones.every((n) => n > 1) && relacionesPrecargadas,
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

        await navegar('mvc/views/horarios/index.php');
        const horariosListos = await esperarTabla('#tabla-horarios-body');
        registrar('Horarios', 'listar', horariosListos, horariosListos ? 'tabla respondio' : 'tabla quedo cargando');
        const horarioCrear = await evaluar(`(() => {
            document.querySelector('#btn-crear-horario')?.click();
            const modal = document.querySelector('#modal-crear-horario');
            const profesor = document.querySelector('#crear-id-profesor');
            const grado = document.querySelector('#crear-id-grado');
            return {
                abierto: Boolean(modal && !modal.classList.contains('hidden')),
                selects: modal?.querySelectorAll('select').length || 0,
                profesores: profesor?.options.length || 0,
                grados: grado?.options.length || 0,
                aulaSoloLectura: document.querySelector('#crear-id-aula')?.readOnly === true
            };
        })()`);
        registrar('Horarios', 'Crear con relaciones', horarioCrear.abierto && horarioCrear.selects >= 4 && horarioCrear.profesores > 1 && horarioCrear.grados > 1 && horarioCrear.aulaSoloLectura, JSON.stringify(horarioCrear));
        await evaluar(`document.querySelector('[data-close-modal="modal-crear-horario"]')?.click()`);
        const horarioEditarExiste = await evaluar(`Boolean(document.querySelector('[data-editar-horario]'))`);
        registrar('Horarios', 'boton Editar', horarioEditarExiste, horarioEditarExiste ? 'disponible' : 'ausente');
        if (horarioEditarExiste) {
            await evaluar(`document.querySelector('[data-editar-horario]').click()`);
            await demora(250);
            const horarioEditar = await evaluar(`({
                abierto: !document.querySelector('#modal-editar-horario').classList.contains('hidden'),
                profesor: document.querySelector('#editar-id-profesor').value,
                materia: document.querySelector('#editar-id-materia').value,
                grado: document.querySelector('#editar-id-grado').value,
                asignacion: document.querySelector('#editar-id-asignacion').value,
                aulaSoloLectura: document.querySelector('#editar-id-aula').readOnly
            })`);
            registrar('Horarios', 'Editar precargado', horarioEditar.abierto && horarioEditar.profesor && horarioEditar.materia && horarioEditar.grado && horarioEditar.asignacion && horarioEditar.aulaSoloLectura, JSON.stringify(horarioEditar));
            await evaluar(`document.querySelector('[data-close-modal="modal-editar-horario"]')?.click()`);
        }

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
        fs.rmSync(profilePath, { recursive: true, force: true });
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
