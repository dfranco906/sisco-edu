<?php
declare(strict_types=1);

class Asignacion {
    private PDO $conn;
    private string $table_name = "asignacion_docente";

    public ?int $id_profesor = null;
    public ?int $id_materia = null;
    public ?int $id_grado = null;
    public ?int $año_lectivo = null;
    public ?int $id_asignacion = null;

    public function __construct(PDO $db) {
        $this->conn = $db;
    }

    public function crear(): bool {
        $query = "INSERT INTO {$this->table_name} (id_profesor, id_materia, id_grado, anho_lectivo)
                  VALUES (:id_p, :id_m, :id_g, :anio)";

        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(":id_p", $this->id_profesor);
        $stmt->bindValue(":id_m", $this->id_materia);
        $stmt->bindValue(":id_g", $this->id_grado);
        $stmt->bindValue(":anio", $this->año_lectivo ?? (int)date('Y'));

        return $stmt->execute();
    }

    public function leer(?int $id_profesor = null): PDOStatement {
        $query = "SELECT
                    ad.id_asignacion,
                    ad.id_profesor,
                    ad.id_materia,
                    ad.id_grado,
                    CONCAT(p.nombre, ' ', p.apellido) AS profesor,
                    m.nombre AS materia,
                    g.nombre AS grado,
                    COALESCE(ad.anho_lectivo, ad.anio_lectivo, 2026) AS anho_lectivo,
                    COALESCE(ad.anho_lectivo, ad.anio_lectivo, 2026) AS \"año_lectivo\",
                    CONCAT(COALESCE(g.nombre, 'Sin Grado'), ' - ', m.nombre, ' (', p.nombre, ' ', p.apellido, ')') AS descripcion
                  FROM {$this->table_name} ad
                  LEFT JOIN profesores p ON ad.id_profesor = p.id_profesor
                  LEFT JOIN materias m ON ad.id_materia = m.id_materia
                  LEFT JOIN grados g ON ad.id_grado = g.id_grado
                  WHERE ad.activo = 1";

        $params = [];
        if ($id_profesor !== null) {
            $query .= " AND ad.id_profesor = :id_profesor ORDER BY g.nombre ASC, m.nombre ASC";
            $params[':id_profesor'] = $id_profesor;
        } else {
            $query .= " ORDER BY ad.id_asignacion DESC";
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
                    CONCAT(p.nombre, ' ', p.apellido) AS profesor,
                    m.nombre AS materia,
                    g.nombre AS grado,
                    COALESCE(ad.anho_lectivo, ad.anio_lectivo, 2026) AS anho_lectivo,
                    COALESCE(ad.anho_lectivo, ad.anio_lectivo, 2026) AS \"año_lectivo\",
                    ad.activo,
                    CONCAT(COALESCE(g.nombre, 'Sin Grado'), ' - ', m.nombre, ' (', p.nombre, ' ', p.apellido, ')') AS descripcion
                  FROM {$this->table_name} ad
                  LEFT JOIN profesores p ON ad.id_profesor = p.id_profesor
                  LEFT JOIN materias m ON ad.id_materia = m.id_materia
                  LEFT JOIN grados g ON ad.id_grado = g.id_grado
                  WHERE ad.activo = 0
                  ORDER BY ad.id_asignacion DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function actualizar(): bool {
        $query = "UPDATE {$this->table_name}
                  SET id_profesor = :id_profesor,
                      id_materia = :id_materia,
                      id_grado = :id_grado,
                      anho_lectivo = :anio
                  WHERE id_asignacion = :id_asignacion";

        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(":id_profesor", $this->id_profesor);
        $stmt->bindValue(":id_materia", $this->id_materia);
        $stmt->bindValue(":id_grado", $this->id_grado);
        $stmt->bindValue(":anio", $this->año_lectivo ?? (int)date('Y'));
        $stmt->bindValue(":id_asignacion", $this->id_asignacion);

        return $stmt->execute();
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
