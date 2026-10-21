<?php
declare(strict_types=1);

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="plantilla_importacion_tutores.csv"');
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
    'celular',
    'email',
    'direccion',
    'parentesco',
    'cedulas_hijos'
], ';');

// Filas de ejemplo instructivas
fputcsv($output, [
    '4567890',
    'Marcos',
    'Ortega',
    '0971555666',
    'marcos.ortega@gmail.com',
    'Calle Palma 123',
    'Padre',
    '6306661'
], ';');

fputcsv($output, [
    '3456789',
    'Ana',
    'Martinez',
    '0981222333',
    'ana.martinez@gmail.com',
    'Barrio San Roque',
    'Madre',
    '6543218, 6548971'
], ';');

fclose($output);
exit;
