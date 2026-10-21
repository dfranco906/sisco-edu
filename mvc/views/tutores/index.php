<?php
$titulo = "Tutores y Familias";
$api = "src/api/Tutor/leer_tutores.php";

$columnas = [
    "id_tutor",
    "nombre",
    "apellido",
    "cedula_identidad",
    "celular",
    "email",
    "total_hijos",
    "activo"
];

$filtros = [
    "buscar" => ["nombre", "apellido", "cedula_identidad", "celular", "email"],
    "placeholder" => "Buscar tutor por nombre, apellido, CI o celular..."
];

$formCrear = [
    "api" => "src/api/Tutor/crear_tutor.php",
    "campos" => [
        ["name" => "nombre", "label" => "Nombre", "type" => "text"],
        ["name" => "apellido", "label" => "Apellido", "type" => "text"],
        ["name" => "cedula_identidad", "label" => "Cédula de Identidad (Usuario y contraseña de acceso)", "type" => "text"],
        ["name" => "celular", "label" => "Celular / WhatsApp", "type" => "number"],
        ["name" => "email", "label" => "Correo Electrónico", "type" => "email"],
        ["name" => "direccion", "label" => "Dirección Domiciliaria", "type" => "text"]
    ]
];

$apiActualizar = "src/api/Tutor/actualizar_tutor.php";
$apiDesactivar = "src/api/Tutor/desactivar_tutor.php";
$urlDesactivados = "mvc/views/tutores/desactivados.php";
$urlVolver = "mvc/views/administracion/index.php";
$idCampo = "id_tutor";
$camposEditar = ["nombre", "apellido", "cedula_identidad", "celular", "email", "direccion"];

$toolbarExtraHtml = '
<button id="btn-vincular-familia" type="button" class="btn btn-secondary inline-flex items-center gap-2">
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
    <span>+ Vincular Estudiante a Tutor</span>
</button>
';

$modalExtraHtml = '
<div id="modal-vincular-familia" class="app-modal hidden">
    <div class="app-modal-dialog max-w-lg">
        <div class="flex items-start justify-between gap-4 mb-5">
            <div>
                <h3 class="text-xl font-bold">Vincular Estudiante a Tutor</h3>
                <p class="text-sm mt-1" style="color: var(--color-muted);">Relacioná a un estudiante con su responsable familiar para el seguimiento académico.</p>
            </div>
            <button type="button" id="cerrar-modal-vincular" class="app-modal-close" aria-label="Cerrar">×</button>
        </div>

        <form id="form-vincular-familia" class="space-y-4">
            <div>
                <label class="block mb-1.5 font-semibold text-xs uppercase tracking-wider text-slate-700 dark:text-slate-300">Tutor Responsable</label>
                <select id="sel-tutor" name="id_tutor" class="app-input w-full" required>
                    <option value="">Cargando tutores...</option>
                </select>
            </div>

            <div>
                <label class="block mb-1.5 font-semibold text-xs uppercase tracking-wider text-slate-700 dark:text-slate-300">Estudiante a Cargo</label>
                <select id="sel-estudiante" name="id_estudiante" class="app-input w-full" required>
                    <option value="">Cargando estudiantes...</option>
                </select>
            </div>

            <div>
                <label class="block mb-1.5 font-semibold text-xs uppercase tracking-wider text-slate-700 dark:text-slate-300">Parentesco o Relación</label>
                <select name="parentesco" class="app-input w-full" required>
                    <option value="Madre">Madre</option>
                    <option value="Padre">Padre</option>
                    <option value="Tutor Legal" selected>Tutor Legal</option>
                    <option value="Encargado/a">Encargado/a</option>
                    <option value="Abuelo/a">Abuelo/a</option>
                    <option value="Tío/a">Tío/a</option>
                </select>
            </div>

            <div class="flex items-center gap-2 pt-1">
                <input type="checkbox" id="chk-principal" name="es_principal" value="1" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500" checked>
                <label for="chk-principal" class="text-sm text-slate-700 dark:text-slate-300 select-none">
                    Tutor principal de contacto y notificaciones
                </label>
            </div>

            <div class="app-modal-actions pt-4 border-t border-slate-200 dark:border-slate-800">
                <button type="button" id="cancelar-vincular" class="btn btn-muted">Cancelar</button>
                <button type="submit" class="btn btn-success">Guardar Vínculo</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const btnVincular = document.getElementById("btn-vincular-familia");
    const modalVincular = document.getElementById("modal-vincular-familia");
    const cerrarModal = document.getElementById("cerrar-modal-vincular");
    const cancelarModal = document.getElementById("cancelar-vincular");
    const formVincular = document.getElementById("form-vincular-familia");
    const selTutor = document.getElementById("sel-tutor");
    const selEstudiante = document.getElementById("sel-estudiante");

    async function cargarOpciones() {
        try {
            const resT = await fetch(window.BASE_URL.replace(/\/+$/, "") + "/src/api/Tutor/leer_tutores.php");
            const dataT = await resT.json();
            const tutores = dataT.data || [];
            selTutor.innerHTML = \'<option value="">Seleccione un tutor...</option>\' +
                tutores.map(t => `<option value="${t.id_tutor}">${t.nombre} ${t.apellido} (CI: ${t.cedula_identidad})</option>`).join("");

            const resE = await fetch(window.BASE_URL.replace(/\/+$/, "") + "/src/api/Estudiante/leer_estudiantes.php");
            const dataE = await resE.json();
            const estudiantes = dataE.data || [];
            selEstudiante.innerHTML = \'<option value="">Seleccione un estudiante...</option>\' +
                estudiantes.map(e => `<option value="${e.id_estudiante}">${e.nombre} ${e.apellido} - ${e.grado || "Sin Grado"} (CI: ${e.cedula_identidad})</option>`).join("");
        } catch (e) {
            console.error("Error cargando opciones de tutores y estudiantes:", e);
        }
    }

    if (btnVincular && modalVincular) {
        btnVincular.addEventListener("click", () => {
            modalVincular.classList.remove("hidden");
            document.body.classList.add("modal-open");
            cargarOpciones();
        });

        const cerrar = () => {
            modalVincular.classList.add("hidden");
            document.body.classList.remove("modal-open");
        };

        if (cerrarModal) cerrarModal.addEventListener("click", cerrar);
        if (cancelarModal) cancelarModal.addEventListener("click", cerrar);

        if (formVincular) {
            formVincular.addEventListener("submit", async (e) => {
                e.preventDefault();
                const formData = new FormData(formVincular);
                try {
                    const res = await fetch(window.BASE_URL.replace(/\/+$/, "") + "/src/api/Tutor/vincular_hijo.php", {
                        method: "POST",
                        body: formData
                    });
                    const resJson = await res.json();
                    if (resJson.status === "success") {
                        if (window.Toast) Toast.success(resJson.message);
                        cerrar();
                        formVincular.reset();
                        setTimeout(() => location.reload(), 600);
                    } else {
                        if (window.Toast) Toast.error(resJson.message || "Error al vincular");
                    }
                } catch (err) {
                    if (window.Toast) Toast.error("Error de comunicación con el servidor");
                }
            });
        }
    }
});
</script>
';

require_once __DIR__ . '/../partials/table_page.php';
