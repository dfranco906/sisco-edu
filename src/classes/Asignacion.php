<?php
class Asignacion
{
    private $conn;
    private $table_name = "asignacion_docente";
    private $col_anio = "a\u{00F1}o_lectivo";

    public $id_profesor;
    public $id_materia;
    public $anio_lectivo;
    public $id_asignacion;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function relacionesActivas()
    {
        $stmt = $this->conn->prepare("
            SELECT COUNT(*)
            FROM profesores p
            INNER JOIN materias m ON m.id_materia = :id_materia AND m.activo = 1
            WHERE p.id_profesor = :id_profesor
              AND p.activo = 1
        ");
        $stmt->execute([
            ":id_profesor" => $this->id_profesor,
            ":id_materia" => $this->id_materia
        ]);

        return (int) $stmt->fetchColumn() === 1;
    }

    public function existeDuplicada($excluirId = null)
    {
        $query = "SELECT id_asignacion
                  FROM {$this->table_name}
                  WHERE id_profesor = :id_profesor
                    AND id_materia = :id_materia
                    AND `{$this->col_anio}` = :anio
                    AND activo = 1";
        $params = [
            ":id_profesor" => $this->id_profesor,
            ":id_materia" => $this->id_materia,
            ":anio" => $this->anio_lectivo
        ];

        if ($excluirId !== null) {
            $query .= " AND id_asignacion <> :excluir_id";
            $params[":excluir_id"] = $excluirId;
        }

        $query .= " LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);

        return $stmt->fetchColumn() !== false;
    }

    public function crear()
    {
        $query = "INSERT INTO {$this->table_name}
                  (id_profesor, id_materia, `{$this->col_anio}`, activo)
                  VALUES (:id_profesor, :id_materia, :anio, 1)";
        $stmt = $this->conn->prepare($query);

        return $stmt->execute([
            ":id_profesor" => $this->id_profesor,
            ":id_materia" => $this->id_materia,
            ":anio" => $this->anio_lectivo
        ]);
    }

    private function consultaListado($activo)
    {
        $query = "SELECT
                    ad.id_asignacion,
                    ad.id_profesor,
                    ad.id_materia,
                    CONCAT(p.nombre, ' ', p.apellido) AS profesor,
                    m.nombre AS materia,
                    ad.`{$this->col_anio}` AS anio_lectivo,
                    ad.activo,
                    COALESCE(p.activo, 0) AS profesor_activo,
                    COALESCE(m.activo, 0) AS materia_activa,
                    CONCAT(
                        COALESCE(CONCAT(p.nombre, ' ', p.apellido), 'Profesor no disponible'),
                        ' - ',
                        COALESCE(m.nombre, 'Materia no disponible'),
                        ' - ',
                        ad.`{$this->col_anio}`
                    ) AS descripcion
                  FROM asignacion_docente ad
                  LEFT JOIN profesores p ON ad.id_profesor = p.id_profesor
                  LEFT JOIN materias m ON ad.id_materia = m.id_materia
                  WHERE ad.activo = :activo
                  ORDER BY ad.id_asignacion DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([":activo" => $activo]);
        return $stmt;
    }

    public function leer()
    {
        return $this->consultaListado(1);
    }

    public function leerOpciones()
    {
        $query = "SELECT
                    ad.id_asignacion,
                    ad.id_profesor,
                    ad.id_materia,
                    CONCAT(p.nombre, ' ', p.apellido) AS profesor,
                    m.nombre AS materia,
                    ad.`{$this->col_anio}` AS anio_lectivo,
                    ad.activo,
                    1 AS profesor_activo,
                    1 AS materia_activa,
                    CONCAT(p.nombre, ' ', p.apellido, ' - ', m.nombre, ' - ', ad.`{$this->col_anio}`) AS descripcion
                  FROM asignacion_docente ad
                  INNER JOIN profesores p ON ad.id_profesor = p.id_profesor AND p.activo = 1
                  INNER JOIN materias m ON ad.id_materia = m.id_materia AND m.activo = 1
                  WHERE ad.activo = 1
                  ORDER BY p.nombre, p.apellido, m.nombre, ad.id_asignacion";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function leerDesactivadas()
    {
        return $this->consultaListado(0);
    }

    public function actualizar()
    {
        $query = "UPDATE {$this->table_name}
                  SET id_profesor = :id_profesor,
                      id_materia = :id_materia,
                      `{$this->col_anio}` = :anio
                  WHERE id_asignacion = :id_asignacion";
        $stmt = $this->conn->prepare($query);

        return $stmt->execute([
            ":id_profesor" => $this->id_profesor,
            ":id_materia" => $this->id_materia,
            ":anio" => $this->anio_lectivo,
            ":id_asignacion" => $this->id_asignacion
        ]);
    }

    public function desactivar()
    {
        $stmt = $this->conn->prepare("
            UPDATE {$this->table_name}
            SET activo = 0
            WHERE id_asignacion = :id
        ");
        return $stmt->execute([":id" => $this->id_asignacion]);
    }

    public function restaurar()
    {
        $stmt = $this->conn->prepare("
            UPDATE {$this->table_name}
            SET activo = 1
            WHERE id_asignacion = :id
        ");
        return $stmt->execute([":id" => $this->id_asignacion]);
    }

    public function contarDependencias()
    {
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) FROM horarios WHERE id_asignacion = :id
        ");
        $stmt->execute([":id" => $this->id_asignacion]);
        return (int) $stmt->fetchColumn();
    }

    public function eliminar()
    {
        $stmt = $this->conn->prepare("
            DELETE FROM {$this->table_name}
            WHERE id_asignacion = :id
        ");
        $stmt->execute([":id" => $this->id_asignacion]);
        return $stmt->rowCount() > 0;
    }
}
?>
