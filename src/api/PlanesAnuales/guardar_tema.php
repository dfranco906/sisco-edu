<?php
header("Content-Type: application/json; charset=UTF-8");
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../classes/PlanAnual.php';

$raw = file_get_contents('php://input');
$data = json_decode($raw, true) ?: $_POST;

$id_capacidad = filter_var($data['id_capacidad'] ?? null, FILTER_VALIDATE_INT);
$titulo = trim($data['titulo'] ?? '');
$horas_catedra = filter_var($data['horas_catedra'] ?? 1, FILTER_VALIDATE_FLOAT) ?: 1.0;
$fecha_inicio = !empty($data['fecha_inicio']) ? trim($data['fecha_inicio']) : null;
$fecha_fin = !empty($data['fecha_fin']) ? trim($data['fecha_fin']) : $fecha_inicio;
$indicadores = is_array($data['indicadores'] ?? null) ? $data['indicadores'] : [];

if (!$id_capacidad || $titulo === '') {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Faltan datos requeridos (id_capacidad, titulo)"]);
    exit;
}

try {
    $db = (new Database())->getConnection();
    $planModel = new PlanAnual($db);

    $id_tema = $planModel->guardarTemaCompleto(
        $id_capacidad,
        $titulo,
        $horas_catedra,
        $fecha_inicio,
        $fecha_fin,
        $indicadores
    );

    http_response_code(201);
    echo json_encode([
        "status" => "success",
        "message" => "Tema y programación guardados exitosamente",
        "data" => [
            "id_tema" => $id_tema,
            "horas_catedra" => $horas_catedra,
            "duracion_minutos" => $horas_catedra * 40
        ]
    ]);
} catch (Throwable $e) {
    error_log("Error en PlanesAnuales/guardar_tema: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Error al guardar tema pedagógico"]);
}
