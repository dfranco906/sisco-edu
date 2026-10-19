<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';

/**
 * Clase Tutor
 * Gestiona el acceso de solo lectura para padres, madres y tutores,
 * listando sus hijos a cargo y generando el expediente integral de progreso.
 */
class Tutor {
    private PDO $conn;

    public function __construct(PDO $db) {
        $this->conn = $db;
    }

    /**
     * Retorna el registro del tutor asociado al id_usuario de sesión
     */
    public function obtenerPerfilPorUsuario(int $id_usuario): ?array {
        $sql = "SELECT id_tutor, nombre, apellido, cedula_identidad, celular, email, direccion
                FROM tutores
                WHERE id_usuario = :id_usuario AND activo = 1
                LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':id_usuario' => $id_usuario]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Retorna la lista de hijos vinculados a este tutor
     */
    public function obtenerHijos(int $id_tutor): array {
        $sql = "SELECT e.id_estudiante, e.nombre, e.apellido, e.cedula_identidad, e.id_grado,
                       g.nombre AS grado_nombre, g.seccion, g.turno,
                       a.nombre AS aula_nombre, a.codigo_sala,
                       et.parentesco, et.es_principal
                FROM estudiante_tutores et
                JOIN estudiantes e ON et.id_estudiante = e.id_estudiante
                LEFT JOIN grados g ON e.id_grado = g.id_grado
                LEFT JOIN aulas a ON g.id_aula = a.id_aula
                WHERE et.id_tutor = :id_tutor AND et.activo = 1 AND e.activo = 1
                ORDER BY e.apellido ASC, e.nombre ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':id_tutor' => $id_tutor]);
        $hijos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $hoy = date('Y-m-d');
        foreach ($hijos as &$hijo) {
            $idEst = (int)$hijo['id_estudiante'];
            $idGrado = (int)($hijo['id_grado'] ?? 0);

            // Asistencia de hoy
            $sqlHoy = "SELECT ae.estado, ae.hora_marcacion
                       FROM asistencias_estudiantes ae
                       WHERE ae.id_estudiante = :id_estudiante AND ae.fecha = :fecha AND ae.activo = 1
                       ORDER BY ae.hora_marcacion DESC LIMIT 1";
            $stmtHoy = $this->conn->prepare($sqlHoy);
            $stmtHoy->execute([':id_estudiante' => $idEst, ':fecha' => $hoy]);
            $asistHoy = $stmtHoy->fetch(PDO::FETCH_ASSOC);
            $hijo['asistencia_hoy'] = $asistHoy ? $asistHoy['estado'] : 'Sin registro';
            $hijo['hora_hoy'] = $asistHoy ? substr((string)$asistHoy['hora_marcacion'], 0, 5) : null;

            // Métricas generales de asistencia acumuladas
            $sqlMetricas = "SELECT ae.estado, COUNT(*) AS cantidad
                            FROM asistencias_estudiantes ae
                            WHERE ae.id_estudiante = :id_estudiante AND ae.activo = 1
                            GROUP BY ae.estado";
            $stmtMetricas = $this->conn->prepare($sqlMetricas);
            $stmtMetricas->execute([':id_estudiante' => $idEst]);
            $raw = $stmtMetricas->fetchAll(PDO::FETCH_KEY_PAIR);

            $p = (int)($raw['PRESENTE'] ?? $raw['presente'] ?? 0);
            $t = (int)($raw['TARDANZA'] ?? $raw['tardanza'] ?? 0);
            $a = (int)($raw['AUSENTE'] ?? $raw['ausente'] ?? $raw['FALTA'] ?? 0);
            $total = $p + $t + $a;
            $hijo['tasa_asistencia'] = ($total > 0) ? round((($p + $t) / $total) * 100) : 100;
            $hijo['total_asistencias'] = $p;
            $hijo['total_tardanzas'] = $t;
            $hijo['total_ausencias'] = $a;

            // Cantidad de materias
            $sqlMat = "SELECT COUNT(DISTINCT id_materia) FROM asignacion_docente WHERE id_grado = :id_grado AND activo = 1";
            $stmtMat = $this->conn->prepare($sqlMat);
            $stmtMat->execute([':id_grado' => $idGrado]);
            $hijo['total_materias'] = (int)$stmtMat->fetchColumn();
        }

        return $hijos;
    }

    /**
     * Expediente integral del estudiante (Currículum / Dossier de avance)
     */
    public function obtenerExpedienteEstudiante(int $id_estudiante): ?array {
        // Datos del estudiante
        $sqlEst = "SELECT e.id_estudiante, e.nombre, e.apellido, e.cedula_identidad, e.id_grado,
                          g.nombre AS grado_nombre, g.seccion, g.turno, g.anho_lectivo,
                          a.nombre AS aula_nombre, a.codigo_sala, a.ubicacion
                   FROM estudiantes e
                   LEFT JOIN grados g ON e.id_grado = g.id_grado
                   LEFT JOIN aulas a ON g.id_aula = a.id_aula
                   WHERE e.id_estudiante = :id_estudiante AND e.activo = 1
                   LIMIT 1";
        $stmtEst = $this->conn->prepare($sqlEst);
        $stmtEst->execute([':id_estudiante' => $id_estudiante]);
        $estudiante = $stmtEst->fetch(PDO::FETCH_ASSOC);

        if (!$estudiante) {
            return null;
        }

        $idGrado = (int)($estudiante['id_grado'] ?? 0);

        // Desglose por materia
        $sqlMaterias = "SELECT ad.id_asignacion, ad.carga_horaria,
                               m.id_materia, m.nombre AS materia_nombre,
                               p.id_profesor, p.nombre AS prof_nombre, p.apellido AS prof_apellido, up.email AS prof_email,
                               pa.id_plan, pa.estado AS plan_estado
                        FROM asignacion_docente ad
                        JOIN materias m ON ad.id_materia = m.id_materia
                        LEFT JOIN profesores p ON ad.id_profesor = p.id_profesor
                        LEFT JOIN usuarios up ON p.id_usuario = up.id_usuario
                        LEFT JOIN planes_anuales pa ON pa.id_asignacion = ad.id_asignacion
                        WHERE ad.id_grado = :id_grado AND ad.activo = 1
                        ORDER BY m.nombre ASC";
        $stmtMaterias = $this->conn->prepare($sqlMaterias);
        $stmtMaterias->execute([':id_grado' => $idGrado]);
        $materias = $stmtMaterias->fetchAll(PDO::FETCH_ASSOC);

        foreach ($materias as &$mat) {
            $idPlan = !empty($mat['id_plan']) ? (int)$mat['id_plan'] : null;
            $idAsig = (int)$mat['id_asignacion'];

            // Progreso del plan anual
            $totalTemas = 0;
            $temasDictados = 0;
            if ($idPlan !== null) {
                $sqlT = "SELECT COUNT(pt.id_tema) 
                         FROM plan_temas pt
                         JOIN plan_capacidades pc ON pt.id_capacidad = pc.id_capacidad
                         JOIN plan_unidades pu ON pc.id_unidad = pu.id_unidad
                         WHERE pu.id_plan = :id_plan";
                $stmtT = $this->conn->prepare($sqlT);
                $stmtT->execute([':id_plan' => $idPlan]);
                $totalTemas = (int)$stmtT->fetchColumn();

                $sqlD = "SELECT COUNT(DISTINCT cd.id_tema) FROM clases_diarias cd WHERE cd.id_plan = :id_plan AND cd.id_tema IS NOT NULL";
                $stmtD = $this->conn->prepare($sqlD);
                $stmtD->execute([':id_plan' => $idPlan]);
                $temasDictados = (int)$stmtD->fetchColumn();
            }
            $mat['total_temas'] = $totalTemas;
            $mat['temas_dictados'] = $temasDictados;
            $mat['porcentaje_avance'] = ($totalTemas > 0) ? round(($temasDictados / $totalTemas) * 100) : 0;

            // Asistencias específicas en esta materia
            $sqlAsistMat = "SELECT ae.estado, COUNT(*) AS cantidad
                            FROM asistencias_estudiantes ae
                            JOIN horarios h ON ae.id_horario = h.id_horario
                            WHERE ae.id_estudiante = :id_estudiante AND h.id_asignacion = :id_asignacion AND ae.activo = 1
                            GROUP BY ae.estado";
            $stmtAsistMat = $this->conn->prepare($sqlAsistMat);
            $stmtAsistMat->execute([':id_estudiante' => $id_estudiante, ':id_asignacion' => $idAsig]);
            $rawMat = $stmtAsistMat->fetchAll(PDO::FETCH_KEY_PAIR);

            $mat['presentes'] = (int)($rawMat['PRESENTE'] ?? $rawMat['presente'] ?? 0);
            $mat['tardanzas'] = (int)($rawMat['TARDANZA'] ?? $rawMat['tardanza'] ?? 0);
            $mat['ausencias'] = (int)($rawMat['AUSENTE'] ?? $rawMat['ausente'] ?? $rawMat['FALTA'] ?? 0);
            $totMat = $mat['presentes'] + $mat['tardanzas'] + $mat['ausencias'];
            $mat['tasa_asistencia'] = ($totMat > 0) ? round((($mat['presentes'] + $mat['tardanzas']) / $totMat) * 100) : 100;
        }

        // Historial reciente de asistencias (últimas 25)
        $sqlHist = "SELECT ae.fecha, ae.hora_marcacion, ae.estado, ae.tipo_registro,
                           m.nombre AS materia_nombre
                    FROM asistencias_estudiantes ae
                    LEFT JOIN horarios h ON ae.id_horario = h.id_horario
                    LEFT JOIN asignacion_docente ad ON h.id_asignacion = ad.id_asignacion
                    LEFT JOIN materias m ON ad.id_materia = m.id_materia
                    WHERE ae.id_estudiante = :id_estudiante AND ae.activo = 1
                    ORDER BY ae.fecha DESC, ae.hora_marcacion DESC
                    LIMIT 25";
        $stmtHist = $this->conn->prepare($sqlHist);
        $stmtHist->execute([':id_estudiante' => $id_estudiante]);
        $historial = $stmtHist->fetchAll(PDO::FETCH_ASSOC);

        // Tutores vinculados
        $sqlTut = "SELECT t.nombre, t.apellido, t.cedula_identidad, t.celular, t.email, et.parentesco, et.es_principal
                   FROM estudiante_tutores et
                   JOIN tutores t ON et.id_tutor = t.id_tutor
                   WHERE et.id_estudiante = :id_estudiante AND et.activo = 1";
        $stmtTut = $this->conn->prepare($sqlTut);
        $stmtTut->execute([':id_estudiante' => $id_estudiante]);
        $tutores = $stmtTut->fetchAll(PDO::FETCH_ASSOC);

        return [
            'estudiante' => $estudiante,
            'materias' => $materias,
            'historial_asistencias' => $historial,
            'tutores' => $tutores,
        ];
    }
}
