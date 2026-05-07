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
    public $id_horario;
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
   $query = "SELECT *
          FROM " . $this->table_name . "
          WHERE activo = 1
          ORDER BY id_horario DESC";

    $stmt = $this->conn->prepare($query);
    $stmt->execute();
    return $stmt;
}
public function actualizar() {
    $query = "UPDATE " . $this->table_name . "
              SET id_asignacion=:id_asignacion,
                  dia_semana=:dia_semana,
                  hora_inicio=:hora_inicio,
                  hora_fin=:hora_fin,
                  aula=:aula
              WHERE id_horario=:id_horario";

    $stmt = $this->conn->prepare($query);

    $stmt->bindParam(":id_asignacion", $this->id_asignacion);
    $stmt->bindParam(":dia_semana", $this->dia_semana);
    $stmt->bindParam(":hora_inicio", $this->hora_inicio);
    $stmt->bindParam(":hora_fin", $this->hora_fin);
    $stmt->bindParam(":aula", $this->aula);
    $stmt->bindParam(":id_horario", $this->id_horario);

    return $stmt->execute();
}
public function desactivar() {
    $query = "UPDATE " . $this->table_name . "
              SET activo = 0
              WHERE id_horario = :id";

    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(":id", $this->id_horario);

    return $stmt->execute();
}
}

?>