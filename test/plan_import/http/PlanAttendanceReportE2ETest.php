<?php
declare(strict_types=1);
require_once __DIR__.'/../support/TestEnvironment.php';
require_once __DIR__.'/../support/TestHttp.php';
require_once dirname(__DIR__,3).'/src/config/app.php';
$checks=0;
function flowCheck(bool $condition,string $message): void {global $checks;if(!$condition)throw new RuntimeException($message);$checks++;echo 'OK | '.$message.PHP_EOL;}
$env=new PlanImportTestEnvironment();$database=$env->database;
try{
    $cookie=$env->directory.'/flow-cookies.txt';
    $http=fn($path,$method='GET',$body=null,$headers=[])=>planTestHttp($env,$cookie,$path,$method,$body,$headers);
    $api=fn($path,$data)=>$http('/src/api/'.$path,'POST',json_encode($data,JSON_THROW_ON_ERROR));
    $login=$http('/src/api/login.php','POST',['usuario'=>'pdf_test','password'=>'Temporal123']);
    flowCheck($login['status']===302,'1. login válido mediante HTTP con identidad positiva');
    $index=$http('/mvc/views/planificacion/index.php');
    if(!preg_match('/"csrf":"([a-f0-9]{64})"/',$index['raw'],$match))throw new RuntimeException('CSRF del formulario ausente');
    $csrf=['X-CSRF-Token: '.$match[1]];
    $upload=$http('/src/api/Planificacion/importar_plan_pdf.php','POST',['id_asignacion'=>$env->assignment,'anio'=>2026,'pdf'=>new CURLFile(dirname(__DIR__,3).'/test/fixtures/planes/Plan Anual - tercer curso - BTI - Administracion financiera.pdf','application/pdf','Plan Anual - tercer curso - BTI - Administracion financiera.pdf')],$csrf);
    flowCheck($upload['status']===201,'2. importación del PDF por multipart HTTP');
    $data=$upload['json']['data'];$canonical=$data['plan'];$counts=[count($canonical['unidades']),0,0,0];
    foreach($canonical['unidades'] as $u)foreach($u['capacidades'] as $c){$counts[1]++;foreach($c['temas'] as $t){$counts[2]++;$counts[3]+=count($t['indicadores']);}}
    flowCheck($counts===[6,6,18,18],'3. conteos exactos 6 unidades / 6 capacidades / 18 temas / 18 indicadores');
    $canonical['unidades'][0]['capacidades'][0]['temas'][0]['contenido']='Contenido temporal del tema para verificar el informe';
    $decisions=array_map(fn($s)=>['kind'=>$s['kind'],'text'=>$s['text'],'action'=>$s['suggested_id']?'existing':'create','id'=>$s['suggested_id']?:null],$data['suggestions']);
    $preview=$http('/src/api/Planificacion/preview_importacion.php','PUT',json_encode(['token'=>$data['token'],'plan'=>$canonical,'decisions'=>$decisions,'programming'=>[]]),$csrf);
    flowCheck($preview['status']===200 && $preview['json']['data']['readiness']['can_confirm'],'4. decisiones de catálogos resueltas en staging');
    $confirmed=$http('/src/api/Planificacion/confirmar_importacion.php','POST',json_encode(['token'=>$data['token']]),$csrf);
    flowCheck($confirmed['status']===201,'5. confirmar importación en BD temporal');$idPlan=(int)$confirmed['json']['data']['id_plan'];
    $read=fn()=>$http('/src/api/Planificacion/planes.php?id_plan='.$idPlan)['json']['data'];
    $plan=$read();flowCheck($plan['estado']==='BORRADOR','6. plan importado BORRADOR');
    $publish=fn()=>$api('Planificacion/planes.php',['accion'=>'estado','id_plan'=>$idPlan,'estado'=>'PUBLICADO']);
    $blocked=$publish();flowCheck($blocked['status']===409 && str_contains($blocked['json']['message'],'18 de 18'),'publicación incompleta rechazada en backend');
    $rows=[];$offset=0;$first=null;
    // Calendario sintético exclusivo de la prueba; no se interpreta ningún período del PDF.
    foreach($plan['unidades'] as $unit)foreach($unit['capacidades'] as $cap)foreach($cap['temas'] as $topic){
        $date=(new DateTimeImmutable('2026-10-05'))->modify('+'.$offset++.' days')->format('Y-m-d');
        $rows[]=['id_tema'=>(int)$topic['id_tema'],'fecha_inicio'=>$date,'fecha_fin'=>$date,'horas_catedra_planificadas'=>1,'observaciones'=>null];
        if($first===null)$first=['unit'=>$unit,'cap'=>$cap,'topic'=>$topic];
    }
    $partial=$api('Planificacion/programacion.php',['accion'=>'guardar_anual','id_plan'=>$idPlan,'programaciones'=>[$rows[0]]]);
    flowCheck($partial['status']===200 && $publish()['status']===409,'un solo tema completo no permite publicar');
    $saved=$api('Planificacion/programacion.php',['accion'=>'guardar_anual','id_plan'=>$idPlan,'programaciones'=>$rows]);
    flowCheck($saved['status']===200,'7. programación anual de los 18 temas sin solapamientos');
    $coverage=$read()['cobertura'];flowCheck($coverage['total_temas']===18 && $coverage['temas_programados']===18 && $coverage['temas_con_indicadores']===18 && $coverage['can_publish'],'8. cobertura 18/18 con indicadores');
    flowCheck($publish()['status']===200 && $read()['estado']==='PUBLICADO','9. publicación autorizada por backend');
    $base=$env->db->query('SELECT ad.id_profesor,ad.id_grado,g.id_aula,p.cedula_identidad,p.user_id_global FROM asignacion_docente ad JOIN grados g USING(id_grado) JOIN profesores p USING(id_profesor) WHERE ad.id_asignacion='.$env->assignment)->fetch(PDO::FETCH_ASSOC);
    $schedule=$http('/src/api/Horario/crear_horario.php','POST',['id_asignacion'=>$env->assignment,'dia_semana'=>'Lunes','hora_inicio'=>'07:00','hora_fin'=>'07:40','permite_superposicion'=>0]);
    flowCheck($schedule['status']===200 && ($schedule['json']['success']??false),'10. horario temporal creado mediante la API actual');
    $hex=str_repeat('A',3072);
    $env->insert("INSERT INTO huellas_templates (user_id_global,id_profesor,fingerprint_data,formato,activo) VALUES (?,?,?,'HEX',1)",[$base['user_id_global'],$base['id_profesor'],$hex]);
    flowCheck((int)$env->db->query('SELECT COUNT(*) FROM huellas_templates WHERE activo=1')->fetchColumn()===1,'11. profesor con huella activa');
    $students=[];
    for($n=1;$n<=5;$n++){
        $ci='FLOW_EST_'.$n;$uid='FLOW_UID_'.$n;
        $student=$env->insert('INSERT INTO estudiantes (nombre,apellido,cedula_identidad,id_grado,user_id_global,activo) VALUES (?,?,?,?,?,1)',['Alumno'.$n,'Temporal',$ci,$base['id_grado'],$uid]);
        $print=$env->insert("INSERT INTO huellas_templates (user_id_global,id_estudiante,fingerprint_data,formato,activo) VALUES (?,?,?,'HEX',1)",[$uid,$student,$hex]);
        $env->db->prepare('UPDATE estudiantes SET huella_id=? WHERE id_estudiante=?')->execute([$print,$student]);$students[]=['id'=>$student,'ci'=>$ci];
    }
    flowCheck((int)$env->db->query('SELECT COUNT(*) FROM huellas_templates WHERE activo=1')->fetchColumn()===6,'12. cinco alumnos con huella activa');
    $gateway=function(string $type,string $ci,string $moment)use($env,$http,$base):array{
        $env->setClock($moment);
        return $http('/src/api/Gateway/registrar_asistencia.php','POST',['id_aula'=>$base['id_aula'],'ci'=>$ci,'tipo_persona'=>$type,'estado'=>'PRESENTE'],['X-GATEWAY-KEY: '.GATEWAY_API_KEY]);
    };
    $wrong=$http('/src/api/Gateway/registrar_asistencia.php','POST',['id_aula'=>$base['id_aula'],'ci'=>$base['cedula_identidad'],'tipo_persona'=>'profesor']);
    flowCheck($wrong['status']===404,'Gateway sin clave rechazado');
    $marked=$gateway('profesor',$base['cedula_identidad'],'2026-10-05 06:50:00');
    flowCheck($marked['status']===200 && ($marked['json']['ok']??false),'13. marca de profesor 06:50 mediante endpoint Gateway real del servidor temporal');
    $idClass=(int)$marked['json']['data']['id_clase'];$class=$env->db->query('SELECT * FROM clases_diarias WHERE id_clase='.$idClass)->fetch(PDO::FETCH_ASSOC);
    flowCheck((bool)$class && $class['fecha']==='2026-10-05','14. clase creada por la huella con fecha autoritativa MySQL');
    flowCheck((int)$class['id_plan']===$idPlan && (int)$class['id_unidad']===(int)$first['unit']['id_unidad'] && (int)$class['id_capacidad']===(int)$first['cap']['id_capacidad'] && (int)$class['id_tema']===(int)$first['topic']['id_tema'],'15. plan / unidad / capacidad / tema esperados para esa fecha');
    $indicatorIds=$env->db->query('SELECT id_indicador FROM clase_diaria_indicadores WHERE id_clase='.$idClass.' ORDER BY id_indicador')->fetchAll(PDO::FETCH_COLUMN);
    flowCheck(array_map('intval',$indicatorIds)===array_map('intval',array_column($first['topic']['indicadores'],'id_indicador')),'16. indicadores copiados del tema correcto');
    foreach(array_slice($students,0,3) as $student){$r=$gateway('estudiante',$student['ci'],'2026-10-05 07:05:00');flowCheck(($r['json']['ok']??false) && (int)$r['json']['data']['id_clase']===$idClass,'17. alumno presente tras marca temprana del profesor');}
    $env->insert('INSERT INTO configuracion_informes (membrete_path,activo,updated_by) VALUES (?,1,?)',['public/uploads/informes/membrete-temporal.png',$env->user]);
    $observation=$api('Informes/registro_anecdotico.php',['id_clase'=>$idClass,'id_estudiante'=>$students[0]['id'],'observacion'=>'Participó activamente en la prueba temporal']);flowCheck($observation['status']===200,'observación anecdótica guardada por la API');
    $report=$http('/src/api/Informes/informe_diario.php?id_clase='.$idClass);flowCheck($report['status']===200,'18. abrir informe diario por HTTP');$report=$report['json']['data'];
    flowCheck((int)$report['id_clase']===$idClass && (int)$report['id_plan']===$idPlan,'informe obtiene la misma clase creada por huella');
    flowCheck($report['materia']==='ADMINISTRACIÓN FINANCIERA' && $report['profesor']==='PDF Test' && $report['grado']==='3° BTI' && $report['fecha']==='2026-10-05' && $report['hora_inicio']==='07:00:00' && $report['hora_fin']==='07:40:00','19. materia, profesor, grado, fecha y horario correctos');
    flowCheck($report['unidad']===$first['unit']['nombre'] && $report['capacidad']===$first['cap']['descripcion'] && $report['tema']===$first['topic']['titulo'] && $report['contenido']===$canonical['unidades'][0]['capacidades'][0]['temas'][0]['contenido'],'unidad, capacidad, tema y contenido correctos');
    flowCheck(array_column($report['indicadores'],'descripcion')===array_column($first['topic']['indicadores'],'descripcion'),'indicadores correctos en el informe');
    $present=count(array_filter($report['estudiantes'],fn($s)=>$s['estado']==='PRESENTE'));
    flowCheck(count($report['estudiantes'])===5 && $present===3 && count($report['estudiantes'])-$present===2,'19. lista completa: 3 PRESENTES / 2 AUSENTES');
    flowCheck($report['membrete_path']==='public/uploads/informes/membrete-temporal.png' && $report['estudiantes'][0]['observacion']==='Participó activamente en la prueba temporal','membrete configurado y observación presentes');
    $repeat=$gateway('profesor',$base['cedula_identidad'],'2026-10-05 07:06:00');flowCheck(($repeat['json']['data']['idempotente']??false) && (int)$repeat['json']['data']['id_clase']===$idClass,'20. segunda huella de profesor reutiliza clase y asistencia');
    $repeat=$gateway('estudiante',$students[0]['ci'],'2026-10-05 07:06:00');flowCheck(($repeat['json']['data']['idempotente']??false),'20. segunda huella de alumno reutiliza asistencia');
    flowCheck((int)$env->db->query('SELECT COUNT(*) FROM clases_diarias')->fetchColumn()===1 && (int)$env->db->query('SELECT COUNT(*) FROM asistencias_profesores')->fetchColumn()===1 && (int)$env->db->query('SELECT COUNT(*) FROM asistencias_estudiantes')->fetchColumn()===3,'sin duplicar clases ni asistencias');
    $rescheduled=$rows;
    foreach(['fecha_inicio','fecha_fin'] as $key){$rescheduled[0][$key]=$rows[1][$key];$rescheduled[1][$key]=$rows[0][$key];}
    $changed=$api('Planificacion/programacion.php',['accion'=>'guardar_anual','id_plan'=>$idPlan,'programaciones'=>$rescheduled]);
    flowCheck($changed['status']===200,'reprogramación temporal conserva cobertura completa');
    $again=$gateway('profesor',$base['cedula_identidad'],'2026-10-05 07:07:00');
    $persisted=$http('/src/api/Informes/informe_diario.php?id_clase='.$idClass)['json']['data'];
    flowCheck((int)$again['json']['data']['id_clase']===$idClass && (int)$persisted['id_tema']===(int)$first['topic']['id_tema'] && array_column($persisted['indicadores'],'descripcion')===array_column($first['topic']['indicadores'],'descripcion'),'segunda huella tras reprogramar conserva tema e indicadores de la clase original');
    $recovered=$api('Informes/clase_diaria.php',['id_asignacion'=>$env->assignment,'fecha'=>'2026-10-05']);flowCheck((int)$recovered['json']['data']['id_clase']===$idClass,'recuperación manual devuelve la misma clase biométrica');
}finally{$env->close();}
$admin=new PDO('mysql:host=localhost;charset=utf8mb4','root','');$q=$admin->prepare('SELECT COUNT(*) FROM information_schema.SCHEMATA WHERE SCHEMA_NAME=?');$q->execute([$database]);flowCheck((int)$q->fetchColumn()===0,'BD temporal eliminada al finalizar');
echo "OK | PlanAttendanceReportE2ETest | $checks PASS; 0 FAIL (PDF -> BORRADOR -> programación 18/18 -> PUBLICADO -> Gateway HTTP -> clase -> tema -> 3/5 presentes -> informe; BD temporal eliminada)\n";
