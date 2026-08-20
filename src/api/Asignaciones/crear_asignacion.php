<?php
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../classes/Asignacion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["success" => false, "status" => "error", "message" => "Método no permitido."]);
    exit;
}

$idProfesor = filter_var($_POST['id_profesor'] ?? null, FILTER_VALIDATE_INT);
$idMateria = filter_var($_POST['id_materia'] ?? null, FILTER_VALIDATE_INT);
$idGrado = filter_var($_POST['id_grado'] ?? null, FILTER_VALIDATE_INT);
$cargaHoraria = filter_var($_POST['carga_horaria'] ?? null, FILTER_VALIDATE_INT);
$anio = $_POST['anio_lectivo']
    ?? $_POST['anio']
    ?? $_POST["a\u{00F1}o_lectivo"]
    ?? $_POST["a\u{00C3}\u{00B1}o_lectivo"]
    ?? null;
$anio = filter_var($anio, FILTER_VALIDATE_INT);

if (!$idProfesor || !$idMateria || !$idGrado || !$cargaHoraria || $cargaHoraria < 1 || $cargaHoraria > 100 || !$anio || $anio < 2000 || $anio > 2100) {
    http_response_code(422);
    echo json_encode([
        "success" => false,
        "status" => "error",
        "message" => "Profesor, materia, grado, carga horaria y año lectivo válidos son obligatorios."
    ]);
    exit;
}

try {
    $db = (new Database())->getConnection();
    $asignacion = new Asignacion($db);
    $asignacion->id_profesor = $idProfesor;
    $asignacion->id_materia = $idMateria;
    $asignacion->id_grado = $idGrado;
    $asignacion->carga_horaria = $cargaHoraria;
    $asignacion->anio_lectivo = $anio;

    if (!$asignacion->relacionesActivas()) {
        http_response_code(422);
        echo json_encode([
            "success" => false,
            "status" => "error",
            "message" => "El profesor, la materia, el grado o el aula asociada no están activos."
        ]);
        exit;
    }

    if ($asignacion->existeDuplicada()) {
        http_response_code(409);
        echo json_encode([
            "success" => false,
            "status" => "error",
            "message" => "Ya existe una asignación activa para este profesor, materia, grado y año lectivo."
        ]);
        exit;
    }

    $resultado = $asignacion->crear();
    if (!$resultado) throw new RuntimeException('La operación de inserción no se completó.');

    $idAsignacion = (int) $db->lastInsertId();
    http_response_code(201);
    echo json_encode([
        "success" => true,
        "status" => "success",
        "message" => "Asignación creada correctamente.",
        "data" => ["id_asignacion" => $idAsignacion]
    ]);
} catch (Throwable $e) {
    error_log('crear_asignacion: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "status" => "error",
        "message" => "No se pudo crear la asignación. Revisá los datos e intentá nuevamente."
    ]);
}
?>
