<?php
declare(strict_types=1);

/** PDF mínimo sintético, sólo para probar rechazo de documentos sin plantilla. */
function planImportSyntheticPdf(string $text): string
{
    $text=str_replace(['\\','(',')'],['\\\\','\\(','\\)'],$text);
    $stream=$text===''?'':'BT /F1 12 Tf 50 700 Td ('.$text.') Tj ET';
    $objects=[
        '<< /Type /Catalog /Pages 2 0 R >>',
        '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
        '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>',
        '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
        '<< /Length '.strlen($stream).">>\nstream\n".$stream."\nendstream",
    ];
    $pdf="%PDF-1.4\n";$offsets=[0];
    foreach($objects as $i=>$object){$offsets[]=strlen($pdf);$pdf.=($i+1)." 0 obj\n".$object."\nendobj\n";}
    $xref=strlen($pdf);$pdf.="xref\n0 6\n0000000000 65535 f \n";
    foreach(array_slice($offsets,1) as $offset)$pdf.=sprintf("%010d 00000 n \n",$offset);
    return $pdf."trailer\n<< /Size 6 /Root 1 0 R >>\nstartxref\n".$xref."\n%%EOF\n";
}
