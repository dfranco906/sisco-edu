<?php
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../classes/Estudiante.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Metodo no permitido"]);
    exit;
}

try {
    $db = (new Database())->getConnection();
    $estudiante = new Estudiante($db);

    $estudiante->id_estudiante = $_POST['id_estudiante'] ?? null;
    $estudiante->nombre = $_POST['nombre'] ?? null;
    $estudiante->apellido = $_POST['apellido'] ?? null;
    $estudiante->cedula_identidad = $_POST['cedula_identidad'] ?? null;
    $estudiante->id_grado = $_POST['id_grado'] ?? null;

    if (!$estudiante->id_estudiante || !$estudiante->nombre || !$estudiante->apellido || !$estudiante->cedula_identidad) {
        echo json_encode(["status" => "error", "message" => "Datos incompletos"]);
        exit;
    }

    $resultado = $estudiante->actualizar();

    echo json_encode([
        "status" => $resultado ? "success" : "error",
        "message" => $resultado ? "Estudiante actualizado correctamente" : "Error al actualizar estudiante"
    ]);

} catch (Throwable $e) {
    echo json_encode([
        "status" => "error",
        "message" => "Error interno",
        "debug" => $e->getMessage()
    ]);
}
?>
