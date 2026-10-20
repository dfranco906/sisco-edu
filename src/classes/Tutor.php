<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';

/**
 * Clase Tutor
 * Gestiona el acceso de solo lectura para padres, madres y tutores,
 * listando sus hijos a cargo y generando el expediente integral de progreso.
 * Optimizado con consultas batch masivas para máxima velocidad.
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
     * Retorna la lista de hijos vinculados a este tutor con métricas agrupadas en batch
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

        if (empty($hijos)) {
            return [];
        }

        $idsEst = array_map(fn($h) => (int)$h['id_estudiante'], $hijos);
        $inEst = implode(',', array_fill(0, count($idsEst), '?'));
        $hoy = date('Y-m-d');

        // Batch 1: Asistencia hoy de todos los hijos
        $paramsHoy = array_merge($idsEst, [$hoy]);
        $sqlHoy = "SELECT ae.id_estudiante, ae.estado, ae.hora_marcacion
                   FROM asistencias_estudiantes ae
                   WHERE ae.id_estudiante IN ($inEst) AND ae.fecha = ? AND ae.activo = 1
                   ORDER BY ae.hora_marcacion DESC";
        $stmtHoy = $this->conn->prepare($sqlHoy);
        $stmtHoy->execute($paramsHoy);
        $hoyRows = $stmtHoy->fetchAll(PDO::FETCH_ASSOC);

        $asistHoyMap = [];
        foreach ($hoyRows as $hr) {
            $id = (int)$hr['id_estudiante'];
            if (!isset($asistHoyMap[$id])) {
                $asistHoyMap[$id] = $hr;
            }
        }

        // Batch 2: Métricas acumuladas de asistencia
        $sqlMetricas = "SELECT ae.id_estudiante, ae.estado, COUNT(*) AS cantidad
                        FROM asistencias_estudiantes ae
                        WHERE ae.id_estudiante IN ($inEst) AND ae.activo = 1
                        GROUP BY ae.id_estudiante, ae.estado";
        $stmtMet = $this->conn->prepare($sqlMetricas);
        $stmtMet->execute($idsEst);
        $metRows = $stmtMet->fetchAll(PDO::FETCH_ASSOC);

        $metMap = [];
        foreach ($metRows as $mr) {
            $id = (int)$mr['id_estudiante'];
            $st = strtoupper((string)$mr['estado']);
            if (!isset($metMap[$id])) {
                $metMap[$id] = ['presente' => 0, 'tardanza' => 0, 'ausente' => 0];
            }
            if ($st === 'PRESENTE') $metMap[$id]['presente'] += (int)$mr['cantidad'];
            elseif ($st === 'TARDANZA') $metMap[$id]['tardanza'] += (int)$mr['cantidad'];
            elseif ($st === 'AUSENTE' || $st === 'FALTA') $metMap[$id]['ausente'] += (int)$mr['cantidad'];
        }

        // Batch 3: Materias por grado
        $idsGrados = array_values(array_filter(array_unique(array_map(fn($h) => (int)($h['id_grado'] ?? 0), $hijos))));
        $matCountMap = [];
        if (!empty($idsGrados)) {
            $inGrados = implode(',', array_fill(0, count($idsGrados), '?'));
            $sqlMat = "SELECT id_grado, COUNT(DISTINCT id_materia) AS total FROM asignacion_docente WHERE id_grado IN ($inGrados) AND activo = 1 GROUP BY id_grado";
            $stmtMat = $this->conn->prepare($sqlMat);
            $stmtMat->execute($idsGrados);
            $matCountMap = $stmtMat->fetchAll(PDO::FETCH_KEY_PAIR);
        }

        foreach ($hijos as &$hijo) {
            $idEst = (int)$hijo['id_estudiante'];
            $idGrado = (int)($hijo['id_grado'] ?? 0);

            $asistHoy = $asistHoyMap[$idEst] ?? null;
            $hijo['asistencia_hoy'] = $asistHoy ? $asistHoy['estado'] : 'Sin registro';
            $hijo['hora_hoy'] = $asistHoy ? substr((string)$asistHoy['hora_marcacion'], 0, 5) : null;

            $p = $metMap[$idEst]['presente'] ?? 0;
            $t = $metMap[$idEst]['tardanza'] ?? 0;
            $a = $metMap[$idEst]['ausente'] ?? 0;
            $tot = $p + $t + $a;

            $hijo['tasa_asistencia'] = ($tot > 0) ? round((($p + $t) / $tot) * 100) : 100;
            $hijo['total_asistencias'] = $p;
            $hijo['total_tardanzas'] = $t;
            $hijo['total_ausencias'] = $a;
            $hijo['total_materias'] = (int)($matCountMap[$idGrado] ?? 0);
        }

        return $hijos;
    }

    /**
     * Expediente integral del estudiante (Currículum / Dossier de avance)
     * Ejecutado con consultas batch para carga instantánea.
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

        $idsPlanes = array_values(array_filter(array_map(fn($m) => !empty($m['id_plan']) ? (int)$m['id_plan'] : null, $materias)));
        $idsAsignaciones = array_values(array_filter(array_map(fn($m) => (int)$m['id_asignacion'], $materias)));

        // Batch 1: Total temas y dictados
        $totalTemasMap = [];
        $temasDictadosMap = [];
        if (!empty($idsPlanes)) {
            $inPlanes = implode(',', array_fill(0, count($idsPlanes), '?'));
            $sqlTT = "SELECT pu.id_plan, COUNT(pt.id_tema) AS total_temas
                      FROM plan_temas pt
                      JOIN plan_capacidades pc ON pt.id_capacidad = pc.id_capacidad
                      JOIN plan_unidades pu ON pc.id_unidad = pu.id_unidad
                      WHERE pu.id_plan IN ($inPlanes)
                      GROUP BY pu.id_plan";
            $stmtTT = $this->conn->prepare($sqlTT);
            $stmtTT->execute($idsPlanes);
            $totalTemasMap = $stmtTT->fetchAll(PDO::FETCH_KEY_PAIR);

            $sqlTD = "SELECT cd.id_plan, COUNT(DISTINCT cd.id_tema) AS temas_dictados
                      FROM clases_diarias cd
                      WHERE cd.id_plan IN ($inPlanes) AND cd.id_tema IS NOT NULL
                      GROUP BY cd.id_plan";
            $stmtTD = $this->conn->prepare($sqlTD);
            $stmtTD->execute($idsPlanes);
            $temasDictadosMap = $stmtTD->fetchAll(PDO::FETCH_KEY_PAIR);
        }

        // Batch 2: Asistencias de este estudiante en las asignaciones
        $asistMap = [];
        if (!empty($idsAsignaciones)) {
            $inAsig = implode(',', array_fill(0, count($idsAsignaciones), '?'));
            $paramsAsist = array_merge([$id_estudiante], $idsAsignaciones);
            $sqlAsist = "SELECT h.id_asignacion, ae.estado, COUNT(*) AS cantidad
                         FROM asistencias_estudiantes ae
                         JOIN horarios h ON ae.id_horario = h.id_horario
                         WHERE ae.id_estudiante = ? AND h.id_asignacion IN ($inAsig) AND ae.activo = 1
                         GROUP BY h.id_asignacion, ae.estado";
            $stmtAsist = $this->conn->prepare($sqlAsist);
            $stmtAsist->execute($paramsAsist);
            $asistRows = $stmtAsist->fetchAll(PDO::FETCH_ASSOC);

            foreach ($asistRows as $ar) {
                $idAsig = (int)$ar['id_asignacion'];
                $estado = strtoupper((string)$ar['estado']);
                if (!isset($asistMap[$idAsig])) {
                    $asistMap[$idAsig] = ['presente' => 0, 'tardanza' => 0, 'ausente' => 0];
                }
                if ($estado === 'PRESENTE') $asistMap[$idAsig]['presente'] += (int)$ar['cantidad'];
                elseif ($estado === 'TARDANZA') $asistMap[$idAsig]['tardanza'] += (int)$ar['cantidad'];
                elseif ($estado === 'AUSENTE' || $estado === 'FALTA') $asistMap[$idAsig]['ausente'] += (int)$ar['cantidad'];
            }
        }

        foreach ($materias as &$mat) {
            $idPlan = !empty($mat['id_plan']) ? (int)$mat['id_plan'] : null;
            $idAsig = (int)$mat['id_asignacion'];

            $totalTemas = (int)($totalTemasMap[$idPlan] ?? 0);
            $temasDictados = (int)($temasDictadosMap[$idPlan] ?? 0);
            $mat['total_temas'] = $totalTemas;
            $mat['temas_dictados'] = $temasDictados;
            $mat['porcentaje_avance'] = ($totalTemas > 0) ? round(($temasDictados / $totalTemas) * 100) : 0;

            $p = $asistMap[$idAsig]['presente'] ?? 0;
            $t = $asistMap[$idAsig]['tardanza'] ?? 0;
            $a = $asistMap[$idAsig]['ausente'] ?? 0;
            $totMat = $p + $t + $a;

            $mat['presentes'] = $p;
            $mat['tardanzas'] = $t;
            $mat['ausencias'] = $a;
            $mat['tasa_asistencia'] = ($totMat > 0) ? round((($p + $t) / $totMat) * 100) : 100;
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

    public function crear(array $datos): int {
        $nombre = trim((string)($datos['nombre'] ?? ''));
        $apellido = trim((string)($datos['apellido'] ?? ''));
        $cedula = trim((string)($datos['cedula_identidad'] ?? ''));
        $celular = trim((string)($datos['celular'] ?? ''));
        $email = trim((string)($datos['email'] ?? ''));
        $direccion = trim((string)($datos['direccion'] ?? ''));

        if (empty($nombre) || empty($apellido) || empty($cedula)) {
            throw new InvalidArgumentException("Nombre, apellido y cédula de identidad son obligatorios.");
        }

        require_once __DIR__ . '/IdentidadHelper.php';
        $idUsuario = IdentidadHelper::asegurarUsuario(
            $this->conn,
            'Tutor',
            $cedula,
            $nombre,
            $apellido,
            $email,
            $celular
        );

        $stmt = $this->conn->prepare("
            INSERT INTO tutores (id_usuario, nombre, apellido, cedula_identidad, celular, email, direccion, activo, fecha_registro)
            VALUES (:id_usuario, :nombre, :apellido, :cedula, :celular, :email, :direccion, 1, NOW())
            RETURNING id_tutor
        ");
        $stmt->execute([
            ':id_usuario' => $idUsuario,
            ':nombre' => $nombre,
            ':apellido' => $apellido,
            ':cedula' => $cedula,
            ':celular' => $celular ?: null,
            ':email' => $email ?: null,
            ':direccion' => $direccion ?: null,
        ]);

        return (int)$stmt->fetchColumn();
    }

    public function leer(): array {
        $sql = "SELECT t.id_tutor, t.nombre, t.apellido, t.cedula_identidad, t.celular, t.email, t.direccion,
                       t.id_usuario, t.activo,
                       COUNT(DISTINCT et.id_estudiante) AS total_hijos,
                       CONCAT(t.nombre, ' ', t.apellido, ' - CI: ', t.cedula_identidad) AS nombre_completo
                FROM tutores t
                LEFT JOIN estudiante_tutores et ON et.id_tutor = t.id_tutor AND et.activo = 1
                WHERE t.activo = 1
                GROUP BY t.id_tutor, t.nombre, t.apellido, t.cedula_identidad, t.celular, t.email, t.direccion, t.id_usuario, t.activo
                ORDER BY t.apellido ASC, t.nombre ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function leerDesactivados(): array {
        $sql = "SELECT t.id_tutor, t.nombre, t.apellido, t.cedula_identidad, t.celular, t.email, t.direccion,
                       t.id_usuario, t.activo,
                       COUNT(DISTINCT et.id_estudiante) AS total_hijos,
                       CONCAT(t.nombre, ' ', t.apellido, ' - CI: ', t.cedula_identidad) AS nombre_completo
                FROM tutores t
                LEFT JOIN estudiante_tutores et ON et.id_tutor = t.id_tutor AND et.activo = 1
                WHERE t.activo = 0
                GROUP BY t.id_tutor, t.nombre, t.apellido, t.cedula_identidad, t.celular, t.email, t.direccion, t.id_usuario, t.activo
                ORDER BY t.apellido ASC, t.nombre ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function actualizar(int $id_tutor, array $datos): bool {
        $nombre = trim((string)($datos['nombre'] ?? ''));
        $apellido = trim((string)($datos['apellido'] ?? ''));
        $cedula = trim((string)($datos['cedula_identidad'] ?? ''));
        $celular = trim((string)($datos['celular'] ?? ''));
        $email = trim((string)($datos['email'] ?? ''));
        $direccion = trim((string)($datos['direccion'] ?? ''));

        if ($id_tutor <= 0 || empty($nombre) || empty($apellido) || empty($cedula)) {
            return false;
        }

        require_once __DIR__ . '/IdentidadHelper.php';
        $idUsuario = IdentidadHelper::asegurarUsuario(
            $this->conn,
            'Tutor',
            $cedula,
            $nombre,
            $apellido,
            $email,
            $celular
        );

        $stmt = $this->conn->prepare("
            UPDATE tutores
            SET nombre = :nombre,
                apellido = :apellido,
                cedula_identidad = :cedula,
                celular = :celular,
                email = :email,
                direccion = :direccion,
                id_usuario = COALESCE(id_usuario, :id_usuario)
            WHERE id_tutor = :id_tutor
        ");
        return $stmt->execute([
            ':nombre' => $nombre,
            ':apellido' => $apellido,
            ':cedula' => $cedula,
            ':celular' => $celular ?: null,
            ':email' => $email ?: null,
            ':direccion' => $direccion ?: null,
            ':id_usuario' => $idUsuario,
            ':id_tutor' => $id_tutor
        ]);
    }

    public function desactivar(int $id_tutor): bool {
        $stmt = $this->conn->prepare("UPDATE tutores SET activo = 0 WHERE id_tutor = :id_tutor");
        return $stmt->execute([':id_tutor' => $id_tutor]);
    }

    public function restaurar(int $id_tutor): bool {
        $stmt = $this->conn->prepare("UPDATE tutores SET activo = 1 WHERE id_tutor = :id_tutor");
        return $stmt->execute([':id_tutor' => $id_tutor]);
    }

    public function vincularEstudiante(int $id_tutor, int $id_estudiante, string $parentesco = 'Tutor/a', int $es_principal = 0): bool {
        if ($id_tutor <= 0 || $id_estudiante <= 0) {
            return false;
        }

        $stmtCheck = $this->conn->prepare("
            SELECT id_estudiante_tutor
            FROM estudiante_tutores
            WHERE id_tutor = :id_tutor AND id_estudiante = :id_estudiante
            LIMIT 1
        ");
        $stmtCheck->execute([':id_tutor' => $id_tutor, ':id_estudiante' => $id_estudiante]);
        $existe = $stmtCheck->fetchColumn();

        if ($existe) {
            $stmtUp = $this->conn->prepare("
                UPDATE estudiante_tutores
                SET parentesco = :parentesco,
                    es_principal = :es_principal,
                    activo = 1
                WHERE id_estudiante_tutor = :id
            ");
            return $stmtUp->execute([
                ':parentesco' => trim($parentesco) ?: 'Tutor/a',
                ':es_principal' => $es_principal,
                ':id' => $existe
            ]);
        }

        $stmtIns = $this->conn->prepare("
            INSERT INTO estudiante_tutores (id_estudiante, id_tutor, parentesco, es_principal, activo, fecha_asignacion)
            VALUES (:id_estudiante, :id_tutor, :parentesco, :es_principal, 1, NOW())
        ");
        return $stmtIns->execute([
            ':id_estudiante' => $id_estudiante,
            ':id_tutor' => $id_tutor,
            ':parentesco' => trim($parentesco) ?: 'Tutor/a',
            ':es_principal' => $es_principal
        ]);
    }

    public function desvincularEstudiante(int $id_tutor, int $id_estudiante): bool {
        $stmt = $this->conn->prepare("
            UPDATE estudiante_tutores
            SET activo = 0
            WHERE id_tutor = :id_tutor AND id_estudiante = :id_estudiante
        ");
        return $stmt->execute([':id_tutor' => $id_tutor, ':id_estudiante' => $id_estudiante]);
    }

    public function obtenerEstudiantesVinculados(int $id_tutor): array {
        $stmt = $this->conn->prepare("
            SELECT et.id_estudiante_tutor, et.parentesco, et.es_principal, et.fecha_asignacion,
                   e.id_estudiante, e.nombre, e.apellido, e.cedula_identidad,
                   g.id_grado, g.nombre AS grado_nombre, g.seccion
            FROM estudiante_tutores et
            JOIN estudiantes e ON et.id_estudiante = e.id_estudiante
            LEFT JOIN grados g ON e.id_grado = g.id_grado
            WHERE et.id_tutor = :id_tutor AND et.activo = 1 AND e.activo = 1
            ORDER BY e.apellido ASC, e.nombre ASC
        ");
        $stmt->execute([':id_tutor' => $id_tutor]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
