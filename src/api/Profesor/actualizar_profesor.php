<?php
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../classes/Profesor.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Metodo no permitido"]);
    exit;
}

$db = (new Database())->getConnection();
$profesor = new Profesor($db);

    $profesor->id_profesor = $_POST['id_profesor'] ?? null;
    $profesor->nombre = $_POST['nombre'] ?? null;
    $profesor->apellido = $_POST['apellido'] ?? null;
    $profesor->cedula_identidad = $_POST['cedula_identidad'] ?? null;

    if ($_POST['user_id_global'] ?? null) {
        $profesor->user_id_global = $_POST['user_id_global'];
    } else {
        $stmtActual = $db->prepare("SELECT user_id_global FROM profesores WHERE id_profesor = :id");
        $stmtActual->execute([":id" => $profesor->id_profesor]);
        $profesor->user_id_global = $stmtActual->fetchColumn() ?: ('PROF_' . uniqid() . '_' . random_int(100, 999));
    }

    if (!$profesor->id_profesor || !$profesor->nombre || !$profesor->apellido || !$profesor->cedula_identidad) {
    echo json_encode(["status" => "error", "message" => "Datos incompletos"]);
    exit;
}

$resultado = $profesor->actualizar();

echo json_encode([
    "status" => $resultado ? "success" : "error",
    "message" => $resultado ? "Profesor actualizado correctamente" : "Error al actualizar profesor"
]);
?>
