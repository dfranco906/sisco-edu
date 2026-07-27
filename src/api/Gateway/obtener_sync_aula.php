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

$id_aula = $_GET['id_aula'] ?? 'GENERAL';

$stmt = $db->prepare("
    SELECT 
        s.id_sync,
        s.id_huella,
        s.id_aula,
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
    LEFT JOIN estudiantes e ON h.user_id_global = e.user_id_global
    LEFT JOIN profesores p ON h.user_id_global = p.user_id_global
    WHERE s.estado = 'PENDIENTE'
      AND h.activo = 1
      AND (s.id_aula = :id_aula OR s.id_aula IS NULL)
      AND NOT (s.id_aula IS NULL AND h.user_id_global IN (SELECT user_id_global FROM estudiantes WHERE activo = 1))
    ORDER BY s.id_sync ASC
    LIMIT 1
");

$stmt->execute([":id_aula" => $id_aula]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$data) {
    echo json_encode(["status" => "empty", "message" => "No hay sincronizaciones pendientes"]);
    exit;
}

$tipo = $data["id_estudiante"] ? "estudiante" : "profesor";
$ci = $tipo === "estudiante" ? $data["ci_estudiante"] : $data["ci_profesor"];

$db->prepare("
    UPDATE sync_biometrica
    SET estado='ENVIADO', intentos=intentos+1
    WHERE id_sync=:id_sync
")->execute([":id_sync" => $data["id_sync"]]);

echo json_encode([
    "status" => "success",
    "data" => [
        "id_sync" => $data["id_sync"],
        "id_huella" => $data["id_huella"],
        "tipo_persona" => $tipo,
        "ci" => $ci,
        "id_aula" => $data["id_aula"],
        "grado" => $data["grado"] ?? null,
        "huella_base64" => $data["huella_base64"],
        "formato" => $data["formato"]
    ]
]);
