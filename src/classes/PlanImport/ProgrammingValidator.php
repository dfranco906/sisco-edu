<?php
declare(strict_types=1);
namespace SiscoEdu\PlanImport;

final class ProgrammingValidator
{
    public function validate(array $rows, array $plan, int $year): array
    {
        if(!array_is_list($rows))throw new PlanImportException('La programación confirmada debe ser una lista.','INVALID_PROGRAMMING');
        $topics=[];
        foreach($plan['unidades'] as $u)foreach($u['capacidades'] as $c)foreach($c['temas'] as $t)$topics[$u['orden'].':'.$c['orden'].':'.$t['orden']]=true;
        $seen=[];
        foreach($rows as $row){
            $keys=['unidad_orden','capacidad_orden','tema_orden','fecha_inicio','fecha_fin','horas_catedra_planificadas','observaciones'];
            if(!is_array($row)||array_diff(array_keys($row),$keys)||array_diff($keys,array_keys($row)))throw new PlanImportException('Programación confirmada inválida.','INVALID_PROGRAMMING');
            foreach(['unidad_orden','capacidad_orden','tema_orden'] as $key)if(!is_int($row[$key])||$row[$key]<1)throw new PlanImportException('Ruta de programación inválida.','INVALID_PROGRAMMING');
            $topicKey=$row['unidad_orden'].':'.$row['capacidad_orden'].':'.$row['tema_orden'];
            if(!isset($topics[$topicKey]))throw new PlanImportException('La programación no corresponde a un tema del preview.','INVALID_PROGRAMMING');
            foreach(['fecha_inicio','fecha_fin'] as $key){$date=\DateTimeImmutable::createFromFormat('!Y-m-d',$row[$key]);if(!$date||$date->format('Y-m-d')!==$row[$key]||(int)$date->format('Y')!==$year)throw new PlanImportException('La programación debe usar fechas válidas del año del plan.','INVALID_PROGRAMMING');}
            if($row['fecha_fin']<$row['fecha_inicio'])throw new PlanImportException('La fecha final no puede ser anterior a la inicial.','INVALID_PROGRAMMING');
            if($row['horas_catedra_planificadas']!==null&&((!is_int($row['horas_catedra_planificadas'])&&!is_float($row['horas_catedra_planificadas']))||$row['horas_catedra_planificadas']<0))throw new PlanImportException('Horas programadas inválidas.','INVALID_PROGRAMMING');
            if($row['observaciones']!==null&&!is_string($row['observaciones']))throw new PlanImportException('Observaciones de programación inválidas.','INVALID_PROGRAMMING');
            $key=$topicKey.':'.$row['fecha_inicio'].':'.$row['fecha_fin'];if(isset($seen[$key]))throw new PlanImportException('Programación duplicada.','INVALID_PROGRAMMING');$seen[$key]=true;
        }
        return $rows;
    }
}
