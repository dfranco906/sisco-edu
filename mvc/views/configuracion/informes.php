<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<?php if(!in_array($_SESSION['rol']??'', ['SuperAdmin','Administracion'],true)){http_response_code(403);echo '<main class="p-8">Acceso denegado.</main>';require_once __DIR__.'/../layouts/footer.php';exit;} ?>
<?php require_once __DIR__ . '/../layouts/sidebar.php'; require_once __DIR__ . '/../../../src/config/app.php'; ?>
<main class="app-main flex-1 p-4 sm:p-6 lg:p-10" data-config-informes>
 <header class="app-page-header p-5 sm:p-6 mb-6"><h2 class="text-2xl sm:text-3xl font-bold">Configuraci&oacute;n de informes</h2><p class="mt-1">Membrete institucional usado por el informe diario y su versi&oacute;n impresa.</p></header>
 <section class="app-panel p-5 sm:p-6 max-w-3xl"><div id="membrete-actual" class="report-letterhead-preview mb-5">Cargando membrete...</div><form id="form-membrete" enctype="multipart/form-data"><label class="font-semibold">Imagen PNG o JPG/JPEG, m&aacute;ximo 2 MB<input name="membrete" class="app-input mt-2 w-full" type="file" accept="image/png,image/jpeg,.png,.jpg,.jpeg" required></label><div class="flex justify-end mt-4"><button class="btn btn-success" type="submit">Subir y activar membrete</button></div></form><p id="mensaje-config-informes" class="mt-3" aria-live="polite"></p></section>
</main>
<script>window.CONFIG_INFORMES=<?= json_encode(['api'=>base_url('src/api/Configuracion/configuracion_informes.php'),'baseUrl'=>base_url('')],JSON_UNESCAPED_SLASHES) ?>;</script>
<script src="<?= base_url('public/js/configuracion-informes.js') ?>?v=<?= urlencode((string)@filemtime(__DIR__.'/../../../public/js/configuracion-informes.js')) ?>"></script>
<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
