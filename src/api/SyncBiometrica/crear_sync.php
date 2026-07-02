<?php
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../classes/SyncBiometrica.php';

$db = (new Database())->getConnection();
$sync = new SyncBiometrica($db);

$id_huella = $_POST['id_huella'] ?? null;
$room_id_recibido = isset($_POST['room_id']) ? trim((string) $_POST['room_id']) : null;

if (!$id_huella) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Falta id_huella"]);
    exit;
}

$stmtHuella = $db->prepare("
    SELECT
        h.id_estudiante,
        h.id_profesor,
        COALESCE(NULLIF(TRIM(e.room_id), ''), NULLIF(TRIM(g.room_id), '')) AS room_estudiante
    FROM huellas_templates h
    LEFT JOIN estudiantes e ON e.id_estudiante = h.id_estudiante
    LEFT JOIN grados g ON g.id_grado = e.id_grado AND g.activo = 1
    WHERE h.id_huella = :id_huella
      AND h.activo = 1
    LIMIT 1
");
$stmtHuella->execute([":id_huella" => $id_huella]);
$huella = $stmtHuella->fetch(PDO::FETCH_ASSOC);

if (!$huella) {
    http_response_code(404);
    echo json_encode(["status" => "error", "message" => "Huella no encontrada"]);
    exit;
}

if ($huella["id_estudiante"] !== null) {
    if ($room_id_recibido !== null && $room_id_recibido !== '') {
        $stmtRoom = $db->prepare("
            SELECT codigo
            FROM aulas
            WHERE codigo = :room_id
              AND activo = 1
            LIMIT 1
        ");
        $stmtRoom->execute([":room_id" => $room_id_recibido]);
        $room_id = $stmtRoom->fetchColumn() ?: null;
    } else {
        $room_id = $huella["room_estudiante"] ?? null;
    }

    if (!$room_id || strcasecmp($room_id, "GENERAL") === 0) {
        http_response_code(422);
        echo json_encode([
            "status" => "error",
            "message" => "El estudiante no tiene room_id asignado. No se puede sincronizar la huella al aula."
        ]);
        exit;
    }
} else {
    $room_id = $room_id_recibido ?: "GENERAL";
}

$sync->id_huella = $id_huella;
$sync->room_id = $room_id;
$sync->estado = $_POST['estado'] ?? 'PENDIENTE';
$sync->intentos = $_POST['intentos'] ?? 0;

$resultado = $sync->crear();

echo json_encode([
    "status" => $resultado ? "success" : "error",
    "message" => $resultado ? "Sync creado" : "Error al crear sync",
    "room_id" => $resultado ? $room_id : null
]);
