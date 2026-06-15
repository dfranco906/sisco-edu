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

$id_sync = $_POST['id_sync'] ?? null;
$id_huella = $_POST['id_huella'] ?? null;
$room_id = $_POST['room_id'] ?? null;
$ci = $_POST['ci'] ?? null;
$slot_local = $_POST['slot_local'] ?? null;
$tipo_persona = $_POST['tipo_persona'] ?? null;

if (!$id_sync || !$id_huella || !$room_id || !$ci || !$slot_local || !$tipo_persona) {
    echo json_encode(["status" => "error", "message" => "Faltan datos"]);
    exit;
}

try {
    $db->beginTransaction();

    $stmt = $db->prepare("
        INSERT INTO aula_huellas_sync
        (id_sync, id_huella, room_id, ci, slot_local, tipo_persona, estado)
        VALUES
        (:id_sync, :id_huella, :room_id, :ci, :slot_local, :tipo_persona, 'RECIBIDO')
    ");

    $stmt->execute([
        ":id_sync" => $id_sync,
        ":id_huella" => $id_huella,
        ":room_id" => $room_id,
        ":ci" => $ci,
        ":slot_local" => $slot_local,
        ":tipo_persona" => $tipo_persona
    ]);

    $upd = $db->prepare("
        UPDATE sync_biometrica
        SET estado = 'CONFIRMADO',
            mensaje = 'Huella recibida por aula',
            fecha_actualizacion = NOW()
        WHERE id_sync = :id_sync
    ");

    $upd->execute([":id_sync" => $id_sync]);

    $db->commit();

    echo json_encode([
        "status" => "success",
        "message" => "Aula confirmó recepción de huella"
    ]);

} catch (Throwable $e) {
    if ($db->inTransaction()) $db->rollBack();

    echo json_encode([
        "status" => "error",
        "message" => "Error al confirmar entrega",
        "debug" => $e->getMessage()
    ]);
}