<?php
class SyncBiometrica {
    private $conn;
    private $table_name = "sync_biometrica";

    public $id_sync;
    public $id_huella;
    public $room_id;
    public $estado;
    public $intentos;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function crear() {
        $query = "INSERT INTO " . $this->table_name . "
                  SET id_huella=:id_huella,
                      room_id=:room_id,
                      estado=:estado,
                      intentos=:intentos";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":id_huella", $this->id_huella);
        $stmt->bindParam(":room_id", $this->room_id);
        $stmt->bindParam(":estado", $this->estado);
        $stmt->bindParam(":intentos", $this->intentos);

        return $stmt->execute();
    }

    public function leer() {
        $query = "SELECT * FROM " . $this->table_name . "
                  ORDER BY id_sync DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt;
    }

    public function actualizar() {
        $query = "UPDATE " . $this->table_name . "
                  SET id_huella=:id_huella,
                      room_id=:room_id,
                      estado=:estado,
                      intentos=:intentos,
                      fecha_actualizacion=NOW()
                  WHERE id_sync=:id";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":id_huella", $this->id_huella);
        $stmt->bindParam(":room_id", $this->room_id);
        $stmt->bindParam(":estado", $this->estado);
        $stmt->bindParam(":intentos", $this->intentos);
        $stmt->bindParam(":id", $this->id_sync);

        return $stmt->execute();
    }

    public function confirmar() {
        $query = "UPDATE " . $this->table_name . "
                  SET estado='CONFIRMADO',
                      fecha_actualizacion=NOW()
                  WHERE id_sync=:id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id_sync);

        return $stmt->execute();
    }
    public function marcarError() {
    $query = "UPDATE " . $this->table_name . "
              SET estado='ERROR',
                  intentos = intentos + 1,
                  fecha_actualizacion=NOW()
              WHERE id_sync=:id";

    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(":id", $this->id_sync);

    return $stmt->execute();
}
}
?>