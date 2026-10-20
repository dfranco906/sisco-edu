<?php
header("Content-Type: application/json; charset=UTF-8");
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../classes/NivelAcademico.php';

try {
    $db = (new Database())->getConnection();
    $nivel = new NivelAcademico($db);

    $nivel->id_nivel = (int)($_POST['id_nivel'] ?? 0);
    $nivel->nombre = $_POST['nombre'] ?? '';
    $nivel->codigo = $_POST['codigo'] ?? null;
    $nivel->descripcion = $_POST['descripcion'] ?? null;

    if (!$nivel->id_nivel || empty(trim((string)$nivel->nombre))) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Datos incompletos"]);
        exit;
    }

    $res = $nivel->actualizar();
    http_response_code(200);
    echo json_encode([
        "status" => $res ? "success" : "error",
        "message" => $res ? "Nivel/Ciclo actualizado correctamente" : "Error al actualizar nivel"
    ]);
} catch (Throwable $e) {
    error_log("Error en actualizar_nivel: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Error interno al actualizar nivel"]);
}
