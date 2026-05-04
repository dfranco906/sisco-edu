<?php
// src/classes/Asignacion.php

class Asignacion {
    private $conn;
    private $table_name = "asignacion_docente";

    public $id_profesor;
    public $id_materia;
    public $año_lectivo;
    public $id_asignacion;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function crear() {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET id_profesor=:id_p, id_materia=:id_m, año_lectivo=:anio";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":id_p", $this->id_profesor);
        $stmt->bindParam(":id_m", $this->id_materia);
        $stmt->bindParam(":anio", $this->año_lectivo);

        return $stmt->execute();
    }
    public function leer() {
    $query = "SELECT *
          FROM " . $this->table_name . "
          WHERE activo = 1
          ORDER BY id_asignacion DESC";

    $stmt = $this->conn->prepare($query);
    $stmt->execute();
    return $stmt;
}
public function actualizar() {
    $query = "UPDATE " . $this->table_name . "
              SET id_profesor=:id_profesor,
                  id_materia=:id_materia,
                  año_lectivo=:anio
              WHERE id_asignacion=:id_asignacion";

    $stmt = $this->conn->prepare($query);

    $stmt->bindParam(":id_profesor", $this->id_profesor);
    $stmt->bindParam(":id_materia", $this->id_materia);
    $stmt->bindParam(":anio", $this->año_lectivo);
    $stmt->bindParam(":id_asignacion", $this->id_asignacion);

    return $stmt->execute();
}
public function desactivar() {
    $query = "UPDATE " . $this->table_name . "
              SET activo = 0
              WHERE id_asignacion = :id";

    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(":id", $this->id_asignacion);

    return $stmt->execute();
}
}