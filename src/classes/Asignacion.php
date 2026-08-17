<?php
class Asignacion {
    private $conn;
    private $table_name = "asignacion_docente";
    private $col_anio = "año_lectivo";

    public $id_profesor;
    public $id_materia;
    public $aÃ±o_lectivo;
    public $id_asignacion;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function crear() {
        $query = "INSERT INTO " . $this->table_name . "
                  SET id_profesor=:id_p,
                      id_materia=:id_m,
                      `" . $this->col_anio . "`=:anio";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":id_p", $this->id_profesor);
        $stmt->bindParam(":id_m", $this->id_materia);
        $stmt->bindParam(":anio", $this->aÃ±o_lectivo);

        return $stmt->execute();
    }

    public function leer() {
        $query = "SELECT
                    ad.id_asignacion,
                    CONCAT(p.nombre, ' ', p.apellido) AS profesor,
                    m.nombre AS materia,
                    ad.`" . $this->col_anio . "` AS `aÃ±o_lectivo`,
                    CONCAT(p.nombre, ' ', p.apellido, ' - ', m.nombre, ' - ', ad.`" . $this->col_anio . "`) AS descripcion
                  FROM asignacion_docente ad
                  LEFT JOIN profesores p ON ad.id_profesor = p.id_profesor
                  LEFT JOIN materias m ON ad.id_materia = m.id_materia
                  WHERE ad.activo = 1
                  ORDER BY ad.id_asignacion DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function leerDesactivadas() {
        $query = "SELECT
                    ad.id_asignacion,
                    ad.id_profesor,
                    ad.id_materia,
                    CONCAT(p.nombre, ' ', p.apellido) AS profesor,
                    m.nombre AS materia,
                    ad.`" . $this->col_anio . "` AS `aÃ±o_lectivo`,
                    ad.activo,
                    CONCAT(p.nombre, ' ', p.apellido, ' - ', m.nombre, ' - ', ad.`" . $this->col_anio . "`) AS descripcion
                  FROM asignacion_docente ad
                  LEFT JOIN profesores p ON ad.id_profesor = p.id_profesor
                  LEFT JOIN materias m ON ad.id_materia = m.id_materia
                  WHERE ad.activo = 0
                  ORDER BY ad.id_asignacion DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function actualizar() {
        $query = "UPDATE " . $this->table_name . "
                  SET id_profesor=:id_profesor,
                      id_materia=:id_materia,
                      `" . $this->col_anio . "`=:anio
                  WHERE id_asignacion=:id_asignacion";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":id_profesor", $this->id_profesor);
        $stmt->bindParam(":id_materia", $this->id_materia);
        $stmt->bindParam(":anio", $this->aÃ±o_lectivo);
        $stmt->bindParam(":id_asignacion", $this->id_asignacion);

        return $stmt->execute();
    }

    public function desactivar() {
        $query = "UPDATE " . $this->table_name . "
                  SET activo = 0
                  WHERE id_asignacion = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id_asignacion);

        return $stmt->execute();
    }

    public function restaurar() {
        $query = "UPDATE " . $this->table_name . "
                  SET activo = 1
                  WHERE id_asignacion = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id_asignacion);

        return $stmt->execute();
    }

    public function contarDependencias() {
        $query = "SELECT COUNT(*) FROM horarios WHERE id_asignacion = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id_asignacion);
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }

    public function eliminar() {
        $query = "DELETE FROM " . $this->table_name . "
                  WHERE id_asignacion = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id_asignacion);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }
}
?>