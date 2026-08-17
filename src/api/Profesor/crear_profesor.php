<?php
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../classes/Profesor.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["success" => false, "status" => "error", "message" => "Método no permitido."]);
    exit;
}

$nombre = trim((string) ($_POST['nombre'] ?? ''));
$apellido = trim((string) ($_POST['apellido'] ?? ''));
$cedula = trim((string) ($_POST['cedula_identidad'] ?? ''));

if ($nombre === '' || $apellido === '' || $cedula === '') {
    http_response_code(422);
    echo json_encode(["success" => false, "status" => "error", "message" => "Nombre, apellido y cédula son obligatorios."]);
    exit;
}

try {
    $db = (new Database())->getConnection();
    $profesor = new Profesor($db);
    $profesor->nombre = $nombre;
    $profesor->apellido = $apellido;
    $profesor->cedula_identidad = $cedula;
    $profesor->user_id_global = trim((string) ($_POST['user_id_global'] ?? ''))
        ?: ('PROF_' . uniqid() . '_' . random_int(100, 999));

    $resultado = $profesor->crear();
    if (!$resultado) throw new RuntimeException('La operación de inserción no se completó.');

    $idProfesor = (int) $db->lastInsertId();
    http_response_code(201);
    echo json_encode([
        "success" => true,
        "status" => "success",
        "message" => "Profesor creado correctamente.",
        "data" => ["id_profesor" => $idProfesor]
    ]);
} catch (PDOException $e) {
    error_log('crear_profesor: ' . $e->getMessage());
    $duplicado = $e->getCode() === '23000';
    http_response_code($duplicado ? 409 : 500);
    echo json_encode([
        "success" => false,
        "status" => "error",
        "message" => $duplicado ? "La cédula ya está registrada para otro profesor." : "No se pudo crear el profesor."
    ]);
} catch (Throwable $e) {
    error_log('crear_profesor: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(["success" => false, "status" => "error", "message" => "No se pudo crear el profesor."]);
}
?>
