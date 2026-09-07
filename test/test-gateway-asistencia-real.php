<?php
declare(strict_types=1);
require_once __DIR__.'/plan_import/support/TestEnvironment.php';

$env = new PlanImportTestEnvironment();
$checks = 0;
function gatewayCheck(bool $ok, string $message): void { global $checks; if (!$ok) throw new RuntimeException($message); $checks++; echo "OK | $message\n"; }
function gatewayInsert(PDO $db, string $sql, array $params=[]): int { $s=$db->prepare($sql); $s->execute($params); return (int)$db->lastInsertId(); }

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
    echo "RESUMEN | $checks PASS | 0 FAIL (BD temporal)\n";
} finally { $env->close(); }
