<?php
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config/db.php';

$db = (new Database())->getConnection();

$huella_id = $_GET['huella_id'] ?? null;

if (!$huella_id) {
    echo json_encode([
        "status" => "error",
        "message" => "Falta huella_id"
    ]);
    exit;
}

$stmt = $db->prepare("
    SELECT 
        h.id_huella,
        h.user_id_global,
        h.fingerprint_data,
        h.formato,
        h.activo,
        e.id_estudiante,
        e.nombre AS estudiante_nombre,
        e.apellido AS estudiante_apellido,
        e.cedula_identidad AS estudiante_cedula,
        p.id_profesor,
        p.nombre AS profesor_nombre,
        p.apellido AS profesor_apellido,
        p.cedula_identidad AS profesor_cedula
    FROM huellas_templates h
    LEFT JOIN estudiantes e ON h.id_estudiante = e.id_estudiante
    LEFT JOIN profesores p ON h.id_profesor = p.id_profesor
    WHERE h.id_huella = :huella_id
      AND h.activo = 1
    LIMIT 1
");

$stmt->execute([":huella_id" => $huella_id]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$data) {
    echo json_encode([
        "status" => "not_found",
        "message" => "Huella no encontrada"
    ]);
    exit;
}

$tipo = $data["id_estudiante"] ? "estudiante" : "profesor";

echo json_encode([
    "status" => "success",
    "message" => "Huella encontrada",
    "tipo" => $tipo,
    "data" => $data
]);