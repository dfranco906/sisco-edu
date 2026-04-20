<?php
// src/classes/Profesor.php

class Profesor {
    private $conn;
    private $table_name = "profesores";

    // Propiedades de la clase (Mapeadas de la tabla 'profesores')
    public $nombre;
    public $apellido;
    public $cedula_identidad;
    public $huella_id;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Lógica para insertar en la BD
    public function crear() {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET nombre=:nombre, 
                      apellido=:apellido, 
                      cedula_identidad=:cedula, 
                      huella_id=:huella";

        $stmt = $this->conn->prepare($query);

        // Sanitización (Limpieza de datos para evitar inyecciones de código)
        $this->nombre = htmlspecialchars(strip_tags($this->nombre));
        $this->apellido = htmlspecialchars(strip_tags($this->apellido));
        $this->cedula_identidad = htmlspecialchars(strip_tags($this->cedula_identidad));
        // Si no hay huella, enviamos NULL para que no rompa la base de datos
        $this->huella_id = !empty($this->huella_id) ? $this->huella_id : NULL;

        // Binding (Asignar los valores a los parámetros :nombre, etc.)
        $stmt->bindParam(":nombre", $this->nombre);
        $stmt->bindParam(":apellido", $this->apellido);
        $stmt->bindParam(":cedula", $this->cedula_identidad);
        $stmt->bindParam(":huella", $this->huella_id);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }
}
?>