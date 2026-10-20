<?php
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../classes/Tutor.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Método no permitido"]);
    exit;
}

try {
    $db = (new Database())->getConnection();
    $tutor = new Tutor($db);

    $idNuevo = $tutor->crear($_POST);

    http_response_code(201);
    echo json_encode([
        "status" => "success",
        "message" => "Tutor registrado exitosamente. Se ha vinculado su usuario de acceso con su cédula de identidad.",
        "data" => ["id_tutor" => $idNuevo]
    ]);
} catch (InvalidArgumentException $ie) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => $ie->getMessage()]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Error interno al crear tutor: " . $e->getMessage()]);
}
