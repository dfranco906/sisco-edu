<?php
declare(strict_types=1);

header("Content-Type: application/json; charset=UTF-8");
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../classes/PlanAnual.php';

verificarRol(['SuperAdmin', 'Administrador', 'Coordinador', 'Profesor']);

$raw = file_get_contents('php://input');
$data = json_decode($raw, true) ?: $_POST;

$id_capacidad = filter_var($data['id_capacidad'] ?? null, FILTER_VALIDATE_INT);
$id_tema = filter_var($data['id_tema'] ?? null, FILTER_VALIDATE_INT) ?: null;
$titulo = trim((string)($data['titulo'] ?? ''));
$horas_catedra = filter_var($data['horas_catedra'] ?? 1, FILTER_VALIDATE_FLOAT) ?: 1.0;
$fecha_inicio = !empty($data['fecha_inicio']) ? trim((string)$data['fecha_inicio']) : null;
$fecha_fin = !empty($data['fecha_fin']) ? trim((string)$data['fecha_fin']) : $fecha_inicio;
$indicadores = is_array($data['indicadores'] ?? null) ? $data['indicadores'] : [];
$procedimientos = is_array($data['procedimientos'] ?? null) ? $data['procedimientos'] : [];
$instrumentos = is_array($data['instrumentos'] ?? null) ? $data['instrumentos'] : [];

if (!$id_capacidad || $titulo === '') {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Faltan datos requeridos (id_capacidad, titulo)"], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $db = (new Database())->getConnection();
    $planModel = new PlanAnual($db);

    $rol = $_SESSION['rol'] ?? null;
    $idProfesorSesion = $_SESSION['id_profesor'] ?? null;

    if ($rol === 'Profesor') {
        $stmtOwner = $db->prepare("
            SELECT a.id_profesor 
            FROM plan_capacidades pc
            JOIN plan_unidades pu ON pu.id_unidad = pc.id_unidad
            JOIN planes_anuales p ON p.id_plan = pu.id_plan
            JOIN asignacion_docente a ON a.id_asignacion = p.id_asignacion 
            WHERE pc.id_capacidad = :id_capacidad LIMIT 1
        ");
        $stmtOwner->execute([':id_capacidad' => $id_capacidad]);
        $ownerProf = $stmtOwner->fetchColumn();
        if ($ownerProf && (int)$ownerProf !== (int)$idProfesorSesion) {
            http_response_code(403);
            echo json_encode(["status" => "error", "message" => "No tiene permiso para editar temas en este plan"], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    $savedTemaId = $planModel->guardarTemaCompleto(
        $id_capacidad,
        $titulo,
        $horas_catedra,
        $fecha_inicio,
        $fecha_fin,
        $indicadores,
        $procedimientos,
        $instrumentos,
        $id_tema
    );

    http_response_code($id_tema ? 200 : 201);
    echo json_encode([
        "status" => "success",
        "message" => $id_tema ? "Tema pedagógico actualizado exitosamente" : "Tema pedagógico planificado exitosamente",
        "data" => [
            "id_tema" => $savedTemaId,
            "horas_catedra" => $horas_catedra,
            "duracion_minutos" => $horas_catedra * 40
        ]
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    error_log("Error en PlanesAnuales/guardar_tema: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Error al guardar tema pedagógico: " . $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
