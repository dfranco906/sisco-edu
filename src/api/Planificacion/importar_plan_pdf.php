<?php
declare(strict_types=1);
require_once __DIR__.'/import_common.php';
use SiscoEdu\PlanImport\PlanImportacionService;
requerirMetodo(['POST']);
$user = usuarioActual(['SuperAdmin','Administracion','Coordinador','Profesor']);
try {
    importCsrfCheck();
    $assignment = enteroPositivo($_POST['id_asignacion'] ?? null, 'Asignación');
    $year = enteroPositivo($_POST['anio'] ?? null, 'Año');
    $config = require __DIR__.'/../../config/plan_import.php';
    $db = (new Database())->getConnection();
    $data = (new PlanImportacionService($db, $user, $config))->upload(is_array($_FILES['pdf'] ?? null) ? $_FILES['pdf'] : [], $assignment, $year);
    responderJson(['success'=>true, 'data'=>$data], 201);
} catch (Throwable $e) { importError($e); }
