<?php
header("Content-Type: application/json; charset=UTF-8");
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../classes/NivelAcademico.php';

try {
    $db = (new Database())->getConnection();
    $nivel = new NivelAcademico($db);

    $nivel->nombre = $_POST['nombre'] ?? '';
    $nivel->codigo = $_POST['codigo'] ?? null;
    $nivel->descripcion = $_POST['descripcion'] ?? null;

    if (empty(trim((string)$nivel->nombre))) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "El nombre del nivel o ciclo es obligatorio"]);
        exit;
    }

    $res = $nivel->crear();
    http_response_code($res ? 201 : 400);
    echo json_encode([
        "status" => $res ? "success" : "error",
        "message" => $res ? "Nivel/Ciclo creado correctamente" : "Error al registrar nivel"
    ]);
} catch (Throwable $e) {
    error_log("Error en crear_nivel: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Error interno al crear nivel"]);
}
