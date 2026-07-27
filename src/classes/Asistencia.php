<?php
class Asistencia {
    private $conn;
    private $table_name = "asistencias";

    public $user_id_global;
    public $id_aula;
    public $fecha_hora;
    public $tipo_usuario;
    public $estado;
    public $id_asistencia;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function crear() {
        $query = "INSERT INTO " . $this->table_name . "
                  SET user_id_global=:user_id_global,
                      id_aula=:id_aula,
                      fecha_hora=:fecha_hora,
                      tipo_usuario=:tipo,
                      estado=:estado";

        $stmt = $this->conn->prepare($query);

        $this->tipo_usuario = htmlspecialchars(strip_tags($this->tipo_usuario));
        $this->estado = htmlspecialchars(strip_tags($this->estado));

        $stmt->bindParam(":user_id_global", $this->user_id_global);
        $stmt->bindParam(":id_aula", $this->id_aula);
        $stmt->bindParam(":fecha_hora", $this->fecha_hora);
        $stmt->bindParam(":tipo", $this->tipo_usuario);
        $stmt->bindParam(":estado", $this->estado);

        return $stmt->execute();
    }

    public function leer() {
        $query = "SELECT id_asistencia, user_id_global, id_aula, fecha_hora, tipo_usuario, estado, activo
                  FROM " . $this->table_name . "
                  WHERE activo = 1
                  ORDER BY id_asistencia DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt;
    }

    public function actualizar() {
        $query = "UPDATE " . $this->table_name . "
                  SET user_id_global=:user_id_global,
                      id_aula=:id_aula,
                      fecha_hora=:fecha_hora,
                      tipo_usuario=:tipo,
                      estado=:estado
                  WHERE id_asistencia=:id";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":user_id_global", $this->user_id_global);
        $stmt->bindParam(":id_aula", $this->id_aula);
        $stmt->bindParam(":fecha_hora", $this->fecha_hora);
        $stmt->bindParam(":tipo", $this->tipo_usuario);
        $stmt->bindParam(":estado", $this->estado);
        $stmt->bindParam(":id", $this->id_asistencia);

        return $stmt->execute();
    }

    public function desactivar() {
        $query = "UPDATE " . $this->table_name . "
                  SET activo = 0
                  WHERE id_asistencia = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id_asistencia);

        return $stmt->execute();
    }
}
?>