<?php
declare(strict_types=1);
require_once __DIR__.'/../support/TestEnvironment.php';
$env=new PlanImportTestEnvironment();
try{
    $pdf=glob(dirname(__DIR__,3).'/test/fixtures/planes/*tercer curso*Administracion financiera.pdf')[0];$uploadBody=['id_asignacion'=>$env->assignment,'anio'=>2026,'pdf'=>new CURLFile($pdf,'application/pdf',basename($pdf))];
    $upload=$env->request('importar_plan_pdf.php','POST',$uploadBody);$data=$upload['json']['data'];$token=$data['token'];
    $blocked=$env->request('confirmar_importacion.php','POST',json_encode(['token'=>$token]));if($blocked['status']!==409||($blocked['json']['error_type']??'')!=='CATALOG_DECISION_REQUIRED')throw new RuntimeException('ConfirmaciÃ³n incompleta no bloqueada: '.json_encode($blocked));
    $decisions=array_map(static fn(array $s):array=>['kind'=>$s['kind'],'text'=>$s['text'],'action'=>'create','id'=>null],$data['suggestions']);$data['plan']['metadata']['institucion']='InstituciÃ³n corregida E2E';
    $saved=$env->request('preview_importacion.php','PUT',json_encode(['token'=>$token,'plan'=>$data['plan'],'decisions'=>$decisions,'programming'=>[]]));if($saved['status']!==200||!$saved['json']['data']['readiness']['can_confirm'])throw new RuntimeException('Preview resuelto invÃ¡lido');
    $withoutCsrf=$env->request('confirmar_importacion.php','POST',json_encode(['token'=>$token]),false);if($withoutCsrf['status']!==403)throw new RuntimeException('Confirmar sin CSRF');
    $first=$env->request('confirmar_importacion.php','POST',json_encode(['token'=>$token]));if($first['status']!==201||!$first['json']['data']['created'])throw new RuntimeException('ConfirmaciÃ³n fallÃ³: '.json_encode($first));$id=(int)$first['json']['data']['id_plan'];
    $second=$env->request('confirmar_importacion.php','POST',json_encode(['token'=>$token]));if($second['status']!==200||$second['json']['data']['created']!==false||(int)$second['json']['data']['id_plan']!==$id)throw new RuntimeException('ConfirmaciÃ³n no idempotente');
    if((int)$env->db->query('SELECT COUNT(*) FROM planes_anuales')->fetchColumn()!==1)throw new RuntimeException('Doble plan');
    if($env->request('preview_importacion.php?token='.$token)['status']!==409)throw new RuntimeException('Token consumido permite preview');
    $row=$env->db->query('SELECT estado,institucion_fuente FROM planes_anuales WHERE id_plan='.$id)->fetch(PDO::FETCH_ASSOC);if($row!==['estado'=>'BORRADOR','institucion_fuente'=>'InstituciÃ³n corregida E2E'])throw new RuntimeException('Plan confirmado incorrecto');
    $duplicate=$env->request('importar_plan_pdf.php','POST',$uploadBody);$duplicateToken=$duplicate['json']['data']['token'];$duplicateConfirm=$env->request('confirmar_importacion.php','POST',json_encode(['token'=>$duplicateToken]));if($duplicateConfirm['status']!==409||!in_array($duplicateConfirm['json']['error_type'],['PLAN_ALREADY_EXISTS','CATALOG_DECISION_REQUIRED'],true))throw new RuntimeException('Plan existente no bloqueado: '.json_encode($duplicateConfirm));
    echo "OK | ConfirmImportTest (CSRF, readiness, transacciÃ³n, BORRADOR, token consumido, doble confirmaciÃ³n idempotente, duplicado bloqueado)\n";
}finally{$env->close();}
