<?php
class AsistenciaEstudiante {
    private $conn;
    private $table_name = "asistencias_estudiantes";

    public $id_asistencia_estudiante;
    public $id_estudiante;
    public $huella_id;
    public $fecha;
    public $hora;
    public $estado;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function crear() {
        $query = "INSERT INTO {$this->table_name}
                  SET id_estudiante=:id_estudiante,
                      huella_id=:huella_id,
                      fecha=:fecha,
                      hora=:hora,
                      estado=:estado";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":id_estudiante", $this->id_estudiante);
        $stmt->bindParam(":huella_id", $this->huella_id);
        $stmt->bindParam(":fecha", $this->fecha);
        $stmt->bindParam(":hora", $this->hora);
        $stmt->bindParam(":estado", $this->estado);

        return $stmt->execute();
    }

    public function leer() {
        $query = "SELECT ae.*, e.nombre, e.apellido, g.nombre AS grado
                  FROM {$this->table_name} ae
                  INNER JOIN estudiantes e ON ae.id_estudiante = e.id_estudiante
                  LEFT JOIN grados g ON e.id_grado = g.id_grado
                  WHERE ae.activo = 1
                  ORDER BY ae.id_asistencia_estudiante DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function leerDesactivados() {
        $query = "SELECT ae.*, e.nombre, e.apellido, g.nombre AS grado
                  FROM {$this->table_name} ae
                  INNER JOIN estudiantes e ON ae.id_estudiante = e.id_estudiante
                  LEFT JOIN grados g ON e.id_grado = g.id_grado
                  WHERE ae.activo = 0
                  ORDER BY ae.id_asistencia_estudiante DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function actualizar() {
        $query = "UPDATE {$this->table_name}
                  SET id_estudiante=:id_estudiante,
                      huella_id=:huella_id,
                      fecha=:fecha,
                      hora=:hora,
                      estado=:estado
                  WHERE id_asistencia_estudiante=:id";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":id_estudiante", $this->id_estudiante);
        $stmt->bindParam(":huella_id", $this->huella_id);
        $stmt->bindParam(":fecha", $this->fecha);
        $stmt->bindParam(":hora", $this->hora);
        $stmt->bindParam(":estado", $this->estado);
        $stmt->bindParam(":id", $this->id_asistencia_estudiante);

        return $stmt->execute();
    }

    public function desactivar() {
        $query = "UPDATE {$this->table_name}
                  SET activo = 0
                  WHERE id_asistencia_estudiante=:id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id_asistencia_estudiante);

        return $stmt->execute();
    }

    public function restaurar() {
        $query = "UPDATE {$this->table_name}
                  SET activo = 1
                  WHERE id_asistencia_estudiante=:id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id_asistencia_estudiante);

        return $stmt->execute();
    }

    public function eliminar() {
        $query = "DELETE FROM {$this->table_name}
                  WHERE id_asistencia_estudiante=:id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id_asistencia_estudiante);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }
}
?>
