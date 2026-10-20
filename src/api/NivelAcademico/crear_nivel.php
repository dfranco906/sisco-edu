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
        echo json_encode(["status" => "error", "message" => "El nombre del nivel es obligatorio"]);
        exit;
    }

    $res = $nivel->crear();
    $exito = is_array($res) ? ($res['ok'] ?? false) : (bool)$res;
    $codigo = is_array($res) ? ($res['code'] ?? 200) : ($exito ? 201 : 400);
    $mensaje = is_array($res) ? ($res['message'] ?? '') : ($exito ? "Nivel creado correctamente" : "Error al registrar el nivel");

    http_response_code($codigo);
    echo json_encode([
        "status" => $exito ? "success" : "error",
        "message" => $mensaje
    ]);
} catch (Throwable $e) {
    error_log("Error en crear_nivel: " . $e->getMessage());
    $esDuplicado = str_contains($e->getMessage(), '23505') || str_contains($e->getMessage(), 'unique constraint');
    http_response_code($esDuplicado ? 409 : 400);
    echo json_encode([
        "status" => "error",
        "message" => $esDuplicado ? "Ya existe un nivel registrado con ese nombre o código." : "Error al registrar el nivel: " . $e->getMessage()
    ]);
}
