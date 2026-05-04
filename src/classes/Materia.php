<?php
// src/classes/Materia.php

class Materia {
    private $conn;
    private $table_name = "materias";

    public $id_materia;
    public $nombre;
    public $descripcion;
    public $carga_horaria_semanal;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function crear() {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET nombre=:nombre, descripcion=:descripcion, carga_horaria_semanal=:carga";

        $stmt = $this->conn->prepare($query);

        $this->nombre = htmlspecialchars(strip_tags($this->nombre));
        $this->descripcion = htmlspecialchars(strip_tags($this->descripcion));
        $this->carga_horaria_semanal = (int)$this->carga_horaria_semanal;

        $stmt->bindParam(":nombre", $this->nombre);
        $stmt->bindParam(":descripcion", $this->descripcion);
        $stmt->bindParam(":carga", $this->carga_horaria_semanal);

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
public function actualizar() {
    $query = "UPDATE " . $this->table_name . "
              SET nombre=:nombre,
                  descripcion=:descripcion,
                  carga_horaria_semanal=:carga
              WHERE id_materia=:id";

    $stmt = $this->conn->prepare($query);

    $stmt->bindParam(":nombre", $this->nombre);
    $stmt->bindParam(":descripcion", $this->descripcion);
    $stmt->bindParam(":carga", $this->carga_horaria_semanal);
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
}
