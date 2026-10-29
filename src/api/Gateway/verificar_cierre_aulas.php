<?php
declare(strict_types=1);

header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../classes/MarcoHorario.php';

// Validación de seguridad por clave de Gateway
$headers = function_exists('getallheaders') ? getallheaders() : [];
$headerKey = $headers['X-GATEWAY-KEY'] ?? ($_SERVER['HTTP_X_GATEWAY_KEY'] ?? '');

if (defined('GATEWAY_API_KEY') && !empty(GATEWAY_API_KEY) && $headerKey !== GATEWAY_API_KEY) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Clave de enlace de Gateway no autorizada"]);
    exit;
}

try {
    $db = (new Database())->getConnection();
    $marcoModel = new MarcoHorario($db);

    $horaActual = date('H:i:s');
    $fechaActual = date('Y-m-d');
    $diaSemana = date('w');

    // Consultar todas las aulas activas
    $stmt = $db->query("SELECT id_aula, nombre, numero_aula FROM public.aulas WHERE activo = 1 ORDER BY id_aula ASC");
    $aulas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $aulasCerradas = [];

    foreach ($aulas as $a) {
        $idAula = (int)$a['id_aula'];
        $marco = $marcoModel->resolverMarcoParaAula($idAula, null, $horaActual);

        if (!$marco) {
            continue;
        }

        // Si la jornada para este aula ya alcanzó o superó la hora de cierre
        if ($horaActual >= $marco['hora_cierre']) {
            $aulasCerradas[] = [
                'id_aula' => $idAula,
                'nombre' => $a['nombre'],
                'numero_aula' => $a['numero_aula'],
                'hora_cierre' => $marco['hora_cierre'],
                'grado_nombre' => $marco['grado_info']['grado_nombre'] ?? '',
                'descripcion' => $marco['descripcion'] ?? ''
            ];
        }
    }

    echo json_encode([
        "status" => "success",
        "fecha" => $fechaActual,
        "hora" => $horaActual,
        "total_aulas" => count($aulas),
        "total_cerradas" => count($aulasCerradas),
        "aulas_cerradas" => $aulasCerradas
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    error_log("[verificar_cierre_aulas] Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Error interno al verificar cierre institucional de aulas"
    ], JSON_UNESCAPED_UNICODE);
}
