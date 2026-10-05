<?php
declare(strict_types=1);
require_once dirname(__DIR__,3).'/src/classes/PlanImport/bootstrap.php';

use SiscoEdu\PlanImport\Pdf\XpdfTableExtractor;
use SiscoEdu\PlanImport\Pdf\Parser\CompetenciaContenidoParser;
use SiscoEdu\PlanImport\PlanImportException;

$root=dirname(__DIR__,3);
$config=require $root.'/src/config/plan_import.php';
$config['pdftotext_binary']='C:\\Program Files\\Git\\mingw64\\bin\\pdftotext.exe';
$extractor=new XpdfTableExtractor($config);
$parser=new CompetenciaContenidoParser();
$failures=[]; $checks=0;
function competenceAssert(bool $c,string $m):void { global $failures,$checks; $checks++; if(!$c) $failures[]=$m; }
function competenceReject(callable $f,string $type,string $label):void {
    try { $f(); competenceAssert(false,$label.' no rechazado'); }
    catch(PlanImportException $e) { competenceAssert($e->errorType===$type,$label.' devolvió '.$e->errorType); }
}
$name='Plan Anual - segundo curso - BCB - Excel Avanzado.pdf';
$document=$extractor->extract($root.'/test/fixtures/planes/'.$name);
$plan=$parser->parse($document);
// Expectations reviewed against the printed cell boundaries in both PDF pages,
// independent of the historical POC snapshot (which contained the 7/7 error).
$capacities=[
    ['Comprende la evolución histórica de la informática.','Identifica las partes de un sistema operativo','Utiliza diferentes herramientas de un sistema operativo'],
    ['Reconoce el Microsoft Word como el procesador de texto más avanzado','Comprende el manejo de la ortografía en el procesador con las configuraciones de tipo de visualización','Utiliza las herramientas de la configuración general de las paginas','Utiliza los distintos objetos en Word','Identifica la seguridad de los documentos con sus estilos, hipervínculos y marcadores','Comprende el uso de combinar correspondencia','Identifica la utilización adecuada de las etiquetas con el manejo de las formulas y ecuaciones','Reconoce la utilización del documento maestro con la ventaja de las tablas de ilustración','Identifica el manejo del Macros y sus formularios','Comprende el modo guardado del documento','Utiliza el teclado de forma correcta'],
];
$topics=[
    ['Historia, evolución y generaciones','Hardware Software Tipos de computadora','Entorno de un sistema Operativo y sus fundamentos'],
    ['Acceso al programa de Word Entorno del programa Guardar Formato fuente Formato párrafo Numeración y viñetas','Ortografía Configuración de Tipos de visualización de','Impresión de documentos Saltos Encabezado y pie de Numeración de pagina','Insertar imágenes, tablas, autoformas, organigramas y diagramas','Seguridad de los documentos Estilos Marcadores Hipervínculos','Combinación de correspondencia','Etiquetas Formulas y Ecuaciones','Documentos maestros Crear tablas de ilustración','Macros Formularios','HTML Compartir documentos PDF','Dactilografía computarizada'],
];
$indicatorCounts=[[3,3,5],[6,3,4,4,5,3,2,3,2,3,1]];
competenceAssert(count($plan['unidades'])===2,'2 competencias');
competenceAssert(array_column($plan['unidades'],'nombre')===['Manejo del sistema operativo avanzado','Uso avanzado de Microsoft Office Word'],'nombres reales de competencias');
$total=0;
foreach($plan['unidades'] as $i=>$unit){
    competenceAssert(array_column($unit['capacidades'],'descripcion')===$capacities[$i],'capacidades de unidad '.$i);
    competenceAssert(array_column($unit['capacidades'],'orden')===range(1,count($capacities[$i])),'órdenes secuenciales de capacidades');
    foreach($unit['capacidades'] as $j=>$capacity){
        $topic=$capacity['temas'][0];
        competenceAssert(count($capacity['temas'])===1,'un tema compuesto por capacidad');
        competenceAssert($topic['titulo']===$topics[$i][$j],'contenido de capacidad '.$i.'/'.$j);
        competenceAssert(count($topic['indicadores'])===$indicatorCounts[$i][$j],'indicadores de capacidad '.$i.'/'.$j);
        competenceAssert($topic['fecha_texto']===($i===0||$j<3?'De marzo a junio':'De Julio a Noviembre'),'periodo fuente de capacidad '.$i.'/'.$j);
        $total+=count($topic['indicadores']);
    }
}
competenceAssert($total===47,'47 indicadores conservados');
$word=$plan['unidades'][1]['capacidades'];
competenceAssert($word[0]['temas'][0]['indicadores'][5]['descripcion']==='Reconoce el procedimiento de las','viñetas permanece con reconocimiento de Word');
competenceAssert($word[2]['temas'][0]['indicadores'][0]['descripcion']==='Conoce las ventajas de impresión','impresión pertenece a configuración de páginas');
competenceAssert($word[6]['temas'][0]['indicadores'][1]['descripcion']==='Inserta las formulas en el documento','continuación sin viñeta cruza borde sin cambiar de indicador');
competenceAssert($word[9]['temas'][0]['indicadores'][0]['descripcion']==='Aplica el guardado del documento como HTML','HTML pertenece a guardado del documento');
competenceAssert(count($word[3]['temas'][0]['indicadores'])===4,'objetos de Word se fusiona entre páginas');
competenceAssert(count($word)===11,'continuación no crea capacidad adicional');
$textOnly=$document;unset($textOnly['table_cells']);
competenceReject(fn()=>$parser->parse($textOnly),'PDF_TABLE_BOUNDARIES_UNSUPPORTED','texto sin geometría');
$ambiguous=$document;$ambiguous['table_cells'][0]['cells'][1][1]['box'][1]=$ambiguous['table_cells'][0]['cells'][1][1]['box'][3]-1;
competenceReject(fn()=>$parser->parse($ambiguous),'PDF_TABLE_ASSOCIATION_AMBIGUOUS','viñeta sin capacidad única');
// The same file under an unrelated name must have identical associations.
$renamed=$document;$renamed['filename']='documento-sin-nombre-de-materia.pdf';
competenceAssert($parser->parse($renamed)['unidades']===$plan['unidades'],'no reglas por nombre del PDF');
if($failures){fwrite(STDERR,"FALLAS COMPETENCIA PARSER:\n- ".implode("\n- ",$failures)."\n");exit(1);}
echo "OK | CompetenciaContenidoParserTest ($checks checks; 3/11 capacidades, 14 temas, 47 indicadores)\n";
