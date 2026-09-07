<?php
declare(strict_types=1);
require_once __DIR__.'/../plan_import/support/TestEnvironment.php';
$env = new PlanImportTestEnvironment();
$passed = 0;
function jointAssert(bool $condition,string $message): void { global $passed;if(!$condition)throw new RuntimeException($message);$passed++;echo 'OK | '.$message.PHP_EOL; }
try {
    $base=$env->db->query('SELECT * FROM asignacion_docente WHERE id_asignacion='.$env->assignment)->fetch(PDO::FETCH_ASSOC);
    $env->db->exec("UPDATE grados SET nombre='9°' WHERE id_grado=".(int)$base['id_grado']);
    $aula8=$env->insert("INSERT INTO aulas (nombre,codigo,ubicacion) VALUES ('Octavo','AULA8','Test')",[]);
    $grado8=$env->insert("INSERT INTO grados (nombre,id_aula,room_id) VALUES ('8°',?,'GRADO8')",[$aula8]);
    $materiaB=$env->insert("INSERT INTO materias (nombre,activo) VALUES ('ETICA',1)",[]);
    $sql='INSERT INTO asignacion_docente (id_profesor,id_materia,id_grado,carga_horaria,anio_lectivo,activo) VALUES (?,?,?,4,?,1)';
    $otra=$env->insert($sql,[$base['id_profesor'],$materiaB,$grado8,2026]);
    $misma=$env->insert($sql,[$base['id_profesor'],$base['id_materia'],$grado8,2026]);
    $otroAnio=$env->insert($sql,[$base['id_profesor'],$materiaB,$grado8,2027]);
    $body=['id_asignacion'=>$env->assignment,'dia_semana'=>'Viernes','hora_inicio'=>'12:50','hora_fin'=>'13:30','permite_superposicion'=>1,'id_asignaciones_conjuntas'=>(string)$otra,'permite_materias_distintas'=>1,'horarios_csrf'=>$env->csrf];
    $request=fn(array $b,bool $auth=true)=>$env->request('../Horario/crear_horario.php','POST',$b,true,$auth);
    $r=$request(array_replace($body,['permite_materias_distintas'=>0]));jointAssert($r['status']===422,'Otra materia requiere habilitación explícita');
    $r=$request($body,false);jointAssert($r['status']===401,'Nueva modalidad requiere sesión');
    $r=$request(array_replace($body,['horarios_csrf'=>'incorrecto']));jointAssert($r['status']===403,'Token CSRF incorrecto rechazado');
    $r=$request(array_replace($body,['id_asignacion'=>$env->otherAssignment]));jointAssert($r['status']===403,'Profesor no modifica asignación ajena');
    $r=$request(array_replace($body,['id_asignaciones_conjuntas'=>(string)$env->otherAssignment]));jointAssert($r['status']===422,'Profesor distinto o mismo grado rechazado');
    $r=$request(array_replace($body,['id_asignaciones_conjuntas'=>(string)$otroAnio]));jointAssert($r['status']===422,'Año distinto rechazado');
    $r=$request(array_replace($body,['id_asignaciones_conjuntas'=>$otra.','.$misma]));jointAssert($r['status']===422,'Dos materias en un mismo grado rechazadas');
    $env->db->exec('UPDATE asignacion_docente SET activo=0 WHERE id_asignacion='.$otra);
    $r=$request($body);jointAssert($r['status']===422,'Asignación inactiva rechazada');
    $env->db->exec('UPDATE asignacion_docente SET activo=1 WHERE id_asignacion='.$otra);
    $r=$request(array_replace($body,['hora_inicio'=>'09:40','hora_fin'=>'10:10']));jointAssert($r['status']===422,'Recreo sigue protegido');
    jointAssert((int)$env->db->query('SELECT COUNT(*) FROM horarios')->fetchColumn()===0,'Rechazos sin horarios parciales');

    $bloqueo=$env->insert("INSERT INTO horarios (id_asignacion,id_grado,id_aula,dia_semana,hora_inicio,hora_fin,activo) VALUES (?,?,?,'Viernes','12:50','13:30',1)",[$misma,$grado8,$aula8]);
    $r=$request($body);jointAssert($r['status']===409,'No se exime un horario no seleccionado');
    jointAssert((int)$env->db->query('SELECT COUNT(*) FROM horarios')->fetchColumn()===1,'Conflicto conserva sólo horario preexistente');
    $env->db->exec('DELETE FROM horarios WHERE id_horario='.$bloqueo);
    $env->db->exec("CREATE TRIGGER fail_joint BEFORE INSERT ON horarios FOR EACH ROW BEGIN IF NEW.id_grado=$grado8 THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='fallo de prueba'; END IF; END");
    $r=$request($body);jointAssert($r['status']===500,'Fallo SQL controlado');
    jointAssert((int)$env->db->query('SELECT COUNT(*) FROM horarios')->fetchColumn()===0,'Rollback total tras fallar segundo curso');
    $env->db->exec('DROP TRIGGER fail_joint');

    $r=$request($body);jointAssert($r['status']===200 && ($r['json']['success']??false),'Crear conjunta de 9° y 8° con materias distintas');
    $id=(int)$r['json']['data']['id_horario'];
    $rows=$env->db->query('SELECT h.*,ad.id_materia FROM horarios h JOIN asignacion_docente ad USING(id_asignacion) ORDER BY h.id_horario')->fetchAll(PDO::FETCH_ASSOC);
    jointAssert(count($rows)===2 && count(array_unique(array_column($rows,'id_grupo_clase_conjunta')))===1,'Dos horarios en un único grupo');
    jointAssert(count(array_unique(array_column($rows,'id_materia')))===2 && count(array_unique(array_column($rows,'id_aula')))===2,'Cada curso conserva su materia y aula');
    $edited=array_replace($body,['id_horario'=>$id,'hora_inicio'=>'12:30','hora_fin'=>'13:10']);
    $r=$env->request('../Horario/actualizar_horario.php','POST',$edited);
    jointAssert($r['status']===200 && ($r['json']['success']??false),'Editar grupo completo');
    jointAssert((int)$env->db->query("SELECT COUNT(*) FROM horarios WHERE hora_inicio='12:30:00' AND hora_fin='13:10:00'")->fetchColumn()===2,'Franja sincronizada sin duplicar materias');
    $r=$request(array_replace($body,['id_asignaciones_conjuntas'=>'','permite_superposicion'=>0,'hora_inicio'=>'12:30','hora_fin'=>'13:10']));
    jointAssert($r['status']===409,'Sin excepción se sigue rechazando superposición');
    echo "RESUMEN | $passed PASS | 0 FAIL (BD exclusiva, HTTP real)\n";
} finally { $env->close(); }
