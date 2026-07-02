<?php
class NodoEsp32 {
    private $conn;
    private $table_name = "nodos_esp32";

    public $id_nodo;
    public $node_id;
    public $room_id;
    public $tipo;
    public $estado;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function crear() {
        $query = "INSERT INTO " . $this->table_name . "
                  SET node_id=:node_id, room_id=:room_id, tipo=:tipo";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":node_id", $this->node_id);
        $stmt->bindParam(":room_id", $this->room_id);
        $stmt->bindParam(":tipo", $this->tipo);

        return $stmt->execute();
    }

    public function leer() {
        $query = "SELECT * FROM " . $this->table_name . "
                  WHERE activo = 1
                  ORDER BY id_nodo DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt;
    }

    public function actualizar() {
        $query = "UPDATE " . $this->table_name . "
                  SET node_id=:node_id,
                      room_id=:room_id,
                      tipo=:tipo,
                      estado=:estado
                  WHERE id_nodo=:id";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":node_id", $this->node_id);
        $stmt->bindParam(":room_id", $this->room_id);
        $stmt->bindParam(":tipo", $this->tipo);
        $stmt->bindParam(":estado", $this->estado);
        $stmt->bindParam(":id", $this->id_nodo);

        return $stmt->execute();
    }

    public function desactivar() {
        $query = "UPDATE " . $this->table_name . "
                  SET activo = 0
                  WHERE id_nodo=:id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id_nodo);

        return $stmt->execute();
    }

    public function heartbeat() {
        $query = "UPDATE " . $this->table_name . "
                  SET estado='online',
                      ultimo_heartbeat=NOW()
                  WHERE node_id=:node_id AND activo=1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":node_id", $this->node_id);

        return $stmt->execute();
    }
}
?>