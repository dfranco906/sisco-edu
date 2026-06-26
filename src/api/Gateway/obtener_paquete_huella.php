<?php
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/app.php';

$headers = getallheaders();
$key = $headers['X-GATEWAY-KEY'] ?? '';

if ($key !== GATEWAY_API_KEY) {
    http_response_code(404);
    echo json_encode(["status" => "not_found"]);
    exit;
}

$huella_id = $_GET['huella_id'] ?? null;

if (!$huella_id) {
    echo json_encode(["status" => "error", "message" => "Falta huella_id"]);
    exit;
}

$db = (new Database())->getConnection();

$stmt = $db->prepare("
    SELECT 
        h.id_huella,
        h.user_id_global,
        h.fingerprint_data AS huella_base64,
        h.formato,

        e.id_estudiante,
        e.cedula_identidad AS ci_estudiante,
        e.room_id AS room_estudiante,

        p.id_profesor,
        p.cedula_identidad AS ci_profesor

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
    echo json_encode(["status" => "not_found", "message" => "Huella no encontrada"]);
    exit;
}

$tipo = $data["id_estudiante"] ? "estudiante" : "profesor";
$ci = $tipo === "estudiante" ? $data["ci_estudiante"] : $data["ci_profesor"];
$room_id = $tipo === "estudiante" ? ($data["room_estudiante"] ?? "GENERAL") : "GENERAL";

echo json_encode([
    "status" => "success",
    "data" => [
        "id_huella" => $data["id_huella"],
        "user_id_global" => $data["user_id_global"],
        "tipo_persona" => $tipo,
        "ci" => $ci,
        "room_id" => $room_id,
        "huella_base64" => $data["huella_base64"],
        "formato" => $data["formato"]
    ]
]);