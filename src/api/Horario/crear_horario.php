<?php
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../classes/Horario.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Método no permitido"]);
    exit;
}

$id_asignacion = $_POST['id_asignacion'] ?? null;
$dia_semana = trim((string) ($_POST['dia_semana'] ?? ''));
$hora_inicio = trim((string) ($_POST['hora_inicio'] ?? ''));
$hora_fin = trim((string) ($_POST['hora_fin'] ?? ''));
$permite_superposicion = filter_var(
    $_POST['permite_superposicion'] ?? false,
    FILTER_VALIDATE_BOOLEAN
);

if (!$id_asignacion || $dia_semana === '' || $hora_inicio === '' || $hora_fin === '') {
    http_response_code(422);
    echo json_encode(["status" => "error", "message" => "Asignación, día y horas son obligatorios."]);
    exit;
}

$diasValidos = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
if (!in_array($dia_semana, $diasValidos, true)) {
    http_response_code(422);
    echo json_encode(["status" => "error", "message" => "El día seleccionado no es válido."]);
    exit;
}

$formatoHoraValido = static function ($hora) {
    return preg_match('/^([01]\d|2[0-3]):[0-5]\d(?::[0-5]\d)?$/', $hora) === 1;
};

if (!$formatoHoraValido($hora_inicio) || !$formatoHoraValido($hora_fin)) {
    http_response_code(422);
    echo json_encode(["status" => "error", "message" => "Las horas indicadas no son válidas."]);
    exit;
}

if (strtotime($hora_fin) <= strtotime($hora_inicio)) {
    http_response_code(422);
    echo json_encode(["status" => "error", "message" => "La hora fin debe ser mayor que la hora inicio."]);
    exit;
}

try {
    $db = (new Database())->getConnection();
    $horario = new Horario($db);

    $asignacion = $horario->obtenerAsignacionActiva($id_asignacion);
    if (!$asignacion) {
        http_response_code(422);
        echo json_encode(["status" => "error", "message" => "La asignación seleccionada ya no está activa o disponible."]);
        exit;
    }

    $id_grado = $asignacion['id_grado'];

    $horario->id_asignacion = $id_asignacion;
    $horario->id_grado = $id_grado;
    $horario->dia_semana = $dia_semana;
    $horario->hora_inicio = $hora_inicio;
    $horario->hora_fin = $hora_fin;
    $horario->id_aula = $asignacion['id_aula'];
    $horario->permite_superposicion = $permite_superposicion ? 1 : 0;

    $conflicto = $horario->obtenerConflicto(null, $permite_superposicion);
    if ($conflicto) {
        $etiquetas = [
            'grado' => 'el grado',
            'aula' => 'el aula',
            'profesor' => 'el profesor'
        ];
        http_response_code(409);
        $mensaje = "Existe un horario superpuesto para " . ($etiquetas[$conflicto['tipo']] ?? 'la selección') . ".";
        if (!empty($conflicto['excepcion_disponible'])) {
            $mensaje .= " Si ambos grados tendrán clase conjunta con el mismo profesor en esta aula, marcá la excepción correspondiente.";
        }
        echo json_encode([
            "success" => false,
            "status" => "error",
            "message" => $mensaje
        ]);
        exit;
    }

    $db->beginTransaction();
    $resultado = $horario->crear();
    if (!$resultado) throw new RuntimeException('La inserción del horario no se completó.');

    $id_horario = (int) $db->lastInsertId();
    $horario->id_horario = $id_horario;
    if (!$horario->marcarClasesConjuntasRelacionadas()) {
        throw new RuntimeException('No se pudieron vincular las clases conjuntas.');
    }
    $db->commit();

    echo json_encode([
        "success" => true,
        "status" => "success",
        "message" => $permite_superposicion ? "Horario conjunto creado correctamente" : "Horario creado correctamente",
        "data" => [
            "id_horario" => $id_horario,
            "id_grado" => (int) $id_grado,
            "id_aula" => (int) $asignacion['id_aula'],
            "permite_superposicion" => $permite_superposicion ? 1 : 0
        ],
        "id_horario" => $id_horario,
        "id_grado" => (int) $id_grado,
        "id_aula" => (int) $asignacion['id_aula']
    ]);
} catch (Throwable $e) {
    if (isset($db) && $db->inTransaction()) $db->rollBack();
    error_log('crear_horario: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(["success" => false, "status" => "error", "message" => "No se pudo crear el horario."]);
}
