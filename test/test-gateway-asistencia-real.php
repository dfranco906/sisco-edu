<?php
declare(strict_types=1);
require_once __DIR__.'/plan_import/support/TestEnvironment.php';

$env = new PlanImportTestEnvironment();
$checks = 0;
function gatewayCheck(bool $ok, string $message): void { global $checks; if (!$ok) throw new RuntimeException($message); $checks++; echo "OK | $message\n"; }
function gatewayInsert(PDO $db, string $sql, array $params=[]): int { $s=$db->prepare($sql); $s->execute($params); return (int)$db->lastInsertId(); }
/** @return array{id_plan:int,id_tema:int,id_indicador:int} */
function gatewayPlan(PDO $db,int $idAsignacion,int $idUsuario,string $nombre): array {
    $plan=gatewayInsert($db,"INSERT INTO planes_anuales (id_asignacion,anio,competencia_general,estado,created_by) VALUES (?,2026,?,'PUBLICADO',?)",[$idAsignacion,$nombre,$idUsuario]);
    $unidad=gatewayInsert($db,'INSERT INTO plan_unidades (id_plan,nombre,orden) VALUES (?,?,1)',[$plan,'Unidad '.$nombre]);
    $capacidad=gatewayInsert($db,'INSERT INTO plan_capacidades (id_unidad,descripcion,orden) VALUES (?,?,1)',[$unidad,'Capacidad '.$nombre]);
    $tema=gatewayInsert($db,'INSERT INTO plan_temas (id_capacidad,titulo,orden) VALUES (?,?,1)',[$capacidad,'Tema '.$nombre]);
    $indicador=gatewayInsert($db,'INSERT INTO plan_indicadores (id_tema,descripcion,orden) VALUES (?,?,1)',[$tema,'Indicador '.$nombre]);
    gatewayInsert($db,"INSERT INTO plan_tema_programacion (id_tema,fecha_inicio,fecha_fin,orden) VALUES (?,'2026-09-07','2026-09-07',1)",[$tema]);
    return ['id_plan'=>$plan,'id_tema'=>$tema,'id_indicador'=>$indicador];
}
/** @return array{id:int,ci:string} */
function gatewayStudent(PDO $db,int $idGrado,string $suffix,string $hex): array {
    $ci='87777'.$suffix; $uid='GATEWAY_JOINT_'.$suffix;
    $id=gatewayInsert($db,'INSERT INTO estudiantes (nombre,apellido,cedula_identidad,id_grado,user_id_global,activo) VALUES (?,?,?,?,?,1)',['Alumno'.$suffix,'Conjunto',$ci,$idGrado,$uid]);
    $huella=gatewayInsert($db,'INSERT INTO huellas_templates (user_id_global,id_estudiante,fingerprint_data,formato,activo) VALUES (?,?,?,\'HEX\',1)',[$uid,$id,$hex]);
    $db->prepare('UPDATE estudiantes SET huella_id=? WHERE id_estudiante=?')->execute([$huella,$id]);
    return ['id'=>$id,'ci'=>$ci];
}

try {
    require_once $env->directory.'/src/classes/GatewayAttendanceService.php';
    gatewayCheck(date_default_timezone_get()==='America/Asuncion','zona horaria central America/Asuncion');
    $base=$env->db->prepare('SELECT ad.id_profesor,ad.id_grado,g.id_aula,p.cedula_identidad,p.user_id_global FROM asignacion_docente ad JOIN grados g ON g.id_grado=ad.id_grado JOIN profesores p ON p.id_profesor=ad.id_profesor WHERE ad.id_asignacion=?');
    $base->execute([$env->assignment]); $prof=$base->fetch(PDO::FETCH_ASSOC);
    gatewayCheck((bool)$prof,'fixture de profesor, grado y aula');
    $horario=gatewayInsert($env->db,"INSERT INTO horarios (id_asignacion,id_grado,id_aula,dia_semana,hora_inicio,hora_fin,activo) VALUES (?,?,?,'Lunes','12:50:00','13:30:00',1)",[$env->assignment,$prof['id_grado'],$prof['id_aula']]);
    $hex=str_repeat('A',3072);
    $huellaProfesor=gatewayInsert($env->db,'INSERT INTO huellas_templates (user_id_global,id_profesor,fingerprint_data,formato,activo) VALUES (?,?,?,\'HEX\',1)',[$prof['user_id_global'],$prof['id_profesor'],$hex]);
    $alumnos=[];
    for($i=1;$i<=5;$i++) {
        $ci='88888'.$i.'01'; $uid='GATEWAY_TEST_EST_'.$i;
        $id=gatewayInsert($env->db,'INSERT INTO estudiantes (nombre,apellido,cedula_identidad,id_grado,user_id_global,activo) VALUES (?,?,?,?,?,1)',['Alumno'.$i,'Gateway',$ci,$prof['id_grado'],$uid]);
        $huella=gatewayInsert($env->db,'INSERT INTO huellas_templates (user_id_global,id_estudiante,fingerprint_data,formato,activo) VALUES (?,?,?,\'HEX\',1)',[$uid,$id,$hex]);
        $env->db->prepare('UPDATE estudiantes SET huella_id=? WHERE id_estudiante=?')->execute([$huella,$id]);
        $alumnos[]=['id'=>$id,'ci'=>$ci];
    }
    $service=new GatewayAttendanceService($env->db);
    $casoTemprano=$service->registrar('profesor',$prof['cedula_identidad'],(int)$prof['id_aula'],new DateTimeImmutable('2026-09-07 12:39:05',new DateTimeZone('America/Asuncion')));
    gatewayCheck(($casoTemprano['ok']??true)===false && ($casoTemprano['status']??'')==='sin_horario','12:39:05 queda fuera del horario 12:50-13:30');
    gatewayCheck((int)$env->db->query('SELECT COUNT(*) FROM clases_diarias')->fetchColumn()===0,'12:39:05 no crea clase ni asistencia');
    $momento=new DateTimeImmutable('2026-09-07 13:17:39',new DateTimeZone('America/Asuncion'));
    $r=$service->registrar('profesor',$prof['cedula_identidad'],(int)$prof['id_aula'],$momento);
    gatewayCheck(($r['ok']??false)===true,'profesor identificado y evento guardado');
    $idClase=(int)$r['data']['id_clase'];
    gatewayCheck($idClase>0,'lunes 13:17:39 encuentra horario 12:50-13:30 y crea clase');
    gatewayCheck(($r['data']['cantidad_clases']??0)===1 && count($r['data']['ids_clases']??[])===1,'CASO 1: clase normal abre una clase diaria');
    gatewayCheck((int)$env->db->query('SELECT COUNT(*) FROM asistencias_profesores')->fetchColumn()===1,'asistencia del profesor creada');
    $repetida=$service->registrar('profesor',$prof['cedula_identidad'],(int)$prof['id_aula'],$momento->modify('+1 minute'));
    gatewayCheck(($repetida['data']['id_clase']??0)===$idClase && ($repetida['data']['idempotente']??false)===true,'segunda marca de profesor es idempotente');
    gatewayCheck((int)$env->db->query('SELECT COUNT(*) FROM clases_diarias')->fetchColumn()===1 && (int)$env->db->query('SELECT COUNT(*) FROM asistencias_profesores')->fetchColumn()===1,'sin clases ni asistencias de profesor duplicadas');
    $casoTarde=$service->registrar('profesor',$prof['cedula_identidad'],(int)$prof['id_aula'],new DateTimeImmutable('2026-09-07 13:39:05',new DateTimeZone('America/Asuncion')));
    gatewayCheck(($casoTarde['ok']??true)===false && ($casoTarde['status']??'')==='sin_horario','13:39:05 queda fuera del horario 12:50-13:30');
    gatewayCheck((int)$env->db->query('SELECT COUNT(*) FROM clases_diarias')->fetchColumn()===1 && (int)$env->db->query('SELECT COUNT(*) FROM asistencias_profesores')->fetchColumn()===1,'13:39:05 no crea otra clase ni asistencia');

    foreach (array_slice($alumnos,0,3) as $alumno) {
        $r=$service->registrar('estudiante',$alumno['ci'],(int)$prof['id_aula'],new DateTimeImmutable('2026-09-07 13:20:00',new DateTimeZone('America/Asuncion')));
        gatewayCheck(($r['ok']??false)===true && ($r['data']['id_clase']??0)===$idClase,'alumno presente dentro de la ventana de clase activa');
    }
    $r=$service->registrar('estudiante',$alumnos[0]['ci'],(int)$prof['id_aula'],new DateTimeImmutable('2026-09-07 13:21:00',new DateTimeZone('America/Asuncion')));
    gatewayCheck(($r['data']['idempotente']??false)===true,'segunda marca de alumno es idempotente');
    gatewayCheck((int)$env->db->query('SELECT COUNT(*) FROM asistencias_estudiantes')->fetchColumn()===3,'tres asistencias de alumnos sin duplicados');
    $informe=(new ClaseDiaria($env->db))->informe($idClase);
    $presentes=count(array_filter($informe['estudiantes'],fn(array $e): bool=>$e['estado']==='PRESENTE'));
    gatewayCheck($presentes===3 && count($informe['estudiantes'])-$presentes===2,'ausentes derivados: 3 presentes y 2 ausentes');

    $sinHorario=$service->registrar('profesor',$prof['cedula_identidad'],(int)$prof['id_aula'],new DateTimeImmutable('2026-09-07 18:17:39',new DateTimeZone('America/Asuncion')));
    gatewayCheck(($sinHorario['ok']??true)===false && ($sinHorario['status']??'')==='sin_horario','profesor fuera de horario responde sin_horario');
    gatewayCheck((int)$env->db->query('SELECT COUNT(*) FROM clases_diarias')->fetchColumn()===1,'fuera de horario no crea clase falsa');
    $sinClase=$service->registrar('estudiante',$alumnos[3]['ci'],(int)$prof['id_aula'],new DateTimeImmutable('2026-09-07 13:29:00',new DateTimeZone('America/Asuncion')));
    gatewayCheck(($sinClase['ok']??true)===false && ($sinClase['status']??'')==='sin_clase_activa','alumno fuera de ventana responde sin_clase_activa');
    $desconocido=$service->registrar('estudiante','CI_INEXISTENTE',(int)$prof['id_aula'],new DateTimeImmutable('2026-09-07 13:20:00',new DateTimeZone('America/Asuncion')));
    gatewayCheck(($desconocido['ok']??true)===false && ($desconocido['status']??'')==='persona_no_encontrada','usuario biometrico desconocido se rechaza claramente');
    $aulaInvalida=$service->registrar('profesor',$prof['cedula_identidad'],999999,new DateTimeImmutable('2026-09-07 13:20:00',new DateTimeZone('America/Asuncion')));
    gatewayCheck(($aulaInvalida['ok']??true)===false && ($aulaInvalida['status']??'')==='aula_invalida','aula incorrecta no se asocia a otra clase');

    $horarioFallo=gatewayInsert($env->db,"INSERT INTO horarios (id_asignacion,id_grado,id_aula,dia_semana,hora_inicio,hora_fin,activo) VALUES (?,?,?,'Lunes','14:10:00','14:50:00',1)",[$env->assignment,$prof['id_grado'],$prof['id_aula']]);
    $env->db->exec("CREATE TRIGGER gateway_fail_prof BEFORE INSERT ON asistencias_profesores FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='fallo de prueba'");
    try { $service->registrar('profesor',$prof['cedula_identidad'],(int)$prof['id_aula'],new DateTimeImmutable('2026-09-07 14:15:00',new DateTimeZone('America/Asuncion'))); throw new RuntimeException('El trigger debio fallar.'); }
    catch (PDOException $e) { gatewayCheck(true,'fallo SQL de asistencia propaga error controlable'); }
    finally { $env->db->exec('DROP TRIGGER gateway_fail_prof'); }
    gatewayCheck((int)$env->db->query("SELECT COUNT(*) FROM clases_diarias WHERE hora_inicio='14:10:00'")->fetchColumn()===0,'rollback elimina la clase de la operacion fallida');
    gatewayCheck((int)$env->db->query("SELECT COUNT(*) FROM eventos_asistencia WHERE user_id_global='".$prof['user_id_global']."'")->fetchColumn()>=4,'evento crudo se conserva para auditoria aun con fallo de dominio');

    $aulaB=gatewayInsert($env->db,"INSERT INTO aulas (nombre,codigo,ubicacion) VALUES ('Aula conjunta B','GW_JOINT_B','Temporal')");
    $gradoB=gatewayInsert($env->db,"INSERT INTO grados (nombre,id_aula,room_id) VALUES ('Grado conjunto B',?,'GW_JOINT_B')",[$aulaB]);
    $materiaB=gatewayInsert($env->db,"INSERT INTO materias (nombre,activo) VALUES ('Materia conjunta B',1)");
    $asignacionB=gatewayInsert($env->db,'INSERT INTO asignacion_docente (id_profesor,id_materia,id_grado,carga_horaria,anio_lectivo,activo) VALUES (?,?,?,4,2026,1)',[$prof['id_profesor'],$materiaB,$gradoB]);
    $alumnoB=gatewayStudent($env->db,$gradoB,'B01',$hex);
    $planA=gatewayPlan($env->db,$env->assignment,$env->user,'Plan A');
    $planB=gatewayPlan($env->db,$asignacionB,$env->user,'Plan B');
    $grupo=random_int(100000,700000);
    $horarioA=gatewayInsert($env->db,"INSERT INTO horarios (id_asignacion,id_grado,id_aula,dia_semana,hora_inicio,hora_fin,activo,permite_superposicion,id_grupo_clase_conjunta) VALUES (?,?,?,'Lunes','15:10:00','15:50:00',1,1,?)",[$env->assignment,$prof['id_grado'],$prof['id_aula'],$grupo]);
    $horarioB=gatewayInsert($env->db,"INSERT INTO horarios (id_asignacion,id_grado,id_aula,dia_semana,hora_inicio,hora_fin,activo,permite_superposicion,id_grupo_clase_conjunta) VALUES (?,?,?,'Lunes','15:10:00','15:50:00',1,1,?)",[$asignacionB,$gradoB,$aulaB,$grupo]);
    $asistenciasAntes=(int)$env->db->query('SELECT COUNT(*) FROM asistencias_profesores')->fetchColumn();
    $conjunta=$service->registrar('profesor',$prof['cedula_identidad'],(int)$prof['id_aula'],new DateTimeImmutable('2026-09-07 15:15:00',new DateTimeZone('America/Asuncion')));
    $clasesConjuntas=$env->db->query("SELECT * FROM clases_diarias WHERE hora_inicio='15:10:00' ORDER BY id_asignacion")->fetchAll(PDO::FETCH_ASSOC);
    gatewayCheck(($conjunta['ok']??false) && ($conjunta['data']['cantidad_clases']??0)===2 && count($clasesConjuntas)===2,'CASO 2: una marca abre dos clases diarias hermanas');
    $porAsignacion=[];foreach($clasesConjuntas as $claseConjunta)$porAsignacion[(int)$claseConjunta['id_asignacion']]=$claseConjunta;
    gatewayCheck((int)$porAsignacion[$env->assignment]['id_horario']===$horarioA
        && (int)$porAsignacion[$asignacionB]['id_horario']===$horarioB,'CASO 2: cada clase hermana conserva su asignacion y horario');
    gatewayCheck((int)$porAsignacion[$env->assignment]['id_plan']===$planA['id_plan'] && (int)$porAsignacion[$env->assignment]['id_tema']===$planA['id_tema']
        && (int)$porAsignacion[$asignacionB]['id_plan']===$planB['id_plan'] && (int)$porAsignacion[$asignacionB]['id_tema']===$planB['id_tema'],'CASO 3: cada clase conjunta resuelve su propio plan y tema');
    $indicadoresConjuntos=$env->db->query("SELECT cd.id_asignacion,cdi.id_indicador FROM clase_diaria_indicadores cdi JOIN clases_diarias cd USING(id_clase) WHERE cd.hora_inicio='15:10:00'")->fetchAll(PDO::FETCH_KEY_PAIR);
    gatewayCheck(count($indicadoresConjuntos)===2 && (int)$indicadoresConjuntos[$env->assignment]===$planA['id_indicador']
        && (int)$indicadoresConjuntos[$asignacionB]===$planB['id_indicador'],'CASO 3: cada clase conjunta copia sus propios indicadores');
    gatewayCheck((int)$env->db->query('SELECT COUNT(*) FROM asistencias_profesores')->fetchColumn()===$asistenciasAntes+1,'CASO 2: una marca conjunta crea una sola asistencia de profesor');

    $alumnoA=$service->registrar('estudiante',$alumnos[3]['ci'],(int)$prof['id_aula'],new DateTimeImmutable('2026-09-07 15:16:00',new DateTimeZone('America/Asuncion')));
    $marcaAlumnoB=$service->registrar('estudiante',$alumnoB['ci'],$aulaB,new DateTimeImmutable('2026-09-07 15:16:00',new DateTimeZone('America/Asuncion')));
    gatewayCheck(($alumnoA['ok']??false) && (int)$alumnoA['data']['id_clase']===(int)$porAsignacion[$env->assignment]['id_clase'],'CASO 5: alumno del grado A encuentra la clase A');
    gatewayCheck(($marcaAlumnoB['ok']??false) && (int)$marcaAlumnoB['data']['id_clase']===(int)$porAsignacion[$asignacionB]['id_clase'],'CASO 6: alumno del grado B encuentra la clase B');

    $clasesAntes=(int)$env->db->query('SELECT COUNT(*) FROM clases_diarias')->fetchColumn();
    $asistenciasAntes=(int)$env->db->query('SELECT COUNT(*) FROM asistencias_profesores')->fetchColumn();
    $conjuntaRepetida=$service->registrar('profesor',$prof['cedula_identidad'],(int)$prof['id_aula'],new DateTimeImmutable('2026-09-07 15:17:00',new DateTimeZone('America/Asuncion')));
    gatewayCheck(($conjuntaRepetida['data']['idempotente']??false) && ($conjuntaRepetida['data']['cantidad_clases']??0)===2
        && (int)$env->db->query('SELECT COUNT(*) FROM clases_diarias')->fetchColumn()===$clasesAntes
        && (int)$env->db->query('SELECT COUNT(*) FROM asistencias_profesores')->fetchColumn()===$asistenciasAntes,'CASO 7: segunda huella no duplica clases ni asistencia');

    $aulaC=gatewayInsert($env->db,"INSERT INTO aulas (nombre,codigo,ubicacion) VALUES ('Aula conjunta sin plan','GW_JOINT_C','Temporal')");
    $gradoC=gatewayInsert($env->db,"INSERT INTO grados (nombre,id_aula,room_id) VALUES ('Grado conjunto sin plan',?,'GW_JOINT_C')",[$aulaC]);
    $materiaC=gatewayInsert($env->db,"INSERT INTO materias (nombre,activo) VALUES ('Materia conjunta sin plan',1)");
    $asignacionC=gatewayInsert($env->db,'INSERT INTO asignacion_docente (id_profesor,id_materia,id_grado,carga_horaria,anio_lectivo,activo) VALUES (?,?,?,4,2026,1)',[$prof['id_profesor'],$materiaC,$gradoC]);
    $alumnoC=gatewayStudent($env->db,$gradoC,'C01',$hex);
    $grupoSinPlan=$grupo+1;
    gatewayInsert($env->db,"INSERT INTO horarios (id_asignacion,id_grado,id_aula,dia_semana,hora_inicio,hora_fin,activo,permite_superposicion,id_grupo_clase_conjunta) VALUES (?,?,?,'Lunes','16:10:00','16:50:00',1,1,?)",[$env->assignment,$prof['id_grado'],$prof['id_aula'],$grupoSinPlan]);
    gatewayInsert($env->db,"INSERT INTO horarios (id_asignacion,id_grado,id_aula,dia_semana,hora_inicio,hora_fin,activo,permite_superposicion,id_grupo_clase_conjunta) VALUES (?,?,?,'Lunes','16:10:00','16:50:00',1,1,?)",[$asignacionC,$gradoC,$aulaC,$grupoSinPlan]);
    $sinPlan=$service->registrar('profesor',$prof['cedula_identidad'],(int)$prof['id_aula'],new DateTimeImmutable('2026-09-07 16:15:00',new DateTimeZone('America/Asuncion')));
    $clasesSinPlan=$env->db->query("SELECT * FROM clases_diarias WHERE hora_inicio='16:10:00' ORDER BY id_asignacion")->fetchAll(PDO::FETCH_ASSOC);
    $porAsignacion=[];foreach($clasesSinPlan as $claseConjunta)$porAsignacion[(int)$claseConjunta['id_asignacion']]=$claseConjunta;
    gatewayCheck(($sinPlan['ok']??false) && count($clasesSinPlan)===2
        && (int)$porAsignacion[$env->assignment]['id_plan']===$planA['id_plan'] && $porAsignacion[$asignacionC]['id_plan']===null
        && $porAsignacion[$asignacionC]['id_tema']===null,'CASO 4: clase sin plan existe sin contenido y no bloquea la asistencia');
    $marcaAlumnoA2=$service->registrar('estudiante',$alumnos[4]['ci'],(int)$prof['id_aula'],new DateTimeImmutable('2026-09-07 16:16:00',new DateTimeZone('America/Asuncion')));
    $marcaAlumnoC=$service->registrar('estudiante',$alumnoC['ci'],$aulaC,new DateTimeImmutable('2026-09-07 16:16:00',new DateTimeZone('America/Asuncion')));
    gatewayCheck(($marcaAlumnoA2['ok']??false) && ($marcaAlumnoC['ok']??false),'CASO 4: asistencia funciona en ambas clases aunque una no tenga plan');

    $grupoRollback=$grupo+2;
    gatewayInsert($env->db,"INSERT INTO horarios (id_asignacion,id_grado,id_aula,dia_semana,hora_inicio,hora_fin,activo,permite_superposicion,id_grupo_clase_conjunta) VALUES (?,?,?,'Lunes','17:10:00','17:50:00',1,1,?)",[$env->assignment,$prof['id_grado'],$prof['id_aula'],$grupoRollback]);
    gatewayInsert($env->db,"INSERT INTO horarios (id_asignacion,id_grado,id_aula,dia_semana,hora_inicio,hora_fin,activo,permite_superposicion,id_grupo_clase_conjunta) VALUES (?,?,?,'Lunes','17:10:00','17:50:00',1,1,?)",[$asignacionC,$gradoC,$aulaC,$grupoRollback]);
    $asistenciasAntes=(int)$env->db->query('SELECT COUNT(*) FROM asistencias_profesores')->fetchColumn();
    $indicadoresAntes=(int)$env->db->query('SELECT COUNT(*) FROM clase_diaria_indicadores')->fetchColumn();
    $env->db->exec("CREATE TRIGGER gateway_fail_joint BEFORE INSERT ON clases_diarias FOR EACH ROW BEGIN IF NEW.id_asignacion=$asignacionC THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='fallo segunda clase'; END IF; END");
    try { $service->registrar('profesor',$prof['cedula_identidad'],(int)$prof['id_aula'],new DateTimeImmutable('2026-09-07 17:15:00',new DateTimeZone('America/Asuncion'))); throw new RuntimeException('El trigger conjunto debio fallar.'); }
    catch (PDOException $e) { gatewayCheck(true,'CASO 8: error al crear la segunda clase se propaga'); }
    finally { $env->db->exec('DROP TRIGGER gateway_fail_joint'); }
    gatewayCheck((int)$env->db->query("SELECT COUNT(*) FROM clases_diarias WHERE hora_inicio='17:10:00'")->fetchColumn()===0
        && (int)$env->db->query('SELECT COUNT(*) FROM clase_diaria_indicadores')->fetchColumn()===$indicadoresAntes
        && (int)$env->db->query('SELECT COUNT(*) FROM asistencias_profesores')->fetchColumn()===$asistenciasAntes,'CASO 8: rollback elimina clases, indicadores y asistencia conjunta');
    echo "RESUMEN | $checks PASS | 0 FAIL (BD temporal)\n";
} finally { $env->close(); }
