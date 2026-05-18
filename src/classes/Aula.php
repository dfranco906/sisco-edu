<?php
class Aula {
    private $conn;
    private $table_name = "aulas";

    public $id_aula;
    public $nombre;
    public $codigo;
    public $ubicacion;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function crear() {
        $query = "INSERT INTO " . $this->table_name . "
                  SET nombre=:nombre, codigo=:codigo, ubicacion=:ubicacion";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":nombre", $this->nombre);
        $stmt->bindParam(":codigo", $this->codigo);
        $stmt->bindParam(":ubicacion", $this->ubicacion);

        return $stmt->execute();
    }

    public function leer() {
        $query = "SELECT * FROM " . $this->table_name . "
                  WHERE activo = 1
                  ORDER BY id_aula DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt;
    }

    public function actualizar() {
        $query = "UPDATE " . $this->table_name . "
                  SET nombre=:nombre,
                      codigo=:codigo,
                      ubicacion=:ubicacion
                  WHERE id_aula=:id";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":nombre", $this->nombre);
        $stmt->bindParam(":codigo", $this->codigo);
        $stmt->bindParam(":ubicacion", $this->ubicacion);
        $stmt->bindParam(":id", $this->id_aula);

        return $stmt->execute();
    }

    public function desactivar() {
        $query = "UPDATE " . $this->table_name . "
                  SET activo = 0
                  WHERE id_aula=:id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id_aula);

        return $stmt->execute();
    }
}
?>