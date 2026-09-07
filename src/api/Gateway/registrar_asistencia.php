<?php
header("Content-Type: application/json; charset=UTF-8");
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../classes/GatewayAttendanceService.php';

function gatewayHeader(string $nombre): string {
    foreach (getallheaders() as $clave=>$valor) if (strcasecmp($clave,$nombre)===0) return (string)$valor;
    return '';
}
if (!hash_equals(GATEWAY_API_KEY, gatewayHeader('X-GATEWAY-KEY'))) { http_response_code(404); echo json_encode(["ok"=>false,"status" => "not_found"]); exit; }
$id_aula = filter_input(INPUT_POST, 'id_aula', FILTER_VALIDATE_INT);
$ci = trim($_POST['ci'] ?? ''); $tipo = strtolower(trim($_POST['tipo_persona'] ?? '')); $estado = strtoupper(trim($_POST['estado'] ?? 'PRESENTE'));
if (!$id_aula || !$ci || !in_array($tipo, ['estudiante','profesor'], true)) { http_response_code(400); echo json_encode(["status"=>"error", "message"=>"Faltan datos requeridos"]); exit; }
$db = (new Database())->getConnection();
try {
    $servicio=new GatewayAttendanceService($db);
    $momento=$servicio->ahoraAutoritativo();
    $resultado=$servicio->registrar($tipo,$ci,(int)$id_aula,$momento);
    $datos=$resultado['data']??[];
    $usuario=null;
    if (!empty($datos['id_evento'])) {
        $q=$db->prepare('SELECT user_id_global FROM eventos_asistencia WHERE id_evento=:evento');
        $q->execute([':evento'=>$datos['id_evento']]);$usuario=$q->fetchColumn()?:null;
    }
    error_log('asistencia_gateway '.json_encode([
        'id_evento'=>$datos['id_evento']??null,'user_id_global'=>$usuario,'tipo_usuario'=>$tipo,
        'id_aula'=>(int)$id_aula,'timestamp_gateway'=>null,
        'timestamp_recepcion_backend'=>$momento->format('Y-m-d H:i:s'),
        'timestamp_efectivo'=>$momento->format('Y-m-d H:i:s'),
        'id_horario'=>$datos['id_horario']??null,'id_clase'=>$datos['id_clase']??null,
        'resultado'=>$resultado['status']??'error'
    ],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
    // El Gateway no reintenta por el cuerpo JSON. Los estados de dominio no
    // recuperables se comunican con HTTP 200 y ok=false, sin fingir exito.
    echo json_encode($resultado, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    error_log('registrar_asistencia_gateway: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(["ok"=>false,"status"=>"error", "message"=>"Error al guardar evento"]);
}
