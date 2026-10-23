<?php
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../classes/Aula.php';

try {
    $bloque = $_GET['bloque'] ?? $_POST['bloque'] ?? '';
    $piso = $_GET['piso'] ?? $_POST['piso'] ?? '';
    $excluirId = !empty($_GET['id_aula']) ? (int)$_GET['id_aula'] : (!empty($_POST['id_aula']) ? (int)$_POST['id_aula'] : null);

    if (empty($bloque)) {
        $bloque = 'Bloque A';
    }
    if (empty($piso)) {
        $piso = 'Planta Baja';
    }

    $db = (new Database())->getConnection();
    $aula = new Aula($db);

    $codigo = $aula->generarSiguienteCodigo($bloque, $piso, $excluirId);

    echo json_encode([
        "status" => "success",
        "data" => [
            "codigo" => $codigo,
            "bloque" => $bloque,
            "piso" => $piso
        ]
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Error al generar código de aula: " . $e->getMessage()
    ]);
}
