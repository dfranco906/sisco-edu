<?php
declare(strict_types=1);
require_once __DIR__.'/../../config/api_auth.php';
require_once __DIR__.'/../../classes/PlanificacionPedagogica.php';
require_once __DIR__.'/../../classes/PlanImport/bootstrap.php';
use SiscoEdu\PlanImport\PlanImportException;

function importCsrfToken(): string
{
    iniciarSesionSegura();
    if (!isset($_SESSION['plan_import_csrf'])) $_SESSION['plan_import_csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['plan_import_csrf'];
}
function importCsrfCheck(): void
{
    $given = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['csrf_token'] ?? '';
    if (!is_string($given) || !isset($_SESSION['plan_import_csrf']) || !hash_equals($_SESSION['plan_import_csrf'], $given)) throw new PlanImportException('La sesión del formulario venció. Recargue la página.', 'INVALID_CSRF', 6, 403);
}
function importJson(array $config): array
{
    $limit = (int)$config['limits']['max_preview_json_bytes'];
    $raw = file_get_contents('php://input', false, null, 0, $limit+1);
    if ($raw === false || strlen($raw)>$limit) throw new PlanImportException('El preview supera el tamaño permitido.', 'PREVIEW_TOO_LARGE', 6, 413);
    try { $data = json_decode($raw, true, 128, JSON_THROW_ON_ERROR); }
    catch (JsonException) { throw new PlanImportException('JSON inválido.', 'INVALID_JSON', 6, 400); }
    if (!is_array($data)) throw new PlanImportException('JSON inválido.', 'INVALID_JSON', 6, 400);
    return $data;
}
function importError(Throwable $error): never
{
    $type = $error instanceof PlanImportException ? $error->errorType : 'IMPORT_INTERNAL_ERROR';
    error_log('plan_import: '.$type); // Sin rutas, tokens ni contenido académico.
    responderJson(['success'=>false, 'error_type'=>$type, 'message'=>$error instanceof PlanImportException ? $error->getMessage() : 'No se pudo procesar la importación.'], $error instanceof PlanImportException ? $error->httpStatus : 500);
}
