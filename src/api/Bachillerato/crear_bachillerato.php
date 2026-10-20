<?php
header("Content-Type: application/json; charset=UTF-8");
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../classes/Bachillerato.php';

try {
    $db = (new Database())->getConnection();
    $bach = new Bachillerato($db);

    $bach->nombre = $_POST['nombre'] ?? '';
    $bach->codigo = $_POST['codigo'] ?? '';
    $bach->tipo = $_POST['tipo'] ?? 'Técnico';
    $bach->descripcion = $_POST['descripcion'] ?? null;

    if (empty(trim((string)$bach->nombre)) || empty(trim((string)$bach->codigo))) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "El nombre y el código del bachillerato son obligatorios."]);
        exit;
    }

    $res = $bach->crear();
    $exito = is_array($res) ? ($res['ok'] ?? false) : (bool)$res;
    $codigo = is_array($res) ? ($res['code'] ?? 200) : ($exito ? 201 : 400);
    $mensaje = is_array($res) ? ($res['message'] ?? '') : ($exito ? "Bachillerato creado correctamente" : "Error al registrar el bachillerato");

    http_response_code($codigo);
    echo json_encode([
        "status" => $exito ? "success" : "error",
        "message" => $mensaje
    ]);
} catch (Throwable $e) {
    error_log("Error en crear_bachillerato: " . $e->getMessage());
    $esDuplicado = str_contains($e->getMessage(), '23505') || str_contains($e->getMessage(), 'unique constraint');
    http_response_code($esDuplicado ? 409 : 400);
    echo json_encode([
        "status" => "error",
        "message" => $esDuplicado ? "Ya existe una especialidad con ese código o nombre." : "Error al registrar el bachillerato: " . $e->getMessage()
    ]);
}
