<?php
declare(strict_types=1);

header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../classes/Profesor.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Método no permitido."]);
    exit;
}

try {
    $db = (new Database())->getConnection();
    $profesor = new Profesor($db);

    $profesor->id_profesor = filter_input(INPUT_POST, 'id_profesor', FILTER_VALIDATE_INT) ?: null;
    $profesor->nombre = $_POST['nombre'] ?? null;
    $profesor->apellido = $_POST['apellido'] ?? null;
    $profesor->cedula_identidad = $_POST['cedula_identidad'] ?? null;
    $profesor->email = $_POST['email'] ?? null;
    $profesor->celular = $_POST['celular'] ?? null;

    if ($_POST['user_id_global'] ?? null) {
        $profesor->user_id_global = $_POST['user_id_global'];
    } else {
        $stmtActual = $db->prepare("SELECT user_id_global FROM profesores WHERE id_profesor = :id");
        $stmtActual->execute([":id" => $profesor->id_profesor]);
        $profesor->user_id_global = $stmtActual->fetchColumn() ?: ('PROF_' . uniqid() . '_' . random_int(100, 999));
    }

    if (!$profesor->id_profesor || !$profesor->nombre || !$profesor->apellido || !$profesor->cedula_identidad) {
        http_response_code(422);
        echo json_encode(["status" => "error", "message" => "Datos incompletos para actualizar profesor."]);
        exit;
    }

    $resultado = $profesor->actualizar();

    if ($resultado) {
        http_response_code(200);
        echo json_encode([
            "status" => "success",
            "message" => "Profesor actualizado correctamente"
        ], JSON_UNESCAPED_UNICODE);
    } else {
        http_response_code(500);
        echo json_encode([
            "status" => "error",
            "message" => "Error al actualizar profesor en la base de datos"
        ], JSON_UNESCAPED_UNICODE);
    }
} catch (Throwable $e) {
    error_log("Error en actualizar_profesor: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Error interno del servidor: " . $e->getMessage()]);
}
