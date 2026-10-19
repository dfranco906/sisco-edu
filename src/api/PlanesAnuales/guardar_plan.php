<?php
declare(strict_types=1);

header("Content-Type: application/json; charset=UTF-8");
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../classes/PlanAnual.php';

verificarRol(['SuperAdmin', 'Administrador', 'Coordinador', 'Profesor']);

$raw = file_get_contents('php://input');
$data = json_decode($raw, true) ?: $_POST;

$id_plan = filter_var($data['id_plan'] ?? null, FILTER_VALIDATE_INT) ?: null;
$id_asignacion = filter_var($data['id_asignacion'] ?? null, FILTER_VALIDATE_INT);
$anio = filter_var($data['anio'] ?? date('Y'), FILTER_VALIDATE_INT) ?: (int)date('Y');
$competencia_general = trim((string)($data['competencia_general'] ?? ''));
$competencia_especifica = trim((string)($data['competencia_especifica'] ?? ''));
$turno_fuente = trim((string)($data['turno_fuente'] ?? 'MAÑANA')) ?: 'MAÑANA';
$institucion_fuente = trim((string)($data['institucion_fuente'] ?? 'COLEGIO PRIV. PRESB. SAN PABLO')) ?: 'COLEGIO PRIV. PRESB. SAN PABLO';
$estado = trim((string)($data['estado'] ?? 'PUBLICADO')) ?: 'PUBLICADO';
$observaciones = !empty($data['observaciones']) ? trim((string)$data['observaciones']) : null;

if (!$id_asignacion) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Debe seleccionar una materia / asignación válida"], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $db = (new Database())->getConnection();
    $planModel = new PlanAnual($db);

    $rol = $_SESSION['rol'] ?? null;
    $idProfesorSesion = $_SESSION['id_profesor'] ?? null;
    $idUsuario = (int)($_SESSION['id_usuario'] ?? 0);

    // Si es Profesor, validar que la asignación le corresponda
    if ($rol === 'Profesor') {
        $stmtAsig = $db->prepare("SELECT 1 FROM asignacion_docente WHERE id_asignacion = :asig AND id_profesor = :prof LIMIT 1");
        $stmtAsig->execute([':asig' => $id_asignacion, ':prof' => $idProfesorSesion]);
        if (!$stmtAsig->fetchColumn()) {
            http_response_code(403);
            echo json_encode(["status" => "error", "message" => "No tiene permiso para gestionar planes en esta asignación"], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Si es edición, validar que el plan pertenezca a su asignación
        if ($id_plan !== null) {
            $stmtOwner = $db->prepare("SELECT a.id_profesor FROM planes_anuales p JOIN asignacion_docente a ON a.id_asignacion = p.id_asignacion WHERE p.id_plan = :id_plan LIMIT 1");
            $stmtOwner->execute([':id_plan' => $id_plan]);
            $ownerProf = $stmtOwner->fetchColumn();
            if ($ownerProf && (int)$ownerProf !== (int)$idProfesorSesion) {
                http_response_code(403);
                echo json_encode(["status" => "error", "message" => "No tiene permiso para modificar este plan anual"], JSON_UNESCAPED_UNICODE);
                exit;
            }
        }
    }

    $savedId = $planModel->guardarPlan(
        $id_asignacion,
        $anio,
        $competencia_general,
        $competencia_especifica,
        $turno_fuente,
        $institucion_fuente,
        $estado,
        $observaciones,
        $id_plan,
        $idUsuario
    );

    http_response_code($id_plan ? 200 : 201);
    echo json_encode([
        "status" => "success",
        "message" => $id_plan ? "Plan anual actualizado exitosamente" : "Plan anual creado exitosamente",
        "data" => ["id_plan" => $savedId]
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    error_log("Error en PlanesAnuales/guardar_plan: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Error al guardar plan anual: " . $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
