<?php
require_once __DIR__ . '/../config/api_auth.php';
require_once __DIR__ . '/PlanificacionPedagogica.php';

class ClaseDiaria
{
    private PDO $db;
    public function __construct(PDO $db){$this->db=$db;}

    public function procesarMarcaProfesor(string $userIdGlobal,int $idAula,string $momento): ?int
    {
        $fecha=$this->momento($momento);
        $stmt=$this->db->prepare("SELECT h.id_horario,h.id_asignacion,h.hora_inicio,h.hora_fin
            FROM profesores p
            JOIN asignacion_docente ad ON ad.id_profesor=p.id_profesor AND ad.activo=1
            JOIN horarios h ON h.id_asignacion=ad.id_asignacion AND h.activo=1
            WHERE p.user_id_global=:usuario AND p.activo=1 AND h.id_aula=:aula
              AND ad.anio_lectivo=YEAR(:momento)
              AND WEEKDAY(DATE(:momento))=CASE LEFT(h.dia_semana,2)
                WHEN 'Lu' THEN 0 WHEN 'Ma' THEN 1 WHEN 'Mi' THEN 2 WHEN 'Ju' THEN 3
                WHEN 'Vi' THEN 4 WHEN 'Sa' THEN 5 WHEN 'Sá' THEN 5 ELSE -1 END
              AND TIME(:momento)>=SUBTIME(h.hora_inicio,'00:10:00') AND TIME(:momento)<h.hora_fin
            ORDER BY (TIME(:momento)>=h.hora_inicio) DESC, ABS(TIME_TO_SEC(TIMEDIFF(TIME(:momento),h.hora_inicio))),h.id_horario LIMIT 1");
        $stmt->execute([':usuario'=>$userIdGlobal,':aula'=>$idAula,':momento'=>$fecha]);
        $horario=$stmt->fetch(PDO::FETCH_ASSOC);
        if(!$horario)return null;
        return $this->crearORecuperar((int)$horario['id_asignacion'],(int)$horario['id_horario'],substr($fecha,0,10),$horario['hora_inicio'],$horario['hora_fin']);
    }

    public function crearManual(int $idAsignacion,string $fecha,?int $idHorario=null): int
    {
        $fecha=$this->fecha($fecha);
        $sql="SELECT id_horario,hora_inicio,hora_fin FROM horarios WHERE id_asignacion=:asignacion AND activo=1
              AND WEEKDAY(:fecha)=CASE LEFT(dia_semana,2) WHEN 'Lu' THEN 0 WHEN 'Ma' THEN 1 WHEN 'Mi' THEN 2 WHEN 'Ju' THEN 3 WHEN 'Vi' THEN 4 WHEN 'Sa' THEN 5 WHEN 'Sá' THEN 5 ELSE -1 END";
        $p=[':asignacion'=>$idAsignacion,':fecha'=>$fecha];
        if($idHorario){$sql.=' AND id_horario=:horario';$p[':horario']=$idHorario;}
        $sql.=' ORDER BY hora_inicio,id_horario LIMIT 1';$s=$this->db->prepare($sql);$s->execute($p);$h=$s->fetch(PDO::FETCH_ASSOC);
        if(!$h)throw new PedagogiaException('No existe un horario activo para la asignacion en esa fecha.',409);
        return $this->crearORecuperar($idAsignacion,(int)$h['id_horario'],$fecha,$h['hora_inicio'],$h['hora_fin']);
    }

    public function crearORecuperar(int $idAsignacion,int $idHorario,string $fecha,string $inicio,string $fin): int
    {
        $propia=!$this->db->inTransaction();if($propia)$this->db->beginTransaction();
        try{
            $contenido=$this->contenidoProgramado($idAsignacion,$fecha);
            $stmt=$this->db->prepare("INSERT INTO clases_diarias
                (id_asignacion,id_horario,id_plan,id_unidad,id_capacidad,id_tema,fecha,hora_inicio,hora_fin)
                VALUES (:asignacion,:horario,:plan,:unidad,:capacidad,:tema,:fecha,:inicio,:fin)
                ON DUPLICATE KEY UPDATE id_clase=LAST_INSERT_ID(id_clase),
                  id_horario=COALESCE(id_horario,VALUES(id_horario)),
                  id_plan=COALESCE(id_plan,VALUES(id_plan)),id_unidad=COALESCE(id_unidad,VALUES(id_unidad)),
                  id_capacidad=COALESCE(id_capacidad,VALUES(id_capacidad)),id_tema=COALESCE(id_tema,VALUES(id_tema))");
            $stmt->execute([':asignacion'=>$idAsignacion,':horario'=>$idHorario,':plan'=>$contenido['id_plan']??null,':unidad'=>$contenido['id_unidad']??null,':capacidad'=>$contenido['id_capacidad']??null,':tema'=>$contenido['id_tema']??null,':fecha'=>$fecha,':inicio'=>$inicio,':fin'=>$fin]);
            $id=(int)$this->db->lastInsertId();
            if(!$id){$q=$this->db->prepare('SELECT id_clase FROM clases_diarias WHERE id_asignacion=:a AND fecha=:f AND hora_inicio=:i AND hora_fin=:n');$q->execute([':a'=>$idAsignacion,':f'=>$fecha,':i'=>$inicio,':n'=>$fin]);$id=(int)$q->fetchColumn();}
            if(!empty($contenido['id_tema']))$this->copiarIndicadores($id,(int)$contenido['id_tema']);
            if($propia)$this->db->commit();return$id;
        }catch(Throwable $e){if($propia&&$this->db->inTransaction())$this->db->rollBack();throw$e;}
    }

    public function asignarTema(int $idClase,int $idTema): void
    {
        $clase=$this->fila('SELECT id_asignacion FROM clases_diarias WHERE id_clase=:id',[':id'=>$idClase]);
        if(!$clase)throw new PedagogiaException('Clase no encontrada.',404);
        $tema=$this->fila("SELECT t.id_tema,c.id_capacidad,u.id_unidad,u.id_plan FROM plan_temas t
            JOIN plan_capacidades c ON c.id_capacidad=t.id_capacidad JOIN plan_unidades u ON u.id_unidad=c.id_unidad
            JOIN planes_anuales p ON p.id_plan=u.id_plan WHERE t.id_tema=:tema AND p.id_asignacion=:asignacion AND p.estado='PUBLICADO'",[':tema'=>$idTema,':asignacion'=>$clase['id_asignacion']]);
        if(!$tema)throw new PedagogiaException('El tema no pertenece a un plan publicado de la asignacion.',409);
        $this->db->beginTransaction();
        try{$this->db->prepare('UPDATE clases_diarias SET id_plan=:plan,id_unidad=:unidad,id_capacidad=:capacidad,id_tema=:tema WHERE id_clase=:clase')->execute([':plan'=>$tema['id_plan'],':unidad'=>$tema['id_unidad'],':capacidad'=>$tema['id_capacidad'],':tema'=>$idTema,':clase'=>$idClase]);$this->db->prepare('DELETE FROM clase_diaria_indicadores WHERE id_clase=:id')->execute([':id'=>$idClase]);$this->copiarIndicadores($idClase,$idTema);$this->db->commit();}
        catch(Throwable $e){if($this->db->inTransaction())$this->db->rollBack();throw$e;}
    }

    public function temasPublicados(int $idAsignacion): array
    {
        return $this->filas("SELECT t.id_tema,t.titulo,c.descripcion capacidad,u.nombre unidad,p.id_plan
            FROM planes_anuales p JOIN plan_unidades u ON u.id_plan=p.id_plan
            JOIN plan_capacidades c ON c.id_unidad=u.id_unidad JOIN plan_temas t ON t.id_capacidad=c.id_capacidad
            WHERE p.id_asignacion=:id AND p.estado='PUBLICADO' ORDER BY u.orden,c.orden,t.orden",[':id'=>$idAsignacion]);
    }

    public function informe(int $idClase): array
    {
        $clase=$this->fila("SELECT cd.*,ad.id_profesor,ad.id_materia,ad.id_grado,
            CONCAT(p.nombre,' ',p.apellido) profesor,m.nombre materia,g.nombre grado,a.nombre aula,g.id_aula,
            u.nombre unidad,c.descripcion capacidad,t.titulo tema,t.contenido
            FROM clases_diarias cd JOIN asignacion_docente ad ON ad.id_asignacion=cd.id_asignacion
            JOIN profesores p ON p.id_profesor=ad.id_profesor JOIN materias m ON m.id_materia=ad.id_materia
            JOIN grados g ON g.id_grado=ad.id_grado JOIN aulas a ON a.id_aula=g.id_aula
            LEFT JOIN plan_unidades u ON u.id_unidad=cd.id_unidad LEFT JOIN plan_capacidades c ON c.id_capacidad=cd.id_capacidad
            LEFT JOIN plan_temas t ON t.id_tema=cd.id_tema WHERE cd.id_clase=:id",[':id'=>$idClase]);
        if(!$clase)throw new PedagogiaException('Clase diaria no encontrada.',404);
        $clase['indicadores']=$this->filas("SELECT i.id_indicador,i.descripcion,ci.cumplido,ci.observaciones
            FROM clase_diaria_indicadores ci JOIN plan_indicadores i ON i.id_indicador=ci.id_indicador
            WHERE ci.id_clase=:id ORDER BY i.orden,i.id_indicador",[':id'=>$idClase]);
        $clase['estudiantes']=$this->filas("SELECT e.id_estudiante,e.nombre,e.apellido,
            CASE WHEN EXISTS(SELECT 1 FROM eventos_asistencia ea WHERE ea.user_id_global=e.user_id_global AND ea.activo=1
                AND DATE(ea.timestamp_evento)=cd.fecha AND TIME(ea.timestamp_evento)>=cd.hora_inicio AND TIME(ea.timestamp_evento)<cd.hora_fin
                AND (ea.id_aula=g.id_aula OR ea.id_aula IS NULL))
              OR EXISTS(SELECT 1 FROM asistencias_estudiantes ae WHERE ae.id_estudiante=e.id_estudiante AND ae.activo=1
                AND ae.fecha=cd.fecha AND ae.hora>=cd.hora_inicio AND ae.hora<cd.hora_fin AND ae.estado IN ('PRESENTE','TARDANZA'))
              THEN 'PRESENTE' ELSE 'AUSENTE' END estado,
            (SELECT ae.id_asistencia_estudiante FROM asistencias_estudiantes ae WHERE ae.id_estudiante=e.id_estudiante AND ae.activo=1
                AND ae.fecha=cd.fecha AND ae.hora>=cd.hora_inicio AND ae.hora<cd.hora_fin ORDER BY ae.hora LIMIT 1) id_asistencia_estudiante,
            ra.observacion
            FROM clases_diarias cd JOIN asignacion_docente ad ON ad.id_asignacion=cd.id_asignacion
            JOIN grados g ON g.id_grado=ad.id_grado JOIN estudiantes e ON e.id_grado=g.id_grado AND e.activo=1
            LEFT JOIN registros_anecdoticos ra ON ra.id_clase=cd.id_clase AND ra.id_estudiante=e.id_estudiante
            WHERE cd.id_clase=:id ORDER BY e.apellido,e.nombre,e.id_estudiante",[':id'=>$idClase]);
        $clase['sin_contenido']=empty($clase['id_tema']);
        return $clase;
    }

    public function buscarClases(array $filtros): array
    {
        $sql="SELECT cd.id_clase,cd.id_asignacion,cd.id_horario,cd.fecha,cd.hora_inicio,cd.hora_fin,
            m.nombre materia,g.nombre grado,CONCAT(p.nombre,' ',p.apellido) profesor,
            COALESCE(t.titulo,'Sin contenido programado para esta fecha') tema
            FROM clases_diarias cd JOIN asignacion_docente ad ON ad.id_asignacion=cd.id_asignacion
            JOIN profesores p ON p.id_profesor=ad.id_profesor JOIN materias m ON m.id_materia=ad.id_materia
            JOIN grados g ON g.id_grado=ad.id_grado LEFT JOIN plan_temas t ON t.id_tema=cd.id_tema WHERE 1=1";$p=[];
        foreach(['fecha'=>'cd.fecha','id_materia'=>'ad.id_materia','id_grado'=>'ad.id_grado','id_profesor'=>'ad.id_profesor','id_asignacion'=>'cd.id_asignacion'] as $k=>$col){if(!empty($filtros[$k])){$sql.=" AND $col=:$k";$p[":$k"]=$filtros[$k];}}
        $sql.=' ORDER BY cd.fecha DESC,cd.hora_inicio,m.nombre,g.nombre';$s=$this->db->prepare($sql);$s->execute($p);return$s->fetchAll(PDO::FETCH_ASSOC);
    }

    public function guardarRegistro(int $idClase,int $idEstudiante,?int $idAsistencia,string $observacion,int $creador): void
    {
        $existe=$this->fila("SELECT 1 FROM clases_diarias cd JOIN asignacion_docente ad ON ad.id_asignacion=cd.id_asignacion
            JOIN estudiantes e ON e.id_grado=ad.id_grado AND e.id_estudiante=:estudiante AND e.activo=1 WHERE cd.id_clase=:clase",[':estudiante'=>$idEstudiante,':clase'=>$idClase]);
        if(!$existe)throw new PedagogiaException('El estudiante no pertenece al grado de la clase.',409);
        $observacion=trim($observacion);
        if(mb_strlen($observacion)>10000)throw new PedagogiaException('La observacion es demasiado extensa.');
        if($observacion===''){$this->db->prepare('DELETE FROM registros_anecdoticos WHERE id_clase=:clase AND id_estudiante=:estudiante')->execute([':clase'=>$idClase,':estudiante'=>$idEstudiante]);return;}
        $this->db->prepare("INSERT INTO registros_anecdoticos (id_clase,id_estudiante,id_asistencia_estudiante,observacion,created_by)
            VALUES (:clase,:estudiante,:asistencia,:observacion,:creador)
            ON DUPLICATE KEY UPDATE id_asistencia_estudiante=VALUES(id_asistencia_estudiante),observacion=VALUES(observacion),created_by=VALUES(created_by)")
            ->execute([':clase'=>$idClase,':estudiante'=>$idEstudiante,':asistencia'=>$idAsistencia,':observacion'=>$observacion,':creador'=>$creador]);
    }

    private function contenidoProgramado(int $idAsignacion,string $fecha): ?array
    {
        return $this->fila("SELECT p.id_plan,u.id_unidad,c.id_capacidad,t.id_tema
            FROM planes_anuales p JOIN plan_unidades u ON u.id_plan=p.id_plan
            JOIN plan_capacidades c ON c.id_unidad=u.id_unidad JOIN plan_temas t ON t.id_capacidad=c.id_capacidad
            JOIN plan_tema_programacion pr ON pr.id_tema=t.id_tema
            WHERE p.id_asignacion=:asignacion AND p.estado='PUBLICADO' AND :fecha BETWEEN pr.fecha_inicio AND pr.fecha_fin
            ORDER BY DATEDIFF(pr.fecha_fin,pr.fecha_inicio),pr.orden,u.orden,c.orden,t.orden,pr.id_programacion LIMIT 1",[':asignacion'=>$idAsignacion,':fecha'=>$fecha]);
    }
    private function copiarIndicadores(int $clase,int $tema): void{$this->db->prepare('INSERT IGNORE INTO clase_diaria_indicadores (id_clase,id_indicador) SELECT :clase,id_indicador FROM plan_indicadores WHERE id_tema=:tema')->execute([':clase'=>$clase,':tema'=>$tema]);}
    private function momento(string $v): string{$d=DateTimeImmutable::createFromFormat('!Y-m-d H:i:s',$v);if(!$d||$d->format('Y-m-d H:i:s')!==$v)throw new PedagogiaException('Fecha y hora no validas.');return$v;}
    private function fecha(string $v): string{$d=DateTimeImmutable::createFromFormat('!Y-m-d',$v);if(!$d||$d->format('Y-m-d')!==$v)throw new PedagogiaException('Fecha no valida.');return$v;}
    private function filas(string $q,array $p=[]):array{$s=$this->db->prepare($q);$s->execute($p);return$s->fetchAll(PDO::FETCH_ASSOC);}
    private function fila(string $q,array $p=[]):?array{$s=$this->db->prepare($q);$s->execute($p);$x=$s->fetch(PDO::FETCH_ASSOC);return$x?:null;}
}
?>
