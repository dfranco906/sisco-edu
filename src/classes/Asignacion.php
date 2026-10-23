<?php
declare(strict_types=1);

class Asignacion {
    private PDO $conn;
    private string $table_name = "asignacion_docente";

    public ?int $id_profesor = null;
    public ?int $id_materia = null;
    public ?int $id_grado = null;
    public ?int $carga_horaria = null;
    public ?int $año_lectivo = null;
    public ?int $id_asignacion = null;

    public function __construct(PDO $db) {
        $this->conn = $db;
    }

    public function matricularEstudiantesDelGrado(int $id_grado, int $id_materia, int $id_asignacion, int $anho): int {
        if ($id_grado <= 0 || $id_materia <= 0) {
            return 0;
        }

        $stmtEst = $this->conn->prepare("SELECT id_estudiante FROM estudiantes WHERE id_grado = :id_grado AND activo = 1");
        $stmtEst->execute([':id_grado' => $id_grado]);
        $estudiantes = $stmtEst->fetchAll(PDO::FETCH_COLUMN);

        if (empty($estudiantes)) {
            return 0;
        }

        $stmtMat = $this->conn->prepare("
            INSERT INTO matriculas (id_estudiante, id_grado, id_materia, id_asignacion, anho_lectivo, activo)
            VALUES (:id_estudiante, :id_grado, :id_materia, :id_asignacion, :anho, 1)
            ON CONFLICT (id_estudiante, id_materia, anho_lectivo)
            DO UPDATE SET id_grado = EXCLUDED.id_grado, id_asignacion = EXCLUDED.id_asignacion, activo = 1
        ");

        $total = 0;
        foreach ($estudiantes as $idEst) {
            $ok = $stmtMat->execute([
                ':id_estudiante' => (int)$idEst,
                ':id_grado' => $id_grado,
                ':id_materia' => $id_materia,
                ':id_asignacion' => $id_asignacion,
                ':anho' => $anho
            ]);
            if ($ok) $total++;
        }

        return $total;
    }

    public function crear(): bool {
        $anho = $this->año_lectivo ?? (int)date('Y');
        $query = "INSERT INTO {$this->table_name} (id_profesor, id_materia, id_grado, carga_horaria, anho_lectivo, anio_lectivo)
                  VALUES (:id_p, :id_m, :id_g, :carga, :anio, :anio)";

        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(":id_p", $this->id_profesor);
        $stmt->bindValue(":id_m", $this->id_materia);
        $stmt->bindValue(":id_g", $this->id_grado);
        $stmt->bindValue(":carga", $this->carga_horaria ?? 0);
        $stmt->bindValue(":anio", $anho);

        $ok = $stmt->execute();
        if ($ok && !empty($this->id_grado) && !empty($this->id_materia)) {
            $idNuevo = (int)$this->conn->lastInsertId();
            $this->id_asignacion = $idNuevo;
            $this->matricularEstudiantesDelGrado((int)$this->id_grado, (int)$this->id_materia, $idNuevo, $anho);
        }

        return $ok;
    }

    public function leer(?int $id_profesor = null): PDOStatement {
        $query = "SELECT
                    ad.id_asignacion,
                    ad.id_profesor,
                    ad.id_materia,
                    ad.id_grado,
                    COALESCE(ad.carga_horaria, 0) AS carga_horaria,
                    CONCAT(p.nombre, ' ', p.apellido) AS profesor,
                    p.cedula_identidad AS profesor_ci,
                    m.nombre AS materia,
                    COALESCE(g.nombre, 'Sin Grado') AS grado,
                    g.seccion,
                    COALESCE(t.nombre, 'Sin Turno') AS turno,
                    COALESCE(ad.anho_lectivo, ad.anio_lectivo, 2026) AS anho_lectivo,
                    COALESCE(ad.anho_lectivo, ad.anio_lectivo, 2026) AS \"año_lectivo\",
                    CONCAT(COALESCE(g.nombre, 'Sin Grado'), ' - ', m.nombre, ' (', p.nombre, ' ', p.apellido, ')') AS descripcion
                  FROM {$this->table_name} ad
                  LEFT JOIN profesores p ON ad.id_profesor = p.id_profesor
                  LEFT JOIN materias m ON ad.id_materia = m.id_materia
                  LEFT JOIN grados g ON ad.id_grado = g.id_grado
                  LEFT JOIN turnos t ON g.id_turno = t.id_turno
                  WHERE ad.activo = 1";

        $params = [];
        if ($id_profesor !== null) {
            $query .= " AND ad.id_profesor = :id_profesor ORDER BY g.nombre ASC, m.nombre ASC";
            $params[':id_profesor'] = $id_profesor;
        } else {
            $query .= " ORDER BY g.nombre ASC, m.nombre ASC, ad.id_asignacion DESC";
        }

        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        return $stmt;
    }

    public function leerDesactivadas(): PDOStatement {
        $query = "SELECT
                    ad.id_asignacion,
                    ad.id_profesor,
                    ad.id_materia,
                    ad.id_grado,
                    COALESCE(ad.carga_horaria, 0) AS carga_horaria,
                    CONCAT(p.nombre, ' ', p.apellido) AS profesor,
                    p.cedula_identidad AS profesor_ci,
                    m.nombre AS materia,
                    COALESCE(g.nombre, 'Sin Grado') AS grado,
                    g.seccion,
                    COALESCE(t.nombre, 'Sin Turno') AS turno,
                    COALESCE(ad.anho_lectivo, ad.anio_lectivo, 2026) AS anho_lectivo,
                    COALESCE(ad.anho_lectivo, ad.anio_lectivo, 2026) AS \"año_lectivo\",
                    ad.activo,
                    CONCAT(COALESCE(g.nombre, 'Sin Grado'), ' - ', m.nombre, ' (', p.nombre, ' ', p.apellido, ')') AS descripcion
                  FROM {$this->table_name} ad
                  LEFT JOIN profesores p ON ad.id_profesor = p.id_profesor
                  LEFT JOIN materias m ON ad.id_materia = m.id_materia
                  LEFT JOIN grados g ON ad.id_grado = g.id_grado
                  LEFT JOIN turnos t ON g.id_turno = t.id_turno
                  WHERE ad.activo = 0
                  ORDER BY ad.id_asignacion DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function obtenerDetalle(int $id_asignacion): ?array {
        $stmt = $this->conn->prepare("
            SELECT
                ad.id_asignacion,
                ad.id_profesor,
                ad.id_materia,
                ad.id_grado,
                COALESCE(ad.carga_horaria, 0) AS carga_horaria,
                COALESCE(ad.anho_lectivo, ad.anio_lectivo, 2026) AS anho_lectivo,
                p.nombre AS profesor_nombre,
                p.apellido AS profesor_apellido,
                p.cedula_identidad AS profesor_ci,
                m.nombre AS materia_nombre,
                m.descripcion AS materia_descripcion,
                g.nombre AS grado_nombre,
                g.seccion,
                COALESCE(a.nombre, 'Sin aula fija') AS aula_nombre,
                COALESCE(t.nombre, 'Sin turno') AS turno_nombre
            FROM {$this->table_name} ad
            LEFT JOIN profesores p ON ad.id_profesor = p.id_profesor
            LEFT JOIN materias m ON ad.id_materia = m.id_materia
            LEFT JOIN grados g ON ad.id_grado = g.id_grado
            LEFT JOIN aulas a ON g.id_aula = a.id_aula
            LEFT JOIN turnos t ON g.id_turno = t.id_turno
            WHERE ad.id_asignacion = :id
        ");
        $stmt->execute([':id' => $id_asignacion]);
        $det = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$det) {
            return null;
        }

        // Horarios asignados en la semana
        $stmtH = $this->conn->prepare("
            SELECT h.id_horario, h.dia_semana, h.hora_inicio, h.hora_fin, COALESCE(a.nombre, 'Aula asignada') AS aula
            FROM horarios h
            LEFT JOIN aulas a ON h.id_aula = a.id_aula
            WHERE h.id_asignacion = :id AND h.activo = 1
            ORDER BY CASE 
                WHEN h.dia_semana ILIKE 'lunes' THEN 1
                WHEN h.dia_semana ILIKE 'martes' THEN 2
                WHEN h.dia_semana ILIKE 'miercoles' OR h.dia_semana ILIKE 'miércoles' THEN 3
                WHEN h.dia_semana ILIKE 'jueves' THEN 4
                WHEN h.dia_semana ILIKE 'viernes' THEN 5
                WHEN h.dia_semana ILIKE 'sabado' OR h.dia_semana ILIKE 'sábado' THEN 6
                ELSE 7 END, h.hora_inicio ASC
        ");
        $stmtH->execute([':id' => $id_asignacion]);
        $det['horarios'] = $stmtH->fetchAll(PDO::FETCH_ASSOC);

        // Conteo de estudiantes matriculados en esta materia/grado
        $stmtCant = $this->conn->prepare("
            SELECT COUNT(*) FROM matriculas WHERE id_asignacion = :id AND activo = 1
        ");
        $stmtCant->execute([':id' => $id_asignacion]);
        $det['total_alumnos'] = (int)$stmtCant->fetchColumn();

        // Plan Anual Curricular
        $stmtPlan = $this->conn->prepare("
            SELECT id_plan, estado
            FROM planes_anuales
            WHERE id_asignacion = :id AND activo = 1
            LIMIT 1
        ");
        $stmtPlan->execute([':id' => $id_asignacion]);
        $det['plan_anual'] = $stmtPlan->fetch(PDO::FETCH_ASSOC) ?: null;

        return $det;
    }

    public function actualizar(): bool {
        $anho = $this->año_lectivo ?? (int)date('Y');
        $query = "UPDATE {$this->table_name}
                  SET id_profesor = :id_profesor,
                      id_materia = :id_materia,
                      id_grado = :id_grado,
                      carga_horaria = :carga,
                      anho_lectivo = :anio,
                      anio_lectivo = :anio
                  WHERE id_asignacion = :id_asignacion";

        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(":id_profesor", $this->id_profesor);
        $stmt->bindValue(":id_materia", $this->id_materia);
        $stmt->bindValue(":id_grado", $this->id_grado);
        $stmt->bindValue(":carga", $this->carga_horaria ?? 0);
        $stmt->bindValue(":anio", $anho);
        $stmt->bindValue(":id_asignacion", $this->id_asignacion);

        $ok = $stmt->execute();
        if ($ok && !empty($this->id_grado) && !empty($this->id_materia) && !empty($this->id_asignacion)) {
            $this->matricularEstudiantesDelGrado((int)$this->id_grado, (int)$this->id_materia, (int)$this->id_asignacion, $anho);
        }

        return $ok;
    }

    public function desactivar(): bool {
        $query = "UPDATE {$this->table_name} SET activo = 0 WHERE id_asignacion = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(":id", $this->id_asignacion);
        return $stmt->execute();
    }

    public function restaurar(): bool {
        $query = "UPDATE {$this->table_name} SET activo = 1 WHERE id_asignacion = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(":id", $this->id_asignacion);
        return $stmt->execute();
    }

    public function contarDependencias(): int {
        $query = "SELECT COUNT(*) FROM horarios WHERE id_asignacion = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(":id", $this->id_asignacion);
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    public function eliminar(): bool {
        $query = "DELETE FROM {$this->table_name} WHERE id_asignacion = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(":id", $this->id_asignacion);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }
}
