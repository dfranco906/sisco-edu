<?php
declare(strict_types=1);
require_once __DIR__.'/../support/TestEnvironment.php';
$env=new PlanImportTestEnvironment();
try {
    $pdf=glob(dirname(__DIR__,3).'/test/fixtures/planes/*.pdf')[0];
    $r=$env->request('importar_plan_pdf.php','POST',['id_asignacion'=>$env->assignment,'anio'=>2026,'pdf'=>new CURLFile($pdf,'application/pdf',basename($pdf))]);
    $data=$r['json']['data']; $plan=$data['plan']; $token=$data['token'];
    $plan['unidades'][0]['capacidades'][0]['temas'][0]['indicadores'][0]['descripcion']='Indicador corregido <script>alert(1)</script>';
    $r=$env->request('preview_importacion.php','PUT',json_encode(['token'=>$token,'plan'=>$plan]));
    if ($r['status']!==200) throw new RuntimeException(json_encode($r));
    $r=$env->request('preview_importacion.php?token='.$token);
    if ($r['json']['data']['plan']['unidades']!==$plan['unidades']) throw new RuntimeException('Edición no conservada');
    $suggestions=$r['json']['data']['suggestions'];
    $decisions=array_map(static fn(array $s): array=>['kind'=>$s['kind'],'text'=>$s['text'],'action'=>'create'],$suggestions);
    $r=$env->request('preview_importacion.php','PUT',json_encode(['token'=>$token,'plan'=>$plan,'decisions'=>$decisions]));
    if($r['status']!==200)throw new RuntimeException('Decisiones no guardadas');
    $r=$env->request('preview_importacion.php?token='.$token);
    if($r['json']['data']['decisions']!==$decisions)throw new RuntimeException('Decisiones no recuperadas');
    foreach(['procedimientos_evaluativos','instrumentos_evaluativos'] as $table)if((int)$env->db->query('SELECT COUNT(*) FROM '.$table)->fetchColumn()!==0)throw new RuntimeException('Preview creó catálogos');
    foreach (['extra','order','parent','source'] as $mutation) {
        $bad=$plan;
        if($mutation==='extra')$bad['id_plan']=123;
        if($mutation==='order')$bad['unidades'][0]['orden']=2;
        if($mutation==='parent')$bad['unidades'][0]['capacidades']=[];
        if($mutation==='source')$bad['source']['filename']='otro.pdf';
        $r=$env->request('preview_importacion.php','PUT',json_encode(['token'=>$token,'plan'=>$bad]));
        if($r['status']!==422)throw new RuntimeException('Aceptó '.$mutation);
    }
    $r=$env->request('preview_importacion.php','PUT','{');
    if($r['status']!==400)throw new RuntimeException('JSON inválido');
    if((int)$env->db->query('SELECT COUNT(*) FROM planes_anuales')->fetchColumn()!==0)throw new RuntimeException('Preview escribió plan');
    $r=$env->request('cancelar_importacion.php','DELETE',json_encode(['token'=>$token]),false);
    if($r['status']!==403)throw new RuntimeException('Cancelar sin CSRF');
    $r=$env->request('cancelar_importacion.php','DELETE',json_encode(['token'=>$token]));
    if($r['status']!==200)throw new RuntimeException('Cancelar falló');
    if($env->request('preview_importacion.php?token='.$token)['status']!==404)throw new RuntimeException('Token cancelado todavía disponible');
    echo "OK | PreviewTest (HTTP round trip, HTML como texto, schema, orden, padres, origen, JSON inválido)\n";
} finally {$env->close();}
