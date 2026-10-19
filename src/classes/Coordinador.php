<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';

/**
 * Clase Coordinador
 * Gestiona el alcance operativo, métricas y exploración pedagógica para coordinadores.
 * Optimizado con consultas batch masivas para máxima velocidad y mínima latencia.
 */
class Coordinador {
    private PDO $conn;

    public function __construct(PDO $db) {
        $this->conn = $db;
    }

    /**
     * Retorna los grados que supervisa un usuario con métricas cuantitativas agrupadas en batch.
     */
    public function obtenerGradosCoordinados(int $id_usuario, ?string $rol = null): array {
        $esAdmin = in_array($rol, ['SuperAdmin', 'Administrador', 'Administracion'], true);

        if ($esAdmin) {
            $sql = "SELECT g.id_grado, g.nombre AS grado_nombre, g.seccion, g.turno, g.anho_lectivo,
                           a.id_aula, a.nombre AS aula_nombre, a.capacidad, a.codigo_sala, a.ubicacion
                    FROM grados g
                    LEFT JOIN aulas a ON g.id_aula = a.id_aula
                    WHERE g.activo = 1
                    ORDER BY g.nombre ASC";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
        } else {
            // Verificar si tiene asignaciones específicas en coordinador_grados
            $sqlCheck = "SELECT COUNT(*) FROM coordinador_grados WHERE id_usuario = :id_usuario AND activo = 1";
            $stmtCheck = $this->conn->prepare($sqlCheck);
            $stmtCheck->execute([':id_usuario' => $id_usuario]);
            $tieneEspecificos = (int)$stmtCheck->fetchColumn() > 0;

            if ($tieneEspecificos) {
                $sql = "SELECT g.id_grado, g.nombre AS grado_nombre, g.seccion, g.turno, g.anho_lectivo,
                               a.id_aula, a.nombre AS aula_nombre, a.capacidad, a.codigo_sala, a.ubicacion
                        FROM coordinador_grados cg
                        JOIN grados g ON cg.id_grado = g.id_grado
                        LEFT JOIN aulas a ON g.id_aula = a.id_aula
                        WHERE cg.id_usuario = :id_usuario AND cg.activo = 1 AND g.activo = 1
                        ORDER BY g.nombre ASC";
                $stmt = $this->conn->prepare($sql);
                $stmt->execute([':id_usuario' => $id_usuario]);
            } else {
                $sql = "SELECT g.id_grado, g.nombre AS grado_nombre, g.seccion, g.turno, g.anho_lectivo,
                               a.id_aula, a.nombre AS aula_nombre, a.capacidad, a.codigo_sala, a.ubicacion
                        FROM grados g
                        LEFT JOIN aulas a ON g.id_aula = a.id_aula
                        WHERE g.activo = 1
                        ORDER BY g.nombre ASC";
                $stmt = $this->conn->prepare($sql);
                $stmt->execute();
            }
        }

        $grados = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($grados)) {
            return [];
        }

        $idsGrados = array_map(fn($g) => (int)$g['id_grado'], $grados);
        $inClause = implode(',', array_fill(0, count($idsGrados), '?'));

        // Batch 1: Conteo de estudiantes por grado
        $sqlEstCount = "SELECT id_grado, COUNT(*) AS total FROM estudiantes WHERE id_grado IN ($inClause) AND activo = 1 GROUP BY id_grado";
        $stmtEstCount = $this->conn->prepare($sqlEstCount);
        $stmtEstCount->execute($idsGrados);
        $estCountMap = $stmtEstCount->fetchAll(PDO::FETCH_KEY_PAIR);

        // Batch 2: Conteo de materias por grado
        $sqlMatCount = "SELECT id_grado, COUNT(DISTINCT id_materia) AS total FROM asignacion_docente WHERE id_grado IN ($inClause) AND activo = 1 GROUP BY id_grado";
        $stmtMatCount = $this->conn->prepare($sqlMatCount);
        $stmtMatCount->execute($idsGrados);
        $matCountMap = $stmtMatCount->fetchAll(PDO::FETCH_KEY_PAIR);

        // Batch 3: Asistencia de hoy por grado
        $hoy = date('Y-m-d');
        $paramsHoy = array_merge($idsGrados, [$hoy]);
        $sqlHoyCount = "SELECT e.id_grado, ae.estado, COUNT(DISTINCT ae.id_estudiante) AS total
                        FROM asistencias_estudiantes ae
                        JOIN estudiantes e ON ae.id_estudiante = e.id_estudiante
                        WHERE e.id_grado IN ($inClause) AND ae.fecha = ? AND ae.activo = 1
                        GROUP BY e.id_grado, ae.estado";
        $stmtHoyCount = $this->conn->prepare($sqlHoyCount);
        $stmtHoyCount->execute($paramsHoy);
        $hoyRows = $stmtHoyCount->fetchAll(PDO::FETCH_ASSOC);

        $asistHoyMap = [];
        foreach ($hoyRows as $hr) {
            $idG = (int)$hr['id_grado'];
            $est = strtoupper((string)$hr['estado']);
            if (!isset($asistHoyMap[$idG])) {
                $asistHoyMap[$idG] = ['presentes' => 0, 'tardanzas' => 0, 'ausentes' => 0];
            }
            if ($est === 'PRESENTE') $asistHoyMap[$idG]['presentes'] += (int)$hr['total'];
            elseif ($est === 'TARDANZA') $asistHoyMap[$idG]['tardanzas'] += (int)$hr['total'];
            elseif ($est === 'AUSENTE' || $est === 'FALTA') $asistHoyMap[$idG]['ausentes'] += (int)$hr['total'];
        }

        // Enriquecer en memoria (0 consultas adicionales)
        foreach ($grados as &$grado) {
            $idG = (int)$grado['id_grado'];
            $grado['total_estudiantes'] = (int)($estCountMap[$idG] ?? 0);
            $grado['total_materias'] = (int)($matCountMap[$idG] ?? 0);
            $grado['metricas_hoy'] = $asistHoyMap[$idG] ?? ['presentes' => 0, 'tardanzas' => 0, 'ausentes' => 0];
        }

        return $grados;
    }

    /**
     * Métricas globales de asistencia del día para el coordinador
     */
    public function obtenerMetricasGenerales(int $id_usuario, ?string $rol = null, ?string $fecha = null): array {
        $fecha = $fecha ?? date('Y-m-d');
        $grados = $this->obtenerGradosCoordinados($id_usuario, $rol);

        $idsGrados = array_map(fn($g) => (int)$g['id_grado'], $grados);

        if (empty($idsGrados)) {
            return [
                'total_aulas' => 0,
                'total_estudiantes' => 0,
                'presentes' => 0,
                'ausentes' => 0,
                'tardanzas' => 0,
                'justificados' => 0,
                'porcentaje_asistencia' => 0.0,
                'fecha' => $fecha,
            ];
        }

        $totalEstudiantes = array_sum(array_column($grados, 'total_estudiantes'));

        $inClause = implode(',', array_fill(0, count($idsGrados), '?'));
        $params = array_merge($idsGrados, [$fecha]);
        $sqlAsist = "SELECT ae.estado, COUNT(DISTINCT ae.id_estudiante) AS total
                     FROM asistencias_estudiantes ae
                     JOIN estudiantes e ON ae.id_estudiante = e.id_estudiante
                     WHERE e.id_grado IN ($inClause)
                       AND ae.fecha = ?
                       AND ae.activo = 1
                     GROUP BY ae.estado";
        $stmtAsist = $this->conn->prepare($sqlAsist);
        $stmtAsist->execute($params);
        $estadosRaw = $stmtAsist->fetchAll(PDO::FETCH_KEY_PAIR);

        $presentes = (int)($estadosRaw['PRESENTE'] ?? $estadosRaw['presente'] ?? 0);
        $tardanzas = (int)($estadosRaw['TARDANZA'] ?? $estadosRaw['tardanza'] ?? 0);
        $justificados = (int)($estadosRaw['JUSTIFICADO'] ?? $estadosRaw['justificado'] ?? 0);
        $marcados = $presentes + $tardanzas + $justificados;
        $ausentes = max(0, $totalEstudiantes - $marcados);

        $porcentaje = ($totalEstudiantes > 0)
            ? round((($presentes + $tardanzas) / $totalEstudiantes) * 100, 1)
            : 0.0;

        return [
            'total_aulas' => count($grados),
            'total_estudiantes' => $totalEstudiantes,
            'presentes' => $presentes,
            'ausentes' => $ausentes,
            'tardanzas' => $tardanzas,
            'justificados' => $justificados,
            'porcentaje_asistencia' => $porcentaje,
            'fecha' => $fecha,
        ];
    }

    /**
     * Retorna la información detallada para el Explorador de Aula (3 Pestañas)
     * Ejecutado con consultas batch de alta velocidad.
     */
    public function obtenerDetalleAula(int $id_grado): ?array {
        // 1. Datos del grado y aula
        $sqlGrado = "SELECT g.id_grado, g.nombre AS grado_nombre, g.seccion, g.turno, g.anho_lectivo,
                            a.id_aula, a.nombre AS aula_nombre, a.capacidad, a.codigo_sala, a.ubicacion
                     FROM grados g
                     LEFT JOIN aulas a ON g.id_aula = a.id_aula
                     WHERE g.id_grado = :id_grado
                     LIMIT 1";
        $stmtGrado = $this->conn->prepare($sqlGrado);
        $stmtGrado->execute([':id_grado' => $id_grado]);
        $grado = $stmtGrado->fetch(PDO::FETCH_ASSOC);

        if (!$grado) {
            return null;
        }

        // 2. Materias asignadas
        $sqlMaterias = "SELECT ad.id_asignacion, ad.carga_horaria,
                               m.id_materia, m.nombre AS materia_nombre,
                               p.id_profesor, p.nombre AS prof_nombre, p.apellido AS prof_apellido, p.cedula_identidad AS prof_ci,
                               pa.id_plan, pa.estado AS plan_estado, pa.anio AS plan_anio
                        FROM asignacion_docente ad
                        JOIN materias m ON ad.id_materia = m.id_materia
                        LEFT JOIN profesores p ON ad.id_profesor = p.id_profesor
                        LEFT JOIN planes_anuales pa ON pa.id_asignacion = ad.id_asignacion
                        WHERE ad.id_grado = :id_grado AND ad.activo = 1
                        ORDER BY m.nombre ASC";
        $stmtMaterias = $this->conn->prepare($sqlMaterias);
        $stmtMaterias->execute([':id_grado' => $id_grado]);
        $materias = $stmtMaterias->fetchAll(PDO::FETCH_ASSOC);

        // Estudiantes del grado
        $estudiantesGrado = $this->obtenerEstudiantesGrado($id_grado);

        // Optimizaciones batch para materias
        $idsPlanes = array_values(array_filter(array_map(fn($m) => !empty($m['id_plan']) ? (int)$m['id_plan'] : null, $materias)));
        $idsAsignaciones = array_values(array_filter(array_map(fn($m) => (int)$m['id_asignacion'], $materias)));

        // Batch: Temas totales de los planes de esta aula
        $totalTemasMap = [];
        $temasDictadosMap = [];
        if (!empty($idsPlanes)) {
            $inPlanes = implode(',', array_fill(0, count($idsPlanes), '?'));
            
            $sqlTotalTemas = "SELECT pu.id_plan, COUNT(pt.id_tema) AS total_temas
                              FROM plan_temas pt
                              JOIN plan_capacidades pc ON pt.id_capacidad = pc.id_capacidad
                              JOIN plan_unidades pu ON pc.id_unidad = pu.id_unidad
                              WHERE pu.id_plan IN ($inPlanes)
                              GROUP BY pu.id_plan";
            $stmtTT = $this->conn->prepare($sqlTotalTemas);
            $stmtTT->execute($idsPlanes);
            $totalTemasMap = $stmtTT->fetchAll(PDO::FETCH_KEY_PAIR);

            $sqlDictados = "SELECT cd.id_plan, COUNT(DISTINCT cd.id_tema) AS temas_dictados
                            FROM clases_diarias cd
                            WHERE cd.id_plan IN ($inPlanes) AND cd.id_tema IS NOT NULL
                            GROUP BY cd.id_plan";
            $stmtTD = $this->conn->prepare($sqlDictados);
            $stmtTD->execute($idsPlanes);
            $temasDictadosMap = $stmtTD->fetchAll(PDO::FETCH_KEY_PAIR);
        }

        // Batch: Asistencias de alumnos en las asignaciones de esta aula
        $procesoMap = [];
        if (!empty($idsAsignaciones)) {
            $inAsig = implode(',', array_fill(0, count($idsAsignaciones), '?'));
            $sqlAsistAll = "SELECT h.id_asignacion, ae.id_estudiante, ae.estado, COUNT(*) AS cantidad
                            FROM asistencias_estudiantes ae
                            JOIN horarios h ON ae.id_horario = h.id_horario
                            WHERE h.id_asignacion IN ($inAsig) AND ae.activo = 1
                            GROUP BY h.id_asignacion, ae.id_estudiante, ae.estado";
            $stmtAsistAll = $this->conn->prepare($sqlAsistAll);
            $stmtAsistAll->execute($idsAsignaciones);
            $asistAllRows = $stmtAsistAll->fetchAll(PDO::FETCH_ASSOC);

            foreach ($asistAllRows as $ar) {
                $idAsig = (int)$ar['id_asignacion'];
                $idEst = (int)$ar['id_estudiante'];
                $est = strtoupper((string)$ar['estado']);
                if (!isset($procesoMap[$idAsig][$idEst])) {
                    $procesoMap[$idAsig][$idEst] = ['presentes' => 0, 'tardanzas' => 0, 'ausentes' => 0];
                }
                if ($est === 'PRESENTE') $procesoMap[$idAsig][$idEst]['presentes'] += (int)$ar['cantidad'];
                elseif ($est === 'TARDANZA') $procesoMap[$idAsig][$idEst]['tardanzas'] += (int)$ar['cantidad'];
                elseif ($est === 'AUSENTE' || $est === 'FALTA') $procesoMap[$idAsig][$idEst]['ausentes'] += (int)$ar['cantidad'];
            }
        }

        // Ensamblado en memoria
        foreach ($materias as &$materia) {
            $idPlan = !empty($materia['id_plan']) ? (int)$materia['id_plan'] : null;
            $idAsig = (int)$materia['id_asignacion'];

            $totalTemas = (int)($totalTemasMap[$idPlan] ?? 0);
            $temasDictados = (int)($temasDictadosMap[$idPlan] ?? 0);
            $porcentajeAvance = ($totalTemas > 0) ? round(($temasDictados / $totalTemas) * 100) : 0;

            $materia['total_temas'] = $totalTemas;
            $materia['temas_dictados'] = $temasDictados;
            $materia['porcentaje_avance'] = $porcentajeAvance;

            // Formar lista de proceso de alumnos
            $alumnosProceso = [];
            foreach ($estudiantesGrado as $eg) {
                $idEst = (int)$eg['id_estudiante'];
                $p = $procesoMap[$idAsig][$idEst]['presentes'] ?? 0;
                $t = $procesoMap[$idAsig][$idEst]['tardanzas'] ?? 0;
                $a = $procesoMap[$idAsig][$idEst]['ausentes'] ?? 0;
                $tot = $p + $t + $a;
                $tasa = ($tot > 0) ? round((($p + $t) / $tot) * 100) : 100;

                $alumnosProceso[] = [
                    'id_estudiante' => $idEst,
                    'nombre_completo' => $eg['apellido'] . ', ' . $eg['nombre'],
                    'cedula_identidad' => $eg['cedula_identidad'],
                    'presentes' => $p,
                    'tardanzas' => $t,
                    'ausentes' => $a,
                    'total_clases' => $tot,
                    'porcentaje' => $tasa,
                ];
            }
            $materia['alumnos_proceso'] = $alumnosProceso;
        }

        // 3. Estudiantes y 4. Tutores
        $estudiantes = $estudiantesGrado;
        $tutores = $this->obtenerTutoresPorGrado($id_grado);

        return [
            'grado' => $grado,
            'materias' => $materias,
            'estudiantes' => $estudiantes,
            'tutores' => $tutores,
        ];
    }

    /**
     * Estudiantes matriculados en un grado con información de sus tutores en batch
     */
    public function obtenerEstudiantesGrado(int $id_grado): array {
        $sql = "SELECT e.id_estudiante, e.nombre, e.apellido, e.cedula_identidad,
                       e.huella_registrada, e.huella_id, e.activo, e.id_usuario,
                       u.usuario AS usuario_login
                FROM estudiantes e
                LEFT JOIN usuarios u ON e.id_usuario = u.id_usuario
                WHERE e.id_grado = :id_grado AND e.activo = 1
                ORDER BY e.apellido ASC, e.nombre ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':id_grado' => $id_grado]);
        $estudiantes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($estudiantes)) {
            return [];
        }

        $idsEstudiantes = array_map(fn($e) => (int)$e['id_estudiante'], $estudiantes);
        $inClause = implode(',', array_fill(0, count($idsEstudiantes), '?'));

        // Batch query de tutores para todos los alumnos del curso
        $sqlTutores = "SELECT et.id_estudiante, t.id_tutor, t.nombre, t.apellido, t.cedula_identidad, t.celular, t.email,
                              et.parentesco, et.es_principal
                       FROM estudiante_tutores et
                       JOIN tutores t ON et.id_tutor = t.id_tutor
                       WHERE et.id_estudiante IN ($inClause) AND et.activo = 1";
        $stmtTut = $this->conn->prepare($sqlTutores);
        $stmtTut->execute($idsEstudiantes);
        $tutoresRows = $stmtTut->fetchAll(PDO::FETCH_ASSOC);

        $tutoresMap = [];
        foreach ($tutoresRows as $tr) {
            $tutoresMap[(int)$tr['id_estudiante']][] = $tr;
        }

        foreach ($estudiantes as &$est) {
            $est['tutores'] = $tutoresMap[(int)$est['id_estudiante']] ?? [];
        }

        return $estudiantes;
    }

    /**
     * Lista de tutores únicos con hijos en el grado
     */
    public function obtenerTutoresPorGrado(int $id_grado): array {
        $sql = "SELECT t.id_tutor, t.nombre, t.apellido, t.cedula_identidad, t.celular, t.email, t.direccion,
                       et.parentesco,
                       e.id_estudiante, e.nombre AS hijo_nombre, e.apellido AS hijo_apellido
                FROM estudiante_tutores et
                JOIN tutores t ON et.id_tutor = t.id_tutor
                JOIN estudiantes e ON et.id_estudiante = e.id_estudiante
                WHERE e.id_grado = :id_grado AND et.activo = 1 AND t.activo = 1
                ORDER BY t.apellido ASC, t.nombre ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':id_grado' => $id_grado]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $tutoresMap = [];
        foreach ($rows as $r) {
            $idTutor = (int)$r['id_tutor'];
            if (!isset($tutoresMap[$idTutor])) {
                $tutoresMap[$idTutor] = [
                    'id_tutor' => $idTutor,
                    'nombre' => $r['nombre'],
                    'apellido' => $r['apellido'],
                    'cedula_identidad' => $r['cedula_identidad'],
                    'celular' => $r['celular'],
                    'email' => $r['email'],
                    'direccion' => $r['direccion'],
                    'hijos' => [],
                ];
            }
            $tutoresMap[$idTutor]['hijos'][] = [
                'id_estudiante' => $r['id_estudiante'],
                'nombre_completo' => $r['hijo_nombre'] . ' ' . $r['hijo_apellido'],
                'parentesco' => $r['parentesco'],
            ];
        }

        return array_values($tutoresMap);
    }
}
