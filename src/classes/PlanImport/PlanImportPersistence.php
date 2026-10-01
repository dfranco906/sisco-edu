<?php
declare(strict_types=1);
namespace SiscoEdu\PlanImport;

final class PlanImportPersistence
{
    public function __construct(private readonly \PDO $db,private readonly ?\Closure $failureInjector=null){}

    public function persist(array $plan,int $assignment,int $year,int $user,array $decisions,array $programming):int
    {
        if(!$this->db->inTransaction())throw new PlanImportException('La importación requiere una transacción externa.','TRANSACTION_REQUIRED',6,500);
        CanonicalPlan::assertValid($plan);$this->validateLengths($plan);
        foreach($plan['unidades'] as $u)foreach($u['capacidades'] as $c)foreach($c['temas'] as $t)foreach($t['indicadores'] as $i)if($i['check']!==null)throw new PlanImportException('CHECK representa avance y no una definición del plan.','CHECK_IS_PROGRESS_NOT_PLAN_DEFINITION');
        $assignmentRow=$this->row('SELECT id_asignacion,anio_lectivo,activo FROM asignacion_docente WHERE id_asignacion=? FOR UPDATE',[$assignment]);
        if(!$assignmentRow||(int)$assignmentRow['activo']!==1)throw new PlanImportException('La asignación no está activa.','INVALID_ASSIGNMENT',6,403);
        if((int)$assignmentRow['anio_lectivo']!==$year)throw new PlanImportException('El año no coincide con la asignación.','ASSIGNMENT_YEAR_MISMATCH');
        $existing=$this->row('SELECT id_plan,importacion_origen_json FROM planes_anuales WHERE id_asignacion=? AND anio=? FOR UPDATE',[$assignment,$year]);
        if($existing){$origin=json_decode((string)$existing['importacion_origen_json'],true);$same=is_array($origin)&&($origin['source']['sha256']??null)===$plan['source']['sha256'];throw new PlanImportException($same?'Este PDF ya fue importado para la asignación y año.':'Ya existe un plan para la asignación y año.',$same?'DUPLICATE_IMPORT':'PLAN_ALREADY_EXISTS',6,409,['id_plan'=>(int)$existing['id_plan']]);}
        $catalogs=['procedimientos'=>$this->rows('SELECT id_procedimiento id,nombre FROM procedimientos_evaluativos WHERE activo=1',[]),'instrumentos'=>$this->rows('SELECT id_instrumento id,nombre FROM instrumentos_evaluativos WHERE activo=1',[])];
        $suggestions=(new CatalogMatcher())->suggestions($plan,$catalogs);$decisions=(new CatalogMatcher())->validate($decisions,$suggestions,$catalogs,true);
        $programming=(new ProgrammingValidator())->validate($programming,$plan,$year);
        $decisionMap=[];foreach($decisions as $d)$decisionMap[$d['kind'].':'.$d['text']]=$d;
        $origin=['schema_version'=>$plan['schema_version'],'source'=>$plan['source'],'warnings'=>$plan['warnings'],'confidence'=>$plan['confidence']];if(isset($plan['debug']))$origin['debug']=$plan['debug'];
        $m=$plan['metadata'];
        $this->db->prepare('INSERT INTO planes_anuales (id_asignacion,anio,competencia_general,competencia_especifica,institucion_fuente,materia_fuente,profesor_fuente,curso_fuente,turno_fuente,anio_fuente,dias_clase_fuente,importacion_origen_json,estado,created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,\'BORRADOR\',?)')->execute([$assignment,$year,$m['competencia_general'],$m['competencia_especifica'],$m['institucion'],$m['materia'],$m['profesor'],$m['curso'],$m['turno'],$m['anio'],$m['dias_clase'],json_encode($origin,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR),$user]);
        $planId=(int)$this->db->lastInsertId();$this->checkpoint('plan');$topicIds=[];
        foreach($plan['unidades'] as $u){
            $this->db->prepare('INSERT INTO plan_unidades (id_plan,codigo,nombre,descripcion,orden,horas_catedra,tiempo_texto,proceso_texto,area_transversal,metodologia,medios_verificacion) VALUES (?,?,?,?,?,?,?,?,?,?,?)')->execute([$planId,$u['codigo'],$u['nombre'],$u['descripcion'],$u['orden'],$u['horas_catedra'],$u['tiempo_texto'],$u['proceso_texto'],$u['area_transversal'],$u['metodologia'],$u['medios_verificacion']]);$unitId=(int)$this->db->lastInsertId();$this->checkpoint('unit');
            foreach($u['capacidades'] as $c){$this->db->prepare('INSERT INTO plan_capacidades (id_unidad,descripcion,proceso_desarrollo,orden) VALUES (?,?,?,?)')->execute([$unitId,$c['descripcion'],$c['proceso_desarrollo'],$c['orden']]);$capacityId=(int)$this->db->lastInsertId();
                foreach($c['temas'] as $t){$this->db->prepare('INSERT INTO plan_temas (id_capacidad,codigo,titulo,contenido,horas_catedra,tiempo_texto,fecha_texto,orden) VALUES (?,?,?,?,?,?,?,?)')->execute([$capacityId,$t['codigo'],$t['titulo'],$t['contenido'],$t['horas_catedra'],$t['tiempo_texto'],$t['fecha_texto'],$t['orden']]);$topicId=(int)$this->db->lastInsertId();$topicIds[$u['orden'].':'.$c['orden'].':'.$t['orden']]=$topicId;$this->checkpoint('topic');
                    foreach($t['indicadores'] as $i)$this->db->prepare('INSERT INTO plan_indicadores (id_tema,codigo,descripcion,orden) VALUES (?,?,?,?)')->execute([$topicId,$i['codigo'],$i['descripcion'],$i['orden']]);
                    $this->persistRelations($topicId,$t['procedimientos_evaluativos'],'procedimientos',$decisionMap);$this->persistRelations($topicId,$t['instrumentos_evaluativos'],'instrumentos',$decisionMap);$this->checkpoint('catalog');
                }
            }
        }
        $programOrder=[];foreach($programming as $p){$key=$p['unidad_orden'].':'.$p['capacidad_orden'].':'.$p['tema_orden'];$order=($programOrder[$key]??0)+1;$programOrder[$key]=$order;$this->db->prepare('INSERT INTO plan_tema_programacion (id_tema,fecha_inicio,fecha_fin,horas_catedra_planificadas,observaciones,orden) VALUES (?,?,?,?,?,?)')->execute([$topicIds[$key],$p['fecha_inicio'],$p['fecha_fin'],$p['horas_catedra_planificadas'],$p['observaciones'],$order]);$this->checkpoint('programming');}
        return $planId;
    }

    private function persistRelations(int $topicId,array $texts,string $kind,array $decisions):void
    {
        [$table,$pk,$bridge]=$kind==='procedimientos'?['procedimientos_evaluativos','id_procedimiento','plan_tema_procedimientos']:['instrumentos_evaluativos','id_instrumento','plan_tema_instrumentos'];
        foreach(array_values($texts) as $index=>$text){$decision=$decisions[$kind.':'.$text]??null;if(!$decision)throw new PlanImportException('Falta una decisión de catálogo.','CATALOG_DECISION_REQUIRED');if($decision['action']==='exclude')continue;
            if($decision['action']==='existing')$id=(int)$decision['id'];else{$this->db->prepare("INSERT INTO $table (nombre) VALUES (?) ON DUPLICATE KEY UPDATE $pk=LAST_INSERT_ID($pk)")->execute([$text]);$id=(int)$this->db->lastInsertId();if(!$id){$row=$this->row("SELECT $pk id FROM $table WHERE nombre=?",[$text]);$id=(int)($row['id']??0);}if(!$id)throw new PlanImportException('No se pudo resolver el catálogo.','CATALOG_PERSISTENCE_FAILED',6,500);}
            $this->db->prepare("INSERT INTO $bridge (id_tema,$pk,texto_fuente,orden) VALUES (?,?,?,?)")->execute([$topicId,$id,$text,$index+1]);
        }
    }
    private function validateLengths(array $plan):void
    {
        $m=$plan['metadata'];foreach([['institucion',255],['materia',255],['profesor',255],['curso',100],['turno',100],['dias_clase',255]] as [$key,$max])if($m[$key]!==null&&mb_strlen($m[$key])>$max)throw new PlanImportException('El campo '.$key.' supera el límite sin pérdida.','VALUE_TOO_LONG');
        foreach($plan['unidades'] as $u){if(mb_strlen($u['nombre'])>255)throw new PlanImportException('Nombre de unidad demasiado largo.','VALUE_TOO_LONG');foreach($u['capacidades'] as $c)foreach($c['temas'] as $t){if(mb_strlen($t['titulo'])>255)throw new PlanImportException('Título de tema demasiado largo.','VALUE_TOO_LONG');foreach(array_merge($t['procedimientos_evaluativos'],$t['instrumentos_evaluativos']) as $text)if(mb_strlen($text)>150)throw new PlanImportException('Texto de catálogo demasiado largo.','VALUE_TOO_LONG');}}
    }
    private function checkpoint(string $point):void{if($this->failureInjector)($this->failureInjector)($point);}
    private function row(string $sql,array $params):array|false{$s=$this->db->prepare($sql);$s->execute($params);return $s->fetch(\PDO::FETCH_ASSOC);}
    private function rows(string $sql,array $params):array{$s=$this->db->prepare($sql);$s->execute($params);return $s->fetchAll(\PDO::FETCH_ASSOC);}
}
