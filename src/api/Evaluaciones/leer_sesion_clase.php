<?php
declare(strict_types=1);

header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../classes/ClaseDiaria.php';
require_once __DIR__ . '/../../classes/EvaluacionMec.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$idClase = isset($_GET['id_clase']) && is_numeric($_GET['id_clase']) ? (int)$_GET['id_clase'] : null;
$idProfesor = !empty($_SESSION['id_profesor']) ? (int)$_SESSION['id_profesor'] : null;
if (!$idProfesor && isset($_GET['id_profesor']) && is_numeric($_GET['id_profesor'])) {
    $idProfesor = (int)$_GET['id_profesor'];
}

$idGrado = isset($_GET['id_grado']) && is_numeric($_GET['id_grado']) ? (int)$_GET['id_grado'] : null;

try {
    $db = (new Database())->getConnection();
    $claseModel = new ClaseDiaria($db);
    $evalModel = new EvaluacionMec($db);

    $clase = null;

    if ($idClase) {
        $clase = $claseModel->obtenerDetalleClase($idClase, $idProfesor);
    } elseif ($idProfesor || $idGrado) {
        $timestamp = date('Y-m-d H:i:s');
        $clase = $claseModel->obtenerOCrearClaseActiva(null, $timestamp, $idProfesor, $idGrado);
    }

    if (!$clase) {
        http_response_code(200);
        echo json_encode([
            "status" => "success",
            "message" => "No hay sesión de clase activa en este momento para los criterios seleccionados",
            "data" => null
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $idClaseActiva = (int)$clase['id_clase'];

    // Componente 2: Registros anecdotarios de la clase
    $anecdotarios = $evalModel->obtenerRegistrosAnecdoticosClase($idClaseActiva);

    // Componente 3: Evaluaciones individuales y plantillas MEC
    $evaluaciones = $evalModel->obtenerEvaluacionesClase($idClaseActiva);
    $plantillas = $evalModel->obtenerPlantillas($idProfesor);

    // Mapear evaluaciones existentes por id_estudiante para acceso rápido O(1)
    $mapaEvaluaciones = [];
    foreach ($evaluaciones as $ev) {
        $mapaEvaluaciones[(int)$ev['id_estudiante']] = $ev;
    }

    // Unificar lista de estudiantes presentes/ausentes con su estado evaluativo
    $todosEstudiantes = array_merge($clase['estudiantes_presentes'] ?? [], $clase['estudiantes_ausentes'] ?? []);
    foreach ($todosEstudiantes as &$est) {
        $idEst = (int)$est['id_estudiante'];
        $est['evaluacion'] = $mapaEvaluaciones[$idEst] ?? null;
    }
    unset($est);

    http_response_code(200);
    echo json_encode([
        "status" => "success",
        "message" => "Sesión pedagógica docente obtenida correctamente",
        "data" => [
            "clase" => [
                "id_clase" => $clase['id_clase'],
                "fecha" => $clase['fecha'],
                "horario_inicio" => $clase['horario_inicio'] ?? $clase['hora_inicio'],
                "horario_fin" => $clase['horario_fin'] ?? $clase['hora_fin'],
                "materia" => $clase['materia_nombre'],
                "grado" => $clase['grado_nombre'],
                "aula" => $clase['aula_nombre'],
                "profesor" => "{$clase['profesor_nombre']} {$clase['profesor_apellido']}",
                "tema" => [
                    "id_tema" => $clase['id_tema'],
                    "titulo" => $clase['tema_titulo'],
                    "contenido" => $clase['tema_contenido'],
                    "capacidad" => $clase['capacidad_descripcion'],
                    "unidad" => $clase['unidad_nombre'],
                    "horas_catedra" => $clase['horas_catedra']
                ],
                "indicadores" => $clase['indicadores'] ?? [],
                "procedimientos_sugeridos" => $clase['procedimientos'] ?? [],
                "instrumentos_sugeridos" => $clase['instrumentos'] ?? []
            ],
            // Componente 1: Asistencia en vivo
            "asistencia" => [
                "profesor" => $clase['profesor_asistencia'],
                "metricas" => $clase['metricas'],
                "estudiantes_presentes" => $clase['estudiantes_presentes'] ?? [],
                "estudiantes_ausentes" => $clase['estudiantes_ausentes'] ?? [],
                "todos_estudiantes" => $todosEstudiantes
            ],
            // Componente 2: Registros anecdotarios
            "registro_anecdotario" => [
                "total" => count($anecdotarios),
                "registros" => $anecdotarios
            ],
            // Componente 3: Evaluación de la lección del día (MEC)
            "evaluacion_leccion" => [
                "total_evaluados" => count($evaluaciones),
                "evaluaciones" => $evaluaciones,
                "plantillas_disponibles" => $plantillas
            ]
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    error_log("Error en Evaluaciones/leer_sesion_clase: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Error interno al consultar sesión pedagógica de clase"
    ], JSON_UNESCAPED_UNICODE);
}
