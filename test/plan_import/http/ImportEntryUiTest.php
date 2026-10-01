<?php
declare(strict_types=1);
require_once __DIR__.'/../support/TestEnvironment.php';

function importEntryGet(PlanImportTestEnvironment $env, string $path): array
{
    $curl=curl_init($env->url.$path);
    curl_setopt_array($curl,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_COOKIE=>'PHPSESSID='.$env->session,CURLOPT_TIMEOUT=>15]);
    $body=(string)curl_exec($curl);
    $status=(int)curl_getinfo($curl,CURLINFO_RESPONSE_CODE);
    curl_close($curl);
    return [$status,$body];
}

$env=new PlanImportTestEnvironment();
try {
    [$status,$html]=importEntryGet($env,'/mvc/views/planificacion/index.php');
    if($status!==200)throw new RuntimeException('PlanificaciÃ³n HTTP '.$status);
    foreach(['data-open-plan-import','id="form-importar-plan"','name="id_asignacion"','name="anio"','name="pdf"','accept="application/pdf,.pdf"','Analizar plan','importar_plan_pdf.php','plan-import.js'] as $needle){
        if(!str_contains($html,$needle))throw new RuntimeException('Falta entrada UI: '.$needle);
    }
    if(!preg_match('/"csrf":"([a-f0-9]{64})"/',$html,$match) || !hash_equals($env->csrf,$match[1]))throw new RuntimeException('CSRF UI ausente o incorrecto');

    [$status,$preview]=importEntryGet($env,'/mvc/views/planificacion/import_preview.php?token='.str_repeat('a',64));
    if($status!==200 || !str_contains($preview,'data-plan-import-preview') || !str_contains($preview,'preview_importacion.php') || !str_contains($preview,'plan-import-preview.js'))throw new RuntimeException('NavegaciÃ³n al preview no disponible');
    $previewJs=file_get_contents($env->directory.'/public/js/plan-import-preview.js');
    foreach(['Datos generales','Estructura del plan','Expandir todo','Contraer todo','Guardar correcciones','Cancelar importaci','Confirmar e importar','confirmationSummary','confirming','procedimientos_evaluativos','instrumentos_evaluativos','tiempo_texto','proceso_texto','fecha_texto'] as $needle){
        if(!str_contains($previewJs,$needle))throw new RuntimeException('Preview editable incompleto: '.$needle);
    }
    if(str_contains($previewJs,'.innerHTML'))throw new RuntimeException('El preview usa innerHTML con contenido extraÃ­do');
    [$status,$invalid]=importEntryGet($env,'/mvc/views/planificacion/import_preview.php?token=../ajeno');
    if($status!==200 || !str_contains($invalid,'token de importaci'))throw new RuntimeException('Token invÃ¡lido no fue rechazado en vista');

    $count=(int)$env->db->query('SELECT COUNT(*) FROM planes_anuales')->fetchColumn();
    if($count!==0)throw new RuntimeException('La entrada UI escribiÃ³ planes acadÃ©micos');
    echo "OK | ImportEntryUiTest (acciÃ³n, formulario, asignaciÃ³n RBAC por API, aÃ±o, PDF, CSRF, navegaciÃ³n por token; cero planes)\n";
} finally { $env->close(); }
