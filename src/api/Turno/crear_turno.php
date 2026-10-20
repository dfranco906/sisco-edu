<?php
header("Content-Type: application/json; charset=UTF-8");
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../classes/Turno.php';

try {
    $db = (new Database())->getConnection();
    $turno = new Turno($db);

    $turno->nombre = $_POST['nombre'] ?? '';
    $turno->hora_inicio = $_POST['hora_inicio'] ?? null;
    $turno->hora_fin = $_POST['hora_fin'] ?? null;
    $turno->descripcion = $_POST['descripcion'] ?? null;

    if (empty(trim((string)$turno->nombre))) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "El nombre del turno es obligatorio"]);
        exit;
    }

    $res = $turno->crear();
    $exito = is_array($res) ? ($res['ok'] ?? false) : (bool)$res;
    $codigo = is_array($res) ? ($res['code'] ?? 200) : ($exito ? 201 : 400);
    $mensaje = is_array($res) ? ($res['message'] ?? '') : ($exito ? "Turno creado correctamente" : "Error al registrar el turno");

    http_response_code($codigo);
    echo json_encode([
        "status" => $exito ? "success" : "error",
        "message" => $mensaje
    ]);
} catch (Throwable $e) {
    error_log("Error en crear_turno: " . $e->getMessage());
    $esDuplicado = str_contains($e->getMessage(), '23505') || str_contains($e->getMessage(), 'unique constraint');
    http_response_code($esDuplicado ? 409 : 400);
    echo json_encode([
        "status" => "error",
        "message" => $esDuplicado ? "Ya existe un turno registrado con ese nombre." : "Error al registrar el turno: " . $e->getMessage()
    ]);
}
