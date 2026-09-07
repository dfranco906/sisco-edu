<?php
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../classes/Horario.php';
require_once __DIR__ . '/clase_conjunta_auth.php';

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
$id_horario_vinculado = filter_var(
    $_POST['id_horario_vinculado'] ?? null,
    FILTER_VALIDATE_INT,
    ["options" => ["min_range" => 1]]
) ?: null;
$id_asignacion_conjunta = filter_var(
    $_POST['id_asignacion_conjunta'] ?? null,
    FILTER_VALIDATE_INT,
    ["options" => ["min_range" => 1]]
) ?: null;
$idsAsignacionesConjuntasEntrada = $_POST['id_asignaciones_conjuntas'] ?? $id_asignacion_conjunta;
$idsAsignacionesConjuntas = is_array($idsAsignacionesConjuntasEntrada)
    ? $idsAsignacionesConjuntasEntrada
    : preg_split('/\s*,\s*/', (string) $idsAsignacionesConjuntasEntrada, -1, PREG_SPLIT_NO_EMPTY);
$idsAsignacionesConjuntas = array_values(array_unique(array_filter(array_map('intval', $idsAsignacionesConjuntas))));
$materiasDistintas = filter_var($_POST['permite_materias_distintas'] ?? false, FILTER_VALIDATE_BOOLEAN);
$usuarioConjunta = $materiasDistintas ? autorizarMateriasDistintas() : null;

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
    if ($usuarioConjunta) asegurarAccesoAsignacion($db, $usuarioConjunta, (int)$id_asignacion, true);
    if (!$asignacion) {
        http_response_code(422);
        echo json_encode(["status" => "error", "message" => "La asignación seleccionada ya no está activa o disponible."]);
        exit;
    }

    $id_grado = $asignacion['id_grado'];
    $asignacionesConjuntas = [];
    $horariosVinculados = [];
    if ($permite_superposicion && $id_horario_vinculado) {
        $vinculadoAnterior = $horario->obtenerHorarioConjuntoActivo($id_horario_vinculado);
        if ($vinculadoAnterior) {
            $idsAsignacionesConjuntas[] = (int) $vinculadoAnterior['id_asignacion'];
            $dia_semana = $vinculadoAnterior['dia_semana'];
            $hora_inicio = $vinculadoAnterior['hora_inicio'];
            $hora_fin = $vinculadoAnterior['hora_fin'];
        }
    }

    if ($horario->interfiereRecesoTercerCiclo($asignacion, $hora_inicio, $hora_fin)) {
        http_response_code(422);
        echo json_encode([
            "status" => "error",
            "message" => "En Tercer Ciclo el recreo es de 09:40 a 10:10. Elegí un bloque antes o después del recreo."
        ]);
        exit;
    }

    $idsAsignacionesConjuntas = array_values(array_unique(array_filter(
        $idsAsignacionesConjuntas,
        fn($id) => (string) $id !== (string) $id_asignacion
    )));
    $gradosConjuntos = [(int)$id_grado => true];
    foreach ($idsAsignacionesConjuntas as $idAsignacionConjunta) {
        $asignacionConjunta = $horario->obtenerAsignacionActiva($idAsignacionConjunta);
        $vinculoValido = $asignacionConjunta
            && (string) $asignacionConjunta['id_grado'] !== (string) $id_grado
            && (string) $asignacionConjunta['id_profesor'] === (string) $asignacion['id_profesor']
            && ($materiasDistintas || (string) $asignacionConjunta['id_materia'] === (string) $asignacion['id_materia'])
            && (string) $asignacionConjunta['anio_lectivo'] === (string) $asignacion['anio_lectivo'];

        if (!$vinculoValido) {
            http_response_code(422);
            echo json_encode([
                "status" => "error",
                "message" => "Seleccione otro grado del mismo profesor y año. Para otra materia, active Incluir materias distintas."
            ]);
            exit;
        }
        if (isset($gradosConjuntos[(int)$asignacionConjunta['id_grado']])) {
            responderJson(['success'=>false,'status'=>'error','message'=>'Una clase conjunta sólo puede incluir una asignación por grado.'],422);
        }
        $gradosConjuntos[(int)$asignacionConjunta['id_grado']] = true;
        if ($usuarioConjunta) asegurarAccesoAsignacion($db, $usuarioConjunta, (int)$idAsignacionConjunta, true);
        if ($horario->interfiereRecesoTercerCiclo($asignacionConjunta, $hora_inicio, $hora_fin)) {
            http_response_code(422);
            echo json_encode([
                "status" => "error",
                "message" => "En Tercer Ciclo el recreo es de 09:40 a 10:10. Elegí un bloque antes o después del recreo."
            ]);
            exit;
        }
        $asignacionesConjuntas[$idAsignacionConjunta] = $asignacionConjunta;
        $existente = $horario->obtenerHorarioExactoAsignacion(
            $idAsignacionConjunta,
            $dia_semana,
            $hora_inicio,
            $hora_fin
        );
        if ($existente) {
            $horariosVinculados[$idAsignacionConjunta] = $existente;
        }
    }
    if ($permite_superposicion && !$asignacionesConjuntas) {
        http_response_code(422);
        echo json_encode([
            "status" => "error",
            "message" => "Seleccioná el curso correspondiente para crear la clase conjunta."
        ]);
        exit;
    }

    $horario->id_asignacion = $id_asignacion;
    if ($permite_superposicion) $horario->validarGruposSeleccionados($horariosVinculados, array_merge([(int)$id_asignacion], array_keys($asignacionesConjuntas)));
    $horario->id_grado = $id_grado;
    $horario->dia_semana = $dia_semana;
    $horario->hora_inicio = $hora_inicio;
    $horario->hora_fin = $hora_fin;
    $horario->id_aula = $asignacion['id_aula'];
    $horario->permite_superposicion = $permite_superposicion ? 1 : 0;

    $idsHorariosExistentes = array_values(array_map(
        fn($item) => (int) $item['id_horario'],
        $horariosVinculados
    ));
    $conflicto = $horario->obtenerConflicto(null, $permite_superposicion, $idsHorariosExistentes);
    if ($conflicto) {
        http_response_code(409);
        $mensaje = $horario->describirConflicto($conflicto);
        if (!empty($conflicto['excepcion_disponible'])) {
            $mensaje .= " Si los cursos tendrán clase conjunta con el mismo profesor en esta franja, vinculá el horario del curso correspondiente.";
        }
        echo json_encode([
            "success" => false,
            "status" => "error",
            "message" => $mensaje,
            "data" => ["conflicto" => $conflicto]
        ]);
        exit;
    }

    $db->beginTransaction();
    $resultado = $horario->crear();
    if (!$resultado) throw new RuntimeException('La inserción del horario no se completó.');

    $id_horario = (int) $db->lastInsertId();
    $horario->id_horario = $id_horario;

    $idsHorariosGrupo = [$id_horario];
    if ($permite_superposicion) {
        foreach ($asignacionesConjuntas as $idAsignacionConjunta => $asignacionConjunta) {
            if (isset($horariosVinculados[$idAsignacionConjunta])) {
                $idsHorariosGrupo[] = (int) $horariosVinculados[$idAsignacionConjunta]['id_horario'];
                continue;
            }
            $horarioConjunto = new Horario($db);
            $horarioConjunto->id_asignacion = $idAsignacionConjunta;
            $horarioConjunto->id_grado = $asignacionConjunta['id_grado'];
            $horarioConjunto->dia_semana = $dia_semana;
            $horarioConjunto->hora_inicio = $hora_inicio;
            $horarioConjunto->hora_fin = $hora_fin;
            $horarioConjunto->id_aula = $asignacionConjunta['id_aula'];
            $horarioConjunto->permite_superposicion = 1;

            // Al ampliar una clase conjunta existente, todos sus horarios exactos
            // ya fueron validados arriba, aunque todavía no se hayan recorrido.
            // Excluirlos desde el inicio evita que el profesor compartido produzca
            // un falso conflicto según el orden de los cursos seleccionados.
            $idsHorariosPermitidos = array_values(array_unique(array_merge(
                $idsHorariosGrupo,
                $idsHorariosExistentes
            )));
            if ($horarioConjunto->obtenerConflicto(null, true, $idsHorariosPermitidos)) {
                throw new DomainException('El curso correspondiente ya tiene otro horario en esa franja.');
            }
            if (!$horarioConjunto->crear()) {
                throw new RuntimeException('No se pudo crear el horario del curso correspondiente.');
            }
            $idsHorariosGrupo[] = (int) $db->lastInsertId();
        }

        if (!$horario->vincularGrupoHorarios($idsHorariosGrupo)) {
            throw new RuntimeException('No se pudieron vincular las clases conjuntas.');
        }
    }
    $db->commit();

    echo json_encode([
        "success" => true,
        "status" => "success",
        "message" => $permite_superposicion ? "Clase conjunta creada y sincronizada en todos los cursos" : "Horario creado correctamente",
        "data" => [
            "id_horario" => $id_horario,
            "id_grado" => (int) $id_grado,
            "id_aula" => (int) $asignacion['id_aula'],
            "id_horario_vinculado" => $idsHorariosGrupo[1] ?? null,
            "ids_horarios_vinculados" => array_slice($idsHorariosGrupo, 1),
            "permite_superposicion" => $permite_superposicion ? 1 : 0
        ],
        "id_horario" => $id_horario,
        "id_grado" => (int) $id_grado,
        "id_aula" => (int) $asignacion['id_aula']
    ]);
} catch (DomainException $e) {
    if (isset($db) && $db->inTransaction()) $db->rollBack();
    http_response_code(409);
    echo json_encode(["success" => false, "status" => "error", "message" => $e->getMessage()]);
} catch (Throwable $e) {
    if (isset($db) && $db->inTransaction()) $db->rollBack();
    error_log('crear_horario: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(["success" => false, "status" => "error", "message" => "No se pudo crear el horario."]);
}
