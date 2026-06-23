<?php
// src/classes/Horario.php

class Horario {
    private $conn;
    private $table_name = "horarios";

    // Propiedades según tu tabla SQL
    public $id_asignacion;
    public $grado;
    public $dia_semana;
    public $hora_inicio;
    public $hora_fin;
    public $aula;
    public $id_horario;
    public function __construct($db) {
        $this->conn = $db;
    }

    public function crear() {
    $query = "INSERT INTO horarios
              SET id_asignacion=:id_asignacion,
                  grado=:grado,
                  dia_semana=:dia_semana,
                  hora_inicio=:hora_inicio,
                  hora_fin=:hora_fin,
                  aula=:aula";

    $stmt = $this->conn->prepare($query);

    $stmt->bindParam(":id_asignacion", $this->id_asignacion);
    $stmt->bindParam(":grado", $this->grado);
    $stmt->bindParam(":dia_semana", $this->dia_semana);
    $stmt->bindParam(":hora_inicio", $this->hora_inicio);
    $stmt->bindParam(":hora_fin", $this->hora_fin);
    $stmt->bindParam(":aula", $this->aula);

    return $stmt->execute();
}
    public function leer() {
    $query = "SELECT 
                h.id_horario,
                h.grado,
                h.dia_semana,
                h.hora_inicio,
                h.hora_fin,
                h.aula,
                m.nombre AS materia,
                CONCAT(p.nombre, ' ', p.apellido) AS profesor
              FROM horarios h
              LEFT JOIN asignacion_docente ad ON h.id_asignacion = ad.id_asignacion
              LEFT JOIN materias m ON ad.id_materia = m.id_materia
              LEFT JOIN profesores p ON ad.id_profesor = p.id_profesor
              WHERE h.activo = 1
              ORDER BY h.grado, h.dia_semana, h.hora_inicio";

    $stmt = $this->conn->prepare($query);
    $stmt->execute();
    return $stmt;
}
public function leerDesactivados() {
    $query = "SELECT 
                h.id_horario,
                h.id_asignacion,
                h.grado,
                h.dia_semana,
                h.hora_inicio,
                h.hora_fin,
                h.aula,
                h.activo,
                m.nombre AS materia,
                CONCAT(p.nombre, ' ', p.apellido) AS profesor
              FROM horarios h
              LEFT JOIN asignacion_docente ad ON h.id_asignacion = ad.id_asignacion
              LEFT JOIN materias m ON ad.id_materia = m.id_materia
              LEFT JOIN profesores p ON ad.id_profesor = p.id_profesor
              WHERE h.activo = 0
              ORDER BY h.grado, h.dia_semana, h.hora_inicio";

    $stmt = $this->conn->prepare($query);
    $stmt->execute();
    return $stmt;
}
public function actualizar() {
    $query = "UPDATE " . $this->table_name . "
              SET id_asignacion=:id_asignacion,
                  grado=:grado,
                  dia_semana=:dia_semana,
                  hora_inicio=:hora_inicio,
                  hora_fin=:hora_fin,
                  aula=:aula
              WHERE id_horario=:id_horario";

    $stmt = $this->conn->prepare($query);

    $stmt->bindParam(":id_asignacion", $this->id_asignacion);
    $stmt->bindParam(":grado", $this->grado);
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
public function restaurar() {
    $query = "UPDATE " . $this->table_name . "
              SET activo = 1
              WHERE id_horario = :id";

    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(":id", $this->id_horario);

    return $stmt->execute();
}
public function eliminar() {
    $query = "DELETE FROM " . $this->table_name . "
              WHERE id_horario = :id";

    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(":id", $this->id_horario);
    $stmt->execute();

    return $stmt->rowCount() > 0;
}
}

?>
