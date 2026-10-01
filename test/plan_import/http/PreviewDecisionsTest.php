<?php
declare(strict_types=1);
require_once __DIR__.'/../support/TestEnvironment.php';
$env=new PlanImportTestEnvironment();
try{
    $pdf=glob(dirname(__DIR__,3).'/test/fixtures/planes/*tercer curso*Administracion financiera.pdf')[0];
    $upload=$env->request('importar_plan_pdf.php','POST',['id_asignacion'=>$env->assignment,'anio'=>2026,'pdf'=>new CURLFile($pdf,'application/pdf',basename($pdf))]);
    $data=$upload['json']['data'];$token=$data['token'];$plan=$data['plan'];
    if(!isset($data['readiness']['can_confirm'])||$data['readiness']['can_confirm']!==false)throw new RuntimeException('Decisiones pendientes no bloquean');
    $decisions=array_map(static fn(array $s):array=>['kind'=>$s['kind'],'text'=>$s['text'],'action'=>'exclude','id'=>null],$data['suggestions']);
    $programming=[['unidad_orden'=>1,'capacidad_orden'=>1,'tema_orden'=>1,'fecha_inicio'=>'2026-03-01','fecha_fin'=>'2026-03-31','horas_catedra_planificadas'=>4,'observaciones'=>'Confirmada por profesor']];
    $saved=$env->request('preview_importacion.php','PUT',json_encode(['token'=>$token,'plan'=>$plan,'decisions'=>$decisions,'programming'=>$programming]));
    if($saved['status']!==200||$saved['json']['data']['programming']!==$programming)throw new RuntimeException('Decisiones/programaciÃ³n no persistieron: '.json_encode($saved));
    if($saved['json']['data']['readiness']['can_confirm']!==true)throw new RuntimeException('Preview resuelto sigue bloqueado');
    $bad=$programming;$bad[0]['fecha_inicio']='2025-01-01';
    if($env->request('preview_importacion.php','PUT',json_encode(['token'=>$token,'plan'=>$plan,'decisions'=>$decisions,'programming'=>$bad]))['status']!==422)throw new RuntimeException('Fecha inventada/fuera del aÃ±o aceptada');
    $plan['unidades'][0]['capacidades'][0]['temas'][0]['indicadores'][0]['check']=true;
    $check=$env->request('preview_importacion.php','PUT',json_encode(['token'=>$token,'plan'=>$plan,'decisions'=>$decisions,'programming'=>$programming]));
    $codes=array_column($check['json']['data']['readiness']['issues'],'code');
    if($check['status']!==200||!in_array('CHECK_IS_PROGRESS_NOT_PLAN_DEFINITION',$codes,true)||$check['json']['data']['readiness']['can_confirm']!==false)throw new RuntimeException('CHECK no bloquea confirmaciÃ³n');
    foreach(['planes_anuales','procedimientos_evaluativos','instrumentos_evaluativos'] as $table)if((int)$env->db->query('SELECT COUNT(*) FROM '.$table)->fetchColumn()!==0)throw new RuntimeException('Preview escribiÃ³ '.$table);
    echo "OK | PreviewDecisionsTest (confidence/warnings, catÃ¡logos explÃ­citos, fechas confirmadas, CHECK bloqueante; cero escrituras acadÃ©micas)\n";
}finally{$env->close();}
