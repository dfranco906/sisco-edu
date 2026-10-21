<?php
declare(strict_types=1);

header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../classes/Profesor.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Método no permitido."]);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    $profesor = new Profesor($db);

    $profesor->nombre = $_POST['nombre'] ?? null;
    $profesor->apellido = $_POST['apellido'] ?? null;
    $profesor->cedula_identidad = $_POST['cedula_identidad'] ?? null;
    $profesor->email = $_POST['email'] ?? null;
    $profesor->celular = $_POST['celular'] ?? null;
    $profesor->user_id_global = $_POST['user_id_global'] ?? ('PROF_' . uniqid() . '_' . random_int(100, 999));

    if (!$profesor->nombre || !$profesor->apellido || !$profesor->cedula_identidad) {
        http_response_code(422);
        echo json_encode(["status" => "error", "message" => "Nombre, apellido y cédula de identidad son obligatorios."]);
        exit;
    }

    if ($profesor->crear()) {
        // Asignación inicial opcional de grado y materia
        $idGrado = filter_input(INPUT_POST, 'id_grado', FILTER_VALIDATE_INT) ?: null;
        $idMateria = filter_input(INPUT_POST, 'id_materia', FILTER_VALIDATE_INT) ?: null;

        if ($idGrado && $idMateria && $profesor->id_profesor) {
            $stmtAsig = $db->prepare("INSERT INTO asignacion_docente (id_profesor, id_materia, id_grado, carga_horaria, anio_lectivo, activo) VALUES (:prof, :mat, :grado, 2, EXTRACT(YEAR FROM CURRENT_DATE), 1)");
            $stmtAsig->execute([
                ':prof' => $profesor->id_profesor,
                ':mat' => $idMateria,
                ':grado' => $idGrado
            ]);
        }

        http_response_code(201);
        echo json_encode([
            "status" => "success",
            "message" => "Profesor creado con éxito.",
            "data" => [
                "id_profesor" => $profesor->id_profesor,
                "user_id_global" => $profesor->user_id_global
            ]
        ], JSON_UNESCAPED_UNICODE);
    } else {
        http_response_code(503);
        echo json_encode(["status" => "error", "message" => "Error interno al guardar en la base de datos."]);
    }
} catch (Throwable $e) {
    error_log("Error en crear_profesor: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Error interno del servidor: " . $e->getMessage()]);
}
