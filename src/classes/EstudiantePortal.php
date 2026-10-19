<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';

/**
 * Clase EstudiantePortal
 * Gestiona el acceso de solo lectura para estudiantes,
 * mostrando sus materias cursadas en formato de tarjetas con indicadores de avance y asistencia.
 */
class EstudiantePortal {
    private PDO $conn;

    public function __construct(PDO $db) {
        $this->conn = $db;
    }

    /**
     * Retorna el registro del estudiante a partir del id_usuario de sesión
     */
    public function obtenerPerfilPorUsuario(int $id_usuario): ?array {
        $sql = "SELECT e.id_estudiante, e.nombre, e.apellido, e.cedula_identidad, e.id_grado,
                       e.huella_registrada, e.huella_id,
                       g.nombre AS grado_nombre, g.seccion, g.turno, g.anho_lectivo,
                       a.nombre AS aula_nombre, a.codigo_sala, a.ubicacion
                FROM estudiantes e
                LEFT JOIN grados g ON e.id_grado = g.id_grado
                LEFT JOIN aulas a ON g.id_aula = a.id_aula
                WHERE e.id_usuario = :id_usuario AND e.activo = 1
                LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':id_usuario' => $id_usuario]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Retorna las materias cursadas por el estudiante con indicador de avance temático y asistencia
     */
    public function obtenerMisMaterias(int $id_estudiante, int $id_grado): array {
        $sql = "SELECT ad.id_asignacion, ad.carga_horaria,
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
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':id_grado' => $id_grado]);
        $materias = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($materias as &$mat) {
            $idPlan = !empty($mat['id_plan']) ? (int)$mat['id_plan'] : null;
            $idAsig = (int)$mat['id_asignacion'];

            // Conteo de temas en el plan
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

                $sqlD = "SELECT COUNT(DISTINCT cd.id_tema)
                         FROM clases_diarias cd
                         WHERE cd.id_plan = :id_plan AND cd.id_tema IS NOT NULL";
                $stmtD = $this->conn->prepare($sqlD);
                $stmtD->execute([':id_plan' => $idPlan]);
                $temasDictados = (int)$stmtD->fetchColumn();
            }

            $mat['total_temas'] = $totalTemas;
            $mat['temas_dictados'] = $temasDictados;
            $mat['porcentaje_avance'] = ($totalTemas > 0) ? round(($temasDictados / $totalTemas) * 100) : 0;

            // Conteo de asistencias del estudiante en esta materia
            $sqlAsist = "SELECT ae.estado, COUNT(*) AS cantidad
                         FROM asistencias_estudiantes ae
                         JOIN horarios h ON ae.id_horario = h.id_horario
                         WHERE ae.id_estudiante = :id_estudiante AND h.id_asignacion = :id_asignacion AND ae.activo = 1
                         GROUP BY ae.estado";
            $stmtAsist = $this->conn->prepare($sqlAsist);
            $stmtAsist->execute([':id_estudiante' => $id_estudiante, ':id_asignacion' => $idAsig]);
            $raw = $stmtAsist->fetchAll(PDO::FETCH_KEY_PAIR);

            $p = (int)($raw['PRESENTE'] ?? $raw['presente'] ?? 0);
            $t = (int)($raw['TARDANZA'] ?? $raw['tardanza'] ?? 0);
            $a = (int)($raw['AUSENTE'] ?? $raw['ausente'] ?? $raw['FALTA'] ?? 0);
            $total = $p + $t + $a;

            $mat['asistencias_presente'] = $p;
            $mat['asistencias_tardanza'] = $t;
            $mat['asistencias_ausente'] = $a;
            $mat['total_clases'] = $total;
            $mat['porcentaje_asistencia'] = ($total > 0) ? round((($p + $t) / $total) * 100) : 100;
        }

        return $materias;
    }

    /**
     * Historial de asistencias recientes del estudiante
     */
    public function obtenerHistorial(int $id_estudiante, int $limit = 20): array {
        $sql = "SELECT ae.fecha, ae.hora_marcacion, ae.estado, ae.tipo_registro,
                       m.nombre AS materia_nombre
                FROM asistencias_estudiantes ae
                LEFT JOIN horarios h ON ae.id_horario = h.id_horario
                LEFT JOIN asignacion_docente ad ON h.id_asignacion = ad.id_asignacion
                LEFT JOIN materias m ON ad.id_materia = m.id_materia
                WHERE ae.id_estudiante = :id_estudiante AND ae.activo = 1
                ORDER BY ae.fecha DESC, ae.hora_marcacion DESC
                LIMIT :limit";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id_estudiante', $id_estudiante, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
