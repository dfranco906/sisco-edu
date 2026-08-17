<?php
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../classes/Estudiante.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["success" => false, "status" => "error", "message" => "Método no permitido."]);
    exit;
}

$idEstudiante = filter_var($_POST['id_estudiante'] ?? null, FILTER_VALIDATE_INT);
$nombre = trim((string) ($_POST['nombre'] ?? ''));
$apellido = trim((string) ($_POST['apellido'] ?? ''));
$cedula = trim((string) ($_POST['cedula_identidad'] ?? ''));
$idGrado = filter_var($_POST['id_grado'] ?? null, FILTER_VALIDATE_INT);

if (!$idEstudiante || $nombre === '' || $apellido === '' || $cedula === '' || !$idGrado) {
    http_response_code(422);
    echo json_encode(["success" => false, "status" => "error", "message" => "Estudiante, nombre, apellido, cédula y grado son obligatorios."]);
    exit;
}

try {
    $db = (new Database())->getConnection();
    $stmtActual = $db->prepare("SELECT user_id_global FROM estudiantes WHERE id_estudiante = :id LIMIT 1");
    $stmtActual->execute([":id" => $idEstudiante]);
    $userIdActual = $stmtActual->fetchColumn();

    if ($userIdActual === false) {
        http_response_code(404);
        echo json_encode(["success" => false, "status" => "error", "message" => "El estudiante no existe."]);
        exit;
    }

    $estudiante = new Estudiante($db);
    if (!$estudiante->gradoActivoExiste($idGrado)) {
        http_response_code(422);
        echo json_encode(["success" => false, "status" => "error", "message" => "El grado seleccionado no tiene un aula activa disponible."]);
        exit;
    }

    $estudiante->id_estudiante = $idEstudiante;
    $estudiante->nombre = $nombre;
    $estudiante->apellido = $apellido;
    $estudiante->cedula_identidad = $cedula;
    $estudiante->id_grado = $idGrado;
    $estudiante->user_id_global = trim((string) ($_POST['user_id_global'] ?? ''))
        ?: ($userIdActual ?: ('EST_' . uniqid() . '_' . random_int(100, 999)));

    $resultado = $estudiante->actualizar();
    echo json_encode([
        "success" => (bool) $resultado,
        "status" => $resultado ? "success" : "error",
        "message" => $resultado ? "Estudiante actualizado correctamente." : "No se pudo actualizar el estudiante.",
        "data" => $resultado ? ["id_estudiante" => $idEstudiante] : null
    ]);
} catch (PDOException $e) {
    error_log('actualizar_estudiante: ' . $e->getMessage());
    $duplicado = $e->getCode() === '23000';
    http_response_code($duplicado ? 409 : 500);
    echo json_encode([
        "success" => false,
        "status" => "error",
        "message" => $duplicado ? "La cédula ya está registrada para otro estudiante." : "No se pudo actualizar el estudiante."
    ]);
} catch (Throwable $e) {
    error_log('actualizar_estudiante: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(["success" => false, "status" => "error", "message" => "No se pudo actualizar el estudiante."]);
}
?>
