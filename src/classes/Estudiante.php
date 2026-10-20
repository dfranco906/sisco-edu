<?php
declare(strict_types=1);

require_once __DIR__ . '/IdentidadHelper.php';

class Estudiante {
    private PDO $conn;

    public $id_estudiante;
    public $nombre;
    public $apellido;
    public $cedula_identidad;
    public $id_grado;
    public $user_id_global;

    public function __construct(PDO $db) {
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

    public function sincronizarTodasLasMatriculas(?int $idGrado = null): array {
        $sql = "SELECT id_estudiante, id_grado FROM estudiantes WHERE activo = 1 AND id_grado IS NOT NULL";
        $params = [];
        if ($idGrado !== null && $idGrado > 0) {
            $sql .= " AND id_grado = :id_grado";
            $params[':id_grado'] = $idGrado;
        }

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        $estudiantes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $totalEstudiantes = 0;
        $totalMatriculas = 0;

        foreach ($estudiantes as $e) {
            $mats = $this->matricularEnMateriasDeGrado((int)$e['id_estudiante'], (int)$e['id_grado']);
            $totalMatriculas += $mats;
            $totalEstudiantes++;
        }

        return [
            'estudiantes_procesados' => $totalEstudiantes,
            'matriculas_sincronizadas' => $totalMatriculas
        ];
    }

    public function crear(): array {
        $nombre = trim((string)$this->nombre);
        $apellido = trim((string)$this->apellido);
        $cedula = trim((string)$this->cedula_identidad);

        $idUsuario = IdentidadHelper::asegurarUsuario(
            $this->conn,
            'Estudiante',
            $cedula,
            $nombre,
            $apellido
        );

        $query = "INSERT INTO estudiantes (nombre, apellido, cedula_identidad, id_grado, user_id_global, id_usuario, activo)
                  VALUES (:nombre, :apellido, :cedula, :id_grado, :user_id_global, :id_usuario, 1)
                  RETURNING id_estudiante";

        $stmt = $this->conn->prepare($query);

        $ok = $stmt->execute([
            ":nombre" => $nombre,
            ":apellido" => $apellido,
            ":cedula" => $cedula,
            ":id_grado" => !empty($this->id_grado) ? (int)$this->id_grado : null,
            ":user_id_global" => !empty($this->user_id_global) ? $this->user_id_global : ('EST_' . uniqid() . '_' . random_int(100, 999)),
            ":id_usuario" => $idUsuario
        ]);

        $matriculadas = 0;
        if ($ok) {
            $idNuevo = (int)$stmt->fetchColumn();
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

    public function leer(): PDOStatement {
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

    public function leerDesactivados(): PDOStatement {
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

    public function actualizar(): bool {
        if (empty($this->id_estudiante)) return false;

        $idUsuario = IdentidadHelper::asegurarUsuario(
            $this->conn,
            'Estudiante',
            (string)$this->cedula_identidad,
            (string)$this->nombre,
            (string)$this->apellido
        );

        $campos = [
            "nombre=:nombre",
            "apellido=:apellido",
            "cedula_identidad=:cedula",
            "id_usuario = COALESCE(id_usuario, :id_usuario)"
        ];

        $params = [
            ":nombre" => $this->nombre,
            ":apellido" => $this->apellido,
            ":cedula" => $this->cedula_identidad,
            ":id_usuario" => $idUsuario,
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

    public function desactivar(): bool {
        if (empty($this->id_estudiante)) return false;

        $query = "UPDATE estudiantes
                  SET activo=0
                  WHERE id_estudiante=:id";

        $stmt = $this->conn->prepare($query);
        return $stmt->execute([":id" => $this->id_estudiante]);
    }

    public function restaurar(): bool {
        if (empty($this->id_estudiante)) return false;

        $query = "UPDATE estudiantes
                  SET activo=1
                  WHERE id_estudiante=:id";

        $stmt = $this->conn->prepare($query);
        return $stmt->execute([":id" => $this->id_estudiante]);
    }

    public function eliminar(): bool {
        if (empty($this->id_estudiante)) return false;

        $query = "DELETE FROM estudiantes WHERE id_estudiante=:id";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([":id" => $this->id_estudiante]);
    }
}
