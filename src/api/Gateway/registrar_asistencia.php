<?php
header("Content-Type: application/json; charset=UTF-8");
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../classes/EventoAsistencia.php';
require_once __DIR__ . '/../../classes/ClaseDiaria.php';

// Validación de la clave secreta enviada por el Gateway
$headerKey = getallheaders()['X-GATEWAY-KEY'] ?? ($_SERVER['HTTP_X_GATEWAY_KEY'] ?? '');
if ($headerKey !== GATEWAY_API_KEY) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Clave de Gateway no autorizada"]);
    exit;
}

$id_aula = filter_input(INPUT_POST, 'id_aula', FILTER_VALIDATE_INT);
$ci = trim($_POST['ci'] ?? '');
$tipo = strtolower(trim($_POST['tipo_persona'] ?? ''));
$estado = strtoupper(trim($_POST['estado'] ?? 'PRESENTE'));
$timestamp = trim($_POST['timestamp'] ?? date('Y-m-d H:i:s'));

if (!$id_aula || !$ci || !in_array($tipo, ['estudiante', 'profesor'], true)) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Faltan datos requeridos (id_aula, ci, tipo_persona)"]);
    exit;
}

try {
    $db = (new Database())->getConnection();

    // 1. Obtener la persona por Cédula de Identidad
    $tabla = ($tipo === 'estudiante') ? 'estudiantes' : 'profesores';
    $idCol = ($tipo === 'estudiante') ? 'id_estudiante' : 'id_profesor';
    
    $stmt = $db->prepare("SELECT {$idCol}, user_id_global, nombre, apellido FROM {$tabla} WHERE cedula_identidad = :ci AND activo = 1 LIMIT 1");
    $stmt->execute([':ci' => $ci]);
    $persona = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$persona) {
        http_response_code(404);
        echo json_encode(["status" => "error", "message" => "Persona no encontrada con CI: {$ci}"]);
        exit;
    }

    $userId = $persona['user_id_global'];
    $nombreCompleto = trim($persona['nombre'] . ' ' . $persona['apellido']);
    $fechaActual = substr($timestamp, 0, 10);
    $horaActual = substr($timestamp, 11, 8);
    $nodoOrigen = 'NODO_AULA_' . $id_aula;

    // 2. Detección de clase activa y validación de coincidencia de aula (Anti-Fraude Cruzado)
    $claseModel = new ClaseDiaria($db);
    $claseActiva = $claseModel->obtenerOCrearClaseActiva($id_aula, $timestamp);
    $idHorario = $claseActiva ? (int)($claseActiva['id_horario'] ?? null) : null;
    $discrepanciaAula = false;
    $aulaEsperadaInfo = null;

    if ($tipo === 'profesor') {
        $id_profesor = (int)$persona['id_profesor'];
        $diaSemanaNum = (int)date('w', strtotime($timestamp));
        $diaInfo = ClaseDiaria::obtenerDiaEspanol($diaSemanaNum);

        // Consultar dónde debería estar dictando el profesor según su horario lectivo activo
        $stmtHorarioEsperado = $db->prepare("
            SELECT h.id_horario, h.id_aula, a.nombre AS aula_nombre, m.nombre AS materia_nombre, g.nombre AS grado_nombre, h.hora_inicio, h.hora_fin
            FROM horarios h
            INNER JOIN asignacion_docente ad ON ad.id_asignacion = h.id_asignacion AND ad.activo = 1
            LEFT JOIN aulas a ON a.id_aula = h.id_aula
            LEFT JOIN materias m ON m.id_materia = ad.id_materia
            LEFT JOIN grados g ON g.id_grado = COALESCE(h.id_grado, ad.id_grado)
            WHERE ad.id_profesor = :id_profesor
              AND (h.dia_semana = :dia1 OR h.dia_semana = :dia2)
              AND :hora BETWEEN (h.hora_inicio - INTERVAL '20 minutes') AND (h.hora_fin + INTERVAL '15 minutes')
              AND h.activo = 1
            ORDER BY h.hora_inicio ASC
            LIMIT 1
        ");
        $stmtHorarioEsperado->execute([
            ':id_profesor' => $id_profesor,
            ':dia1' => $diaInfo['con_tilde'],
            ':dia2' => $diaInfo['sin_tilde'],
            ':hora' => $horaActual
        ]);
        $horarioEsperado = $stmtHorarioEsperado->fetch(PDO::FETCH_ASSOC);

        if ($horarioEsperado) {
            $idAulaEsperada = (int)$horarioEsperado['id_aula'];
            if ($idAulaEsperada > 0 && $idAulaEsperada !== $id_aula) {
                // Alerta de seguridad: El docente marcó huella en un aula ajena a la que le correspondía impartir cátedra
                $discrepanciaAula = true;
                $estado = 'DISCREPANCIA_AULA';
                $nodoOrigen = 'NODO_AULA_' . $id_aula . '_DISCREPANCIA_ESPERADA_' . $idAulaEsperada;
                $aulaEsperadaInfo = [
                    'id_aula_esperada' => $idAulaEsperada,
                    'aula_esperada' => $horarioEsperado['aula_nombre'] ?: "Aula #{$idAulaEsperada}",
                    'materia' => $horarioEsperado['materia_nombre'],
                    'grado' => $horarioEsperado['grado_nombre'],
                    'horario' => substr($horarioEsperado['hora_inicio'], 0, 5) . ' - ' . substr($horarioEsperado['hora_fin'], 0, 5)
                ];
            } else {
                $idHorario = (int)$horarioEsperado['id_horario'];
            }
        }
    }

    // 3. Registro de asistencia con control estricto anti-duplicación por día
    if ($tipo === 'estudiante') {
        $id_estudiante = (int)$persona['id_estudiante'];
        $resAsistencia = $claseModel->registrarAsistenciaEstudiante(
            $id_estudiante,
            $fechaActual,
            $horaActual,
            $estado,
            'BIOMETRICO',
            $nodoOrigen,
            $idHorario,
            false // forzar_actualizacion = false (estricta prevención de doble escritura)
        );
    } else {
        $id_profesor = (int)$persona['id_profesor'];
        $resAsistencia = $claseModel->registrarAsistenciaProfesor(
            $id_profesor,
            $fechaActual,
            $horaActual,
            $estado,
            'BIOMETRICO',
            $nodoOrigen,
            $idHorario,
            false // forzar_actualizacion = false
        );

        if ($discrepanciaAula && $aulaEsperadaInfo) {
            $resAsistencia['mensaje'] = "⚠️ Alerta de Discrepancia: El docente marcó en Aula #{$id_aula}, pero tiene cátedra asignada en {$aulaEsperadaInfo['aula_esperada']} ({$aulaEsperadaInfo['materia']}). Registro auditado como DISCREPANCIA_AULA.";
        }
    }

    $esDuplicado = !empty($resAsistencia['duplicado']);

    // 4. Registrar evento crudo de auditoría biométrica únicamente si no es marcación duplicada inmediata
    if (!$esDuplicado) {
        $evento = new EventoAsistencia($db);
        $evento->user_id_global = $userId;
        $evento->id_aula = $id_aula;
        $evento->estado = $resAsistencia['estado'] ?? $estado;
        $evento->timestamp_evento = $timestamp;
        $evento->origen_node_id = 'GATEWAY_ESP32';
        $evento->sincronizado = 1;
        $evento->crear();
    }

    // Refrescar estado de la clase activa si existe
    $detalleClase = $claseActiva ? $claseModel->obtenerDetalleClase((int)$claseActiva['id_clase']) : null;

    http_response_code(200);
    echo json_encode([
        "status" => "success",
        "duplicado" => $esDuplicado,
        "discrepancia_aula" => $discrepanciaAula,
        "aula_esperada" => $aulaEsperadaInfo,
        "message" => $resAsistencia['mensaje'] ?? "Asistencia biométrica procesada correctamente",
        "data" => [
            "persona" => [
                "tipo" => $tipo,
                "nombre_completo" => $nombreCompleto,
                "cedula" => $ci,
                "user_id_global" => $userId,
                "hora_marcacion" => $resAsistencia['hora'] ?? $horaActual,
                "estado" => $resAsistencia['estado'] ?? $estado
            ],
            "resultado" => $resAsistencia,
            "clase_detectada" => $detalleClase ? [
                "id_clase" => $detalleClase['id_clase'],
                "materia" => $detalleClase['materia_nombre'],
                "profesor" => $detalleClase['profesor_nombre'] . ' ' . $detalleClase['profesor_apellido'],
                "grado" => $detalleClase['grado_nombre'],
                "aula" => $detalleClase['aula_nombre'],
                "horario" => ($detalleClase['horario_inicio'] ?? '') . ' - ' . ($detalleClase['horario_fin'] ?? ''),
                "horas_catedra" => $detalleClase['horas_catedra'] ?? 1,
                "duracion_minutos" => (float)($detalleClase['horas_catedra'] ?? 1) * 40,
                "leccion" => [
                    "unidad" => $detalleClase['unidad_nombre'],
                    "capacidad" => $detalleClase['capacidad_descripcion'],
                    "tema" => $detalleClase['tema_titulo'],
                    "contenido" => $detalleClase['tema_contenido'],
                    "indicadores" => $detalleClase['indicadores']
                ],
                "asistencia_en_vivo" => $detalleClase['metricas']
            ] : null
        ]
    ]);
} catch (Throwable $e) {
    error_log("Error en Gateway registrar_asistencia: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Error interno al registrar asistencia"]);
}