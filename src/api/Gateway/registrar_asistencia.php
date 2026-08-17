<?php
header("Content-Type: application/json; charset=UTF-8");
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../classes/EventoAsistencia.php';
if ((getallheaders()['X-GATEWAY-KEY'] ?? '') !== GATEWAY_API_KEY) { http_response_code(404); echo json_encode(["status" => "not_found"]); exit; }
$id_aula = filter_input(INPUT_POST, 'id_aula', FILTER_VALIDATE_INT);
$ci = trim($_POST['ci'] ?? ''); $tipo = strtolower(trim($_POST['tipo_persona'] ?? '')); $estado = strtoupper(trim($_POST['estado'] ?? 'PRESENTE'));
if (!$id_aula || !$ci || !in_array($tipo, ['estudiante','profesor'], true)) { http_response_code(400); echo json_encode(["status"=>"error", "message"=>"Faltan datos requeridos"]); exit; }
$db = (new Database())->getConnection();
$stmt = $db->prepare("SELECT user_id_global FROM " . ($tipo === 'estudiante' ? 'estudiantes' : 'profesores') . " WHERE cedula_identidad=:ci AND activo=1 LIMIT 1");
$stmt->execute([':ci'=>$ci]); $userId = $stmt->fetchColumn();
if (!$userId) { http_response_code(404); echo json_encode(["status"=>"error", "message"=>"Persona no encontrada"]); exit; }
$evento = new EventoAsistencia($db); $evento->user_id_global=$userId; $evento->id_aula=$id_aula; $evento->estado=$estado;
$evento->timestamp_evento=date('Y-m-d H:i:s'); $evento->origen_node_id='GATEWAY_ESP32'; $evento->sincronizado=1;
if ($evento->crear()) echo json_encode(["status"=>"success", "message"=>"Asistencia registrada"]);
else { http_response_code(500); echo json_encode(["status"=>"error", "message"=>"Error al guardar evento"]); }