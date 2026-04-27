<?php
// src/classes/Asignacion.php

class Asignacion {
    private $conn;
    private $table_name = "asignacion_docente";

    public $id_profesor;
    public $id_materia;
    public $año_lectivo;

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
    $query = "SELECT a.*, 
                     p.nombre as profesor,
                     p.apellido,
                     m.nombre as materia
              FROM asignacion_docente a
              INNER JOIN profesores p ON a.id_profesor = p.id_profesor
              INNER JOIN materias m ON a.id_materia = m.id_materia
              ORDER BY a.id_asignacion DESC";

    $stmt = $this->conn->prepare($query);
    $stmt->execute();
    return $stmt;
}

}