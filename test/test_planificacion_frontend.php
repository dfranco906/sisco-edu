<?php
declare(strict_types=1);
require_once __DIR__.'/plan_import/support/TestEnvironment.php';
require_once dirname(__DIR__).'/src/classes/PlanImport/bootstrap.php';
require_once dirname(__DIR__).'/src/config/app.php';
use SiscoEdu\PlanImport\PlanImportPersistence;
$env=new PlanImportTestEnvironment();
try{
    $fixture=__DIR__.'/fixtures/planes/Plan Anual - tercer curso - BTI - Administracion financiera.pdf';
    $upload=$env->request('importar_plan_pdf.php','POST',['id_asignacion'=>$env->assignment,'anio'=>2026,'pdf'=>new CURLFile($fixture,'application/pdf')]);
    if($upload['status']!==201)throw new RuntimeException('Upload frontend temporal falló.');$data=$upload['json']['data'];$canonical=$data['plan'];
    $canonical['unidades'][0]['capacidades'][0]['temas'][0]['contenido']='Contenido <seguro> del tema temporal';
    $decisions=array_map(fn($s)=>['kind'=>$s['kind'],'text'=>$s['text'],'action'=>'create','id'=>null],$data['suggestions']);
    $env->db->beginTransaction();$plan=(new PlanImportPersistence($env->db))->persist($canonical,$env->assignment,2026,$env->user,$decisions,[]);$env->db->commit();
    $base=$env->db->query('SELECT ad.id_profesor,ad.id_grado,g.id_aula,p.cedula_identidad,p.user_id_global FROM asignacion_docente ad JOIN grados g USING(id_grado) JOIN profesores p USING(id_profesor) WHERE ad.id_asignacion='.$env->assignment)->fetch(PDO::FETCH_ASSOC);
    $env->insert("INSERT INTO horarios (id_asignacion,id_grado,id_aula,dia_semana,hora_inicio,hora_fin,activo) VALUES (?,?,?,'Lunes','07:00','07:40',1)",[$env->assignment,$base['id_grado'],$base['id_aula']]);
    $env->insert("INSERT INTO huellas_templates (user_id_global,id_profesor,fingerprint_data,formato,activo) VALUES (?,?,?,'HEX',1)",[$base['user_id_global'],$base['id_profesor'],str_repeat('A',3072)]);
    $students=[];for($n=1;$n<=5;$n++){$ci='BROWSER_STUDENT_'.$n;$students[]=$ci;$student=$env->insert("INSERT INTO estudiantes (nombre,apellido,cedula_identidad,id_grado,user_id_global,activo) VALUES ('Alumno','Temporal',?,?,?,1)",[$ci,$base['id_grado'],$ci]);$env->insert("INSERT INTO huellas_templates (user_id_global,id_estudiante,fingerprint_data,formato,activo) VALUES (?,?,?,'HEX',1)",[$ci,$student,str_repeat('A',3072)]);}
    $env->setClock('2026-10-05 07:05:00');
    $vars=getenv();$vars['SISCO_PLANNING_FIXTURE']=json_encode(['url'=>$env->url,'plan'=>$plan,'token'=>$data['token'],'aula'=>(int)$base['id_aula'],'teacher'=>$base['cedula_identidad'],'students'=>$students]);$vars['SISCO_TEST_GATEWAY_KEY']=GATEWAY_API_KEY;
    $vars['SISCO_BROWSER_PORT']=(string)random_int(46000,49000);
    $process=proc_open(['node',__DIR__.'/planning_annual_headless.cjs'],[0=>['pipe','r'],1=>STDOUT,2=>STDERR],$pipes,dirname(__DIR__),$vars,['bypass_shell'=>true]);
    if(!is_resource($process))throw new RuntimeException('No inició prueba del navegador.');fclose($pipes[0]);$code=proc_close($process);if($code!==0)throw new RuntimeException('Prueba del navegador falló.');
}finally{if($env->db->inTransaction())$env->db->rollBack();$env->close();}
echo "OK | planificación frontend: BD temporal eliminada\n";
