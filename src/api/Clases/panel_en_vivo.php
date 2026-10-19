<?php
header("Content-Type: application/json; charset=UTF-8");
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../classes/ClaseDiaria.php';

$id_clase = filter_input(INPUT_GET, 'id_clase', FILTER_VALIDATE_INT);
$id_aula = filter_input(INPUT_GET, 'id_aula', FILTER_VALIDATE_INT);
$timestamp = trim($_GET['timestamp'] ?? date('Y-m-d H:i:s'));

try {
    $db = (new Database())->getConnection();
    $claseModel = new ClaseDiaria($db);

    $clase = null;
    if ($id_clase) {
        $clase = $claseModel->obtenerDetalleClase($id_clase);
    } else {
        // Si no se pasa aula, buscar la primera aula activa que tenga horarios hoy o aula con id más bajo
        if (!$id_aula) {
            $stmtAula = $db->query("
                SELECT COALESCE(
                    (SELECT id_aula FROM horarios WHERE activo = 1 AND id_aula IS NOT NULL LIMIT 1),
                    (SELECT id_aula FROM aulas WHERE activo = 1 ORDER BY id_aula ASC LIMIT 1),
                    1
                )
            ");
            $id_aula = (int)$stmtAula->fetchColumn();
        }
        $clase = $claseModel->obtenerOCrearClaseActiva($id_aula, $timestamp);
    }

    // Obtener lista de aulas disponibles para el selector rápido del panel
    $stmtAulas = $db->query("SELECT id_aula, nombre, codigo, ubicacion FROM aulas WHERE activo = 1 ORDER BY id_aula ASC");
    $aulas = $stmtAulas->fetchAll(PDO::FETCH_ASSOC);

    // Obtener lista de grados disponibles
    $stmtGrados = $db->query("SELECT id_grado, nombre, id_aula FROM grados WHERE activo = 1 ORDER BY id_grado ASC");
    $grados = $stmtGrados->fetchAll(PDO::FETCH_ASSOC);

    http_response_code(200);
    echo json_encode([
        "status" => "success",
        "message" => $clase ? "Detalle de clase en vivo obtenido" : "No hay clase activa en este momento para los parámetros seleccionados",
        "data" => [
            "clase" => $clase,
            "aulas" => $aulas,
            "grados" => $grados,
            "servidor" => [
                "fecha_actual" => date('Y-m-d'),
                "hora_actual" => date('H:i:s'),
                "timestamp" => $timestamp,
                "dia_semana" => ClaseDiaria::obtenerDiaEspanol((int)date('w'))['con_tilde']
            ]
        ]
    ]);
} catch (Throwable $e) {
    error_log("Error en Clases/panel_en_vivo: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Error al cargar monitor de clase en vivo"]);
}
