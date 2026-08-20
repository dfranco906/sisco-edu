<?php
// src/classes/Materia.php

class Materia {
    private $conn;
    private $table_name = "materias";

    public $id_materia;
    public $nombre;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function crear() {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET nombre=:nombre";

        $stmt = $this->conn->prepare($query);

        $this->nombre = htmlspecialchars(strip_tags($this->nombre));

        $stmt->bindParam(":nombre", $this->nombre);

        return $stmt->execute();
    }
    public function leer() {
   $query = "SELECT *
          FROM " . $this->table_name . "
          WHERE activo = 1
          ORDER BY id_materia DESC";
    $stmt = $this->conn->prepare($query);
    $stmt->execute();
    return $stmt;
}
public function leerDesactivadas() {
    $query = "SELECT *
          FROM " . $this->table_name . "
          WHERE activo = 0
          ORDER BY id_materia DESC";
    $stmt = $this->conn->prepare($query);
    $stmt->execute();
    return $stmt;
}
public function actualizar() {
    $query = "UPDATE " . $this->table_name . "
              SET nombre=:nombre
              WHERE id_materia=:id";

    $stmt = $this->conn->prepare($query);

    $stmt->bindParam(":nombre", $this->nombre);
    $stmt->bindParam(":id", $this->id_materia);

    return $stmt->execute();
}
public function desactivar() {
    $query = "UPDATE " . $this->table_name . "
              SET activo = 0
              WHERE id_materia = :id";

    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(":id", $this->id_materia);

    return $stmt->execute();
}
public function restaurar() {
    $query = "UPDATE " . $this->table_name . "
              SET activo = 1
              WHERE id_materia = :id";

    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(":id", $this->id_materia);

    return $stmt->execute();
}
public function contarDependencias() {
    $query = "SELECT COUNT(*) FROM asignacion_docente WHERE id_materia = :id";
    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(":id", $this->id_materia);
    $stmt->execute();

    return (int) $stmt->fetchColumn();
}
public function eliminar() {
    $query = "DELETE FROM " . $this->table_name . "
              WHERE id_materia = :id";

    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(":id", $this->id_materia);
    $stmt->execute();

    return $stmt->rowCount() > 0;
}
}
