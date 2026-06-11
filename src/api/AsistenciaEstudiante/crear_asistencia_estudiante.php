<?php
header("Content-Type: application/json; charset=UTF-8");
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../classes/AsistenciaEstudiante.php';

try {
    $db = (new Database())->getConnection();

    $id_estudiante = $_POST['id_estudiante'] ?? null;
    $fecha = $_POST['fecha'] ?? date('Y-m-d');
    $hora = $_POST['hora'] ?? date('H:i:s');
    $estado = $_POST['estado'] ?? 'PRESENTE';

    if (!$id_estudiante) {
        echo json_encode(["status" => "error", "message" => "Seleccione un estudiante"]);
        exit;
    }

    $stmt = $db->prepare("SELECT huella_id FROM estudiantes WHERE id_estudiante = :id");
    $stmt->execute([":id" => $id_estudiante]);
    $estudiante = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$estudiante || empty($estudiante["huella_id"])) {
        echo json_encode(["status" => "error", "message" => "Este estudiante no tiene huella registrada"]);
        exit;
    }

    $a = new AsistenciaEstudiante($db);
    $a->id_estudiante = $id_estudiante;
    $a->huella_id = $estudiante["huella_id"];
    $a->fecha = $fecha;
    $a->hora = $hora;
    $a->estado = strtoupper($estado);

    $resultado = $a->crear();

    echo json_encode([
        "status" => $resultado ? "success" : "error",
        "message" => $resultado ? "Asistencia creada correctamente" : "Error al crear asistencia"
    ]);

} catch (Throwable $e) {
    echo json_encode([
        "status" => "error",
        "message" => "Error interno",
        "debug" => $e->getMessage()
    ]);
}