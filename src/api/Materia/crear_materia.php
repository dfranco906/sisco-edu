<?php
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../classes/Materia.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Método no permitido"]);
    exit;
}

$db = (new Database())->getConnection();
$materia = new Materia($db);

$materia->nombre = trim((string)($_POST['nombre'] ?? ''));
$materia->descripcion = trim((string)($_POST['descripcion'] ?? ''));
$materia->carga_horaria_semanal = (int)($_POST['carga_horaria_semanal'] ?? $_POST['carga_horaria'] ?? 0);

if (empty($materia->nombre)) {
    http_response_code(422);
    echo json_encode([
        "status" => "error",
        "message" => "El nombre de la materia es obligatorio"
    ]);
    exit;
}

try {
    $resultado = $materia->crear();
    if ($resultado) {
        echo json_encode([
            "status" => "success",
            "message" => "Materia registrada correctamente"
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            "status" => "error",
            "message" => "Error al registrar la materia en la base de datos"
        ]);
    }
} catch (Throwable $e) {
    error_log("Error creando materia: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Ocurrió un error inesperado al registrar la materia"
    ]);
}
?>
