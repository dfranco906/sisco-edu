<?php
class Asistencia {
    private $conn;
    private $table_name = "asistencias";

    public $huella_id;
    public $tipo_usuario;
    public $estado;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function crear() {
        $query = "INSERT INTO " . $this->table_name . "
                  SET huella_id=:huella,
                      tipo_usuario=:tipo,
                      estado=:estado";

        $stmt = $this->conn->prepare($query);

        $this->tipo_usuario = htmlspecialchars(strip_tags($this->tipo_usuario));
        $this->estado = htmlspecialchars(strip_tags($this->estado));

        $stmt->bindParam(":huella", $this->huella_id);
        $stmt->bindParam(":tipo", $this->tipo_usuario);
        $stmt->bindParam(":estado", $this->estado);

        return $stmt->execute();
    }

    // READ
    public function leer() {
        $query = "SELECT * FROM " . $this->table_name . " 
                  ORDER BY id_asistencia DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt;
    }
}
?>