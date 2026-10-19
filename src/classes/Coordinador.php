<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';

/**
 * Clase Coordinador
 * Gestiona el alcance operativo, métricas y exploración pedagógica para coordinadores.
 */
class Coordinador {
    private PDO $conn;

    public function __construct(PDO $db) {
        $this->conn = $db;
    }

    /**
     * Retorna los grados que supervisa un usuario.
     * Si el rol es SuperAdmin o Administrador, o si no tiene asignaciones específicas,
     * retorna todos los grados activos.
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
                // Fallback: si es coordinador pero aún no le asignaron específicos, lista todos los activos
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

        // Enriquecer cada grado con estadísticas rápidas
        foreach ($grados as &$grado) {
            $id_grado = (int)$grado['id_grado'];
            $grado['total_estudiantes'] = $this->contarEstudiantesGrado($id_grado);
            $grado['total_materias'] = $this->contarMateriasGrado($id_grado);
            $grado['metricas_hoy'] = $this->obtenerMetricasAsistenciaGradoHoy($id_grado);
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

        $inClause = implode(',', array_fill(0, count($idsGrados), '?'));

        // Total estudiantes matriculados en sus grados
        $sqlEst = "SELECT COUNT(*) FROM estudiantes WHERE id_grado IN ($inClause) AND activo = 1";
        $stmtEst = $this->conn->prepare($sqlEst);
        $stmtEst->execute($idsGrados);
        $totalEstudiantes = (int)$stmtEst->fetchColumn();

        // Conteo de asistencias por estado para la fecha
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
     * Retorna toda la información detallada para el Explorador de Aula (3 Pestañas)
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

        // 2. Pestaña 1: Materias asignadas
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

        // Estudiantes del grado para el cálculo de asistencia por materia
        $estudiantesGrado = $this->obtenerEstudiantesGrado($id_grado);

        foreach ($materias as &$materia) {
            $id_plan = !empty($materia['id_plan']) ? (int)$materia['id_plan'] : null;
            $id_asignacion = (int)$materia['id_asignacion'];

            // Conteo de temas totales del plan
            $totalTemas = 0;
            $temasDictados = 0;

            if ($id_plan !== null) {
                $sqlTemas = "SELECT COUNT(pt.id_tema) 
                             FROM plan_temas pt
                             JOIN plan_capacidades pc ON pt.id_capacidad = pc.id_capacidad
                             JOIN plan_unidades pu ON pc.id_unidad = pu.id_unidad
                             WHERE pu.id_plan = :id_plan";
                $stmtTemas = $this->conn->prepare($sqlTemas);
                $stmtTemas->execute([':id_plan' => $id_plan]);
                $totalTemas = (int)$stmtTemas->fetchColumn();

                // Temas dictados (registrados en clases_diarias)
                $sqlDictados = "SELECT COUNT(DISTINCT cd.id_tema)
                                FROM clases_diarias cd
                                WHERE cd.id_plan = :id_plan AND cd.id_tema IS NOT NULL";
                $stmtDictados = $this->conn->prepare($sqlDictados);
                $stmtDictados->execute([':id_plan' => $id_plan]);
                $temasDictados = (int)$stmtDictados->fetchColumn();
            }

            $porcentajeAvance = ($totalTemas > 0) ? round(($temasDictados / $totalTemas) * 100) : 0;
            $materia['total_temas'] = $totalTemas;
            $materia['temas_dictados'] = $temasDictados;
            $materia['porcentaje_avance'] = $porcentajeAvance;

            // Desglose de proceso/asistencia de alumnos en esta materia
            $materia['alumnos_proceso'] = $this->obtenerProcesoAlumnosMateria($id_asignacion, $estudiantesGrado);
        }

        // 3. Pestaña 2: Estudiantes
        $estudiantes = $estudiantesGrado;

        // 4. Pestaña 3: Tutores del grado
        $tutores = $this->obtenerTutoresPorGrado($id_grado);

        return [
            'grado' => $grado,
            'materias' => $materias,
            'estudiantes' => $estudiantes,
            'tutores' => $tutores,
        ];
    }

    /**
     * Estudiantes matriculados en un grado con información de sus tutores
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

        foreach ($estudiantes as &$est) {
            $id_est = (int)$est['id_estudiante'];
            $est['tutores'] = $this->obtenerTutoresDeEstudiante($id_est);
        }

        return $estudiantes;
    }

    /**
     * Tutores vinculados a un estudiante
     */
    public function obtenerTutoresDeEstudiante(int $id_estudiante): array {
        $sql = "SELECT t.id_tutor, t.nombre, t.apellido, t.cedula_identidad, t.celular, t.email,
                       et.parentesco, et.es_principal
                FROM estudiante_tutores et
                JOIN tutores t ON et.id_tutor = t.id_tutor
                WHERE et.id_estudiante = :id_estudiante AND et.activo = 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':id_estudiante' => $id_estudiante]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
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

        // Agrupar por tutor
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

    /**
     * Resumen de asistencia de alumnos para una materia/asignación específica
     */
    private function obtenerProcesoAlumnosMateria(int $id_asignacion, array $estudiantes): array {
        // Enlazar asistencias a través de los horarios de esta asignación
        $sql = "SELECT ae.id_estudiante, ae.estado, COUNT(*) AS cantidad
                FROM asistencias_estudiantes ae
                JOIN horarios h ON ae.id_horario = h.id_horario
                WHERE h.id_asignacion = :id_asignacion AND ae.activo = 1
                GROUP BY ae.id_estudiante, ae.estado";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':id_asignacion' => $id_asignacion]);
        $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $map = [];
        foreach ($registros as $reg) {
            $idEst = (int)$reg['id_estudiante'];
            $estado = strtoupper((string)$reg['estado']);
            if (!isset($map[$idEst])) {
                $map[$idEst] = ['presentes' => 0, 'ausentes' => 0, 'tardanzas' => 0];
            }
            if ($estado === 'PRESENTE') {
                $map[$idEst]['presentes'] += (int)$reg['cantidad'];
            } elseif ($estado === 'TARDANZA') {
                $map[$idEst]['tardanzas'] += (int)$reg['cantidad'];
            } elseif ($estado === 'AUSENTE' || $estado === 'FALTA') {
                $map[$idEst]['ausentes'] += (int)$reg['cantidad'];
            }
        }

        $resultado = [];
        foreach ($estudiantes as $est) {
            $idEst = (int)$est['id_estudiante'];
            $p = $map[$idEst]['presentes'] ?? 0;
            $a = $map[$idEst]['ausentes'] ?? 0;
            $t = $map[$idEst]['tardanzas'] ?? 0;
            $totalClases = $p + $a + $t;
            $tasa = ($totalClases > 0) ? round((($p + $t) / $totalClases) * 100) : 100;

            $resultado[] = [
                'id_estudiante' => $idEst,
                'nombre_completo' => $est['apellido'] . ', ' . $est['nombre'],
                'cedula_identidad' => $est['cedula_identidad'],
                'presentes' => $p,
                'ausentes' => $a,
                'tardanzas' => $t,
                'total_clases' => $totalClases,
                'porcentaje' => $tasa,
            ];
        }

        return $resultado;
    }

    private function contarEstudiantesGrado(int $id_grado): int {
        $stmt = $this->conn->prepare("SELECT COUNT(*) FROM estudiantes WHERE id_grado = :id_grado AND activo = 1");
        $stmt->execute([':id_grado' => $id_grado]);
        return (int)$stmt->fetchColumn();
    }

    private function contarMateriasGrado(int $id_grado): int {
        $stmt = $this->conn->prepare("SELECT COUNT(DISTINCT id_materia) FROM asignacion_docente WHERE id_grado = :id_grado AND activo = 1");
        $stmt->execute([':id_grado' => $id_grado]);
        return (int)$stmt->fetchColumn();
    }

    private function obtenerMetricasAsistenciaGradoHoy(int $id_grado): array {
        $hoy = date('Y-m-d');
        $sql = "SELECT ae.estado, COUNT(DISTINCT ae.id_estudiante) AS total
                FROM asistencias_estudiantes ae
                JOIN estudiantes e ON ae.id_estudiante = e.id_estudiante
                WHERE e.id_grado = :id_grado AND ae.fecha = :fecha AND ae.activo = 1
                GROUP BY ae.estado";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':id_grado' => $id_grado, ':fecha' => $hoy]);
        $raw = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

        return [
            'presentes' => (int)($raw['PRESENTE'] ?? $raw['presente'] ?? 0),
            'tardanzas' => (int)($raw['TARDANZA'] ?? $raw['tardanza'] ?? 0),
            'ausentes' => (int)($raw['AUSENTE'] ?? $raw['ausente'] ?? 0),
        ];
    }
}
