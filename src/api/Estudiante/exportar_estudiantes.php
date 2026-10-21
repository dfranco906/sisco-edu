<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../classes/Estudiante.php';

try {
    $db = (new Database())->getConnection();
    $estudiante = new Estudiante($db);

    $idGrado = filter_input(INPUT_GET, 'id_grado', FILTER_VALIDATE_INT) ?: null;
    $idTurno = filter_input(INPUT_GET, 'id_turno', FILTER_VALIDATE_INT) ?: null;
    $idNivel = filter_input(INPUT_GET, 'id_nivel', FILTER_VALIDATE_INT) ?: null;
    $idBachillerato = filter_input(INPUT_GET, 'id_bachillerato', FILTER_VALIDATE_INT) ?: null;

    $stmt = $estudiante->leer($idGrado, $idTurno, $idNivel, $idBachillerato);
    $estudiantes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $gradoTag = 'todos';
    if ($idGrado && !empty($estudiantes[0]['grado'])) {
        $gradoTag = preg_replace('/[^a-zA-Z0-9_-]/', '_', $estudiantes[0]['grado']);
    }

    $filename = "nomina_estudiantes_{$gradoTag}_" . date('Ymd_His') . ".csv";

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $output = fopen('php://output', 'w');

    // Escribir Byte Order Mark (BOM) para compatibilidad absoluta con Microsoft Excel (tildes y ñ)
    fwrite($output, "\xEF\xBB\xBF");

    // Cabecera CSV delimitada por punto y coma (;)
    fputcsv($output, [
        'Cédula de Identidad',
        'Apellido',
        'Nombre',
        'Grado / Curso',
        'Materias Matriculadas',
        'Huella Biométrica',
        'Total Tutores',
        'Tutores Vinculados'
    ], ';');

    foreach ($estudiantes as $e) {
        $estadoHuella = (!empty($e['huella_registrada']) || !empty($e['huella_id'])) ? 'Activa' : 'Sin huella';

        fputcsv($output, [
            $e['cedula_identidad'],
            $e['apellido'],
            $e['nombre'],
            $e['grado'] ?? 'Sin asignar',
            $e['materias_matriculadas'] ?? 0,
            $estadoHuella,
            $e['total_tutores'] ?? 0,
            $e['tutores_resumen'] ?? 'Sin tutores'
        ], ';');
    }

    fclose($output);
    exit;
} catch (Throwable $e) {
    error_log("Error en exportar_estudiantes: " . $e->getMessage());
    http_response_code(500);
    echo "Error interno al exportar nómina de estudiantes.";
    exit;
}
