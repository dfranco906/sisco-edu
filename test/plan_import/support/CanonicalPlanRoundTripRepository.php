<?php
declare(strict_types=1);

use SiscoEdu\PlanImport\CanonicalPlan;

/** Repositorio de prueba: valida el mapping SQL antes de implementar T20. */
final class CanonicalPlanRoundTripRepository
{
    public function __construct(private PDO $db) {}

    public function persist(array $plan, int $assignment, int $year, int $user, ?string $failurePoint=null): int
    {
        CanonicalPlan::assertValid($plan);
        foreach ($plan['unidades'] as $unit) foreach ($unit['capacidades'] as $capacity)
            foreach ($capacity['temas'] as $topic) foreach ($topic['indicadores'] as $indicator)
                if ($indicator['check'] !== null) throw new RuntimeException('CHECK_IS_PROGRESS_NOT_PLAN_DEFINITION');

        $originData = [
            'schema_version'=>$plan['schema_version'], 'source'=>$plan['source'],
            'warnings'=>$plan['warnings'], 'confidence'=>$plan['confidence'],
        ];
        if (isset($plan['debug'])) $originData['debug']=$plan['debug'];
        $origin = json_encode($originData, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
        $metadata = $plan['metadata'];
        $this->db->beginTransaction();
        try {
            $stmt=$this->db->prepare('INSERT INTO planes_anuales
                (id_asignacion,anio,competencia_general,competencia_especifica,institucion_fuente,materia_fuente,
                 profesor_fuente,curso_fuente,turno_fuente,anio_fuente,dias_clase_fuente,importacion_origen_json,created_by)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)');
            $stmt->execute([$assignment,$year,$metadata['competencia_general'],$metadata['competencia_especifica'],
                $metadata['institucion'],$metadata['materia'],$metadata['profesor'],$metadata['curso'],$metadata['turno'],
                $metadata['anio'],$metadata['dias_clase'],$origin,$user]);
            $planId=(int)$this->db->lastInsertId();
            if ($failurePoint==='plan') throw new RuntimeException('INJECTED_SQL_ROLLBACK_TEST');
            foreach ($plan['unidades'] as $unit) {
                $stmt=$this->db->prepare('INSERT INTO plan_unidades
                    (id_plan,codigo,nombre,descripcion,orden,horas_catedra,tiempo_texto,proceso_texto,area_transversal,metodologia,medios_verificacion)
                    VALUES (?,?,?,?,?,?,?,?,?,?,?)');
                $stmt->execute([$planId,$unit['codigo'],$unit['nombre'],$unit['descripcion'],$unit['orden'],$unit['horas_catedra'],
                    $unit['tiempo_texto'],$unit['proceso_texto'],$unit['area_transversal'],$unit['metodologia'],$unit['medios_verificacion']]);
                $unitId=(int)$this->db->lastInsertId();
                if ($failurePoint==='unit') throw new RuntimeException('INJECTED_SQL_ROLLBACK_TEST');
                foreach ($unit['capacidades'] as $capacity) {
                    $this->db->prepare('INSERT INTO plan_capacidades (id_unidad,descripcion,proceso_desarrollo,orden) VALUES (?,?,?,?)')
                        ->execute([$unitId,$capacity['descripcion'],$capacity['proceso_desarrollo'],$capacity['orden']]);
                    $capacityId=(int)$this->db->lastInsertId();
                    foreach ($capacity['temas'] as $topic) {
                        $this->db->prepare('INSERT INTO plan_temas
                            (id_capacidad,codigo,titulo,contenido,horas_catedra,tiempo_texto,fecha_texto,orden) VALUES (?,?,?,?,?,?,?,?)')
                            ->execute([$capacityId,$topic['codigo'],$topic['titulo'],$topic['contenido'],$topic['horas_catedra'],
                                $topic['tiempo_texto'],$topic['fecha_texto'],$topic['orden']]);
                        $topicId=(int)$this->db->lastInsertId();
                        if ($failurePoint==='topic') throw new RuntimeException('INJECTED_SQL_ROLLBACK_TEST');
                        foreach ($topic['indicadores'] as $indicator) {
                            $this->db->prepare('INSERT INTO plan_indicadores (id_tema,codigo,descripcion,orden) VALUES (?,?,?,?)')
                                ->execute([$topicId,$indicator['codigo'],$indicator['descripcion'],$indicator['orden']]);
                        }
                        $this->persistCatalog($topicId,$topic['procedimientos_evaluativos'],'procedimiento');
                        $this->persistCatalog($topicId,$topic['instrumentos_evaluativos'],'instrumento');
                        if ($failurePoint==='catalog') throw new RuntimeException('INJECTED_SQL_ROLLBACK_TEST');
                    }
                }
            }
            $this->db->commit();
            return $planId;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $e;
        }
    }

    public function reconstruct(int $planId): array
    {
        $head=$this->row('SELECT * FROM planes_anuales WHERE id_plan=?',[$planId]);
        if (!$head) throw new RuntimeException('Plan temporal no encontrado');
        $origin=json_decode((string)$head['importacion_origen_json'],true,128,JSON_THROW_ON_ERROR);
        $plan=[
            'schema_version'=>$origin['schema_version'], 'source'=>$origin['source'],
            'metadata'=>[
                'institucion'=>$head['institucion_fuente'], 'materia'=>$head['materia_fuente'],
                'profesor'=>$head['profesor_fuente'], 'curso'=>$head['curso_fuente'], 'turno'=>$head['turno_fuente'],
                'anio'=>$head['anio_fuente']===null?null:(int)$head['anio_fuente'], 'dias_clase'=>$head['dias_clase_fuente'],
                'competencia_general'=>$head['competencia_general'], 'competencia_especifica'=>$head['competencia_especifica'],
            ],
            'unidades'=>[], 'warnings'=>$origin['warnings'], 'confidence'=>$origin['confidence'],
        ];
        if (isset($origin['debug'])) $plan['debug']=$origin['debug'];
        foreach ($this->rows('SELECT * FROM plan_unidades WHERE id_plan=? ORDER BY orden,id_unidad',[$planId]) as $unitRow) {
            $unit=[
                'orden'=>(int)$unitRow['orden'],'codigo'=>$unitRow['codigo'],'nombre'=>$unitRow['nombre'],'descripcion'=>$unitRow['descripcion'],
                'horas_catedra'=>$this->number($unitRow['horas_catedra']),'tiempo_texto'=>$unitRow['tiempo_texto'],
                'proceso_texto'=>$unitRow['proceso_texto'],'area_transversal'=>$unitRow['area_transversal'],
                'metodologia'=>$unitRow['metodologia'],'medios_verificacion'=>$unitRow['medios_verificacion'],'capacidades'=>[],
            ];
            foreach ($this->rows('SELECT * FROM plan_capacidades WHERE id_unidad=? ORDER BY orden,id_capacidad',[(int)$unitRow['id_unidad']]) as $capacityRow) {
                $capacity=['orden'=>(int)$capacityRow['orden'],'descripcion'=>$capacityRow['descripcion'],
                    'proceso_desarrollo'=>$capacityRow['proceso_desarrollo'],'temas'=>[]];
                foreach ($this->rows('SELECT * FROM plan_temas WHERE id_capacidad=? ORDER BY orden,id_tema',[(int)$capacityRow['id_capacidad']]) as $topicRow) {
                    $topic=['orden'=>(int)$topicRow['orden'],'codigo'=>$topicRow['codigo'],'titulo'=>$topicRow['titulo'],
                        'contenido'=>$topicRow['contenido'],'horas_catedra'=>$this->number($topicRow['horas_catedra']),
                        'tiempo_texto'=>$topicRow['tiempo_texto'],'fecha_texto'=>$topicRow['fecha_texto'],'indicadores'=>[],
                        'procedimientos_evaluativos'=>[],'instrumentos_evaluativos'=>[]];
                    foreach ($this->rows('SELECT * FROM plan_indicadores WHERE id_tema=? ORDER BY orden,id_indicador',[(int)$topicRow['id_tema']]) as $indicator)
                        $topic['indicadores'][]=['orden'=>(int)$indicator['orden'],'codigo'=>$indicator['codigo'],
                            'descripcion'=>$indicator['descripcion'],'check'=>null];
                    $topic['procedimientos_evaluativos']=$this->catalogTexts((int)$topicRow['id_tema'],'procedimiento');
                    $topic['instrumentos_evaluativos']=$this->catalogTexts((int)$topicRow['id_tema'],'instrumento');
                    $capacity['temas'][]=$topic;
                }
                $unit['capacidades'][]=$capacity;
            }
            $plan['unidades'][]=$unit;
        }
        CanonicalPlan::assertValid($plan);
        return $plan;
    }

    private function persistCatalog(int $topicId,array $texts,string $type):void
    {
        [$table,$pk,$bridge]= $type==='procedimiento'
            ? ['procedimientos_evaluativos','id_procedimiento','plan_tema_procedimientos']
            : ['instrumentos_evaluativos','id_instrumento','plan_tema_instrumentos'];
        foreach (array_values($texts) as $index=>$text) {
            $row=$this->row("SELECT $pk id FROM $table WHERE nombre=? LIMIT 1",[$text]);
            if (!$row) {
                try {$this->db->prepare("INSERT INTO $table (nombre) VALUES (?)")->execute([$text]);$id=(int)$this->db->lastInsertId();}
                catch (PDOException $e) {
                    if ($e->getCode()!=='23000') throw $e;
                    $row=$this->row("SELECT $pk id FROM $table WHERE nombre=? LIMIT 1",[$text]);
                    if (!$row) throw $e;
                    $id=(int)$row['id'];
                }
            } else $id=(int)$row['id'];
            $this->db->prepare("INSERT INTO $bridge (id_tema,$pk,texto_fuente,orden) VALUES (?,?,?,?)")
                ->execute([$topicId,$id,$text,$index+1]);
        }
    }

    private function catalogTexts(int $topicId,string $type):array
    {
        if ($type==='procedimiento')
            return array_column($this->rows('SELECT COALESCE(tp.texto_fuente,p.nombre) texto FROM plan_tema_procedimientos tp JOIN procedimientos_evaluativos p ON p.id_procedimiento=tp.id_procedimiento WHERE tp.id_tema=? ORDER BY tp.orden,tp.id_procedimiento',[$topicId]),'texto');
        return array_column($this->rows('SELECT COALESCE(ti.texto_fuente,i.nombre) texto FROM plan_tema_instrumentos ti JOIN instrumentos_evaluativos i ON i.id_instrumento=ti.id_instrumento WHERE ti.id_tema=? ORDER BY ti.orden,ti.id_instrumento',[$topicId]),'texto');
    }

    private function number(mixed $value):int|float|null
    {
        if ($value===null) return null;
        $number=(float)$value;
        return floor($number)===$number?(int)$number:$number;
    }
    private function row(string $sql,array $params):array|false {$s=$this->db->prepare($sql);$s->execute($params);return $s->fetch(PDO::FETCH_ASSOC);}
    private function rows(string $sql,array $params):array {$s=$this->db->prepare($sql);$s->execute($params);return $s->fetchAll(PDO::FETCH_ASSOC);}
}
