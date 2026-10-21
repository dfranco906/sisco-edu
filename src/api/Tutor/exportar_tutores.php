<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../classes/Tutor.php';

try {
    $db = (new Database())->getConnection();
    $tutor = new Tutor($db);

    $tutores = $tutor->leer();

    $filename = "directorio_tutores_" . date('Ymd_His') . ".csv";

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $output = fopen('php://output', 'w');

    // BOM UTF-8
    fwrite($output, "\xEF\xBB\xBF");

    // Cabecera CSV delimitada por punto y coma (;)
    fputcsv($output, [
        'Cédula de Identidad',
        'Apellido',
        'Nombre',
        'Celular',
        'Email',
        'Dirección',
        'Total Hijos',
        'Cédulas de Hijos',
        'Hijos y Cursos'
    ], ';');

    foreach ($tutores as $t) {
        fputcsv($output, [
            $t['cedula_identidad'],
            $t['apellido'],
            $t['nombre'],
            $t['celular'] ?? '',
            $t['email'] ?? '',
            $t['direccion'] ?? '',
            $t['total_hijos'] ?? 0,
            $t['cedulas_hijos'] ?? '',
            $t['hijos_resumen'] ?? 'Sin hijos asignados'
        ], ';');
    }

    fclose($output);
    exit;
} catch (Throwable $e) {
    error_log("Error en exportar_tutores: " . $e->getMessage());
    http_response_code(500);
    echo "Error interno al exportar directorio de tutores.";
    exit;
}
