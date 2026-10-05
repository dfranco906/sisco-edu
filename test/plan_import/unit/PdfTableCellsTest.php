<?php
declare(strict_types=1);
require_once dirname(__DIR__,3).'/src/classes/PlanImport/bootstrap.php';

use SiscoEdu\PlanImport\Pdf\XpdfTableExtractor;
use SiscoEdu\PlanImport\Pdf\Parser\CompetenciaContenidoParser;
use SiscoEdu\PlanImport\Pdf\PdfTableCells;
use SiscoEdu\PlanImport\ProcessRunner;
use SiscoEdu\PlanImport\PlanImportException;

$config=require dirname(__DIR__,3).'/src/config/plan_import.php';
$config['pdftotext_binary']='C:\\Program Files\\Git\\mingw64\\bin\\pdftotext.exe';
$failures=[];$checks=0;
function cellsAssert(bool $value,string $message):void {global $failures,$checks;$checks++;if(!$value)$failures[]=$message;}
function cellsReject(callable $f,string $type,string $message):void {
    try{$f();cellsAssert(false,$message.' no rechazado');}
    catch(PlanImportException $e){cellsAssert($e->errorType===$type,$message.' tipo '.$e->errorType);}
}
function cellsPdf(string $stream):string {
    $objects=[
        '<< /Type /Catalog /Pages 2 0 R >>',
        '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
        '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 800 600] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>',
        '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
        '<< /Length '.strlen($stream)." >>\nstream\n".$stream."\nendstream",
    ];
    $pdf="%PDF-1.4\n";$offsets=[];
    foreach($objects as $i=>$o){$offsets[]=strlen($pdf);$pdf.=($i+1)." 0 obj\n".$o."\nendobj\n";}
    $xref=strlen($pdf);$pdf.="xref\n0 6\n0000000000 65535 f \n";
    foreach($offsets as $offset)$pdf.=sprintf('%010d 00000 n ', $offset)."\n";
    return $pdf."trailer << /Size 6 /Root 1 0 R >>\nstartxref\n".$xref."\n%%EOF\n";
}
$x=[40,130,270,390,500,580,660,720,790];
$stream="q\n0 0 800 600 re W n\n";
foreach($x as $v)$stream.="$v 550 m $v 310 l\n";
foreach([550,520,310] as $v)$stream.="40 $v m 790 $v l\n";
foreach([450,380] as $v)$stream.="130 $v m 500 $v l\n";
$stream.="40 380 m 130 380 l\nS\nQ\n";
$labels=[
    [45,535,'COMPETENCIA'],[135,535,'CAPACIDAD'],[275,535,'INDICADORES'],[395,535,'CONTENIDOS'],
    // Names centered below the beginning of their merged cells: arbitrary subject.
    [45,405,'Unidad Alfa'],[45,340,'Unidad Beta'],
    [135,480,'Capacidad A'],[135,410,'Capacidad B'],[135,340,'Capacidad C'],
    [275,480,'- Indicador A'],[275,410,'- Indicador B'],[275,340,'- Indicador C'],
    [395,480,'Contenido A'],[395,410,'Contenido B'],[395,340,'Contenido C'],[725,420,'Periodo libre'],
];
foreach($labels as [$a,$b,$text])$stream.="BT /F1 8 Tf 1 0 0 1 $a $b Tm ($text) Tj ET\n";
$path=tempnam(sys_get_temp_dir(),'sisco-grid-test-').'.pdf';
try{
    file_put_contents($path,cellsPdf($stream));
    $doc=(new XpdfTableExtractor($config))->extract($path);
    $plan=(new CompetenciaContenidoParser())->parse($doc);
    cellsAssert(array_column($plan['unidades'],'nombre')===['Unidad Alfa','Unidad Beta'],'nombres centrados no definen inicio');
    cellsAssert(array_map(fn($u)=>array_column($u['capacidades'],'descripcion'),$plan['unidades'])===[['Capacidad A','Capacidad B'],['Capacidad C']],'asociación 2/1 por bordes, sin nombres de materia');
    cellsAssert($plan['unidades'][0]['capacidades'][0]['temas'][0]['indicadores'][0]['descripcion']==='Indicador A','viñeta vinculada a capacidad A');
    cellsAssert($plan['unidades'][1]['capacidades'][0]['temas'][0]['titulo']==='Contenido C','contenido vinculado a capacidad C');
    $limits=$config['limits'];$limits['timeout_seconds']=0.000001;
    cellsReject(fn()=>(new PdfTableCells($config['pdftotext_binary'],new ProcessRunner()))->extract($path,1,$limits),'PARSER_TIMEOUT','presupuesto global de lectura');
    $limits=$config['limits'];$limits['max_output_bytes']=8;
    cellsReject(fn()=>(new PdfTableCells($config['pdftotext_binary'],new ProcessRunner()))->extract($path,1,$limits),'PDF_TABLE_BOUNDARIES_UNSUPPORTED','stream superior al límite');
    file_put_contents($path,cellsPdf(str_replace('790 550 m 790 310 l', '790 550 m 789 310 l',$stream)));
    cellsReject(fn()=>(new XpdfTableExtractor($config))->extract($path),'PDF_TABLE_BOUNDARIES_UNSUPPORTED','borde no verificable no cae en parser de texto');
}finally{
    @unlink($path);
    @unlink(substr($path,0,-4)); // Owned tempnam placeholder, not user data.
}
if($failures){fwrite(STDERR,"FALLAS PDF TABLE CELLS:\n- ".implode("\n- ",$failures)."\n");exit(1);}
echo "OK | PdfTableCellsTest ($checks checks; PDF sintético, límites, sin BD)\n";
