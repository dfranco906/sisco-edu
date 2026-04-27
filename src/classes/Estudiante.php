<?php
class Estudiante {
    private $conn;
    private $table_name = "estudiantes";

    public $nombre;
    public $apellido;
    public $cedula_identidad;
    public $huella_id;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function crear() {
        $query = "INSERT INTO " . $this->table_name . "
                  SET nombre=:nombre,
                      apellido=:apellido,
                      cedula_identidad=:cedula,
                      huella_id=:huella";

        $stmt = $this->conn->prepare($query);

        $this->nombre = htmlspecialchars(strip_tags($this->nombre));
        $this->apellido = htmlspecialchars(strip_tags($this->apellido));
        $this->cedula_identidad = htmlspecialchars(strip_tags($this->cedula_identidad));
        $this->huella_id = !empty($this->huella_id) ? $this->huella_id : NULL;

        $stmt->bindParam(":nombre", $this->nombre);
        $stmt->bindParam(":apellido", $this->apellido);
        $stmt->bindParam(":cedula", $this->cedula_identidad);
        $stmt->bindParam(":huella", $this->huella_id);

        return $stmt->execute();
    }
    public function leer() {
        $query = "SELECT * FROM " . $this->table_name . " 
                  ORDER BY id_estudiante DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt;
    }
    public $id_estudiante;

public function actualizar() {
    $query = "UPDATE estudiantes 
              SET nombre=:nombre,
                  apellido=:apellido,
                  cedula_identidad=:cedula,
                  huella_id=:huella
              WHERE id_estudiante=:id";

    $stmt = $this->conn->prepare($query);

    $this->nombre = htmlspecialchars(strip_tags($this->nombre));
    $this->apellido = htmlspecialchars(strip_tags($this->apellido));
    $this->cedula_identidad = htmlspecialchars(strip_tags($this->cedula_identidad));
    $this->huella_id = !empty($this->huella_id) ? $this->huella_id : NULL;

    $stmt->bindParam(":nombre", $this->nombre);
    $stmt->bindParam(":apellido", $this->apellido);
    $stmt->bindParam(":cedula", $this->cedula_identidad);
    $stmt->bindParam(":huella", $this->huella_id);
    $stmt->bindParam(":id", $this->id_estudiante);

    return $stmt->execute();
}
}
?>