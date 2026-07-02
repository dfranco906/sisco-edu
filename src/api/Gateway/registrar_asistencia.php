<?php
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../classes/EventoAsistencia.php';

$headers = getallheaders();
$key = $headers['X-GATEWAY-KEY'] ?? '';

if ($key !== GATEWAY_API_KEY) {
    http_response_code(404);
    echo json_encode(["status" => "not_found"]);
    exit;
}

$db = (new Database())->getConnection();

$room_id = $_POST['room_id'] ?? null;
$ci = $_POST['ci'] ?? null;
$tipo_persona = $_POST['tipo_persona'] ?? null;
$estado = $_POST['estado'] ?? null; // ENTRADA / SALIDA / etc.
$fecha_hora = $_POST['fecha_hora'] ?? null;

if (!$room_id || !$ci || !$tipo_persona) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Faltan datos requeridos"]);
    exit;
}

// Buscar el user_id_global correspondiente usando la cédula y el tipo de persona
$user_id_global = null;
if ($tipo_persona === 'estudiante') {
    $stmt = $db->prepare("SELECT user_id_global FROM estudiantes WHERE cedula_identidad = :ci AND activo = 1 LIMIT 1");
    $stmt->execute([":ci" => $ci]);
    $user_id_global = $stmt->fetchColumn();
} elseif ($tipo_persona === 'profesor') {
    $stmt = $db->prepare("SELECT user_id_global FROM profesores WHERE cedula_identidad = :ci AND activo = 1 LIMIT 1");
    $stmt->execute([":ci" => $ci]);
    $user_id_global = $stmt->fetchColumn();
}

if (!$user_id_global) {
    http_response_code(404);
    echo json_encode(["status" => "error", "message" => "Persona no encontrada en el sistema con esa C.I."]);
    exit;
}

// Crear el registro de EventoAsistencia
$evento = new EventoAsistencia($db);
$evento->user_id_global = $user_id_global;
$evento->room_id = $room_id;
// Si viene fecha_hora del gateway la usamos, de lo contrario la fecha y hora actual
$evento->timestamp_evento = $fecha_hora ?: date('Y-m-d H:i:s');
$evento->origen_node_id = 'GATEWAY_ESP32';
$evento->sincronizado = 1;

if ($evento->crear()) {
    echo json_encode(["status" => "success", "message" => "Asistencia registrada correctamente"]);
} else {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Error al guardar el evento de asistencia"]);
}
?>
