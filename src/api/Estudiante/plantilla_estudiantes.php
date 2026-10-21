<?php
declare(strict_types=1);

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="plantilla_importacion_estudiantes.csv"');
header('Pragma: no-cache');
header('Expires: 0');

$output = fopen('php://output', 'w');

// BOM UTF-8
fwrite($output, "\xEF\xBB\xBF");

// Cabecera estándar
fputcsv($output, [
    'cedula_identidad',
    'nombre',
    'apellido',
    'grado'
], ';');

// Filas de ejemplo instructivas
fputcsv($output, ['6306661', 'Tiago', 'Ortega', '3° BTI'], ';');
fputcsv($output, ['6548971', 'Thiago', 'Amarilla', '3° BTI'], ';');
fputcsv($output, ['6543218', 'Fiorella', 'Martinez', '3° BTI'], ';');

fclose($output);
exit;
