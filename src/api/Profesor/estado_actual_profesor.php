<?php
declare(strict_types=1);

header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../classes/ClaseDiaria.php';

$idProfesor = isset($_GET['id_profesor']) && is_numeric($_GET['id_profesor']) ? (int)$_GET['id_profesor'] : null;

if (!$idProfesor || $idProfesor <= 0) {
    http_response_code(400);
    echo json_encode([
        "status" => "error",
        "message" => "Identificador de profesor no válido"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $db = (new Database())->getConnection();

    // 1. Obtener datos del profesor
    $stmtP = $db->prepare("SELECT id_profesor, nombre, apellido, cedula_identidad, user_id_global FROM profesores WHERE id_profesor = :id AND activo = 1 LIMIT 1");
    $stmtP->execute([':id' => $idProfesor]);
    $profesor = $stmtP->fetch(PDO::FETCH_ASSOC);

    if (!$profesor) {
        http_response_code(404);
        echo json_encode([
            "status" => "error",
            "message" => "Profesor no encontrado o inactivo"
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 2. Establecer hora institucional de Paraguay
    date_default_timezone_set('America/Asuncion');
    $ahora = new DateTime();
    $fechaActual = $ahora->format('Y-m-d');
    $horaActual = $ahora->format('H:i:s');
    $diaNumero = (int)$ahora->format('w'); // 0: Domingo, 1: Lunes...

    $diaInfo = ClaseDiaria::obtenerDiaEspanol($diaNumero);
    $diaConTilde = $diaInfo['con_tilde'];
    $diaSinTilde = $diaInfo['sin_tilde'];

    // 3. Consultar horarios del profesor para el día de hoy
    $sqlHorariosHoy = "
        SELECT 
            h.id_horario,
            h.id_asignacion,
            h.dia_semana,
            h.hora_inicio,
            h.hora_fin,
            h.id_aula,
            m.id_materia,
            m.nombre AS materia,
            '' AS materia_codigo,
            g.id_grado,
            g.nombre AS grado,
            g.seccion AS seccion,
            aul.nombre AS aula,
            aul.codigo AS aula_codigo,
            aul.ubicacion AS aula_ubicacion
        FROM horarios h
        INNER JOIN asignacion_docente ad ON ad.id_asignacion = h.id_asignacion AND ad.activo = 1
        INNER JOIN materias m ON m.id_materia = ad.id_materia
        LEFT JOIN grados g ON g.id_grado = COALESCE(h.id_grado, ad.id_grado)
        LEFT JOIN aulas aul ON aul.id_aula = COALESCE(h.id_aula, g.id_aula)
        WHERE ad.id_profesor = :id_profesor
          AND (h.dia_semana = :dia1 OR h.dia_semana = :dia2)
          AND h.activo = 1
        ORDER BY h.hora_inicio ASC
    ";

    $stmtHH = $db->prepare($sqlHorariosHoy);
    $stmtHH->execute([
        ':id_profesor' => $idProfesor,
        ':dia1' => $diaConTilde,
        ':dia2' => $diaSinTilde
    ]);
    $horariosHoy = $stmtHH->fetchAll(PDO::FETCH_ASSOC);

    // 4. Consultar asistencias del profesor registradas hoy
    $stmtAsist = $db->prepare("
        SELECT id_asistencia_profesor, id_horario, fecha, hora, hora_marcacion, estado, tipo_registro, nodo_origen
        FROM asistencias_profesores
        WHERE id_profesor = :id_profesor AND fecha = :fecha AND activo = 1
        ORDER BY COALESCE(hora_marcacion, hora) ASC
    ");
    $stmtAsist->execute([
        ':id_profesor' => $idProfesor,
        ':fecha' => $fechaActual
    ]);
    $asistenciasHoy = $stmtAsist->fetchAll(PDO::FETCH_ASSOC);

    $marcadoConHuella = false;
    $horaMarcacionHuella = null;
    $nodoMarcacion = null;
    $estadoAsistenciaGeneral = 'SIN_REGISTRO';

    foreach ($asistenciasHoy as $asist) {
        if ($asist['estado'] !== 'AUSENTE') {
            $marcadoConHuella = ($asist['tipo_registro'] === 'BIOMETRICO');
            $horaMarcacionHuella = $asist['hora_marcacion'] ?: $asist['hora'];
            $nodoMarcacion = $asist['nodo_origen'];
            $estadoAsistenciaGeneral = $asist['estado'];
            break;
        }
    }

    // 5. Determinar estado institucional en tiempo real
    $esDiaLaboral = !empty($horariosHoy);
    $claseEnCurso = null;
    $proximaClase = null;
    $minutosParaProxima = null;
    $jornadaFinalizada = false;

    if ($esDiaLaboral) {
        // Recorrer los horarios del día
        foreach ($horariosHoy as $h) {
            $hIni = $h['hora_inicio'];
            $hFin = $h['hora_fin'];

            if ($horaActual >= $hIni && $horaActual <= $hFin) {
                $claseEnCurso = $h;
                break;
            }

            if ($horaActual < $hIni && $proximaClase === null) {
                $proximaClase = $h;
                $horaInicioObj = DateTime::createFromFormat('Y-m-d H:i:s', "{$fechaActual} {$hIni}");
                if ($horaInicioObj) {
                    $diffSegundos = $horaInicioObj->getTimestamp() - $ahora->getTimestamp();
                    $minutosParaProxima = max(1, (int)round($diffSegundos / 60));
                }
            }
        }

        // Si no está en clase y no hay próxima clase hoy, ya terminó
        if (!$claseEnCurso && !$proximaClase) {
            $jornadaFinalizada = true;
        }
    }

    // 6. Mensaje e indicador visual sugerente
    $estadoBadge = [];
    if (!$esDiaLaboral) {
        $estadoBadge = [
            "tipo" => "NO_LABORAL",
            "titulo" => "No es su día laboral",
            "mensaje" => "El docente no tiene horas cátedra asignadas para hoy ({$diaConTilde}).",
            "clase_css" => "bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-300 border-slate-300 dark:border-slate-700",
            "icono" => "🗓️"
        ];
    } elseif ($claseEnCurso !== null) {
        $detalleAsist = $marcadoConHuella 
            ? "Presencia confirmada con huella dactilar a las {$horaMarcacionHuella}" 
            : "⚠️ Aún no ha registrado presencia con huella en el aula";

        $estadoBadge = [
            "tipo" => "EN_CLASE",
            "titulo" => "En clase activa ahora",
            "materia" => $claseEnCurso['materia'],
            "grado" => $claseEnCurso['grado'],
            "aula" => $claseEnCurso['aula'] ?: 'Sin aula asignada',
            "horario" => substr($claseEnCurso['hora_inicio'], 0, 5) . " - " . substr($claseEnCurso['hora_fin'], 0, 5),
            "asistencia_huella" => $marcadoConHuella,
            "hora_huella" => $horaMarcacionHuella,
            "mensaje" => "Dictando cátedra de {$claseEnCurso['materia']} en {$claseEnCurso['aula']}. {$detalleAsist}",
            "clase_css" => "bg-emerald-100 text-emerald-950 dark:bg-emerald-950/70 dark:text-emerald-300 border-emerald-400 dark:border-emerald-700 animate-pulse",
            "icono" => "🔴"
        ];
    } elseif ($proximaClase !== null) {
        $horaFmt = substr($proximaClase['hora_inicio'], 0, 5);
        $estadoBadge = [
            "tipo" => "PROXIMA_CLASE",
            "titulo" => "Día laboral en espera",
            "minutos_restantes" => $minutosParaProxima,
            "proxima_materia" => $proximaClase['materia'],
            "proximo_grado" => $proximaClase['grado'],
            "proxima_aula" => $proximaClase['aula'],
            "proxima_hora" => $horaFmt,
            "mensaje" => "Hoy es su día laboral. Faltan {$minutosParaProxima} minutos para su siguiente hora cátedra ({$horaFmt} hs - {$proximaClase['materia']} en {$proximaClase['aula']}).",
            "clase_css" => "bg-amber-100 text-amber-950 dark:bg-amber-950/70 dark:text-amber-300 border-amber-300 dark:border-amber-700",
            "icono" => "⏳"
        ];
    } else {
        $estadoBadge = [
            "tipo" => "JORNADA_FINALIZADA",
            "titulo" => "Jornada laboral finalizada",
            "mensaje" => "El docente ya completó todas sus horas cátedra programadas para el día de hoy.",
            "clase_css" => "bg-blue-100 text-blue-950 dark:bg-blue-950/70 dark:text-blue-300 border-blue-300 dark:border-blue-700",
            "icono" => "✓"
        ];
    }

    // 7. Mapear todas las asignaciones del docente con su estado en vivo
    $stmtAsignaciones = $db->prepare("
        SELECT 
            ad.id_asignacion,
            ad.carga_horaria,
            m.id_materia,
            m.nombre AS materia,
            '' AS materia_codigo,
            g.id_grado,
            g.nombre AS grado,
            g.seccion,
            g.turno,
            aul.id_aula,
            aul.nombre AS aula,
            aul.codigo AS aula_codigo,
            h.id_horario,
            h.dia_semana,
            h.hora_inicio,
            h.hora_fin
        FROM asignacion_docente ad
        INNER JOIN materias m ON m.id_materia = ad.id_materia
        LEFT JOIN grados g ON g.id_grado = ad.id_grado
        LEFT JOIN horarios h ON h.id_asignacion = ad.id_asignacion AND h.activo = 1
        LEFT JOIN aulas aul ON aul.id_aula = COALESCE(h.id_aula, g.id_aula)
        WHERE ad.id_profesor = :id_profesor AND ad.activo = 1
        ORDER BY g.nombre ASC, m.nombre ASC, h.hora_inicio ASC
    ");
    $stmtAsignaciones->execute([':id_profesor' => $idProfesor]);
    $filasAsignaciones = $stmtAsignaciones->fetchAll(PDO::FETCH_ASSOC);

    $asignacionesAgrupadas = [];
    foreach ($filasAsignaciones as $fila) {
        $idAsig = $fila['id_asignacion'];
        if (!isset($asignacionesAgrupadas[$idAsig])) {
            $asignacionesAgrupadas[$idAsig] = [
                "id_asignacion" => $idAsig,
                "grado" => $fila['grado'] ?: 'Sin curso',
                "seccion" => $fila['seccion'] ?: '',
                "turno" => $fila['turno'] ?: '',
                "materia" => $fila['materia'] ?: 'Sin materia',
                "materia_codigo" => $fila['materia_codigo'] ?: '',
                "carga_horaria" => $fila['carga_horaria'],
                "aula" => $fila['aula'] ?: 'Sin aula',
                "id_aula" => $fila['id_aula'] ?: null,
                "horarios" => []
            ];
        }

        if (!empty($fila['id_horario'])) {
            $esHoy = (strcasecmp($fila['dia_semana'] ?? '', $diaConTilde) === 0 || strcasecmp($fila['dia_semana'] ?? '', $diaSinTilde) === 0);
            $enCursoFila = $esHoy && ($horaActual >= $fila['hora_inicio'] && $horaActual <= $fila['hora_fin']);
            
            $asignacionesAgrupadas[$idAsig]["horarios"][] = [
                "id_horario" => $fila['id_horario'],
                "dia_semana" => $fila['dia_semana'],
                "hora_inicio" => substr($fila['hora_inicio'], 0, 5),
                "hora_fin" => substr($fila['hora_fin'], 0, 5),
                "es_hoy" => $esHoy,
                "en_curso" => $enCursoFila
            ];
        }
    }

    // Calcular estado en tiempo real para cada asignación individual
    $listaAsignacionesResultado = [];
    foreach ($asignacionesAgrupadas as $asig) {
        $horariosHoyAsig = array_filter($asig['horarios'], fn($h) => $h['es_hoy']);
        $estadoRealtime = "NO_HOY";
        $estadoTexto = "No se dicta hoy";
        $badgeClass = "bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400";

        if (!empty($horariosHoyAsig)) {
            $enCurso = false;
            $proximo = null;
            $finalizado = true;

            foreach ($horariosHoyAsig as $hH) {
                if ($hH['en_curso']) {
                    $enCurso = true;
                    $asistenciaTexto = $marcadoConHuella ? " | ✓ Huella Marcada" : " | ⚠️ Falta Huella";
                    $estadoRealtime = "EN_CURSO";
                    $estadoTexto = "🔴 En Curso Ahora ({$hH['hora_inicio']} - {$hH['hora_fin']})" . $asistenciaTexto;
                    $badgeClass = "bg-emerald-100 text-emerald-900 dark:bg-emerald-950/70 dark:text-emerald-300 font-bold border border-emerald-300 dark:border-emerald-800 animate-pulse";
                    break;
                }

                if ($horaActual < $hH['hora_inicio'] . ':00' && $proximo === null) {
                    $proximo = $hH;
                    $finalizado = false;
                } elseif ($horaActual < $hH['hora_inicio'] . ':00') {
                    $finalizado = false;
                }
            }

            if (!$enCurso && $proximo !== null) {
                $hIniObj = DateTime::createFromFormat('Y-m-d H:i', "{$fechaActual} {$proximo['hora_inicio']}");
                $minRestantes = $hIniObj ? max(1, (int)round(($hIniObj->getTimestamp() - $ahora->getTimestamp()) / 60)) : 0;
                $estadoRealtime = "PROXIMO";
                $estadoTexto = "⏳ Hoy a las {$proximo['hora_inicio']} (Faltan {$minRestantes} min)";
                $badgeClass = "bg-amber-100 text-amber-900 dark:bg-amber-950/70 dark:text-amber-300 font-bold border border-amber-300 dark:border-amber-800";
            } elseif (!$enCurso && $finalizado) {
                $estadoRealtime = "FINALIZADO";
                $estadoTexto = "✓ Cátedra finalizada por hoy";
                $badgeClass = "bg-blue-50 text-blue-800 dark:bg-blue-950/60 dark:text-blue-300 font-semibold border border-blue-200 dark:border-blue-800";
            }
        }

        $asig['estado_realtime'] = $estadoRealtime;
        $asig['estado_texto'] = $estadoTexto;
        $asig['badge_class'] = $badgeClass;
        $listaAsignacionesResultado[] = $asig;
    }

    http_response_code(200);
    echo json_encode([
        "status" => "success",
        "message" => "Estado en tiempo real del docente consultado correctamente",
        "data" => [
            "profesor" => [
                "id_profesor" => (int)$profesor['id_profesor'],
                "nombre_completo" => trim("{$profesor['nombre']} {$profesor['apellido']}"),
                "cedula" => $profesor['cedula_identidad'],
                "user_id_global" => $profesor['user_id_global']
            ],
            "servidor_paraguay" => [
                "fecha" => $fechaActual,
                "hora" => $horaActual,
                "dia_semana" => $diaConTilde,
                "timezone" => "America/Asuncion"
            ],
            "asistencia_hoy" => [
                "marcado" => ($estadoAsistenciaGeneral !== 'SIN_REGISTRO' && $estadoAsistenciaGeneral !== 'AUSENTE'),
                "con_huella" => $marcadoConHuella,
                "hora_marcacion" => $horaMarcacionHuella,
                "nodo_origen" => $nodoMarcacion,
                "estado" => $estadoAsistenciaGeneral
            ],
            "indicador_tiempo_real" => $estadoBadge,
            "asignaciones" => $listaAsignacionesResultado
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    error_log("Error en Profesor/estado_actual_profesor: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Error interno al consultar estado actual del profesor",
        "debug" => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
