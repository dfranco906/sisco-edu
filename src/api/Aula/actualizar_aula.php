<?php
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../classes/Aula.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["success" => false, "status" => "error", "message" => "Método no permitido."]);
    exit;
}

$idAula = filter_var($_POST['id_aula'] ?? null, FILTER_VALIDATE_INT);
$nombre = trim((string) ($_POST['nombre'] ?? ''));
$codigo = trim((string) ($_POST['codigo'] ?? ''));
$ubicacion = trim((string) ($_POST['ubicacion'] ?? ''));

if (!$idAula || $nombre === '' || $codigo === '') {
    http_response_code(422);
    echo json_encode(["success" => false, "status" => "error", "message" => "Aula, nombre y código son obligatorios."]);
    exit;
}

try {
    $db = (new Database())->getConnection();
    $aula = new Aula($db);
    $aula->id_aula = $idAula;
    $aula->nombre = $nombre;
    $aula->codigo = $codigo;
    $aula->ubicacion = $ubicacion !== '' ? $ubicacion : null;
    $resultado = $aula->actualizar();

    echo json_encode(["success" => (bool) $resultado, "status" => $resultado ? "success" : "error", "message" => $resultado ? "Aula actualizada correctamente." : "No se pudo actualizar el aula.", "data" => $resultado ? ["id_aula" => $idAula] : null]);
} catch (PDOException $e) {
    error_log('actualizar_aula: ' . $e->getMessage());
    $duplicado = $e->getCode() === '23000';
    http_response_code($duplicado ? 409 : 500);
    echo json_encode(["success" => false, "status" => "error", "message" => $duplicado ? "El código de aula ya está registrado." : "No se pudo actualizar el aula."]);
} catch (Throwable $e) {
    error_log('actualizar_aula: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(["success" => false, "status" => "error", "message" => "No se pudo actualizar el aula."]);
}
?>
