<?php
declare(strict_types=1);

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="plantilla_importacion_profesores.csv"');
header('Pragma: no-cache');
header('Expires: 0');

$output = fopen('php://output', 'w');

// Escribir Byte Order Mark (BOM) para compatibilidad con Microsoft Excel
fwrite($output, "\xEF\xBB\xBF");

// Cabecera estándar delimitada por punto y coma (;)
fputcsv($output, [
    'cedula_identidad',
    'nombre',
    'apellido',
    'email',
    'celular',
    'grado',
    'materia'
], ';');

// Filas de ejemplo instructivas
fputcsv($output, ['6767676', 'Danilo', 'Franco', 'danilofranco906@gmail.com', '0995684004', '3° BTI', 'LABORATORIO PROGRAMACION'], ';');
fputcsv($output, ['323289', 'Paola', 'Lezcano', 'paola.lezcano@profesor.sisco.edu', '0981123456', '3° BTI', 'MATEMATICA'], ';');
fputcsv($output, ['6015000', 'Raul', 'Alonso', 'raul.alonso@profesor.sisco.edu', '0981987654', '1° BTI', 'GABINETE LABORATORIO'], ';');

fclose($output);
exit;
