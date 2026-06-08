<?php
header("Content-Type: application/json; charset=UTF-8");
require_once __DIR__ . '/../../config/db.php';

$db = (new Database())->getConnection();

$id_persona = $_POST['id_persona'] ?? $_POST['id_estudiante'] ?? null;
$tipo_persona = $_POST['tipo_persona'] ?? 'estudiante';
$template = $_POST['template'] ?? null;

if (!$id_persona || !$template) {
    echo json_encode(["status" => "error", "message" => "Faltan datos"]);
    exit;
}

try {
    $db->beginTransaction();

    if ($tipo_persona === "profesor") {
        $tabla = "profesores";
        $idCampo = "id_profesor";
        $user_id_global = "PROF_" . $id_persona;
    } else {
        $tabla = "estudiantes";
        $idCampo = "id_estudiante";
        $user_id_global = "EST_" . $id_persona;
    }

    $stmt = $db->prepare("
        INSERT INTO huellas_templates
        (user_id_global, {$idCampo}, fingerprint_data, formato, pendiente_sync, activo)
        VALUES
        (:user_id_global, :id_persona, :fingerprint_data, 'HEX', 1, 1)
    ");

    $stmt->execute([
        ":user_id_global" => $user_id_global,
        ":id_persona" => $id_persona,
        ":fingerprint_data" => $template
    ]);

    $id_huella = $db->lastInsertId();

    $stmt2 = $db->prepare("
        UPDATE {$tabla}
        SET huella_id = :id_huella,
            user_id_global = :user_id_global,
            pendiente_sync = 1
        WHERE {$idCampo} = :id_persona
    ");

    $stmt2->execute([
        ":id_huella" => $id_huella,
        ":user_id_global" => $user_id_global,
        ":id_persona" => $id_persona
    ]);

    $stmt3 = $db->prepare("
        INSERT INTO sync_biometrica
        (id_huella, room_id, estado, intentos)
        VALUES
        (:id_huella, 'GENERAL', 'PENDIENTE', 0)
    ");

    $stmt3->execute([":id_huella" => $id_huella]);

    $db->commit();

    echo json_encode([
        "status" => "success",
        "message" => "Huella guardada correctamente y pendiente para Gateway",
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