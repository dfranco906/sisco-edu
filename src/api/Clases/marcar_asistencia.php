<?php
header("Content-Type: application/json; charset=UTF-8");
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../classes/ClaseDiaria.php';

$raw = file_get_contents('php://input');
$data = json_decode($raw, true) ?: $_POST;

$id_estudiante = filter_var($data['id_estudiante'] ?? null, FILTER_VALIDATE_INT);
$fecha = trim($data['fecha'] ?? date('Y-m-d'));
$hora = trim($data['hora'] ?? date('H:i:s'));
$estado = strtoupper(trim($data['estado'] ?? 'PRESENTE'));

if (!$id_estudiante || !in_array($estado, ['PRESENTE', 'TARDANZA', 'AUSENTE', 'JUSTIFICADO'], true)) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Datos requeridos inválidos (id_estudiante, estado)"]);
    exit;
}

try {
    $db = (new Database())->getConnection();
    $claseModel = new ClaseDiaria($db);

    $ok = $claseModel->registrarAsistenciaEstudiante($id_estudiante, $fecha, $hora, $estado);

    http_response_code(200);
    echo json_encode([
        "status" => $ok ? "success" : "error",
        "message" => $ok ? "Asistencia actualizada a {$estado}" : "No se pudo actualizar asistencia"
    ]);
} catch (Throwable $e) {
    error_log("Error en Clases/marcar_asistencia: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Error interno al actualizar asistencia"]);
}
