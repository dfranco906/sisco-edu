<?php
declare(strict_types=1);
require_once __DIR__.'/../support/TestEnvironment.php';
require_once __DIR__.'/../support/PdfFixture.php';
$env = new PlanImportTestEnvironment();
try {
    $pdf = glob(dirname(__DIR__,3).'/test/fixtures/planes/*.pdf')[0];
    $body = ['id_asignacion'=>$env->assignment,'anio'=>2026,'pdf'=>new CURLFile($pdf,'application/pdf',basename($pdf))];
    $r = $env->request('importar_plan_pdf.php','POST',$body);
    if ($r['status']!==201 || !isset($r['json']['data']['token'])) throw new RuntimeException('Upload válido falló: '.json_encode($r));
    foreach ([[false,true,403],[true,false,401]] as [$csrf,$auth,$expected]) {
        $r=$env->request('importar_plan_pdf.php','POST',$body,$csrf,$auth);
        if ($r['status']!==$expected) throw new RuntimeException('CSRF/sesión no rechazados');
    }
    $body['id_asignacion']=$env->otherAssignment;
    if ($env->request('importar_plan_pdf.php','POST',$body)['status']!==403) throw new RuntimeException('Ownership');
    $body['id_asignacion']=$env->assignment;
    $body['pdf']=new CURLFile($pdf,'application/pdf','plan.php');
    if ($env->request('importar_plan_pdf.php','POST',$body)['json']['error_type']!=='INVALID_EXTENSION') throw new RuntimeException('Extensión');
    $invalid=$env->directory.'/invalid.pdf'; file_put_contents($invalid,'No es un PDF');
    $body['pdf']=new CURLFile($invalid,'application/pdf','plan.pdf');
    if ($env->request('importar_plan_pdf.php','POST',$body)['json']['error_type']!=='INVALID_PDF_SIGNATURE') throw new RuntimeException('Firma');
    file_put_contents($invalid,'');
    if ($env->request('importar_plan_pdf.php','POST',$body)['json']['error_type']!=='INVALID_FILE_SIZE') throw new RuntimeException('Vacío');
    foreach ([
        ['%PDF-broken','PDF_EXTRACTION_FAILED'],
        [planImportSyntheticPdf(''),'PDF_SCAN_NOT_SUPPORTED'],
        [planImportSyntheticPdf('Este documento digital contiene texto suficiente pero no es un plan anual de ninguno de los formatos compatibles.'),'UNKNOWN_FORMAT'],
        ['%PDF-'.str_repeat('x',10*1024*1024),'INVALID_FILE_SIZE'],
    ] as [$contents,$expected]) {
        file_put_contents($invalid,$contents);
        $r=$env->request('importar_plan_pdf.php','POST',$body);
        if(($r['json']['error_type']??null)!==$expected)throw new RuntimeException('Esperado '.$expected.': '.json_encode($r));
    }
    $body['pdf']=new CURLFile($pdf,'application/pdf','plan.pdf');
    $configPath=$env->directory.'/src/config/plan_import.php';
    $originalConfig=file_get_contents($configPath);
    file_put_contents($configPath,str_replace("'timeout_seconds' => 15","'timeout_seconds' => 0.000001",$originalConfig));
    $r=$env->request('importar_plan_pdf.php','POST',$body);
    if(($r['json']['error_type']??null)!=='PARSER_TIMEOUT')throw new RuntimeException('Timeout: '.json_encode($r));
    file_put_contents($configPath,$originalConfig);
    $folders=glob($env->directory.'-storage/*',GLOB_ONLYDIR);
    if(count($folders)!==1)throw new RuntimeException('Uploads inválidos dejaron temporales');
    session_id($env->session);session_start();$_SESSION['rol']='SinPermiso';session_write_close();
    if($env->request('importar_plan_pdf.php','POST',$body)['status']!==403)throw new RuntimeException('Rol no rechazado');
    if ((int)$env->db->query('SELECT COUNT(*) FROM planes_anuales')->fetchColumn()!==0) throw new RuntimeException('Upload escribió plan');
    echo "OK | UploadTest (HTTP real, preview, CSRF, sesión, rol, ownership, extensión, firma, vacío, corrupto, sin texto, UNKNOWN, tamaño, timeout; cero planes y sin residuos inválidos)\n";
} finally { $env->close(); }
