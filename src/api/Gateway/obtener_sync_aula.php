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

$db = (new Database())->getConnection();

$room_id = $_GET['room_id'] ?? 'GENERAL';

$stmt = $db->prepare("
    SELECT 
        s.id_sync,
        s.id_huella,
        s.room_id,
        h.user_id_global,
        h.fingerprint_data AS huella_base64,
        h.formato,
        e.id_estudiante,
        e.cedula_identidad AS ci_estudiante,
        e.nombre AS nombre_estudiante,
        e.apellido AS apellido_estudiante,
        NULL AS grado,
        p.id_profesor,
        p.cedula_identidad AS ci_profesor,
        p.nombre AS nombre_profesor,
        p.apellido AS apellido_profesor
    FROM sync_biometrica s
    INNER JOIN huellas_templates h ON s.id_huella = h.id_huella
    LEFT JOIN estudiantes e ON h.id_estudiante = e.id_estudiante
    LEFT JOIN profesores p ON h.id_profesor = p.id_profesor
    WHERE s.estado = 'PENDIENTE'
      AND h.activo = 1
      AND (s.room_id = :room_id OR s.room_id = 'GENERAL')
    ORDER BY s.id_sync ASC
    LIMIT 1
");

$stmt->execute([":room_id" => $room_id]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$data) {
    echo json_encode(["status" => "empty", "message" => "No hay sincronizaciones pendientes"]);
    exit;
}

$tipo = $data["id_estudiante"] ? "estudiante" : "profesor";
$ci = $tipo === "estudiante" ? $data["ci_estudiante"] : $data["ci_profesor"];

$db->prepare("
    UPDATE sync_biometrica
    SET estado='EN_PROCESO', intentos=intentos+1
    WHERE id_sync=:id_sync
")->execute([":id_sync" => $data["id_sync"]]);

echo json_encode([
    "status" => "success",
    "data" => [
        "id_sync" => $data["id_sync"],
        "id_huella" => $data["id_huella"],
        "tipo_persona" => $tipo,
        "ci" => $ci,
        "room_id" => $data["room_id"],
        "grado" => $data["grado"] ?? null,
        "huella_base64" => $data["huella_base64"],
        "formato" => $data["formato"]
    ]
]);