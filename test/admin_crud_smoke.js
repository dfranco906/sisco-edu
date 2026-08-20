/*
 * Prueba funcional CRUD de la administracion.
 * Crea registros identificados con AUDIT_, prueba actualizacion,
 * desactivacion/restauracion y los elimina al finalizar.
 * No llama endpoints de asistencia, huellas, sync ni hardware.
 */
'use strict';

const baseUrl = (process.env.SISCO_BASE_URL || 'http://localhost/tiago3roBTI2026/sisco-edu').replace(/\/$/, '');
const token = `AUDIT_${Date.now().toString().slice(-8)}`;
const ids = {};
const resultados = [];

function registrar(modulo, accion, ok, detalle = '') {
    resultados.push({ modulo, accion, ok, detalle });
    console.log(`${ok ? 'OK' : 'FALLA'} | ${modulo} | ${accion}${detalle ? ` | ${detalle}` : ''}`);
}

async function solicitar(ruta, datos = null) {
    const opciones = datos === null
        ? {}
        : {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
            body: new URLSearchParams(datos)
        };
    const respuesta = await fetch(`${baseUrl}/${ruta}`, opciones);
    const texto = await respuesta.text();
    let json;
    try {
        json = JSON.parse(texto);
    } catch {
        throw new Error(`${ruta} devolvio contenido no JSON (${respuesta.status}): ${texto.slice(0, 180)}`);
    }
    return { http: respuesta.status, json };
}

function esExito(respuesta) {
    return respuesta.json.success === true || respuesta.json.status === 'success';
}

async function exigir(modulo, accion, ruta, datos, httpEsperado = null) {
    const respuesta = await solicitar(ruta, datos);
    const ok = httpEsperado === null
        ? (respuesta.http >= 200 && respuesta.http < 300 && esExito(respuesta))
        : respuesta.http === httpEsperado;
    registrar(modulo, accion, ok, `HTTP ${respuesta.http}: ${respuesta.json.message || 'sin mensaje'}`);
    if (!ok) throw new Error(`${modulo}/${accion} fallo`);
    return respuesta.json;
}

async function cicloEstado(modulo, carpeta, nombreId, id) {
    await exigir(modulo, 'desactivar', `src/api/${carpeta}/desactivar_${modulo.toLowerCase()}.php`, { [nombreId]: id });
    await exigir(modulo, 'restaurar', `src/api/${carpeta}/restaurar_${modulo.toLowerCase()}.php`, { [nombreId]: id });
}

async function limpiar() {
    const objetivos = [
        ['Horario', 'Horario', 'id_horario', ids.horario],
        ['Asignacion', 'Asignaciones', 'id_asignacion', ids.asignacion],
        ['Estudiante', 'Estudiante', 'id_estudiante', ids.estudiante],
        ['Grado', 'Grado', 'id_grado', ids.grado],
        ['Aula', 'Aula', 'id_aula', ids.aula],
        ['Profesor', 'Profesor', 'id_profesor', ids.profesor],
        ['Materia', 'Materia', 'id_materia', ids.materia],
        ['Usuario', 'Usuario', 'id_usuario', ids.usuario]
    ];

    for (const [modulo, carpeta, nombreId, id] of objetivos) {
        if (id === undefined || id === null) continue;
        try {
            const ruta = `src/api/${carpeta}/eliminar_${modulo.toLowerCase()}.php`;
            const respuesta = await solicitar(ruta, { [nombreId]: id });
            registrar(modulo, 'limpieza', esExito(respuesta), `HTTP ${respuesta.http}: ${respuesta.json.message || 'sin mensaje'}`);
        } catch (error) {
            registrar(modulo, 'limpieza', false, error.message);
        }
    }
}

async function ejecutar() {
    const profesor = await exigir('Profesor', 'crear', 'src/api/Profesor/crear_profesor.php', {
        nombre: 'Auditoria', apellido: 'Profesor', cedula_identidad: `${token}P`
    });
    ids.profesor = profesor.data.id_profesor;
    await exigir('Profesor', 'editar', 'src/api/Profesor/actualizar_profesor.php', {
        id_profesor: ids.profesor, nombre: 'Auditoria Editada', apellido: 'Profesor', cedula_identidad: `${token}P`
    });
    await exigir('Profesor', 'duplicado claro', 'src/api/Profesor/crear_profesor.php', {
        nombre: 'Duplicado', apellido: 'Profesor', cedula_identidad: `${token}P`
    }, 409);

    const materia = await exigir('Materia', 'crear', 'src/api/Materia/crear_materia.php', {
        nombre: `${token} Materia`, descripcion: 'Registro temporal', carga_horaria_semanal: 2
    });
    ids.materia = materia.data.id_materia;
    await exigir('Materia', 'editar', 'src/api/Materia/actualizar_materia.php', {
        id_materia: ids.materia, nombre: `${token} Materia editada`, descripcion: 'Registro temporal editado', carga_horaria_semanal: 3
    });

    const aula = await exigir('Aula', 'crear', 'src/api/Aula/crear_aula.php', {
        nombre: `${token} Aula`, codigo: token, ubicacion: 'Prueba funcional'
    });
    ids.aula = aula.data.id_aula;
    await exigir('Aula', 'editar', 'src/api/Aula/actualizar_aula.php', {
        id_aula: ids.aula, nombre: `${token} Aula editada`, codigo: token, ubicacion: 'Prueba funcional editada'
    });

    const grado = await exigir('Grado', 'crear', 'src/api/Grado/crear_grado.php', {
        nombre: `${token} Grado`, id_aula: ids.aula
    });
    ids.grado = grado.data.id_grado;
    await exigir('Grado', 'editar', 'src/api/Grado/actualizar_grado.php', {
        id_grado: ids.grado, nombre: `${token} Grado editado`, id_aula: ids.aula
    });

    const estudiante = await exigir('Estudiante', 'crear', 'src/api/Estudiante/crear_estudiante.php', {
        nombre: 'Auditoria', apellido: 'Estudiante', cedula_identidad: `${token}E`, id_grado: ids.grado
    });
    ids.estudiante = estudiante.data.id_estudiante;
    await exigir('Estudiante', 'editar', 'src/api/Estudiante/actualizar_estudiante.php', {
        id_estudiante: ids.estudiante, nombre: 'Auditoria Editada', apellido: 'Estudiante', cedula_identidad: `${token}E`, id_grado: ids.grado
    });

    const usuario = await exigir('Usuario', 'crear', 'src/api/Usuario/crear_usuario.php', {
        nombre: 'Auditoria', apellido: 'Usuario', usuario: token.toLowerCase(),
        email: `${token.toLowerCase()}@example.test`, celular: `09${Date.now().toString().slice(-8)}`,
        password: 'Temporal123', rol: 'Administracion'
    });
    ids.usuario = usuario.data.id_usuario;
    await exigir('Usuario', 'editar', 'src/api/Usuario/actualizar_usuario.php', {
        id_usuario: ids.usuario, nombre: 'Auditoria Editada', apellido: 'Usuario',
        usuario: token.toLowerCase(), email: `${token.toLowerCase()}@example.test`,
        celular: `09${Date.now().toString().slice(-8)}`, rol: 'Coordinador', password: ''
    });

    const asignacion = await exigir('Asignacion', 'crear', 'src/api/Asignaciones/crear_asignacion.php', {
        id_profesor: ids.profesor, id_materia: ids.materia, id_grado: ids.grado,
        carga_horaria: 4, anio_lectivo: 2026, activo: 1
    });
    ids.asignacion = asignacion.data.id_asignacion;
    await exigir('Asignacion', 'editar', 'src/api/Asignaciones/actualizar_asignacion.php', {
        id_asignacion: ids.asignacion, id_profesor: ids.profesor, id_materia: ids.materia,
        id_grado: ids.grado, carga_horaria: 5, anio_lectivo: 2027, activo: 1
    });
    await exigir('Asignacion', 'duplicado claro', 'src/api/Asignaciones/crear_asignacion.php', {
        id_profesor: ids.profesor, id_materia: ids.materia, id_grado: ids.grado,
        carga_horaria: 5, anio_lectivo: 2027, activo: 1
    }, 409);

    const horario = await exigir('Horario', 'crear', 'src/api/Horario/crear_horario.php', {
        id_asignacion: ids.asignacion, id_grado: ids.grado,
        dia_semana: 'Sábado', hora_inicio: '22:00', hora_fin: '22:40'
    });
    ids.horario = horario.data.id_horario;
    await exigir('Horario', 'editar', 'src/api/Horario/actualizar_horario.php', {
        id_horario: ids.horario, id_asignacion: ids.asignacion, id_grado: ids.grado,
        dia_semana: 'Sábado', hora_inicio: '22:10', hora_fin: '22:50'
    });
    await exigir('Horario', 'choque claro', 'src/api/Horario/crear_horario.php', {
        id_asignacion: ids.asignacion, id_grado: ids.grado,
        dia_semana: 'Sábado', hora_inicio: '22:20', hora_fin: '22:30'
    }, 409);

    await cicloEstado('Horario', 'Horario', 'id_horario', ids.horario);
    await exigir('Horario', 'eliminar', 'src/api/Horario/eliminar_horario.php', { id_horario: ids.horario });
    delete ids.horario;

    await cicloEstado('Asignacion', 'Asignaciones', 'id_asignacion', ids.asignacion);
    await exigir('Asignacion', 'eliminar', 'src/api/Asignaciones/eliminar_asignacion.php', { id_asignacion: ids.asignacion });
    delete ids.asignacion;

    for (const [modulo, carpeta, nombreId, clave] of [
        ['Estudiante', 'Estudiante', 'id_estudiante', 'estudiante'],
        ['Grado', 'Grado', 'id_grado', 'grado'],
        ['Aula', 'Aula', 'id_aula', 'aula'],
        ['Profesor', 'Profesor', 'id_profesor', 'profesor'],
        ['Materia', 'Materia', 'id_materia', 'materia'],
        ['Usuario', 'Usuario', 'id_usuario', 'usuario']
    ]) {
        await cicloEstado(modulo, carpeta, nombreId, ids[clave]);
        await exigir(modulo, 'eliminar', `src/api/${carpeta}/eliminar_${modulo.toLowerCase()}.php`, { [nombreId]: ids[clave] });
        delete ids[clave];
    }
}

(async () => {
    let errorPrincipal = null;
    try {
        await ejecutar();
    } catch (error) {
        errorPrincipal = error;
        console.error(`ERROR PRINCIPAL: ${error.message}`);
    } finally {
        await limpiar();
    }

    const fallas = resultados.filter((resultado) => !resultado.ok);
    console.log(`RESUMEN | ${resultados.length - fallas.length} OK | ${fallas.length} FALLAS | token=${token}`);
    process.exitCode = errorPrincipal || fallas.length ? 1 : 0;
})();
