<?php
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../classes/Aula.php';

try {
    $db = (new Database())->getConnection();
    $aula = new Aula($db);

    $aula->nombre = !empty($_POST['nombre']) ? trim($_POST['nombre']) : null;
    $aula->bloque = !empty($_POST['bloque']) ? trim($_POST['bloque']) : 'Bloque A';
    $aula->piso = !empty($_POST['piso']) ? trim($_POST['piso']) : 'Planta Baja';

    $codigo = !empty($_POST['codigo']) ? trim($_POST['codigo']) : null;
    if (!$codigo) {
        $codigo = $aula->generarSiguienteCodigo($aula->bloque, $aula->piso);
    }
    $aula->codigo = strtoupper($codigo);

    $aula->ubicacion = !empty($_POST['ubicacion']) ? trim($_POST['ubicacion']) : "{$aula->bloque} - {$aula->piso}";

    if (!$aula->nombre) {
        http_response_code(400);
        echo json_encode([
            "status" => "error",
            "message" => "El nombre del aula o espacio es obligatorio."
        ]);
        exit;
    }

    $resultado = $aula->crear();

    http_response_code($resultado ? 201 : 400);
    echo json_encode([
        "status" => $resultado ? "success" : "error",
        "message" => $resultado ? "Aula creada correctamente con código {$aula->codigo}" : "Error al crear aula",
        "data" => [
            "codigo" => $aula->codigo,
            "bloque" => $aula->bloque,
            "piso" => $aula->piso
        ]
    ]);

} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode([
        "status" => "error",
        "message" => "Error al registrar aula: " . $e->getMessage()
    ]);
}
