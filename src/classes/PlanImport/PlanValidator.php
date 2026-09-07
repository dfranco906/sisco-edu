<?php
declare(strict_types=1);

namespace SiscoEdu\PlanImport;

final class PlanValidator
{
    /** @param array<string,mixed> $plan @return array<string,mixed> */
    public function validate(array $plan): array
    {
        $format=(string)($plan['source']['format']??'');$required=$format==='COMPETENCIA_CONTENIDO'?['institucion','materia','profesor','curso','anio','dias_clase']:['institucion','materia','profesor','curso','turno','anio'];
        foreach($required as$field)if(($plan['metadata'][$field]??null)===null||$plan['metadata'][$field]==='')$plan['warnings'][]=Text::warning('MISSING_METADATA','No se encontró metadata principal: '.$field.'.');
        $year=$plan['metadata']['anio']??null;if($year!==null&&(!is_int($year)||$year<2000||$year>2100))$plan['warnings'][]=Text::warning('INVALID_YEAR','El año extraído está fuera del rango 2000-2100.',null,(string)$year);
        if(!$plan['unidades'])$plan['warnings'][]=Text::warning('MISSING_UNIT','El plan no contiene unidades detectadas.');
        $unitCodes=[];$topicCodes=[];$indicatorCodes=[];
        foreach($plan['unidades']as$unitIndex=>$unit){
            if(($unit['orden']??null)!==$unitIndex+1)$plan['warnings'][]=Text::warning('INVALID_ORDER','El orden de la unidad no es secuencial.',null,(string)($unit['nombre']??''));
            $unitNumber=!empty($unit['codigo'])?Text::romanToInt((string)$unit['codigo']):null;if(!empty($unit['codigo'])){$key=(string)$unit['codigo'];if(isset($unitCodes[$key]))$plan['warnings'][]=Text::warning('DUPLICATE_CODE','Código de unidad duplicado.',null,$key);$unitCodes[$key]=true;}
            if(!$unit['capacidades'])$plan['warnings'][]=Text::warning('MISSING_CAPACITY','La unidad no contiene capacidades.',null,(string)$unit['nombre']);
            if($unit['horas_catedra']!==null&&(!is_numeric($unit['horas_catedra'])||$unit['horas_catedra']<0))$plan['warnings'][]=Text::warning('INVALID_HOURS','Horas cátedra inválidas en unidad.',null,(string)$unit['tiempo_texto']);
            foreach($unit['capacidades']as$capacityIndex=>$capacity){
                if(($capacity['orden']??null)!==$capacityIndex+1)$plan['warnings'][]=Text::warning('INVALID_ORDER','El orden de la capacidad no es secuencial.',null,(string)($capacity['descripcion']??''));
                if(!$capacity['temas'])$plan['warnings'][]=Text::warning('MISSING_TOPIC','La capacidad no contiene temas.',null,(string)$capacity['descripcion']);
                foreach($capacity['temas']as$topicIndex=>$topic){
                    if(($topic['orden']??null)!==$topicIndex+1)$plan['warnings'][]=Text::warning('INVALID_ORDER','El orden del tema no es secuencial.',null,(string)($topic['titulo']??''));
                    $topicCode=$topic['codigo'];if($topicCode){if(isset($topicCodes[$topicCode]))$plan['warnings'][]=Text::warning('DUPLICATE_CODE','Código de tema duplicado.',null,$topicCode);$topicCodes[$topicCode]=true;$topicUnit=(int)explode('.',$topicCode)[0];if($unitNumber!==null&&$topicUnit!==$unitNumber)$plan['warnings'][]=Text::warning('INCOHERENT_TOPIC_CODE','El código del tema no corresponde a su unidad.',null,$topicCode);}
                    if(!$topic['indicadores'])$plan['warnings'][]=Text::warning('MISSING_INDICATOR','El tema no contiene indicadores.',null,(string)$topic['titulo']);
                    if($format==='EXCEL_AVANZADO'&&$topic['horas_catedra']===null)$plan['warnings'][]=Text::warning('MISSING_HOURS','No se encontró tiempo/HC para el tema de Excel Avanzado.',null,(string)$topic['codigo']);
                    if($topic['horas_catedra']!==null&&(!is_numeric($topic['horas_catedra'])||$topic['horas_catedra']<0))$plan['warnings'][]=Text::warning('INVALID_HOURS','Horas cátedra inválidas en tema.',null,(string)$topic['tiempo_texto']);
                    foreach($topic['indicadores']as$indicatorIndex=>$indicator){if(($indicator['orden']??null)!==$indicatorIndex+1)$plan['warnings'][]=Text::warning('INVALID_ORDER','El orden del indicador no es secuencial.',null,(string)($indicator['descripcion']??''));$indicatorCode=$indicator['codigo'];if(!$indicatorCode)continue;if(isset($indicatorCodes[$indicatorCode]))$plan['warnings'][]=Text::warning('DUPLICATE_CODE','Código de indicador duplicado.',null,$indicatorCode);$indicatorCodes[$indicatorCode]=true;$parent=implode('.',array_slice(explode('.',$indicatorCode),0,2));if($topicCode!==null&&$parent!==$topicCode)$plan['warnings'][]=Text::warning('INCOHERENT_INDICATOR_CODE','El código del indicador no corresponde a su tema.',null,$indicatorCode);}
                }
            }
            if($format==='ADMINISTRACION_FINANCIERA'&&$unit['horas_catedra']===null)$plan['warnings'][]=Text::warning('MISSING_HOURS','No se encontró tiempo/HC para la unidad financiera.',null,(string)$unit['codigo']);
        }
        $plan['warnings']=$this->uniqueWarnings($plan['warnings']);return$plan;
    }
    private function uniqueWarnings(array$warnings):array{$seen=[];$result=[];foreach($warnings as$warning){$key=implode('|',[(string)($warning['type']??''),(string)($warning['page']??''),(string)($warning['line']??''),(string)($warning['text']??''),(string)($warning['message']??'')]);if(isset($seen[$key]))continue;$seen[$key]=true;$result[]=$warning;}return$result;}
}
