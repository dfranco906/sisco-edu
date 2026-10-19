<?php
declare(strict_types=1);

header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../middleware/auth.php';

verificarRol(['SuperAdmin', 'Administrador']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Método no permitido"]);
    exit;
}

try {
    $db = (new Database())->getConnection();

    $idUsuario = (int)($_POST['id_usuario'] ?? 0);
    $idGrado = (int)($_POST['id_grado'] ?? 0);
    $accion = $_POST['accion'] ?? 'asignar'; // 'asignar' o 'desasignar'

    if ($idUsuario <= 0 || $idGrado <= 0) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Parámetros inválidos (id_usuario e id_grado requeridos)"]);
        exit;
    }

    if ($accion === 'desasignar') {
        $stmt = $db->prepare("DELETE FROM coordinador_grados WHERE id_usuario = :id_usuario AND id_grado = :id_grado");
        $stmt->execute([':id_usuario' => $idUsuario, ':id_grado' => $idGrado]);
        echo json_encode(["status" => "success", "message" => "Asignación eliminada correctamente"]);
        exit;
    }

    // Insertar o activar
    $stmt = $db->prepare("INSERT INTO coordinador_grados (id_usuario, id_grado, activo)
                          VALUES (:id_usuario, :id_grado, 1)
                          ON CONFLICT (id_usuario, id_grado) DO UPDATE SET activo = 1");
    $stmt->execute([':id_usuario' => $idUsuario, ':id_grado' => $idGrado]);

    echo json_encode(["status" => "success", "message" => "Grado asignado exitosamente al coordinador"]);
} catch (Throwable $e) {
    error_log("[Coordinacion] Error en asignar_grado: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Error interno al procesar la asignación"]);
}
