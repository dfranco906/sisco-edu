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
    public $id_profesor;

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
    // ... tu código anterior (constructor y método crear) ...

    // Lógica para LEER (READ) todos los profesores de la BD
    public function leer() {
        // Escribimos la consulta SQL (traemos todos los campos)
        // ORDER BY id_profesor DESC los ordena del más nuevo al más viejo
        $query = "SELECT id_profesor, nombre, apellido, cedula_identidad, huella_id, fecha_registro, activo
          FROM " . $this->table_name . "
          WHERE activo = 1
          ORDER BY id_profesor DESC";

        // Preparamos la consulta
        $stmt = $this->conn->prepare($query);

        // Ejecutamos la consulta
        $stmt->execute();

        // Retornamos el "statement" (la declaración con los datos) para que la API lo procese
        return $stmt;
    }
    // ✅ UPDATE
public function actualizar() {
    $query = "UPDATE " . $this->table_name . "
              SET nombre = :nombre,
                  apellido = :apellido,
                  cedula_identidad = :cedula,
                  huella_id = :huella
              WHERE id_profesor = :id";

    $stmt = $this->conn->prepare($query);

    // Sanitizar
    $this->nombre = htmlspecialchars(strip_tags($this->nombre));
    $this->apellido = htmlspecialchars(strip_tags($this->apellido));
    $this->cedula_identidad = htmlspecialchars(strip_tags($this->cedula_identidad));
    $this->huella_id = !empty($this->huella_id) ? $this->huella_id : NULL;
    $this->id_profesor = htmlspecialchars(strip_tags($this->id_profesor));

    // Bind
    $stmt->bindParam(":nombre", $this->nombre);
    $stmt->bindParam(":apellido", $this->apellido);
    $stmt->bindParam(":cedula", $this->cedula_identidad);
    $stmt->bindParam(":huella", $this->huella_id);
    $stmt->bindParam(":id", $this->id_profesor);

    return $stmt->execute();
}
public function desactivar() {
    $query = "UPDATE " . $this->table_name . "
              SET activo = 0
              WHERE id_profesor = :id";

    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(":id", $this->id_profesor);

    return $stmt->execute();
}
}
?>