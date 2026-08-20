<?php
class Asignacion
{
    private $conn;
    private $table_name = "asignacion_docente";
    public $id_profesor;
    public $id_materia;
    public $id_grado;
    public $carga_horaria;
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
            INNER JOIN grados g ON g.id_grado = :id_grado AND g.activo = 1
            INNER JOIN aulas a ON a.id_aula = g.id_aula AND a.activo = 1
            WHERE p.id_profesor = :id_profesor
              AND p.activo = 1
        ");
        $stmt->execute([
            ":id_profesor" => $this->id_profesor,
            ":id_materia" => $this->id_materia,
            ":id_grado" => $this->id_grado
        ]);

        return (int) $stmt->fetchColumn() === 1;
    }

    public function existeDuplicada($excluirId = null)
    {
        $query = "SELECT id_asignacion
                  FROM {$this->table_name}
                  WHERE id_profesor = :id_profesor
                    AND id_materia = :id_materia
                    AND id_grado = :id_grado
                    AND anio_lectivo = :anio
                    AND activo = 1";
        $params = [
            ":id_profesor" => $this->id_profesor,
            ":id_materia" => $this->id_materia,
            ":id_grado" => $this->id_grado,
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
                  (id_profesor, id_materia, id_grado, carga_horaria, anio_lectivo, activo)
                  VALUES (:id_profesor, :id_materia, :id_grado, :carga_horaria, :anio, 1)";
        $stmt = $this->conn->prepare($query);

        return $stmt->execute([
            ":id_profesor" => $this->id_profesor,
            ":id_materia" => $this->id_materia,
            ":id_grado" => $this->id_grado,
            ":carga_horaria" => $this->carga_horaria,
            ":anio" => $this->anio_lectivo
        ]);
    }

    private function consultaListado($activo)
    {
        $query = "SELECT
                    ad.id_asignacion,
                    ad.id_profesor,
                    ad.id_materia,
                    ad.id_grado,
                    CONCAT(p.nombre, ' ', p.apellido) AS profesor,
                    m.nombre AS materia,
                    g.nombre AS grado,
                    g.id_aula,
                    a.nombre AS aula,
                    a.codigo AS codigo_aula,
                    ad.carga_horaria,
                    ad.anio_lectivo,
                    ad.activo,
                    COALESCE(p.activo, 0) AS profesor_activo,
                    COALESCE(m.activo, 0) AS materia_activa,
                    COALESCE(g.activo, 0) AS grado_activo,
                    COALESCE(a.activo, 0) AS aula_activa,
                    CONCAT(
                        COALESCE(CONCAT(p.nombre, ' ', p.apellido), 'Profesor no disponible'),
                        ' - ',
                        COALESCE(m.nombre, 'Materia no disponible'),
                        ' - ',
                        COALESCE(g.nombre, 'Grado no disponible'),
                        ' - ',
                        ad.anio_lectivo
                    ) AS descripcion
                  FROM asignacion_docente ad
                  LEFT JOIN profesores p ON ad.id_profesor = p.id_profesor
                  LEFT JOIN materias m ON ad.id_materia = m.id_materia
                  LEFT JOIN grados g ON ad.id_grado = g.id_grado
                  LEFT JOIN aulas a ON g.id_aula = a.id_aula
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
                    ad.id_grado,
                    CONCAT(p.nombre, ' ', p.apellido) AS profesor,
                    m.nombre AS materia,
                    g.nombre AS grado,
                    g.id_aula,
                    a.nombre AS aula,
                    a.codigo AS codigo_aula,
                    ad.carga_horaria,
                    ad.anio_lectivo,
                    ad.activo,
                    1 AS profesor_activo,
                    1 AS materia_activa,
                    1 AS grado_activo,
                    1 AS aula_activa,
                    CONCAT(p.nombre, ' ', p.apellido, ' - ', m.nombre, ' - ', g.nombre, ' - ', ad.anio_lectivo) AS descripcion
                  FROM asignacion_docente ad
                  INNER JOIN profesores p ON ad.id_profesor = p.id_profesor AND p.activo = 1
                  INNER JOIN materias m ON ad.id_materia = m.id_materia AND m.activo = 1
                  INNER JOIN grados g ON ad.id_grado = g.id_grado AND g.activo = 1
                  INNER JOIN aulas a ON g.id_aula = a.id_aula AND a.activo = 1
                  WHERE ad.activo = 1
                  ORDER BY g.nombre, p.nombre, p.apellido, m.nombre, ad.id_asignacion";
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
                      id_grado = :id_grado,
                      carga_horaria = :carga_horaria,
                      anio_lectivo = :anio
                  WHERE id_asignacion = :id_asignacion";
        $stmt = $this->conn->prepare($query);

        return $stmt->execute([
            ":id_profesor" => $this->id_profesor,
            ":id_materia" => $this->id_materia,
            ":id_grado" => $this->id_grado,
            ":carga_horaria" => $this->carga_horaria,
            ":anio" => $this->anio_lectivo,
            ":id_asignacion" => $this->id_asignacion
        ]);
    }

    public function gradoPuedeCambiar()
    {
        $stmt = $this->conn->prepare("
            SELECT COUNT(*)
            FROM horarios
            WHERE id_asignacion = :id_asignacion
              AND (id_grado IS NULL OR id_grado <> :id_grado)
        ");
        $stmt->execute([
            ":id_asignacion" => $this->id_asignacion,
            ":id_grado" => $this->id_grado
        ]);

        return (int) $stmt->fetchColumn() === 0;
    }

    public function cargarPorId()
    {
        $stmt = $this->conn->prepare("
            SELECT id_profesor, id_materia, id_grado, carga_horaria, anio_lectivo
            FROM {$this->table_name}
            WHERE id_asignacion = :id
            LIMIT 1
        ");
        $stmt->execute([":id" => $this->id_asignacion]);
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$fila) return false;

        $this->id_profesor = $fila['id_profesor'];
        $this->id_materia = $fila['id_materia'];
        $this->id_grado = $fila['id_grado'];
        $this->carga_horaria = $fila['carga_horaria'];
        $this->anio_lectivo = $fila['anio_lectivo'];
        return true;
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
