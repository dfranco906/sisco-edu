<?php
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../classes/Estudiante.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["success" => false, "status" => "error", "message" => "Método no permitido."]);
    exit;
}

$nombre = trim((string) ($_POST['nombre'] ?? ''));
$apellido = trim((string) ($_POST['apellido'] ?? ''));
$cedula = trim((string) ($_POST['cedula_identidad'] ?? ''));
$idGrado = filter_var($_POST['id_grado'] ?? null, FILTER_VALIDATE_INT);

if ($nombre === '' || $apellido === '' || $cedula === '' || !$idGrado) {
    http_response_code(422);
    echo json_encode(["success" => false, "status" => "error", "message" => "Nombre, apellido, cédula y grado son obligatorios."]);
    exit;
}

try {
    $db = (new Database())->getConnection();
    $estudiante = new Estudiante($db);
    if (!$estudiante->gradoActivoExiste($idGrado)) {
        http_response_code(422);
        echo json_encode(["success" => false, "status" => "error", "message" => "El grado seleccionado no tiene un aula activa disponible."]);
        exit;
    }

    $estudiante->nombre = $nombre;
    $estudiante->apellido = $apellido;
    $estudiante->cedula_identidad = $cedula;
    $estudiante->id_grado = $idGrado;
    $estudiante->user_id_global = trim((string) ($_POST['user_id_global'] ?? ''))
        ?: ('EST_' . uniqid() . '_' . random_int(100, 999));

    $resultado = $estudiante->crear();
    if (!$resultado) throw new RuntimeException('La operación de inserción no se completó.');

    $idEstudiante = (int) $db->lastInsertId();
    http_response_code(201);
    echo json_encode([
        "success" => true,
        "status" => "success",
        "message" => "Estudiante creado correctamente.",
        "data" => ["id_estudiante" => $idEstudiante]
    ]);
} catch (PDOException $e) {
    error_log('crear_estudiante: ' . $e->getMessage());
    $duplicado = $e->getCode() === '23000';
    http_response_code($duplicado ? 409 : 500);
    echo json_encode([
        "success" => false,
        "status" => "error",
        "message" => $duplicado ? "La cédula ya está registrada para otro estudiante." : "No se pudo crear el estudiante."
    ]);
} catch (Throwable $e) {
    error_log('crear_estudiante: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(["success" => false, "status" => "error", "message" => "No se pudo crear el estudiante."]);
}
?>
