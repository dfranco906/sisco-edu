<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<?php require_once __DIR__ . '/../layouts/sidebar.php'; ?>
<?php require_once __DIR__ . '/../../../src/config/app.php'; ?>
<main class="app-main flex-1 p-4 sm:p-6 lg:p-10" data-planificacion-lista>
  <header class="app-page-header p-5 sm:p-6 mb-6">
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
      <div><h2 class="text-2xl sm:text-3xl font-bold">Planificaci&oacute;n pedag&oacute;gica</h2><p class="mt-1">Planes anuales por asignaci&oacute;n, organizados en unidades, capacidades, temas e indicadores.</p></div>
      <a class="btn btn-primary" href="<?= base_url('mvc/views/dashboard.php') ?>">Volver</a>
    </div>
  </header>
  <section class="app-panel p-5 sm:p-6 mb-6">
    <h3 class="text-xl font-bold mb-1">Crear plan anual</h3>
    <p class="text-sm mb-4" style="color:var(--color-muted)">Profesor, materia, grado/curso y a&ntilde;o se derivan de la asignaci&oacute;n.</p>
    <form id="form-crear-plan" class="app-form-grid">
      <label class="font-semibold sm:col-span-2">Asignaci&oacute;n<select name="id_asignacion" id="plan-asignacion" class="app-input mt-2 w-full" required><option value="">Cargando...</option></select></label>
      <label class="font-semibold">A&ntilde;o<input name="anio" id="plan-anio" class="app-input mt-2 w-full" type="number" min="2000" max="2100" required></label>
      <div id="plan-asignacion-resumen" class="plan-assignment-summary">Seleccione una asignaci&oacute;n.</div>
      <label class="font-semibold sm:col-span-2">Competencia general <span class="app-optional">opcional</span><textarea name="competencia_general" class="app-input mt-2 w-full" rows="2"></textarea></label>
      <label class="font-semibold sm:col-span-2">Competencia espec&iacute;fica <span class="app-optional">opcional</span><textarea name="competencia_especifica" class="app-input mt-2 w-full" rows="2"></textarea></label>
      <div class="sm:col-span-2 flex justify-end"><button class="btn btn-success" type="submit">Crear y abrir plan</button></div>
    </form>
    <p id="mensaje-plan-lista" class="mt-3" aria-live="polite"></p>
  </section>
  <section class="app-panel p-5 sm:p-6">
    <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4 mb-4"><div><h3 class="text-xl font-bold">Planes existentes</h3><p class="text-sm" style="color:var(--color-muted)">Solo un plan por asignaci&oacute;n y a&ntilde;o.</p></div><div class="app-filters"><select id="filtro-plan-estado" class="app-input"><option value="">Todos los estados</option><option>BORRADOR</option><option>PUBLICADO</option><option>ARCHIVADO</option></select><input id="filtro-plan-buscar" class="app-input" type="search" placeholder="Buscar materia, grado o profesor"></div></div>
    <div id="planes-lista" class="plan-list-grid"><p>Cargando planes...</p></div>
  </section>
</main>
<script>window.PLANIFICACION_CONFIG=<?= json_encode(['apiPlanes'=>base_url('src/api/Planificacion/planes.php'),'apiAsignaciones'=>base_url('src/api/Planificacion/asignaciones.php'),'editor'=>base_url('mvc/views/planificacion/editor.php')],JSON_UNESCAPED_SLASHES) ?>;</script>
<script src="<?= base_url('public/js/planificacion.js') ?>?v=<?= urlencode((string)@filemtime(__DIR__.'/../../../public/js/planificacion.js')) ?>"></script>
<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
