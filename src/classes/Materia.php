<?php
// src/classes/Materia.php

class Materia {
    private $conn;
    private $table_name = "materias";

    public $id_materia;
    public $nombre;
    public $descripcion;
    public $carga_horaria_semanal = 0;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function crear() {
        $query = "INSERT INTO " . $this->table_name . " (nombre, descripcion, carga_horaria_semanal)
                  VALUES (:nombre, :descripcion, :carga)";

        $stmt = $this->conn->prepare($query);

        $this->nombre = htmlspecialchars(strip_tags((string)$this->nombre));
        $this->descripcion = htmlspecialchars(strip_tags((string)$this->descripcion));
        $carga = (int)($this->carga_horaria_semanal ?? 0);

        $stmt->bindParam(":nombre", $this->nombre);
        $stmt->bindParam(":descripcion", $this->descripcion);
        $stmt->bindParam(":carga", $carga, PDO::PARAM_INT);

        return $stmt->execute();
    }

    public function leer() {
        $query = "SELECT m.*,
                         (SELECT COUNT(*) FROM asignacion_docente ad WHERE ad.id_materia = m.id_materia AND ad.activo = 1) AS total_asignaciones
                  FROM " . $this->table_name . " m
                  WHERE m.activo = 1
                  ORDER BY m.nombre ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function leerDesactivadas() {
        $query = "SELECT m.*,
                         (SELECT COUNT(*) FROM asignacion_docente ad WHERE ad.id_materia = m.id_materia AND ad.activo = 1) AS total_asignaciones
                  FROM " . $this->table_name . " m
                  WHERE m.activo = 0
                  ORDER BY m.nombre ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function obtenerDetalle(int $id_materia): ?array {
        $stmt = $this->conn->prepare("SELECT * FROM {$this->table_name} WHERE id_materia = :id");
        $stmt->execute([':id' => $id_materia]);
        $materia = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$materia) {
            return null;
        }

        // Obtener las asignaciones docentes reales donde se imparte esta materia y su carga horaria por curso
        $stmtAsig = $this->conn->prepare("
            SELECT ad.id_asignacion, 
                   COALESCE(ad.carga_horaria, 0) AS carga_horaria, 
                   ad.anho_lectivo,
                   p.id_profesor,
                   p.nombre AS profesor_nombre, 
                   p.apellido AS profesor_apellido,
                   g.id_grado,
                   g.nombre AS grado_nombre, 
                   g.seccion,
                   t.nombre AS turno_nombre
            FROM asignacion_docente ad
            JOIN profesores p ON ad.id_profesor = p.id_profesor
            JOIN grados g ON ad.id_grado = g.id_grado
            LEFT JOIN turnos t ON g.id_turno = t.id_turno
            WHERE ad.id_materia = :id AND ad.activo = 1
            ORDER BY g.nombre ASC
        ");
        $stmtAsig->execute([':id' => $id_materia]);
        $materia['asignaciones'] = $stmtAsig->fetchAll(PDO::FETCH_ASSOC);

        return $materia;
    }

    public function actualizar() {
        $query = "UPDATE " . $this->table_name . "
                  SET nombre=:nombre,
                      descripcion=:descripcion,
                      carga_horaria_semanal=:carga
                  WHERE id_materia=:id";

        $stmt = $this->conn->prepare($query);

        $this->nombre = htmlspecialchars(strip_tags((string)$this->nombre));
        $this->descripcion = htmlspecialchars(strip_tags((string)$this->descripcion));
        $carga = (int)($this->carga_horaria_semanal ?? 0);

        $stmt->bindParam(":nombre", $this->nombre);
        $stmt->bindParam(":descripcion", $this->descripcion);
        $stmt->bindParam(":carga", $carga, PDO::PARAM_INT);
        $stmt->bindParam(":id", $this->id_materia, PDO::PARAM_INT);

        return $stmt->execute();
    }

    public function desactivar() {
        $query = "UPDATE " . $this->table_name . "
                  SET activo = 0
                  WHERE id_materia = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id_materia, PDO::PARAM_INT);

        return $stmt->execute();
    }

    public function restaurar() {
        $query = "UPDATE " . $this->table_name . "
                  SET activo = 1
                  WHERE id_materia = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id_materia, PDO::PARAM_INT);

        return $stmt->execute();
    }

    public function contarDependencias() {
        $query = "SELECT COUNT(*) FROM asignacion_docente WHERE id_materia = :id AND activo = 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id_materia, PDO::PARAM_INT);
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }

    public function eliminar() {
        $query = "DELETE FROM " . $this->table_name . "
                  WHERE id_materia = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id_materia, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }
}
