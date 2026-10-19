<?php
header("Content-Type: application/json; charset=UTF-8");
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../classes/EventoAsistencia.php';
require_once __DIR__ . '/../../classes/ClaseDiaria.php';

// Validación de la clave secreta enviada por el Gateway
$headerKey = getallheaders()['X-GATEWAY-KEY'] ?? ($_SERVER['HTTP_X_GATEWAY_KEY'] ?? '');
if ($headerKey !== GATEWAY_API_KEY) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Clave de Gateway no autorizada"]);
    exit;
}

$id_aula = filter_input(INPUT_POST, 'id_aula', FILTER_VALIDATE_INT);
$ci = trim($_POST['ci'] ?? '');
$tipo = strtolower(trim($_POST['tipo_persona'] ?? ''));
$estado = strtoupper(trim($_POST['estado'] ?? 'PRESENTE'));
$timestamp = trim($_POST['timestamp'] ?? date('Y-m-d H:i:s'));

if (!$id_aula || !$ci || !in_array($tipo, ['estudiante', 'profesor'], true)) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Faltan datos requeridos (id_aula, ci, tipo_persona)"]);
    exit;
}

try {
    $db = (new Database())->getConnection();

    // 1. Obtener la persona por Cédula de Identidad
    $tabla = ($tipo === 'estudiante') ? 'estudiantes' : 'profesores';
    $idCol = ($tipo === 'estudiante') ? 'id_estudiante' : 'id_profesor';
    
    $stmt = $db->prepare("SELECT {$idCol}, user_id_global, nombre, apellido FROM {$tabla} WHERE cedula_identidad = :ci AND activo = 1 LIMIT 1");
    $stmt->execute([':ci' => $ci]);
    $persona = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$persona) {
        http_response_code(404);
        echo json_encode(["status" => "error", "message" => "Persona no encontrada con CI: {$ci}"]);
        exit;
    }

    $userId = $persona['user_id_global'];
    $nombreCompleto = trim($persona['nombre'] . ' ' . $persona['apellido']);

    // 2. Registrar evento crudo de auditoría biométrica
    $evento = new EventoAsistencia($db);
    $evento->user_id_global = $userId;
    $evento->id_aula = $id_aula;
    $evento->estado = $estado;
    $evento->timestamp_evento = $timestamp;
    $evento->origen_node_id = 'GATEWAY_ESP32';
    $evento->sincronizado = 1;
    $evento->crear();

    // 3. Detección automática de la clase activa en el aula y lección del día
    $claseModel = new ClaseDiaria($db);
    $claseActiva = $claseModel->obtenerOCrearClaseActiva($id_aula, $timestamp);

    $fechaActual = substr($timestamp, 0, 10);
    $horaActual = substr($timestamp, 11, 8);

    if ($tipo === 'estudiante') {
        $id_estudiante = (int)$persona['id_estudiante'];
        $claseModel->registrarAsistenciaEstudiante($id_estudiante, $fechaActual, $horaActual, $estado);
    } else {
        $id_profesor = (int)$persona['id_profesor'];
        $claseModel->registrarAsistenciaProfesor($id_profesor, $fechaActual, $horaActual, $estado);
    }

    // Refrescar estado de la clase activa si existe
    $detalleClase = $claseActiva ? $claseModel->obtenerDetalleClase((int)$claseActiva['id_clase']) : null;

    http_response_code(200);
    echo json_encode([
        "status" => "success",
        "message" => "Asistencia biométrica registrada correctamente",
        "data" => [
            "persona" => [
                "tipo" => $tipo,
                "nombre_completo" => $nombreCompleto,
                "cedula" => $ci,
                "user_id_global" => $userId,
                "hora_marcacion" => $horaActual
            ],
            "clase_detectada" => $detalleClase ? [
                "id_clase" => $detalleClase['id_clase'],
                "materia" => $detalleClase['materia_nombre'],
                "profesor" => $detalleClase['profesor_nombre'] . ' ' . $detalleClase['profesor_apellido'],
                "grado" => $detalleClase['grado_nombre'],
                "aula" => $detalleClase['aula_nombre'],
                "horario" => ($detalleClase['horario_inicio'] ?? '') . ' - ' . ($detalleClase['horario_fin'] ?? ''),
                "horas_catedra" => $detalleClase['horas_catedra'] ?? 1,
                "duracion_minutos" => (float)($detalleClase['horas_catedra'] ?? 1) * 40,
                "leccion" => [
                    "unidad" => $detalleClase['unidad_nombre'],
                    "capacidad" => $detalleClase['capacidad_descripcion'],
                    "tema" => $detalleClase['tema_titulo'],
                    "contenido" => $detalleClase['tema_contenido'],
                    "indicadores" => $detalleClase['indicadores']
                ],
                "asistencia_en_vivo" => $detalleClase['metricas']
            ] : null
        ]
    ]);
} catch (Throwable $e) {
    error_log("Error en Gateway registrar_asistencia: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Error interno al registrar asistencia"]);
}