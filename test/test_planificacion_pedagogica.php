<?php
require_once __DIR__ . '/../src/config/db.php';
require_once __DIR__ . '/../src/classes/PlanificacionPedagogica.php';
require_once __DIR__ . '/../src/classes/ClaseDiaria.php';

require_once __DIR__.'/plan_import/support/TestEnvironment.php';
$env=new PlanImportTestEnvironment();
$db=$env->db;
$token='PLAN_TEST_'.date('YmdHis').'_'.random_int(100,999);
$ciBase=substr(hash('sha256',$token),0,12);
$ids=['eventos'=>[],'asistencias_estudiantes'=>[],'estudiantes'=>[],'unidades'=>[],'capacidades'=>[],'temas'=>[]];
$resultados=[];
function comprobar(bool $condicion,string $nombre): void { global $resultados; $resultados[]=[$condicion,$nombre]; echo ($condicion?'OK':'FALLA')." | $nombre\n"; if(!$condicion)throw new RuntimeException("Fallo: $nombre"); }
function insertar(PDO $db,string $sql,array $params): int {$s=$db->prepare($sql);$s->execute($params);return(int)$db->lastInsertId();}

try {
    // Esta conexión pertenece exclusivamente al entorno temporal.
    if(!preg_match('/^sisco_pdf_test_[a-f0-9]{12}$/D',$env->database))throw new RuntimeException('BD no temporal');
    $tables=$db->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    $db->exec('SET FOREIGN_KEY_CHECKS=0');
    foreach($tables as $table){if(!preg_match('/^[a-zA-Z0-9_]+$/D',$table))throw new RuntimeException('Tabla inválida');$db->exec('TRUNCATE TABLE '.$table);}
    $db->exec('SET FOREIGN_KEY_CHECKS=1');
    $ids['aula']=insertar($db,'INSERT INTO aulas (nombre,codigo,ubicacion) VALUES (:n,:c,:u)',[':n'=>$token.' Aula',':c'=>$token,':u'=>'Prueba automatizada']);
    $ids['grado']=insertar($db,'INSERT INTO grados (nombre,id_aula,room_id) VALUES (:n,:a,:r)',[':n'=>$token.' Grado',':a'=>$ids['aula'],':r'=>$token]);
    $ids['profesor']=insertar($db,'INSERT INTO profesores (nombre,apellido,cedula_identidad,user_id_global,activo) VALUES (:n,:a,:c,:u,1)',[':n'=>'Profesor',':a'=>$token,':c'=>'9'.$ciBase,':u'=>$token.'_PROF']);
    $ids['usuario']=insertar($db,"INSERT INTO usuarios (nombre,apellido,usuario,email,celular,password,rol,activo) VALUES ('Profesor',:a,:u,:e,981234567,:p,'Profesor',1)",[':a'=>$token,':u'=>$token,':e'=>$ciBase.'@test.local',':p'=>password_hash('Temporal123',PASSWORD_DEFAULT)]);
    $db->prepare('UPDATE profesores SET id_usuario=:usuario WHERE id_profesor=:profesor')->execute([':usuario'=>$ids['usuario'],':profesor'=>$ids['profesor']]);
    $ids['materia']=insertar($db,'INSERT INTO materias (nombre,activo) VALUES (:n,1)',[':n'=>$token.' Materia']);
    $ids['asignacion']=insertar($db,'INSERT INTO asignacion_docente (id_profesor,id_materia,id_grado,carga_horaria,anio_lectivo,activo) VALUES (:p,:m,:g,4,2026,1)',[':p'=>$ids['profesor'],':m'=>$ids['materia'],':g'=>$ids['grado']]);
    $ids['horario']=insertar($db,"INSERT INTO horarios (id_asignacion,id_grado,id_aula,dia_semana,hora_inicio,hora_fin,activo,permite_superposicion) VALUES (:a,:g,:u,'Lunes','07:00:00','07:40:00',1,0)",[':a'=>$ids['asignacion'],':g'=>$ids['grado'],':u'=>$ids['aula']]);
    for($i=1;$i<=5;$i++)$ids['estudiantes'][]=insertar($db,'INSERT INTO estudiantes (nombre,apellido,cedula_identidad,id_grado,user_id_global,activo) VALUES (:n,:a,:c,:g,:u,1)',[':n'=>'Alumno'.$i,':a'=>$token,':c'=>$i.$ciBase,':u'=>$token.'_EST_'.$i,':g'=>$ids['grado']]);

    $studentPrints=[];
    foreach($ids['estudiantes'] as $index=>$student)$studentPrints[$student]=insertar($db,"INSERT INTO huellas_templates (user_id_global,id_estudiante,fingerprint_data,formato,activo) VALUES (?,?,?,'HEX',1)",[$token.'_EST_'.($index+1),$student,str_repeat('A',3072)]);
    $servicio=new PlanificacionPedagogica($db,['id_usuario'=>$ids['usuario'],'rol'=>'Profesor']);
    $ids['plan']=$servicio->crearPlan(['id_asignacion'=>$ids['asignacion'],'anio'=>2026,'competencia_general'=>'Competencia de prueba','competencia_especifica'=>'Competencia especifica']);
    for($u=1;$u<=3;$u++){
        $unidad=$servicio->guardarElemento('unidad',['id_plan'=>$ids['plan'],'nombre'=>"Unidad $u",'descripcion'=>"Descripcion $u",'horas_catedra'=>10]);$ids['unidades'][]=$unidad;
        $cap=$servicio->guardarElemento('capacidad',['id_unidad'=>$unidad,'descripcion'=>"Capacidad $u",'proceso_desarrollo'=>'Proceso']);$ids['capacidades'][]=$cap;
        $tema=$servicio->guardarElemento('tema',['id_capacidad'=>$cap,'titulo'=>"Tema $u",'contenido'=>'Contenido','horas_catedra'=>2]);$ids['temas'][]=$tema;
        $servicio->guardarElemento('indicador',['id_tema'=>$tema,'descripcion'=>"Indicador U$u"]);
    }
    $tema=$ids['temas'][0];
    $servicio->guardarElemento('indicador',['id_tema'=>$tema,'descripcion'=>'Indicador 2']);
    $servicio->guardarElemento('indicador',['id_tema'=>$tema,'descripcion'=>'Indicador 3']);
    $db->exec("INSERT INTO procedimientos_evaluativos (nombre,activo) VALUES ('Observacion temporal',1),('Analisis temporal',1)");
    $db->exec("INSERT INTO instrumentos_evaluativos (nombre,activo) VALUES ('Lista temporal',1),('Rubrica temporal',1)");
    $catalogos=$servicio->catalogos();
    $servicio->guardarEvaluacion($tema,array_column(array_slice($catalogos['procedimientos'],0,2),'id_procedimiento'),array_column(array_slice($catalogos['instrumentos'],0,2),'id_instrumento'));
    $servicio->guardarProgramacion(['id_tema'=>$tema,'fecha_inicio'=>'2026-08-31','fecha_fin'=>'2026-08-31','horas_catedra_planificadas'=>1]);
    $servicio->guardarProgramacion(['id_tema'=>$tema,'fecha_inicio'=>'2026-09-07','fecha_fin'=>'2026-09-07','horas_catedra_planificadas'=>1]);
    foreach(array_slice($ids['temas'],1) as $index=>$otherTopic){$date='2026-09-'.($index===0?'01':'02');$servicio->guardarProgramacion(['id_tema'=>$otherTopic,'fecha_inicio'=>$date,'fecha_fin'=>$date,'horas_catedra_planificadas'=>1]);}
    $servicio->cambiarEstado($ids['plan'],'PUBLICADO');
    $plan=$servicio->obtenerPlan($ids['plan']);
    comprobar(count($plan['unidades'])===3,'plan con 3 unidades');
    comprobar(count($plan['unidades'][0]['capacidades'][0]['temas'][0]['indicadores'])===3,'tema con 3 indicadores');
    comprobar(count($plan['unidades'][0]['capacidades'][0]['temas'][0]['procedimientos'])===2,'tema con varios procedimientos');
    comprobar(count($plan['unidades'][0]['capacidades'][0]['temas'][0]['instrumentos'])===2,'tema con varios instrumentos');
    comprobar(count($plan['unidades'][0]['capacidades'][0]['temas'][0]['programaciones'])===2,'tema con varias fechas programadas');
    $servicioProfesor=new PlanificacionPedagogica($db,['id_usuario'=>$ids['usuario'],'rol'=>'Profesor']);
    $propias=$servicioProfesor->asignacionesDisponibles();
    comprobar(count($propias)===1&&(int)$propias[0]['id_asignacion']===$ids['asignacion'],'profesor solo lista su asignacion vinculada');

    for($i=0;$i<3;$i++){
        $ids['eventos'][]=insertar($db,"INSERT INTO eventos_asistencia (user_id_global,id_aula,estado,timestamp_evento,origen_node_id,sincronizado,activo) VALUES (:u,:a,'PRESENTE','2026-08-31 07:10:00','TEST',1,1)",[':u'=>$token.'_EST_'.($i+1),':a'=>$ids['aula']]);
        $ids['asistencias_estudiantes'][]=insertar($db,"INSERT INTO asistencias_estudiantes (id_estudiante,huella_id,fecha,hora,estado,activo) VALUES (:estudiante,:huella,'2026-08-31','07:10:00','PRESENTE',1)",[':estudiante'=>$ids['estudiantes'][$i],':huella'=>$studentPrints[$ids['estudiantes'][$i]]]);
    }
    $clases=new ClaseDiaria($db);$ids['clase']=$clases->crearORecuperar($ids['asignacion'],$ids['horario'],'2026-08-31','07:00:00','07:40:00');
    $repetida=$clases->crearORecuperar($ids['asignacion'],$ids['horario'],'2026-08-31','07:00:00','07:40:00');
    comprobar($ids['clase']===$repetida,'marca repetida recupera la misma clase');
    $desdeHuella=$clases->procesarMarcaProfesor($token.'_PROF',$ids['aula'],'2026-08-31 07:05:00');
    comprobar($desdeHuella===$ids['clase'],'marca del profesor resuelve horario, plan y clase');
    $informe=$clases->informe($ids['clase']);$presentes=count(array_filter($informe['estudiantes'],fn($e)=>$e['estado']==='PRESENTE'));
    comprobar(count($informe['estudiantes'])===5,'informe contiene 5 alumnos');comprobar($presentes===3,'informe calcula 3 presentes');comprobar(count($informe['estudiantes'])-$presentes===2,'informe calcula 2 ausentes');
    $clases->guardarRegistro($ids['clase'],$ids['estudiantes'][0],null,'Participo activamente',$ids['usuario']);
    $clases->guardarRegistro($ids['clase'],$ids['estudiantes'][4],null,'Ausencia justificada',$ids['usuario']);
    $reabierto=$clases->informe($ids['clase']);$obs=array_column($reabierto['estudiantes'],'observacion','id_estudiante');
    comprobar(($obs[$ids['estudiantes'][0]]??'')==='Participo activamente','persiste observacion de presente');
    comprobar(($obs[$ids['estudiantes'][4]]??'')==='Ausencia justificada','persiste observacion de ausente');
    comprobar($reabierto['materia']===$token.' Materia'&&$reabierto['grado']===$token.' Grado'&&$reabierto['tema']==='Tema 1','informe recupera materia, grado y contenido');
    $ids['clase_sin_contenido']=$clases->crearORecuperar($ids['asignacion'],$ids['horario'],'2026-09-14','07:00:00','07:40:00');
    comprobar($clases->informe($ids['clase_sin_contenido'])['sin_contenido']===true,'fecha sin programacion no inventa contenido');
} finally {
    $env->close();
}
echo 'RESUMEN | '.count($resultados)." OK (BD temporal eliminada; identidad positiva)\n";
?>
