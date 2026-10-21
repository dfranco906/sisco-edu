<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../classes/Profesor.php';

try {
    $db = (new Database())->getConnection();
    $profesor = new Profesor($db);

    $idGrado = isset($_GET['id_grado']) && is_numeric($_GET['id_grado']) ? (int)$_GET['id_grado'] : null;
    $idTurno = isset($_GET['id_turno']) && is_numeric($_GET['id_turno']) ? (int)$_GET['id_turno'] : null;
    $idNivel = isset($_GET['id_nivel']) && is_numeric($_GET['id_nivel']) ? (int)$_GET['id_nivel'] : null;
    $idBachillerato = isset($_GET['id_bachillerato']) && is_numeric($_GET['id_bachillerato']) ? (int)$_GET['id_bachillerato'] : null;

    $stmt = $profesor->leer($idGrado, $idTurno, $idNivel, $idBachillerato);
    $profesores = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $gradoTag = 'todos';
    if ($idGrado) {
        $stmtG = $db->prepare("SELECT nombre FROM grados WHERE id_grado = :id LIMIT 1");
        $stmtG->execute([':id' => $idGrado]);
        $nomG = $stmtG->fetchColumn();
        if ($nomG) {
            $gradoTag = preg_replace('/[^a-zA-Z0-9_-]/', '_', (string)$nomG);
        }
    }

    $filename = "nomina_profesores_{$gradoTag}_" . date('Ymd_His') . ".csv";

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $output = fopen('php://output', 'w');

    // Escribir Byte Order Mark (BOM) para compatibilidad absoluta con Microsoft Excel (tildes y caracteres especiales)
    fwrite($output, "\xEF\xBB\xBF");

    // Cabecera CSV delimitada por punto y coma (;)
    fputcsv($output, [
        'Cédula de Identidad',
        'Apellido',
        'Nombre',
        'Correo Electrónico',
        'Celular',
        'Cursos Asignados',
        'Materias Asignadas',
        'Total Cargas / Asignaciones',
        'Huella Biométrica'
    ], ';');

    foreach ($profesores as $p) {
        $estadoHuella = (!empty($p['huella_registrada']) || !empty($p['huella_id'])) ? 'Activa' : 'Sin huella';

        fputcsv($output, [
            $p['cedula_identidad'],
            $p['apellido'],
            $p['nombre'],
            $p['email'] ?? 'Sin correo',
            $p['celular'] ?? 'Sin celular',
            $p['grados_resumen'] ?: 'Sin curso asignado',
            $p['asignaciones_resumen'] ?: 'Sin materias asignadas',
            $p['total_asignaciones'] ?? 0,
            $estadoHuella
        ], ';');
    }

    fclose($output);
    exit;
} catch (Throwable $e) {
    error_log("Error en exportar_profesores: " . $e->getMessage());
    http_response_code(500);
    echo "Error interno al exportar nómina de profesores.";
    exit;
}
