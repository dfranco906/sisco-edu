<?php
require_once __DIR__ . '/../../../src/api/Planificacion/import_common.php';
$planImportCsrf = importCsrfToken();
require_once __DIR__ . '/../layouts/header.php';
?>
<?php require_once __DIR__ . '/../layouts/sidebar.php'; ?>
<?php require_once __DIR__ . '/../../../src/config/app.php'; ?>
<main class="app-main flex-1 p-4 sm:p-6 lg:p-10" data-planificacion-lista>
  <header class="app-page-header p-5 sm:p-6 mb-6">
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
      <div><h2 class="text-2xl sm:text-3xl font-bold">Planificaci&oacute;n pedag&oacute;gica</h2><p class="mt-1">Planes anuales por asignaci&oacute;n, organizados en unidades, capacidades, temas e indicadores.</p></div>
      <div class="flex flex-wrap gap-2">
        <button class="btn btn-success" type="button" data-open-plan-import>Importar Plan Anual</button>
        <a class="btn btn-primary" href="<?= base_url('mvc/views/dashboard.php') ?>">Volver</a>
      </div>
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
<div class="app-modal hidden" id="plan-import-modal" role="dialog" aria-modal="true" aria-labelledby="plan-import-title">
  <div class="app-modal-dialog">
    <div class="flex items-start justify-between gap-4 mb-4">
      <div>
        <h3 id="plan-import-title" class="text-xl font-bold">Importar Plan Anual desde PDF</h3>
        <p class="text-sm mt-1" style="color:var(--color-muted)">El archivo se analizar&aacute; primero. No se guardar&aacute;n datos acad&eacute;micos hasta que revise y confirme el preview.</p>
      </div>
      <button type="button" class="btn btn-secondary" data-close-plan-import aria-label="Cerrar">Cerrar</button>
    </div>
    <form id="form-importar-plan" class="app-form-grid" enctype="multipart/form-data" novalidate>
      <label class="font-semibold sm:col-span-2">Asignaci&oacute;n
        <select name="id_asignacion" id="plan-import-asignacion" class="app-input mt-2 w-full" required><option value="">Cargando...</option></select>
      </label>
      <label class="font-semibold">A&ntilde;o
        <input name="anio" id="plan-import-anio" class="app-input mt-2 w-full" type="number" min="2000" max="2100" value="<?= (int)date('Y') ?>" required>
      </label>
      <label class="font-semibold">Archivo PDF
        <input name="pdf" id="plan-import-pdf" class="app-input mt-2 w-full" type="file" accept="application/pdf,.pdf" required>
      </label>
      <p class="sm:col-span-2 text-sm" style="color:var(--color-muted)">PDF digital, hasta 10 MB y 100 p&aacute;ginas. El contenido no se interpreta como HTML.</p>
      <p id="plan-import-message" class="sm:col-span-2" aria-live="polite"></p>
      <div class="sm:col-span-2 flex justify-end gap-2">
        <button type="button" class="btn btn-secondary" data-close-plan-import>Cancelar</button>
        <button type="submit" class="btn btn-success" id="plan-import-submit">Analizar plan</button>
      </div>
    </form>
  </div>
</div>
<script>window.PLANIFICACION_CONFIG=<?= json_encode(['apiPlanes'=>base_url('src/api/Planificacion/planes.php'),'apiAsignaciones'=>base_url('src/api/Planificacion/asignaciones.php'),'editor'=>base_url('mvc/views/planificacion/editor.php')],JSON_UNESCAPED_SLASHES) ?>;</script>
<script>window.PLAN_IMPORT_CONFIG=<?= json_encode(['apiUpload'=>base_url('src/api/Planificacion/importar_plan_pdf.php'),'apiAssignments'=>base_url('src/api/Planificacion/asignaciones.php'),'preview'=>base_url('mvc/views/planificacion/import_preview.php'),'csrf'=>$planImportCsrf],JSON_UNESCAPED_SLASHES) ?>;</script>
<script src="<?= base_url('public/js/planificacion.js') ?>?v=<?= urlencode((string)@filemtime(__DIR__.'/../../../public/js/planificacion.js')) ?>"></script>
<script src="<?= base_url('public/js/plan-import.js') ?>?v=<?= urlencode((string)@filemtime(__DIR__.'/../../../public/js/plan-import.js')) ?>"></script>
<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
