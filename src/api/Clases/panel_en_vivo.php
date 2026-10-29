<?php
header("Content-Type: application/json; charset=UTF-8");
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../classes/ClaseDiaria.php';
require_once __DIR__ . '/../../classes/AlcanceUsuario.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$rol = $_SESSION['rol'] ?? '';
$idUsuarioSesion = (int)($_SESSION['id_usuario'] ?? 0);
$id_profesor = null;
if ($rol === 'Profesor' && !empty($_SESSION['id_profesor'])) {
    $id_profesor = (int)$_SESSION['id_profesor'];
}

$id_clase = isset($_GET['id_clase']) && is_numeric($_GET['id_clase']) ? (int)$_GET['id_clase'] : null;
$id_grado = isset($_GET['id_grado']) && is_numeric($_GET['id_grado']) ? (int)$_GET['id_grado'] : null;
$timestamp = trim($_GET['timestamp'] ?? date('Y-m-d H:i:s'));
$solo_clase = isset($_GET['solo_clase']) && (string)$_GET['solo_clase'] === '1';

try {
    $db = (new Database())->getConnection();

    // Segmentación granular de permisos para Coordinadores y Administradores
    $gradosPermitidos = AlcanceUsuario::obtenerGradosPermitidos($db, $idUsuarioSesion, $rol);
    if ($gradosPermitidos !== null && $id_grado !== null && !in_array($id_grado, $gradosPermitidos, true)) {
        http_response_code(403);
        echo json_encode([
            "status" => "error",
            "message" => "Acceso restringido: no tiene autorización sobre este curso."
        ]);
        exit;
    }

    $claseModel = new ClaseDiaria($db);

    $clase = null;

    // Solo consultar o crear clase si se especificó explícitamente un id_clase, id_grado, o si es profesor con sesión
    if ($id_clase) {
        $clase = $claseModel->obtenerDetalleClase($id_clase, $id_profesor);
    } elseif ($id_grado || $id_profesor !== null) {
        $clase = $claseModel->obtenerOCrearClaseActiva(null, $timestamp, $id_profesor, $id_grado);
    }

    $servidorInfo = [
        "fecha_actual" => date('Y-m-d'),
        "hora_actual" => date('H:i:s'),
        "timestamp" => $timestamp,
        "dia_semana" => ClaseDiaria::obtenerDiaEspanol((int)date('w'))['con_tilde']
    ];

    // Si la petición es un refresco ligero (solo estado de la clase activa)
    if ($solo_clase) {
        http_response_code(200);
        echo json_encode([
            "status" => "success",
            "message" => $clase ? "Detalle de clase en vivo obtenido" : "No hay clase activa en este momento para el curso seleccionado",
            "data" => [
                "clase" => $clase,
                "servidor" => $servidorInfo
            ]
        ]);
        exit;
    }

    // Carga de catálogo para los selectores en cascada
    $stmtTurnos = $db->query("SELECT id_turno, nombre FROM turnos WHERE activo = 1 ORDER BY id_turno ASC");
    $turnos = $stmtTurnos->fetchAll(PDO::FETCH_ASSOC);

    $stmtNiveles = $db->query("SELECT id_nivel, nombre FROM niveles_academicos WHERE activo = 1 ORDER BY id_nivel ASC");
    $niveles = $stmtNiveles->fetchAll(PDO::FETCH_ASSOC);

    $stmtBachi = $db->query("SELECT id_bachillerato, nombre, codigo FROM bachilleratos WHERE activo = 1 ORDER BY id_bachillerato ASC");
    $bachilleratos = $stmtBachi->fetchAll(PDO::FETCH_ASSOC);

    if ($id_profesor !== null) {
        $stmtGrados = $db->prepare("
            SELECT DISTINCT g.id_grado, g.nombre, g.seccion, g.id_aula, g.id_turno, g.id_nivel, g.id_bachillerato,
                            aul.nombre AS aula_nombre, aul.ubicacion AS aula_ubicacion
            FROM asignacion_docente ad
            JOIN grados g ON g.id_grado = ad.id_grado
            LEFT JOIN aulas aul ON aul.id_aula = g.id_aula
            WHERE ad.id_profesor = :id_profesor
              AND ad.activo = 1
              AND g.activo = 1
            ORDER BY g.nombre ASC
        ");
        $stmtGrados->execute([':id_profesor' => $id_profesor]);
        $grados = $stmtGrados->fetchAll(PDO::FETCH_ASSOC);
    } else {
        if ($gradosPermitidos !== null) {
            if (empty($gradosPermitidos)) {
                $grados = [];
            } else {
                $placeholders = implode(',', array_fill(0, count($gradosPermitidos), '?'));
                $stmtGrados = $db->prepare("
                    SELECT g.id_grado, g.nombre, g.seccion, g.id_aula, g.id_turno, g.id_nivel, g.id_bachillerato,
                           aul.nombre AS aula_nombre, aul.ubicacion AS aula_ubicacion
                    FROM grados g
                    LEFT JOIN aulas aul ON aul.id_aula = g.id_aula
                    WHERE g.activo = 1 AND g.id_grado IN ($placeholders)
                    ORDER BY g.nombre ASC
                ");
                $stmtGrados->execute($gradosPermitidos);
                $grados = $stmtGrados->fetchAll(PDO::FETCH_ASSOC);
            }
        } else {
            $stmtGrados = $db->query("
                SELECT g.id_grado, g.nombre, g.seccion, g.id_aula, g.id_turno, g.id_nivel, g.id_bachillerato,
                       aul.nombre AS aula_nombre, aul.ubicacion AS aula_ubicacion
                FROM grados g
                LEFT JOIN aulas aul ON aul.id_aula = g.id_aula
                WHERE g.activo = 1
                ORDER BY g.nombre ASC
            ");
            $grados = $stmtGrados->fetchAll(PDO::FETCH_ASSOC);
        }
    }

    http_response_code(200);
    echo json_encode([
        "status" => "success",
        "message" => $clase ? "Detalle de clase en vivo obtenido" : "Seleccione un grado o curso para monitorear",
        "data" => [
            "clase" => $clase,
            "grados" => $grados,
            "turnos" => $turnos,
            "niveles" => $niveles,
            "bachilleratos" => $bachilleratos,
            "servidor" => $servidorInfo
        ]
    ]);
} catch (Throwable $e) {
    error_log("Error en Clases/panel_en_vivo: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Error al cargar monitor de clase en vivo"]);
}
