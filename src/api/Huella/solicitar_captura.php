<?php
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config/db.php';

$db = (new Database())->getConnection();

$id_estudiante = $_POST['id_estudiante'] ?? null;

if (!$id_estudiante) {
    echo json_encode([
        "status" => "error",
        "message" => "Falta id_estudiante"
    ]);
    exit;
}

$query = "INSERT INTO solicitudes_huella (id_estudiante, estado)
          VALUES (:id_estudiante, 'PENDIENTE')";

$stmt = $db->prepare($query);
$stmt->bindParam(":id_estudiante", $id_estudiante);

$resultado = $stmt->execute();

echo json_encode([
    "status" => $resultado ? "success" : "error",
    "message" => $resultado ? "Solicitud de huella creada correctamente" : "Error al crear solicitud"
]);
?>