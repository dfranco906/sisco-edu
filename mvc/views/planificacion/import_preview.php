<?php
require_once __DIR__ . '/../../../src/api/Planificacion/import_common.php';
$planImportCsrf = importCsrfToken();
$token = trim((string)($_GET['token'] ?? ''));
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';
require_once __DIR__ . '/../../../src/config/app.php';
?>
<main class="app-main flex-1 p-4 sm:p-6 lg:p-10" data-plan-import-preview>
  <header class="app-page-header p-5 sm:p-6 mb-6">
    <h2 class="text-2xl sm:text-3xl font-bold">Preview de importaci&oacute;n</h2>
    <p class="mt-1">Revise y corrija el plan antes de confirmar la importaci&oacute;n.</p>
  </header>
  <?php if (!preg_match('/^[a-f0-9]{64}$/', $token)): ?>
    <section class="app-panel p-5 sm:p-6"><p class="app-message-error">El token de importaci&oacute;n no es v&aacute;lido.</p><a class="btn btn-primary mt-4" href="<?= base_url('mvc/views/planificacion/index.php') ?>">Volver a Planificaci&oacute;n</a></section>
  <?php else: ?>
    <section class="app-panel p-5 sm:p-6" id="plan-import-preview-root"><p>Cargando preview...</p></section>
  <?php endif; ?>
</main>
<script>window.PLAN_IMPORT_PREVIEW_CONFIG=<?= json_encode(['token'=>$token,'apiPreview'=>base_url('src/api/Planificacion/preview_importacion.php'),'apiCancel'=>base_url('src/api/Planificacion/cancelar_importacion.php'),'apiConfirm'=>base_url('src/api/Planificacion/confirmar_importacion.php'),'planning'=>base_url('mvc/views/planificacion/index.php'),'editor'=>base_url('mvc/views/planificacion/editor.php'),'csrf'=>$planImportCsrf],JSON_UNESCAPED_SLASHES) ?>;</script>
<?php if (preg_match('/^[a-f0-9]{64}$/', $token)): ?><script src="<?= base_url('public/js/plan-import-preview.js') ?>?v=<?= urlencode((string)@filemtime(__DIR__.'/../../../public/js/plan-import-preview.js')) ?>"></script><?php endif; ?>
<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
