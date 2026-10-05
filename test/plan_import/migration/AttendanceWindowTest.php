<?php
declare(strict_types=1);
require_once __DIR__.'/../support/TestEnvironment.php';
$env=new PlanImportTestEnvironment();$checks=0;
try{
    require_once $env->directory.'/src/classes/GatewayAttendanceService.php';
    $base=$env->db->query('SELECT ad.id_profesor,ad.id_grado,g.id_aula,p.cedula_identidad,p.user_id_global FROM asignacion_docente ad JOIN grados g USING(id_grado) JOIN profesores p USING(id_profesor) WHERE ad.id_asignacion='.$env->assignment)->fetch(PDO::FETCH_ASSOC);
    $env->insert("INSERT INTO horarios (id_asignacion,id_grado,id_aula,dia_semana,hora_inicio,hora_fin,activo) VALUES (?,?,?,'Lunes','07:00','07:40',1)",[$env->assignment,$base['id_grado'],$base['id_aula']]);
    $env->insert("INSERT INTO huellas_templates (user_id_global,id_profesor,fingerprint_data,formato,activo) VALUES (?,?,?,'HEX',1)",[$base['user_id_global'],$base['id_profesor'],str_repeat('A',3072)]);
    $student=$env->insert("INSERT INTO estudiantes (nombre,apellido,cedula_identidad,id_grado,user_id_global,activo) VALUES ('Alumno','Temporal','WINDOW_STUDENT',?,'WINDOW_UID',1)",[$base['id_grado']]);
    $env->insert("INSERT INTO huellas_templates (user_id_global,id_estudiante,fingerprint_data,formato,activo) VALUES ('WINDOW_UID',?,?,'HEX',1)",[$student,str_repeat('A',3072)]);
    $service=new GatewayAttendanceService($env->db);
    $cases=[
        ['A','06:50:00','07:05:00',true],['B','06:55:00','07:06:00',true],['C','07:05:00','07:14:00',true],
        ['D','06:50:00','07:10:01',false],['E','07:35:00','07:40:01',false],
        ['antes del inicio','06:50:00','06:59:59',false],['fin inclusivo','07:35:00','07:40:00',true],
        ['límite de 10 minutos','07:05:00','07:15:00',true],['fuera de límite','07:05:00','07:15:01',false],
    ];
    foreach($cases as $offset=>[$label,$teacherTime,$studentTime,$expected]){
        $date=(new DateTimeImmutable('2026-10-05'))->modify('+'.($offset*7).' days')->format('Y-m-d');
        $teacher=$service->registrar('profesor',$base['cedula_identidad'],(int)$base['id_aula'],new DateTimeImmutable($date.' '.$teacherTime));
        if(!($teacher['ok']??false))throw new RuntimeException('No abrió la clase '.$label);
        $mark=$service->registrar('estudiante','WINDOW_STUDENT',(int)$base['id_aula'],new DateTimeImmutable($date.' '.$studentTime));
        if(($mark['ok']??false)!==$expected)throw new RuntimeException('Ventana incorrecta '.$label);
        if(!$expected && $mark['status']!=='sin_clase_activa')throw new RuntimeException('Rechazo incorrecto '.$label);
        $checks++;echo 'OK | ventana '.$label.' profesor '.$teacherTime.' / alumno '.$studentTime.PHP_EOL;
        if($expected){
            $repeat=$service->registrar('estudiante','WINDOW_STUDENT',(int)$base['id_aula'],new DateTimeImmutable($date.' '.$studentTime));
            if(!($repeat['data']['idempotente']??false))throw new RuntimeException('Idempotencia '.$label);
            $report=(new ClaseDiaria($env->db))->informe((int)$mark['data']['id_clase']);
            if($report['estudiantes'][0]['estado']!=='PRESENTE')throw new RuntimeException('Informe al límite '.$label);$checks+=2;
        }
    }
    $print=(int)$env->db->query('SELECT id_huella FROM huellas_templates WHERE id_estudiante='.$student)->fetchColumn();
    $env->db->prepare('UPDATE estudiantes SET huella_id=? WHERE id_estudiante=?')->execute([$print,$student]);
    $env->db->prepare('UPDATE huellas_templates SET activo=0 WHERE id_huella=?')->execute([$print]);
    $inactive=$service->registrar('estudiante','WINDOW_STUDENT',(int)$base['id_aula'],new DateTimeImmutable('2026-10-05 07:05:00'));
    if(($inactive['ok']??true)!==false || $inactive['status']!=='huella_no_configurada')throw new RuntimeException('Se aceptó una huella inactiva vinculada.');$checks++;
}finally{$env->close();}
echo "OK | AttendanceWindowTest | $checks PASS; 0 FAIL (A/B/C, fuera de ventana, antes de inicio, fin inclusivo, idempotencia e informe; BD temporal eliminada)\n";
