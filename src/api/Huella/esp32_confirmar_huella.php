<?php
header("Content-Type: application/json; charset=UTF-8");
require_once __DIR__ . '/../../config/db.php';

$db = (new Database())->getConnection();

$id_solicitud = $_POST['id_solicitud'] ?? null;
$id_estudiante = $_POST['id_estudiante'] ?? null;
$id_huella = $_POST['id_huella'] ?? null;

if (!$id_solicitud || !$id_estudiante || !$id_huella) {
    echo json_encode(["status" => "error", "message" => "Faltan datos"]);
    exit;
}

try {
    $db->beginTransaction();

    $stmt2 = $db->prepare("
        UPDATE solicitudes_huella
        SET estado = 'COMPLETADO',
            huella_id = :huella_id,
            mensaje = 'Huella registrada correctamente'
        WHERE id_solicitud = :id_solicitud
    ");

    $stmt2->execute([
        ":huella_id" => $id_huella,
        ":id_solicitud" => $id_solicitud
    ]);

    $db->commit();

    echo json_encode(["status" => "success", "message" => "Huella guardada"]);
} catch (Exception $e) {
    if ($db->inTransaction()) $db->rollBack();
    echo json_encode(["status" => "error", "message" => "Error al guardar huella"]);
}