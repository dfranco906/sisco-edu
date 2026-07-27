<?php
class HuellaTemplate {
    private $conn;
    private $table_name = "huellas_templates";

    public $id_huella;
    public $user_id_global;
    public $fingerprint_data;
    public $formato;
    public $slot_index;
    public $pendiente_sync;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function crear() {
        $query = "INSERT INTO " . $this->table_name . "
                  SET user_id_global=:user_id_global,
                      fingerprint_data=:fingerprint_data,
                      formato=:formato,
                      slot_index=:slot_index,
                      pendiente_sync=1";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":user_id_global", $this->user_id_global);
        $stmt->bindParam(":fingerprint_data", $this->fingerprint_data);
        $stmt->bindParam(":formato", $this->formato);
        $stmt->bindParam(":slot_index", $this->slot_index);

        return $stmt->execute();
    }

    public function leer() {
        $query = "SELECT * FROM " . $this->table_name . "
                  WHERE activo = 1
                  ORDER BY id_huella DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt;
    }

    public function actualizar() {
        $query = "UPDATE " . $this->table_name . "
                  SET user_id_global=:user_id_global,
                      fingerprint_data=:fingerprint_data,
                      formato=:formato,
                      slot_index=:slot_index,
                      pendiente_sync=:pendiente_sync
                  WHERE id_huella=:id";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":user_id_global", $this->user_id_global);
        $stmt->bindParam(":fingerprint_data", $this->fingerprint_data);
        $stmt->bindParam(":formato", $this->formato);
        $stmt->bindParam(":slot_index", $this->slot_index);
        $stmt->bindParam(":pendiente_sync", $this->pendiente_sync);
        $stmt->bindParam(":id", $this->id_huella);

        return $stmt->execute();
    }

    public function desactivar() {
        $query = "UPDATE " . $this->table_name . "
                  SET activo = 0
                  WHERE id_huella=:id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id_huella);

        return $stmt->execute();
    }
}
?>