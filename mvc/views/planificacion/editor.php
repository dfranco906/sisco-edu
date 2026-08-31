<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<?php require_once __DIR__ . '/../layouts/sidebar.php'; ?>
<?php require_once __DIR__ . '/../../../src/config/app.php'; ?>
<?php $idPlan=filter_input(INPUT_GET,'id_plan',FILTER_VALIDATE_INT)?:0; ?>
<main class="app-main flex-1 p-4 sm:p-6 lg:p-10" data-planificacion-editor data-id-plan="<?= (int)$idPlan ?>">
  <header class="app-page-header p-5 sm:p-6 mb-6">
    <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4"><div><p class="app-eyebrow">PLAN ANUAL</p><h2 id="plan-editor-titulo" class="text-2xl sm:text-3xl font-bold">Cargando plan...</h2><p id="plan-editor-contexto" class="mt-1"></p></div><div class="app-toolbar-actions flex flex-wrap gap-2"><button id="btn-estado-plan" class="btn btn-success" type="button">Publicar</button><button id="btn-archivar-plan" class="btn btn-danger" type="button">Archivar</button><a class="btn btn-primary" href="<?= base_url('mvc/views/planificacion/index.php') ?>">Volver a planes</a></div></div>
  </header>
  <section class="plan-stats mb-6" id="plan-contadores"></section>
  <section class="app-panel p-5 sm:p-6 mb-6">
    <form id="form-datos-plan" class="app-form-grid">
      <label class="font-semibold sm:col-span-2">Competencia general<textarea name="competencia_general" class="app-input mt-2 w-full" rows="2"></textarea></label>
      <label class="font-semibold sm:col-span-2">Competencia espec&iacute;fica<textarea name="competencia_especifica" class="app-input mt-2 w-full" rows="2"></textarea></label>
      <label class="font-semibold sm:col-span-2">Observaciones<textarea name="observaciones" class="app-input mt-2 w-full" rows="2"></textarea></label>
      <div class="sm:col-span-2 flex justify-end"><button class="btn btn-primary" type="submit">Guardar datos generales</button></div>
    </form>
  </section>
  <div class="flex items-center justify-between gap-3 mb-4"><div><h3 class="text-xl font-bold">Estructura del plan</h3><p class="text-sm" style="color:var(--color-muted)">Expand&iacute; cada nivel para trabajar de forma progresiva.</p></div><button class="btn btn-success" data-action="agregar" data-tipo="unidad" type="button">+ Agregar unidad</button></div>
  <p id="mensaje-plan-editor" class="mb-4" aria-live="polite"></p>
  <section id="plan-arbol" class="plan-tree"><p>Cargando estructura...</p></section>
</main>
<div id="plan-modal" class="app-modal hidden" role="dialog" aria-modal="true"><div class="app-modal-dialog max-w-2xl"><div class="flex items-start justify-between gap-3 mb-4"><div><h3 id="plan-modal-titulo" class="text-xl font-bold"></h3><p id="plan-modal-ayuda" class="text-sm" style="color:var(--color-muted)"></p></div><button class="app-modal-close" type="button" data-close-plan-modal>&times;</button></div><form id="plan-modal-form"><div id="plan-modal-campos" class="app-form-grid"></div><div class="app-modal-actions mt-5"><button class="btn btn-muted" type="button" data-close-plan-modal>Cancelar</button><button class="btn btn-success" type="submit">Guardar</button></div></form></div></div>
<script>window.PLANIFICACION_CONFIG=<?= json_encode(['idPlan'=>$idPlan,'apiPlanes'=>base_url('src/api/Planificacion/planes.php'),'apiEstructura'=>base_url('src/api/Planificacion/estructura.php'),'apiCatalogos'=>base_url('src/api/Planificacion/catalogos.php'),'apiProgramacion'=>base_url('src/api/Planificacion/programacion.php')],JSON_UNESCAPED_SLASHES) ?>;</script>
<script src="<?= base_url('public/js/planificacion.js') ?>?v=<?= urlencode((string)@filemtime(__DIR__.'/../../../public/js/planificacion.js')) ?>"></script>
<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
