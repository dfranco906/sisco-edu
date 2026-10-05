<?php
declare(strict_types=1);
require_once __DIR__.'/plan_import/support/TestEnvironment.php';
$env=new PlanImportTestEnvironment();
try{
    $env->insert("INSERT INTO usuarios (nombre,apellido,usuario,email,celular,password,rol,activo) VALUES ('Admin','Temporal','admin1','admin@test.local',981234567,?,'SuperAdmin',1)",[password_hash('123456',PASSWORD_DEFAULT)]);
    $prof=(int)$env->db->query('SELECT id_profesor FROM asignacion_docente WHERE id_asignacion='.$env->assignment)->fetchColumn();
    $mediumProf=$env->insert("INSERT INTO profesores (nombre,apellido,cedula_identidad,user_id_global,activo) VALUES ('Medio','Temporal','SMOKE_MEDIUM','SMOKE_MEDIUM',1)",[]);
    $env->insert("INSERT INTO huellas_templates (user_id_global,id_profesor,fingerprint_data,formato,activo) VALUES ('PDF_TEST',?,?,'HEX',1)",[$prof,str_repeat('A',3072)]);
    file_put_contents($env->directory.'/favicon.ico',base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+j7uoAAAAASUVORK5CYII='));
    $matter=[];foreach(['CULTO','ROBOTICA','MATEMATICA'] as $name)$matter[$name]=$env->insert('INSERT INTO materias (nombre,activo) VALUES (?,1)',[$name]);
    foreach(['7°','8°','9°','2° BCB','3° BCB','2° BTI'] as $index=>$name){
        $aula=$env->insert('INSERT INTO aulas (nombre,codigo,ubicacion) VALUES (?,?,?)',['Aula '.$name,'SMOKE_'.$index,'Temporal']);
        $grade=$env->insert('INSERT INTO grados (nombre,id_aula,room_id) VALUES (?,?,?)',[$name,$aula,'SMOKE_'.$index]);
        foreach($matter as $id)$env->insert('INSERT INTO asignacion_docente (id_profesor,id_materia,id_grado,carga_horaria,anio_lectivo,activo) VALUES (?,?,?,4,2026,1)',[$index<3?$prof:$mediumProf,$id,$grade]);
        $env->insert("INSERT INTO estudiantes (nombre,apellido,cedula_identidad,id_grado,user_id_global,activo) VALUES ('Alumno','Temporal',?,?,?,1)",['SMOKE_'.$index,$grade,'SMOKE_'.$index]);
    }
    $base=$env->db->query('SELECT ad.id_grado,g.id_aula FROM asignacion_docente ad JOIN grados g USING(id_grado) WHERE ad.id_asignacion='.$env->assignment)->fetch(PDO::FETCH_ASSOC);
    $env->insert("INSERT INTO horarios (id_asignacion,id_grado,id_aula,dia_semana,hora_inicio,hora_fin,activo) VALUES (?,?,?,'Lunes','07:00','07:40',1)",[$env->assignment,$base['id_grado'],$base['id_aula']]);
    // Se ejecuta el smoke existente sin enviar formularios, sobre el servidor y DB temporales.
    $vars=getenv();$vars['SISCO_BASE_URL']=$env->url;
    if(empty($vars['CHROME_PATH']))$vars['CHROME_PATH']='C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe';
    $process=proc_open(['node',__DIR__.'/admin_frontend_headless.js'],[0=>['pipe','r'],1=>STDOUT,2=>STDERR],$pipes,dirname(__DIR__),$vars,['bypass_shell'=>true]);
    if(!is_resource($process))throw new RuntimeException('No inició el smoke frontend.');fclose($pipes[0]);$code=proc_close($process);
    if($code!==0)throw new RuntimeException('Smoke frontend falló: '.$code);
}finally{$env->close();}
echo "OK | frontend headless actual: BD temporal eliminada\n";
