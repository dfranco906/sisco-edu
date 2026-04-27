<?php
// src/classes/Horario.php

class Horario {
    private $conn;
    private $table_name = "horarios";

    // Propiedades según tu tabla SQL
    public $id_asignacion;
    public $dia_semana;
    public $hora_inicio;
    public $hora_fin;
    public $aula;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function crear() {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET id_asignacion=:id_asig, 
                      dia_semana=:dia, 
                      hora_inicio=:inicio, 
                      hora_fin=:fin, 
                      aula=:aula";

        $stmt = $this->conn->prepare($query);

        // Sanitización
        $this->dia_semana = htmlspecialchars(strip_tags($this->dia_semana));
        $this->hora_inicio = htmlspecialchars(strip_tags($this->hora_inicio));
        $this->hora_fin = htmlspecialchars(strip_tags($this->hora_fin));
        $this->aula = htmlspecialchars(strip_tags($this->aula));

        // Binding
        $stmt->bindParam(":id_asig", $this->id_asignacion);
        $stmt->bindParam(":dia", $this->dia_semana);
        $stmt->bindParam(":inicio", $this->hora_inicio);
        $stmt->bindParam(":fin", $this->hora_fin);
        $stmt->bindParam(":aula", $this->aula);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }
    public function leer() {
    $query = "SELECT h.*, m.nombre as materia, p.nombre as profesor
              FROM horarios h
              INNER JOIN asignacion_docente a ON h.id_asignacion = a.id_asignacion
              INNER JOIN materias m ON a.id_materia = m.id_materia
              INNER JOIN profesores p ON a.id_profesor = p.id_profesor
              ORDER BY h.id_horario DESC";

    $stmt = $this->conn->prepare($query);
    $stmt->execute();
    return $stmt;
}
}

?>