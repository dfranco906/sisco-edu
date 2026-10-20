<?php
header("Content-Type: application/json; charset=UTF-8");
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../classes/ClaseDiaria.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$rol = $_SESSION['rol'] ?? '';
$id_profesor = null;
if ($rol === 'Profesor' && !empty($_SESSION['id_profesor'])) {
    $id_profesor = (int)$_SESSION['id_profesor'];
}

$id_clase = isset($_GET['id_clase']) && is_numeric($_GET['id_clase']) ? (int)$_GET['id_clase'] : null;
$id_aula = isset($_GET['id_aula']) && is_numeric($_GET['id_aula']) ? (int)$_GET['id_aula'] : null;
$timestamp = trim($_GET['timestamp'] ?? date('Y-m-d H:i:s'));

try {
    $db = (new Database())->getConnection();
    $claseModel = new ClaseDiaria($db);

    $clase = null;
    if ($id_clase) {
        $clase = $claseModel->obtenerDetalleClase($id_clase, $id_profesor);
    } else {
        $clase = $claseModel->obtenerOCrearClaseActiva($id_aula, $timestamp, $id_profesor);
    }

    // Aulas y Grados disponibles según el rol
    if ($id_profesor !== null) {
        $stmtAulas = $db->prepare("
            SELECT DISTINCT aul.id_aula, aul.nombre, aul.codigo, aul.ubicacion, g.nombre AS grado_nombre
            FROM asignacion_docente ad
            LEFT JOIN grados g ON g.id_grado = ad.id_grado
            LEFT JOIN horarios h ON h.id_asignacion = ad.id_asignacion
            JOIN aulas aul ON aul.id_aula = COALESCE(h.id_aula, g.id_aula)
            WHERE ad.id_profesor = :id_profesor
              AND ad.activo = 1
              AND aul.activo = 1
            ORDER BY aul.nombre ASC
        ");
        $stmtAulas->execute([':id_profesor' => $id_profesor]);
        $aulas = $stmtAulas->fetchAll(PDO::FETCH_ASSOC);

        $stmtGrados = $db->prepare("
            SELECT DISTINCT g.id_grado, g.nombre, g.id_aula
            FROM asignacion_docente ad
            JOIN grados g ON g.id_grado = ad.id_grado
            WHERE ad.id_profesor = :id_profesor
              AND ad.activo = 1
              AND g.activo = 1
            ORDER BY g.nombre ASC
        ");
        $stmtGrados->execute([':id_profesor' => $id_profesor]);
        $grados = $stmtGrados->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $stmtAulas = $db->query("
            SELECT id_aula, nombre, codigo, ubicacion, '' AS grado_nombre
            FROM aulas
            WHERE activo = 1
            ORDER BY id_aula ASC
        ");
        $aulas = $stmtAulas->fetchAll(PDO::FETCH_ASSOC);

        $stmtGrados = $db->query("
            SELECT id_grado, nombre, id_aula
            FROM grados
            WHERE activo = 1
            ORDER BY id_grado ASC
        ");
        $grados = $stmtGrados->fetchAll(PDO::FETCH_ASSOC);
    }

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
