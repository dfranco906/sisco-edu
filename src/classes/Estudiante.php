<?php
class Estudiante {
    private $conn;

    public $id_estudiante, $nombre, $apellido, $cedula_identidad;
    public $id_grado;
    public $user_id_global;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function matricularEnMateriasDeGrado(int $id_estudiante, int $id_grado): int {
        if ($id_estudiante <= 0 || $id_grado <= 0) {
            return 0;
        }

        $stmt = $this->conn->prepare("
            SELECT DISTINCT ad.id_materia, ad.id_asignacion
            FROM asignacion_docente ad
            WHERE ad.id_grado = :id_grado AND ad.activo = 1
        ");
        $stmt->execute([':id_grado' => $id_grado]);
        $materias = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($materias)) {
            return 0;
        }

        $stmtMatricula = $this->conn->prepare("
            INSERT INTO matriculas (id_estudiante, id_grado, id_materia, id_asignacion, anho_lectivo, activo)
            VALUES (:id_estudiante, :id_grado, :id_materia, :id_asignacion, 2026, 1)
            ON CONFLICT (id_estudiante, id_materia, anho_lectivo)
            DO UPDATE SET id_grado = EXCLUDED.id_grado, id_asignacion = EXCLUDED.id_asignacion, activo = 1
        ");

        $total = 0;
        foreach ($materias as $m) {
            $ok = $stmtMatricula->execute([
                ':id_estudiante' => $id_estudiante,
                ':id_grado' => $id_grado,
                ':id_materia' => (int)$m['id_materia'],
                ':id_asignacion' => !empty($m['id_asignacion']) ? (int)$m['id_asignacion'] : null
            ]);
            if ($ok) $total++;
        }

        return $total;
    }

    public function crear(): array {
        $query = "INSERT INTO estudiantes (nombre, apellido, cedula_identidad, id_grado, user_id_global)
                  VALUES (:nombre, :apellido, :cedula, :id_grado, :user_id_global)";

        $stmt = $this->conn->prepare($query);

        $ok = $stmt->execute([
            ":nombre" => trim((string)$this->nombre),
            ":apellido" => trim((string)$this->apellido),
            ":cedula" => trim((string)$this->cedula_identidad),
            ":id_grado" => !empty($this->id_grado) ? (int)$this->id_grado : null,
            ":user_id_global" => $this->user_id_global
        ]);

        $matriculadas = 0;
        if ($ok) {
            $idNuevo = (int)$this->conn->lastInsertId();
            $this->id_estudiante = $idNuevo;
            if (!empty($this->id_grado)) {
                $matriculadas = $this->matricularEnMateriasDeGrado($idNuevo, (int)$this->id_grado);
            }
        }

        return [
            'ok' => $ok,
            'id_estudiante' => $this->id_estudiante,
            'materias_matriculadas' => $matriculadas
        ];
    }

    public function leer() {
        $query = "SELECT 
                    e.id_estudiante,
                    e.nombre,
                    e.apellido,
                    e.cedula_identidad,
                    e.user_id_global,
                    e.id_grado,
                    g.nombre AS grado,
                    COALESCE(m.total_materias, 0) AS materias_matriculadas,
                    e.activo,
                    CONCAT(e.nombre, ' ', e.apellido, ' - CI: ', e.cedula_identidad) AS nombre_completo
                  FROM estudiantes e
                  LEFT JOIN grados g ON e.id_grado = g.id_grado
                  LEFT JOIN (
                      SELECT id_estudiante, COUNT(*) AS total_materias
                      FROM matriculas
                      WHERE activo = 1
                      GROUP BY id_estudiante
                  ) m ON e.id_estudiante = m.id_estudiante
                  WHERE e.activo = 1
                  ORDER BY g.nombre ASC, e.apellido ASC, e.nombre ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function leerDesactivados() {
        $query = "SELECT 
                    e.id_estudiante,
                    e.nombre,
                    e.apellido,
                    e.cedula_identidad,
                    e.user_id_global,
                    e.id_grado,
                    g.nombre AS grado,
                    COALESCE(m.total_materias, 0) AS materias_matriculadas,
                    e.activo,
                    CONCAT(e.nombre, ' ', e.apellido, ' - CI: ', e.cedula_identidad) AS nombre_completo
                  FROM estudiantes e
                  LEFT JOIN grados g ON e.id_grado = g.id_grado
                  LEFT JOIN (
                      SELECT id_estudiante, COUNT(*) AS total_materias
                      FROM matriculas
                      WHERE activo = 1
                      GROUP BY id_estudiante
                  ) m ON e.id_estudiante = m.id_estudiante
                  WHERE e.activo = 0
                  ORDER BY e.nombre ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function actualizar() {
        if (empty($this->id_estudiante)) return false;

        $campos = [
            "nombre=:nombre",
            "apellido=:apellido",
            "cedula_identidad=:cedula"
        ];

        $params = [
            ":nombre" => $this->nombre,
            ":apellido" => $this->apellido,
            ":cedula" => $this->cedula_identidad,
            ":id" => $this->id_estudiante
        ];

        if (!empty($this->user_id_global)) {
            $campos[] = "user_id_global=:user_id_global";
            $params[":user_id_global"] = $this->user_id_global;
        }

        if (!empty($this->id_grado)) {
            $campos[] = "id_grado=:id_grado";
            $params[":id_grado"] = $this->id_grado;
        }

        $query = "UPDATE estudiantes
                  SET " . implode(", ", $campos) . "
                  WHERE id_estudiante=:id";

        $stmt = $this->conn->prepare($query);
        $ok = $stmt->execute($params);

        if ($ok && !empty($this->id_grado)) {
            $this->matricularEnMateriasDeGrado((int)$this->id_estudiante, (int)$this->id_grado);
        }

        return $ok;
    }

    public function desactivar() {
        if (empty($this->id_estudiante)) return false;

        $query = "UPDATE estudiantes
                  SET activo=0
                  WHERE id_estudiante=:id";

        $stmt = $this->conn->prepare($query);
        return $stmt->execute([":id" => $this->id_estudiante]);
    }

    public function restaurar() {
        if (empty($this->id_estudiante)) return false;

        $query = "UPDATE estudiantes
                  SET activo=1
                  WHERE id_estudiante=:id";

        $stmt = $this->conn->prepare($query);
        return $stmt->execute([":id" => $this->id_estudiante]);
    }

    public function eliminar() {
        if (empty($this->id_estudiante)) return false;

        $query = "DELETE FROM estudiantes WHERE id_estudiante=:id";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([":id" => $this->id_estudiante]);
    }
}
