<?php
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../classes/Estudiante.php';

try {
    $db = (new Database())->getConnection();
    $estudiante = new Estudiante($db);

    $idGrado = !empty($_POST['id_grado']) ? (int)$_POST['id_grado'] : null;
    $resultado = $estudiante->sincronizarTodasLasMatriculas($idGrado);

    echo json_encode([
        "status" => "success",
        "message" => "Sincronización de matrículas completada: {$resultado['estudiantes_procesados']} estudiantes verificados, {$resultado['matriculas_sincronizadas']} asignaciones vinculadas.",
        "data" => $resultado
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Error al sincronizar matrículas: " . $e->getMessage()
    ]);
}
