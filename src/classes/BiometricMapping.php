<?php
class BiometricMapping {
    private $conn;
    private $table_name = "biometric_mapping";

    public $id_mapping;
    public $user_id_global;
    public $room_id;
    public $sensor_slot;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function crear() {
        $query = "INSERT INTO " . $this->table_name . "
                  SET user_id_global=:user_id_global,
                      room_id=:room_id,
                      sensor_slot=:sensor_slot";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":user_id_global", $this->user_id_global);
        $stmt->bindParam(":room_id", $this->room_id);
        $stmt->bindParam(":sensor_slot", $this->sensor_slot);

        return $stmt->execute();
    }

    public function leer() {
        $query = "SELECT * FROM " . $this->table_name . "
                  WHERE activo = 1
                  ORDER BY id_mapping DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt;
    }

    public function actualizar() {
        $query = "UPDATE " . $this->table_name . "
                  SET user_id_global=:user_id_global,
                      room_id=:room_id,
                      sensor_slot=:sensor_slot
                  WHERE id_mapping=:id";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":user_id_global", $this->user_id_global);
        $stmt->bindParam(":room_id", $this->room_id);
        $stmt->bindParam(":sensor_slot", $this->sensor_slot);
        $stmt->bindParam(":id", $this->id_mapping);

        return $stmt->execute();
    }

    public function desactivar() {
        $query = "UPDATE " . $this->table_name . "
                  SET activo = 0
                  WHERE id_mapping=:id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id_mapping);

        return $stmt->execute();
    }
}
?>