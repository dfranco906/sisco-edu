<?php
class Profesor {
    private $conn;
    private $table_name = "profesores";

    public $nombre;
    public $apellido;
    public $cedula_identidad;
    public $user_id_global;
    public $id_profesor;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function crear() {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET nombre=:nombre, 
                      apellido=:apellido, 
                      cedula_identidad=:cedula, 
                      user_id_global=:user_id_global";

        $stmt = $this->conn->prepare($query);

        $this->nombre = htmlspecialchars(strip_tags($this->nombre));
        $this->apellido = htmlspecialchars(strip_tags($this->apellido));
        $this->cedula_identidad = htmlspecialchars(strip_tags($this->cedula_identidad));
        $this->user_id_global = !empty($this->user_id_global) ? $this->user_id_global : NULL;

        $stmt->bindParam(":nombre", $this->nombre);
        $stmt->bindParam(":apellido", $this->apellido);
        $stmt->bindParam(":cedula", $this->cedula_identidad);
        $stmt->bindParam(":user_id_global", $this->user_id_global);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    public function leer() {
        $query = "SELECT 
                    p.id_profesor,
                    p.nombre,
                    p.apellido,
                    p.cedula_identidad,
                    p.user_id_global,
                    CASE
                        WHEN p.huella_id IS NOT NULL OR EXISTS (
                            SELECT 1
                            FROM huellas_templates ht
                            WHERE ht.user_id_global = p.user_id_global
                              AND ht.activo = 1
                        ) THEN 'Registrada'
                        ELSE 'Pendiente'
                    END AS huella,
                    p.activo,
                    CONCAT(p.nombre, ' ', p.apellido) AS nombre_completo
                  FROM profesores p
                  WHERE p.activo = 1
                  ORDER BY p.nombre ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function leerDesactivados() {
        $query = "SELECT 
                    p.id_profesor,
                    p.nombre,
                    p.apellido,
                    p.cedula_identidad,
                    p.user_id_global,
                    CASE
                        WHEN p.huella_id IS NOT NULL OR EXISTS (
                            SELECT 1
                            FROM huellas_templates ht
                            WHERE ht.user_id_global = p.user_id_global
                              AND ht.activo = 1
                        ) THEN 'Registrada'
                        ELSE 'Pendiente'
                    END AS huella,
                    p.activo,
                    CONCAT(p.nombre, ' ', p.apellido) AS nombre_completo
                  FROM profesores p
                  WHERE p.activo = 0
                  ORDER BY p.nombre ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function actualizar() {
        $query = "UPDATE " . $this->table_name . "
                  SET nombre = :nombre,
                      apellido = :apellido,
                      cedula_identidad = :cedula,
                      user_id_global = :user_id_global
                  WHERE id_profesor = :id";

        $stmt = $this->conn->prepare($query);

        $this->nombre = htmlspecialchars(strip_tags($this->nombre));
        $this->apellido = htmlspecialchars(strip_tags($this->apellido));
        $this->cedula_identidad = htmlspecialchars(strip_tags($this->cedula_identidad));
        $this->user_id_global = !empty($this->user_id_global) ? $this->user_id_global : NULL;
        $this->id_profesor = htmlspecialchars(strip_tags($this->id_profesor));

        $stmt->bindParam(":nombre", $this->nombre);
        $stmt->bindParam(":apellido", $this->apellido);
        $stmt->bindParam(":cedula", $this->cedula_identidad);
        $stmt->bindParam(":user_id_global", $this->user_id_global);
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

    public function restaurar() {
        $query = "UPDATE " . $this->table_name . "
                  SET activo = 1
                  WHERE id_profesor = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id_profesor);

        return $stmt->execute();
    }

    public function contarDependencias() {
        $query = "SELECT
                    (SELECT COUNT(*) FROM asignacion_docente WHERE id_profesor=:id1) +
                    (SELECT COUNT(*) FROM asistencias_profesores WHERE id_profesor=:id2) +
                    (SELECT COUNT(*) FROM huellas_templates WHERE id_profesor=:id3)";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            ":id1" => $this->id_profesor,
            ":id2" => $this->id_profesor,
            ":id3" => $this->id_profesor
        ]);

        return (int) $stmt->fetchColumn();
    }

    public function eliminar() {
        $query = "DELETE FROM " . $this->table_name . "
                  WHERE id_profesor = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id_profesor);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }
}
?>
