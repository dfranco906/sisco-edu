<?php
header("Content-Type: application/json; charset=UTF-8");
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../classes/ClaseDiaria.php';

$raw = file_get_contents('php://input');
$data = json_decode($raw, true) ?: $_POST;

$id_aula = filter_var($data['id_aula'] ?? null, FILTER_VALIDATE_INT) ?: 1;
$tipo = strtolower(trim($data['tipo_persona'] ?? 'estudiante'));
$ci = trim($data['ci'] ?? '');
$user_id_global = trim($data['user_id_global'] ?? '');
$id_persona = filter_var($data['id_persona'] ?? null, FILTER_VALIDATE_INT);
$estado = strtoupper(trim($data['estado'] ?? 'PRESENTE'));
$timestamp = trim($data['timestamp'] ?? date('Y-m-d H:i:s'));

if (!in_array($tipo, ['estudiante', 'profesor'], true)) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Tipo de persona inválido"]);
    exit;
}

try {
    $db = (new Database())->getConnection();

    $tabla = ($tipo === 'estudiante') ? 'estudiantes' : 'profesores';
    $idCol = ($tipo === 'estudiante') ? 'id_estudiante' : 'id_profesor';

    // Buscar a la persona
    $where = [];
    $params = [];
    if ($id_persona) {
        $where[] = "{$idCol} = :id";
        $params[':id'] = $id_persona;
    } elseif ($ci !== '') {
        $where[] = "cedula_identidad = :ci";
        $params[':ci'] = $ci;
    } elseif ($user_id_global !== '') {
        $where[] = "user_id_global = :uid";
        $params[':uid'] = $user_id_global;
    } else {
        // Seleccionar la primera persona activa para prueba rápida
        $where[] = "activo = 1";
    }

    $sql = "SELECT {$idCol}, user_id_global, nombre, apellido, cedula_identidad FROM {$tabla} WHERE " . implode(' AND ', $where) . " LIMIT 1";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $persona = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$persona) {
        http_response_code(404);
        echo json_encode(["status" => "error", "message" => "Persona no encontrada"]);
        exit;
    }

    $id = (int)$persona[$idCol];
    $fecha = substr($timestamp, 0, 10);
    $hora = substr($timestamp, 11, 8);

    $claseModel = new ClaseDiaria($db);
    $claseActiva = $claseModel->obtenerOCrearClaseActiva($id_aula, $timestamp);

    if ($tipo === 'estudiante') {
        $claseModel->registrarAsistenciaEstudiante($id, $fecha, $hora, $estado);
    } else {
        $claseModel->registrarAsistenciaProfesor($id, $fecha, $hora, $estado);
    }

    $detalleClase = $claseActiva ? $claseModel->obtenerDetalleClase((int)$claseActiva['id_clase']) : null;

    http_response_code(200);
    echo json_encode([
        "status" => "success",
        "message" => "Huella simulada y asistencia registrada para " . $persona['nombre'] . ' ' . $persona['apellido'],
        "data" => [
            "persona" => [
                "tipo" => $tipo,
                "nombre_completo" => $persona['nombre'] . ' ' . $persona['apellido'],
                "ci" => $persona['cedula_identidad'],
                "user_id_global" => $persona['user_id_global'],
                "hora_marcacion" => $hora
            ],
            "clase" => $detalleClase
        ]
    ]);
} catch (Throwable $e) {
    error_log("Error en Clases/simular_marcacion: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Error al simular marcación"]);
}
