<?php
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../classes/Estudiante.php';

try {
    $db = (new Database())->getConnection();
    $estudiante = new Estudiante($db);

    $estudiante->nombre = $_POST['nombre'] ?? null;
    $estudiante->apellido = $_POST['apellido'] ?? null;
    $estudiante->cedula_identidad = $_POST['cedula_identidad'] ?? null;
    $estudiante->id_grado = $_POST['id_grado'] ?? null;

    if (!$estudiante->nombre || !$estudiante->apellido || !$estudiante->cedula_identidad || !$estudiante->id_grado) {
        echo json_encode(["status" => "error", "message" => "Datos incompletos"]);
        exit;
    }

    $resultado = $estudiante->crear();

    echo json_encode([
        "status" => $resultado ? "success" : "error",
        "message" => $resultado ? "Estudiante creado correctamente" : "Error al crear estudiante"
    ]);

} catch (Throwable $e) {
    echo json_encode([
        "status" => "error",
        "message" => "Error interno",
        "debug" => $e->getMessage()
    ]);
}
?>