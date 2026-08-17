<?php
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(["success" => false, "status" => "error", "message" => "Método no permitido."]);
    exit;
}

try {
    $db = (new Database())->getConnection();
    $consultas = [
        'profesores' => "SELECT COUNT(*) FROM profesores WHERE activo = 1",
        'estudiantes' => "SELECT COUNT(*) FROM estudiantes WHERE activo = 1",
        'materias' => "SELECT COUNT(*) FROM materias WHERE activo = 1",
        'aulas' => "SELECT COUNT(*) FROM aulas WHERE activo = 1",
        'grados' => "SELECT COUNT(*) FROM grados WHERE activo = 1",
        'asignaciones' => "SELECT COUNT(*) FROM asignacion_docente WHERE activo = 1",
        'horarios' => "SELECT COUNT(*) FROM horarios WHERE activo = 1",
        'marcas_hoy' => "SELECT COUNT(*) FROM eventos_asistencia WHERE activo = 1 AND DATE(timestamp_evento) = CURDATE()"
    ];

    $data = [];
    foreach ($consultas as $clave => $sql) {
        $data[$clave] = (int) $db->query($sql)->fetchColumn();
    }

    echo json_encode([
        "success" => true,
        "status" => "success",
        "message" => "Resumen cargado.",
        "data" => $data
    ]);
} catch (Throwable $e) {
    error_log('dashboard_resumen: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "status" => "error",
        "message" => "No se pudieron cargar las métricas del dashboard.",
        "data" => []
    ]);
}
?>
