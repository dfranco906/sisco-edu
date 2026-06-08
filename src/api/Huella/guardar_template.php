<?php
header("Content-Type: application/json; charset=UTF-8");
require_once __DIR__ . '/../../config/db.php';

$db = (new Database())->getConnection();

$id_estudiante = $_POST['id_estudiante'] ?? null;
$template = $_POST['template'] ?? null;
$bytes = $_POST['bytes'] ?? null;

if (!$id_estudiante || !$template) {
    echo json_encode(["status" => "error", "message" => "Faltan datos"]);
    exit;
}

try {
    $db->beginTransaction();

    $user_id_global = "EST_" . $id_estudiante;
    $room_id = "GENERAL";

    $stmt = $db->prepare("
        INSERT INTO huellas_templates
        (user_id_global, id_estudiante, fingerprint_data, formato, pendiente_sync, activo)
        VALUES
        (:user_id_global, :id_estudiante, :fingerprint_data, 'HEX', 1, 1)
    ");

    $stmt->execute([
        ":user_id_global" => $user_id_global,
        ":id_estudiante" => $id_estudiante,
        ":fingerprint_data" => $template
    ]);

    $id_huella = $db->lastInsertId();

    $stmt2 = $db->prepare("
        UPDATE estudiantes
        SET huella_id = :id_huella,
            user_id_global = :user_id_global,
            fingerprint_data_user = :fingerprint_data,
            pendiente_sync = 1
        WHERE id_estudiante = :id_estudiante
    ");

    $stmt2->execute([
        ":id_huella" => $id_huella,
        ":user_id_global" => $user_id_global,
        ":fingerprint_data" => $template,
        ":id_estudiante" => $id_estudiante
    ]);

    $stmt3 = $db->prepare("
        INSERT INTO sync_biometrica
        (id_huella, room_id, estado, intentos)
        VALUES
        (:id_huella, :room_id, 'PENDIENTE', 0)
    ");

    $stmt3->execute([
        ":id_huella" => $id_huella,
        ":room_id" => $room_id
    ]);

    $db->commit();

    echo json_encode([
        "status" => "success",
        "message" => "Huella real guardada y pendiente para Gateway",
        "id_huella" => $id_huella
    ]);

} catch (Throwable $e) {
    if ($db->inTransaction()) $db->rollBack();

    echo json_encode([
        "status" => "error",
        "message" => "Error al guardar template",
        "debug" => $e->getMessage()
    ]);
}