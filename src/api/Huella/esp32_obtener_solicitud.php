<?php
header("Content-Type: application/json; charset=UTF-8");
require_once __DIR__ . '/../../config/db.php';

$db = (new Database())->getConnection();

$stmt = $db->prepare("
    SELECT id_solicitud, id_estudiante
    FROM solicitudes_huella
    WHERE estado = 'PENDIENTE'
    ORDER BY id_solicitud ASC
    LIMIT 1
");
$stmt->execute();

$solicitud = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$solicitud) {
    echo json_encode(["status" => "empty"]);
    exit;
}

$upd = $db->prepare("
    UPDATE solicitudes_huella
    SET estado = 'EN_PROCESO'
    WHERE id_solicitud = :id
");
$upd->execute([":id" => $solicitud["id_solicitud"]]);

echo json_encode([
    "status" => "success",
    "data" => $solicitud
]);