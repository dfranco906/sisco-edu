<?php
declare(strict_types=1);
require_once __DIR__.'/../support/TestEnvironment.php';
require_once dirname(__DIR__,3).'/src/classes/PlanificacionPedagogica.php';
$env=new PlanImportTestEnvironment();$checks=0;
function coverageCheck(bool $ok,string $message):void{global $checks;if(!$ok)throw new RuntimeException($message);$checks++;echo 'OK | '.$message.PHP_EOL;}
try{
    $service=new PlanificacionPedagogica($env->db,['id_usuario'=>$env->user,'rol'=>'Profesor']);
    $id=$service->crearPlan(['id_asignacion'=>$env->assignment,'anio'=>2026]);
    $post=fn(string $endpoint,array $data)=>$env->request($endpoint,'POST',json_encode($data));
    $publish=fn()=>$post('planes.php',['accion'=>'estado','id_plan'=>$id,'estado'=>'PUBLICADO']);
    coverageCheck($publish()['status']===409,'plan sin temas no publicable');
    $unit=$service->guardarElemento('unidad',['id_plan'=>$id,'nombre'=>'Unidad temporal']);
    $cap=$service->guardarElemento('capacidad',['id_unidad'=>$unit,'descripcion'=>'Capacidad temporal']);
    $rows=[];$indicators=[];
    for($i=0;$i<10;$i++){
        $topic=$service->guardarElemento('tema',['id_capacidad'=>$cap,'titulo'=>'Tema '.($i+1)]);
        if($i<9)$indicators[]=$service->guardarElemento('indicador',['id_tema'=>$topic,'descripcion'=>'Indicador '.($i+1)]);
        $date=(new DateTimeImmutable('2026-10-01'))->modify('+'.$i.' days')->format('Y-m-d');
        $rows[]=['id_tema'=>$topic,'fecha_inicio'=>$date,'fecha_fin'=>$date];
    }
    $cov=$service->obtenerPlan($id)['cobertura'];
    coverageCheck($cov['total_temas']===10 && $cov['temas_con_indicadores']===9 && $cov['temas_sin_indicadores']===1 && $cov['temas_sin_programacion']===10,'cobertura total y faltantes calculados');
    coverageCheck($publish()['status']===409 && str_contains($publish()['json']['message'],'indicadores'),'publicación exige indicadores de todos los temas');
    $indicators[]=$service->guardarElemento('indicador',['id_tema'=>$rows[9]['id_tema'],'descripcion'=>'Indicador 10']);
    $service->guardarProgramacion($rows[0]);
    coverageCheck($publish()['status']===409 && str_contains($publish()['json']['message'],'9 de 10'),'publicación informa exactamente los temas pendientes');
    $before=$env->db->query('SELECT * FROM plan_tema_programacion ORDER BY id_programacion')->fetchAll(PDO::FETCH_ASSOC);
    foreach(['','2026-02-30','2025-10-01'] as $bad){
        $response=$post('programacion.php',['accion'=>'guardar']+array_replace($rows[1],['fecha_inicio'=>$bad]));
        coverageCheck($response['status']===409,'fecha vacía, imposible o fuera del año rechazada por API');
    }
    $response=$post('programacion.php',['accion'=>'guardar']+array_replace($rows[1],['fecha_inicio'=>'2026-10-05']));
    coverageCheck($response['status']===409,'inicio posterior al fin rechazado');
    coverageCheck($post('programacion.php',['accion'=>'guardar']+$rows[0])['status']===409,'programación duplicada rechazada');
    coverageCheck($post('programacion.php',['accion'=>'guardar']+array_replace($rows[1],['fecha_inicio'=>'2026-10-01','fecha_fin'=>'2026-10-05']))['status']===409,'solapamiento entre temas distintos rechazado manualmente');
    $bad=$rows;$bad[7]['fecha_inicio']='';
    coverageCheck($post('programacion.php',['accion'=>'guardar_anual','id_plan'=>$id,'programaciones'=>$bad])['status']===409,'fila 8 inválida rechaza todo el guardado masivo');
    coverageCheck($env->db->query('SELECT * FROM plan_tema_programacion ORDER BY id_programacion')->fetchAll(PDO::FETCH_ASSOC)===$before,'fallo de validación preserva íntegra la programación anterior');
    $env->db->exec("CREATE TRIGGER annual_fail_row8 BEFORE INSERT ON plan_tema_programacion FOR EACH ROW BEGIN IF NEW.id_tema=".$rows[7]['id_tema']." THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='fallo fila 8'; END IF; END");
    try{coverageCheck($post('programacion.php',['accion'=>'guardar_anual','id_plan'=>$id,'programaciones'=>$rows])['status']===500,'fallo SQL en fila 8 controlado');}
    finally{$env->db->exec('DROP TRIGGER annual_fail_row8');}
    coverageCheck($env->db->query('SELECT * FROM plan_tema_programacion ORDER BY id_programacion')->fetchAll(PDO::FETCH_ASSOC)===$before,'rollback restaura programación anterior después de insertar 7 filas');
    coverageCheck($post('programacion.php',['accion'=>'guardar_anual','id_plan'=>$id,'programaciones'=>$rows])['status']===200,'guardar programación anual completa');
    $cov=$service->obtenerPlan($id)['cobertura'];coverageCheck($cov['can_publish'] && $cov['temas_programados']===10,'cobertura completa permite publicar');
    $env->db->prepare('UPDATE plan_tema_programacion SET fecha_inicio=?,fecha_fin=? WHERE id_tema=?')->execute(['2025-10-01','2025-10-01',$rows[0]['id_tema']]);
    coverageCheck($service->obtenerPlan($id)['cobertura']['fechas_invalidas']===1 && $publish()['status']===409,'publicación detecta fecha histórica inválida aunque se haya insertado fuera de la API');
    $service->guardarProgramaciones($id,$rows);
    $env->db->prepare('UPDATE plan_tema_programacion SET fecha_inicio=?,fecha_fin=? WHERE id_tema=?')->execute(['2026-10-01','2026-10-02',$rows[1]['id_tema']]);
    coverageCheck($service->obtenerPlan($id)['cobertura']['solapamientos']===1 && $publish()['status']===409,'publicación detecta solapamiento histórico');
    $service->guardarProgramaciones($id,$rows);
    coverageCheck($publish()['status']===200,'publicación completa por HTTP');
    $program=(int)$env->db->query('SELECT MIN(id_programacion) FROM plan_tema_programacion')->fetchColumn();
    coverageCheck($post('programacion.php',['accion'=>'eliminar','id_programacion'=>$program])['status']===409,'un plan publicado no pierde cobertura al borrar su último período de un tema');
    coverageCheck($post('estructura.php',['accion'=>'eliminar','tipo'=>'indicador','id'=>$indicators[0],'confirmado'=>true])['status']===409,'un plan publicado no pierde su último indicador de un tema');
    $service->cambiarEstado($id,'ARCHIVADO');
    $mutations=[
        ['planes.php',['accion'=>'actualizar','id_plan'=>$id,'competencia_general'=>'Cambio prohibido']],
        ['planes.php',['accion'=>'estado','id_plan'=>$id,'estado'=>'PUBLICADO']],
        ['planes.php',['accion'=>'estado','id_plan'=>$id,'estado'=>'BORRADOR']],
        ['estructura.php',['accion'=>'guardar','tipo'=>'unidad','id_plan'=>$id,'nombre'=>'Prohibida']],
        ['estructura.php',['accion'=>'eliminar','tipo'=>'tema','id'=>$rows[0]['id_tema'],'confirmado'=>true]],
        ['estructura.php',['accion'=>'reordenar','tipo'=>'tema','ids'=>array_column($rows,'id_tema')]],
        ['estructura.php',['accion'=>'evaluacion','tipo'=>'tema','id_tema'=>$rows[0]['id_tema'],'procedimientos'=>[],'instrumentos'=>[]]],
        ['programacion.php',['accion'=>'guardar']+$rows[0]],
        ['programacion.php',['accion'=>'guardar','id_programacion'=>$program]+$rows[0]],
        ['programacion.php',['accion'=>'guardar_anual','id_plan'=>$id,'programaciones'=>$rows]],
        ['programacion.php',['accion'=>'eliminar','id_programacion'=>$program]],
    ];
    $snapshot=$service->obtenerPlan($id);
    foreach($mutations as [$endpoint,$body])coverageCheck($post($endpoint,$body)['status']===409,'ARCHIVADO bloquea '.$endpoint.' / '.$body['accion']);
    coverageCheck($service->obtenerPlan($id)===$snapshot,'ARCHIVADO conserva cabecera, estructura, evaluación y programación');
}finally{$env->close();}
echo "OK | PublicationCoverageTest | $checks PASS; 0 FAIL (HTTP, cobertura, atomicidad, publicación y archivo; BD temporal eliminada)\n";
