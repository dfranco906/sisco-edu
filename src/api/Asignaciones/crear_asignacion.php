<?php
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../classes/Asignacion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["success" => false, "status" => "error", "message" => "Método no permitido."]);
    exit;
}

$idProfesor = filter_var($_POST['id_profesor'] ?? null, FILTER_VALIDATE_INT);
$idMateria = filter_var($_POST['id_materia'] ?? null, FILTER_VALIDATE_INT);
$gradosEntrada = $_POST['id_grados'] ?? ($_POST['id_grado'] ?? []);
$gradosEntrada = is_array($gradosEntrada) ? $gradosEntrada : [$gradosEntrada];
$idsGrados = array_values(array_unique(array_filter(array_map(
    static fn($id) => filter_var($id, FILTER_VALIDATE_INT),
    $gradosEntrada
))));
$cargaHoraria = filter_var($_POST['carga_horaria'] ?? null, FILTER_VALIDATE_INT);
$anio = $_POST['anio_lectivo']
    ?? $_POST['anio']
    ?? $_POST["a\u{00F1}o_lectivo"]
    ?? $_POST["a\u{00C3}\u{00B1}o_lectivo"]
    ?? null;
$anio = filter_var($anio, FILTER_VALIDATE_INT);

if (!$idProfesor || !$idMateria || !$idsGrados || !$cargaHoraria || $cargaHoraria < 1 || $cargaHoraria > 100 || !$anio || $anio < 2000 || $anio > 2100) {
    http_response_code(422);
    echo json_encode([
        "success" => false,
        "status" => "error",
        "message" => "Profesor, materia, al menos un grado, carga horaria y año lectivo válidos son obligatorios."
    ]);
    exit;
}

try {
    $db = (new Database())->getConnection();
    $asignacion = new Asignacion($db);
    $asignacion->id_profesor = $idProfesor;
    $asignacion->id_materia = $idMateria;
    $asignacion->carga_horaria = $cargaHoraria;
    $asignacion->anio_lectivo = $anio;

    $gradosNuevos = [];
    $gradosDuplicados = [];
    foreach ($idsGrados as $idGrado) {
        $asignacion->id_grado = $idGrado;
        if (!$asignacion->relacionesActivas()) {
            http_response_code(422);
            echo json_encode([
                "success" => false,
                "status" => "error",
                "message" => "El profesor, la materia, uno de los grados o su aula asociada no están activos."
            ]);
            exit;
        }

        if ($asignacion->existeDuplicada()) {
            $gradosDuplicados[] = $idGrado;
        } else {
            $gradosNuevos[] = $idGrado;
        }
    }

    if (!$gradosNuevos) {
        http_response_code(409);
        echo json_encode([
            "success" => false,
            "status" => "error",
            "message" => count($gradosDuplicados) === 1
                ? "Ya existe una asignación activa para este profesor, materia, grado y año lectivo."
                : "La asignación ya existe en todos los grados seleccionados."
        ]);
        exit;
    }

    $db->beginTransaction();
    $idsAsignaciones = [];
    foreach ($gradosNuevos as $idGrado) {
        $asignacion->id_grado = $idGrado;
        if (!$asignacion->crear()) {
            throw new RuntimeException('La operación de inserción no se completó.');
        }
        $idsAsignaciones[] = (int) $db->lastInsertId();
    }
    $db->commit();

    $cantidad = count($idsAsignaciones);
    $omitidas = count($gradosDuplicados);
    $mensaje = $cantidad === 1
        ? "Asignación creada correctamente."
        : "Asignación creada correctamente en {$cantidad} grados.";
    if ($omitidas) {
        $mensaje .= " {$omitidas} grado" . ($omitidas === 1 ? "" : "s") . " ya tenía" . ($omitidas === 1 ? "" : "n") . " esta asignación.";
    }
    http_response_code(201);
    echo json_encode([
        "success" => true,
        "status" => "success",
        "message" => $mensaje,
        "data" => [
            "id_asignacion" => $idsAsignaciones[0],
            "ids_asignaciones" => $idsAsignaciones,
            "grados_creados" => $gradosNuevos,
            "grados_omitidos" => $gradosDuplicados
        ]
    ]);
} catch (PDOException $e) {
    if (isset($db) && $db->inTransaction()) $db->rollBack();
    error_log('crear_asignacion: ' . $e->getMessage());
    if ((string) $e->getCode() === '23000') {
        http_response_code(409);
        echo json_encode([
            "success" => false,
            "status" => "error",
            "message" => "Ya existe una asignación activa equivalente en uno de los grados seleccionados."
        ]);
        exit;
    }
    http_response_code(500);
    echo json_encode(["success" => false, "status" => "error", "message" => "No se pudo crear la asignación."]);
} catch (Throwable $e) {
    if (isset($db) && $db->inTransaction()) $db->rollBack();
    error_log('crear_asignacion: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "status" => "error",
        "message" => "No se pudo crear la asignación. Revisá los datos e intentá nuevamente."
    ]);
}
?>
