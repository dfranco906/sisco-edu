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
        echo json_encode(["status" => "error", "message" => "Seleccione un profesor"]);
        exit;
    }

    $stmt = $db->prepare("SELECT huella_id FROM profesores WHERE id_profesor = :id");
    $stmt->execute([":id" => $id_profesor]);
    $profesor = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$profesor || empty($profesor["huella_id"])) {
        echo json_encode(["status" => "error", "message" => "Este profesor no tiene huella registrada"]);
        exit;
    }

    $a = new AsistenciaProfesor($db);
    $a->id_profesor = $id_profesor;
    $a->huella_id = $profesor["huella_id"];
    $a->fecha = $fecha;
    $a->hora = $hora;
    $a->estado = strtoupper($estado);

    $resultado = $a->crear();

    echo json_encode([
        "status" => $resultado ? "success" : "error",
        "message" => $resultado ? "Asistencia de profesor creada correctamente" : "Error al crear asistencia"
    ]);

} catch (Throwable $e) {
    echo json_encode([
        "status" => "error",
        "message" => "Error interno",
        "debug" => $e->getMessage()
    ]);
}