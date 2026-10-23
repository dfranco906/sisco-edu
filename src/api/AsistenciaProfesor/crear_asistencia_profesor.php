<?php
header("Content-Type: application/json; charset=UTF-8");
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../classes/AsistenciaProfesor.php';

try {
    $db = (new Database())->getConnection();

    $id_profesor = $_POST['id_profesor'] ?? null;
    $fecha = $_POST['fecha'] ?? date('Y-m-d');
    $hora = $_POST['hora'] ?? date('H:i:s');
    $estado = $_POST['estado'] ?? 'PRESENTE';

    if (!$id_profesor) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Seleccione un profesor"]);
        exit;
    }

    $stmt = $db->prepare("SELECT huella_id FROM profesores WHERE id_profesor = :id");
    $stmt->execute([":id" => $id_profesor]);
    $profesor = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$profesor || empty($profesor["huella_id"])) {
        http_response_code(422);
        echo json_encode(["status" => "error", "message" => "Este profesor no tiene huella registrada"]);
        exit;
    }

    // Control estricto anti-duplicación por día
    $stmtCheck = $db->prepare("SELECT id_asistencia_profesor FROM asistencias_profesores WHERE id_profesor = :id AND fecha = :fecha AND activo = 1 LIMIT 1");
    $stmtCheck->execute([':id' => $id_profesor, ':fecha' => $fecha]);
    if ($stmtCheck->fetchColumn()) {
        http_response_code(409);
        echo json_encode(["status" => "error", "message" => "El profesor ya cuenta con asistencia registrada en esta fecha"]);
        exit;
    }

    $a = new AsistenciaProfesor($db);
    $a->id_profesor = $id_profesor;
    $a->huella_id = $profesor["huella_id"];
    $a->fecha = $fecha;
    $a->hora = $hora;
    $a->estado = strtoupper($estado);

    $resultado = $a->crear();

    http_response_code($resultado ? 200 : 500);
    echo json_encode([
        "status" => $resultado ? "success" : "error",
        "message" => $resultado ? "Asistencia de profesor creada correctamente" : "Error al crear asistencia"
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Error interno",
        "debug" => $e->getMessage()
    ]);
}
