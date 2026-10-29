<?php
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../classes/Estudiante.php';

try {
    $db = (new Database())->getConnection();
    $estudiante = new Estudiante($db);

    $estudiante->nombre = trim((string)($_POST['nombre'] ?? ''));
    $estudiante->apellido = trim((string)($_POST['apellido'] ?? ''));
    $cedulaRaw = trim((string)($_POST['cedula_identidad'] ?? ''));
    $estudiante->cedula_identidad = preg_replace('/[^0-9a-zA-Z]/', '', $cedulaRaw);
    $estudiante->id_grado = !empty($_POST['id_grado']) ? (int)$_POST['id_grado'] : null;
    $estudiante->user_id_global = $_POST['user_id_global'] ?? ('EST_' . uniqid() . '_' . random_int(100, 999));

    if (!$estudiante->nombre || !$estudiante->apellido || !$estudiante->cedula_identidad || !$estudiante->id_grado) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Datos incompletos. Todos los campos incluyendo el grado son obligatorios."]);
        exit;
    }

    $resultado = $estudiante->crear();
    $exito = is_array($resultado) ? ($resultado['ok'] ?? false) : (bool)$resultado;
    $matriculadas = is_array($resultado) ? ($resultado['materias_matriculadas'] ?? 0) : 0;

    http_response_code($exito ? 201 : 400);
    echo json_encode([
        "status" => $exito ? "success" : "error",
        "message" => $exito
            ? "Estudiante registrado correctamente y matriculado en {$matriculadas} materias del curso."
            : "Error al crear el estudiante",
        "materias_matriculadas" => $matriculadas
    ]);

} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode([
        "status" => "error",
        "message" => "Error interno al crear estudiante: " . $e->getMessage()
    ]);
}
