<?php
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../classes/Aula.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["success" => false, "status" => "error", "message" => "Método no permitido."]);
    exit;
}

$nombre = trim((string) ($_POST['nombre'] ?? ''));
$codigo = trim((string) ($_POST['codigo'] ?? ''));
$ubicacion = trim((string) ($_POST['ubicacion'] ?? ''));

if ($nombre === '' || $codigo === '') {
    http_response_code(422);
    echo json_encode(["success" => false, "status" => "error", "message" => "Nombre y código de aula son obligatorios."]);
    exit;
}

try {
    $db = (new Database())->getConnection();
    $aula = new Aula($db);
    $aula->nombre = $nombre;
    $aula->codigo = $codigo;
    $aula->ubicacion = $ubicacion !== '' ? $ubicacion : null;
    $resultado = $aula->crear();
    if (!$resultado) throw new RuntimeException('La inserción no se completó.');

    $idAula = (int) $db->lastInsertId();
    http_response_code(201);
    echo json_encode(["success" => true, "status" => "success", "message" => "Aula creada correctamente.", "data" => ["id_aula" => $idAula]]);
} catch (PDOException $e) {
    error_log('crear_aula: ' . $e->getMessage());
    $duplicado = $e->getCode() === '23000';
    http_response_code($duplicado ? 409 : 500);
    echo json_encode(["success" => false, "status" => "error", "message" => $duplicado ? "El código de aula ya está registrado." : "No se pudo crear el aula."]);
} catch (Throwable $e) {
    error_log('crear_aula: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(["success" => false, "status" => "error", "message" => "No se pudo crear el aula."]);
}
?>
