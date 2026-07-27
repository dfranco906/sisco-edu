<?php
class EventoAsistencia {
    private $conn;
    private $table_name = "eventos_asistencia";

    public $id_evento;
    public $user_id_global;
    public $id_aula;
    public $timestamp_evento;
    public $origen_node_id;
    public $sincronizado;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function crear() {
        $query = "INSERT INTO " . $this->table_name . "
                  SET user_id_global=:user_id_global,
                      id_aula=:id_aula,
                      timestamp_evento=:timestamp_evento,
                      origen_node_id=:origen_node_id,
                      sincronizado=:sincronizado";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":user_id_global", $this->user_id_global);
        $stmt->bindParam(":id_aula", $this->id_aula);
        $stmt->bindParam(":timestamp_evento", $this->timestamp_evento);
        $stmt->bindParam(":origen_node_id", $this->origen_node_id);
        $stmt->bindParam(":sincronizado", $this->sincronizado);

        return $stmt->execute();
    }

    public function leer() {
        $query = "SELECT * FROM " . $this->table_name . "
                  WHERE activo = 1
                  ORDER BY id_evento DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt;
    }

    public function actualizar() {
        $query = "UPDATE " . $this->table_name . "
                  SET user_id_global=:user_id_global,
                      id_aula=:id_aula,
                      timestamp_evento=:timestamp_evento,
                      origen_node_id=:origen_node_id,
                      sincronizado=:sincronizado
                  WHERE id_evento=:id";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":user_id_global", $this->user_id_global);
        $stmt->bindParam(":id_aula", $this->id_aula);
        $stmt->bindParam(":timestamp_evento", $this->timestamp_evento);
        $stmt->bindParam(":origen_node_id", $this->origen_node_id);
        $stmt->bindParam(":sincronizado", $this->sincronizado);
        $stmt->bindParam(":id", $this->id_evento);

        return $stmt->execute();
    }

    public function desactivar() {
        $query = "UPDATE " . $this->table_name . "
                  SET activo = 0
                  WHERE id_evento=:id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id_evento);

        return $stmt->execute();
    }
}
?>