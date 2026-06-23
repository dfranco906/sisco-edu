<?php
class AsistenciaProfesor {
    private $conn;
    private $table_name = "asistencias_profesores";

    public $id_asistencia_profesor;
    public $id_profesor;
    public $huella_id;
    public $fecha;
    public $hora;
    public $estado;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function crear() {
        $query = "INSERT INTO {$this->table_name}
                  SET id_profesor=:id_profesor,
                      huella_id=:huella_id,
                      fecha=:fecha,
                      hora=:hora,
                      estado=:estado";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":id_profesor", $this->id_profesor);
        $stmt->bindParam(":huella_id", $this->huella_id);
        $stmt->bindParam(":fecha", $this->fecha);
        $stmt->bindParam(":hora", $this->hora);
        $stmt->bindParam(":estado", $this->estado);

        return $stmt->execute();
    }

    public function leer() {
        $query = "SELECT ap.*, p.nombre, p.apellido, CONCAT(p.nombre, ' ', p.apellido) AS profesor, CONCAT(p.nombre, ' ', p.apellido, ' - CI: ', p.cedula_identidad) AS nombre_completo
                  FROM {$this->table_name} ap
                  INNER JOIN profesores p ON ap.id_profesor = p.id_profesor
                  WHERE ap.activo = 1
                  ORDER BY ap.id_asistencia_profesor DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function leerDesactivados() {
        $query = "SELECT ap.*, p.nombre, p.apellido, CONCAT(p.nombre, ' ', p.apellido) AS profesor, CONCAT(p.nombre, ' ', p.apellido, ' - CI: ', p.cedula_identidad) AS nombre_completo
                  FROM {$this->table_name} ap
                  INNER JOIN profesores p ON ap.id_profesor = p.id_profesor
                  WHERE ap.activo = 0
                  ORDER BY ap.id_asistencia_profesor DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function actualizar() {
        $query = "UPDATE {$this->table_name}
                  SET id_profesor=:id_profesor,
                      huella_id=:huella_id,
                      fecha=:fecha,
                      hora=:hora,
                      estado=:estado
                  WHERE id_asistencia_profesor=:id";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":id_profesor", $this->id_profesor);
        $stmt->bindParam(":huella_id", $this->huella_id);
        $stmt->bindParam(":fecha", $this->fecha);
        $stmt->bindParam(":hora", $this->hora);
        $stmt->bindParam(":estado", $this->estado);
        $stmt->bindParam(":id", $this->id_asistencia_profesor);

        return $stmt->execute();
    }

    public function desactivar() {
        $query = "UPDATE {$this->table_name}
                  SET activo = 0
                  WHERE id_asistencia_profesor=:id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id_asistencia_profesor);

        return $stmt->execute();
    }

    public function restaurar() {
        $query = "UPDATE {$this->table_name}
                  SET activo = 1
                  WHERE id_asistencia_profesor=:id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id_asistencia_profesor);

        return $stmt->execute();
    }

    public function eliminar() {
        $query = "DELETE FROM {$this->table_name}
                  WHERE id_asistencia_profesor=:id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id_asistencia_profesor);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }
}
?>
