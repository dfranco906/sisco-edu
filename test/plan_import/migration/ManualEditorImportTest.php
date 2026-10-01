<?php
declare(strict_types=1);
require_once dirname(__DIR__,3).'/src/classes/PlanImport/bootstrap.php';
require_once dirname(__DIR__,3).'/src/classes/PlanificacionPedagogica.php';
require_once __DIR__.'/../support/TestEnvironment.php';
use SiscoEdu\PlanImport\{PdfPlanImporter,PlanImportPersistence,CatalogMatcher};
$env=new PlanImportTestEnvironment();
try{
    $root=dirname(__DIR__,3);if(trim((string)getenv('SISCO_PDFTOTEXT_PATH'))==='')putenv('SISCO_PDFTOTEXT_PATH=C:\\Program Files\\Git\\mingw64\\bin\\pdftotext.exe');$pdf=glob($root.'/test/fixtures/planes/*tercer curso*Administracion financiera.pdf')[0];$plan=PdfPlanImporter::fromConfig(require $root.'/src/config/plan_import.php')->import($pdf,true);$suggestions=(new CatalogMatcher())->suggestions($plan,['procedimientos'=>[],'instrumentos'=>[]]);$decisions=array_map(static fn(array $s):array=>['kind'=>$s['kind'],'text'=>$s['text'],'action'=>'create','id'=>null],$suggestions);
    $env->db->beginTransaction();$id=(new PlanImportPersistence($env->db))->persist($plan,$env->assignment,2026,$env->user,$decisions,[]);$env->db->commit();
    $service=new PlanificacionPedagogica($env->db,['id_usuario'=>$env->user,'rol'=>'Profesor']);$loaded=$service->obtenerPlan($id);
    if(isset($loaded['importacion_origen_json'])||($loaded['importacion_origen']['archivo']??null)!==basename($pdf))throw new RuntimeException('Procedencia tÃ©cnica expuesta o leyenda ausente');
    $unit=$loaded['unidades'][0];$cap=$unit['capacidades'][0];$topic=$cap['temas'][0];$indicator=$topic['indicadores'][0];if($unit['codigo']===null||!isset($unit['tiempo_texto'],$unit['proceso_texto'])||!array_key_exists('fecha_texto',$topic)||!array_key_exists('codigo',$indicator))throw new RuntimeException('Campos importados no leÃ­dos');
    $service->actualizarPlan($id,['competencia_general'=>'General editada','competencia_especifica'=>$loaded['competencia_especifica'],'observaciones'=>'Editado normalmente','institucion_fuente'=>'InstituciÃ³n editada','materia_fuente'=>$loaded['materia_fuente'],'profesor_fuente'=>$loaded['profesor_fuente'],'curso_fuente'=>$loaded['curso_fuente'],'turno_fuente'=>'MaÃ±ana','anio_fuente'=>2026,'dias_clase_fuente'=>'Lunes']);
    $service->guardarElemento('unidad',['id_unidad'=>$unit['id_unidad'],'id_plan'=>$id,'codigo'=>'U-EDIT','nombre'=>$unit['nombre'],'descripcion'=>$unit['descripcion'],'horas_catedra'=>$unit['horas_catedra'],'tiempo_texto'=>'12 HC texto','proceso_texto'=>'Febrero - Marzo','proceso_inicio'=>'','proceso_fin'=>'','area_transversal'=>$unit['area_transversal'],'metodologia'=>$unit['metodologia'],'medios_verificacion'=>$unit['medios_verificacion']]);
    $service->guardarElemento('tema',['id_tema'=>$topic['id_tema'],'id_capacidad'=>$cap['id_capacidad'],'codigo'=>'1.EDIT','titulo'=>'Tema corregido manualmente','contenido'=>$topic['contenido'],'horas_catedra'=>$topic['horas_catedra'],'tiempo_texto'=>'4 HC','fecha_texto'=>'Febrero - Marzo']);
    $service->guardarElemento('indicador',['id_indicador'=>$indicator['id_indicador'],'id_tema'=>$topic['id_tema'],'codigo'=>'1.EDIT.1','descripcion'=>'Indicador corregido manualmente']);
    $service->guardarEvaluacion((int)$topic['id_tema'],$topic['procedimientos'],$topic['instrumentos']);
    $again=$service->obtenerPlan($id);$u=$again['unidades'][0];$t=$u['capacidades'][0]['temas'][0];$i=$t['indicadores'][0];if($again['institucion_fuente']!=='InstituciÃ³n editada'||$u['codigo']!=='U-EDIT'||$u['proceso_texto']!=='Febrero - Marzo'||$t['codigo']!=='1.EDIT'||$t['fecha_texto']!=='Febrero - Marzo'||$i['codigo']!=='1.EDIT.1')throw new RuntimeException('EdiciÃ³n manual no sobreviviÃ³ reapertura');
    if($topic['procedimientos']&&$again['unidades'][0]['capacidades'][0]['temas'][0]['procedimientos'][0]['texto_fuente']!==$topic['procedimientos'][0]['texto_fuente'])throw new RuntimeException('Texto fuente evaluativo perdido');
    echo "OK | ManualEditorImportTest (abrir, procedencia segura, editar campos importados, guardar y reabrir con relaciones ordenadas)\n";
}finally{$env->close();}
